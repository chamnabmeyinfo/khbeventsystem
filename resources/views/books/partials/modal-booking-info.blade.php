<div class="booking-modal-info">
    <div class="row mb-3">
        <div class="col-6">
            <strong>Booking #{{ $book->id }}</strong>
        </div>
        <div class="col-6 text-end">
            <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-primary">View Full Details</a>
        </div>
    </div>
    <hr>
    <div class="mb-2">
        <strong>Client:</strong> {{ $book->client ? ($book->client->company ?? $book->client->name) : 'N/A' }}
        @if($book->client && $book->client->name && $book->client->company)
        <br><small class="text-muted">{{ $book->client->name }}</small>
        @endif
    </div>
    <div class="mb-2">
        <strong>Date:</strong> {{ $book->date_book ? $book->date_book->format('F d, Y h:i A') : 'N/A' }}
    </div>
    <div class="mb-2">
        <strong>Booked by:</strong> <i class="fas fa-user text-primary me-1"></i>{{ $book->user->username ?? 'System' }}
    </div>
    <div class="mb-2">
        <strong>Booths:</strong> {{ count($booths) }} {{ count($booths) == 1 ? 'Booth' : 'Booths' }}
        @if(count($booths) > 0)
        <br><small class="text-muted">{{ $booths->pluck('booth_number')->join(', ') }}</small>
        @endif
    </div>
    <div class="mb-2">
        <strong>Total:</strong> <span class="text-success fw-bold">${{ number_format($book->total_amount ?? $booths->sum('price') ?? 0, 2) }}</span>
    </div>
    <div class="mb-2">
        <strong>Status:</strong>
        @php
            try {
                $st = $book->statusSetting ?? \App\Models\BookingStatusSetting::getByCode($book->status ?? 1);
                $sc = $st ? $st->status_color : '#6c757d';
                $stc = $st && $st->text_color ? $st->text_color : '#fff';
            } catch (\Exception $e) {
                $sc = '#6c757d';
                $stc = '#fff';
                $st = null;
            }
        @endphp
        <span class="badge" style="background-color: {{ $sc }}; color: {{ $stc }};">{{ $st ? $st->status_name : 'Pending' }}</span>
    </div>
</div>
