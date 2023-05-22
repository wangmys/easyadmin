<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 采购入库指令单 商品详情 model
 */
class ErpReceiptNoticeGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Receiptnoticegoodsdetail';
    protected $schema = [
        'ReceiptNoticeGoodsId' => 'nvarchar',
        'ColorId' => 'bigint',
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'Quantity' => 'decimal',
    ];
}