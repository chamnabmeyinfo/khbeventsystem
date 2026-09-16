@php
    $boothsByBookId = $boothsByBookId ?? [];
    $boothsForBook = $boothsByBookId[$book->id] ?? collect();
    $boothCount = $boothsForBook->count() ?: (is_array(json_decode($book->boothid, true) ?? null) ? count(json_decode($book->boothid, true)) : 0);
    $boothNumbers = $boothsForBook->pluck('booth_number')->join(', ') ?: '—';
    $floorPlanName = $book->floorPlan->name ?? '—';
    $eventName = optional($book->floorPlan)->event?->title;
    
    $typeBadgeClass = 'regular';
    $typeBadgeName = 'Regular';
    if ($book->type == 2) {
        $typeBadgeClass = 'special';
        $typeBadgeName = 'Special';
    } elseif ($book->type == 3) {
        $typeBadgeClass = 'temporary';
        $typeBadgeName = 'Temporary';
    }
    
    $totalAmount = $book->total_amount ?? 0;
    $paidAmount = $book->paid_amount ?? 0;
    $balanceAmount = $book->balance_amount ?? ($totalAmount - $paidAmount);
    
    try {
        $statusSetting = isset($statusSetting) ? $statusSetting : ($book->statusSetting ?? \App\Models\BookingStatusSetting::getByCode($book->status ?? 1));
        $statusName = $statusSetting ? $statusSetting->status_name : 'Pending';
    } catch (\Exception $e) {
        $statusName = 'Pending';
    }

    $c = $book->client;
    $clientGradients = [
        'linear-gradient(135deg, #0284c7 0%, #38bdf8 100%)', // sky blue
        'linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%)', // teal (SV)
        'linear-gradient(135deg, #059669 0%, #34d399 100%)', // emerald (SC)
        'linear-gradient(135deg, #4f46e5 0%, #818cf8 100%)', // indigo (UR)
        'linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%)', // purple (HB)
        'linear-gradient(135deg, #475569 0%, #94a3b8 100%)', // slate (CS)
        'linear-gradient(135deg, #db2777 0%, #f472b6 100%)', // pink
    ];
    $gradientIdx = $c ? ($c->id % count($clientGradients)) : 0;
