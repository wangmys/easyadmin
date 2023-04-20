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
 * 店铺端常量
 * Class ShopContstant
 * @package app\common\constants
 */
class ShopContstant
{
    /**
     * 超级管理员，不受权限控制
     */
    const SUPER_ADMIN_ID = -1;

    // 登录账户表
    const  AUTH_USER_MODEL = 'app\admin\model\ShopUser';
}