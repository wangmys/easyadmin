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
     * 这里是列表
     */
    public function list(){
        echo '<pre>';
        print_r('这里是列表');
        die;
    }

    /**
     * 执行任务
     */
    public function rund()
    {
        $redis = new Redis;
        $model = (new \app\http\logic\AddHistoryData);
        while ($redis->llen('finish_task') <= 31){
            $model->run();
        }
        echo '<pre>';
        print_r(22);
        die;
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
    public function test1()
    {
        $model = (new \app\http\logic\AddHistoryData);
        $redis = new Redis;
        $d = $redis->lindex('task_queue',0);
        echo '<pre>';
        print_r(date('Y-m-d',strtotime('2023-01-01'."+{$d}day")));
        $model->showTaskInfo();
        die;
    }

    public function get()
    {
        // 指定商品负责人
        $charge = $get['商品负责人']??'曹太阳';
        // 获取开始至结束日期
        $date_all = getThisDayToStartDate();
        // 开始日期
        $start_date = $date_all[0];
        // 结束日期
        $end_date = $date_all[1];
        // 查询负责人待完成店铺
        $model = new YinliuStore;
        // 查询开始数据
        $info = $model->where([
            '商品负责人' => $charge,
            'Date' => $start_date
        ])->find();
        $info_1 = $model->where([
            '商品负责人' => $charge,
            'Date' => $end_date
        ])->find();
        $merge_data = [];
        $list = AdminConstant::ACCESSORIES_LIST;
         // 获取配置
        $config = sysconfig('stock_warn');
        $arr1 = [];
        if($info && $info_1){
            $info = $info->toArray();
            foreach ($info as $k => $v){
                if(in_array($k,$list)){
                    // 获取标准判断
                    $standard = $config[$k];
                    $arr = ['商品负责人' => $info['商品负责人']];
                    $arr['配饰'] = $k;
                    if(empty($v)){
                        $store_arr = [];
                    }else{
                        $store_arr = explode(',',$v);
                    }
                    $arr['问题店铺'] = count($store_arr);
                    // 剩余店铺
                    $store_list = Yinliu::where([
                        '商品负责人' => $info['商品负责人'],
                        'Date' => $end_date,
                        '店铺名称' => $store_arr
                    ])->where(function ($q)use($k,$standard){
                        if($standard > 0){
                            $q->whereNull($k);
                        }
                        $q->whereOr($k,'<',$standard);
                    })->column('店铺名称');
                    $arr['剩余店铺'] = count($store_list);
                    $arr1[] = $arr;
                }
            }
            return $arr1;
        }
    }
}
