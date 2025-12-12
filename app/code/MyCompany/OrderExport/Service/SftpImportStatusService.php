<?php
namespace MyCompany\OrderExport\Service;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class SftpImportStatusService
{
    const XML_PATH_SFTP_HOST     = 'mycompany_export/sftp/host';
    const XML_PATH_SFTP_USER     = 'mycompany_export/sftp/user';
    const XML_PATH_SFTP_PASSWORD = 'mycompany_export/sftp/password';
    const XML_PATH_SFTP_PATH     = 'mycompany_export/sftp/path';

    const PROCESSED_SUFFIX = '.json_processed';

    protected $sftp;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $scopeConfig;
    protected $encryptor;
    protected $logger;

    public function __construct(
        Sftp $sftp,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        LoggerInterface $logger
    ) {
        $this->sftp = $sftp;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
    }

    public function execute()
    {
        $host = $this->scopeConfig->getValue(self::XML_PATH_SFTP_HOST);
        $user = $this->scopeConfig->getValue(self::XML_PATH_SFTP_USER);
        $path = $this->scopeConfig->getValue(self::XML_PATH_SFTP_PATH);

        $encryptedPass = $this->scopeConfig->getValue(self::XML_PATH_SFTP_PASSWORD);
        $pass = $this->encryptor->decrypt($encryptedPass);

        if (!$host || !$user || !$pass) {
            $this->logger->error('Order Import Status: Missing SFTP credentials.');
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

            $files = $this->sftp->ls();

            foreach ($files as $file) {
                $filename = $file['text'];

                if (strpos($filename, self::PROCESSED_SUFFIX) !== false) {
                    $incrementId = str_replace(self::PROCESSED_SUFFIX, '', $filename);
                    $this->processOrder($incrementId, $filename);
                }
            }

            $this->sftp->close();

        } catch (\Exception $e) {
            $this->logger->critical('Order Import Status SFTP Error: ' . $e->getMessage());
        }
    }

    protected function processOrder($incrementId, $filename)
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId, 'eq')
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria)->getItems();
            $order = reset($orders);

            if (!$order) {
                $this->logger->warning("Order Import: File $filename found but order $incrementId does not exist.");
                return;
            }

            if ($order->getState() !== Order::STATE_COMPLETE) {
                $order->setState(Order::STATE_COMPLETE);
                $order->setStatus(Order::STATE_COMPLETE);
                $order->setExportStatus(2);

                $this->orderRepository->save($order);

                $this->logger->info("Order $incrementId marked as COMPLETE (File: $filename).");
            }

        } catch (\Exception $e) {
            $this->logger->error("Error processing order $incrementId: " . $e->getMessage());
        }
    }
}
