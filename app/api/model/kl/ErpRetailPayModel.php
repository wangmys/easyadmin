<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单支付表 model
 */
class ErpRetailPayModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'RetailPay';
}