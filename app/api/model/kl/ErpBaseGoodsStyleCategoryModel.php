<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * ErpBaseGoodsStyleCategoryModel
 */
class ErpBaseGoodsStyleCategoryModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Basegoodsstylecategory';

    protected $schema = [
        'StyleCategoryId' => 'bigint', 
        'ParentId' => 'bigint', 
        'StyleCategoryName' => 'nvarchar', 
        'ViewOrder' => 'int', 
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