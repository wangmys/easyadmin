<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单货品表 model
 */
class ErpRetailGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'RetailGoods';
}