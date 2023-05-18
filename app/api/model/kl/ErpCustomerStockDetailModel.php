<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单库存流水尺码表 model
 */
class ErpCustomerStockDetailModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'CustomerStockDetail';
}