<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Controller\Adminhtml\Recommendation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use QuickCart\ProductRecommendation\Model\Rebuilder;

/**
 * "Rebuild now" button on the grid (POST).
 */
class Rebuild extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'QuickCart_ProductRecommendation::recommendations_rebuild';

    /**
     * @param Context $context
     * @param Rebuilder $rebuilder
     */
    public function __construct(
        Context $context,
        private readonly Rebuilder $rebuilder
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $result = $this->rebuilder->rebuild();
            $this->messageManager->addSuccessMessage(__(
                'Recommendations rebuilt: %1 "bought together" pairs from orders, %2 similar-product pairs.',
                $result['bought_together'],
                $result['similar']
            ));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('The recommendations could not be rebuilt.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
