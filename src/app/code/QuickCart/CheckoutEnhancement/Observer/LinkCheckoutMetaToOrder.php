<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use QuickCart\CheckoutEnhancement\Api\CheckoutMetaRepositoryInterface;

/**
 * sales_model_service_quote_submit_success: attach the quote's checkout details
 * to the newly placed order. Never interrupts order placement.
 */
class LinkCheckoutMetaToOrder implements ObserverInterface
{
    /**
     * @param CheckoutMetaRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CheckoutMetaRepositoryInterface $repository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');
        if (!$order || !$quote || !$order->getEntityId()) {
            return;
        }
        try {
            $meta = $this->repository->getByQuoteId((int)$quote->getId());
            $meta->setOrderId((int)$order->getEntityId());
            $this->repository->save($meta);
        } catch (NoSuchEntityException) {
            return;
        } catch (\Exception $e) {
            $this->logger->error('QuickCart: could not link checkout details to order: ' . $e->getMessage());
        }
    }
}
