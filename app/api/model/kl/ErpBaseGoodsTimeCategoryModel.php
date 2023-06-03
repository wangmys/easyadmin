<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * ErpBaseGoodsTimeCategoryModel
 */
class ErpBaseGoodsTimeCategoryModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Basegoodstimecategory';

    protected $schema = [
        'TimeCategoryId' => 'bigint', 
        'ParentId' => 'bigint', 
        'TimeCategoryName' => 'nvarchar', 
        'ViewOrder' => 'int', 
        'Level' => 'int',
        'BranchId' => 'bigint', 
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