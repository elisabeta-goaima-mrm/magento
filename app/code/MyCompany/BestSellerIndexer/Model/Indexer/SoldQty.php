<?php

namespace MyCompany\BestSellerIndexer\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\App\ResourceConnection;
class SoldQty implements IndexerActionInterface, MviewActionInterface
{
    private $resource;

    public function __construct(ResourceConnection $resource){
        $this->resource = $resource;
    }

    public function executeFull()
    {
        // 1. calculate for each product how much was sold

        // 1.a. get the table and make the select on the table

        $connection = $this->resource->getConnection();
        $salesTable = $this->resource->getTableName('sales_order_item');

        $select = $connection->select()
        ->from($salesTable, ['product_id', 'sum_qty' => new \Zend_Db_Expr('SUM(qty_ordered)')])
        ->where('parent_item_id IS NULL')
        ->group('product_id');

        $salesData = $connection->fetchAll($select);

        $attributeId = $this->getAttributeId('sold_qty');
        $tableName = $this->resource->getTableName('catalog_product_entity_int');

        if(!$attributeId){
            return;
        }

        // 2. insert / update the table catalog_product_entity_int with the qty

        $dataToInsert = [];
        foreach ($salesData as $data) {
            $dataToInsert[] = [
                'attribute_id' => $attributeId,
                'store_id' => 0, // global
                'entity_id' => $data['product_id'],
                'value' => (int) $data['sum_qty'],
            ];
        }

        if(!empty($dataToInsert)){
            $connection->insertOnDuplicate(
                $tableName,
                $dataToInsert, ['value'] // when updating if a row exists already override the column value
            );
        }


    }

    private function getAttributeId($attributeCode){
        $connection = $this->resource->getConnection();
        $eavTable = $this->resource->getTableName('eav_attribute');
        $select = $connection->select()
        ->from($eavTable, ['attribute_id'])
        ->where('attribute_code = ?', $attributeCode)
        ->where('entity_type_id = ?', 4);

        return $connection->fetchOne($select);
    }

    public function executeList(array $ids)
    {
        $this->executeFull();
    }

    public function executeRow($id)
    {
        $this->executeFull();
    }

    public function execute($ids)
    {
        $this->executeFull();
    }
}
