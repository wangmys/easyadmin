<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 零售核销单库存流水表 model
 */
class ErpCustomerStockModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'CustomerStock';
}