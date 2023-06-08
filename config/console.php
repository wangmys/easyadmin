<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'curd'      => 'app\common\command\Curd',
        'node'      => 'app\common\command\Node',
        'OssStatic' => 'app\common\command\OssStatic',
        'worker' => 'think\Workerman\command\worker',
        'action'    => 'app\common\command\Action',
        'stock' => 'app\command\Stock',
        'duanma_sk' => 'app\command\Duanma_sk',
        'stock_size' => 'app\command\Stock_size',
        'sendpic' => 'app\command\Sendpic',
        'create_daogou_aim' => 'app\command\Create_daogou_aim',
        'customer_sz_detail' => 'app\command\Customer_sz_detail',
    ],
];
