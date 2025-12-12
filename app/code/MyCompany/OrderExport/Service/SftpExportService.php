<?php
namespace MyCompany\OrderExport\Service;

use Magento\Framework\Filesystem\Io\Sftp;
use MyCompany\OrderExport\Model\Export\JsonDataBuilder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;

class SftpExportService
{
    const XML_PATH_SFTP_HOST     = 'mycompany_export/sftp/host';
    const XML_PATH_SFTP_USER     = 'mycompany_export/sftp/user';
    const XML_PATH_SFTP_PASSWORD = 'mycompany_export/sftp/password';
    const XML_PATH_SFTP_PATH     = 'mycompany_export/sftp/path';

    protected $sftp;
    protected $jsonBuilder;
    protected $orderCollectionFactory;
    protected $scopeConfig;
    protected $logger;
    protected $encryptor;

    public function __construct(
        Sftp $sftp,
        JsonDataBuilder $jsonBuilder,
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        EncryptorInterface $encryptor
    ) {
        $this->sftp = $sftp;
        $this->jsonBuilder = $jsonBuilder;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
    }

    public function execute()
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('export_status', 0)
            ->addFieldToFilter('state', ['neq' => 'canceled']);

        if ($collection->getSize() === 0) {
            return;
        }

        $host = $this->scopeConfig->getValue(self::XML_PATH_SFTP_HOST);
        $user = $this->scopeConfig->getValue(self::XML_PATH_SFTP_USER);
        $path = $this->scopeConfig->getValue(self::XML_PATH_SFTP_PATH);

        $encryptedPass = $this->scopeConfig->getValue(self::XML_PATH_SFTP_PASSWORD);
        $pass = $this->encryptor->decrypt($encryptedPass);

        if (!$host || !$user || !$pass) {
            $this->logger->error('Order Export: Missing SFTP credentials in configuration.');
            return;
        }

        try {
            $this->sftp->open([
                'host'     => $host,
                'username' => $user,
                'password' => $pass
            ]);

            if ($path) {
                if (!$this->sftp->cd($path)) {
                    throw new \Exception("Could not change to directory: $path");
                }
            }

            foreach ($collection as $order) {
                try {
                    $data = $this->jsonBuilder->build($order);
                    $jsonString = json_encode($data, JSON_PRETTY_PRINT);

                    $filename = $order->getIncrementId() . '.json';

                    $writeResult = $this->sftp->write($filename, $jsonString);

                    if ($writeResult) {
                        $order->setExportStatus(1);
                        $order->save();
                        $this->logger->info("Order {$order->getIncrementId()} exported successfully.");
                    } else {
                        $this->logger->error("Failed to write file for Order {$order->getIncrementId()}");
                    }

                } catch (\Exception $e) {
                    $this->logger->error("Error exporting Order {$order->getIncrementId()}: " . $e->getMessage());
                }
            }

            $this->sftp->close();

        } catch (\Exception $e) {
            $this->logger->critical('Order Export SFTP Critical Error: ' . $e->getMessage());
        }
    }
}
