<?php
declare (strict_types = 1);

namespace app\api\controller;
use think\cache\driver\Redis;
use think\facade\Db;
use app\api\model\store\Zy;
use app\api\model\store\Jm;
use app\api\service\DingdingService;

class Store
{

    public function test111111()
    {
        // 类名
        $name = '\app\api\service\DingdingService';
        $model = new $name;
        $res = $model->send();
        echo '<pre>';
        print_r($res);
        die;
    }

    /**
     * 创建表格
     */
    public function create_zy_table()
    {
        $arr = [];
        // 查询数据
        $zy_model = new Zy;
        $field = '云仓,省份,店铺名称,_2022冬预计库存,_2022冬采购额,_2022冬近一周销量,_2021以及之前冬预计库存,_2021以及之前采购额,_2021以及之前近一周销量,库存汇总,采购额汇总,近一周销量汇总';
        // 查询云仓分组
        $yuncang_group = $zy_model->where('云仓','<>','总计')->group('云仓')->column('云仓');
        // 实例化
        $model = new DingdingService;
        // 循环查询数据
        foreach ($yuncang_group as $key => $val){
            // 查询云仓数据
            $data = $zy_model->where([
                '云仓' => $val
            ])->select();

            $list = $zy_model->field($field)->where(['云仓' => $val])->select()->toArray();

            $table_header = ['行号'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 130;
            }
            $field_width[6] = 200;
            $field_width[7] = 200;
            $field_width[8] = 200;
            $field_width[9] = 200;
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
            ];
            
            // 编号
            $code = '99' + $key;
            //参数
            $params = [
                'row' => count($list),          //数据的行数
                'file_name' => 'S'.$code.'.jpg',   //保存的文件名
                'title' => "直营店铺预计库存 - 数据更新时间 （". date("Y-m-d", strtotime("-1 day")) ."）",
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: S'.$code,
                'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
            ];
            $path = $params['file_path'].$params['file_name'];
            // 生成图片
            $res = $model->create_image($params);
            $arr[] = $res;
        }
    }

    /**
     * 创建表格
     */
    public function create_jm_table()
    {
        // 查询数据
        $jm_model = new Jm;
        $field = '加盟商客户,店铺名称,_2022冬预计库存,_2022冬分销额,_2022冬近一周销量,_2021以及之前冬预计库存,_2021以及之前分销额,_2021以及之前近一周销量,库存汇总,分销额汇总,近一周销量汇总';
        // 实例化
        $model = new DingdingService;

        $list = $jm_model->field($field)->order('加盟商客户')->select()->toArray();

        $table_header = ['行号'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 130;
        }
        $field_width[6] = 200;
        $field_width[7] = 200;
        $field_width[8] = 200;
        $field_width[9] = 200;
        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
        ];

        // 编号
        $code = '199';
        //参数
        $params = [
            'row' => count($list),          //数据的行数
            'file_name' => 'S'.$code.'.jpg',   //保存的文件名
            'title' => "加盟店铺冬季预计库存 - 数据更新时间 （". date("Y-m-d", strtotime("-1 day")) ."）",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: S'.$code,
            'file_path' => "./img/".date('Ymd').'/'  //文件保存路径
        ];
        $path = $params['file_path'].$params['file_name'];
        // 生成图片
        $res = $model->create_image($params);
        $arr = $res;
    }
}
