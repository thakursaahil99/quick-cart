<?php
/**
 * QuickCart_Core
 */
declare(strict_types=1);

namespace QuickCart\Core\Block\Adminhtml\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Base for the QuickCart UI-form buttons.
 *
 * Every QuickCart edit page uses the request parameter "id" and the controller
 * actions "delete" / "index" next to "edit", so the buttons build their URLs
 * relative to the current route and controller.
 */
class GenericButton
{
    /**
     * @param Context $context
     */
    public function __construct(
        protected readonly Context $context
    ) {
    }

    /**
     * ID of the entity being edited, or 0 for a new one.
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return max(0, (int)$this->context->getRequest()->getParam('id'));
    }

    /**
     * Admin URL relative to the current route.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
