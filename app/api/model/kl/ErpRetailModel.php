<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单主表 model
 */
class ErpRetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Retail';
}