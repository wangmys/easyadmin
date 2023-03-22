<?php
declare (strict_types = 1);

namespace app\api\controller;
use think\facade\Db;
use app\api\service\dingding\Sample;

class Test
{
    public function run()
    {

        //表格数据
        $data = [
            [
                'name' => '张三',
                'sexdesc' => '女',
                'type1' => '说明',
                'type2' => '2020-05-25 10:00~11:30',
                'type3' => '2020-05-25',
                'type4' => '2020-05-25',
                'type5' => '2020-05-25',
                'type6' => '2020-05-25',

               ],

        ];
        //循环制造数据
        for ($i=0; $i < 10; $i++) {
            $data[] = [
                'name' => '张三',
                'sexdesc' => '女',
                'type1' => '说明',
                'type2' => '2020-05-25 10:00~11:30',
                'type3' => '2020-05-25',
                'type4' => '2020-05-25',
                'type5' => '2020-05-25',
                'type6' => '2020-05-25',

               ];
        }
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => '[性别]：女100 男50 不详2',
            1 => '[类型]：说明8 说明16 ',
            2 => '[类型]：说明8 说明16 ',
        ];
        //表头信息
        $table_header = [
            0   =>  '序号',
            1   =>  '表头1',
            2   =>  '表头2',
            3   =>  '表头3',
            4   =>  '表头4',
            5   =>  '表头5',
            6   =>  '表头6',
            7   =>  '表头7',
            8   =>  '表头8'

        ];
        //每个格子的宽度，可根据数据长度自定义
        $field_width = [
            0   =>  '60',
            1   =>  '80',
            2   =>  '60',
            3   =>  '80',
            4   =>  '220',
            5   =>  '300',
            6   =>  '300',
            7   =>  '100',
            8   =>  '120'
        ];
        //参数
        $params = [
            'row' => count($data),          //数据的行数
            'file_name' => '数据_'.date("Ymd" , strtotime("+1 day")).'.png',      //保存的文件名
            'title' => date("Y-m-d")." 数据说明",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain'    => $table_explain,
            'table_header'    => $table_header,
            'field_width'    => $field_width,
            'file_path' =>  "./public/".date("Y-m-d")."/"  //文件保存路径
        ];

        $this->create_table($params);
    }
    
    public function getToken()
    {
        $model = new Sample();
        echo '<pre>';
        print_r($model->send());
//        print_r($model->main());
        die;
    }
}
