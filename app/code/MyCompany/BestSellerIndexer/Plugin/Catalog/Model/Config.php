<?php

namespace MyCompany\BestSellerIndexer\Plugin\Catalog\Model;

class Config
{
    public function afterGetAttributeUsedForSortByArray(
        \Magento\Catalog\Model\Config $subject,
        $result
    ) {
        if(isset($result['sold_qty'])){
            $result['sold_qty'] = __('Best Seller');
        }

        return $result;
    }
}
