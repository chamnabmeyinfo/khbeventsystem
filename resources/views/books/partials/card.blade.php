@php
    $boothsByBookId = $boothsByBookId ?? [];
    $boothsForBook = $boothsByBookId[$book->id] ?? collect();
    $boothCount = $boothsForBook->count() ?: (is_array(json_decode($book->boothid, true) ?? null) ? count(json_decode($book->boothid, true)) : 0);
    $boothNumbers = $boothsForBook->pluck('booth_number')->join(', ') ?: '—';
    $floorPlanName = $book->floorPlan->name ?? '—';
    $eventName = optional($book->floorPlan)->event?->title;
    
    $totalAmount = $book->total_amount ?? 0;
    $paidAmount = $book->paid_amount ?? 0;
    $balanceAmount = $book->balance_amount ?? ($totalAmount - $paidAmount);

    try {
        $statusSetting = isset($statusSetting) ? $statusSetting : ($book->statusSetting ?? \App\Models\BookingStatusSetting::getByCode($book->status ?? 1));
        $statusColor = $statusSetting ? $statusSetting->status_color : '#6c757d';
        $statusTextColor = $statusSetting && $statusSetting->text_color ? $statusSetting->text_color : '#ffffff';
        $statusName = $statusSetting ? $statusSetting->status_name : 'Pending';
    } catch (\Exception $e) {
        $statusColor = '#6c757d';
        $statusTextColor = '#ffffff';
        $statusName = 'Pending';
    }

    $c = $book->client;
    $clientName = $c ? ($c->company ?: $c->name) : 'N/A';
    $contactName = $c ? ($c->name ?: ($c->company ?: '—')) : '—';
    $clientId = $c ? $c->id : null;
    
    // Dynamic initials & gradients for client avatar fallback matching image_0.png
    $avatarGradients = [
        'linear-gradient(135deg, #38bdf8 0%, #818cf8 100%)', // cyan/indigo (SV)
        'linear-gradient(135deg, #2dd4bf 0%, #06b6d4 100%)', // teal/cyan (SC)
        'linear-gradient(135deg, #34d399 0%, #10b981 100%)', // mint/emerald (CS)
        'linear-gradient(135deg, #818cf8 0%, #6366f1 100%)', // indigo (UR)
        'linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%)', // purple/violet (HB)
        'linear-gradient(135deg, #f472b6 0%, #ec4899 100%)', // pink
        'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)', // amber
    ];
    $gradIdx = $c ? ($c->id % count($avatarGradients)) : 0;
    $avatarBg = $avatarGradients[$gradIdx];

    $clientInitials = 'NA';
    if ($c) {
        $rawWords = preg_split('/\s+/', trim($c->company ?: $c->name));
        if (count($rawWords) >= 2) {
            $clientInitials = strtoupper(mb_substr($rawWords[0], 0, 1) . mb_substr($rawWords[1], 0, 1));
        } else {
            $clientInitials = strtoupper(mb_substr($c->company ?: $c->name, 0, 2));
        }
    }

    $clientAvatarUrl = $c ? \App\Helpers\AssetHelper::imageUrl($c->avatar ?? null) : null;

    $staff = $book->user;
    $staffName = $staff ? ($staff->username ?: ($staff->name ?: 'Staff')) : '—';
    $staffIsAdmin = $staff && method_exists($staff, 'isAdmin') ? $staff->isAdmin() : false;
@endphp

<div class="booking-card books-booking-card books-card-modern books-card-modern--type-{{ (int) $book->type }}" onclick="window.location='{{ route('books.show', $book) }}'">
    <!-- Column 1: Booking ID, Action buttons & Type -->
    <div class="bcard-col bcard-col--id">
        <div class="bcard-id-row">
            <span class="bcard-id-title">
                <i class="fas fa-calendar-check me-1 bcard-id-icon" aria-hidden="true"></i>Booking #{{ $book->id }}
            </span>
            <div class="bcard-actions-group" onclick="event.stopPropagation()">
                <button type="button" class="bcard-btn-action bcard-btn-action--view plastic-btn-press" onclick="showBookingInfo({{ $book->id }})" title="Quick view">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
