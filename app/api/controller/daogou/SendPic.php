<?php
declare (strict_types = 1);

namespace app\api\controller\daogou;
use app\api\constants\ApiConstant;
use app\api\service\bi\report\ReportFormsService;
use app\api\service\dingding\Sample;
use app\api\service\dingding\SendpicService;
use app\BaseController;
use think\Request;
use think\facade\Db;

class SendPic extends BaseController
{
    protected $request;
    protected $service;
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->service = new SendpicService();
    }

    //2023 导购推送模板图片 晚上10点
    public function daogou_night() {

        $v_data = $this->request->param();

        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        $day_start = date('Y-m-d 00:00:00');
        $day_end = date('Y-m-d 23:59:59');
        $month_aim_date_start = date('Ym01');
        $month_aim_date_end = date("Ymt");
        $day_aim_date_start = date('Ymd');

        //该店铺对应的导购：
        $daogou_users_data = $this->service->get_daogou_users($v_data['id']);
        // print_r($daogou_users_data);die;

        //该店铺所有导购今天的业绩
        $all_daogou_today_data = $this->service->get_achievement($v_data['erp_shop_id'], $day_start, $day_end);
        //该店铺所有导购本月的业绩 (截至当前时间为止)
        $all_daogou_month_data = $this->service->get_achievement($v_data['erp_shop_id'], $month_start, $day_end);
        //全国导购本月的业绩 /连带/件单 排名 (截至当前时间为止)
        $whole_country_month_data = $this->service->get_achievement('', $month_start, $day_end, 1);
        //店铺日目标：
        $dept_aim = $this->service->get_dept_aim($day_aim_date_start, $day_aim_date_start, $v_data['id']);

        //该店铺里所有导购本月销售业绩情况 数据
        $daogou_users_table_data = [];

        //今日有业绩的人员，排前面去
        if ($all_daogou_today_data) {
            foreach ($all_daogou_today_data as $v_all_daogou_today_data) {
                $daogou_users_table_data[] = ['name'=>$v_all_daogou_today_data['Name'], 'liandai'=>$v_all_daogou_today_data['ld'].'%', 'jiandanjia'=>$v_all_daogou_today_data['jd'], 'today_finish'=>$v_all_daogou_today_data['sum']];
            }
        }
        //今日无业绩的人员处理，排后面去
        if ($daogou_users_data) {
            foreach ($daogou_users_data as $v_daogou_users_data) {
                $sign = false;
                foreach ($all_daogou_today_data as $v_all_daogou_today_data) {
                    if ($v_daogou_users_data['erp_uid'] == $v_all_daogou_today_data['SalesmanID']) {
                        $sign = true;
                    }
                }
                if ($sign == false) {
                    $daogou_users_table_data[] = ['name'=>$v_daogou_users_data['real_name'], 'liandai'=>'0%', 'jiandanjia'=>0, 'today_finish'=>0];
                }
            }
        }

        //导购-待发送数组
        $wait_send_arr = [];

        if ($daogou_users_data) {
            foreach ($daogou_users_data as $v_daogou) {

                //每个店铺的每个导购业绩情况：
                $sql = "";
                $code = 'daogou_night'.$v_daogou['erp_uid'];//导购用户id区分

                //店铺本月排名、全国业绩排名 数据
                $store_month_sort = $this->return_sort($all_daogou_month_data, $v_daogou['erp_uid']);//店铺本月排名
                $whole_country_sort = $this->return_sort($whole_country_month_data, $v_daogou['erp_uid']);//sort_arr($arr, 'sum');//全国业绩排名
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
                $whole_country_liandai_sort = $this->return_sort(sort_arr($whole_country_month_data, 'ld'), $v_daogou['erp_uid']);//sort_arr($arr, 'sum');//全国连带排名
                $whole_country_jiandan_sort = $this->return_sort(sort_arr($whole_country_month_data, 'jd'), $v_daogou['erp_uid']);//sort_arr($arr, 'sum');//全国件单排名
                $month_aim = $this->service->return_month_aim_sql($v_daogou['erp_uid'], $month_aim_date_start, $month_aim_date_end);//本月目标
                $actual_finish = $this->return_sum($all_daogou_month_data, $v_daogou['erp_uid']);//实际完成多少
                $table_data= [
                    ['name'=>'全国连带排名', 'liandai'=>$whole_country_liandai_sort, 'jiandanjia'=>'全国件单排名', 'today_finish'=>$whole_country_jiandan_sort],
                    ['name'=>'本月目标', 'liandai'=>$month_aim, 'jiandanjia'=>'实际完成多少', 'today_finish'=>$actual_finish],
                    ['name'=>'姓名', 'liandai'=>'连带率', 'jiandanjia'=>'件单价', 'today_finish'=>'今日完成多少'],
                ];
                foreach ($daogou_users_table_data as $kk=>$vv) {
                    $table_data[] = $vv;
                }
                //20号前每天需要做多少 数据
                $before_20_date = $this->return_20_day_aim($month_aim, $actual_finish, $dept_aim, $day_end, count($daogou_users_data));
                $table_data[] = ['name'=>'20号前每天需要做多少', 'liandai'=>$before_20_date, 'jiandanjia'=>'', 'today_finish'=>''];

                $table_explain = [
                    0 => "导购-".$v_daogou['real_name']//.date('Y年m月d日 H时')
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

                $wait_send_arr[] = ['img_url'=>$res, 'userid'=>$v_daogou['checkin_sys_uid']];

            }
        }

        echo json_encode($wait_send_arr);die;

    }


    protected function return_20_day_aim($month_aim_total, $actual_finish, $dept_aim, $current_date, $daogou_users) {

        $every_day = 0;
        if ($month_aim_total) {
            $current_day = date('d', strtotime($current_date));
            if ($current_day <= 20) {//20号前
                if ($current_day == 20) {
                    $every_day = round( $month_aim_total-$actual_finish , 2);
                } else {
                    //没完成目标
                    if ($actual_finish < $month_aim_total) {
                        $every_day = round( ($month_aim_total-$actual_finish) / (20-$current_day+1), 2 );
                    } else {//完成目标的情况
                        $every_day = round($dept_aim/$daogou_users, 2);
                    }
                }
            } else {//20号后
                //没完成目标
                if ($actual_finish < $month_aim_total) {
                    $last_day = date('t', strtotime( $current_date ));
                    $every_day = round( ($month_aim_total-$actual_finish) / ($last_day-$current_day), 2 );
                } else {//完成目标的情况
                    $every_day = round($dept_aim/$daogou_users, 2);
                }
            }
        }
        return $every_day;

    }

    /**
     * 返回排名
     * @param $all_daogou_month_data
     * @param $erp_uid
     * @param $param
     * @return int|mixed|string
     */
    protected function return_sort($all_daogou_month_data, $erp_uid, $param = 'SalesmanID') {

        $sort = 1;
        if ($all_daogou_month_data) {
            $sign = false;
            foreach ($all_daogou_month_data as $k=>$v) {
                if ($v[$param] == $erp_uid) {
                    $sign = true;
                    $sort = $sort+$k;
                }
            }
            if ($sign == false) {
                $sort = count($all_daogou_month_data)+1;
            }
        }
        return $sort;

    }

    /**
     * 返回该店铺个人业绩
     * @param $all_daogou_month_data
     * @param $erp_uid
     * @param $param
     * @return int
     */
    protected function return_sum($all_daogou_month_data, $erp_uid, $param='SalesmanID') {

        $sum = 0;
        foreach ($all_daogou_month_data as $v_data) {
            if ($v_data[$param] == $erp_uid) $sum = $v_data['sum'];
        }
        return $sum;

    }

    //2023 导购推送模板图片 早上9点
    public function daogou_morning() {

        $v_data = $this->request->param();

        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        $day_start = date('Y-m-d 00:00:00');
        $day_end = date('Y-m-d 23:59:59');
        $month_aim_date_start = date('Ym01');
        $month_aim_date_end = date("Ymt");
        $day_aim_date_start = date('Ymd');

        //该店铺对应的导购：
        $daogou_users_data = $this->service->get_daogou_users($v_data['id']);

        //导购-待发送数组
        $wait_send_arr = [];
        if ($daogou_users_data) {
            foreach ($daogou_users_data as $v_daogou) {

                $month_aim = $this->service->return_month_aim_sql($v_daogou['erp_uid'], $day_aim_date_start, $day_aim_date_start);//今日目标
                // print_r($month_aim);die;

                $code = 'daogou_morning'.$v_daogou['erp_uid'];//如果每个人看到的都不一样，则在这里填入每个导购的用户id即可
                $today_aim = $month_aim;//今日目标 数据
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
                    0 => "导购-".$v_daogou['real_name']
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

                $wait_send_arr[] = ['img_url'=>$res, 'userid'=>$v_daogou['checkin_sys_uid']];

            }
        }

        echo json_encode($wait_send_arr);die;

    }

    //2023 店长推送模板图片 晚上11点
    public function dianzhang_night() {

        $v_data = $this->request->param();

        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        $day_start = date('Y-m-d 00:00:00');
        $day_end = date('Y-m-d 23:59:59');
        $month_aim_date_start = date('Ym01');
        $month_aim_date_end = date("Ymt");
        $day_aim_date_start = date('Ymd');

        //店铺日目标：
        $dept_aim = $this->service->get_dept_aim($day_aim_date_start, $day_aim_date_start, $v_data['id']);
        // print_r($dept_aim);die;

        //该店铺所有导购本月的业绩 (截至当前时间为止)
        $all_daogou_month_data = $this->service->get_achievement($v_data['erp_shop_id'], $month_start, $day_end);

        //所有店铺今天的业绩
        $all_customers_retail = $this->service->return_customer_retail($day_start, $day_end);

        $code = 'dianzhang_night'.$v_data['erp_shop_id'];

        //该店铺所有导购今天的业绩
        $all_daogou_today_data = $this->service->get_achievement($v_data['erp_shop_id'], $day_start, $day_end);

        //获取店长推送信息
        $dianzhang_info = [];

        $data = []; //该店铺里所有导购销售业绩情况 数据
        if ($all_daogou_today_data) {
            $dianzhang_info = $this->service->get_dianzhang_info($all_daogou_today_data[0]['CustomItem19']);
            //每天需要做多少数据 (按20号前规则来)
            foreach ($all_daogou_today_data as $v_each_daogou) {
                
                $month_aim = $this->service->return_month_aim_sql($v_each_daogou['SalesmanID'], $month_aim_date_start, $month_aim_date_end);//本月目标
                
                $actual_finish = $this->return_sum($all_daogou_month_data, $v_each_daogou['SalesmanID']);//实际完成多少

                //20号前每天需要做多少 数据
                $before_20_date = $this->return_20_day_aim($month_aim, $actual_finish, $dept_aim, $day_end, count($all_daogou_today_data));

                $data[] = ['name'=>$v_each_daogou['Name'], 'liandai'=>$v_each_daogou['ld'], 'jiandanjia'=>$v_each_daogou['jd'], 'already_finish'=>$v_each_daogou['sum'], 'today_finish'=>$before_20_date];

            }
        }

        //店铺全国排名、店铺实际完成 数据
        $store_whole_country_sort = $this->return_sort($all_customers_retail, $v_data['erp_shop_id'], 'CustomerId');//店铺全国排名
        $store_actual_finish = $this->return_sum($all_customers_retail, $v_data['erp_shop_id'], 'CustomerId');//店铺实际完成

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
        $whole_country_jiandanjia_sort = $this->return_sort(sort_arr($all_customers_retail, 'jd'), $v_data['erp_shop_id'], 'CustomerId');//全国件单价排名
        $whole_country_liandai_sort = $this->return_sort(sort_arr($all_customers_retail, 'ld'), $v_data['erp_shop_id'], 'CustomerId');;//全国连带率排名
        $table_data= [
            ['name'=>'全国件单价排名', 'liandai'=>$whole_country_jiandanjia_sort, 'jiandanjia'=>'全国连带率排名', 'already_finish'=>$whole_country_liandai_sort, 'today_finish'=>''],
            ['name'=>'姓名', 'liandai'=>'连带率', 'jiandanjia'=>'件单价', 'already_finish'=>'完成多少', 'today_finish'=>'每天需要做多少'],
        ];
        if ($data) {
            foreach ($data as $k=>$v){
                $table_data[]=$v;
            }
        }

        $table_explain = [
            0 => "店长-".($dianzhang_info ? $dianzhang_info[0]['real_name'] : '')
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

        $wait_send_arr = [];
        if ($dianzhang_info) {
            $service = new ReportFormsService;
            $res = $service->table_pic_daogou_night($params, 0, 0);
            $wait_send_arr[] = ['img_url'=>$res, 'userid'=>($dianzhang_info ? $dianzhang_info[0]['checkin_sys_uid'] : '')];
        }

        echo json_encode($wait_send_arr);die;

    }

    //2023 店长推送模板图片 早上9点
    public function dianzhang_morning() {

        $v_data = $this->request->param();

        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        $day_start = date('Y-m-d 00:00:00');
        $day_end = date('Y-m-d 23:59:59');
        $month_aim_date_start = date('Ym01');
        $month_aim_date_end = date("Ymt");
        $day_aim_date_start = date('Ymd');

        $code = 'dianzhang_morning'.$v_data['erp_shop_id'];
        $today_store_aim = $this->service->get_dept_aim($day_aim_date_start, $day_aim_date_start, $v_data['id']);//今日店铺目标 数据
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
        $cus_info = $this->service->get_customer_info($v_data['erp_shop_id'], 'State,CustomItem19');
        $dianzhang_info = [];
        if ($cus_info) {
            $dianzhang_info = $this->service->get_dianzhang_info($cus_info[0]['CustomItem19']);;
        }
        // print_r($dianzhang_info);die;
        $province = $cus_info ? $cus_info[0]['State'] : '';
        $province = $province ? mb_substr($province, 0, 2) : '';
        $store_liandai_aim = '0.0';
        $store_jiandanjia_aim = '0';
        if (isset(config('sendpic')[$province])) {
            $store_liandai_aim = config('sendpic')[$province]['ld'];
            $store_jiandanjia_aim = config('sendpic')[$province]['jd'];
        }
        // print_r([$store_liandai_aim, $store_jiandanjia_aim]);die;
        $today_shoe_aim = '8';
        $today_dianzhang_aim = '2000';
        $table_data= [
            ['title' => '店铺连带目标', 'number' => $store_liandai_aim],
            ['title' => '店铺件单价目标', 'number' => $store_jiandanjia_aim],
            ['title' => '今日鞋子目标', 'number' => $today_shoe_aim],
            ['title' => '今日店长业绩目标', 'number' => $today_dianzhang_aim],
        ];

        $table_explain = [
            0 => "店长-".($dianzhang_info ? $dianzhang_info[0]['real_name'] : '')
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

        $wait_send_arr = [];
        if ($dianzhang_info) {
            $service = new ReportFormsService;
            $res = $service->table_pic_daogou_night($params, 0, 0);
            $wait_send_arr[] = ['img_url'=>$res, 'userid'=>($dianzhang_info ? $dianzhang_info[0]['checkin_sys_uid'] : '')];
        }

        echo json_encode($wait_send_arr);die;

    }


}
