<div class="compact-booking-card {{ $typeClass }}" onclick="window.location='{{ route('books.show', $book) }}'">
    <div class="compact-card-header">
        <div class="compact-card-id">
            <i class="fas fa-hashtag"></i>
            <span>#{{ $book->id }}</span>
        </div>
        <span class="compact-card-badge {{ $typeBadge }}" style="background: {{ $statusColor }}; color: {{ $statusTextColor }};">
            {{ $statusName }}
        </span>
    </div>
    <div class="compact-card-content">
        <div class="compact-card-row">
            <i class="fas fa-building"></i>
            <strong>{{ $book->client ? ($book->client->company ?? $book->client->name) : 'N/A' }}</strong>
        </div>
        @if($book->client && $book->client->name && $book->client->company)
        <div class="compact-card-row">
            <i class="fas fa-user"></i>
            <span>{{ $book->client->name }}</span>
        </div>
        @endif
        @php
            $boothsByBookId = $boothsByBookId ?? [];
            $boothsForBook = $boothsByBookId[$book->id] ?? collect();
            $boothNumbers = $boothsForBook->pluck('booth_number')->join(', ') ?: null;
            $floorPlanName = $book->floorPlan->name ?? null;
        @endphp
        @if($floorPlanName)
        <div class="compact-card-row">
            <i class="fas fa-map"></i>
            <span>{{ $floorPlanName }}</span>
        </div>
        @endif
        <div class="compact-card-row">
            <i class="fas fa-cube"></i>
            <span>{{ $boothCount }} {{ $boothCount == 1 ? 'Booth' : 'Booths' }}{{ $boothNumbers ? ': ' . Str::limit($boothNumbers, 40) : '' }}</span>
        </div>
        <div class="compact-card-row">
            <i class="fas fa-calendar"></i>
            <span>{{ $book->date_book ? $book->date_book->format('M d, Y') : '—' }}</span>
            <i class="fas fa-clock ml-2"></i>
            <span>{{ $book->date_book ? $book->date_book->format('h:i A') : '—' }}</span>
        </div>
        <div class="compact-card-row">
            <i class="fas fa-dollar-sign"></i>
            <strong style="color: #10b981;">${{ number_format($totalAmount, 2) }}</strong>
            @if($balanceAmount > 0)
            <span style="color: #f59e0b; margin-left: 8px;">Balance: ${{ number_format($balanceAmount, 2) }}</span>
            @endif
        </div>
    </div>
    <div class="compact-card-footer">
        <div class="compact-card-row">
            @if($book->user)
            <x-avatar 
                :avatar="$book->user->avatar ?? null" 
                :name="$book->user->username ?? 'User'" 
                :size="'xs'" 
                :type="($book->user && method_exists($book->user, 'isAdmin') && $book->user->isAdmin()) ? 'admin' : 'user'"
                :shape="'circle'"
            />
            <span style="font-size: 0.75rem; color: #6b7280;">{{ $book->user->username }}</span>
            @else
            <i class="fas fa-server" style="color: #6b7280;"></i>
            <span style="font-size: 0.75rem; color: #6b7280;">System</span>
            @endif
        </div>
        <div class="compact-card-actions" onclick="event.stopPropagation()">
            <button type="button" class="btn btn-info btn-sm" onclick="showBookingInfo({{ $book->id }})" title="View">
                <i class="fas fa-eye"></i>
            </button>
            @if(auth()->user()?->isAdmin() || \Illuminate\Support\Facades\Auth::guard('admin')->check())
            <button type="button" class="btn btn-danger btn-sm" onclick="deleteBooking({{ $book->id }})" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    </div>
</div>
