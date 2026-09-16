@extends('layouts.app')

@section('title', 'Bookings Management')


@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-looker.css') }}?v=3.6">
<link rel="stylesheet" href="{{ asset('css/books-page-index.css') }}?v=1.3">
<link rel="stylesheet" href="{{ asset('css/booths-on-books.css') }}?v=2.4">
@endpush

@push('body-class', 'ios-dashboard-mode')


@section('content')
<div class="looker-dashboard books-page">
    @if(!empty($restrictToOwnBookings))
        <div class="alert alert-info mb-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>You are viewing only your own bookings.</strong> You can create, edit, update, and delete only the bookings you created. You cannot view or manage other users&#39; bookings. This is controlled in <a href="{{ route('settings.index') }}">Settings &rarr; Public View Actions</a>.
        </div>
    @endif

    <header class="looker-header">
        <div class="looker-header-title">
            <h1>Bookings</h1>
            <p>Create, filter, and manage booth bookings from one place.</p>
        </div>
        <div class="looker-actions">
            <a href="{{ route('books.create') }}" class="action-btn action-btn-primary">
                <i class="fas fa-plus"></i> New booking
            </a>
            <a href="{{ route('export.bookings') }}" class="action-btn action-btn-secondary">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <button type="button" class="action-btn action-btn-secondary" onclick="refreshPage()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            @if(auth()->user()->isAdmin())
            <button type="button" class="action-btn action-btn-secondary" style="border-color: rgba(229, 62, 62, 0.35); color: #c53030;" onclick="showDeleteAllModal()">
                <i class="fas fa-trash-alt"></i> Delete all
            </button>
            @endif
        </div>
    </header>

    @php
        try {
            $totalBoothsKpi = \App\Models\Book::get()->sum(function ($book) {
                $boothIds = json_decode($book->boothid, true);
                return is_array($boothIds) ? count($boothIds) : 0;
            });
        } catch (\Exception $e) {
            $totalBoothsKpi = 0;
        }
    @endphp
    <div class="kpi-wrapper">
        <div class="kpi-card-looker">
            <div class="kpi-top">
                <div class="kpi-title">Total bookings</div>
                <div class="kpi-icon-wrapper primary-icon"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="kpi-value-looker">{{ number_format(\App\Models\Book::count()) }}</div>
            <div class="kpi-bottom trend-neutral"><i class="fas fa-layer-group fa-fw"></i> All time</div>
        </div>
        <div class="kpi-card-looker success">
            <div class="kpi-top">
                <div class="kpi-title">Today</div>
                <div class="kpi-icon-wrapper success-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
            <div class="kpi-value-looker">{{ number_format(\App\Models\Book::whereDate('date_book', today())->count()) }}</div>
            <div class="kpi-bottom trend-positive"><i class="fas fa-sun fa-fw"></i> Scheduled for today</div>
        </div>
        <div class="kpi-card-looker warning">
            <div class="kpi-top">
                <div class="kpi-title">This month</div>
                <div class="kpi-icon-wrapper warning-icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div class="kpi-value-looker">{{ number_format(\App\Models\Book::whereMonth('date_book', now()->month)->whereYear('date_book', now()->year)->count()) }}</div>
            <div class="kpi-bottom trend-warning"><i class="fas fa-calendar-week fa-fw"></i> {{ now()->format('F Y') }}</div>
        </div>
        <div class="kpi-card-looker purple">
            <div class="kpi-top">
                <div class="kpi-title">Booth slots (booked)</div>
                <div class="kpi-icon-wrapper purple-icon"><i class="fas fa-cube"></i></div>
            </div>
            <div class="kpi-value-looker">{{ number_format($totalBoothsKpi) }}</div>
            <div class="kpi-bottom trend-neutral"><i class="fas fa-th fa-fw"></i> Sum across bookings</div>
        </div>
    </div>

    <div class="books-toolbar d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="books-view-toggle" role="group" aria-label="List layout">
            <button type="button" class="active plastic-btn-press" onclick="switchView('table')" id="viewTable">
                <i class="fas fa-table me-1"></i>Table
            </button>
            <button type="button" class="plastic-btn-press" onclick="switchView('cards')" id="viewCards" title="Card view — adjust size in Book settings">
                <i class="fas fa-th-large me-1"></i>Cards
            </button>
        </div>
        <button type="button" class="action-btn action-btn-secondary booths-list-settings-btn" data-bs-toggle="modal" data-bs-target="#booksListSettingsModal" aria-controls="booksListSettingsModal" id="booksListSettingsOpen">
            <i class="fas fa-cog me-1" aria-hidden="true"></i>Book settings
        </button>
    </div>

    <!-- Filter Bar -->
    @php
        $hasActiveFilters = request()->hasAny(['search', 'date_from', 'date_to', 'type', 'floor_plan_id', 'status', 'amount_min', 'amount_max', 'booth_count_min']) || (request('date_range') && request('date_range') !== 'all');
        $activeFilterCount = 0;
        if (request('search')) $activeFilterCount++;
        if (request('date_from') || request('date_to')) $activeFilterCount++;
        if (request('type')) $activeFilterCount++;
        if (request('floor_plan_id')) $activeFilterCount++;
        if (request('status')) $activeFilterCount++;
        if (request('amount_min') || request('amount_max')) $activeFilterCount++;
        if (request('booth_count_min')) $activeFilterCount++;
        if (request('date_range') && request('date_range') !== 'all') $activeFilterCount++;
    @endphp
    <div class="filter-bar">
        <form method="GET" action="{{ route('books.index') }}" id="filterForm">
            <div class="filter-header">
                <h6>
                    <i class="fas fa-filter"></i> Filters
                    <span class="filter-badge {{ $activeFilterCount > 0 ? '' : 'd-none' }}" id="booksFilterBadge">{{ $activeFilterCount }} active</span>
                </h6>
                <span class="filter-toggle" onclick="document.getElementById('filterAdvanced').classList.toggle('d-none'); this.querySelector('i').classList.toggle('fa-chevron-down'); this.querySelector('i').classList.toggle('fa-chevron-up');">
                    <i class="fas fa-chevron-down"></i> <span>Advanced</span>
                </span>
            </div>

            <!-- Primary Filters (always visible) -->
            <div class="filter-row-primary">
                <div>
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-modern form-control-sm"
                           placeholder="Client, company, user..." value="{{ request('search') }}">
                </div>
                <div>
                    <label class="form-label small mb-1">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-modern form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div>
                    <label class="form-label small mb-1">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-modern form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div>
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-control form-control-modern form-control-sm">
                        <option value="">All Types</option>
                        <option value="1" {{ request('type') == '1' ? 'selected' : '' }}>Regular</option>
                        <option value="2" {{ request('type') == '2' ? 'selected' : '' }}>Special</option>
                        <option value="3" {{ request('type') == '3' ? 'selected' : '' }}>Temporary</option>
                    </select>
                </div>
                <div>
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-control form-control-modern form-control-sm">
                        <option value="">All Statuses</option>
                        @foreach($statusSettings ?? [] as $sts)
                        <option value="{{ $sts->status_code }}" {{ request('status') == (string)$sts->status_code ? 'selected' : '' }}>{{ $sts->status_name }}</option>
                        @endforeach
                        @if(($statusSettings ?? collect())->isEmpty())
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Pending</option>
                        <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Confirmed</option>
                        <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Reserved</option>
                        <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Paid</option>
                        <option value="6" {{ request('status') == '6' ? 'selected' : '' }}>Cancelled</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="form-label small mb-1">Floor Plan</label>
                    <select name="floor_plan_id" class="form-control form-control-modern form-control-sm">
                        <option value="">All Floor Plans</option>
                        @foreach($floorPlans ?? [] as $fp)
                        <option value="{{ $fp->id }}" {{ request('floor_plan_id') == (string)$fp->id ? 'selected' : '' }}>{{ $fp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small mb-1">Group By</label>
                    <select name="group_by" class="form-control form-control-modern form-control-sm books-filter-ajax-trigger">
                        <option value="none" {{ request('group_by', 'none') == 'none' ? 'selected' : '' }}>No Grouping</option>
                        <option value="name" {{ request('group_by') == 'name' ? 'selected' : '' }}>By Client</option>
                        <option value="date" {{ request('group_by') == 'date' ? 'selected' : '' }}>By Date</option>
                    </select>
                </div>
            </div>

            <!-- Advanced Filters (collapsible) -->
            <div id="filterAdvanced" class="filter-row-advanced {{ $hasActiveFilters && (request('amount_min') || request('amount_max') || request('booth_count_min') || request('date_range')) ? '' : 'd-none' }}">
                <div class="row g-3">
                    <div class="col-md-2 col-6">
                        <label class="form-label small mb-1">Amount Min ($)</label>
                        <input type="number" name="amount_min" class="form-control form-control-modern form-control-sm" step="0.01" min="0" placeholder="0" value="{{ request('amount_min') }}">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label small mb-1">Amount Max ($)</label>
                        <input type="number" name="amount_max" class="form-control form-control-modern form-control-sm" step="0.01" min="0" placeholder="—" value="{{ request('amount_max') }}">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label small mb-1">Min Booths</label>
                        <input type="number" name="booth_count_min" class="form-control form-control-modern form-control-sm" min="1" placeholder="1" value="{{ request('booth_count_min') }}">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label small mb-1">Date Range Preset</label>
                        <select name="date_range" class="form-control form-control-modern form-control-sm">
                            <option value="all" {{ request('date_range', 'all') == 'all' ? 'selected' : '' }}>All Dates</option>
                            <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="3days" {{ request('date_range') == '3days' ? 'selected' : '' }}>Last 3 Days</option>
                            <option value="7days" {{ request('date_range') == '7days' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="14days" {{ request('date_range') == '14days' ? 'selected' : '' }}>Last 14 Days</option>
                            <option value="more" {{ request('date_range') == 'more' ? 'selected' : '' }}>Older than 14 Days</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Quick date presets (chips) -->
            <div class="filter-actions">
                <button type="submit" class="action-btn action-btn-primary books-filter-apply">
                    <i class="fas fa-filter me-1"></i>Apply
                </button>
                <a href="{{ route('books.index') }}" class="action-btn action-btn-secondary books-filter-clear" id="booksFilterClearLink">
                    <i class="fas fa-times me-1"></i>Clear all
                </a>
                <div class="d-flex flex-wrap gap-2 ms-2 align-items-center">
                    <span class="text-muted small me-1">Quick:</span>
                    <button type="button" class="filter-chip {{ !request('date_from') && !request('date_to') && (!request('date_range') || request('date_range') == 'all') ? 'active' : '' }}" onclick="setQuickDate('')">All</button>
                    <button type="button" class="filter-chip {{ request('date_range') == 'today' ? 'active' : '' }}" onclick="setQuickDate('today')">Today</button>
                    <button type="button" class="filter-chip {{ request('date_range') == '7days' ? 'active' : '' }}" onclick="setQuickDate('7days')">Last 7 Days</button>
                    <button type="button" class="filter-chip" onclick="setQuickDate('30days')">Last 30 Days</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bookings Content -->
    <div id="bookingsContainer">
        @include('books.partials.index-bookings-container', ['books' => $books, 'groupBy' => $groupBy, 'groupedBooks' => $groupedBooks, 'boothsByBookId' => $boothsByBookId])
    </div>

    {{-- Book list settings: card density + links (same Looker-style shell as Booth settings) --}}
    <div class="modal fade booths-list-settings-modal text-body" id="booksListSettingsModal" tabindex="-1" aria-labelledby="booksListSettingsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl booths-settings-modal-dialog">
            <div class="modal-content booths-settings-modal__shell">
                <div class="modal-header booths-settings-modal__header border-0">
                    <div class="d-flex align-items-start gap-3 flex-grow-1 min-w-0">
                        <div class="booths-settings-modal__icon-wrap" aria-hidden="true">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div class="min-w-0">
                            <h5 class="modal-title mb-1" id="booksListSettingsLabel">Book list settings</h5>
                            <p class="booths-settings-modal__subtitle mb-0">Cards layout and where to change booking rules in System Settings.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close booths-settings-modal__close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booths-settings-modal__body">
                    <div class="booths-settings-block">
                        <div class="booths-settings-block__head">
                            <span class="booths-settings-block__icon booths-settings-block__icon--blue"><i class="fas fa-th-large" aria-hidden="true"></i></span>
                            <div>
                                <h6 class="booths-settings-block__title">Card layout</h6>
                                <p class="booths-settings-block__desc">Used when <strong>Cards</strong> is selected. Stored in this browser only.</p>
                            </div>
                        </div>
                        <div class="books-view-toggle booths-density-toggle booths-density-toggle--settings w-100 flex-wrap" role="group" aria-label="Booking card size" id="booksListDensityToggle">
                            <button type="button" class="plastic-btn-press" onclick="setBooksCardDensity('tiny')" id="booksDensityTiny" title="Tiny list — compact rows">
                                <i class="fas fa-list me-1" aria-hidden="true"></i>Tiny
                            </button>
                            <button type="button" class="plastic-btn-press" onclick="setBooksCardDensity('small')" id="booksDensitySmall">Small</button>
                            <button type="button" class="active plastic-btn-press" onclick="setBooksCardDensity('medium')" id="booksDensityMedium">Medium</button>
                            <button type="button" class="plastic-btn-press" onclick="setBooksCardDensity('large')" id="booksDensityLarge">Large</button>
                        </div>
                    </div>

                    <div class="booths-settings-block booths-settings-block--muted" aria-labelledby="booksSettingsSystemLinkHeading">
                        <div class="booths-settings-block__head">
                            <span class="booths-settings-block__icon booths-settings-block__icon--muted" aria-hidden="true"><i class="fas fa-cog"></i></span>
                            <div>
                                <h6 class="booths-settings-block__title" id="booksSettingsSystemLinkHeading">System-wide options</h6>
                                <p class="booths-settings-block__desc mb-2">Who can create or see bookings on the public floor plan, upload limits, and module visibility are configured in <strong>System Settings</strong>.</p>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <a href="{{ route('settings.index') }}#settings-public-view" class="btn btn-sm btn-outline-secondary">Public view &amp; booking actions</a>
                                    <a href="{{ route('settings.index') }}#settings-upload-control" class="btn btn-sm btn-outline-secondary">Upload control</a>
                                    <a href="{{ route('settings.index') }}#module-display" class="btn btn-sm btn-outline-secondary">Module display</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer booths-settings-modal__footer border-0">
                    <button type="button" class="btn btn-light border booths-settings-modal__btn-done" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Info Modal -->
<div class="modal fade" id="bookingInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header booking-info-modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check me-2"></i>Booking Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingInfoContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
@php
    $lazyLoadMoreAvailable = ($groupBy === 'none' && isset($total, $books) && $total > $books->count());
@endphp
<script src="{{ asset('js/books-table-column-resize.js') }}?v=2"></script>
<script>
(function() {
    'use strict';
    
    let currentPage = 1;
    let isLoading = false;
    let hasMore = @json($lazyLoadMoreAvailable);
    let currentView = 'table';

    window.setBooksCardDensity = function(size) {
        var allowed = { tiny: 1, small: 1, medium: 1, large: 1 };
        if (!allowed[size]) {
            size = 'medium';
        }
        var inners = document.querySelectorAll('#bookingsContainer .books-card-view-inner');
        if (!inners.length) return;

        inners.forEach(function(inner) {
            inner.classList.remove(
                'booths-card-density--tiny',
                'booths-card-density--small',
                'booths-card-density--medium',
                'booths-card-density--large'
            );
            inner.classList.add('booths-card-density--' + size);
        });

        var idSuffix = { tiny: 'Tiny', small: 'Small', medium: 'Medium', large: 'Large' };
        document.querySelectorAll('#booksListDensityToggle button').forEach(function(btn) {
            btn.classList.remove('active');
        });
        var activeBtn = document.getElementById('booksDensity' + idSuffix[size]);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        try {
            localStorage.setItem('bookingsCardDensity', size);
        } catch (e) {}
    };
    
    // Switch View
    window.switchView = function(view) {
        currentView = view;
        document.querySelectorAll('.books-view-toggle button').forEach(btn => btn.classList.remove('active'));
        document.getElementById('view' + view.charAt(0).toUpperCase() + view.slice(1)).classList.add('active');
        
        document.querySelectorAll('.table-view').forEach(el => {
            el.style.display = view === 'table' ? 'block' : 'none';
        });
        document.querySelectorAll('.card-view').forEach(el => {
            el.style.display = view === 'cards' ? 'block' : 'none';
        });
        
        // Save preference
        localStorage.setItem('bookingsView', view);
    };
    
    // Load saved view preference
    const savedView = localStorage.getItem('bookingsView');
    if (savedView) {
        switchView(savedView);
    }

    var savedBooksDensity = 'medium';
    try {
        savedBooksDensity = localStorage.getItem('bookingsCardDensity') || 'medium';
    } catch (e) {}
    if (typeof window.setBooksCardDensity === 'function') {
        window.setBooksCardDensity(savedBooksDensity);
    }
    
    // Lazy loading — GET must use query string (request body is ignored for GET in fetch)
    const lazyLoadIndexUrl = @json(route('books.index'));
    let lazyLoadGroupBy = @json($groupBy);
    let lazyObserver = null;

    function updateFilterBadge(count) {
        const badge = document.getElementById('booksFilterBadge');
        if (!badge) return;
        if (count > 0) {
            badge.classList.remove('d-none');
            badge.textContent = count + ' active';
        } else {
            badge.classList.add('d-none');
        }
    }

    function setupLazyLoadObserver() {
        if (lazyObserver) {
            lazyObserver.disconnect();
            lazyObserver = null;
        }
        const trigger = document.getElementById('lazyLoadTrigger');
        const bookingsTableBody = document.getElementById('bookingsTableBody');
        if (!trigger || !bookingsTableBody || !hasMore) {
            return;
        }
        lazyObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && hasMore && !isLoading) {
                    loadMoreBookings();
                }
            });
        }, { threshold: 0.1 });
        lazyObserver.observe(trigger);
    }

    function reinitBooksTableResize() {
        if (typeof window.initBooksTableColumnResize !== 'function') return;
        document.querySelectorAll('table.books-looker-table').forEach(function(table) {
            delete table.dataset.booksColumnResizeInit;
            window.initBooksTableColumnResize(table);
        });
    }

    function applyFiltersAjax() {
        const form = document.getElementById('filterForm');
        if (!form) return;
        const params = new URLSearchParams(new FormData(form));
        params.set('books_list_partial', '1');
        const url = lazyLoadIndexUrl + '?' + params.toString();
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function(data) {
            const container = document.getElementById('bookingsContainer');
            if (container && data.html) {
                container.innerHTML = data.html;
            }
            if (typeof data.groupBy === 'string') {
                lazyLoadGroupBy = data.groupBy;
            }
            hasMore = !!data.hasMore;
            currentPage = 1;
            if (typeof data.activeFilterCount === 'number') {
                updateFilterBadge(data.activeFilterCount);
            }
            const cleanParams = new URLSearchParams(new FormData(form));
            const cleanQs = cleanParams.toString();
            history.replaceState(null, '', lazyLoadIndexUrl + (cleanQs ? '?' + cleanQs : ''));
            switchView(currentView);
            try {
                var d = localStorage.getItem('bookingsCardDensity') || 'medium';
                if (typeof window.setBooksCardDensity === 'function') {
                    window.setBooksCardDensity(d);
                }
            } catch (e) {}
            setupLazyLoadObserver();
            reinitBooksTableResize();
        })
        .catch(function() {
            const fallback = new URLSearchParams(new FormData(form)).toString();
            window.location.href = lazyLoadIndexUrl + (fallback ? '?' + fallback : '');
        });
    }

    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFiltersAjax();
        });
    }

    const groupBySelect = document.querySelector('select[name="group_by"]');
    if (groupBySelect) {
        groupBySelect.addEventListener('change', function() {
            applyFiltersAjax();
        });
    }

    const clearLink = document.getElementById('booksFilterClearLink');
    if (clearLink) {
        clearLink.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('filterForm');
            if (!form) return;
            form.querySelectorAll('input, select').forEach(function(el) {
                if (el.name === 'group_by') {
                    el.value = 'none';
                } else if (el.name === 'date_range') {
                    el.value = 'all';
                } else if (el.type === 'text' || el.type === 'date' || el.type === 'number' || el.type === 'search') {
                    el.value = '';
                } else if (el.tagName === 'SELECT' && el.name !== 'group_by') {
                    el.selectedIndex = 0;
                }
            });
            applyFiltersAjax();
        });
    }

    setupLazyLoadObserver();
    
    function loadMoreBookings() {
        if (isLoading || !hasMore) return;
        if (!document.getElementById('bookingsTableBody')) return;
        
        isLoading = true;
        currentPage++;
        
        const spinner = document.getElementById('lazyLoadSpinner');
        if (spinner) spinner.classList.add('active');
        
        const form = document.getElementById('filterForm');
        const params = new URLSearchParams(form ? new FormData(form) : undefined);
        params.set('page', String(currentPage));
        params.set('view', currentView);
        params.set('group_by', lazyLoadGroupBy);
        
        const url = lazyLoadIndexUrl + (params.toString() ? '?' + params.toString() : '');
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.html) {
                if (currentView === 'table') {
                    const tbody = document.getElementById('bookingsTableBody');
                    if (tbody) {
                        tbody.insertAdjacentHTML('beforeend', data.html);
                    }
                } else {
                    const cardView = document.querySelector('#bookingsContainer > .canvas-panel .card-view .books-card-view-inner');
                    if (cardView) {
                        cardView.insertAdjacentHTML('beforeend', data.html);
                    }
                }
            }
            
            hasMore = !!data.hasMore;
            if (!hasMore) {
                const end = document.getElementById('lazyLoadEnd');
                if (end) end.style.display = 'block';
            }
        })
        .catch(function(error) {
            console.error('Error loading bookings:', error);
            currentPage--;
        })
        .finally(function() {
            isLoading = false;
            if (spinner) spinner.classList.remove('active');
        });
    }
    
    // Show Booking Info
    window.showBookingInfo = function(bookId) {
        const modal = new bootstrap.Modal(document.getElementById('bookingInfoModal'));
        const content = document.getElementById('bookingInfoContent');
        
        content.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        modal.show();
        
        fetch(`/books/${bookId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Request failed: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.html) {
                content.innerHTML = data.html;
            } else if (data.book) {
                var b = data.book;
                content.innerHTML = '<div class="booking-modal-info"><p><strong>Booking #' + b.id + '</strong></p>' +
                    '<p><strong>Client:</strong> ' + (b.client ? (b.client.company || b.client.name) : 'N/A') + '</p>' +
                    '<p><strong>Date:</strong> ' + (b.date_book || 'N/A') + '</p>' +
                    '<p><strong>Booths:</strong> ' + (b.booth_count || 0) + '</p>' +
                    '<p><strong>Total:</strong> $' + parseFloat(b.total_amount || 0).toFixed(2) + '</p>' +
                    '<a href="/books/' + b.id + '" class="btn btn-sm btn-primary">View Full Details</a></div>';
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load booking details.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading booking info:', error);
            content.innerHTML = '<div class="alert alert-danger">Error loading booking details.</div>';
        });
    };
    
    // Refresh Page
    window.refreshPage = function() {
        window.location.reload();
    };

    // Delete Booking (used by table row and card action buttons)
    window.deleteBooking = function(id) {
        if (typeof Swal === 'undefined') {
            if (confirm('Delete this booking? This will release all booths. This action cannot be undone!')) {
                document.getElementById('delete-booking-form-' + id)?.submit();
            }
            return;
        }
        Swal.fire({
            title: 'Delete Booking?',
            text: 'This will release all booths in this booking. This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                fetch('/books/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    Swal.close();
                    if (data.success) {
                        Swal.fire('Deleted!', data.message || 'Booking has been deleted.', 'success').then(function() {
                            window.location.href = '{{ route("books.index") }}';
                        });
                    } else {
                        Swal.fire('Error!', data.message || 'Failed to delete booking.', 'error');
                    }
                })
                .catch(function(error) {
                    Swal.close();
                    Swal.fire('Error!', 'An error occurred while deleting the booking.', 'error');
                    console.error('Error:', error);
                });
            }
        });
    };

    // Delete All Modal
    window.showDeleteAllModal = function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete All Records?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete all',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Implement delete all functionality
                    Swal.fire('Deleted!', 'All records have been deleted.', 'success');
                }
            });
        }
    };
    
    // Quick date preset - uses date_range for today/7days, date_from/date_to for 30days
    window.setQuickDate = function(preset) {
        const form = document.getElementById('filterForm');
        const dateRangeSelect = form.querySelector('select[name="date_range"]');
        const fromInput = form.querySelector('input[name="date_from"]');
        const toInput = form.querySelector('input[name="date_to"]');

        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        if (dateRangeSelect) dateRangeSelect.value = preset === '' ? 'all' : (preset === '30days' ? 'all' : preset);

        if (preset === '30days') {
            const today = new Date();
            const y = today.getFullYear();
            const m = String(today.getMonth() + 1).padStart(2, '0');
            const d = String(today.getDate()).padStart(2, '0');
            const todayStr = y + '-' + m + '-' + d;
            const past = new Date(today);
            past.setDate(past.getDate() - 29);
            const py = past.getFullYear();
            const pm = String(past.getMonth() + 1).padStart(2, '0');
            const pd = String(past.getDate()).padStart(2, '0');
            if (fromInput) fromInput.value = py + '-' + pm + '-' + pd;
            if (toInput) toInput.value = todayStr;
        }
        applyFiltersAjax();
    };

    // Instant Search (debounced)
    let searchTimeout;
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyFiltersAjax();
            }, 500);
        });
    }
})();
</script>
@endpush
@endsection
