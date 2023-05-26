<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 仓库调拨入库单商品详情 model
 */
class ErpReceiptGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Receiptgoodsdetail';
    protected $schema = [
        'ReceiptGoodsId' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}