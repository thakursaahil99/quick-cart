<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use QuickCart\SalesDashboard\Model\Report\Reports;
use QuickCart\SalesDashboard\ViewModel\Analytics;

/**
 * Best-selling products report; ?export=csv downloads it.
 */
class Products extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_SalesDashboard::reports';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param FileFactory $fileFactory
     * @param Reports $reports
     * @param Analytics $analytics
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly FileFactory $fileFactory,
        private readonly Reports $reports,
        private readonly Analytics $analytics
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('export') === 'csv') {
            $period = $this->analytics->getPeriod();
            $report = $this->reports->products($period);
            $rows = array_map(static fn (array $row) => [
                $row['rank'],
                $row['sku'],
                $row['name'],
                (float)$row['qty'],
                $row['orders'],
                round($row['revenue'], 2),
                $row['share'],
            ], $report['rows']);

            return $this->fileFactory->create(
                sprintf('quickcart-best-sellers-%s-to-%s.csv', $period['from'], $period['to']),
                $this->reports->toCsv(
                    ['Rank', 'SKU', 'Product', 'Units', 'Orders', 'Net revenue (' . $report['currency'] . ')', 'Share %'],
                    $rows
                ),
                DirectoryList::VAR_DIR,
                'text/csv'
            );
        }

        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('QuickCart_SalesDashboard::report_products');
        $page->getConfig()->getTitle()->prepend(__('Best-selling Products'));

        return $page;
    }
}
