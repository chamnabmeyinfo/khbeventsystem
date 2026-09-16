@php
    // Detect active guard
    $user = null;
    $roleName = 'User';
    
    if (auth('admin')->check()) {
        $user = auth('admin')->user();
        $roleName = 'Administrator';
    } elseif (auth()->check()) {
        $user = auth()->user();
        $roleName = $user->isAdmin() ? 'Administrator' : 'Sales Rep';
    }
    
    $companySettings = \App\Models\Setting::getCompanySettings();
    $logoPath = trim(str_replace('\\', '/', $companySettings['company_logo'] ?? ''));
    $logoUrl = $logoPath ? \App\Helpers\AssetHelper::imageUrl(ltrim($logoPath, '/')) : null;

    // Git & cPanel Deployment Status
    $gitStatus = [];
    try {
        $gitDeployService = app(\App\Services\GitDeploymentService::class);
        $gitStatus = $gitDeployService->getStatus();
    } catch (\Throwable $e) {
        $gitStatus = [
            'repository' => ['name' => 'khbeventsystem', 'full_name' => 'chamnabmeyinfo/khbeventsystem', 'url' => 'https://github.com/chamnabmeyinfo/khbeventsystem'],
            'environment' => ['type' => 'Server', 'repo_path' => '/home/khbevents/repositories/khbeventsystem'],
            'local' => ['branch' => 'main', 'sha' => 'unknown', 'message' => '', 'author_name' => '', 'date_human' => ''],
            'remote' => ['sha' => null],
            'sync' => ['status' => 'unknown', 'message' => 'Status unavailable', 'behind_by' => 0],
            'cpanel' => ['pull_command' => 'cd /home/khbevents/repositories/khbeventsystem && git pull origin main', 'repo_path' => '/home/khbevents/repositories/khbeventsystem'],
            'checked_at_human' => 'Just now',
        ];
    }
    $localGit = $gitStatus['local'] ?? [];
    $remoteGit = $gitStatus['remote'] ?? [];
    $syncData = $gitStatus['sync'] ?? [];
    $gitBranch = $localGit['branch'] ?? 'main';
    $gitSha = $localGit['sha'] ?? 'unknown';
    $gitSyncStatus = $syncData['status'] ?? 'unknown';
    $gitBehindBy = (int) ($syncData['behind_by'] ?? 0);
    $cpanelPullCmd = $gitStatus['cpanel']['pull_command'] ?? 'cd /home/khbevents/repositories/khbeventsystem && git pull origin main';
@endphp

