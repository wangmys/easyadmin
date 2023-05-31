<?php

namespace app\api\constants;

/**
 * API常量
 * Class AdminConstant
 * @package app\api\constants
 */
class ApiConstant
{
    /**
     * 引流配饰的所需字段
     */
    const YINLIU_FIELD = [
        '省份',
        'Date',
        '店铺名称',
        '商品负责人',
        '背包',
        '挎包',
        '领带',
        '帽子',
        '内裤',
        '皮带',
        '袜子',
        '手包',
        '胸包'
    ];

    const SUCCESS_CODE = 1;
    const ERROR_CODE = 0;
    const ERROR_CODE_1 = 10001;
    const ERROR_CODE_2 = 10002;
    const ERROR_CODE_3 = 10002;

    const ERROR_CODE_LIST = [
      self::SUCCESS_CODE => '执行成功',
      self::ERROR_CODE => '执行失败',
      self::ERROR_CODE_1 => '今日数据已处理',
      self::ERROR_CODE_2 => '执行成功',
      self::ERROR_CODE_3 => '今日数据未拉取',
    ];

    /**
     * 商品专员钉钉列表
     */
    const DINGDING_ID_LIST = [
        [
            '商品负责人' => '任文秀',
            '所属云仓' => '武汉云仓',
            'tel' => '13535001705',
            'userid' => '0159492060301296848184'
        ],
        [
            '商品负责人' => '曹太阳',
            '所属云仓' => '武汉云仓',
            'tel' => '18720923905',
            'userid' => '29523612796930823'
        ],
        [
            '商品负责人' => '许文贤',
            '所属云仓' => '贵阳云仓',
            'tel' => '18027380882',
            'userid' => '0144542031592020143914'
        ],
        [
            '商品负责人' => '易丽平',
            '所属云仓' => '贵阳云仓',
            'tel' => '19128636235',
            'userid' => '013127343113796417702'
        ],
        [
            '商品负责人' => '于燕华',
            '所属云仓' => '南昌云仓',
            'tel' => '18102558436',
            'userid' => '0161036132291919586862'
        ],
        [
            '商品负责人' => '廖翠芳',
            '所属云仓' => '南昌云仓',
            'tel' => '15070800024',
            'userid' => '013200064128903549467'
        ],
        [
            '商品负责人' => '张洋涛',
            '所属云仓' => '广州云仓',
            'tel' => '18027386358',
            'userid' => '0555042929802744940'
        ],
        [
            '商品负责人' => '周奇志',
            '所属云仓' => '广州云仓',
            'tel' => '18720934041',
            'userid' => '013127403444-666705531'
        ],
        [
            '商品负责人' => '黎亿炎',
            '所属云仓' => '长沙云仓',
            'tel' => '13507099871',
            'userid' => '6500374158790510025'
        ],
        [
            '商品负责人' => '林冠豪',
            '所属云仓' => '长沙云仓',
            'tel' => '19927623064',
            'userid' => '016154094852-538174336'
        ]
    ];

    /**
     * 管理者钉钉列表
     */
    const DINGDING_MANAGE_LIST = [
//        [
//            'name' => '王梦园',
//            'tel' => '17775611493',
//            'userid' => '293746204229278162'
//        ],
        [
            'name' => '王威',
            'tel' => '15880012590',
            'userid' => '0812473564939990'
        ]
    ];

    /**
     * 码比数据缓存key,通过缓存的数据同步到MYSQL数据库
     */
    const RATIO_PULL_REDIS_KEY = [
        // 周销
        '7DaySale',
        // 累销
        'AccumulatedSale',
        // 店铺预计库存
        'ShopEstimatedStock',
        // 云仓可用库存
        'WarehouseAvailableStock',
        // 云仓在途库存
        'WarehouseTransitStock',
        // 云仓采购库存
        'PurchaseStock'
    ];
}