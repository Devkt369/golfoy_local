<?php
declare(strict_types=1);

namespace ArtM\LuxuryCategory\Service;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

/**
 * Central service for luxury mode detection and URL transforms.
 *
 * This is the single source of truth used by all routers/plugins/observers.
 */
class LuxuryModeService
{
    public const REQUEST_ATTRIBUTE_LUXURY_MODE = 'artm_luxury_mode';
    public const LUXE_SEGMENT = 'luxe';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * Returns whether current request should run in luxury mode.
     */
    public function isLuxuryMode(): bool
    {
        $flag = $this->request->getParam(self::REQUEST_ATTRIBUTE_LUXURY_MODE);
        if ($flag !== null) {
            return (bool)$flag;
        }

        $path = (string)($this->request->getOriginalPathInfo() ?: $this->request->getPathInfo());
        $isLuxury = $this->isLuxuryPath($path);

        if ($isLuxury) {
            $this->markLuxuryMode(true);
        }

        return $isLuxury;
    }

    /**
     * Marks request state for downstream classes.
     */
    public function markLuxuryMode(bool $enabled = true): void
    {
        $this->request->setParams([
            self::REQUEST_ATTRIBUTE_LUXURY_MODE => $enabled ? 1 : 0,
        ] + $this->request->getParams());
    }

    /**
     * Returns true when path ends with /luxe.
     */
    public function isLuxuryPath(string $path): bool
    {
        $normalized = $this->normalizePath($path);
        if ($normalized === '') {
            return false;
        }

        return str_ends_with($normalized, '/' . self::LUXE_SEGMENT);
    }

    /**
     * Returns category URL with /luxe appended.
     */
    public function getLuxuryCategoryUrl(CategoryInterface $category): string
    {
        return $this->appendLuxuryToUrl((string)$category->getUrl());
    }

    /**
     * Removes trailing /luxe from URL while preserving query and fragment.
     */
    public function removeLuxuryFromUrl(?string $url = null): string
    {
        $targetUrl = trim((string)($url ?? $this->urlBuilder->getCurrentUrl()));
        if ($targetUrl === '') {
            return '';
        }

        return $this->rewriteUrlPath(
            $targetUrl,
            function (string $path): string {
                $normalized = $this->normalizePath($path);
                if ($normalized === '') {
                    return '/';
                }

                if (!str_ends_with($normalized, '/' . self::LUXE_SEGMENT)) {
                    return $normalized;
                }

                $trimmed = substr($normalized, 0, -strlen('/' . self::LUXE_SEGMENT));
                return $trimmed !== '' ? $trimmed : '/';
            }
        );
    }

    /**
     * Appends /luxe to URL path while preserving query and fragment.
     */
    public function appendLuxuryToUrl(string $url): string
    {
        $targetUrl = trim($url);
        if ($targetUrl === '') {
            return '';
        }

        return $this->rewriteUrlPath(
            $targetUrl,
            function (string $path): string {
                $normalized = $this->normalizePath($path);
                $base = $normalized === '' ? '/' : $normalized;

                if (str_ends_with($base, '/' . self::LUXE_SEGMENT)) {
                    return $base;
                }

                return rtrim($base, '/') . '/' . self::LUXE_SEGMENT;
            }
        );
    }

    /**
     * Rewrites only URL path and keeps scheme/host/query/fragment untouched.
     */
    private function rewriteUrlPath(string $url, callable $pathRewriter): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        $path = (string)($parts['path'] ?? '/');
        $parts['path'] = $pathRewriter($path);

        return $this->buildUrl($parts);
    }

    /**
     * Converts path to canonical form with leading slash and no trailing slash.
     */
    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        $normalized = '/' . ltrim($trimmed, '/');
        return rtrim($normalized, '/');
    }

    /**
     * Builds URL from parse_url parts.
     *
     * @param array<string, mixed> $parts
     */
    private function buildUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $user = (string)($parts['user'] ?? '');
        $pass = (string)($parts['pass'] ?? '');
        $auth = $user !== '' ? $user . ($pass !== '' ? ':' . $pass : '') . '@' : '';
        $host = (string)($parts['host'] ?? '');
        $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
        $path = (string)($parts['path'] ?? '/');
        $query = isset($parts['query']) && (string)$parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && (string)$parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

        return $scheme . $auth . $host . $port . $path . $query . $fragment;
    }
}
