<?php
namespace MyCompany\AdminLogger\Observer;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MyCompany\AdminLogger\Helper\Config;
use MyCompany\AdminLogger\Model\ActionLogFactory;
use MyCompany\AdminLogger\Model\ResourceModel\ActionLog as ActionLogResource;
use Psr\Log\LoggerInterface;

class LogAdminAction implements ObserverInterface
{
    protected $logFactory;
    protected $authSession;
    protected $configHelper;
    protected $remoteAddress;
    protected $dateTime;
    protected $jsonSerializer;
    protected $request;

    protected $actionType;
    protected $entityType;
    protected $actionLogResource;
    protected $logger;

    public function __construct(
        ActionLogFactory $logFactory,
        ActionLogResource $actionLogResource,
        Session $authSession,
        Config $configHelper,
        RemoteAddress $remoteAddress,
        DateTime $dateTime,
        Json $jsonSerializer,
        RequestInterface $request,
        LoggerInterface $logger,
        string $actionType = 'unknown',
        string $entityType = 'unknown',
    ) {
        $this->logFactory = $logFactory;
        $this->actionLogResource = $actionLogResource;
        $this->authSession = $authSession;
        $this->configHelper = $configHelper;
        $this->remoteAddress = $remoteAddress;
        $this->dateTime = $dateTime;
        $this->jsonSerializer = $jsonSerializer;
        $this->request = $request;
        $this->actionType = $actionType;
        $this->entityType = $entityType;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }
        $actionType = $this->actionType;
        $entityType = $this->entityType;

        $allowedActions = $this->configHelper->getLoggedActionTypes();
        $allowedEntities = $this->configHelper->getLoggedEntities();

        if (!in_array($actionType, $allowedActions) || !in_array($entityType, $allowedEntities)) {
            return;
        }

        $entityId = null;
        $requestData = null;

        if ($actionType == 'save' || $actionType == 'delete') {
            $eventObject = $observer->getData($entityType) ?: $observer->getData('object');

            if (!$eventObject && $entityType == 'customer') {
                $eventObject = $observer->getData('customer');
            }

            if ($eventObject && method_exists($eventObject, 'getId')) {
                $entityId = $eventObject->getId();
            }

            if ($actionType == 'save') {
                $requestData = $this->jsonSerializer->serialize($this->request->getPostValue());
            }
        } elseif ($actionType == 'edit' || $actionType == 'view') {
            $entityId = $this->request->getParam('id') ?: $this->request->getParam('entity_id');
        }

        $user = $this->authSession->getUser();
        if (!$user) {
            return;
        }

        try {
            $log = $this->logFactory->create();
            $log->setData([
                'admin_user_id' => $user->getId(),
                'admin_username' => $user->getUserName(),
                'action_type' => $actionType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'request_data' => $requestData,
                'ip_address' => $this->remoteAddress->getRemoteAddress(),
                'user_agent' => $this->request->getHeader('User-Agent'),
                'created_at' => $this->dateTime->gmtDate()
            ]);
            $this->actionLogResource->save($log);
        } catch (\Exception $e) {
            $this->logger->error('AdminLogger Error: ' . $e->getMessage());
        }
    }
}
