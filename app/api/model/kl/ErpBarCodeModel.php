<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 出货指令单 model
 */
class ErpBarCodeModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Barcode';

}