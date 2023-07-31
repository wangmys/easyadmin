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
        'skc_sz_detail' => 'app\command\Skc_sz_detail',
        'skc_shoe_detail' => 'app\command\Skc_shoe_detail',
        'skc_kz_detail' => 'app\command\Skc_kz_detail',
        'cus_weather_output' => 'app\command\Cus_weather_output',
        'ww_data' => 'app\command\Ww_data',
        'ww_data_diaobo' => 'app\command\Ww_data_diaobo',
        'ww_data_cusstock' => 'app\command\Ww_data_cusstock',
        'puhuo_yuncangkeyong' => 'app\command\Puhuo_yuncangkeyong',
        'puhuo_liangzhou' => 'app\command\Puhuo_liangzhou',
        'puhuo_start' => 'app\command\Puhuo_start',
    ],
];
