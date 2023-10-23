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
        'stock2' => 'app\command\Stock2',
        'stock_week_date' => 'app\command\Stock_week_date',
        'stock_week' => 'app\command\Stock_week',
        'stock_week_select' => 'app\command\Stock_week_select',
        'duanma_sk' => 'app\command\Duanma_sk',
        'cwl_caigou' => 'app\command\Cwi_caigou',
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
        'ww_data_cussale14day' => 'app\command\Ww_data_cussale14day',
        'puhuo_yuncangkeyong' => 'app\command\Puhuo_yuncangkeyong',
        'puhuo_shangshiday' => 'app\command\Puhuo_shangshiday',
        'puhuo_liangzhou' => 'app\command\Puhuo_liangzhou',
        'puhuo_spsk_stock' => 'app\command\Puhuo_spsk_stock',
        'puhuo_start1' => 'app\command\Puhuo_start1',
        'puhuo_start2' => 'app\command\Puhuo_start2',
        'puhuo_start3' => 'app\command\Puhuo_start3',
        'puhuo_start4' => 'app\command\Puhuo_start4',
        'puhuo_start5' => 'app\command\Puhuo_start5',
        'puhuo_start6' => 'app\command\Puhuo_start6',
        'puhuo_end' => 'app\command\Puhuo_end',
        'puhuo_end_data' => 'app\command\Puhuo_end_data',
    ],
];
