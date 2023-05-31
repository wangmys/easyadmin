<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * ErpBaseGoodsColor model
 */
class ErpBaseGoodsColorModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Basegoodscolor';

    protected $schema = [
        'ColorId' => 'bigint', 
        'BranchId' => 'bigint', 
        'ColorGroup' => 'nvarchar', 
        'ColorCode' => 'nvarchar', 
        'ColorDesc' => 'nvarchar', 
        'ColorImg' => 'nvarchar',
        'IsEnable' => 'bit',
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
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}