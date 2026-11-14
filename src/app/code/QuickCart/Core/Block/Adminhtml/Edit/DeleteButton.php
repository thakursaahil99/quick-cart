<?php
/**
 * QuickCart_Core
 */
declare(strict_types=1);

namespace QuickCart\Core\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * "Delete" (POST, with confirmation), shown only when editing an existing entity.
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        $id = $this->getEntityId();
        if ($id === 0) {
            return [];
        }
        $escaper = $this->context->getEscaper();
        $message = $escaper->escapeJs($escaper->escapeHtml(__('Are you sure you want to delete this record?')));

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {\"data\": {}})",
                $message,
                $this->getUrl('*/*/delete', ['id' => $id])
            ),
            'sort_order' => 20,
        ];
    }
}
