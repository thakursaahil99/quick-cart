<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Cron;

use QuickCart\ProductRecommendation\Model\Rebuilder;

/**
 * Nightly recommendation rebuild (etc/crontab.xml).
 */
class Rebuild
{
    /**
     * @param Rebuilder $rebuilder
     */
    public function __construct(
        private readonly Rebuilder $rebuilder
    ) {
    }

    /**
     * Run the rebuild.
     *
     * @return void
     * @throws \Exception
     */
    public function execute(): void
    {
        $this->rebuilder->rebuild();
    }
}
