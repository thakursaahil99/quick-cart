<?php
/**
 * QuickCart_CheckoutEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CheckoutEnhancement\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\CheckoutEnhancement\Api\CheckoutMetaRepositoryInterface;
use QuickCart\CheckoutEnhancement\Model\CheckoutFieldsValidator;
use QuickCart\CheckoutEnhancement\Model\CheckoutMetaFactory;

/**
 * Validates Indian addresses + QuickCart checkout fields and stores the delivery
 * instructions / GSTIN for the quote when the shipping step is saved.
 *
 * Guest checkout (GuestShippingInformationManagement) delegates to this service
 * with the real cart id, so both flows - and the REST API - are covered.
 */
class SaveCheckoutMeta
{
    /**
     * @param CheckoutFieldsValidator $validator
     * @param CheckoutMetaRepositoryInterface $repository
     * @param CheckoutMetaFactory $metaFactory
     */
    public function __construct(
        private readonly CheckoutFieldsValidator $validator,
        private readonly CheckoutMetaRepositoryInterface $repository,
        private readonly CheckoutMetaFactory $metaFactory
    ) {
    }

    /**
     * Before plugin for saveAddressInformation().
     *
     * @param ShippingInformationManagementInterface $subject
     * @param int|string $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return null
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagementInterface $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $extension = $addressInformation->getExtensionAttributes();
        $note = trim((string)($extension ? $extension->getQcDeliveryNote() : ''));
        $gstin = strtoupper(trim((string)($extension ? $extension->getQcGstin() : '')));

        $errors = $this->validator->validateMeta($note, $gstin);
        $address = $addressInformation->getShippingAddress();
        if ($address) {
            $errors = array_merge($errors, $this->validator->validateIndianAddress($address));
        }
        if ($errors) {
            throw new InputException(__(implode(' ', $errors)));
        }

        try {
            $meta = $this->repository->getByQuoteId((int)$cartId);
        } catch (NoSuchEntityException) {
            if ($note === '' && $gstin === '') {
                return null;
            }
            $meta = $this->metaFactory->create();
            $meta->setQuoteId((int)$cartId);
        }
        $meta->setDeliveryNote($note !== '' ? $note : null);
        $meta->setGstin($gstin !== '' ? $gstin : null);
        $this->repository->save($meta);

        return null;
    }
}
