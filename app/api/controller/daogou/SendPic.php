<?php
declare (strict_types = 1);

namespace app\api\controller\daogou;
use app\api\constants\ApiConstant;
use app\api\service\bi\report\ReportFormsService;
use app\api\service\dingding\Sample;
use app\BaseController;
use think\Request;
use think\facade\Db;

class SendPic extends BaseController
{
    //2023 导购推送模板图片 晚上10点
    public function daogou_night() {

        //所有店铺数据
        $sql = "select id, name, erp_shop_id from dept where del_flag=0 and is_virtual=0 and type=1";
        $data = Db::connect("cip")->Query($sql);

        $month_start = date('Y-m-01 00:00:00');
        $day_start = date('Y-m-d 00:00:00');
        $month_end = date('Y-m-d 23:59:59');
        $month_aim_date_start = date('Ym01');
        $month_aim_date_end = date('Ymd');

        if ($data) {
            foreach ($data as $v_data) {

                //该店铺对应的导购：
                $daogou_users_sql = "select u.id, u.erp_uid, u.real_name  from user u 
left join user_dept_relation udr on u.id=udr.user_id 
left join user_role_relations urr on u.id=urr.user_id 
left join role r on r.id=urr.role_id 
where dept_id='{$v_data['id']}' and u.erp_uid<>'' and u.state=0 "; // and r.name like '%导购%'
                $daogou_users_data = Db::connect("cip")->Query($daogou_users_sql);

                //该店铺所有导购今天的业绩
                $all_daogou_today_data = Db::connect("sqlsrv")->Query($this->return_daogou_sql($v_data['name'], $day_start, $month_end));
                //该店铺所有导购本月的业绩
                $all_daogou_month_data = Db::connect("sqlsrv")->Query($this->return_daogou_sql($v_data['name'], $month_start, $month_end));
                //全国导购本月的业绩 /连带/件单 排名
                $whole_country_month_data = Db::connect("sqlsrv")->Query($this->return_daogou_sql('', $month_start, $month_end, 1));

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

                if ($daogou_users_data) {
                    foreach ($daogou_users_data as $v_daogou) {

                        //每个店铺的每个导购业绩情况：
                        $sql = "";
                        $code = 'daogou_night'.$v_daogou['id'];//导购用户id区分

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
                        //每个导购目标值
                        //test....
//                        $month_aim_date_start = '20230401';
//                        $month_aim_date_end = '20230430';
                        $month_aim = $this->return_month_aim_sql($v_daogou['erp_uid'], $month_aim_date_start, $month_aim_date_end);//本月目标
                        $actual_finish = $this->return_sum($all_daogou_month_data, $v_daogou['erp_uid']);//实际完成多少
                        $table_data= [
                            ['name'=>'全国连带排名', 'liandai'=>$whole_country_liandai_sort, 'jiandanjia'=>'全国件单排名', 'today_finish'=>$whole_country_jiandan_sort],
                            ['name'=>'本月目标', 'liandai'=>$month_aim, 'jiandanjia'=>'实际完成多少', 'today_finish'=>$actual_finish],
                            ['name'=>'姓名', 'liandai'=>'连带率', 'jiandanjia'=>'件单价', 'today_finish'=>'今日完成多少'],
                        ];
                        foreach ($daogou_users_table_data as $kk=>$vv){
                            $table_data[] = $vv;
                        }
                        //20号前每天需要做多少 数据
                        $before_20_date = 1000;
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
                }

//                print_r($daogou_users_data);die;

            }
        }

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

    /**
     * 返回导购本月目标
     * @param $erp_uid
     * @param $start_time
     * @param $end_time
     * @return string
     */
    protected function return_month_aim_sql($erp_uid, $start_time, $end_time) {

        $sql = "SELECT
 userId,
 userName,
 userErpId,
 deptId,
 deptName,
 deptErpId,
 postId,
 postName,
 storeRelationId,
 SUM( originalTarget ) monthOriginalTarget,
 SUM( practicalTarget ) monthPracticalTarget 
FROM
 (
 SELECT
 u.id AS userId,
 u.real_name AS userName,
 u.erp_uid AS userErpId,
 d.id AS deptId,
 d.`name` AS deptName,
 d.erp_shop_id AS deptErpId,
 uspr.post_id AS postId,
 sp.post AS postName,
 sp.type AS storeRelationId,
 CASE
 sp.type 
 WHEN 3 THEN
 '店长' 
 WHEN 2 THEN
 '店助' ELSE '导购' 
 END AS storeRelationName,
 IFNULL( sdot.old_amount, 0 ) AS originalTarget,
 IFNULL( sdot.amount, 0 ) AS practicalTarget 
 FROM
 `user` u
 LEFT JOIN user_salary_post_relation uspr ON u.id = uspr.user_id
 LEFT JOIN salary_post sp ON uspr.post_id = sp.id
 LEFT JOIN user_dept_relation udr ON u.id = udr.user_id
 LEFT JOIN dept d ON d.id = udr.dept_id
 LEFT JOIN sales_day_object_target sdot ON sdot.for_id = u.id 
 AND sdot.type = 0 
 AND sdot.for_type = 1 
 WHERE
 u.state = 0 
 AND u.is_virtual = 0 
 AND uspr.post_id IS NOT NULL 
 AND d.sale_quality = 'ZY' 
 AND d.del_flag = 0 
 AND d.is_virtual = 0 
 AND d.type = 1 
 AND u.erp_uid = '{$erp_uid}' 
 AND sdot.date between '{$start_time}' and '{$end_time}'
 ) z 
GROUP BY
 userId,
 userName,
 userErpId,
 deptId,
 deptName,
 deptErpId,
 postId,
 postName,
 storeRelationId
";
        $res = Db::connect("cip")->Query($sql);
        return $res ? $res[0]['monthPracticalTarget'] : [];

    }

    protected function return_daogou_sql($store_name, $start_time, $end_time, $if_whole_country = 0, $order_by = 'sum') {

        $if_whole_country_str = '';
        if ($if_whole_country) {
            $if_whole_country_str = "";
        } else {
            $if_whole_country_str = "AND EC.CustomerName='{$store_name}'";
        }
        return "SELECT 
	T.CustomerCode,
	T.CustomerName,
	T.SalesmanName as Name,
	T.SalesmanID,
	T.CustomItem19,
	SUM(T.[销售数量]) AS quantity,
	SUM(T.[单数]) AS count,
	SUM(T.[销售业绩]) 五大类业绩,
	SUM(T.[总销售业绩]) AS sum,
	CASE WHEN SUM(T.[销售数量])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售业绩])/SUM(T.[销售数量])) END  AS jd,
	CASE WHEN SUM(T.[单数])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售业绩])/SUM(T.[单数])) END  AS kd,
	CASE WHEN SUM(T.[单数])=0 THEN NULL ELSE CONVERT(DECIMAL(10,2),SUM(T.[销售数量])/SUM(T.[单数])) END AS ld
