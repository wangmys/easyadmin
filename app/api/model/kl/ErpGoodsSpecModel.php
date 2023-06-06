<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * ErpGoodsSpecModel
 */
class ErpGoodsSpecModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Goodsspec';

    protected $schema = [
        'GoodsId' => 'bigint', 
        'SpecId' => 'bigint', 
        'SpecName' => 'nvarchar', 
        'IsEnable' => 'bit', 
        'CreateTime' => 'datetime',
        'CreateUserId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateTime' => 'datetime',
        'UpdateUserId' => 'bigint',
        'UpdateUserName' => 'nvarchar',
    ];

    const INSERT = [
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}