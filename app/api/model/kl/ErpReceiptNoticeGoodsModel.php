<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购入库指令单商品 model
 */
class ErpReceiptNoticeGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Receiptnoticegoods';

    protected $schema = [
        'ReceiptNoticeGoodsId' => 'nvarchar',
        'ReceiptNoticeId' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
        'Remark' => 'nvarchar',
        'PurchaseID' => 'nvarchar',
        'CostPrice' => 'decimal',
        'ROutboundID' => 'nvarchar',
    ];
}