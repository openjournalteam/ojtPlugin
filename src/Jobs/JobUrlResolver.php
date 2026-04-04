<?php

namespace Openjournalteam\OjtPlugin\Jobs;

use Illuminate\Database\Capsule\Manager as Capsule;

class JobUrlResolver
{
    /** @var array<int,string> */
    protected static $contextPathCache = [];

    /**
     * Build full cache URL for page-cache jobs.
     *
     * @param int|null $contextId
     * @param string $page
     * @param string $op
     * @param array $args
     * @param array $queryVars
     * @param string|null $explicitBaseUrl
     * @return string
     */
    public function buildCachePageUrl($contextId, $page, $op, array $args = [], array $queryVars = [], $explicitBaseUrl = null)
    {
        $resolvedContextId = (int) ($contextId ?: 0);
        $contextPath = $this->getContextPath($resolvedContextId);
        $baseUrl = $this->resolveBaseUrl($contextPath, $explicitBaseUrl);

        return $this->buildAbsolutePageUrl($baseUrl, $contextPath, $page, $op, $args, $queryVars);
    }

    /**
     * Resolve and cache journal context path by context id.
     *
     * @param int $contextId
     * @return string
     */
    public function getContextPath($contextId)
    {
        $contextId = (int) $contextId;
        if (isset(self::$contextPathCache[$contextId])) {
            return self::$contextPathCache[$contextId];
        }

        $contextPath = Capsule::table('journals')
            ->where('journal_id', $contextId)
            ->value('path');

        if (!$contextPath) {
            $contextPath = 'index';
        }

        self::$contextPathCache[$contextId] = (string) $contextPath;
        return self::$contextPathCache[$contextId];
    }

    /**
     * Resolve base URL candidate list for jobs/CLI runtime.
     *
     * @param string $contextPath
     * @param string|null $explicitBaseUrl
     * @return string
     */
    protected function resolveBaseUrl($contextPath, $explicitBaseUrl = null)
    {
        $candidates = [];

        if ($explicitBaseUrl !== null && trim((string) $explicitBaseUrl) !== '') {
            $candidates[] = trim((string) $explicitBaseUrl);
        }

        $serverBase = $this->getServerBaseUrlCandidate();
        if ($serverBase !== '') {
            $candidates[] = $serverBase;
        }

        $envBase = getenv('OJT_BLAZING_CACHE_BASE_URL');
        if ($envBase !== false && trim((string) $envBase) !== '') {
            $candidates[] = trim((string) $envBase);
        }

        $appUrl = getenv('APP_URL');
        if ($appUrl !== false && trim((string) $appUrl) !== '') {
            $candidates[] = trim((string) $appUrl);
        }

        $contextBase = \Config::getVar('general', 'base_url[' . $contextPath . ']');
        if ($contextBase && trim((string) $contextBase) !== '') {
            $candidates[] = trim((string) $contextBase);
        }

        $indexBase = \Config::getVar('general', 'base_url[index]');
        if ($indexBase && trim((string) $indexBase) !== '') {
            $candidates[] = trim((string) $indexBase);
        }

        $generalBase = \Config::getVar('general', 'base_url');
        if ($generalBase && trim((string) $generalBase) !== '') {
            $candidates[] = trim((string) $generalBase);
        }

        $allowedHosts = $this->getAllowedHosts($candidates);

        foreach ($candidates as $candidate) {
            $candidate = $this->sanitizeBaseUrlCandidate($candidate, $allowedHosts);
            if ($candidate === '') {
                continue;
            }

            // Ignore known OJS sample fallback URL.
            if (stripos($candidate, 'pkp.sfu.ca/ojs') !== false) {
                continue;
            }

            return rtrim($candidate, '/');
        }

        return $this->inferLocalBaseUrlFromProject();
    }

    /**
     * Build base URL from server globals (web runtime).
     *
     * @return string
     */
    protected function getServerBaseUrlCandidate()
    {
        if (PHP_SAPI === 'cli') {
            return '';
        }

        $host = '';
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = (string) $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $host = (string) $_SERVER['SERVER_NAME'];
        }

        if ($host === '') {
            return '';
        }

        $scheme = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);
        } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
            $scheme = strtolower((string) $_SERVER['REQUEST_SCHEME']);
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $basePath = '';
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $basePath = rtrim(dirname((string) $_SERVER['SCRIPT_NAME']), '/');
        }

        $url = $scheme . '://' . $host;
        if ($basePath !== '' && $basePath !== '.') {
            $url .= $basePath;
        }

        return $url;
    }

    /**
     * Build absolute page URL from base/context/page segments.
     *
     * @param string $baseUrl
     * @param string $contextPath
     * @param string $page
     * @param string $op
     * @param array $args
     * @param array $queryVars
     * @return string
     */
    protected function buildAbsolutePageUrl($baseUrl, $contextPath, $page, $op, array $args = [], array $queryVars = [])
    {
        $baseUrl = rtrim((string) $baseUrl, '/');
        if (substr($baseUrl, -10) === '/index.php') {
            $baseUrl = substr($baseUrl, 0, -10);
        }

        $segments = [
            'index.php',
            rawurlencode((string) $contextPath),
            rawurlencode((string) $page),
        ];

        if ($op !== null && $op !== '') {
            $segments[] = rawurlencode((string) $op);
        }

        foreach ($args as $arg) {
            if ($arg === null || $arg === '') {
                continue;
            }
            $segments[] = rawurlencode((string) $arg);
        }

        $url = $baseUrl . '/' . implode('/', $segments);
        if (!empty($queryVars)) {
            $query = http_build_query($queryVars, '', '&', PHP_QUERY_RFC3986);
            if ($query !== '') {
                $url .= '?' . $query;
            }
        }

        return $url;
    }

    /**
     * Only allow absolute HTTP(S) URLs.
     *
     * @param string $candidate
     * @param array $allowedHosts
     * @return string
     */
    protected function sanitizeBaseUrlCandidate($candidate, array $allowedHosts = [])
    {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            return '';
        }

        $parts = parse_url($candidate);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower((string) $parts['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return '';
        }

        $host = strtolower((string) $parts['host']);
        if (!empty($allowedHosts) && !in_array($host, $allowedHosts, true)) {
            return '';
        }

        return $candidate;
    }

    /**
     * Build allowed host list from trusted candidate sources.
     *
     * @param array $candidates
     * @return array
     */
    protected function getAllowedHosts(array $candidates = [])
    {
        $hosts = [];

        foreach ($candidates as $candidate) {
            $parts = parse_url((string) $candidate);
            if ($parts === false || empty($parts['host'])) {
                continue;
            }

            $host = strtolower((string) $parts['host']);
            if ($host !== '' && !in_array($host, $hosts, true)) {
                $hosts[] = $host;
            }
        }

        // Ensure fallback local host is always allowed.
        $fallbackHost = parse_url($this->inferLocalBaseUrlFromProject(), PHP_URL_HOST);
        if ($fallbackHost) {
            $fallbackHost = strtolower((string) $fallbackHost);
            if (!in_array($fallbackHost, $hosts, true)) {
                $hosts[] = $fallbackHost;
            }
        }

        return $hosts;
    }

    /**
     * Local fallback for CLI/dev runtime.
     *
     * @return string
     */
    protected function inferLocalBaseUrlFromProject()
    {
        $projectDir = basename((string) \Core::getBaseDir());
        if ($projectDir === '' || $projectDir === '.' || $projectDir === DIRECTORY_SEPARATOR) {
            return 'http://localhost';
        }

        return 'http://localhost/' . rawurlencode($projectDir);
    }
}
