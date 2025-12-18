<?php
namespace MyCompany\OrderExport\Model\Export;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;
use MyCompany\OrderExport\Model\Export\Strategy\BuilderInterface;

class JsonDataBuilder
{
    const XML_PATH_FORMAT_VERSION = 'mycompany_export/sftp/format_version';
    protected $strategies;
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        array $strategies = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->strategies = $strategies;
    }

    public function build(OrderInterface $order): array
    {
        $version = $this->scopeConfig->getValue(self::XML_PATH_FORMAT_VERSION);

        if (!isset($this->strategies[$version])) {
            $version = 'standard';
        }

        if (!isset($this->strategies[$version])) {
            throw new \Exception("Export strategy '{$version}' not found.");
        }

        return $this->strategies[$version]->build($order);
    }
}
