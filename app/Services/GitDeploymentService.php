<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitDeploymentService
{
    protected string $repoOwner = 'chamnabmeyinfo';
    protected string $repoName = 'khbeventsystem';
    protected string $defaultBranch = 'main';
    protected string $cacheKey = 'cpanel_git_deployment_status_v2';
    protected int $cacheTtlMinutes = 5;

    /**
     * Get deployment status (cached by default, or force fresh).
     */
    public function getStatus(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            $this->clearCache();
        }

        try {
            if (function_exists('app') && app()->has('cache')) {
                return Cache::remember($this->cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () {
                    return $this->resolveStatus();
                });
            }
        } catch (\Throwable $e) {
            // Fall through if container/cache is not available
        }

        return $this->resolveStatus();
    }

    /**
     * Clear cached deployment status.
     */
    public function clearCache(): void
    {
        try {
            if (function_exists('app') && app()->has('cache')) {
                Cache::forget($this->cacheKey);
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }
    }

    /**
     * Resolve full deployment and sync status.
     */
    public function resolveStatus(): array
    {
        $localGit = $this->getLocalGitInfo();
        $remoteGit = $this->getRemoteGitHubInfo($localGit['branch'] ?? $this->defaultBranch);

        $syncStatus = 'unknown';
        $syncMessage = 'Status unavailable';
        $behindBy = 0;
        $aheadBy = 0;
        $pendingCommits = [];

        if (! empty($localGit['sha']) && ! empty($remoteGit['sha'])) {
            if ($localGit['sha'] === $remoteGit['sha']) {
                $syncStatus = 'synced';
                $syncMessage = 'Up to date with GitHub main';
            } else {
                // If SHAs differ, check comparison
                $comparison = $this->compareWithRemote($localGit['sha'], $remoteGit['sha']);
                $behindBy = $comparison['behind_by'] ?? 0;
                $aheadBy = $comparison['ahead_by'] ?? 0;
                $pendingCommits = $comparison['commits'] ?? [];

                if ($behindBy > 0 && $aheadBy === 0) {
                    $syncStatus = 'behind';
                    $syncMessage = "cPanel is {$behindBy} commit(s) behind GitHub. Pull required.";
                } elseif ($aheadBy > 0 && $behindBy === 0) {
                    $syncStatus = 'ahead';
                    $syncMessage = "Local is {$aheadBy} commit(s) ahead of GitHub.";
                } elseif ($behindBy > 0 && $aheadBy > 0) {
                    $syncStatus = 'diverged';
                    $syncMessage = "Branches have diverged ({$behindBy} behind, {$aheadBy} ahead).";
                } else {
                    // Default to behind if commit hashes don't match
                    $syncStatus = 'behind';
                    $syncMessage = 'cPanel update available. Pull recommended.';
                    $behindBy = 1;
                }
            }
        } elseif (! empty($localGit['sha'])) {
            $syncStatus = 'offline';
            $syncMessage = 'GitHub status temporarily unreachable';
        }

        $environment = $this->detectEnvironment();

        return [
            'repository' => [
                'owner' => $this->repoOwner,
                'name' => $this->repoName,
                'full_name' => "{$this->repoOwner}/{$this->repoName}",
                'url' => "https://github.com/{$this->repoOwner}/{$this->repoName}",
            ],
            'environment' => $environment,
            'local' => $localGit,
            'remote' => $remoteGit,
            'sync' => [
                'status' => $syncStatus, // synced, behind, ahead, diverged, offline, unknown
                'message' => $syncMessage,
                'behind_by' => $behindBy,
                'ahead_by' => $aheadBy,
                'pending_commits' => $pendingCommits,
            ],
            'cpanel' => [
                'pull_command' => 'cd ' . $environment['repo_path'] . ' && git pull origin main',
                'docroot' => $environment['docroot'] ?? null,
                'repo_path' => $environment['repo_path'],
            ],
            'checked_at' => Carbon::now()->toIso8601String(),
            'checked_at_human' => Carbon::now()->diffForHumans(),
        ];
    }

    /**
     * Safe base path resolver.
     */
    protected function getBasePath(string $path = ''): string
    {
        if (function_exists('base_path')) {
            try {
                if (function_exists('app') && app()->has('path.base')) {
                    return base_path($path);
                }
            } catch (\Throwable) {
                // Ignore
            }
        }

        $root = dirname(__DIR__, 2);
        return $path ? $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $root;
    }

    /**
     * Detect running environment and paths.
     */
    protected function detectEnvironment(): array
    {
        $basePath = $this->getBasePath();
        $isCPanel = str_contains($basePath, '/home/khbevents') || str_contains($basePath, 'system.khbevents.com');
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        $cpanelRepoPath = '/home/khbevents/repositories/khbeventsystem';
        if (is_dir('/home/khbevents/system.khbevents.com/.git')) {
            $cpanelRepoPath = '/home/khbevents/system.khbevents.com';
        }

        return [
            'is_cpanel' => $isCPanel,
            'is_windows' => $isWindows,
            'type' => $isCPanel ? 'cPanel Production' : ($isWindows ? 'Local Windows' : 'Development Server'),
            'current_path' => $basePath,
            'repo_path' => $isCPanel ? $cpanelRepoPath : $basePath,
        ];
    }

    /**
     * Retrieve local Git info using CLI with native PHP file fallback.
     */
    public function getLocalGitInfo(): array
    {
        // 1. Try CLI git if available and functions are enabled
        if ($this->isCommandAvailable('git') && ! $this->isFunctionDisabled('shell_exec')) {
            $cliInfo = $this->getLocalGitFromCli();
            if (! empty($cliInfo['sha'])) {
                return $cliInfo;
            }
        }

        // 2. Pure PHP fallback: read directly from .git directory
        $fileInfo = $this->getLocalGitFromFiles();
        if (! empty($fileInfo['sha'])) {
            return $fileInfo;
        }

        // 3. Fallback to cached file or defaults
        return $this->getLocalGitDefaults();
    }

    /**
     * Read local Git details via CLI commands.
     */
    protected function getLocalGitFromCli(): array
    {
        try {
            $isWindows = DIRECTORY_SEPARATOR === '\\';
            $nullDev = $isWindows ? '2>nul' : '2>/dev/null';

            $branch = trim((string) @shell_exec("git rev-parse --abbrev-ref HEAD {$nullDev}"));
            $sha = trim((string) @shell_exec("git rev-parse --short HEAD {$nullDev}"));
            $fullSha = trim((string) @shell_exec("git rev-parse HEAD {$nullDev}"));

            // Format: %h|%an|%ae|%cd|%s
            $log = trim((string) @shell_exec("git log -1 --pretty=format:\"%h|%an|%ae|%cd|%s\" --date=iso {$nullDev}"));

            if ($log && str_contains($log, '|')) {
                $parts = explode('|', $log, 5);
                $dateStr = $parts[3] ?? '';
                $carbonDate = ! empty($dateStr) ? Carbon::parse($dateStr) : null;

                return [
                    'source' => 'cli',
                    'branch' => $branch ?: $this->defaultBranch,
                    'sha' => $sha ?: ($parts[0] ?? substr($fullSha, 0, 7)),
                    'full_sha' => $fullSha,
                    'author_name' => $parts[1] ?? 'Unknown',
                    'author_email' => $parts[2] ?? '',
                    'date' => $carbonDate ? $carbonDate->toIso8601String() : null,
                    'date_human' => $carbonDate ? $carbonDate->diffForHumans() : 'Recently',
                    'message' => $parts[4] ?? 'Commit',
                    'github_commit_url' => $fullSha ? "https://github.com/{$this->repoOwner}/{$this->repoName}/commit/{$fullSha}" : null,
                ];
            }

            if ($sha) {
                return [
                    'source' => 'cli_basic',
                    'branch' => $branch ?: $this->defaultBranch,
                    'sha' => $sha,
                    'full_sha' => $fullSha,
                    'author_name' => 'Git Repository',
                    'author_email' => '',
                    'date' => null,
                    'date_human' => 'Active',
                    'message' => 'Latest checkout',
                    'github_commit_url' => $fullSha ? "https://github.com/{$this->repoOwner}/{$this->repoName}/commit/{$fullSha}" : null,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return [];
    }

    /**
     * Pure PHP parser: Reads Git refs & logs directly without shell_exec.
     */
    protected function getLocalGitFromFiles(): array
    {
        $base = $this->getBasePath();
        $gitDir = $base . DIRECTORY_SEPARATOR . '.git';
        if (! is_dir($gitDir)) {
            // Check if .git is in parent directory
            $parentGit = dirname($base) . DIRECTORY_SEPARATOR . '.git';
            if (is_dir($parentGit)) {
                $gitDir = $parentGit;
            } else {
                return [];
            }
        }

        try {
            $branch = $this->defaultBranch;
            $fullSha = null;

            // 1. Read HEAD file
            $headPath = $gitDir . DIRECTORY_SEPARATOR . 'HEAD';
            if (is_file($headPath)) {
                $headContent = trim((string) file_get_contents($headPath));
                if (str_starts_with($headContent, 'ref:')) {
                    $ref = trim(substr($headContent, 4));
                    $parts = explode('/', str_replace('\\', '/', $ref));
                    $branch = end($parts) ?: $this->defaultBranch;

                    // Read ref file
                    $refPath = $gitDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ref);
                    if (is_file($refPath)) {
                        $fullSha = trim((string) file_get_contents($refPath));
                    }
                } else {
                    // Detached HEAD
                    $fullSha = $headContent;
                }
            }

            // 2. Check packed-refs if ref file was not found
            if (! $fullSha && is_file($gitDir . DIRECTORY_SEPARATOR . 'packed-refs')) {
                $packed = file_get_contents($gitDir . DIRECTORY_SEPARATOR . 'packed-refs');
                if (preg_match('/^([a-f0-9]{40})\s+refs\/heads\/' . preg_quote($branch, '/') . '$/m', $packed, $m)) {
                    $fullSha = $m[1];
                }
            }

            if (! $fullSha) {
                return [];
            }

            $sha = substr($fullSha, 0, 7);
            $authorName = 'Git User';
            $commitMessage = 'Latest commit';
            $carbonDate = null;

            // 3. Read reflog from .git/logs/HEAD to get message and author
            $logsHeadPath = $gitDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'HEAD';
            if (is_file($logsHeadPath)) {
                $lines = file($logsHeadPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (! empty($lines)) {
                    $lastLine = end($lines);
                    // Format: <old-sha> <new-sha> <name> <<email>> <timestamp> <tz>\t<message>
                    if (preg_match('/^[a-f0-9]{40}\s+([a-f0-9]{40})\s+([^<]+)\s+<([^>]+)>\s+(\d+)\s+[+\-]?\d{4}\s+(.*)$/', $lastLine, $matches)) {
                        $authorName = trim($matches[2]);
                        $timestamp = (int) $matches[4];
                        $carbonDate = Carbon::createFromTimestamp($timestamp);
                        $msg = trim($matches[5]);
                        if (str_starts_with($msg, 'commit: ')) {
                            $commitMessage = substr($msg, 8);
                        } elseif (str_starts_with($msg, 'pull: ')) {
                            $commitMessage = substr($msg, 6);
                        } else {
                            $commitMessage = $msg;
                        }
                    }
                }
            }

            return [
                'source' => 'file_parser',
                'branch' => $branch,
                'sha' => $sha,
                'full_sha' => $fullSha,
                'author_name' => $authorName,
                'author_email' => '',
                'date' => $carbonDate ? $carbonDate->toIso8601String() : null,
                'date_human' => $carbonDate ? $carbonDate->diffForHumans() : 'Recently',
                'message' => $commitMessage,
                'github_commit_url' => "https://github.com/{$this->repoOwner}/{$this->repoName}/commit/{$fullSha}",
            ];
        } catch (\Throwable $e) {
            // Fallback
        }

        return [];
    }

    /**
     * Fallback defaults if no git data found.
     */
    protected function getLocalGitDefaults(): array
    {
        return [
            'source' => 'fallback',
            'branch' => $this->defaultBranch,
            'sha' => 'unknown',
            'full_sha' => null,
            'author_name' => 'System',
            'author_email' => '',
            'date' => null,
            'date_human' => 'Unknown',
            'message' => 'Deployed version',
            'github_commit_url' => "https://github.com/{$this->repoOwner}/{$this->repoName}",
        ];
    }

    /**
     * Fetch latest commit info from GitHub Public API.
     */
    public function getRemoteGitHubInfo(string $branch = 'main'): array
    {
        try {
            $url = "https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/commits/{$branch}";
            $data = $this->fetchJsonOverHttp($url);

            if ($data && ! empty($data['sha'])) {
                $fullSha = $data['sha'];
                $commitData = $data['commit'] ?? [];
                $authorData = $commitData['author'] ?? [];
                $dateStr = $authorData['date'] ?? null;
                $carbonDate = $dateStr ? Carbon::parse($dateStr) : null;

                return [
                    'status' => 'ok',
                    'branch' => $branch,
                    'sha' => substr($fullSha, 0, 7),
                    'full_sha' => $fullSha,
                    'message' => $commitData['message'] ?? '',
                    'author_name' => $authorData['name'] ?? 'GitHub Committer',
                    'date' => $carbonDate ? $carbonDate->toIso8601String() : null,
                    'date_human' => $carbonDate ? $carbonDate->diffForHumans() : '',
                    'html_url' => $data['html_url'] ?? "https://github.com/{$this->repoOwner}/{$this->repoName}/commit/{$fullSha}",
                ];
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return [
            'status' => 'error',
            'branch' => $branch,
            'sha' => null,
            'full_sha' => null,
            'message' => 'Could not query GitHub API',
        ];
    }

    /**
     * Compare local SHA against remote HEAD using GitHub Compare API.
     */
    protected function compareWithRemote(string $localSha, string $remoteSha): array
    {
        try {
            $url = "https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/compare/{$localSha}...{$remoteSha}";
            $data = $this->fetchJsonOverHttp($url);

            if ($data && isset($data['status'])) {
                $commits = [];

                foreach (array_slice($data['commits'] ?? [], 0, 5) as $c) {
                    $commits[] = [
                        'sha' => substr($c['sha'] ?? '', 0, 7),
                        'message' => explode("\n", $c['commit']['message'] ?? '')[0],
                        'author' => $c['commit']['author']['name'] ?? '',
                        'url' => $c['html_url'] ?? '',
                    ];
                }

                return [
                    'status' => $data['status'] ?? 'unknown',
                    'ahead_by' => (int) ($data['ahead_by'] ?? 0),
                    'behind_by' => (int) ($data['behind_by'] ?? 0),
                    'total_commits' => (int) ($data['total_commits'] ?? 0),
                    'commits' => $commits,
                ];
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return [
            'behind_by' => 0,
            'ahead_by' => 0,
            'commits' => [],
        ];
    }

    /**
     * Universal HTTP JSON fetcher using Laravel Http, cURL, or streams.
     */
    protected function fetchJsonOverHttp(string $url): ?array
    {
        // 1. Try Laravel Http facade if available
        if (class_exists(\Illuminate\Support\Facades\Http::class)) {
            try {
                if (function_exists('app') && app()->has('cache')) {
                    $res = Http::withHeaders([
                        'User-Agent' => 'KHB-Events-Application',
                        'Accept' => 'application/vnd.github.v3+json',
                    ])->timeout(6)->get($url);

                    if ($res->successful()) {
                        return $res->json();
                    }
                }
            } catch (\Throwable) {
                // Ignore and fall back to curl
            }
        }

        // 2. Try cURL
        if (function_exists('curl_init')) {
            try {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_USERAGENT => 'KHB-Events-Application',
                    CURLOPT_HTTPHEADER => ['Accept: application/vnd.github.v3+json'],
                    CURLOPT_TIMEOUT => 6,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    return json_decode($response, true);
                }
            } catch (\Throwable) {
                // Ignore and fall back to file_get_contents
            }
        }

        // 3. Try stream context file_get_contents
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: KHB-Events-Application\r\nAccept: application/vnd.github.v3+json\r\n",
                    'timeout' => 6,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                return json_decode($response, true);
            }
        } catch (\Throwable) {
            // Nothing worked
        }

        return null;
    }

    /**
     * Check if a system command exists.
     */
    protected function isCommandAvailable(string $command): bool
    {
        if ($this->isFunctionDisabled('exec') && $this->isFunctionDisabled('shell_exec')) {
            return false;
        }

        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $testCmd = $isWindows ? "where {$command} 2>nul" : "command -v {$command} 2>/dev/null";

        $output = @shell_exec($testCmd);

        return ! empty($output);
    }

    /**
     * Check if a PHP function is disabled in php.ini.
     */
    protected function isFunctionDisabled(string $functionName): bool
    {
        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        return in_array($functionName, $disabled, true);
    }
}
