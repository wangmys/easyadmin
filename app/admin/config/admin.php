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

return [

    // 不需要验证登录的控制器
    'no_login_controller' => [
        'login',
        'system.dress.inventory'
    ],

    // 不需要验证登录的节点
    'no_login_node'       => [
        'login/index',
        'login/out',
        'system.dress.inventory/index',
        'system.dress.inventory/question',
        'system.dress.inventory/finish_rate',
        'system.dress.inventory/gather',
        'system.dress.inventory/task_overview',
        'system.dress.inventory/stock',
    ],

    // 不需要验证权限的控制器
    'no_auth_controller'  => [
        'ajax',
        'login',
        'index',
        'system.dress.inventory'
    ],

    // 不需要验证权限的节点
    'no_auth_node'        => [
        'login/index',
        'login/out',
        'system.dress.inventory/index',
        'system.dress.inventory/question',
        'system.dress.inventory/finish_rate',
        'system.dress.inventory/gather',
        'system.dress.inventory/task_overview',
        'system.dress.inventory/stock',
    ],
];