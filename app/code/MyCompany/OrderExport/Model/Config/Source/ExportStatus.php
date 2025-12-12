<?php
namespace MyCompany\OrderExport\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ExportStatus implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Pending')],
            ['value' => 1, 'label' => __('Exported')],
            ['value' => 2, 'label' => __('Processed')],
        ];
    }
}
