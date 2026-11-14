<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use QuickCart\ProductRecommendation\Model\Rebuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento quickcart:recommendations:rebuild
 */
class RebuildCommand extends Command
{
    /**
     * @param State $appState
     * @param Rebuilder $rebuilder
     */
    public function __construct(
        private readonly State $appState,
        private readonly Rebuilder $rebuilder
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('quickcart:recommendations:rebuild')
            ->setDescription('Recompute "frequently bought together" (orders) and "similar products" (catalog)');
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->getAreaCode();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // No area yet (plain CLI run): price and URL models need one.
            $this->appState->setAreaCode(Area::AREA_CRONTAB);
        }
        $started = microtime(true);
        $result = $this->rebuilder->rebuild();
        $output->writeln(sprintf(
            '<info>Frequently bought together: %d pairs, similar products: %d pairs (%.2fs)</info>',
            $result['bought_together'],
            $result['similar'],
            microtime(true) - $started
        ));

        return Cli::RETURN_SUCCESS;
    }
}
