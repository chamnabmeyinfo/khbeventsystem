@php
    $boothsByBookId = $boothsByBookId ?? [];
    $boothsForBook = $boothsByBookId[$book->id] ?? collect();
    $boothCount = $boothsForBook->count() ?: (is_array(json_decode($book->boothid, true) ?? null) ? count(json_decode($book->boothid, true)) : 0);
    $boothNumbers = $boothsForBook->pluck('booth_number')->join(', ') ?: '—';
    $floorPlanName = $book->floorPlan->name ?? '—';
    $eventName = optional($book->floorPlan)->event?->title;
    $typeBadgeClass = 'books-type-regular';
    if ($book->type == 2) {
        $typeBadgeClass = 'books-type-special';
    } elseif ($book->type == 3) {
        $typeBadgeClass = 'books-type-temporary';
    }
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
@endphp
<tr class="books-table-row" onclick="window.location='{{ route('books.show', $book) }}'">
    <td class="books-col-rownum">{{ $rowNumber ?? '—' }}</td>
    <td class="books-col-id"><strong>#{{ $book->id }}</strong></td>
    <td>
        @if($book->client)
            @php $c = $book->client; @endphp
            <strong>{{ $c->company ?? $c->name }}</strong>
            <br><small class="text-muted">Contact Name: {{ $c->name ?? $c->company ?? '—' }} · ID {{ $c->id }}</small>
        @else
            <span class="text-muted">N/A</span>
        @endif
    </td>
    <td class="books-col-team text-center">
        @if($book->user)
            @php
                $u = $book->user;
                $uName = $u->display_name ?? $u->username;
            @endphp
            <div class="books-team-cell">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('users.show', $u) }}" onclick="event.stopPropagation()" class="books-team-link" title="View Team Member: {{ $uName }} ({{ $u->username }})">
                        <div class="books-team-avatar-wrap">
                            <x-avatar
                                :avatar="$u->avatar"
                                :name="$u->username"
                                size="34px"
                                :type="$u->isAdmin() ? 'admin' : 'user'"
                                shape="circle"
                            />
                        </div>
                        <span class="books-team-name text-truncate">
                            {{ $uName }}
                        </span>
                    </a>
                @else
                    <div class="books-team-avatar-wrap">
                        <x-avatar
                            :avatar="$u->avatar"
                            :name="$u->username"
                            size="34px"
                            :type="$u->isAdmin() ? 'admin' : 'user'"
                            shape="circle"
                        />
                    </div>
                    <span class="books-team-name text-truncate" title="{{ $uName }}">
                        {{ $uName }}
                    </span>
                @endif
            </div>
        @else
            <span class="text-muted small">—</span>
        @endif
    </td>
    <td>
        <span title="{{ $eventName ? 'Event: ' . $eventName : '' }}">{{ $floorPlanName }}</span>
        @if($eventName)
        <br><small class="text-muted">{{ Str::limit($eventName, 20) }}</small>
        @endif
    </td>
    <td>
        {{ $book->date_book->format('M d, Y') }}<br>
        <small class="text-muted">{{ $book->date_book->format('h:i A') }}</small>
    </td>
    <td>
        <strong>{{ $boothCount }}</strong> {{ $boothCount == 1 ? 'Booth' : 'Booths' }}
        <br><small class="text-muted" title="{{ $boothNumbers }}">{{ Str::limit($boothNumbers, 30) ?: '—' }}</small>
    </td>
    <td>
        <span class="books-type-badge {{ $typeBadgeClass }}">
            @if($book->type == 1) Regular
            @elseif($book->type == 2) Special
            @elseif($book->type == 3) Temporary
            @else {{ $book->type }}
            @endif
        </span>
    </td>
    <td>
        <span class="books-status-pill" style="background-color: {{ $statusColor }}; color: {{ $statusTextColor }};">
            {{ $statusName }}
        </span>
    </td>
    <td><strong class="books-amount-cell">${{ number_format($totalAmount, 2) }}</strong></td>
    <td class="books-col-actions" onclick="event.stopPropagation()">
        <div class="books-table-actions" role="group" aria-label="Row actions">
            <button type="button" class="books-table-btn plastic-btn-press" onclick="showBookingInfo({{ $book->id }})" title="Quick view">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
            @if(auth()->user()->isAdmin())
            <button type="button" class="books-table-btn books-table-btn-danger plastic-btn-press" onclick="deleteBooking({{ $book->id }})" title="Delete booking">
                <i class="fas fa-trash" aria-hidden="true"></i>
            </button>
            @endif
        </div>
    </td>
</tr>
