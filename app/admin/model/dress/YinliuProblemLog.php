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

namespace app\admin\model\dress;


use app\common\model\TimeModel;

class YinliuProblemLog extends TimeModel
{
    // 表名
    protected $name = 'yinliu_problem_log';

    public static function selfSaveData($data)
    {
        // 实例化
        $model = new self();
        return $model->saveAll($data);
    }

}