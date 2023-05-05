<?php
declare (strict_types = 1);

namespace app\api\controller;
use app\admin\model\dress\YinliuStore;
use app\common\constants\AdminConstant;
use think\cache\driver\Redis;
use think\facade\Db;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\Yinliu;
use voku\helper\HtmlDomParser;
use app\admin\model\weather\Customers;

class Index
{
    public function index()
    {
        $url = "http://www.weather.com.cn/weather40d/101280101.shtml";
        $url = "https://tianqi.2345.com/wea_forty/57516.htm";
        $html = HtmlDomParser::file_get_html($url);
        $el = $html->find('ul[class="weeks-names"] li');
        echo '<pre>';
        print_r($el);
        die;
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

    /**
     * 执行任务
     */
    public function run()
    {
        $redis = new Redis;
        $model = (new \app\http\logic\AddHistoryData);
        while ($redis->llen('finish_task') <= 145){
            $model->run2();
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
//        die;

        echo '<pre>';
        print_r(date('Y-m-d',strtotime('2022-07-09'."+{$d}day")));
//        $model->showTaskInfo();
        die;
    }

    /**
     * 更新天气温带+气温区域
     */
    public function updateWeatherInfo()
    {
        // 查询所有门店ID
        $ids = Customers::where('CustomItem30','=','')->column('CustomerId');
        // 根据所有门店ID查询所属温带 + 气温区域
        $data = Db::connect("sqlsrv")->table('ErpCustomer')->whereIn('CustomerId',$ids)->column('CustomItem30,CustomItem36','CustomerId');
        $update_data = [];
        foreach ($data as $k => $v){
            $update_data[$v['CustomerId']] = [
                'CustomItem30' => $v['CustomItem30'],
                'CustomItem36' => $v['CustomItem36'],
            ];
        }
        Db::startTrans();
        $result = [];
        try {
            foreach ($update_data as $kk=>$vv){
                $result[$kk] =  Customers::where([
                    'CustomerId' => $kk
                ])->update($vv);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return json([
                'msg' => $e->getMessage(),
                'code' => 0
            ]);
        }
        return json([
            'msg' => '成功',
            'code' => 1,
            'data' => $result
        ]);
    }
}
