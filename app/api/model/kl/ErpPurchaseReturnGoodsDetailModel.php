<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购退货单商品详情 model
 */
class ErpPurchaseReturnGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Purchasereturngoodsdetail';
    protected $schema = [
        'PurchaseReturnGoodsId' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}