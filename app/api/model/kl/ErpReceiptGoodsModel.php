<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨入库单商品 model
 */
class ErpReceiptGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Receiptgoods';

    protected $schema = [
        'ReceiptGoodsId' => 'nvarchar',
        'ReceiptId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
        'Remark' => 'nvarchar',
        'PurchaseID' => 'nvarchar',
        'DeliveryId' => 'nvarchar',
        'ReturnId' => 'nvarchar',
        'InstructionId' => 'nvarchar',
        'ReceiptNoticeId' => 'varchar',
        'CostPrice' => 'decimal',
        'JUnitPrice' => 'decimal',
        'JDiscount' => 'decimal',
        'ReferCostPrice' => 'decimal',
        'ReferCostAmount' => 'decimal',
    ];
}