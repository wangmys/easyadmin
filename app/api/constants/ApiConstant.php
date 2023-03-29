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
}