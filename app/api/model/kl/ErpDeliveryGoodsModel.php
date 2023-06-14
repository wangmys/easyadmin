<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库收货单商品 model
 */
class ErpDeliveryGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Deliverygoods';

    protected $schema = [
        'DeliveryGoodsID' => 'nvarchar',
        'DeliveryID' => 'nvarchar',
        'GoodsId' => 'bigint',
        'UnitPrice' => 'decimal',
        'Price' => 'decimal',
        'Quantity' => 'decimal',
        'Discount' => 'decimal',
        'SortingID' => 'nvarchar',
    ];
}