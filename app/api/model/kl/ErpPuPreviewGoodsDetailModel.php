<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购预收单 商品详情 model
 */
class ErpPuPreviewGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Pupreviewgoodsdetail';
    protected $schema = [
        'PuPreviewGoodsID' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}