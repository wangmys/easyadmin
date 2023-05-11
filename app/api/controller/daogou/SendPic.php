<?php
declare (strict_types = 1);

namespace app\api\controller\daogou;
use app\api\constants\ApiConstant;
use app\api\service\bi\report\ReportFormsService;
use app\api\service\dingding\Sample;
use app\BaseController;
use think\Request;

class SendPic extends BaseController
{
    //2023 导购推送模板图片 晚上10点
    public function daogou_night() {


        $code = 'daogou_night';//如果每个店铺看到的都一样，则在这里填入店铺id即可
//        $sql = "select 性质,风格,一级分类,二级分类,采购入库数,仓库库存,仓库可用库存,仓库库存成本,收仓在途,收仓在途成本,已配未发,最后一周销,昨天销,累计销售,累销成本,在途库存数量,店库存数量,合计库存数,合计库存数占比,合计库存成本,数量售罄率,成本售罄率,前四周销量,前三周销量,前两周销量,前一周销量,周转周 from spring_report where 更新日期 = '$date'";
//        $data = Db::connect("bi")->Query($sql);
        //该店铺里所有导购销售业绩情况 数据
        $data = [
            ['name'=>'aa', 'liandai'=>'20%', 'jiandanjia'=>'100', 'today_finish'=>1000],
            ['name'=>'aa', 'liandai'=>'20%', 'jiandanjia'=>'100', 'today_finish'=>1000],
            ['name'=>'aa', 'liandai'=>'20%', 'jiandanjia'=>'100', 'today_finish'=>1000],
        ];
        //店铺本月排名、全国业绩排名 数据
        $store_month_sort = 2;//店铺本月排名
        $whole_country_sort = 15;//全国业绩排名
        $table_header = [''];
        $title = ['店铺本月排名', $store_month_sort, '全国业绩排名', $whole_country_sort];
        $table_header = array_merge($table_header, $title);
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }
        $field_width[0] = 0;
        $field_width[1] = 250;
        $field_width[2] = 150;
        $field_width[3] = 150;
        $field_width[4] = 150;
        //全国连带排名、全国件单排名、本月目标、实际完成多少 数据
        $whole_country_liandai_sort = '20';
        $whole_country_jiandan_sort = '22';
        $month_aim = '20000';
        $actual_finish = '13500';
        $table_data= [
            ['name'=>'全国连带排名', 'liandai'=>$whole_country_liandai_sort, 'jiandanjia'=>'全国件单排名', 'today_finish'=>$whole_country_jiandan_sort],
            ['name'=>'本月目标', 'liandai'=>$month_aim, 'jiandanjia'=>'实际完成多少', 'today_finish'=>$actual_finish],
            ['name'=>'姓名', 'liandai'=>'连带率', 'jiandanjia'=>'件单价', 'today_finish'=>'今日完成多少'],
        ];
        foreach ($data as $V=>$k){
            $new = [
                'name'=>$k['name'],
                'liandai'=>$k['liandai'],
                'jiandanjia'=>$k['jiandanjia'],
                'today_finish'=>$k['today_finish'],
            ];
            $table_data[]=$new;
        }
        //20号前每天需要做多少 数据
        $before_20_date = 1000;
        $table_data[] = ['name'=>'20号前每天需要做多少', 'liandai'=>$before_20_date, 'jiandanjia'=>'', 'today_finish'=>''];

        $table_explain = [
            0 => "导购推送"
        ];

        $params = [
            'row' => count($table_data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => '',//"数据更新时间 [". date("Y-m-d", strtotime("-1 day")) ."]- 2023 春季货品销售报表",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $table_data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '',//'图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/',  //文件保存路径
        ];

        $service = new ReportFormsService;
        $res = $service->table_pic_daogou_night($params, 0);


        //推送钉钉 ：
        $sample = new Sample();
        $users = [
            [
                'name' => '李友沛',
                'tel' => '13556122516',
                'userid' => '1344391026107390'
            ],
        ];
        $path = app()->getRootPath().'/public'.$params['file_path'].$params['file_name'];
//        echo $path;die;
        //上传图
        $media_id = $sample->uploadDingFile($path, "");//每日导购业绩{$date}
        foreach ($users as $val) {
            $res = $sample->sendImageMsg($val['userid'], $media_id);
        }


    }

    //2023 导购推送模板图片 早上9点
    public function daogou_morning() {

        $code = 'daogou_morning';//如果每个人看到的都不一样，则在这里填入每个导购的用户id即可
//        $sql = "select 性质,风格,一级分类,二级分类,采购入库数,仓库库存,仓库可用库存,仓库库存成本,收仓在途,收仓在途成本,已配未发,最后一周销,昨天销,累计销售,累销成本,在途库存数量,店库存数量,合计库存数,合计库存数占比,合计库存成本,数量售罄率,成本售罄率,前四周销量,前三周销量,前两周销量,前一周销量,周转周 from spring_report where 更新日期 = '$date'";
//        $data = Db::connect("bi")->Query($sql);
        $today_aim = '1500';//今日目标 数据
        $table_header = ['行号'];
        $title = ['今日目标', $today_aim];
        $table_header = array_merge($table_header, $title);
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }
        $field_width[0] = 0;
        $field_width[1] = 250;
        $field_width[2] = 150;
        //连带目标、件单价目标、 今日鞋子目标 数据
        $liandai_aim = '2.0';
        $jiandanjia_aim = '140';
        $today_shoe_aim = '2';
        $table_data= [
            ['title' => '连带目标', 'number' => $liandai_aim],
            ['title' => '件单价目标', 'number' => $jiandanjia_aim],
            ['title' => '今日鞋子目标', 'number' => $today_shoe_aim],
        ];

        $table_explain = [
            0 => "导购推送"
        ];

