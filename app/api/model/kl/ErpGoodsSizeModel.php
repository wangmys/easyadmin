<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 商品尺码 model
 */
class ErpGoodsSizeModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Goodssize';

    protected $schema = [
        'GoodsId' => 'bigint', 
        'SizeId' => 'bigint',
        'SizeClass' => 'nvarchar',
        'Size' => 'nvarchar',
        'IsEnable' => 'bit',
        'CreateTime' => 'datetime',
        'CreateUserId' => 'bigint',
        'CreateUserName' => 'nvarchar',
        'UpdateTime' => 'datetime',
        'UpdateUserId' => 'bigint',
        'UpdateUserName' => 'nvarchar',
    ];

    const INSERT = [
        'IsEnable' => '1',
        'CreateUserId' => '29',
        'CreateUserName' => '辛斌',
        'UpdateUserId' => '29',
        'UpdateUserName' => '辛斌',
    ];

}