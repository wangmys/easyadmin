<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 出货指令单 model
 */
class ErpGoodsModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Goods';

}