<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Order number linking to the admin order view (rendered with ui/grid/cells/html).
 */
class OrderLink extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        $field = $this->getData('name');
        foreach ($dataSource['data']['items'] ?? [] as $index => $item) {
            if (empty($item['order_id'])) {
                continue;
            }
            $dataSource['data']['items'][$index][$field] = sprintf(
                '<a href="%s">#%s</a>',
                $this->escaper->escapeUrl(
                    $this->urlBuilder->getUrl('sales/order/view', ['order_id' => (int)$item['order_id']])
                ),
                $this->escaper->escapeHtml((string)($item[$field] ?? ''))
            );
        }

        return $dataSource;
    }
}
