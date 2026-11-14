<?php
/**
 * QuickCart_SalesDashboard
 */
declare(strict_types=1);

namespace QuickCart\SalesDashboard\Model\SalesTarget;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use QuickCart\SalesDashboard\Controller\Adminhtml\Target\Save;
use QuickCart\SalesDashboard\Model\ResourceModel\SalesTarget\CollectionFactory;

/**
 * Data for the target form (falls back to the posted data after a failed save).
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
        foreach ($this->collection->getItems() as $target) {
            $this->loadedData[$target->getId()] = $target->getData();
        }
        $persisted = $this->dataPersistor->get(Save::PERSISTOR_KEY);
        if (!empty($persisted)) {
            $target = $this->collection->getNewEmptyItem();
            $target->setData($persisted);
            $this->loadedData[$target->getId()] = $target->getData();
            $this->dataPersistor->clear(Save::PERSISTOR_KEY);
        }

        return $this->loadedData;
    }
}
