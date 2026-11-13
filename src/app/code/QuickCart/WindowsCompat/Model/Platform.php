<?php
/**
 * QuickCart_WindowsCompat
 */
declare(strict_types=1);

namespace QuickCart\WindowsCompat\Model;

/**
 * Detects the host platform and normalises Windows paths to Magento's format.
 */
class Platform
{
    /**
     * @param string $directorySeparator injectable for unit tests
     */
    public function __construct(
        private readonly string $directorySeparator = DIRECTORY_SEPARATOR
    ) {
    }

    /**
     * Whether PHP is running on Windows.
     *
     * @return bool
     */
    public function isWindows(): bool
    {
        return $this->directorySeparator === '\\';
    }

    /**
     * Convert a filesystem path to forward slashes (Magento's canonical form).
     *
     * @param string $path
     * @return string
     */
    public function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Case-insensitive "path is inside directory" check (NTFS is case-insensitive).
     *
     * @param string $path
     * @param string $directory
     * @return bool
     */
    public function isPathInside(string $path, string $directory): bool
    {
        $path = $this->normalizePath($path);
        $directory = $this->normalizePath($directory);
        if ($directory === '') {
            return false;
        }

        return strncasecmp($path, $directory, strlen($directory)) === 0;
    }

    /**
     * Turn backslashes into URL separators and collapse duplicate slashes (scheme kept).
     *
     * @param string $url
     * @return string
     */
    public function normalizeUrl(string $url): string
    {
        return (string)preg_replace('#(?<!:)/{2,}#', '/', str_replace('\\', '/', $url));
    }

    /**
     * Whether the path is an absolute Windows drive path such as "E:/x" or "e:\x".
     *
     * @param string $path
     * @return bool
     */
    public function isDrivePath(string $path): bool
    {
        return (bool)preg_match('#^[A-Za-z]:[\\\\/]#', $path);
    }

    /**
     * Express an absolute path relative to a base directory on the same drive.
     *
     * Returns null when no relative form exists (different drives), so callers can
     * fall back to the original path.
     *
     * @param string $path absolute target path
     * @param string $baseDirectory absolute directory the result is relative to
     * @return string|null
     */
    public function toRelativePath(string $path, string $baseDirectory): ?string
    {
        $target = explode('/', $this->normalizePath($path));
        $base = explode('/', rtrim($this->normalizePath($baseDirectory), '/'));

        if (strcasecmp($target[0], $base[0]) !== 0) {
            return null;
        }

        $common = 0;
        $max = min(count($target), count($base));
        while ($common < $max && strcasecmp($target[$common], $base[$common]) === 0) {
            $common++;
        }

        $relative = str_repeat('../', count($base) - $common) . implode('/', array_slice($target, $common));

        return $relative === '' ? '.' : $relative;
    }
}
