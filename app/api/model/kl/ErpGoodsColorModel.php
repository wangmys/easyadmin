<?php

namespace app\api\model\kl;

use app\common\model\TimeModel;

/**
 * 商品颜色 model
 */
class ErpGoodsColorModel extends TimeModel
{
    protected $connection = 'sqlsrv2';

    // 表名
    protected $name = 'Goodscolor';

    protected $schema = [
        'GoodsId' => 'bigint', 
        'ColorId' => 'bigint', 
        'ColorGroup' => 'nvarchar', 
        'ColorCode' => 'nvarchar', 
        'ColorDesc' => 'nvarchar', 
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