@endphp
<tr class="books-table-row" onclick="window.location='{{ route('books.show', $book) }}'">
    <!-- 1. Booking ID, Actions & Type -->
    <td class="books-col-id-status">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center min-w-0">
                <i class="fas fa-calendar-check text-dark me-2" style="font-size: 0.95rem;"></i>
                <strong class="books-id-label">Booking #{{ $book->id }}</strong>
            </div>
            <div class="books-row-actions d-inline-flex align-items-center gap-1 ms-2" onclick="event.stopPropagation()">
                <button type="button" class="books-action-btn books-action-btn--view" onclick="showBookingInfo({{ $book->id }})" title="Quick view">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
                @if(auth()->user()->isAdmin())
                <button type="button" class="books-action-btn books-action-btn--delete" onclick="deleteBooking({{ $book->id }})" title="Delete booking">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                </button>
                @endif
            </div>
        </div>
        <div>
            <span class="books-pill-type books-pill-type--{{ $typeBadgeClass }}">
                {{ $typeBadgeName }}
            </span>
        </div>
    </td>

    <!-- 2. Customer & Location -->
    <td class="books-col-customer">
        <div class="books-client-cell d-flex align-items-center">
            <div class="books-client-avatar-wrap me-3 flex-shrink-0">
                @if($c && !empty($c->avatar))
                    <img src="{{ \App\Helpers\AssetHelper::imageUrl($c->avatar) }}" alt="{{ $c->company ?? $c->name }}" class="books-client-avatar-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="books-client-avatar-fallback" style="display: none; background: {{ $clientGradients[$gradientIdx] }};">
                        {{ strtoupper(substr($c->company ?? $c->name ?? 'C', 0, 2)) }}
                    </div>
                @elseif($c)
                    <div class="books-client-avatar-fallback" style="background: {{ $clientGradients[$gradientIdx] }};">
                        {{ strtoupper(substr($c->company ?? $c->name ?? 'C', 0, 2)) }}
                    </div>
                @else
                    <div class="books-client-avatar-fallback" style="background: #94a3b8;">
                        NA
                    </div>
                @endif
            </div>
            <div class="books-client-info min-w-0">
                @if($c)
                    <strong class="books-client-name text-truncate d-block">{{ $c->company ?? $c->name }}</strong>
                    <div class="books-client-meta text-truncate">
                        <span>Contact Name: {{ $c->name ?? $c->company ?? '—' }} · ID {{ $c->id }}</span>
                        @if($floorPlanName !== '—')
                            <span class="books-meta-location ms-2 fw-semibold text-secondary">{{ $floorPlanName }}</span>
                        @endif
                    </div>
                @else
                    <span class="text-muted">N/A</span>
                @endif
            </div>
        </div>
    </td>

    <!-- 3. Date & Time -->
    <td class="books-col-date">
        <div class="books-date-main">{{ $book->date_book->format('M d, Y') }}</div>
        <div class="books-date-sub">
            <i class="fas fa-clock text-dark me-1" style="font-size: 0.78rem;"></i>
            <span>{{ $book->date_book->format('h:i A') }}</span>
        </div>
    </td>

    <!-- 4. Booths / Items -->
    <td class="books-col-booths">
        <div class="books-booths-head">
            <i class="fas fa-store me-1 text-dark" style="font-size: 0.85rem;"></i>
            <strong>{{ $boothCount }}</strong> {{ $boothCount == 1 ? 'Booths' : 'Booths' }}
        </div>
        <div class="books-booths-list text-truncate" title="{{ $boothNumbers }}">
            {{ $boothNumbers }}
        </div>
    </td>

    <!-- 5. Total Amount -->
    <td class="books-col-amount">
        <span class="books-amount-tag">${{ number_format($totalAmount, 2) }}</span>
    </td>

    <!-- 6. Payment Status & Staff -->
    <td class="books-col-status-staff">
        <div class="d-flex align-items-center mb-1">
            @if($balanceAmount <= 0)
                <span class="books-pill-status books-pill-status--paid">
                    <i class="fas fa-check me-1"></i>PAID
                </span>
                @if($book->payments && $book->payments->count() > 1 || ($book->payments->first() && $book->payments->first()->isSplit()))
                    <span class="books-status-info ms-1" title="Split/Multi Payment"><i class="fas fa-info-circle text-success" style="font-size: 0.8rem;"></i></span>
                @endif
            @elseif($paidAmount > 0)
                <span class="books-pill-status books-pill-status--partial">
                    <i class="fas fa-clock me-1"></i>PARTIAL
                </span>
                <span class="books-status-info ms-1" title="Balance: ${{ number_format($balanceAmount, 2) }}"><i class="fas fa-info-circle text-warning" style="font-size: 0.8rem;"></i></span>
            @else
                <span class="books-pill-status books-pill-status--pending">
                    <i class="fas fa-clock me-1"></i>PENDING
                </span>
            @endif
        </div>
        @if($book->user)
            <div class="books-staff-profile d-flex align-items-center mt-1" onclick="event.stopPropagation()">
                <div class="books-staff-avatar-wrap me-2 flex-shrink-0">
                    <x-avatar
                        :avatar="$book->user->avatar"
                        :name="$book->user->username"
                        size="24px"
                        :type="$book->user->isAdmin() ? 'admin' : 'user'"
                        shape="circle"
                    />
                </div>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('users.show', $book->user) }}" class="books-staff-name text-truncate text-decoration-none" title="{{ $book->user->display_name ?? $book->user->username }}">
                        {{ $book->user->display_name ?? $book->user->username }}
                    </a>
                @else
                    <span class="books-staff-name text-truncate" title="{{ $book->user->display_name ?? $book->user->username }}">
                        {{ $book->user->display_name ?? $book->user->username }}
                    </span>
                @endif
            </div>
        @else
            <div class="books-staff-profile text-muted small mt-1">—</div>
        @endif
    </td>
</tr>

