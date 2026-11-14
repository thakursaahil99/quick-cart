<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use QuickCart\CheckoutEnhancement\Model\CheckoutFieldsValidator;
use QuickCart\CheckoutEnhancement\Model\Config;

/**
 * Adds the "Delivery details" form (instructions + GSTIN) above the shipping methods
 * and Indian phone / PIN code rules to the shipping address form, as configured in
 * Stores > Configuration > QuickCart > Checkout.
 */
class AddCheckoutFields
{
    private const SCOPE = 'qcCheckout';

    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * After plugin for process().
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        if (!isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children'])) {
            return $jsLayout;
        }
        $shipping = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children'];

        $fields = array_filter([
            'delivery_note' => $this->config->isDeliveryNoteEnabled() ? $this->deliveryNoteField() : null,
            'gstin' => $this->config->isGstinEnabled() ? $this->gstinField() : null,
        ]);
        if ($fields) {
            $shipping['before-shipping-method-form']['children']['qc-checkout-fields'] = [
                'component' => 'uiComponent',
                'config' => ['template' => 'QuickCart_CheckoutEnhancement/checkout/delivery-details'],
                'sortOrder' => 50,
                'children' => [
                    'qc-delivery-fieldset' => [
                        'component' => 'uiComponent',
                        'displayArea' => 'qc-delivery-fieldset',
                        'children' => $fields,
                    ],
                ],
            ];
        }

        if ($this->config->isIndianValidationEnabled()
            && isset($shipping['shipping-address-fieldset']['children'])) {
            $addressFields = &$shipping['shipping-address-fieldset']['children'];
            if (isset($addressFields['telephone'])) {
                $addressFields['telephone']['validation']['qc-in-phone'] = true;
            }
            if (isset($addressFields['postcode'])) {
                $addressFields['postcode']['validation']['qc-in-pincode'] = true;
            }
        }

        return $jsLayout;
    }

    /**
     * Delivery instructions textarea.
     *
     * @return array
     */
    private function deliveryNoteField(): array
    {
        return $this->field('delivery_note', 10, [
            'component' => 'Magento_Ui/js/form/element/textarea',
            'elementTmpl' => 'ui/form/element/textarea',
            'label' => __('Delivery instructions'),
            'notice' => __(
                'Optional: landmark, preferred time, gate code (max %1 characters).',
                CheckoutFieldsValidator::NOTE_MAX_LENGTH
            ),
            'validation' => ['max_text_length' => CheckoutFieldsValidator::NOTE_MAX_LENGTH],
            'rows' => 3,
        ]);
    }

    /**
     * GSTIN input.
     *
     * @return array
     */
    private function gstinField(): array
    {
        return $this->field('gstin', 20, [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'elementTmpl' => 'ui/form/element/input',
            'label' => __('GSTIN for a business invoice'),
            'notice' => __('Optional: 15 characters, for example 27ABCDE1234F1Z5.'),
            'validation' => ['qc-gstin' => true],
        ]);
    }

    /**
     * UI form element bound to checkoutProvider.qcCheckout.<name>.
     *
     * @param string $name
     * @param int $sortOrder
     * @param array $options
     * @return array
     */
    private function field(string $name, int $sortOrder, array $options): array
    {
        return [
            'component' => $options['component'],
            'config' => array_filter([
                'customScope' => self::SCOPE,
                'template' => 'ui/form/field',
                'elementTmpl' => $options['elementTmpl'],
                'rows' => $options['rows'] ?? null,
                'id' => 'qc-' . str_replace('_', '-', $name),
            ]),
            'provider' => 'checkoutProvider',
            'dataScope' => self::SCOPE . '.' . $name,
            'label' => $options['label'],
            'notice' => $options['notice'],
            'sortOrder' => $sortOrder,
            'validation' => $options['validation'],
            'visible' => true,
        ];
    }
}
