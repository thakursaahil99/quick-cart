<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use QuickCart\CustomApi\Model\GuestLookupLimiter;

#[CoversClass(GuestLookupLimiter::class)]
class GuestLookupLimiterTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private array $storage = [];

    public function testLocksAfterTheAllowedFailuresPerIp(): void
    {
        $limiter = $this->limiter('203.0.113.7');
        for ($i = 0; $i < 3; $i++) {
            $this->assertFalse($limiter->isLocked(3));
            $limiter->addFailure();
        }

        $this->assertTrue($limiter->isLocked(3));
        // Another client is not affected; the address is stored hashed.
        $this->assertFalse($this->limiter('198.51.100.20')->isLocked(3));
        $this->assertStringNotContainsString('203.0.113.7', implode(',', array_keys($this->storage)));
    }

    public function testOldFailuresLeaveTheWindow(): void
    {
        $now = 1_800_000_000;
        $window = $this->limiter('203.0.113.7')->filterWindow(
            [$now - GuestLookupLimiter::WINDOW_SECONDS - 1, $now - 60, $now, $now + 30],
            $now
        );

        $this->assertSame([$now - 60, $now], $window);
    }

    private function limiter(string $ip): GuestLookupLimiter
    {
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('load')->willReturnCallback(fn (string $key) => $this->storage[$key] ?? false);
        $cache->method('save')->willReturnCallback(function (string $data, string $key) {
            $this->storage[$key] = $data;

            return true;
        });
        $remote = $this->createStub(RemoteAddress::class);
        $remote->method('getRemoteAddress')->willReturn($ip);

        return new GuestLookupLimiter($cache, $remote, new Json());
    }
}
