<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Model\OrderHistory;

use DateTimeImmutable;
use DateTimeZone;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\InvoiceOrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use QuickCart\OrderTracking\Model\Milestone;
use QuickCart\OrderTracking\Model\TrackingUpdater;

/**
 * Places a realistic order history through Magento's own services (quote → order →
 * invoice / shipment / cancellation), then backdates it.
 *
 * Baskets follow the catalog's cross-sell links (so "frequently bought together" has
 * real co-purchases), recent orders are still being fulfilled, older ones delivered,
 * some use the QUICK10 coupon, and returning customers order more than once.
 * A seed makes runs reproducible.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Generator
{
    private const STORE_TIMEZONE = 'Asia/Kolkata';
    private const CARRIERS = [
        ['BlueDart Express', 'BD', 'IN'],
        ['Delhivery', 'DL', ''],
        ['Ekart Logistics', 'FMPC', ''],
        ['DTDC', 'D', 'IN'],
    ];

    /**
     * @var callable(int, int): int
     */
    private $random;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Visibility $visibility
     * @param QuoteFactory $quoteFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $cartManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerFactory
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceOrderInterface $invoiceOrder
     * @param ShipOrderInterface $shipOrder
     * @param ShipmentTrackCreationInterfaceFactory $trackFactory
     * @param OrderManagementInterface $orderManagement
     * @param TrackingUpdater $trackingUpdater
     * @param Scenario $scenario
     * @param People $people
     * @param Backdater $backdater
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Visibility $visibility,
        private readonly QuoteFactory $quoteFactory,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartManagementInterface $cartManagement,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerInterfaceFactory $customerFactory,
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly RegionInterfaceFactory $regionFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InvoiceOrderInterface $invoiceOrder,
        private readonly ShipOrderInterface $shipOrder,
        private readonly ShipmentTrackCreationInterfaceFactory $trackFactory,
        private readonly OrderManagementInterface $orderManagement,
        private readonly TrackingUpdater $trackingUpdater,
        private readonly Scenario $scenario,
        private readonly People $people,
        private readonly Backdater $backdater,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate $count orders spread over the last $days days.
     *
     * @param int $count
     * @param int $days
     * @param int $customers Registered (returning) customers to create
     * @param int $seed
     * @param callable(string): void $progress
     * @return array<string, int> Orders per scenario plus "failed"
     */
    public function generate(int $count, int $days, int $customers, int $seed, callable $progress): array
    {
        $randomizer = new \Random\Randomizer(new \Random\Engine\Mt19937($seed));
        $this->random = static fn (int $min, int $max): int => $randomizer->getInt($min, $max);
        $store = $this->storeManager->getDefaultStoreView();
        $this->storeManager->setCurrentStore($store->getId());

        $catalog = $this->loadCatalog((int)$store->getId());
        $registered = $this->createCustomers($customers, (int)$store->getWebsiteId());
        $progress(sprintf('%d products, %d registered customers', count($catalog), count($registered)));

        // Oldest first, so order numbers grow with time; slightly more orders recently.
        $ages = [];
        for ($i = 0; $i < $count; $i++) {
            $ages[] = round($days * (($this->roll() ** 1.35)), 3);
        }
        rsort($ages);

        $stats = ['failed' => 0];
        $firstOrderAt = [];
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach ($ages as $index => $age) {
            $buyer = $registered && $this->roll() < 0.4
                ? $registered[($this->random)(0, count($registered) - 1)]
                : ['customer' => null] + $this->people->create($this->random);
            $placedAt = $this->placedAt($now, $age);
            if ($placedAt > $now) {
                $placedAt = $now->modify('-5 minutes');
            }
            $scenario = $this->scenario->pick($age, $this->roll());

            try {
                $order = $this->placeOrder($buyer, $this->pickBasket($catalog), (int)$store->getId());
                $this->applyScenario($order, $scenario, (string)$buyer['address']['city']);
                $this->backdater->backdate(
                    (int)$order->getEntityId(),
                    $placedAt,
                    Scenario::MILESTONE_HOURS[$scenario === Scenario::PENDING ? 'placed' : $scenario] ?? 0
                );
                $stats[$scenario] = ($stats[$scenario] ?? 0) + 1;
                if ($buyer['customer'] instanceof CustomerInterface) {
                    $customerId = (int)$buyer['customer']->getId();
                    $firstOrderAt[$customerId] = min($firstOrderAt[$customerId] ?? $placedAt, $placedAt);
                }
                $progress(sprintf(
                    '#%s %s %s ₹%s (%d/%d)',
                    $order->getIncrementId(),
                    $placedAt->format('Y-m-d'),
                    $scenario,
                    number_format((float)$order->getGrandTotal()),
                    $index + 1,
                    $count
                ));
            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->logger->warning('QuickCart demo order failed: ' . $e->getMessage());
                $progress(sprintf(
                    'skipped: %s (%s:%d)',
                    $e->getMessage(),
                    preg_replace('#^.*[\\\\/]#', '', $e->getFile()),
                    $e->getLine()
                ));
            }
        }

        foreach ($firstOrderAt as $customerId => $first) {
            $this->backdater->backdateCustomer(
                $customerId,
                $first->modify(sprintf('-%d days -%d hours', ($this->random)(2, 40), ($this->random)(0, 20)))
            );
        }

        return $stats;
    }

    /**
     * Visible, enabled products with a popularity weight and their cross-sell IDs.
     *
     * @param int $storeId
     * @return array<int, array{id: int, weight: int, crosssells: int[]}>
     */
    private function loadCatalog(int $storeId): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect(['price', 'qc_is_trending', 'special_price'])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('type_id', ['in' => ['simple', 'configurable']])
            ->setVisibility($this->visibility->getVisibleInCatalogIds());

        $catalog = [];
        foreach ($collection as $product) {
            $price = (float)$product->getData('price');
            // Affordable, trending and discounted products sell more often.
            $weight = ($price < 5000 ? 5 : ($price < 30000 ? 3 : 1))
                + ((int)$product->getData('qc_is_trending') ? 3 : 0)
                + ($product->getData('special_price') ? 2 : 0);
            $catalog[(int)$product->getId()] = [
                'id' => (int)$product->getId(),
                'weight' => $weight,
                'crosssells' => array_map('intval', $product->getCrossSellProductIds()),
            ];
        }
        if (!$catalog) {
            throw new LocalizedException(__('No visible products to order. Install the demo catalog first.'));
        }

        return $catalog;
    }

    /**
     * A main product plus, often, one or two of its cross-sells.
     *
     * @param array<int, array{id: int, weight: int, crosssells: int[]}> $catalog
     * @return array<int, int> Product ID => qty
     */
    private function pickBasket(array $catalog): array
    {
        $total = array_sum(array_column($catalog, 'weight'));
        $pick = ($this->random)(1, $total);
        $main = null;
        foreach ($catalog as $entry) {
            $pick -= $entry['weight'];
            if ($pick <= 0) {
                $main = $entry;
                break;
            }
        }
        $main ??= reset($catalog);

        $basket = [$main['id'] => $this->roll() < 0.1 ? 2 : 1];
        $companions = array_values(array_filter($main['crosssells'], static fn (int $id) => isset($catalog[$id])));
        if ($companions && $this->roll() < 0.5) {
            $basket[$companions[($this->random)(0, count($companions) - 1)]] = 1;
            if (count($companions) > 1 && $this->roll() < 0.3) {
                $basket[$companions[($this->random)(0, count($companions) - 1)]] = 1;
            }
        }

        return $basket;
    }

    /**
     * Quote → order through CartManagementInterface::submit().
     *
     * @param array $buyer
     * @param array<int, int> $basket
     * @param int $storeId
     * @return Order
     * @throws LocalizedException
     */
    private function placeOrder(array $buyer, array $basket, int $storeId): Order
    {
        $quote = $this->quoteFactory->create();
        $quote->setStoreId($storeId);
        $quote->setCurrency();
        if ($buyer['customer'] instanceof CustomerInterface) {
            $quote->assignCustomer($buyer['customer']);
        } else {
            $quote->setCustomerIsGuest(true)
                ->setCustomerEmail($buyer['email'])
                ->setCustomerFirstname($buyer['firstname'])
                ->setCustomerLastname($buyer['lastname'])
                ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }

        foreach ($basket as $productId => $qty) {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId, false, $storeId, true);
            $result = $quote->addProduct($product, $this->buyRequest($product, $qty));
            if (is_string($result)) {
                throw new LocalizedException(__($result));
            }
        }

        $address = $buyer['address'] + ['email' => $buyer['email']];
        $quote->getBillingAddress()->addData($address);
        $shipping = $quote->getShippingAddress()->addData($address);
        if ($this->roll() < 0.15) {
            $quote->setCouponCode('QUICK10');
        }
        $quote->collectTotals();
        $shipping->setCollectShippingRates(true)->collectShippingRates();
        $method = 'flatrate_flatrate';
        foreach ($shipping->getAllShippingRates() as $rate) {
            if ($rate->getCode() === 'freeshipping_freeshipping') {
                $method = 'freeshipping_freeshipping';
            }
        }
        $shipping->setShippingMethod($method);
        $paymentMethod = $this->roll() < 0.75 ? 'cashondelivery' : 'checkmo';
        $quote->setPaymentMethod($paymentMethod);
        $quote->setInventoryProcessed(false);
        // The payment needs a persisted quote before it can import data.
        $this->cartRepository->save($quote);
        $quote->getPayment()->importData(['method' => $paymentMethod]);
        $quote->collectTotals();
        $this->cartRepository->save($quote);

        /** @var Order $order */
        $order = $this->cartManagement->submit($quote);

        return $order;
    }

    /**
     * Buy request; configurable products get a random in-stock variant.
     *
     * @param Product $product
     * @param int $qty
     * @return DataObject
     */
    private function buyRequest(Product $product, int $qty): DataObject
    {
        $request = ['qty' => $qty];
        if ($product->getTypeId() === 'configurable') {
            $type = $product->getTypeInstance();
            $children = array_values(array_filter(
                $type->getUsedProducts($product),
                static fn (Product $child) => $child->isSalable()
            ));
            if ($children) {
                $child = $children[($this->random)(0, count($children) - 1)];
                foreach ($type->getConfigurableAttributesAsArray($product) as $attributeId => $attribute) {
                    $request['super_attribute'][$attributeId] = $child->getData($attribute['attribute_code']);
                }
            }
        }

        return new DataObject($request);
    }

    /**
     * Move the order to its scenario through the normal fulfilment services.
     *
     * @param Order $order
     * @param string $scenario
     * @param string $city
     * @return void
     * @throws LocalizedException
     */
    private function applyScenario(Order $order, string $scenario, string $city): void
    {
        $orderId = (int)$order->getEntityId();
        switch ($scenario) {
            case Scenario::CONFIRMED:
            case Scenario::PACKED:
                $this->trackingUpdater->addUpdate(
                    $order,
                    $scenario === Scenario::PACKED ? Milestone::PACKED : Milestone::CONFIRMED,
                    '',
                    null,
                    $scenario === Scenario::PACKED ? $city . ' fulfilment centre' : null,
                    true,
                    true,
                    'demo-data'
                );
                break;
            case Scenario::SHIPPED:
                $this->ship($orderId);
                $shipped = $this->orderRepository->get($orderId);
                $shipped->setStatus(Milestone::STATUS_SHIPPED);
                $this->orderRepository->save($shipped);
                break;
            case Scenario::OUT_FOR_DELIVERY:
                $this->ship($orderId);
                /** @var Order $shipped */
                $shipped = $this->orderRepository->get($orderId);
                $this->trackingUpdater->addUpdate(
                    $shipped,
                    Milestone::OUT_FOR_DELIVERY,
                    '',
                    (string)__('Our delivery partner will reach you today.'),
                    $city,
                    true,
                    true,
                    'demo-data'
                );
                break;
            case Scenario::DELIVERED:
                $this->ship($orderId);
                $this->invoiceOrder->execute($orderId, false);
                break;
            case Scenario::CANCELED:
                $this->orderManagement->cancel($orderId);
                break;
            default:
                break;
        }
    }

    /**
     * Ship all items with a carrier tracking number.
     *
     * @param int $orderId
     * @return void
     */
    private function ship(int $orderId): void
    {
        [$carrier, $prefix, $suffix] = self::CARRIERS[($this->random)(0, count(self::CARRIERS) - 1)];
        $track = $this->trackFactory->create();
        $track->setCarrierCode('custom')
            ->setTitle($carrier)
            ->setTrackNumber($prefix . ($this->random)(100000000, 999999999) . $suffix);
        $this->shipOrder->execute($orderId, [], false, false, null, [$track]);
    }

    /**
     * Returning customers with a default address (no password: they can use "Forgot password").
     *
     * @param int $count
     * @param int $websiteId
     * @return array<int, array{customer: CustomerInterface, email: string, firstname: string, lastname: string, address: array}>
     */
    private function createCustomers(int $count, int $websiteId): array
    {
        $customers = [];
        for ($i = 0; $i < $count; $i++) {
            $person = $this->people->create($this->random);
            try {
                $customer = $this->customerRepository->get($person['email'], $websiteId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $address = $this->addressFactory->create();
                $address->setFirstname($person['firstname'])
                    ->setLastname($person['lastname'])
                    ->setStreet($person['address']['street'])
                    ->setCity($person['address']['city'])
                    ->setPostcode($person['address']['postcode'])
                    ->setCountryId('IN')
                    ->setTelephone($person['address']['telephone'])
                    ->setRegionId($person['address']['region_id'])
                    ->setRegion($this->regionFactory->create()
                        ->setRegionId($person['address']['region_id'])
                        ->setRegion($person['address']['region']))
                    ->setIsDefaultBilling(true)
                    ->setIsDefaultShipping(true);
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($websiteId)
                    ->setEmail($person['email'])
                    ->setFirstname($person['firstname'])
                    ->setLastname($person['lastname'])
                    ->setGroupId(1)
                    ->setAddresses([$address]);
                $customer = $this->customerRepository->save($customer);
            }
            $customers[] = ['customer' => $customer] + $person;
        }

        return $customers;
    }

    /**
     * Order time: $age days ago, between 09:00 and 23:00 store time, in UTC.
     *
     * @param DateTimeImmutable $now
     * @param float $age
     * @return DateTimeImmutable
     */
    private function placedAt(DateTimeImmutable $now, float $age): DateTimeImmutable
    {
        $local = $now->setTimezone(new DateTimeZone(self::STORE_TIMEZONE))
            ->modify(sprintf('-%d days', (int)floor($age)))
            ->setTime(($this->random)(9, 22), ($this->random)(0, 59), ($this->random)(0, 59));

        return $local->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Random float in [0, 1).
     *
     * @return float
     */
    private function roll(): float
    {
        return ($this->random)(0, 999999) / 1000000;
    }
}
