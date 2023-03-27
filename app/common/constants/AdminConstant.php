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

    /**
     * 配饰列表
     */
    const ACCESSORIES_LIST = [
      '背包',
      '挎包',
      '领带',
      '帽子',
      '内裤',
      '皮带',
      '袜子',
      '手包',
      '胸包',
    ];

    /**
     * 钉钉ID
     */
    // 王威
    const ID_WV = '0812473564939990';
    // 王梦园
    const ID_WMY = '293746204229278162';

    /**
     * 商品负责人
     */
    const CHARGE_LIST = [
        '于燕华',
        '周奇志',
        '廖翠芳',
        '张洋涛',
        '易丽平',
        '曹太阳',
        '林冠豪',
        '许文贤',
        '黎亿炎'
    ];
}