FROM 
(
SELECT  
	EC.CustomerCode,
	EC.CustomerName,
	ERG.SalesmanName,
	ERG.SalesmanID,
	ER.RetailID,
	EC.CustomItem19,
	SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END ) AS 销售数量,
	SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity*ERG.DiscountPrice ELSE 0 END ) AS 销售业绩,
	SUM(ERG.Quantity*ERG.DiscountPrice) AS 总销售业绩,
	CASE WHEN SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END )>0 THEN 1 WHEN SUM(ERG.Quantity)<0 THEN -1 ELSE 0 END AS 单数
FROM ErpRetail ER
LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
WHERE EC.ShutOut=0
	AND ER.CodingCodeText='已审结'
	{$if_whole_country_str}
	AND ER.RetailDate BETWEEN '{$start_time}'  AND '{$end_time}'
	AND ER.RetailID NOT IN (SELECT ER.RetailID FROM ErpRetail ER  LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID WHERE ERG.Status='退'  	AND ER.RetailDate BETWEEN '{$start_time}'  AND '{$end_time}' GROUP BY ER.RetailID )
	AND ERG.Status!='赠'
GROUP BY 
	ERG.SalesmanName,
	ERG.SalesmanID,
	EC.CustomerCode,
	EC.CustomerName,
EC.CustomItem19,
	ER.RetailID
) T
GROUP BY 
	T.CustomerCode,
	T.SalesmanName,
	T.SalesmanID,
	T.CustomerName,
	T.CustomItem19
ORDER BY 
	{$order_by} desc;";

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
