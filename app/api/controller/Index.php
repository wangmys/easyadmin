<?php
declare (strict_types = 1);

namespace app\api\controller;
use app\admin\model\dress\YinliuStore;
use app\common\constants\AdminConstant;
use think\cache\driver\Redis;
use think\facade\Db;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\Yinliu;

class Index
{
    public function index()
    {
        return '您好！这是一个[api]示例应用';
    }

    /**
     * 执行任务
     */
    public function rund()
    {
        $redis = new Redis;
        $model = (new \app\http\logic\AddHistoryData);
        while ($redis->llen('finish_task') <= 396){
            $model->run();
        }
        echo '<pre>';
        print_r(22);
        die;
    }

    public function test1()
    {
        $model = (new \app\http\logic\AddHistoryData);
        $redis = new Redis;

//        $num = $model->redis->lpop('task_queue');
//        // 添加任务完成记录
//        $model->redis->rpush('finish_task',$num);
        $d = $redis->lindex('task_queue',0);
        echo '<pre>';
        print_r($d);
        die;
        echo '<pre>';
        print_r(date('Y-m-d',strtotime('2022-01-01'."+{$d}day")));
//        $model->showTaskInfo();
        die;
    }
}
