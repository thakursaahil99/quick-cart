<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Block\Adminhtml;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use QuickCart\Core\Block\Adminhtml\Edit\GenericButton;

/**
 * "Rebuild Now" grid button: POSTs to the rebuild action after a confirmation.
 */
class RebuildButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        private readonly AuthorizationInterface $authorization
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('QuickCart_ProductRecommendation::recommendations_rebuild')) {
            return [];
        }
        $escaper = $this->context->getEscaper();
        $message = $escaper->escapeJs($escaper->escapeHtml(
            __('Recompute "frequently bought together" from order history and "similar products" from the catalog now?')
        ));

        return [
            'label' => __('Rebuild Now'),
            'class' => 'secondary',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {\"data\": {}})",
                $message,
                $this->getUrl('*/*/rebuild')
            ),
            'sort_order' => 10,
        ];
    }
}
