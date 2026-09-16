{{-- Inner HTML for #bookingsContainer (full page + AJAX filter refresh) --}}
@if($groupBy !== 'none' && !empty($groupedBooks))
    <!-- Grouped View -->
    @php $booksRowCounter = 0; @endphp
    @foreach($groupedBooks as $groupKey => $groupBooks)
        <div class="group-section">
            <div class="group-header">
                <h5>
                    <span>
                        <i class="fas fa-layer-group me-2"></i>
                        {{ $groupBy === 'name' ? $groupKey : \Carbon\Carbon::parse($groupKey)->format('F d, Y') }}
                    </span>
                    <span class="badge bg-secondary">{{ count($groupBooks) }} bookings</span>
                </h5>
            </div>
            <div class="canvas-panel books-data-panel">
                    <!-- Table View -->
                    <div class="table-view">
                        <div class="looker-table-wrapper">
                        <table class="looker-table books-looker-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Row</th>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th class="text-center">Team</th>
                                    <th>Floor Plan</th>
                                    <th>Date</th>
                                    <th>Booths</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupBooks as $book)
                                    @php $booksRowCounter++; @endphp
                                    @include('books.partials.table-row', ['book' => $book, 'rowNumber' => $booksRowCounter])
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <!-- Card View -->
                    <div class="card-view" style="display: none;">
                        <div class="books-card-header-row d-none d-lg-grid">
                            <div class="bcard-hdr bcard-hdr--id">Booking ID &amp; Type</div>
                            <div class="bcard-hdr bcard-hdr--customer">Customer &amp; Location</div>
                            <div class="bcard-hdr bcard-hdr--date">Date &amp; Time</div>
                            <div class="bcard-hdr bcard-hdr--booths">Booths</div>
                            <div class="bcard-hdr bcard-hdr--amount">Total Amount</div>
                            <div class="bcard-hdr bcard-hdr--status">Payment &amp; Staff</div>
                        </div>
                        <div class="books-card-view-inner booths-card-density--medium">
                            @php $groupCardRowStart = $booksRowCounter - count($groupBooks) + 1; @endphp
                            @foreach($groupBooks as $book)
                                @include('books.partials.card', ['book' => $book, 'rowNumber' => $groupCardRowStart + $loop->index])
                            @endforeach
                        </div>
                    </div>
            </div>
        </div>
    @endforeach
@else
    <!-- Regular View -->
    <div class="canvas-panel books-data-panel">
            <!-- Table View -->
            <div class="table-view">
                <div class="looker-table-wrapper">
                <table class="looker-table books-looker-table mb-0" id="bookingsTable">
                    <thead>
                        <tr>
                            <th scope="col">Row</th>
                            <th>ID</th>
                            <th>Client</th>
                            <th class="text-center">Team</th>
                            <th>Floor Plan</th>
                            <th>Date</th>
                            <th>Booths</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTableBody">
                        @forelse($books as $book)
                            @include('books.partials.table-row', ['book' => $book, 'rowNumber' => $loop->iteration])
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox empty-state-icon"></i>
                                        <h3>No bookings found</h3>
                                        <p class="text-muted">Try adjusting your filters or create a new booking.</p>
                                        <a href="{{ route('books.create') }}" class="action-btn action-btn-primary mt-3">
                                            <i class="fas fa-plus"></i> Create booking
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            <!-- Card View -->
            <div class="card-view" style="display: none;">
                <div class="books-card-header-row d-none d-lg-grid">
                    <div class="bcard-hdr bcard-hdr--id">Booking ID &amp; Type</div>
                    <div class="bcard-hdr bcard-hdr--customer">Customer &amp; Location</div>
                    <div class="bcard-hdr bcard-hdr--date">Date &amp; Time</div>
                    <div class="bcard-hdr bcard-hdr--booths">Booths</div>
                    <div class="bcard-hdr bcard-hdr--amount">Total Amount</div>
                    <div class="bcard-hdr bcard-hdr--status">Payment &amp; Staff</div>
                </div>
                <div class="books-card-view-inner booths-card-density--medium">
                    @forelse($books as $book)
                        @include('books.partials.card', ['book' => $book, 'rowNumber' => $loop->iteration])
                    @empty
                        <div class="empty-state books-card-view-empty">
                            <i class="fas fa-inbox empty-state-icon"></i>
                            <h3>No bookings found</h3>
                            <p class="text-muted">Try adjusting your filters or create a new booking.</p>
                            <a href="{{ route('books.create') }}" class="action-btn action-btn-primary mt-3">
                                <i class="fas fa-plus"></i> Create booking
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
    </div>
@endif

<!-- Lazy Loading Spinner -->
<div class="lazy-load-spinner" id="lazyLoadSpinner">
    <i class="fas fa-spinner"></i>
</div>

<!-- Lazy Loading End -->
<div class="lazy-load-end" id="lazyLoadEnd" style="display: none;">
    <i class="fas fa-check-circle me-2"></i>All bookings loaded
</div>

<!-- Lazy Load Trigger -->
<div class="lazy-load-trigger" id="lazyLoadTrigger"></div>
