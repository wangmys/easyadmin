<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库收货单商品详情 model
 */
class ErpDeliveryGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Deliverygoodsdetail';
    protected $schema = [
        'DeliveryGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}