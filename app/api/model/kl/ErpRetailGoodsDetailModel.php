<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单货品明细尺码表 model
 */
class ErpRetailGoodsDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'RetailGoodsDetail';
}