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
 * Date-wise sales report; ?export=csv downloads it.
 */
class Daily extends Action implements HttpGetActionInterface
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
            $report = $this->reports->daily($period);
            $rows = array_map(static fn (array $row) => [
                $row['day'],
                $row['orders'],
                $row['canceled'],
                (float)$row['items'],
                round($row['revenue'], 2),
                round($row['discount'], 2),
                round($row['shipping'], 2),
                round($row['aov'], 2),
            ], $report['rows']);

            return $this->fileFactory->create(
                sprintf('quickcart-sales-%s-to-%s.csv', $period['from'], $period['to']),
                $this->reports->toCsv(
                    ['Date', 'Orders', 'Cancelled', 'Items', 'Net revenue (' . $report['currency'] . ')', 'Discounts',
                        'Shipping', 'Average order value'],
                    $rows
                ),
                DirectoryList::VAR_DIR,
                'text/csv'
            );
        }

        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('QuickCart_SalesDashboard::report_daily');
        $page->getConfig()->getTitle()->prepend(__('Date-wise Sales Report'));

        return $page;
    }
}
