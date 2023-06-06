<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * BarCode model
 */
class ErpBarCodeModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Barcode';

    protected $schema = [
        'BarCode' => 'varchar', 
        'BranchId' => 'bigint', 
        'GoodsId' => 'bigint', 
        'ColorId' => 'bigint', 
        'SizeId' => 'bigint',
        'SpecId' => 'bigint',
        'CreateTime' => 'datetime',
        'CreateUserId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateTime' => 'datetime',
        'UpdateUserId' => 'bigint',
        'UpdateUserName' => 'nvarchar',
    ];

    const INSERT = [
        'BranchId' => '2',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}