<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 收仓库收货单商品 model
 */
class ErpCustReceiptGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Custreceiptgoods';

    protected $schema = [
        'ReceiptGoodsID' => 'nvarchar',
        'ReceiptID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
    ];
}