<header class="modern-unified-header {{ $headerClass ?? '' }}">
    <div class="header-left">
        @if($useSidebarToggle ?? true)
            <button class="sidebar-toggle-btn" role="button" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        @endif

        <a href="{{ route('dashboard') }}" class="header-brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo" class="brand-icon-wrapper" style="object-fit: contain; padding: 4px; background: white;">
            @else
                <div class="brand-icon-wrapper">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            @endif
            <div class="brand-text-modern">
                <span class="brand-name">{{ $companySettings['company_name'] ?? 'KHB Booths' }}</span>
                <span class="brand-sub">Management System</span>
            </div>
        </a>
    </div>

    <div class="header-center">
        <div class="modern-search-wrapper">
            <i class="fas fa-search search-icon-fixed"></i>
            <input type="text" class="modern-search-input" id="globalModernSearch" placeholder="Search booths, clients, bookings..." autocomplete="off">
            <span class="search-shortcut">⌘ K</span>
        </div>
    </div>

    <div class="header-right">
        <!-- GitHub Repo & cPanel Pull Status Pill -->
        <button type="button" 
                class="git-deploy-pill" 
                id="gitHeaderPill" 
                data-bs-toggle="modal" 
                data-bs-target="#gitDeployModal" 
                data-toggle="modal"
                data-target="#gitDeployModal"
                title="View GitHub Commit & cPanel Sync Status">
            <span class="git-pill-icon"><i class="fab fa-github"></i></span>
            <span class="git-pill-branch"><i class="fas fa-code-branch me-1 text-primary"></i><span id="gitPillBranch">{{ $gitBranch }}</span></span>
            <span class="git-pill-sha" id="gitPillSha">{{ $gitSha }}</span>
            <span class="git-status-dot dot-{{ $gitSyncStatus }}" id="gitPillDot"></span>
            <span class="git-pill-badge" id="gitPillBadge">
                @if($gitSyncStatus === 'synced')
                    <span class="text-success d-none d-xl-inline">cPanel Synced</span>
                @elseif($gitSyncStatus === 'behind')
                    <span class="git-behind-pill">{{ $gitBehindBy }} Behind (Pull)</span>
                @elseif($gitSyncStatus === 'ahead')
                    <span class="text-info">{{ $syncData['ahead_by'] ?? 1 }} Ahead</span>
                @else
                    <span class="text-muted d-none d-xl-inline">Git</span>
                @endif
            </span>
        </button>

        <!-- Dashboard Link -->
        <a href="{{ route('dashboard') }}" class="header-action-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
            <i class="fas fa-th-large"></i>
        </a>

        <!-- Booths Link -->
        <a href="{{ route('booths.index') }}" class="header-action-btn {{ request()->routeIs('booths.*') ? 'active' : '' }}" title="Booths">
            <i class="fas fa-store"></i>
        </a>

        <!-- Notifications -->
        <div class="header-action-btn notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">3</span>
        </div>

        @auth
            <div class="dropdown">
                <div class="user-profile-pill" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-modern">
                        {{ strtoupper(substr($user->username ?? $user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="user-info-text">
                        <span class="user-name-header">{{ $user->username ?? $user->name }}</span>
                        <span class="user-role-header">{{ $roleName }}</span>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" aria-labelledby="userDropdown" style="border-radius: 12px; min-width: 200px; z-index: 1100;">
                    <li class="p-2 border-bottom mb-2">
                        <div class="fw-bold">{{ $user->username ?? $user->name }}</div>
                        <div class="text-muted small">{{ $user->email ?? 'No email set' }}</div>
                    </li>
                    @if(auth('admin')->check())
                        <li><a class="dropdown-item rounded-2 py-2" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Admin Dashboard</a></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="{{ route('admin.events.index') }}"><i class="fas fa-calendar-alt me-2 text-info"></i>Manage Events</a></li>
                    @endif
                    <li><a class="dropdown-item rounded-2 py-2" href="{{ route('dashboard') }}"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Booth Dashboard</a></li>
                    <li><a class="dropdown-item rounded-2 py-2" href="{{ route('booths.my-booths') }}"><i class="fas fa-id-badge me-2 text-info"></i>My Booths</a></li>
                    @if(auth()->check() && $user->isAdmin())
                        <li><a class="dropdown-item rounded-2 py-2" href="{{ route('settings.index') }}"><i class="fas fa-cog me-2 text-secondary"></i>Settings</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ auth('admin')->check() ? route('admin.logout') : route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item rounded-2 py-2 text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @else
            <a href="{{ route('login') }}" class="modern-btn modern-btn-primary py-2 px-4 shadow-sm" style="font-size: 0.85rem; border-radius: 30px;">
                Login
            </a>
        @endauth
    </div>
</header>

<!-- GitHub & cPanel Deployment Status Modal -->
<div class="modal fade" id="gitDeployModal" tabindex="-1" aria-labelledby="gitDeployModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fab fa-github fa-2x text-white me-2"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="gitDeployModalLabel">GitHub Repo & cPanel Deployment</h5>
                        <small class="text-white-50">Repository & live synchronization status</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Sync Status Banner -->
                <div id="gitModalSyncBanner" class="mb-4">
                    @if($gitSyncStatus === 'synced')
                        <div class="alert alert-success d-flex align-items-center rounded-3 p-3 mb-0">
                            <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                            <div>
                                <h6 class="fw-bold mb-1">cPanel is Up to Date</h6>
                                <p class="mb-0 small text-muted">The deployed code matches GitHub <strong class="text-dark">{{ $gitBranch }}</strong> (Commit <code>{{ $gitSha }}</code>). No pull required.</p>
                            </div>
                        </div>
                    @elseif($gitSyncStatus === 'behind')
                        <div class="alert alert-warning d-flex align-items-center rounded-3 p-3 mb-0">
                            <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Update Pending — Pull Required on cPanel</h6>
                                <p class="mb-0 small text-muted">cPanel is <strong>{{ $gitBehindBy }} commit(s)</strong> behind GitHub <strong class="text-dark">{{ $gitBranch }}</strong>. Run a pull on the server to update.</p>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info d-flex align-items-center rounded-3 p-3 mb-0">
                            <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Status: {{ ucfirst($gitSyncStatus) }}</h6>
                                <p class="mb-0 small text-muted">{{ $syncData['message'] ?? 'Git repository active.' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="row g-3 mb-4">
                    <!-- Local / cPanel Deployed Commit -->
                    <div class="col-md-6">
                        <div class="git-info-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary text-white"><i class="fas fa-server me-1"></i> Active Deployment</span>
                                <span class="badge bg-secondary" id="gitModalBranch"><i class="fas fa-code-branch me-1"></i>{{ $gitBranch }}</span>
                            </div>
                            <div class="mb-2">
                                <div class="text-muted small">Current Commit:</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <code class="px-2 py-1 bg-white border rounded fw-bold text-primary" id="gitModalSha">{{ $gitSha }}</code>
                                    @if(!empty($localGit['github_commit_url']))
                                        <a href="{{ $localGit['github_commit_url'] }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2" title="View on GitHub">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="fw-semibold text-dark text-truncate" id="gitModalMessage" title="{{ $localGit['message'] ?? '' }}">
                                    {{ $localGit['message'] ?? 'No message available' }}
                                </div>
                            </div>
                            <div class="text-muted small" id="gitModalMeta">
                                <i class="fas fa-user-circle me-1"></i> {{ $localGit['author_name'] ?? 'Committer' }}
                                • <i class="fas fa-clock ms-1 me-1"></i> {{ $localGit['date_human'] ?? 'Recent' }}
                            </div>
                        </div>
                    </div>

                    <!-- Remote GitHub Info -->
                    <div class="col-md-6">
                        <div class="git-info-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-dark text-white"><i class="fab fa-github me-1"></i> GitHub Remote</span>
                                <a href="https://github.com/chamnabmeyinfo/khbeventsystem" target="_blank" class="small text-decoration-none text-primary">
                                    chamnabmeyinfo/khbeventsystem <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            </div>
                            <div class="mb-2">
                                <div class="text-muted small">Latest Remote HEAD:</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <code class="px-2 py-1 bg-white border rounded fw-bold text-dark" id="gitModalRemoteSha">{{ $remoteGit['sha'] ?? $gitSha }}</code>
                                    <span id="gitModalRemoteBadge">
                                        @if($gitSyncStatus === 'synced')
                                            <span class="badge bg-success text-white">Synced</span>
                                        @elseif($gitSyncStatus === 'behind')
                                            <span class="badge bg-warning text-dark">{{ $gitBehindBy }} new</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="fw-semibold text-dark text-truncate" id="gitModalRemoteMessage" title="{{ $remoteGit['message'] ?? '' }}">
                                    {{ !empty($remoteGit['message']) ? explode("\n", $remoteGit['message'])[0] : ($localGit['message'] ?? '') }}
                                </div>
                            </div>
                            <div class="text-muted small" id="gitModalRemoteMeta">
                                <i class="fas fa-code-commit me-1"></i> Origin branch: <strong>{{ $gitBranch }}</strong>
                                • <i class="fas fa-shield-alt ms-1 me-1"></i> Verified
                            </div>
                        </div>
                    </div>
                </div>

                <!-- One-Click Pull on cPanel Command -->
                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label fw-bold mb-0 text-dark small text-uppercase letter-spacing-1">
                            <i class="fas fa-terminal me-1 text-primary"></i> cPanel Pull Command (Terminal / SSH)
                        </label>
                        <small class="text-muted">Runs from repository root</small>
                    </div>
                    <div class="git-terminal-box">
                        <span class="git-terminal-code" id="gitPullCommandText">{{ $cpanelPullCmd }}</span>
                        <button type="button" class="btn btn-sm btn-primary px-3 text-nowrap" id="copyGitPullCmdBtn">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i> <strong>cPanel UI:</strong> Go to <em>cPanel → Git™ Version Control</em> → Click <strong>Update from Remote</strong>.
                        </small>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light d-flex justify-content-between">
                <div class="small text-muted" id="gitModalCheckedAt">
                    <i class="fas fa-history me-1"></i> Checked: {{ $gitStatus['checked_at_human'] ?? 'Just now' }}
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="gitRefreshBtn">
                        <i class="fas fa-sync-alt me-1" id="gitRefreshIcon"></i> Refresh Status
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy pull command
    const copyBtn = document.getElementById('copyGitPullCmdBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const cmdEl = document.getElementById('gitPullCommandText');
            const cmd = cmdEl ? cmdEl.innerText.trim() : '';
            if (!cmd) return;
            
            navigator.clipboard.writeText(cmd).then(function() {
                copyBtn.innerHTML = '<i class="fas fa-check text-white me-1"></i> Copied!';
                copyBtn.classList.remove('btn-primary');
                copyBtn.classList.add('btn-success');
                setTimeout(function() {
                    copyBtn.innerHTML = '<i class="fas fa-copy me-1"></i> Copy';
                    copyBtn.classList.remove('btn-success');
                    copyBtn.classList.add('btn-primary');
                }, 2000);
            });
        });
    }

    // Refresh Git & cPanel status
    const refreshBtn = document.getElementById('gitRefreshBtn');
    const refreshIcon = document.getElementById('gitRefreshIcon');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            if (refreshIcon) refreshIcon.classList.add('fa-spin');
            refreshBtn.disabled = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch('{{ route("admin.git-status.refresh") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                refreshBtn.disabled = false;

                if (res.success && res.data) {
                    const data = res.data;
                    const local = data.local || {};
                    const remote = data.remote || {};
                    const sync = data.sync || {};
                    const status = sync.status || 'unknown';

                    // Update header pill
                    const shaEl = document.getElementById('gitPillSha');
                    if (shaEl && local.sha) shaEl.innerText = local.sha;

                    const branchEl = document.getElementById('gitPillBranch');
                    if (branchEl && local.branch) branchEl.innerText = local.branch;

                    const dotEl = document.getElementById('gitPillDot');
                    if (dotEl) {
                        dotEl.className = 'git-status-dot dot-' + status;
                    }

                    const badgeEl = document.getElementById('gitPillBadge');
                    if (badgeEl) {
                        if (status === 'synced') {
                            badgeEl.innerHTML = '<span class="text-success d-none d-xl-inline">cPanel Synced</span>';
                        } else if (status === 'behind') {
                            badgeEl.innerHTML = '<span class="git-behind-pill">' + (sync.behind_by || 1) + ' Behind (Pull)</span>';
                        } else if (status === 'ahead') {
                            badgeEl.innerHTML = '<span class="text-info">' + (sync.ahead_by || 1) + ' Ahead</span>';
                        } else {
                            badgeEl.innerHTML = '<span class="text-muted d-none d-xl-inline">Git</span>';
                        }
                    }

                    // Update modal elements
                    const modalSha = document.getElementById('gitModalSha');
                    if (modalSha && local.sha) modalSha.innerText = local.sha;

                    const modalBranch = document.getElementById('gitModalBranch');
                    if (modalBranch && local.branch) modalBranch.innerHTML = '<i class="fas fa-code-branch me-1"></i>' + local.branch;

                    const modalMsg = document.getElementById('gitModalMessage');
                    if (modalMsg && local.message) modalMsg.innerText = local.message;

                    const modalRemoteSha = document.getElementById('gitModalRemoteSha');
                    if (modalRemoteSha && remote.sha) modalRemoteSha.innerText = remote.sha;

                    const modalRemoteMsg = document.getElementById('gitModalRemoteMessage');
                    if (modalRemoteMsg && remote.message) {
                        modalRemoteMsg.innerText = remote.message.split('\n')[0];
                    }

                    const checkedAt = document.getElementById('gitModalCheckedAt');
                    if (checkedAt) checkedAt.innerHTML = '<i class="fas fa-history me-1"></i> Checked: Just now';

                    // Update sync banner
                    const syncBanner = document.getElementById('gitModalSyncBanner');
                    if (syncBanner) {
                        if (status === 'synced') {
                            syncBanner.innerHTML = `
                                <div class="alert alert-success d-flex align-items-center rounded-3 p-3 mb-0">
                                    <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">cPanel is Up to Date</h6>
                                        <p class="mb-0 small text-muted">The deployed code matches GitHub <strong class="text-dark">${local.branch || 'main'}</strong> (Commit <code>${local.sha || ''}</code>). No pull required.</p>
                                    </div>
                                </div>
                            `;
                        } else if (status === 'behind') {
                            syncBanner.innerHTML = `
                                <div class="alert alert-warning d-flex align-items-center rounded-3 p-3 mb-0">
                                    <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Update Pending — Pull Required on cPanel</h6>
                                        <p class="mb-0 small text-muted">cPanel is <strong>${sync.behind_by || 1} commit(s)</strong> behind GitHub <strong class="text-dark">${local.branch || 'main'}</strong>. Run a pull on the server to update.</p>
                                    </div>
                                </div>
                            `;
                        }
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success('Git & cPanel deployment status updated!');
                    }
                }
            })
            .catch(err => {
                if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                refreshBtn.disabled = false;
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to refresh status.');
                }
            });
        });
    }
});
</script>
