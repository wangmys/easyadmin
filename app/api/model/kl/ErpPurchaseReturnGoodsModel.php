<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购退货单商品 model
 */
class ErpPurchaseReturnGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Purchasereturngoods';

    protected $schema = [
        'PurchaseReturnGoodsId' => 'nvarchar',
        'PurchaseReturnId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
        'Remark' => 'nvarchar',
        'PurchaseID' => 'nvarchar',
        'PuReturnNoticeId' => 'nvarchar',
        'DeliveryId' => 'nvarchar',
        'ReturnId' => 'nvarchar',
        'CostPrice' => 'decimal',
    ];
}