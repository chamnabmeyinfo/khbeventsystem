<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Models\Book;
use App\Models\BookingTimeline;
use App\Models\Booth;
use App\Models\User;
use App\Repositories\BookingRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private BookingRepository $repository
    ) {}

    /**
     * Create a new booking
     */
    public function createBooking(array $data): Book
    {
        DB::beginTransaction();

        try {
            // Check if all booths are available
            $availability = $this->repository->checkBoothsAvailability($data['booth_ids']);
            if (! $availability['available']) {
                $boothNumbers = $availability['unavailable_booths']->pluck('booth_number')->implode(', ');
                throw ValidationException::withMessages([
                    'booth_ids' => ['Some selected booths are not available: '.$boothNumbers],
                ]);
            }

            // Verify all booths exist
            if (! $this->repository->verifyBoothsExist($data['booth_ids'])) {
                throw ValidationException::withMessages([
                    'booth_ids' => ['One or more selected booths do not exist.'],
                ]);
            }

            // Get booths and verify they're from same floor plan
            $booths = $this->repository->getBoothsForBooking($data['booth_ids']);
            $floorPlanIds = $booths->pluck('floor_plan_id')->unique()->filter();

            if ($floorPlanIds->count() > 1) {
                throw ValidationException::withMessages([
                    'booth_ids' => ['All booths must be from the same floor plan.'],
                ]);
            }

            // Get floor plan and event from first booth
            $firstBooth = $booths->first();
            $floorPlanId = $firstBooth ? $firstBooth->floor_plan_id : null;
            $eventId = null;

            if ($floorPlanId) {
                $floorPlan = \App\Models\FloorPlan::find($floorPlanId);
                $eventId = $floorPlan ? $floorPlan->event_id : null;
            }

            // Calculate total amount
            $totalAmount = $this->repository->calculateTotalAmount($data['booth_ids']);

            // Use the user's primary key (id), not auth identifier (username) - book.userid is an integer column
            $userId = auth()->user() ? auth()->user()->getKey() : null;

            // Prepare booking data
            $bookingData = [
                'clientid' => $data['clientid'],
                'boothid' => json_encode($data['booth_ids']),
                'date_book' => $data['date_book'] ?? now(),
                'userid' => $userId,
                'type' => $data['type'] ?? 1,
                'floor_plan_id' => $floorPlanId,
                'event_id' => $eventId ?? $data['event_id'] ?? null,
                'affiliate_user_id' => $data['affiliate_user_id'] ?? null,
                'status' => Book::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
                'payment_due_date' => $data['payment_due_date'] ?? null,
            ];

            // Create booking
            $booking = $this->repository->create($bookingData);

            // Update booth statuses and link to booking
            foreach ($booths as $booth) {
                $booth->update([
                    'status' => Booth::STATUS_RESERVED,
                    'client_id' => $data['clientid'],
                    'userid' => $userId,
                    'bookid' => $booking->id,
                ]);
            }

            $this->createTimelineEntry($booking, 'created', 'Booking created');

            $activity = null;
            try {
                $activity = ActivityLogger::log('booking.created', $booking, 'Booking created: #'.$booking->id);
            } catch (\Exception $e) {
                Log::error('Failed to log booking creation activity: '.$e->getMessage());
            }

            try {
                NotificationService::notifyBookingAction('created', $booking, $userId, $activity?->id);
            } catch (\Exception $e) {
                Log::error('Failed to send booking creation notification: '.$e->getMessage());
            }

            DB::commit();

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Restore a deleted booking from a snapshot (for Undo).
     * Creates a new Book and re-links the same booths; skips availability check.
     */
    public function restoreBooking(array $snapshot): Book
    {
        DB::beginTransaction();

        try {
            $boothIds = $snapshot['booth_ids'] ?? [];
            if (empty($boothIds)) {
                throw ValidationException::withMessages(['booth_ids' => ['At least one booth is required to restore.']]);
            }

            $booths = Booth::whereIn('id', $boothIds)->get();
            if ($booths->count() !== count($boothIds)) {
                throw ValidationException::withMessages(['booth_ids' => ['One or more booths no longer exist.']]);
            }

            $totalAmount = $snapshot['total_amount'] ?? $this->repository->calculateTotalAmount($boothIds);
            $userId = $snapshot['userid'] ?? (auth()->user() ? auth()->user()->getKey() : null);

            $bookingData = [
                'clientid' => $snapshot['clientid'],
                'boothid' => json_encode(array_values($boothIds)),
                'date_book' => $snapshot['date_book'] ?? now(),
                'userid' => $userId,
                'type' => $snapshot['type'] ?? 1,
                'floor_plan_id' => $snapshot['floor_plan_id'] ?? $booths->first()->floor_plan_id,
                'event_id' => $snapshot['event_id'] ?? null,
                'affiliate_user_id' => $snapshot['affiliate_user_id'] ?? null,
                'status' => $snapshot['status'] ?? Book::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'paid_amount' => $snapshot['paid_amount'] ?? 0,
                'balance_amount' => $totalAmount - ($snapshot['paid_amount'] ?? 0),
                'notes' => $snapshot['notes'] ?? null,
                'payment_due_date' => isset($snapshot['payment_due_date']) ? $snapshot['payment_due_date'] : null,
            ];

            $booking = $this->repository->create($bookingData);

            $boothStatus = ($snapshot['type'] ?? 1) == 2 ? Booth::STATUS_CONFIRMED : Booth::STATUS_RESERVED;
            foreach ($booths as $booth) {
                $booth->update([
                    'status' => $boothStatus,
                    'client_id' => $snapshot['clientid'],
                    'userid' => $userId,
                    'bookid' => $booking->id,
                ]);
            }

            $this->createTimelineEntry($booking, 'restored', 'Booking restored (Undo)');

            DB::commit();

            return $booking;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a booking
     */
    public function updateBooking(Book $booking, array $data): Book
    {
        DB::beginTransaction();

        try {
            $oldStatus = $booking->status;
            $oldUserId = $booking->userid;

            // If booth_ids are being updated
            if (isset($data['booth_ids'])) {
                // Get current booth IDs
                $currentBoothIds = json_decode($booking->boothid, true) ?? [];
                $newBoothIds = $data['booth_ids'];

                // Find booths to release (in current but not in new)
                $boothsToRelease = array_diff($currentBoothIds, $newBoothIds);

                // Find booths to reserve (in new but not in current)
                $boothsToReserve = array_diff($newBoothIds, $currentBoothIds);

                // Check if new booths are available (only check booths that need to be reserved)
                if (! empty($boothsToReserve)) {
                    $availability = $this->repository->checkBoothsAvailability($boothsToReserve);
                    if (! $availability['available']) {
                        $boothNumbers = $availability['unavailable_booths']->pluck('booth_number')->implode(', ');
                        throw ValidationException::withMessages([
                            'booth_ids' => ['Some selected booths are not available: '.$boothNumbers],
                        ]);
                    }
                }

                // Verify all new booths exist
                if (! $this->repository->verifyBoothsExist($newBoothIds)) {
                    throw ValidationException::withMessages([
                        'booth_ids' => ['One or more selected booths do not exist.'],
                    ]);
                }

                // Release old booths - but NOT if they are PAID
                if (! empty($boothsToRelease)) {
                    $boothsToReleaseModels = Booth::whereIn('id', $boothsToRelease)->get();
                    $paidBooths = [];

                    foreach ($boothsToReleaseModels as $booth) {
                        if ($booth->status === Booth::STATUS_PAID) {
                            $paidBooths[] = $booth->booth_number;
                        } else {
                            // Only release non-paid booths
                            if ($booth->bookid == $booking->id) {
                                $booth->update([
                                    'status' => Booth::STATUS_AVAILABLE,
                                    'client_id' => null,
                                    'userid' => null,
                                    'bookid' => null,
                                ]);
                            }
                        }
                    }

                    if (! empty($paidBooths)) {
                        throw ValidationException::withMessages([
                            'booth_ids' => ['Cannot remove paid booths from booking: '.implode(', ', $paidBooths).'. Please refund payment first.'],
                        ]);
                    }
                }

                // Reserve new booths - use lock to prevent race conditions
                if (! empty($boothsToReserve)) {
                    $updated = Booth::whereIn('id', $boothsToReserve)
                        ->whereIn('status', [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN])
                        ->lockForUpdate()
                        ->update([
                            'status' => Booth::STATUS_RESERVED,
                            'client_id' => $data['clientid'] ?? $booking->clientid,
                            'userid' => $data['userid'] ?? $booking->userid,
                            'bookid' => $booking->id,
                        ]);

                    // Verify all booths were updated
                    if ($updated !== count($boothsToReserve)) {
                        throw ValidationException::withMessages([
                            'booth_ids' => ['Some booths became unavailable during update. Please try again.'],
                        ]);
                    }
                }

                // Update existing booths with new client if client changed
                $boothsToKeep = array_intersect($currentBoothIds, $newBoothIds);
                if (! empty($boothsToKeep) && isset($data['clientid']) && $booking->clientid != $data['clientid']) {
                    Booth::whereIn('id', $boothsToKeep)->update([
                        'client_id' => $data['clientid'],
                    ]);
                }

                // Recalculate total amount
                $data['total_amount'] = $this->repository->calculateTotalAmount($newBoothIds);
                $data['balance_amount'] = ($data['total_amount'] ?? $booking->total_amount) - ($data['paid_amount'] ?? $booking->paid_amount ?? 0);
                $data['boothid'] = json_encode($newBoothIds);
            }

            // Handle optional fields based on schema
            if (\Illuminate\Support\Facades\Schema::hasColumn('book', 'status') && ! isset($data['status'])) {
                $data['status'] = $booking->status ?? Book::STATUS_PENDING;
            }

            // Update booking
            $this->repository->update($booking, $data);
            $booking->refresh();

            // Sync booth user ownership if userid was updated
            if (isset($data['userid']) && ! empty($data['userid']) && (int) $data['userid'] !== (int) $oldUserId) {
                $newUserId = (int) $data['userid'];
                Booth::where('bookid', $booking->id)->update(['userid' => $newUserId]);

                $oldUser = User::find($oldUserId);
                $newUser = User::find($newUserId);
                $oldName = $oldUser?->username ?? ($oldUserId ? 'User #'.$oldUserId : 'System');
                $newName = $newUser?->username ?? 'User #'.$newUserId;

                $this->createTimelineEntry(
                    $booking,
                    'reassigned',
                    "Booking reassigned from {$oldName} to {$newName}"
                );
            }

            // Recalculate amounts after booth changes (if model has this method)
            if (method_exists($booking, 'updatePaymentAmounts')) {
                $booking->updatePaymentAmounts();
            }

            // Sync booths status & timeline entry if status changed
            if (isset($data['status']) && $oldStatus != $data['status']) {
                $this->syncBoothsStatusForBooking($booking, (int) $data['status']);
                $this->createTimelineEntry($booking, 'status_changed', 'Status changed from '.$oldStatus.' to '.$data['status']);
            }

            $activity = null;
            try {
                $activity = ActivityLogger::log('booking.updated', $booking, 'Booking updated: #'.$booking->id);
            } catch (\Exception $e) {
                Log::error('Failed to log booking update activity: '.$e->getMessage());
            }

            try {
                if (isset($data['status']) && $oldStatus != $data['status']) {
                    NotificationService::notifyBookingAction('status_changed', $booking, $booking->userid, $activity?->id);
                } else {
                    NotificationService::notifyBookingAction('updated', $booking, $booking->userid, $activity?->id);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send booking update notification: '.$e->getMessage());
            }

            DB::commit();

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update booking status
     */
    public function updateBookingStatus(Book $booking, int $status, ?string $notes = null): Book
    {
        DB::beginTransaction();

        try {
            $oldStatus = $booking->status;

            $this->repository->update($booking, [
                'status' => $status,
                'notes' => $notes ?? $booking->notes,
            ]);

            $booking->refresh();

            // Sync linked booths status
            $this->syncBoothsStatusForBooking($booking, (int) $status);

            $this->createTimelineEntry($booking, 'status_changed', 'Status changed from '.$oldStatus.' to '.$status);

            $activity = null;
            try {
                $activity = ActivityLogger::log('booking.status_changed', $booking, 'Booking status changed: #'.$booking->id);
            } catch (\Exception $e) {
                Log::error('Failed to log booking status change activity: '.$e->getMessage());
            }

            try {
                NotificationService::notifyBookingAction('status_changed', $booking, $booking->userid, $activity?->id);
            } catch (\Exception $e) {
                Log::error('Failed to send booking status change notification: '.$e->getMessage());
            }

            DB::commit();

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reassign booking and all linked booths to a new team member
     */
    public function reassignBooking(Book $booking, int $newUserId): Book
    {
        DB::beginTransaction();

        try {
            $oldUserId = $booking->userid;
            $oldUser = User::find($oldUserId);
            $newUser = User::findOrFail($newUserId);

            $this->repository->update($booking, [
                'userid' => $newUserId,
            ]);

            $booking->refresh();

            // Update all linked booths for this booking
            $boothIds = json_decode($booking->boothid, true) ?? [];
            if (! empty($boothIds)) {
                Booth::whereIn('id', $boothIds)
                    ->where('bookid', $booking->id)
                    ->update(['userid' => $newUserId]);
            } else {
                Booth::where('bookid', $booking->id)
                    ->update(['userid' => $newUserId]);
            }

            $oldName = $oldUser?->username ?? ($oldUserId ? 'User #'.$oldUserId : 'System');
            $newName = $newUser->username ?? 'User #'.$newUserId;

            $this->createTimelineEntry(
                $booking,
                'reassigned',
                "Booking reassigned from {$oldName} to {$newName}"
            );

            $activity = null;
            try {
                $activity = ActivityLogger::log(
                    'booking.reassigned',
                    $booking,
                    "Booking #{$booking->id} reassigned to {$newName} (was {$oldName})"
                );
            } catch (\Exception $e) {
                Log::error('Failed to log booking reassignment activity: '.$e->getMessage());
            }

            try {
                NotificationService::notifyBookingAction('updated', $booking, $newUserId, $activity?->id);
            } catch (\Exception $e) {
                Log::error('Failed to send booking reassignment notification: '.$e->getMessage());
            }

            DB::commit();

            $booking->load('user');

            return $booking;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sync linked booth statuses with booking status
     */
    private function syncBoothsStatusForBooking(Book $booking, int $status): void
    {
        $boothIds = json_decode($booking->boothid, true) ?? [];
        if (empty($boothIds)) {
            return;
        }

        $booths = Booth::whereIn('id', $boothIds)->where('bookid', $booking->id)->get();
        if ($booths->isEmpty()) {
            return;
        }

        if ($status === Book::STATUS_PAID) {
            Booth::whereIn('id', $booths->pluck('id'))->update(['status' => Booth::STATUS_PAID]);
        } elseif ($status === Book::STATUS_CONFIRMED) {
            Booth::whereIn('id', $booths->pluck('id'))->update(['status' => Booth::STATUS_CONFIRMED]);
        } elseif ($status === Book::STATUS_RESERVED || $status === Book::STATUS_PENDING) {
            Booth::whereIn('id', $booths->pluck('id'))->update(['status' => Booth::STATUS_RESERVED]);
        } elseif ($status === Book::STATUS_CANCELLED) {
            foreach ($booths as $booth) {
                if ($booth->status !== Booth::STATUS_PAID) {
                    $booth->update([
                        'status' => Booth::STATUS_AVAILABLE,
                        'client_id' => null,
                        'userid' => null,
                        'bookid' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Delete a booking
     */
    public function deleteBooking(Book $booking): bool
    {
        DB::beginTransaction();

        try {
            // Release booths (but NOT if they are PAID - those are handled in controller)
            $boothIds = json_decode($booking->boothid, true) ?? [];
            if (! empty($boothIds)) {
                $booths = Booth::whereIn('id', $boothIds)->get();
                foreach ($booths as $booth) {
                    if ($booth->bookid == $booking->id && $booth->status !== Booth::STATUS_PAID) {
                        $booth->update([
                            'status' => Booth::STATUS_AVAILABLE,
                            'client_id' => null,
                            'userid' => null,
                            'bookid' => null,
                        ]);
                    }
                }
            }

            $activity = null;
            try {
                $activity = ActivityLogger::log('booking.deleted', $booking, 'Booking deleted: #'.$booking->id);
            } catch (\Exception $e) {
                Log::error('Failed to log booking deletion activity: '.$e->getMessage());
            }

            $deleted = $this->repository->delete($booking);

            try {
                NotificationService::notifyBookingAction('deleted', $booking, $booking->userid, $activity?->id);
            } catch (\Exception $e) {
                Log::error('Failed to send booking deletion notification: '.$e->getMessage());
            }

            DB::commit();

            return $deleted;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Book a single booth with client information
     */
    public function bookSingleBooth(array $data, ClientService $clientService, \Illuminate\Http\Request $request): array
    {
        DB::beginTransaction();

        try {
            // Normalize sex to integer (1=Male, 2=Female, 3=Other)
            $sex = null;
            if (isset($data['sex'])) {
                $sexVal = $data['sex'];
                if (is_numeric($sexVal)) {
                    $sexInt = (int) $sexVal;
                    if (in_array($sexInt, [1, 2, 3])) {
                        $sex = $sexInt;
                    }
                } else {
                    $sexStr = strtolower(trim((string) $sexVal));
                    if (in_array($sexStr, ['male', 'm'])) {
                        $sex = 1;
                    } elseif (in_array($sexStr, ['female', 'f'])) {
                        $sex = 2;
                    } elseif (in_array($sexStr, ['other', 'o'])) {
                        $sex = 3;
                    }
                }
            }

            // Find the booth with lock to prevent race conditions
            $booth = Booth::where('id', $data['booth_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Check if booth is available before booking
            if (! in_array($booth->status, [Booth::STATUS_AVAILABLE, Booth::STATUS_HIDDEN])) {
                throw ValidationException::withMessages([
                    'booth_id' => ['Booth is not available for booking. Current status: '.$booth->getStatusLabel()],
                ]);
            }

            // Check if client_id is provided (from search/selection)
            $client = null;
            if (isset($data['client_id']) && ! empty($data['client_id'])) {
                $client = \App\Models\Client::find($data['client_id']);
            }

            // If client not found by ID, check by email or phone number
            if (! $client) {
                $client = \App\Models\Client::where('email', $data['email'])
                    ->orWhere('phone_number', $data['phone_number'])
                    ->first();
            }

            // Create or update client
            $clientData = [
                'name' => $data['name'],
                'sex' => $sex ?? null,
                'company' => $data['company'],
                'position' => $data['position'] ?? null,
                'phone_number' => $data['phone_number'],
                'email' => $data['email'],
                'address' => $data['address'],
                'tax_id' => $data['tax_id'] ?? null,
                'website' => $data['website'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if ($client) {
                // Update existing client with latest information
                $clientService->updateClient($client, $clientData);
                $client->refresh();
            } else {
                // Create new client
                $clientResult = $clientService->createClient($clientData);
                $client = $clientResult['client'];
            }

            // Get user ID
            $userId = auth()->user()->id ?? null;
            if ($userId) {
                $userId = (int) $userId;
            }

            // Map status to booking type OR use provided type
            $bookingType = $data['type'] ?? null;
            if (! $bookingType) {
                if ($data['status'] == Booth::STATUS_CONFIRMED || $data['status'] == Booth::STATUS_PAID) {
                    $bookingType = 2; // Special/Confirmed or Paid
                } else {
                    $bookingType = 1; // Regular/Reserved
                }
            }

            // Ensure status matches booking type for consistency
            $finalStatus = $data['status'];
            if ($bookingType == 1 || $bookingType == 3) {
                if ($finalStatus != Booth::STATUS_RESERVED) {
                    $finalStatus = Booth::STATUS_RESERVED;
                }
            } elseif ($bookingType == 2) {
                if ($finalStatus == Booth::STATUS_PAID) {
                    $finalStatus = Booth::STATUS_PAID;
                } else {
                    $finalStatus = Booth::STATUS_CONFIRMED;
                }
            }

            // Get floor plan and event from booth
            $floorPlanId = $booth->floor_plan_id;
            $eventId = null;

            if ($floorPlanId) {
                $floorPlan = \App\Models\FloorPlan::find($floorPlanId);
                $eventId = $floorPlan ? $floorPlan->event_id : null;
            }

            // Get affiliate user ID from cookie or session (first-touch wins)
            $affiliateUserId = null;
            $cookieName = 'affiliate_fp_'.$floorPlanId;

            $cookieData = $request->cookie($cookieName);
            if ($cookieData) {
                $decoded = json_decode($cookieData, true);
                $cookieFloorPlanId = $decoded['floor_plan_id'] ?? null;
                $cookieExpiresAt = $decoded['expires_at'] ?? null;
                if ($cookieFloorPlanId == $floorPlanId && $cookieExpiresAt && time() < (int) $cookieExpiresAt) {
                    $affiliateUserId = (int) ($decoded['affiliate_user_id'] ?? 0);
                }
            }

            // Fallback to existing session if cookie missing but still valid
            if (! $affiliateUserId && session()->has('affiliate_user_id') && session('affiliate_floor_plan_id') == $floorPlanId) {
                if (session()->has('affiliate_expires_at') && now()->lt(session('affiliate_expires_at'))) {
                    $affiliateUserId = (int) session('affiliate_user_id');
                }
            }

            // Create Book record to link booking with floor plan and event
            $book = Book::create([
                'event_id' => $eventId,
                'floor_plan_id' => $floorPlanId,
                'clientid' => $client->id,
                'boothid' => json_encode([$booth->id]),
                'date_book' => now(),
                'userid' => $userId,
                'affiliate_user_id' => $affiliateUserId,
                'type' => $bookingType,
            ]);

            // Update booth with client, status, and book ID
            $booth->update([
                'client_id' => $client->id,
                'status' => $finalStatus,
                'userid' => $userId,
                'bookid' => $book->id,
            ]);

            DB::commit();

            return [
                'booth_id' => $booth->id,
                'booth_number' => $booth->booth_number,
                'book_id' => $book->id,
                'client_id' => $client->id,
                'client_name' => $client->name,
                'client_company' => $client->company,
                'status' => $booth->status,
                'booking_type' => $bookingType,
                'floor_plan_id' => $floorPlanId,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check which booths have active bookings
     */
    public function checkBoothsBookings(array $boothIds): array
    {
        $boothIds = array_unique(array_map('intval', $boothIds));
        $boothsWithBookings = [];

        // Batch load all booths with their clients to prevent N+1 queries
        $booths = Booth::with('client')
            ->whereIn('id', $boothIds)
            ->get()
            ->keyBy('id');

        // Batch load all books for booths that have bookings
        $bookIds = $booths->pluck('bookid')->filter()->unique();
        $books = Book::whereIn('id', $bookIds)->get()->keyBy('id');

        // Process each booth ID
        foreach ($boothIds as $boothId) {
            $booth = $booths->get($boothId);
            if (! $booth) {
                continue;
            }

            if ($booth->bookid && $books->has($booth->bookid)) {
                $boothsWithBookings[] = [
                    'booth_id' => $booth->id,
                    'booth_number' => $booth->booth_number,
                    'book_id' => $booth->bookid,
                    'client_company' => $booth->client ? $booth->client->company : 'Unknown',
                ];
            }
        }

        return [
            'all_clear' => count($boothsWithBookings) === 0,
            'booths_with_bookings' => $boothsWithBookings,
        ];
    }

    /**
     * Create timeline entry
     */
    private function createTimelineEntry(Book $booking, string $action, string $description): void
    {
        try {
            $userId = auth()->user() ? auth()->user()->getKey() : null;
            BookingTimeline::create([
                'book_id' => $booking->id,
                'action' => $action,
                'description' => $description,
                'user_id' => $userId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create booking timeline entry: '.$e->getMessage());
        }
    }
}
