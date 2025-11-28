<?php

namespace MyCompany\AdminLogger\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use MyCompany\AdminLogger\Model\ActionLogFactory;
use MyCompany\AdminLogger\Model\ResourceModel\ActionLog as ActionLogResource;

// data patches are used for modifications of data in a database
// they can be used as migrations or initial data insert
class AddInitialLog implements DataPatchInterface
{
    // usually we don't extend a data patch so we can make this fields private
    private $logFactory;
    private $actionLogResource;

    public function __construct(ActionLogFactory $logFactory, ActionLogResource $actionLogResource)
    {
        $this->logFactory = $logFactory;
        $this->actionLogResource = $actionLogResource;
    }

    public static function getDependencies()
    {
        // TODO: Implement getDependencies() method.
        return [];
    }

    public function getAliases()
    {
        // TODO: Implement getAliases() method.
        return [];
    }

    public function apply()
    {
        $log = $this->logFactory->create();
        $log->setData([
            'admin_username' => 'System',
            'action_type'    => 'install',
            'entity_type'    => 'module',
            'request_data'   => '{"message": "AdminLogger module installed"}',
            'ip_address'     => '127.0.0.1'
        ]);
        $this->actionLogResource->save($log);
        return $this;

    }
}
