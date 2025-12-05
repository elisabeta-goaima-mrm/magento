<?php
namespace MyCompany\LegalPerson\Plugin\Checkout\Model;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressResource;
use Psr\Log\LoggerInterface;

class GuestPaymentInformationManagement
{
    protected $cartRepository;
    protected $addressResource;
    protected $quoteAddressFactory;
    protected $quoteIdMaskFactory;
    protected $logger;
    protected $request;

    protected $customAttributesList = [
        'legal_cui',
        'legal_company',
        'street_number',
        'building',
        'floor',
        'apartment'
    ];

    public function __construct(
        CartRepositoryInterface $cartRepository,
        AddressResource $addressResource,
        AddressFactory $quoteAddressFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LoggerInterface $logger,
        Request $request
    ) {
        $this->cartRepository = $cartRepository;
        $this->addressResource = $addressResource;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->logger = $logger;
        $this->request = $request;
    }

    public function afterSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $result,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $realCartId = $quoteIdMask->getQuoteId();

            $bodyParams = $this->request->getBodyParams();
            $billingData = $bodyParams['billingAddress'] ?? [];

            $valuesToSave = [];
            foreach ($this->customAttributesList as $key) {
                $val = null;
                if (!empty($billingData['extension_attributes'][$key])) {
                    $val = $billingData['extension_attributes'][$key];
                }
                if (!$val && !empty($billingData['customAttributes'])) {
                    foreach ($billingData['customAttributes'] as $attr) {
                        if (($attr['attribute_code'] ?? '') === $key) {
                            $val = $attr['value'];
                            break;
                        }
                    }
                }
                if ($val !== null) {
                    $valuesToSave[$key] = $val;
                }
            }

            $quote = $this->cartRepository->getActive($realCartId);
            $shippingAddress = $quote->getShippingAddress();

            if ($shippingAddress && $shippingAddress->getId()) {
                $freshShipping = $this->quoteAddressFactory->create()->load($shippingAddress->getId());
                foreach ($this->customAttributesList as $key) {
                    if (!isset($valuesToSave[$key])) {
                        $shippingVal = $freshShipping->getData($key);
                        if ($shippingVal) {
                            $valuesToSave[$key] = $shippingVal;
                        }
                    }
                }
            }

            if (!empty($valuesToSave)) {
                $quoteBillingAddress = $quote->getBillingAddress();
                if ($quoteBillingAddress && $quoteBillingAddress->getId()) {
                    $realBillingModel = $this->quoteAddressFactory->create()->load($quoteBillingAddress->getId());
                    if ($realBillingModel->getId()) {
                        foreach ($valuesToSave as $key => $value) {
                            $realBillingModel->setData($key, $value);
                        }
                        $this->addressResource->save($realBillingModel);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('LegalPerson GUEST Billing Error: ' . $e->getMessage());
        }

        return $result;
    }
}