        $params = [
            'row' => count($table_data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => '',//"数据更新时间 [". date("Y-m-d", strtotime("-1 day")) ."]- 2023 春季货品销售报表",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $table_data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '',//'图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/',  //文件保存路径
        ];
        $service = new ReportFormsService;
        $res = $service->table_pic_daogou_night($params, 0, 0);

    }

    //2023 店长推送模板图片 晚上11点
    public function dianzhang_night() {

        $code = 'dianzhang_night';//如果每个店铺看到的都一样，则在这里填入店铺id即可
//        $sql = "select 性质,风格,一级分类,二级分类,采购入库数,仓库库存,仓库可用库存,仓库库存成本,收仓在途,收仓在途成本,已配未发,最后一周销,昨天销,累计销售,累销成本,在途库存数量,店库存数量,合计库存数,合计库存数占比,合计库存成本,数量售罄率,成本售罄率,前四周销量,前三周销量,前两周销量,前一周销量,周转周 from spring_report where 更新日期 = '$date'";
//        $data = Db::connect("bi")->Query($sql);
        //该店铺里所有导购销售业绩情况 数据
        $data = [
            ['name'=>'张三', 'liandai'=>'2.1', 'jiandanjia'=>'100', 'already_finish'=>'3000', 'today_finish'=>4000],
            ['name'=>'李四', 'liandai'=>'2', 'jiandanjia'=>'100', 'already_finish'=>'2000', 'today_finish'=>3000],
            ['name'=>'王五', 'liandai'=>'1.9', 'jiandanjia'=>'100', 'already_finish'=>'1000', 'today_finish'=>2000],
        ];
        //店铺全国排名、店铺实际完成 数据
        $store_whole_country_sort = 1;//店铺全国排名
        $store_actual_finish = 20000;//店铺实际完成
        $table_header = ['行号'];
        $title = ['店铺全国排名', $store_whole_country_sort, '店铺实际完成', $store_actual_finish, ''];
        $table_header = array_merge($table_header, $title);
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }
        $field_width[0] = 0;
        $field_width[1] = 250;
        $field_width[2] = 150;
        $field_width[3] = 150;
        $field_width[4] = 150;
        $field_width[5] = 150;
        //全国件单价排名、全国连带率排名 数据
        $whole_country_jiandanjia_sort = 20;//全国件单价排名
        $whole_country_liandai_sort = 15;//全国连带率排名
        $table_data= [
            ['name'=>'全国件单价排名', 'liandai'=>$whole_country_jiandanjia_sort, 'jiandanjia'=>'全国连带率排名', 'already_finish'=>$whole_country_liandai_sort, 'today_finish'=>''],
            ['name'=>'姓名', 'liandai'=>'连带率', 'jiandanjia'=>'件单价', 'already_finish'=>'完成多少', 'today_finish'=>'每天需要做多少'],
        ];
        foreach ($data as $V=>$k){
            $new = [
                'name'=>$k['name'],
                'liandai'=>$k['liandai'],
                'jiandanjia'=>$k['jiandanjia'],
                'already_finish'=>$k['already_finish'],
                'today_finish'=>$k['today_finish'],
            ];
            $table_data[]=$new;
        }

        $table_explain = [
            0 => "店长推送"
        ];

        $params = [
            'row' => count($table_data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => '',//"数据更新时间 [". date("Y-m-d", strtotime("-1 day")) ."]- 2023 春季货品销售报表",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $table_data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '',//'图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/',  //文件保存路径
        ];
        $service = new ReportFormsService;
        $res = $service->table_pic_daogou_night($params, 0, 0);

    }

    //2023 店长推送模板图片 早上9点
    public function dianzhang_morning() {

        $code = 'dianzhang_morning';//如果每个店长看到的都不一样，则在这里填入每个店长的用户id即可
//        $sql = "select 性质,风格,一级分类,二级分类,采购入库数,仓库库存,仓库可用库存,仓库库存成本,收仓在途,收仓在途成本,已配未发,最后一周销,昨天销,累计销售,累销成本,在途库存数量,店库存数量,合计库存数,合计库存数占比,合计库存成本,数量售罄率,成本售罄率,前四周销量,前三周销量,前两周销量,前一周销量,周转周 from spring_report where 更新日期 = '$date'";
//        $data = Db::connect("bi")->Query($sql);
        $today_store_aim = '10000';//今日店铺目标 数据
        $table_header = ['行号'];
        $title = ['今日店铺目标', $today_store_aim];
        $table_header = array_merge($table_header, $title);
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }
        $field_width[0] = 0;
        $field_width[1] = 250;
        $field_width[2] = 150;
        //店铺连带目标、店铺件单价目标、 今日鞋子目标、今日店长业绩目标 数据
        $store_liandai_aim = '2.0';
        $store_jiandanjia_aim = '140';
        $today_shoe_aim = '8';
        $today_dianzhang_aim = '1000';
        $table_data= [
            ['title' => '店铺连带目标', 'number' => $store_liandai_aim],
            ['title' => '店铺件单价目标', 'number' => $store_jiandanjia_aim],
            ['title' => '今日鞋子目标', 'number' => $today_shoe_aim],
            ['title' => '今日店长业绩目标', 'number' => $today_dianzhang_aim],
        ];

        $table_explain = [
            0 => "店长推送"
        ];

        $params = [
            'row' => count($table_data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => '',//"数据更新时间 [". date("Y-m-d", strtotime("-1 day")) ."]- 2023 春季货品销售报表",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $table_data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '',//'图片报表编号: '.$code,
            'file_path' => "./img/".date('Ymd').'/',  //文件保存路径
        ];
        $service = new ReportFormsService;
        $res = $service->table_pic_daogou_night($params, 0, 0);

    }


}
