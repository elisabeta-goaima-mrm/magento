<?php
namespace MyCompany\StorePickup\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class ShippingInformationManagement
{
    protected $quoteRepository;
    protected $logger;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }
    public function afterSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $result,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {

        $shippingAddress = $addressInformation->getShippingAddress();

        if (!$shippingAddress) {
            return $result;
        }

        if ($shippingAddress->getExtensionAttributes()) {
            $extAttributes = $shippingAddress->getExtensionAttributes();

            $pickupStore = $extAttributes->getPickupStore();
            $pickupTime = $extAttributes->getPickupTime();
            try {
                $quote = $this->quoteRepository->getActive($cartId);

                $quote->setData('pickup_store', $pickupStore);
                $quote->setData('pickup_time', $pickupTime);

                $this->quoteRepository->save($quote);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        } else {
            $this->logger->info('Extension attributes missing.');
        }

        return $result;
    }
}
