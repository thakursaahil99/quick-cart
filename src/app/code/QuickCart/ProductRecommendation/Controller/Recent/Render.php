<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Controller\Recent;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Element\FormKey;
use Magento\Framework\View\LayoutInterface;
use QuickCart\ProductRecommendation\Block\RecentlyViewed;
use QuickCart\ProductRecommendation\Model\Config;

/**
 * GET /qcreco/recent/render?ids=12,7,30 - renders the "Recently viewed" rail.
 *
 * Only public catalog data is returned: the block re-applies store, status and
 * visibility filters, so arbitrary IDs cannot expose disabled or hidden products.
 */
class Render implements HttpGetActionInterface
{
    private const MAX_IDS = 12;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param LayoutInterface $layout
     * @param Config $config
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly LayoutInterface $layout,
        private readonly Config $config
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Json $result */
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('X-Robots-Tag', 'noindex, nofollow', true);

        $ids = $this->parseIds((string)$this->request->getParam('ids'), self::MAX_IDS);
        if (!$ids || !$this->config->isRecentlyViewedEnabled()) {
            return $result->setData(['html' => '']);
        }

        if (!$this->layout->getBlock('formkey')) {
            $this->layout->createBlock(FormKey::class, 'formkey');
        }
        /** @var RecentlyViewed $block */
        $block = $this->layout->createBlock(RecentlyViewed::class, 'qc.recently.viewed.ajax');
        $block->setData('title', (string)__('Recently viewed'))
            ->setData('subtitle', (string)__('Pick up where you left off'))
            ->setData('products_count', $this->config->getRecentlyViewedLimit())
            ->setProductIds($ids);

        return $result->setData(['html' => $block->toHtml()]);
    }

    /**
     * Unique positive integer IDs from a comma separated list, in order.
     *
     * @param string $value
     * @param int $max
     * @return int[]
     */
    public function parseIds(string $value, int $max): array
    {
        $ids = [];
        foreach (explode(',', $value) as $part) {
            $part = trim($part);
            if ($part === '' || !ctype_digit($part) || strlen($part) > 10) {
                continue;
            }
            $id = (int)$part;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
            if (count($ids) >= $max) {
                break;
            }
        }

        return $ids;
    }
}
