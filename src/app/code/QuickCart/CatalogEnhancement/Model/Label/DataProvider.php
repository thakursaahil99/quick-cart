<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\Label;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use QuickCart\CatalogEnhancement\Api\Data\LabelInterface;
use QuickCart\CatalogEnhancement\Controller\Adminhtml\Label\Save;
use QuickCart\CatalogEnhancement\Model\ResourceModel\Label\CollectionFactory;

/**
 * Data for the label edit form (falls back to the posted data after a failed save).
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
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
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
        /** @var LabelInterface $label */
        foreach ($this->collection->getItems() as $label) {
            $this->loadedData[$label->getLabelId()] = $this->toFormData($label);
        }

        $persisted = $this->dataPersistor->get(Save::PERSISTOR_KEY);
        if (!empty($persisted)) {
            $label = $this->collection->getNewEmptyItem();
            $label->setData($persisted);
            $this->loadedData[$label->getId()] = $label->getData();
            $this->dataPersistor->clear(Save::PERSISTOR_KEY);
        }

        return $this->loadedData;
    }

    /**
     * Label data with store IDs as strings (multiselect values).
     *
     * @param LabelInterface $label
     * @return array
     */
    private function toFormData(LabelInterface $label): array
    {
        /** @var \QuickCart\CatalogEnhancement\Model\Label $label */
        $data = $label->getData();
        $data['store_ids'] = array_map('strval', $label->getStoreIds());

        return $data;
    }
}
