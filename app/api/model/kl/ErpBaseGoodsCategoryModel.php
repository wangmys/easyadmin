<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * ErpBaseGoodsCategoryModel
 */
class ErpBaseGoodsCategoryModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Basegoodscategory';

    protected $schema = [
        'CategoryId' => 'bigint', 
        'ParentId' => 'bigint', 
        'CategoryName' => 'nvarchar', 
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