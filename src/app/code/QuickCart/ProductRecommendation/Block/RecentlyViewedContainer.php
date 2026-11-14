<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Block;

use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use QuickCart\ProductRecommendation\Model\Config;

/**
 * Placeholder that tracks product views in localStorage and loads the
 * "Recently viewed" rail over AJAX (cache-safe on full-page-cached pages).
 */
class RecentlyViewedContainer extends Template
{
    /**
     * @var string
     */
    protected $_template = 'QuickCart_ProductRecommendation::recently-viewed.phtml';

    /**
     * @param Context $context
     * @param Config $config
     * @param Registry $registry
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly Registry $registry,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Widget configuration.
     *
     * @return string
     */
    public function getJsConfig(): string
    {
        $product = $this->registry->registry('current_product');
        $productId = $product instanceof Product ? (int)$product->getId() : 0;

        return $this->json->serialize([
            'url' => $this->getUrl('qcreco/recent/render'),
            'limit' => $this->config->getRecentlyViewedLimit(),
            'trackId' => $this->getData('track') ? $productId : 0,
            'excludeId' => $productId,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        return $this->config->isRecentlyViewedEnabled() ? parent::_toHtml() : '';
    }
}
