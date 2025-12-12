<?php
namespace MyCompany\OrderExport\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class MassRetry extends Action
{
    // link to acl
    const ADMIN_RESOURCE = 'MyCompany_OrderExport::retry_export';

    protected $filter;
    protected $collectionFactory;

    public function __construct(Action\Context $context, Filter $filter, CollectionFactory $collectionFactory)
    {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;

        foreach ($collection as $order) {
            $order->setExportStatus(0); // Reset to Pending
            $order->save();
            $count++;
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 order(s) have been reset for export retry.', $count));
        return $this->resultRedirectFactory->create()->setPath('sales/order/index');
    }
}