@if(auth()->user()?->isAdmin() || \Illuminate\Support\Facades\Auth::guard('admin')->check())
                <button type="button" class="bcard-btn-action bcard-btn-action--delete plastic-btn-press" onclick="deleteBooking({{ $book->id }})" title="Delete booking">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                </button>
                @endif
            </div>
        </div>
        <div class="bcard-type-row">
            <span class="bcard-pill-type bcard-pill-type--{{ (int) $book->type }}">
                @if($book->type == 1) REGULAR
                @elseif($book->type == 2) SPECIAL
                @elseif($book->type == 3) TEMPORARY
                @else {{ strtoupper($book->type) }}
                @endif
            </span>
        </div>
    </div>

    <!-- Column 2: Customer & Location -->
    <div class="bcard-col bcard-col--customer">
        <div class="bcard-customer-avatar-wrap">
            @if($clientAvatarUrl)
                <img src="{{ $clientAvatarUrl }}" alt="{{ $clientName }}" class="bcard-customer-avatar-img" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <div class="bcard-customer-avatar-fallback" style="display: none; background: {{ $avatarBg }};">
                    {{ $clientInitials }}
                </div>
            @else
                <div class="bcard-customer-avatar-fallback" style="background: {{ $avatarBg }};">
                    {{ $clientInitials }}
                </div>
            @endif
        </div>
        <div class="bcard-customer-info">
            <div class="bcard-customer-name" title="{{ $clientName }}">{{ $clientName }}</div>
            <div class="bcard-customer-meta">
                <span class="bcard-contact-text">Contact Name: {{ $contactName }}</span>
                @if($clientId)
                    <span class="bcard-meta-dot">·</span>
                    <span class="bcard-meta-id">ID {{ $clientId }}</span>
                @endif
                @if($floorPlanName !== '—' || $eventName)
                    <span class="bcard-location-badge" title="{{ $eventName ?: $floorPlanName }}">
                        {{ Str::limit($floorPlanName !== '—' ? $floorPlanName : $eventName, 28) }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Column 3: Date & Time -->
    <div class="bcard-col bcard-col--date">
        <div class="bcard-date-main">
            {{ $book->date_book ? $book->date_book->format('M d, Y') : '—' }}
        </div>
        <div class="bcard-date-time">
            <i class="fas fa-clock bcard-clock-icon me-1" aria-hidden="true"></i>
            {{ $book->date_book ? $book->date_book->format('h:i A') : '—' }}
        </div>
    </div>

    <!-- Column 4: Booths -->
    <div class="bcard-col bcard-col--booths">
        <div class="bcard-booth-count">
            <i class="fas fa-store bcard-store-icon me-1" aria-hidden="true"></i>
            {{ $boothCount }} {{ $boothCount == 1 ? 'Booth' : 'Booths' }}
        </div>
        <div class="bcard-booth-numbers text-truncate" title="{{ $boothNumbers }}">
            {{ $boothNumbers }}
        </div>
    </div>

    <!-- Column 5: Total Amount -->
    <div class="bcard-col bcard-col--amount">
        <div class="bcard-amount-val">
            ${{ number_format($totalAmount, 2) }}
        </div>
    </div>

    <!-- Column 6: Payment & Staff -->
    <div class="bcard-col bcard-col--status-staff">
        <div class="bcard-status-row">
            @if($balanceAmount <= 0 || (int)$book->status === 2 || strtolower($statusName) === 'paid')
                <span class="bcard-status-pill bcard-status-pill--paid">PAID</span>
            @elseif($paidAmount > 0)
                <span class="bcard-status-pill bcard-status-pill--partial" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }};">PARTIAL</span>
            @else
                <span class="bcard-status-pill" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }};">{{ strtoupper($statusName) }}</span>
            @endif

            @if($balanceAmount > 0 && $paidAmount > 0)
                <span class="bcard-info-badge" title="Paid: ${{ number_format($paidAmount, 2) }} | Balance: ${{ number_format($balanceAmount, 2) }}">
                    <i class="fas fa-info-circle text-warning"></i>
                </span>
            @elseif($balanceAmount <= 0 && $paidAmount > 0)
                <span class="bcard-info-badge" title="Fully Paid">
                    <i class="fas fa-info-circle text-success" style="opacity: 0.85;"></i>
                </span>
            @endif
        </div>
        @if($staff)
            <div class="bcard-staff-row">
                <div class="bcard-staff-avatar-wrap">
                    <x-avatar
                        :avatar="$staff->avatar ?? null"
                        :name="$staff->username ?? 'User'"
                        size="22px"
                        :type="$staffIsAdmin ? 'admin' : 'user'"
                        shape="circle"
                    />
                </div>
                <span class="bcard-staff-name" title="{{ $staffName }}">{{ $staffName }}</span>
            </div>
        @endif
    </div>
</div>
