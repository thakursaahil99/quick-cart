<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Setup\Patch\Data;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\ConditionInterface;
use Magento\SalesRule\Api\Data\ConditionInterfaceFactory;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Api\Data\CouponInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleLabelInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Coupon codes advertised on the cart page (CMS block qc-cart-offers):
 *  - QUICK10     10% off any order
 *  - WELCOME500  ₹500 off orders with a subtotal of ₹4,999 or more
 */
class InstallCartRules implements DataPatchInterface
{
    /**
     * Coupon code => rule definition.
     */
    private const RULES = [
        'QUICK10' => [
            'name' => 'QuickCart - 10% off (QUICK10)',
            'label' => '10% off (QUICK10)',
            'action' => RuleInterface::DISCOUNT_ACTION_BY_PERCENT,
            'amount' => 10,
            'min_subtotal' => null,
        ],
        'WELCOME500' => [
            'name' => 'QuickCart - Rs 500 off above Rs 4,999 (WELCOME500)',
            'label' => '₹500 off (WELCOME500)',
            'action' => RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT_FOR_CART,
            'amount' => 500,
            'min_subtotal' => 4999,
        ],
    ];

    /**
     * @param RuleRepositoryInterface $ruleRepository
     * @param RuleInterfaceFactory $ruleFactory
     * @param RuleLabelInterfaceFactory $labelFactory
     * @param ConditionInterfaceFactory $conditionFactory
     * @param CouponRepositoryInterface $couponRepository
     * @param CouponInterfaceFactory $couponFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly RuleInterfaceFactory $ruleFactory,
        private readonly RuleLabelInterfaceFactory $labelFactory,
        private readonly ConditionInterfaceFactory $conditionFactory,
        private readonly CouponRepositoryInterface $couponRepository,
        private readonly CouponInterfaceFactory $couponFactory,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function (): void {
            $this->install();
        });

        return $this;
    }

    /**
     * Create the rules and their coupon codes.
     *
     * @return void
     */
    private function install(): void
    {
        $websiteId = (int)$this->storeManager->getDefaultStoreView()->getWebsiteId();
        $groupIds = [];
        foreach ($this->groupRepository->getList($this->searchCriteriaBuilder->create())->getItems() as $group) {
            $groupIds[] = (int)$group->getId();
        }

        foreach (self::RULES as $code => $definition) {
            $existing = $this->couponRepository->getList(
                $this->searchCriteriaBuilder->addFilter('code', $code)->create()
            );
            if ($existing->getTotalCount() > 0) {
                continue;
            }

            $label = $this->labelFactory->create();
            $label->setStoreId(Store::DEFAULT_STORE_ID);
            $label->setStoreLabel($definition['label']);

            $rule = $this->ruleFactory->create();
            $rule->setName($definition['name'])
                ->setIsActive(true)
                ->setWebsiteIds([$websiteId])
                ->setCustomerGroupIds($groupIds)
                ->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON)
                ->setUseAutoGeneration(false)
                ->setSimpleAction($definition['action'])
                ->setDiscountAmount($definition['amount'])
                ->setDiscountQty(0)
                ->setUsesPerCustomer(0)
                ->setStopRulesProcessing(false)
                ->setApplyToShipping(false)
                ->setSortOrder(10)
                ->setStoreLabels([$label]);
            if ($definition['min_subtotal'] !== null) {
                $rule->setCondition($this->subtotalAtLeast((float)$definition['min_subtotal']));
            }
            $savedRule = $this->ruleRepository->save($rule);

            $coupon = $this->couponFactory->create();
            $coupon->setRuleId($savedRule->getRuleId())
                ->setCode($code)
                ->setIsPrimary(true)
                ->setType(CouponInterface::TYPE_MANUAL);
            $this->couponRepository->save($coupon);
        }
    }

    /**
     * "Subtotal is at least X" cart condition.
     *
     * @param float $amount
     * @return ConditionInterface
     */
    private function subtotalAtLeast(float $amount): ConditionInterface
    {
        $subtotal = $this->conditionFactory->create();
        $subtotal->setConditionType(Address::class)
            ->setAttributeName('base_subtotal')
            ->setOperator('>=')
            ->setValue((string)$amount);

        $combine = $this->conditionFactory->create();
        $combine->setConditionType(Combine::class)
            ->setAggregatorType(ConditionInterface::AGGREGATOR_TYPE_ALL)
            ->setValue('1')
            ->setConditions([$subtotal]);

        return $combine;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
