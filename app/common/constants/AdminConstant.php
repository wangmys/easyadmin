<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\common\constants;

/**
 * 管理员常量
 * Class AdminConstant
 * @package app\common\constants
 */
class AdminConstant
{

    /**
     * 超级管理员，不受权限控制
     */
    const SUPER_ADMIN_ID = 1;

    /**
     * 库存筛选字段
     */
    const STOCK_FIELD = [
        'knapsack' =>'背包',
        'satchel' => '挎包',
        'necktie' =>'领带',
        'cap' =>'帽子',
        'briefs' =>'内裤',
        'belt' =>'皮带',
        'socks' =>'袜子',
        'handbag' =>'手包',
        'chestpack' =>'胸包'
    ];

    const NOT_FIELD = '店铺名称&省份&商品负责人';
}