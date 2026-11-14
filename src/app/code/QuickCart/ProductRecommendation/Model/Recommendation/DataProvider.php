<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\Recommendation;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;
use QuickCart\ProductRecommendation\Controller\Adminhtml\Recommendation\Save;
use QuickCart\ProductRecommendation\Model\ResourceModel\Recommendation\CollectionFactory;

/**
 * Data for the recommendation form; product IDs are shown as SKUs.
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var array<int|string, array>|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param ProductResource $productResource
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly ProductResource $productResource,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }
        $this->loadedData = [];
        /** @var RecommendationInterface[] $items */
        $items = $this->collection->getItems();
        $productIds = [];
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
            $productIds[] = $item->getRecommendedProductId();
        }
        $skus = [];
        foreach ($productIds ? $this->productResource->getProductsSku(array_unique($productIds)) : [] as $row) {
            $skus[(int)$row['entity_id']] = (string)$row['sku'];
        }
        foreach ($items as $item) {
            /** @var \QuickCart\ProductRecommendation\Model\Recommendation $item */
            $this->loadedData[$item->getId()] = $item->getData() + [
                'product_sku' => $skus[$item->getProductId()] ?? '',
                'recommended_sku' => $skus[$item->getRecommendedProductId()] ?? '',
            ];
        }

        $persisted = $this->dataPersistor->get(Save::PERSISTOR_KEY);
        if (!empty($persisted)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($persisted);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear(Save::PERSISTOR_KEY);
        }

        return $this->loadedData;
    }
}
