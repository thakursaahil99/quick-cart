<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Controller\Adminhtml\Target;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * New target: same form as edit.
 */
class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_SalesDashboard::targets_save';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('edit');
    }
}
