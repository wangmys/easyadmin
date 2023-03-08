<?php
declare (strict_types = 1);

namespace app\api\controller;
use think\cache\driver\Redis;
use think\facade\Db;

class Index
{
    public function index()
    {
        return '您好！这是一个[api]示例应用';
    }

    /**
     * 这里是列表
     */
    public function list(){
        echo '<pre>';
        print_r('这里是列表');
        die;
    }

    public function run()
    {
        $model = (new \app\http\logic\AddHistoryData);
//        $res = $model->run();
//        $model->showTaskInfo();
//        $res = $model->clearTask();
        echo '<pre>';
//        print_r($res);
        die;
    }

    public function rund()
    {
        $redis = new Redis;
        $model = (new \app\http\logic\AddHistoryData);
        while ($redis->llen('finish_task') < 365){
            $model->run();
        }
    }
    public function test()
    {
        $sql = 'show full columns from sp_customer_stock_sale_year';
        $list = Db::connect("mysql")->Query($sql);
        $key = array_column($list,'Field');
        $val = array_column($list,'Comment');
        $arr = array_combine($key,$val);
        foreach ($arr as $k=>$v){
            if(empty($v)){
                if(strpos($k,'StockQuantity') !== false){
                    // 包含StockQuantity
                    // 赋值替换
                    $val = str_replace('StockQuantity','店铺库存',$k);
                }else if(strpos($k,'Sales') !== false){
                    // 包含Sales
                    // 赋值替换
                    $val = str_replace('Sales','销售',$k);
                }
                $arr[$k] = $val;
            }
        }
        $db = Db::connect("mysql");
        $result = [];
        foreach ($arr as $kk => $vv){
            $change = "ALTER TABLE sp_customer_stock_sale_year CHANGE {$kk} {$vv}";
            $result[] = $change;
        }
        echo '<pre>';
        print_r($result);
        die;
    }
}
