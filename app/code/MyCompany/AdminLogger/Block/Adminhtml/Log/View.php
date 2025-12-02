<?php

namespace MyCompany\AdminLogger\Block\Adminhtml\Log;

use Magento\Backend\Block\Template;
use MyCompany\AdminLogger\Model\ActionLogFactory;
use MyCompany\AdminLogger\Model\ResourceModel\ActionLog as ActionLogResource;

class View extends Template
{
    protected $logFactory;
    protected $currentLog;

    protected $actionLogResource;

    public function __construct(
        Template\Context $context,
        ActionLogFactory $logFactory,
        ActionLogResource $actionLogResource,
        array $data = []
    ){
        $this->logFactory = $logFactory;
        $this->actionLogResource = $actionLogResource;
        parent::__construct($context, $data);
    }

    public function getLog()
    {
        if(!$this->currentLog) {
            $id= $this->getRequest()->getParam('id');
            $log = $this->logFactory->create();
            $this->actionLogResource->load($log, $id);
            $this->currentLog = $log;
        }
        return $this->currentLog;
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/index');
    }
}
