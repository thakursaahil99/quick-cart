<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Failed guest tracking lookups per client IP (REST has no session), kept in the cache
 * for a sliding 15-minute window.
 */
class GuestLookupLimiter
{
    public const WINDOW_SECONDS = 900;
    private const CACHE_PREFIX = 'qc_api_track_fail_';
    private const CACHE_TAG = 'QUICKCART_API_RATE_LIMIT';

    /**
     * @param CacheInterface $cache
     * @param RemoteAddress $remoteAddress
     * @param Json $json
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly RemoteAddress $remoteAddress,
        private readonly Json $json
    ) {
    }

    /**
     * Whether the client used up its failed lookups.
     *
     * @param int $maxAttempts
     * @return bool
     */
    public function isLocked(int $maxAttempts): bool
    {
        return count($this->recentFailures(time())) >= $maxAttempts;
    }

    /**
     * Remember a failed lookup.
     *
     * @return void
     */
    public function addFailure(): void
    {
        $now = time();
        $failures = $this->recentFailures($now);
        $failures[] = $now;
        $this->cache->save(
            $this->json->serialize($failures),
            $this->cacheKey(),
            [self::CACHE_TAG],
            self::WINDOW_SECONDS
        );
    }

    /**
     * Timestamps of failures inside the window.
     *
     * @param int $now
     * @return int[]
     */
    private function recentFailures(int $now): array
    {
        $stored = $this->cache->load($this->cacheKey());
        $failures = $stored ? (array)$this->json->unserialize($stored) : [];

        return $this->filterWindow($failures, $now);
    }

    /**
     * Keep timestamps newer than the window.
     *
     * @param array $timestamps
     * @param int $now
     * @return int[]
     */
    public function filterWindow(array $timestamps, int $now): array
    {
        $since = $now - self::WINDOW_SECONDS;

        return array_values(array_filter(
            array_map('intval', $timestamps),
            static fn (int $time) => $time > $since && $time <= $now
        ));
    }

    /**
     * Cache key per client IP (hashed, so no address is stored in clear text).
     *
     * @return string
     */
    private function cacheKey(): string
    {
        return self::CACHE_PREFIX . hash('sha256', (string)$this->remoteAddress->getRemoteAddress());
    }
}
