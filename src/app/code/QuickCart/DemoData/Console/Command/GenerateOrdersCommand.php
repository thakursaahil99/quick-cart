<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers;
use Magento\Sales\Model\ResourceModel\Report\Order as OrderReport;
use QuickCart\DemoData\Model\OrderHistory\Backdater;
use QuickCart\DemoData\Model\OrderHistory\Generator;
use QuickCart\ProductRecommendation\Model\Rebuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento quickcart:demo:orders - generate a realistic demo order history, then refresh
 * the sales report aggregations (best sellers) and the product recommendations.
 */
class GenerateOrdersCommand extends Command
{
    private const MAX_COUNT = 1000;

    /**
     * @param State $appState
     * @param Generator $generator
     * @param Backdater $backdater
     * @param Bestsellers $bestsellers
     * @param OrderReport $orderReport
     * @param Rebuilder $rebuilder
     */
    public function __construct(
        private readonly State $appState,
        private readonly Generator $generator,
        private readonly Backdater $backdater,
        private readonly Bestsellers $bestsellers,
        private readonly OrderReport $orderReport,
        private readonly Rebuilder $rebuilder
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('quickcart:demo:orders')
            ->setDescription('Generate a realistic demo order history (real orders, invoices, shipments, cancellations)')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Orders to create', '150')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Spread over the last N days', '120')
            ->addOption('customers', null, InputOption::VALUE_REQUIRED, 'Returning registered customers', '14')
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Random seed (same seed, same history)', '2026')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Generate even if demo orders already exist');
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int)$input->getOption('count');
        $days = (int)$input->getOption('days');
        if ($count < 1 || $count > self::MAX_COUNT || $days < 1 || $days > 730) {
            $output->writeln(sprintf('<error>--count must be 1-%d and --days 1-730.</error>', self::MAX_COUNT));
            return Cli::RETURN_FAILURE;
        }
        $existing = $this->backdater->countGenerated();
        if ($existing > 0 && !$input->getOption('force')) {
            $output->writeln(sprintf(
                '<comment>%d demo orders already exist. Use --force to add more.</comment>',
                $existing
            ));
            return Cli::RETURN_SUCCESS;
        }

        try {
            $this->appState->getAreaCode();
        } catch (LocalizedException $e) {
            // Invoices, shipments and cancellations run as in the admin.
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        }

        $started = microtime(true);
        $stats = $this->generator->generate(
            $count,
            $days,
            max(0, (int)$input->getOption('customers')),
            (int)$input->getOption('seed'),
            static function (string $line) use ($output): void {
                $output->writeln('  ' . $line, OutputInterface::VERBOSITY_VERBOSE);
            }
        );
        $output->writeln('<info>Orders by outcome:</info> ' . http_build_query($stats, '', ', '));

        $output->writeln('Refreshing sales report statistics (best sellers, orders)...');
        $this->bestsellers->aggregate();
        $this->orderReport->aggregate();
        $output->writeln('Rebuilding product recommendations...');
        $result = $this->rebuilder->rebuild();
        $output->writeln(sprintf(
            '<info>Done in %.1fs: %d "bought together" pairs from orders, %d similar-product pairs.</info>',
            microtime(true) - $started,
            $result['bought_together'],
            $result['similar']
        ));

        return Cli::RETURN_SUCCESS;
    }
}
