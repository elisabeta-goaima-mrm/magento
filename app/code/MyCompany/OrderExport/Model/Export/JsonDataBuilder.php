<?php
namespace MyCompany\OrderExport\Model\Export;

use Magento\Sales\Api\Data\OrderInterface;

class JsonDataBuilder
{
    public function build(OrderInterface $order): array
    {
        $shipping = $order->getShippingAddress();

        return [
            'order_id'       => $order->getIncrementId(),
            'created_at'     => $order->getCreatedAt(),
            'state'          => $order->getState(),
            'status'         => $order->getStatus(),
            'customer_email' => $order->getCustomerEmail(),
            'total_amount'   => $order->getGrandTotal(),
            'discount'       => $order->getDiscountAmount(),
            'shipping_address' => [
                'city'    => $shipping ? $shipping->getCity() : '',
                'zipcode' => $shipping ? $shipping->getPostcode() : ''
            ]
        ];
    }
}
