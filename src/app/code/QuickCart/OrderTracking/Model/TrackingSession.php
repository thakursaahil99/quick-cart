<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Customer\Model\Session as CustomerSession;

/**
 * Per-visitor tracking state kept in the (guest or customer) session:
 * orders unlocked through the lookup form and failed lookup attempts.
 */
class TrackingSession
{
    private const KEY_ORDERS = 'qc_tracked_orders';
    private const KEY_FAILURES = 'qc_tracking_failures';
    private const MAX_ORDERS = 10;
    private const WINDOW_SECONDS = 900;

    /**
     * @param CustomerSession $session
     */
    public function __construct(
        private readonly CustomerSession $session
    ) {
    }

    /**
     * Allow this visitor to view an order's tracking page.
     *
     * @param int $orderId
     * @return void
     */
    public function allow(int $orderId): void
    {
        $orders = array_values(array_diff((array)$this->session->getData(self::KEY_ORDERS), [$orderId]));
        array_unshift($orders, $orderId);
        $this->session->setData(self::KEY_ORDERS, array_slice($orders, 0, self::MAX_ORDERS));
    }

    /**
     * Whether the visitor unlocked the order, or is the logged-in customer who placed it.
     *
     * @param int $orderId
     * @param int|null $orderCustomerId
     * @return bool
     */
    public function canView(int $orderId, ?int $orderCustomerId): bool
    {
        if ($orderCustomerId && $this->session->isLoggedIn()
            && (int)$this->session->getCustomerId() === $orderCustomerId) {
            return true;
        }

        return in_array($orderId, array_map('intval', (array)$this->session->getData(self::KEY_ORDERS)), true);
    }

    /**
     * Whether the visitor used up the failed lookups allowed in the time window.
     *
     * @param int $maxAttempts
     * @return bool
     */
    public function isLocked(int $maxAttempts): bool
    {
        return count($this->recentFailures()) >= $maxAttempts;
    }

    /**
     * Remember a failed lookup.
     *
     * @return void
     */
    public function addFailure(): void
    {
        $failures = $this->recentFailures();
        $failures[] = time();
        $this->session->setData(self::KEY_FAILURES, $failures);
    }

    /**
     * Failed lookups inside the window.
     *
     * @return int[]
     */
    private function recentFailures(): array
    {
        $since = time() - self::WINDOW_SECONDS;

        return array_values(array_filter(
            array_map('intval', (array)$this->session->getData(self::KEY_FAILURES)),
            static fn (int $time) => $time > $since
        ));
    }
}
