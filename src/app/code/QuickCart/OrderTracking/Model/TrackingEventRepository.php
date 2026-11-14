<?php
/**
 * QuickCart_OrderTracking
 */
declare(strict_types=1);

namespace QuickCart\OrderTracking\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\OrderTracking\Api\Data\TrackingEventInterface;
use QuickCart\OrderTracking\Api\TrackingEventRepositoryInterface;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent as TrackingEventResource;
use QuickCart\OrderTracking\Model\ResourceModel\TrackingEvent\CollectionFactory;

/**
 * Tracking event repository.
 */
class TrackingEventRepository implements TrackingEventRepositoryInterface
{
    public const TITLE_MAX_LENGTH = 255;
    public const COMMENT_MAX_LENGTH = 1000;
    public const LOCATION_MAX_LENGTH = 120;

    /**
     * @param TrackingEventResource $resource
     * @param TrackingEventFactory $eventFactory
     * @param CollectionFactory $collectionFactory
     * @param Milestone $milestone
     */
    public function __construct(
        private readonly TrackingEventResource $resource,
        private readonly TrackingEventFactory $eventFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly Milestone $milestone
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(TrackingEventInterface $event): TrackingEventInterface
    {
        $event->setTitle(trim($event->getTitle()) ?: $this->milestone->getLabel($event->getMilestone()));
        $event->setComment($event->getComment() === null ? null : trim($event->getComment()));
        $event->setLocation($event->getLocation() === null ? null : trim($event->getLocation()));

        if ($event->getOrderId() < 1) {
            throw new CouldNotSaveException(__('The tracking event needs an order.'));
        }
        if (!$this->milestone->isValid($event->getMilestone())) {
            throw new CouldNotSaveException(__('Unknown milestone "%1".', $event->getMilestone()));
        }
        if (mb_strlen($event->getTitle()) > self::TITLE_MAX_LENGTH
            || mb_strlen((string)$event->getComment()) > self::COMMENT_MAX_LENGTH
            || mb_strlen((string)$event->getLocation()) > self::LOCATION_MAX_LENGTH) {
            throw new CouldNotSaveException(__(
                'Keep the title under %1, the comment under %2 and the location under %3 characters.',
                self::TITLE_MAX_LENGTH,
                self::COMMENT_MAX_LENGTH,
                self::LOCATION_MAX_LENGTH
            ));
        }
        try {
            /** @var TrackingEvent $event */
            $this->resource->save($event);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the tracking event: %1', $e->getMessage()), $e);
        }

        return $event;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $eventId): TrackingEventInterface
    {
        $event = $this->eventFactory->create();
        $this->resource->load($event, $eventId);
        if (!$event->getId()) {
            throw new NoSuchEntityException(__('The tracking event with ID "%1" does not exist.', $eventId));
        }

        return $event;
    }

    /**
     * @inheritdoc
     */
    public function getByOrderId(int $orderId, bool $customerVisibleOnly = false): array
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(TrackingEventInterface::ORDER_ID, $orderId)
            ->setOrder(TrackingEventInterface::CREATED_AT, 'ASC')
            ->setOrder(TrackingEventInterface::EVENT_ID, 'ASC');
        if ($customerVisibleOnly) {
            $collection->addFieldToFilter(TrackingEventInterface::IS_VISIBLE_ON_FRONT, 1);
        }

        return array_values($collection->getItems());
    }

    /**
     * @inheritdoc
     */
    public function delete(TrackingEventInterface $event): bool
    {
        try {
            /** @var TrackingEvent $event */
            $this->resource->delete($event);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the tracking event: %1', $e->getMessage()), $e);
        }

        return true;
    }
}
