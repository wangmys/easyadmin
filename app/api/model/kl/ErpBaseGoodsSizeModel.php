<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * ErpBaseGoodsSize model
 */
class ErpBaseGoodsSizeModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Basegoodssize';

    protected $schema = [
        'SizeId' => 'bigint', 
        'SizeClass' => 'nvarchar', 
        'ClassName' => 'nvarchar', 
        'Size' => 'nvarchar', 
        'ViewOrder' => 'int', 
        'IsEnable' => 'bit',
        'BranchId' => 'bigint', 
        'IsSystemDefault' => 'bit',
        'CreateTime' => 'datetime',
        'CreateUserId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateTime' => 'datetime',
        'UpdateUserId' => 'bigint',
        'UpdateUserName' => 'nvarchar',
    ];

    const INSERT = [
        'IsEnable' => '1',
        'IsSystemDefault' => '0',
        'BranchId' => '2',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}