<?php


namespace app\api\service\bi\report;

use app\admin\model\dress\Yinliu;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use think\App;
use think\facade\Db;
use think\cache\driver\Redis;

/**
 * 引流配饰数据拉取服务
 * Class AuthService
 * @package app\common\service
 */
class ReportFormsServicePro
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [];

    protected $code = 0;
    protected $msg = '';

    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';

    public function __construct()
    {
        $this->model = new Yinliu();
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }

    public function create_table_s101($code = 'S101', $date = '')
    {
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        switch ($code) {
            case 'S101':
                // $sql = "select 经营模式,省份,店铺名称,首单日期 as 开店日期,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('加盟','加盟合计')";
                $title = "加盟老店业绩同比 [" . date("Y-m-d",  strtotime($date . '-1day')) . ']';
                $jingyingmoshi = '【加盟】';
                $sql = "
                SELECT
                    省份,店铺名称,
                    前年对比今年昨日递增率 AS 前年日增长,
                    昨日递增率 AS 去年日增长,
                    前年对比今年累销递增率 AS 前年月增长,
                    累销递增率 AS 去年月增长,
                    前年同日 as 前年同日销额,
                    去年同日 as 去年同日销额,
                    昨天销量 as 昨天销额,
                    前年同月 as 前年同月销额,
                    去年同月 as 去年同月销额,
                    本月业绩 as 本月销额
                    from old_customer_state_detail_ww where 更新时间 = '$date' and  经营模式 in ('加盟','加盟合计')";
                break;
            default:
                $title = "直营老店业绩同比 [" . date("Y-m-d",  strtotime($date . '-1day')) . ']';
                $jingyingmoshi = '【直营】';
                // $sql = "select 经营模式,省份,店铺名称,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,
                // 昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,
                // 累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";
                $sql = "select
                    省份,店铺名称,
                    前年对比今年昨日递增率 AS 前年日增长,
                    昨日递增率 AS 去年日增长,
                    前年对比今年累销递增率 AS 前年月增长,
                    累销递增率 AS 去年月增长,
                    前年同日 as 前年同日销额,
                    去年同日 as 去年同日销额,
                    昨天销量 AS 昨天销额,
                    前年同月 as 前年同月销额,
                    去年同月 AS 去年同月销额,
                    本月业绩 as 本月销额
                    from old_customer_state_detail_ww where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";
                break;
        }
        $data = Db::connect("mysql2")->query($sql);
        if ($data) {
            // echo '<pre>';
            // print_r($data);
            foreach ($data as $key => $val) {
                $data[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $table_header = array_merge($table_header, array_keys($data[0]));
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 130;
            }

            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 90;
            $field_width[3] = 90;
            $field_width[4] = 90;
            $field_width[5] = 90;
            $field_width[6] = 90;
            $field_width[7] = 100;
            $field_width[8] = 100;
            $field_width[9] = 90;
            $field_width[10] = 100;
            $field_width[11] = 100;
            $field_width[12] = 90;
            // $field_width[13] = 150;
            // $field_width[14] = 120;
            // $field_width[15] = 90;

            // $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空

            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "{$jingyingmoshi} 今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
            ];
            //参数
            $params = [
                'row' => count($data),          //数据的行数
                // 'file_name' =>  $code . $dingName . '.jpg',      //保存的文件名
                'file_name' =>  $code . '.jpg',      //保存的文件名
                'title' => $title,
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $data,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
            // return $this->create_image_bgcolor($params, [
            //     '前年日增长' => 3,
            //     '去年日增长' => 4,
            //     '前年月增长' => 5,
            //     '去年月增长' => 6,
            // ]);
                // 生成图片
                return $this->create_image_bgcolor($params,
                [
                    // '前年日增长' => 3,
                    // '去年日增长' => 4,
                    // '前年月增长' => 5,
                    // '去年月增长' => 6,
                ]
            );
        }
    }

    public function create_table_s102($date = '')
    {
        // 编号
        $code = 'S102';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        // $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,省份,前年同日,去年同日,
        // 昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,
        // 去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,
        // 累销递增金额差 from old_customer_state_2 where 更新时间 = '$date'";
        $sql = "select
            省份,
            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长,
            前年同日 as 前年同日销额,
            去年同日 as 去年同日销额,
            昨天销量 AS 昨天销额,
            前年同月 as 前年同月销额,
            去年同月 AS 去年同月销额,
            本月业绩 as 本月销额
            from old_customer_state_2_ww where 更新时间 = '$date'";


        $list = Db::connect("mysql2")->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 90;
            }
            $field_width[0] = 35;
            $field_width[1] = 45;
    
            $field_width[6] = 100;
            $field_width[7] = 100;
            $field_width[9] = 100;
            $field_width[10] = 100;
    
    
            // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
            ];
    
            //参数
            $params = [
                'row' => count($list),          //数据的行数
                // 'file_name' => $code . $dingName  .'.jpg',   //保存的文件名
                'file_name' => $code .'.jpg',   //保存的文件名
                'title' =>  "省份老店业绩同比 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
            // 防止多次创建
            //        $file = app()->getRootPath().'public/'.$params['file_path'].$params['file_name'];
            //        if(file_exists($file)){
            //            echo "<img src='/{$params['file_path']}{$params['file_name']}' />";return;
            //        }
            // 生成图片
            return $this->create_image_bgcolor($params, [
                // '前年日增长' => 2,
                // '去年日增长' => 3,
                // '前年月增长' => 4,
                // '去年月增长' => 5,
            ]);
        }
    }

    public function create_table_s103($date = '')
    {
        // 编号
        $code = 'S103';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        // $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,经营模式,省份,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,累销递增金额差 from old_customer_state  where 更新时间 = '$date'";
        $sql = "select
            经营模式 as 经营,
            省份,

            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长,
            前年同日 as 前年同日销额,
            去年同日 as 去年同日销额,
            昨天销量 AS 昨天销额,
            前年同月 as 前年同月销额,
            去年同月 AS 去年同月销额,
            本月业绩 as 本月销额

            from old_customer_state_ww  where 更新时间 = '$date'";
        $list = Db::connect("mysql2")->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 90;
            }
            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 45;
            $field_width[7] = 100;
            $field_width[8] = 100;
    
    
            $field_width[10] = 100;
            $field_width[11] = 100;
    
            // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "今日:" . $week . " 去年今日:" . $last_year_week_today . " 前年今日:" . $the_year_week_today,
            ];
    
            //参数
            $params = [
                'code' => $code,
                'row' => count($list),          //数据的行数
                // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "省份老店业绩同比-分经营模式 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
            // 防止多次创建
            //        $file = app()->getRootPath().'public/'.$params['file_path'].$params['file_name'];
            //        if(file_exists($file)){
            //            echo "<img src='/{$params['file_path']}{$params['file_name']}' />";return;
            //        }
            // 生成图片
            return $this->create_image_bgcolor($params, [
                // '前年日增长' => 3,
                // '去年日增长' => 4,
                // '前年月增长' => 5,
                // '去年月增长' => 6,
            ]);
        }
    }

    // 加盟
    public function create_table_s103B($date = '')
    {
        // 编号
        $code = 'S103B';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        // $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,经营模式,省份,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,累销递增金额差 from old_customer_state  where 更新时间 = '$date'";
        $sql = "select
            省份,
            两年以上老店数 AS 前年店数,
            店铺数 AS 去年店数,
            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长,
            前年同日 as 前年同日销额,
            去年同日 as 去年同日销额,
            昨天销量 AS 昨天销额,
            前年同月 as 前年同月销额,
            去年同月 AS 去年同月销额,
            本月业绩 as 本月销额,
            前年累销递增金额差,
            累销递增金额差
            from old_customer_state  where 更新时间 = '$date' and 经营模式='加盟'";
        $list = Db::connect("mysql2")->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 90;
            }
            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 75;
            $field_width[3] = 75;
            $field_width[4] = 100;
            $field_width[9] = 100;
    
    
            $field_width[11] = 100;
            $field_width[12] = 100;
            $field_width[14] = 150;
            $field_width[15] = 120;
    
            // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => "【加盟】 今日:" . $week . "  .  去年今日:" . $last_year_week_today . "  .  前年今日:" . $the_year_week_today,
            ];
    
            //参数
            $params = [
                'row' => count($list),          //数据的行数
                // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "省份老店业绩同比-加盟 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '图片报表编号: ' . $code,
                'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        }
    }

    // 直营ll
    public function create_table_s103C($date = '')
    {
        // 编号
        $code = 'S103C';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        // $sql = "select 店铺数 as 22店数,两年以上老店数 as 21店数,经营模式,省份,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增率,前年累销递增金额差,累销递增金额差 from old_customer_state  where 更新时间 = '$date'";
        $sql = "select
            省份,
            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长
            from old_customer_state_ww  where 更新时间 = '$date' and 经营模式='直营'";
        $list = Db::connect("mysql2")->query($sql);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $field_width = [];
            $table_header = array_merge($table_header, array_keys($list[0]));
            foreach ($table_header as $v => $k) {
                $field_width[] = 100;
            }
            $field_width[0] = 60;
            // $field_width[1] = 75;
            // $field_width[2] = 100;
            // $field_width[3] = 100;
    
    
    
            // $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => " "
            ];
    
            //参数
            $params = [
                'row' => count($list),          //数据的行数
                // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
                'file_name' => $code . '.jpg',   //保存的文件名
                'title' => "省份老店业绩同比-直营 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $list,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '                 ' . $code,
                'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];
    
            // 生成图片
            return $this->create_image($params);
        }
    }

    // 直营
    public function create_table_s104C($date = '')
    {
        $code = 'S104C';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');

        $title = "直营老店业绩同比 [" . date("Y-m-d",  strtotime($date . '-1day')) . ']';
        $jingyingmoshi = '【直营】';
        // $sql = "select 经营模式,省份,店铺名称,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,
        // 昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,
        // 累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";
        $sql = "select
            省份,店铺名称,
            前年对比今年昨日递增率 AS 前年日增长,
            昨日递增率 AS 去年日增长,
            前年对比今年累销递增率 AS 前年月增长,
            累销递增率 AS 去年月增长
            from old_customer_state_detail_ww where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";

        $data = Db::connect("mysql2")->query($sql);
        if ($data) {
            // echo '<pre>';
            // print_r($data);
            foreach ($data as $key => $val) {
                $data[$key]['省份'] = province2zi($val['省份']);
            }
            $table_header = ['ID'];
            $table_header = array_merge($table_header, array_keys($data[0]));
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 90;
            }

            $field_width[0] = 35;
            $field_width[1] = 45;
            $field_width[2] = 90;
            $field_width[3] = 90;

            // $field_width[13] = 150;
            // $field_width[14] = 120;
            // $field_width[15] = 90;

            // $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空

            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => " ",
            ];
            //参数
            $params = [
                'row' => count($data),          //数据的行数
                // 'file_name' =>  $code . $dingName . '.jpg',      //保存的文件名
                'file_name' =>  $code . '.jpg',      //保存的文件名
                'title' => $title,
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $data,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '                 ' . $code,
                'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];

            // 生成图片
            return $this->create_image_bgcolor($params,
                [
                    // '前年日增长' => 3,
                    // '去年日增长' => 4,
                    // '前年月增长' => 5,
                    // '去年月增长' => 6,
                ]
            );
        }
    }

    // 直营
    public function create_table_s104Z($date = '')
    {
        $code = 'S104Z';
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');

        $title = "下水道店业绩同比 [" . date("Y-m-d",  strtotime($date . '-1day')) . ']';

        // $sql = "select 经营模式,省份,店铺名称,前年同日,去年同日,昨天销量 as 昨日销额,前年对比今年昨日递增率 as 前年昨日递增率,
        // 昨日递增率,前年同月,去年同月,本月业绩,前年对比今年累销递增率 as 前年累销递增率,累销递增金额差,前年累销递增金额差,
        // 累销递增金额差 from old_customer_state_detail where 更新时间 = '$date' and  经营模式 in ('直营','直营合计')";
        $sql = "
            SELECT
                店铺名称,
                concat(round(今日达成率 * 100, 1), '%') as 今日达成率,
                concat(round(本月达成率 * 100, 1), '%') as 本月达成率,
                昨日递增率 AS `22年日同比`,
                前年对比今年昨日递增率 AS `21年日同比`,
                累销递增率 AS `22年月累同比`,
                前年对比今年累销递增率 AS `21年月累同比`,
                昨天销量 as 今日流水,
                今日目标,
                本月业绩 as 本月流水,
                本月目标
            from xiashui_old_customer_state_detail_ww where 更新时间 = '{$date}'
        ";

        $data = Db::connect("mysql2")->query($sql);
        if ($data) {
            $table_header = ['ID'];
            $table_header = array_merge($table_header, array_keys($data[0]));
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 100;
            }

            $field_width[0] = 45;
            // $field_width[1] = 80;
            // $field_width[2] = 90;
            // $field_width[3] = 90;

            // $field_width[13] = 150;
            // $field_width[14] = 120;
            // $field_width[15] = 90;

            // $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
            $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -0 day")));
            // $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
            $week =  date_to_week(date("Y-m-d", strtotime("-0 day")));
            // $the_year_week_today =  date_to_week( date("Y-m-d", strtotime("-2 year -1 day")));
            $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -0 day")));
            //图片左上角汇总说明数据，可为空

            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => " ",
            ];
            //参数
            $params = [
                'code' => $code,
                'row' => count($data),          //数据的行数
                // 'file_name' =>  $code . $dingName . '.jpg',      //保存的文件名
                'file_name' =>  $code . '.jpg',      //保存的文件名
                'title' => $title,
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $data,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '                 ' . $code,
                'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
            ];

            // 生成图片
            return $this->create_image_bgcolor($params,
                [
                    // '前年日增长' => 3,
                    // '去年日增长' => 4,
                    // '前年月增长' => 5,
                    // '去年月增长' => 6,
                ]
            );
        }
    }

    // s108 督导挑战目标
    public function create_table_s108A($date = '')
    {
        // 编号
        $code = 'S108A';
        // $date = $date ?: date('Y-m-d');
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');

        $sql2_old = "
            select * from (
                SELECT
                IFNULL(SCL.`经营模式`,'总计') AS 经营模式,
                IFNULL(SCL.`督导`,'合计') AS 督导,
                IFNULL(SCL.`省份`,'合计') AS 省份,
                SUM(SCM.`今日目标`) AS 今日目标,
                SUM(SCL.`今天流水`) AS 今天流水,
                CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
                SUM(SCM.`本月目标`) 本月目标,
                SUM(SCL.`本月流水`) 本月流水,
                CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
                SUM(SCL.`近七天日均`) AS 近七天日均流水,
                ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  DATEDIFF(LAST_DAY(CURDATE()),CURDATE()),2) AS 剩余目标日均
                FROM sp_customer_liushui SCL
                LEFT JOIN sp_customer_mubiao SCM ON SCL.`店铺名称`=SCM.`店铺名称`
                GROUP BY
                SCL.`经营模式`,
                SCL.`督导`,
                SCL.`省份`
                WITH ROLLUP
            ) as aa order by 经营模式 desc
        ";

        $sql3 = "
            SELECT
            IFNULL(SCL.`督导`,'总计') AS 督导,
            IFNULL(SCL.`省份`,'合计') AS 省份,
            CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
            SUM(SCM.`今日目标`) AS 今日目标,
            SUM(SCL.`今天流水`) AS 今天流水,
            SUM(SCM.`本月目标`) 本月目标,
            SUM(SCL.`本月流水`) 本月流水,
            SUM(SCL.`近七天日均`) AS 近七天日均,
            ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  (DATEDIFF(LAST_DAY(CURDATE()),CURDATE()) ), 2)  AS 剩余目标日均
            FROM sp_customer_liushui_ww SCL
            LEFT JOIN sp_customer_mubiao_ww SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            where SCL.`经营模式`='直营'
            GROUP BY
            SCL.`督导`,
            SCL.`省份`
            WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 150;
        $field_width[2] = 45;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 80;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 95;
        $field_width[9] = 95;
        $field_width[10] = 100;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            0 => "【直营】"
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($list),          //数据的行数
            // 'file_name' => $code .$dingName . '.jpg',   //保存的文件名
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "督导挑战目标完成率 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            // 'banben' => '',
            'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 3,
            '本月达成率' => 4,
        ]);
    }

    // s108 督导挑战目标
    public function create_table_s108B($date = '')
    {
        // 编号
        $code = 'S108B';
        // $date = $date ?: date('Y-m-d');
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');
        $sql3 = "
                SELECT
                IFNULL(SCL.`省份`,'合计') AS 省份,
                CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
                CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
                SUM(SCM.`今日目标`) AS 今日目标,
                SUM(SCL.`今天流水`) AS 今天流水,
                SUM(SCM.`本月目标`) 本月目标,
                SUM(SCL.`本月流水`) 本月流水,
                SUM(SCL.`近七天日均`) AS 近七天日均,
                ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  (DATEDIFF(LAST_DAY(CURDATE()),CURDATE()) ),2) AS 剩余目标日均
                FROM sp_customer_liushui_ww SCL
                LEFT JOIN sp_customer_mubiao_ww SCM ON SCL.`店铺名称`=SCM.`店铺名称`
                where SCL.`经营模式`='加盟'
                GROUP BY
                SCL.`省份`
                WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        array_pop($list);

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 80;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 95;
        $field_width[8] = 95;
        $field_width[9] = 110;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => "【加盟】",
        ];

        //参数
        $params = [
            'row' => count($list),          //数据的行数
            // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "区域挑战目标完成率 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 2,
            '本月达成率' => 3,
        ]);
    }

    // s109 各省挑战目标完成情况
    public function create_table_s109($date = '')
    {
        // 编号
        $code = 'S109';
        // $date = $date ?: date('Y-m-d');
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');

        $sql2 = "
            SELECT
            IFNULL(SCL.`经营模式`,'总计') AS 经营,
            IFNULL(SCL.`省份`,'合计') AS 省份,
            CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
            COUNT(DISTINCT SCL.`店铺名称`) AS 销售店铺数,
            SUM(SCM.`今日目标`) AS 今日目标,
            SUM(SCL.`今天流水`) AS 今天流水,
            SUM(SCM.`本月目标`) 本月目标,
            SUM(SCL.`本月流水`) 本月流水,
            SUM(SCL.`近七天日均`) AS 近七天日均,
            ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  (DATEDIFF(LAST_DAY(CURDATE()),CURDATE())),2) AS 剩余目标日均
            FROM sp_customer_liushui_ww SCL
            LEFT JOIN sp_customer_mubiao_ww SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            GROUP BY
            SCL.`经营模式`,
            SCL.`省份`
            WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql2);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        // dump($list); die;

        // cache('cache_xielv', null);
        // if (!cache('cache_xielv')) {
        //     $list = Db::connect("sqlsrv")->query($sql2);
        //     cache('cache_xielv', $list, 3600);
        // }
        // $list = cache('cache_xielv');

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 45;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 90;
        $field_width[11] = 100;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【加盟 & 直营】'
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($list),          //数据的行数
            // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'file_name' => $code .'.jpg',   //保存的文件名
            'title' => "各省挑战目标完成情况 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            // 'banben' => '',
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 3,
            '本月达成率' => 4,
        ]);
    }

    // s109 各省挑战目标完成情况
    public function create_table_s109B($date = '')
    {
        // 编号
        $code = 'S109B';
        // $date = $date ?: date('Y-m-d');
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');

        $sql2 = "
            SELECT
            IFNULL(SCL.`省份`,'合计') AS 省份,
            CONCAT(ROUND(SUM(SCL.`今天流水`)/SUM(SCM.`今日目标`)*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SUM(SCL.`本月流水`)/SUM(SCM.`本月目标`)*100,2),'%') AS 本月达成率,
            COUNT(DISTINCT SCL.`店铺名称`) AS 销售店铺数,
            SUM(SCM.`今日目标`) AS 今日目标,
            SUM(SCL.`今天流水`) AS 今天流水,
            SUM(SCM.`本月目标`) 本月目标,
            SUM(SCL.`本月流水`) 本月流水,
            SUM(SCL.`近七天日均`) AS 近七天日均,
            ROUND((SUM(SCM.`本月目标`) - SUM(SCL.`本月流水`)) /  (DATEDIFF(LAST_DAY(CURDATE()),CURDATE()) ), 2) AS 剩余目标日均
            FROM sp_customer_liushui_ww SCL
            LEFT JOIN sp_customer_mubiao_ww SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            where 经营模式='加盟'
            GROUP BY
            SCL.`省份`
            WITH ROLLUP
        ";
        $list = Db::connect("mysql2")->query($sql2);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }
        // dump($list); die;

        // cache('cache_xielv', null);
        // if (!cache('cache_xielv')) {
        //     $list = Db::connect("sqlsrv")->query($sql2);
        //     cache('cache_xielv', $list, 3600);
        // }
        // $list = cache('cache_xielv');

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 100;

        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【加盟】'
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($list),          //数据的行数
            // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "各省挑战目标完成情况-加盟 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 2,
            '本月达成率' => 3,
            // '今日目标' => 5,
        ]);
    }

    // s110 单店目标达成情况
    public function create_table_s110A($date = '')
    {
        // 编号
        $code = 'S110A';
        // $date = $date ?: date('Y-m-d');
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');

        $sql3 = "
            SELECT
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`,
            CONCAT(ROUND(SCL.`今天流水`/SCM.`今日目标`*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SCL.`本月流水`/SCM.`本月目标`*100,2),'%') AS 本月达成率,
            SCM.`今日目标`,
            SCL.`今天流水`,
            SCM.`本月目标`,
            SCL.`本月流水`,
            SCL.`近七天日均`,
            ROUND((SCM.`本月目标` - SCL.`本月流水`) /  (DATEDIFF(LAST_DAY(CURDATE()),CURDATE()) ), 2) AS 剩余目标日均
            FROM sp_customer_liushui_ww SCL
            LEFT JOIN sp_customer_mubiao_ww SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            WHERE SCL.`经营模式`='直营'
            ORDER BY
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`
            ;
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 150;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 100;
        $field_width[11] = 100;


        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【直营】',
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($list),          //数据的行数
            // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'file_name' => $code . '.jpg',   //保存的文件名
            'title' => "直营单店目标达成情况 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 4,
            '本月达成率' => 5,
        ]);
    }

    // s110 单店目标达成情况
    public function create_table_s110B($date = '')
    {
        // 编号
        $code = 'S110B';
        // $date = $date ?: date('Y-m-d');
        $date = $date ?: date('Y-m-d', strtotime('+1day'));
        $dingName = cache('dingding_table_name');

        $sql3 = "
            SELECT
            SCL.`省份`,
            SCL.`店铺名称`,
            CONCAT(ROUND(SCL.`今天流水`/SCM.`今日目标`*100,2),'%') AS 今日达成率,
            CONCAT(ROUND(SCL.`本月流水`/SCM.`本月目标`*100,2),'%') AS 本月达成率,
            SCM.`今日目标`,
            SCL.`今天流水`,
            SCM.`本月目标`,
            SCL.`本月流水`,
            SCL.`近七天日均`,
            ROUND((SCM.`本月目标` - SCL.`本月流水`) /  (DATEDIFF(LAST_DAY(CURDATE()),CURDATE()) ), 2) AS 剩余目标日均
            FROM sp_customer_liushui_ww SCL
            LEFT JOIN sp_customer_mubiao_ww SCM ON SCL.`店铺名称`=SCM.`店铺名称`
            WHERE SCL.`经营模式`='加盟'
            ORDER BY
            SCL.`省份`,
            SCL.`督导`,
            SCL.`店铺名称`
            ;
        ";
        $list = Db::connect("mysql2")->query($sql3);
        foreach ($list as $key => $val) {
            $list[$key]['省份'] = province2zi($val['省份']);
        }

        $table_header = ['ID'];
        $field_width = [];
        $table_header = array_merge($table_header, array_keys($list[0]));
        foreach ($table_header as $v => $k) {
            $field_width[] = 80;
        }
        $field_width[0] = 30;
        $field_width[1] = 45;
        $field_width[2] = 90;
        $field_width[3] = 90;
        $field_width[4] = 90;
        $field_width[5] = 90;
        $field_width[6] = 90;
        $field_width[7] = 90;
        $field_width[8] = 90;
        $field_width[9] = 90;
        $field_width[10] = 100;


        $last_year_week_today = date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week(date("Y-m-d", strtotime("-1 day")));
        $the_year_week_today =  date_to_week(date("Y-m-d", strtotime("-2 year -1 day")));
        //图片左上角汇总说明数据，可为空
        $table_explain = [
            // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
            0 => '【加盟】'
        ];

        //参数
        $params = [
            'code' => $code,
            'row' => count($list),          //数据的行数
            // 'file_name' => $code . $dingName . '.jpg',   //保存的文件名
            'file_name' => $code  . '.jpg',   //保存的文件名
            'title' => "加盟单店目标达成情况 [" . date("Y-m-d", strtotime($date . '-1day')) . ']',
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $list,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'banben' => '图片报表编号: ' . $code,
            'file_path' => "./img/cwl/" . date('Ymd', strtotime('+1day')) . '/'  //文件保存路径
        ];

        // 生成图片
        return $this->create_image_bgcolor($params, [
            '今日达成率' => 3,
            '本月达成率' => 4,
        ]);
    }

    //2022 冬季货品销售报表
    public function create_table_s010($date)
    {
        $date ? $date : date('Y-m-d');
        $code = 'S010';
        // $date = date('Y-m-d');
        $sql = "
            select 
                性质,
                风格,
                一级分类,
                二级分类,
                采购入库数,
                仓库库存,
                仓库可用库存,
                仓库库存成本,
                收仓在途,
                收仓在途成本,
                已配未发,
                最后一周销,
                昨天销,
                累计销售,
                累销成本,
                在途库存数量,
                店库存数量,
                合计库存数,
                合计库存数占比,
                合计库存成本,
                数量售罄率,
                成本售罄率,
                前四周销量,
                前三周销量,
                前两周销量,
                前一周销量,
                周转周 
            from winter_report_b 
            WHERE 1
                AND 更新日期 = '{$date}'  
            ";
        $data = $this->db_bi->Query($sql);
        // echo '<pre>';
        // print_r($data);die;

        $table_header = ['ID'];
        $table_header = array_merge($table_header, array_keys($data[0]));

        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }

        $field_width[0] = 40;
        $field_width[1] = 80;
        $field_width[2] = 80;
        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));

        // $table_data= [];


        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today, 
        ];

        $params = [
            'code' => $code,
            'row' => count($data),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => "2023 冬季货品销售报表  [" . date("Y-m-d", strtotime("-1 day")) . "]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '风格',
            'field' => '合计',
            'color'=>16711877,
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/cwl/".date('Ymd').'/'  //文件保存路径
        ];
        return $this->create_image($params);

    }

    public function create_table_s032($date)
    {
        $date = $date ?  : date('Y-m-d'); 
        // echo $date;
        // echo '<br>';
        // die;
        $code = 'S032';
        // $date = date('Y-m-d');
        $sql = "select 风格,一级分类,二级分类,SUM(采购入库数) AS 采购入库数,SUM(仓库库存) AS 仓库库存,SUM(仓库可用库存) AS 仓库可用库存,SUM(仓库库存成本) AS 仓库库存成本,SUM(收仓在途) AS 收仓在途,SUM(收仓在途成本) AS 收仓在途成本,sum(已配未发) as 已配未发,sum(最后一周销) as 最后一周销,sum(昨天销) as 昨天销,sum(累计销售) as 累计销售,sum(累销成本) as 累销成本,sum(在途库存数量) as 在途库存数量,sum(店库存数量) as 店库存数量,sum(合计库存数) as 合计库存数,sum(合计库存成本) as 合计库存成本,sum(前四周销量) as 前四周销量,sum(前三周销量) as 前三周销量,sum(前两周销量) as 前两周销量,sum(前一周销量) as 前一周销量 from winter_report_b where 更新日期 = '{$date}' and  一级分类 <> '合计' group by 风格,一级分类,二级分类  order by 风格";

        $data = $this->db_bi->Query($sql);
        $arr =  array_column($data, '合计库存数');
        $sum = array_sum($arr);
        $cols = array_column($data, '风格');
        $styles = array_values( array_unique($cols));
        $arr_counts =  array_count_values($cols);
        $arr = [];
        $all['风格'] = '汇总合计';
        $all['一级分类'] = '';
        $all['二级分类'] = '合计';
        foreach ($data as $v=>$k){
            foreach ($styles as $sv=>$sk){
                if($k['风格'] === $sk){
                    $arr[$sv]['风格'] =$sk;
                    $arr[$sv]['一级分类'] =$sk.'合计';
                    $arr[$sv]['二级分类'] ='合计';
                    $arr[$sv] = arr_add_member($arr[$sv],$k,['采购入库数','仓库库存','仓库可用库存','仓库库存成本','收仓在途','收仓在途成本','已配未发','最后一周销','昨天销','累计销售','累销成本','在途库存数量','店库存数量','合计库存数','合计库存成本','前四周销量','前三周销量','前两周销量','前一周销量']);
                }
            }

            $all =  arr_add_member($all,$k,['采购入库数','仓库库存','仓库可用库存','仓库库存成本','收仓在途','收仓在途成本','已配未发','最后一周销','昨天销','累计销售','累销成本','在途库存数量','店库存数量','合计库存数','合计库存成本','前四周销量','前三周销量','前两周销量','前一周销量']);
        }
        $ls[0] = $arr[0];

        array_splice($data,13,0,$ls);
        $ls[0] = $arr[1];
        $length = count($data);
        array_splice($data,$length,0,$ls);
        $ls[0] = $all;
        array_splice($data,$length+1,0,$ls);

        foreach ($data as $v=>$k){
            $data[$v]['合计库存占比'] =number_format(round( $k['合计库存数'] / $sum,4)*100,2).'%';
            $data[$v]['数量售罄率'] =  number_format(round( $k['累计销售'] /( $k['累计销售']  + $k['合计库存数']) ,4)*100,2).'%';
            $data[$v]['成本售罄率'] =  number_format(round( $k['累销成本'] /( $k['累销成本']  + $k['合计库存成本']) ,4)*100,2).'%';
            if($k['合计库存数'] > 0 &&$k['前一周销量'] >0){
                $data[$v]['周转周'] =  number_format(round( $k['合计库存数'] /( ($k['前一周销量'] +$k['前两周销量'] +$k['前三周销量'])/3) ,4),2);
            }else{
                $data[$v]['周转周'] = 0;
            }
        }
        $table_header = ['行号'];
        $title = ['风格','二级分类','采购入库数','累计销售','数量售罄率','合计库存数','合计库存成本','周转周','仓库库存','仓库可用库存',
            '仓库库存成本','收仓在途','收仓在途成本','已配未发','最后一周销','昨天销','累销成本','在途库存数量','店库存数量','前四周销量','前三周销量'
            ,'前两周销量','前一周销量','合计库存占比','成本售罄率'];

        $table_header = array_merge($table_header, $title);
        foreach ($table_header as $v => $k) {
            $field_width[$v] = 100;
        }
        $field_width[0] = 40;
        $field_width[1] = 80;
        $field_width[2] = 80;
        $last_year_week_today =date_to_week(date("Y-m-d", strtotime("-1 year -1 day")));
        $week =  date_to_week( date("Y-m-d", strtotime("-1 day")));
        $table_data= [];
        foreach ($data as $V=>$k){
            $new = [
                '风格'=>$k['风格'],
                '二级分类'=>$k['二级分类'],
                '采购入库数'=>$k['采购入库数'],
                '累计销售'=>$k['累计销售'],
                '数量售罄率'=>$k['数量售罄率'],
                '合计库存数'=>$k['合计库存数'],
                '合计库存成本'=>$k['合计库存成本'],
                '周转周'=>$k['周转周'],
                '仓库库存'=>$k['仓库库存'],
                '仓库可用库存'=>$k['仓库可用库存'],
                '仓库库存成本'=>$k['仓库库存成本'],
                '收仓在途'=>$k['收仓在途'],
                '收仓在途成本'=>$k['收仓在途成本'],
                '已配未发'=>$k['已配未发'],
                '最后一周销'=>$k['最后一周销'],
                '昨天销'=>$k['昨天销'],
                '累销成本'=>$k['累销成本'],
                '在途库存数量'=>$k['在途库存数量'],
                '店库存数量'=>$k['店库存数量'],
                '前四周销量'=>$k['前四周销量'],
                '前三周销量'=>$k['前三周销量'],
                '前两周销量'=>$k['前两周销量'],
                '前一周销量'=>$k['前一周销量'],
                '合计库存占比'=>$k['合计库存占比'],
                '成本售罄率'=>$k['成本售罄率'],
            ];

            $table_data[]=$new;
        }

        $data_handle = [];
        // 新数组装最终结果
        $data_new_cwl = [];
        foreach ($table_data as $key => $val) {
            if ($val['风格'] == '基本款' && $val['二级分类'] == '合计') {
                $data_handle['基本款合计'] = $table_data[$key];
                // 删
                unset($table_data[$key]);
            }
            if ($val['风格'] == '引流款' && $val['二级分类'] == '合计') {
                $data_handle['引流款合计'] = $table_data[$key];
                // 删
                unset($table_data[$key]);
            }
            if ($val['风格'] == '汇总合计' && $val['二级分类'] == '合计') {
                $data_handle['汇总合计'] = $table_data[$key];
                // 删
                unset($table_data[$key]);
            }
        }
        // dump($data_handle);
        // echo '<pre>';
        // print_r($table_data); 
        
        foreach ($table_data as $key => $val) {
            $base = $table_data[0]['风格'];
            if ($val['风格'] == $base) {
                // $data_new_cwl[$key] = $table_data[$key];
                array_push($data_new_cwl, $table_data[$key]);
            } elseif ($val['风格'] != $base && $table_data[$key - 1]['风格'] == $base) {
                // $data_new_cwl[$key] = $data_handle['基本款合计'];
                array_push($data_new_cwl, $data_handle['基本款合计']);
            } else {
                // $data_new_cwl[$key] = $table_data[$key-1];
                array_push($data_new_cwl, $table_data[$key-1]);
            }
        }
        array_push($data_new_cwl, $table_data[count($table_data)]);
        array_push($data_new_cwl, $data_handle['引流款合计']);
        array_push($data_new_cwl, $data_handle['汇总合计']);


        $table_explain = [
            0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today,
        ];
        $params = [
            'code' => $code,
            'row' => count($data_new_cwl),          //数据的行数
            'file_name' =>$code.'.jpg',      //保存的文件名
            'title' => "2023 冬季货品零售汇总报表  [" . date("Y-m-d", strtotime("-1 day")) . "]",
            'table_time' => date("Y-m-d H:i:s"),
            'data' => $data_new_cwl,
            'table_explain' => $table_explain,
            'table_header' => $table_header,
            'field_width' => $field_width,
            'col' => '二级分类',
            'color'=>16711877,
            'field' => '合计',
            'banben' => '图片报表编号: '.$code,
            'file_path' => "./img/cwl/".date('Ymd').'/'  //文件保存路径
        ];
        return $this->create_image($params);
        // halt($res);

    }
    public function create_image($params)
    {
        $base = [
            'border' => 1, //图片外边框
            'file_path' => $params['file_path'], //图片保存路径
            'title_height' => 35, //报表名称高度
            'title_font_size' => 16, //报表名称字体大小
            'font_ulr' => app()->getRootPath() . '/public/Medium.ttf', //字体文件路径
            'text_size' => 12, //正文字体大小
            'row_hight' => 30, //每行数据行高
        ];

        $y1 = 36;
        $x2 = 1542;
        $y2 = 65;
        $font_west =  realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf'); //字体文件路径
        $save_path = $base['file_path'] . $params['file_name'];

        //如果表说明部分不为空，则增加表图片的高度
        if (!empty($params['table_explain'])) {
            $base['title_height'] =   $base['title_height'] * count($params['table_explain']);
        }

        //计算图片总宽
        $w_sum = $base['border'];
        foreach ($params['field_width'] as $key => $value) {
            //图片总宽
            $w_sum += $value;
            //计算每一列的位置
            $base['column_x_arr'][$key] = $w_sum;
        }

        $base['img_width'] = $w_sum + $base['border'] * 2 - $base['border']; //图片宽度
        $base['img_height'] = ($params['row'] + 1) * $base['row_hight'] + $base['border'] * 2 + $base['title_height']; //图片高度
        $border_top = $base['border'] + $base['title_height']; //表格顶部高度
        $border_bottom = $base['img_height'] - $base['border']; //表格底部高度


        $img = imagecreatetruecolor($base['img_width'], $base['img_height']); //创建指定尺寸图片
        $bg_color = imagecolorallocate($img, 24, 98, 229); //设定图片背景色

        $yellow = imagecolorallocate($img, 238, 228, 0); //设定图片背景色
        $yellow2 = imagecolorallocate($img, 255, 252, 188); //设定图片背景色
        $text_coler = imagecolorallocate($img, 0, 0, 0); //设定文字颜色
        $text_coler2 = imagecolorallocate($img, 255, 255, 255); //设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150); //设定边框颜色
        $xb  = imagecolorallocate($img, 255, 255, 255); //设定图片背景色

        $red = imagecolorallocate($img, 255, 0, 0); //设定图片背景色
        $green = imagecolorallocate($img, 24, 98, 0); //设定图片背景色
        $chengse = imagecolorallocate($img, 255, 72, 22); //设定图片背景色
        $blue = imagecolorallocate($img, 0, 42, 212); //设定图片背景色
        $blue2 = imagecolorallocate($img, 141, 193, 247); //设定图片背景色
        $orange = imagecolorallocate($img, 255, 192, 0); //设定图片背景色
        $littleblue = imagecolorallocate($img, 22, 119, 210); //设定图片背景色

        imagefill($img, 0, 0, $bg_color); //填充图片背景色

        // 表面颜色（浅灰）
        $surface_color = imagecolorallocate($img, 235, 242, 255);
        // 标题字体颜色（白色）
        //先填充一个黑色的大块背景
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'], $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $bg_color); //画矩形

        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框
        imagefilledrectangle($img, $base['border'] + 2, $base['border'] + $base['title_height'] + 2, $base['img_width'] - $base['border'] - 2, $base['img_height'] - $base['border'] - 2, $surface_color); //画矩形
        //画表格纵线 及 写入表头文字

        $sum = $base['border'];

        foreach ($params['data'] as $key => $item) {
            if (isset($item['省份']) && $item['省份'] == '合计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
            } elseif (isset($item['省份']) && $item['省份'] == '总计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
            }

            if ($params['banben'] == '图片报表编号: S107') {
                if (isset($item['二级分类']) && $item['二级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['新老品']) && $item['新老品'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S007' || @$params['code'] == 'S008') {
                if (isset($item['二级分类']) && $item['二级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['风格']) && $item['风格'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S009') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['风格']) && $item['风格'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S010') {
                if (isset($item['一级分类']) && $item['一级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['风格']) && $item['风格'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S013' || @$params['code'] == 'S014') {
                if (isset($item['二级分类']) && $item['二级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['风格']) && $item['风格'] == '汇总合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S015') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['风格']) && $item['风格'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S016') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['大类']) && $item['大类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S018') {
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['大类']) && $item['大类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S017' || @$params['code'] == 'S019') {
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S030' || @$params['code'] == 'S031') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['经营']) && $item['经营'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S112') {
                if (isset($item['领型']) && $item['领型'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['中类']) && $item['中类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $blue2);
                }
                if (isset($item['大类']) && $item['大类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['风格']) && $item['风格'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S113') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['性质']) && $item['性质'] == '总计') {
                    
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S116') {
                if (isset($item['省份']) && $item['省份'] == '单日总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['省份']) && $item['省份'] == '本月总计') {
                    
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S115B') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
                if (isset($item['省份']) && $item['省份'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }

            if (@$params['code'] == 'S115C' || @$params['code'] == 'S115D') {
                if (isset($item['性质']) && $item['性质'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }
            }

            if (@$params['code'] == 'S032') {
                // if (isset($item['一级分类']) && $item['一级分类'] == '合计') {
                //     imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                // }
                if (isset($item['二级分类']) && $item['二级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['风格']) && $item['风格'] == '汇总合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }
            }
        }
        // create_table_s105
        if ($params['banben'] == '图片报表编号: S105') {
            if (isset($item['一级分类']) && $item['一级分类'] == '总计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
            }
        }
        // s106
        if ($params['banben'] == '图片报表编号: S106') {
            imagefilledrectangle($img, 370, $y1, $x2 + 3000, $y2, $yellow);
        }
        
        foreach ($base['column_x_arr'] as $key => $x) {
            imageline($img, $x, $border_top, $x, $border_bottom, $border_coler); //画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            imagettftext($img, $base['text_size'], 0, $sum + (($x - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $params['table_header'][$key]); //写入表头文字
            $sum += $params['field_width'][$key];
        }
        

        //画表格横线
        foreach ($params['data'] as $key => $item) {
            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $font_west, $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0] / 2 - $first_len / 2 + $base['border'], $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $key + 1); //写入序号
            $sub = 0;
            $sum = $params['field_width'][0] + $base['border'];
            foreach ($item as $k => $value) {
                // dump($value);
                if (empty($value)) {
                    $value = '';
                }
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $value); //写入data数据
                // imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $value); //写入data数据
                $sum += $params['field_width'][$sub];
            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $font_west, $params['title']); //imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0]; //右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7]; //左下角 Y 位置- 左上角 Y 位置 为文字高度
        $save_path = $base['file_path'] . $params['file_name'];
        if (!is_dir($base['file_path'])) //判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'], 0777, true);
        }

        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width) / 2, 30, $xb, $font_west, $params['title']);
        //设置图片左上角信息
        $a_hight = 10;
        if (!empty($params['table_explain'])) {
            foreach ($params['table_explain'] as $key => $value) {
                imagettftext($img, $base['text_size'], 0, 10, 20 + $a_hight, $yellow, $font_west, $value);
                imagettftext($img, $base['text_size'], 0, $base['img_width'] - 180, 20 + $a_hight, $xb, $font_west, $params['banben']);
                $a_hight += 20;
            }
        }

        imagepng($img, $save_path); //输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        echo '<img src="/' . $save_path . '"/>';
    }

    function create_table($params)
    {
        $base = [
            'border' => 1, //图片外边框
            'file_path' => $params['file_path'], //图片保存路径
            'title_height' => 35, //报表名称高度
            'title_font_size' => 16, //报表名称字体大小
            'font_url' => realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf'), //字体文件路径
            'text_size' => 12, //正文字体大小
            'row_hight' => 30, //每行数据行高
        ];


        $font_west =  realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf'); //字体文件路径
        $save_path = $base['file_path'] . $params['file_name'];

        //如果表说明部分不为空，则增加表图片的高度
        if (!empty($params['table_explain'])) {
            $base['title_height'] =   $base['title_height'] * count($params['table_explain']);
        }

        //计算图片总宽
        $w_sum = $base['border'];
        foreach ($params['field_width'] as $key => $value) {
            //图片总宽
            $w_sum += $value;
            //计算每一列的位置
            $base['column_x_arr'][$key] = $w_sum;
        }

        $base['img_width'] = $w_sum + $base['border'] * 2 - $base['border']; //图片宽度
        $base['img_height'] = ($params['row'] + 1) * $base['row_hight'] + $base['border'] * 2 + $base['title_height']; //图片高度
        $border_top = $base['border'] + $base['title_height']; //表格顶部高度
        $border_bottom = $base['img_height'] - $base['border']; //表格底部高度


        $img = imagecreatetruecolor($base['img_width'], $base['img_height']); //创建指定尺寸图片
        $bg_color = imagecolorallocate($img, 24, 98, 229); //设定图片背景色
        $text_coler = imagecolorallocate($img, 0, 0, 0); //设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150); //设定边框颜色
        $xb  = imagecolorallocate($img, 255, 255, 255); //设定图片背景色

        $red = imagecolorallocate($img, 255, 0, 0); //设定图片背景色
        $green = imagecolorallocate($img, 24, 98, 0); //设定图片背景色
        $chengse = imagecolorallocate($img, 255, 72, 22); //设定图片背景色
        $blue = imagecolorallocate($img, 0, 42, 212); //设定图片背景色
        $yellow = imagecolorallocate($img, 238, 228, 0); //设定图片背景色
        imagefill($img, 0, 0, $bg_color); //填充图片背景色

        // 表面颜色（浅灰）
        $surface_color = imagecolorallocate($img, 235, 242, 255);
        // 标题字体颜色（白色）
        //先填充一个黑色的大块背景
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'], $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $bg_color); //画矩形

        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框
        imagefilledrectangle($img, $base['border'] + 2, $base['border'] + $base['title_height'] + 2, $base['img_width'] - $base['border'] - 2, $base['img_height'] - $base['border'] - 2, $surface_color); //画矩形
        //画表格纵线 及 写入表头文字

        $sum = $base['border'];
        foreach ($base['column_x_arr'] as $key => $x) {
            imageline($img, $x, $border_top, $x, $border_bottom, $border_coler); //画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $base['font_url'], $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            imagettftext($img, $base['text_size'], 0, $sum + (($x - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $base['font_url'], $params['table_header'][$key]); //写入表头文字
            $sum += $params['field_width'][$key];
        }
        
        //画表格横线
        foreach ($params['data'] as $key => $item) {
            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $base['font_url'], $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0] / 2 - $first_len / 2 + $base['border'], $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $base['font_url'], $key + 1); //写入序号
            $sub = 0;
            $sum = $params['field_width'][0] + $base['border'];
            foreach ($item as $k => $value) {
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $base['font_url'], $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                if ((isset($item['店铺名称']) && $item['店铺名称']  === '合计') || isset($item['省份']) && $item['省份']  === '合计') {
                    imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $chengse, $font_west, $value);
                    $sum += $params['field_width'][$sub];
                } else {
                    if ($k === "累销递增率" || $k === "昨日递增率") {
                        $value = str_replace('%', "", $value);
                        if ($value < 0) {
                            imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $blue, $base['font_url'], $value . '%'); //写入data数据
                            $sum += $params['field_width'][$sub];
                        } else {
                            imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $red, $base['font_url'], $value . '%'); //写入data数据
                            $sum += $params['field_width'][$sub];
                        }
                    } else {
                        imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $base['font_url'], $value); //写入data数据
                        $sum += $params['field_width'][$sub];
                    }
                }
            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $base['font_url'], $params['title']); //imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0]; //右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7]; //左下角 Y 位置- 左上角 Y 位置 为文字高度
        $save_path = $base['file_path'] . $params['file_name'];
        if (!is_dir($base['file_path'])) //判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'], 0777, true);
        }

        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width) / 2, 30, $xb, $font_west, $params['title']);
        //设置图片左上角信息
        $a_hight = 10;
        if (!empty($params['table_explain'])) {
            foreach ($params['table_explain'] as $key => $value) {
                imagettftext($img, $base['text_size'], 0, 10, 20 + $a_hight, $yellow, $font_west, $value);
                imagettftext($img, $base['text_size'], 0, $base['img_width'] - 180, 20 + $a_hight, $xb, $font_west, $params['banben']);
                $a_hight += 20;
            }
        }

        imagepng($img, $save_path); //输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        echo '<img src="/' . $save_path . '"/>';
    }


    // 格子带背景色
    public function create_image_bgcolor($params, $set_bgcolor = [])
    {
        // echo '<pre>';
        // print_r($params);die;
        $base = [
            'border' => 1, //图片外边框
            'file_path' => $params['file_path'], //图片保存路径
            'title_height' => 35, //报表名称高度
            'title_font_size' => 16, //报表名称字体大小
            'font_ulr' => app()->getRootPath() . '/public/Medium.ttf', //字体文件路径
            'text_size' => 12, //正文字体大小
            'row_hight' => 30, //每行数据行高
        ];

        $y1 = 36;
        $x2 = 1542;
        $y2 = 65;
        $font_west =  realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf'); //字体文件路径
        $save_path = $base['file_path'] . $params['file_name'];

        //如果表说明部分不为空，则增加表图片的高度
        if (!empty($params['table_explain'])) {
            $base['title_height'] =   $base['title_height'] * count($params['table_explain']);
        }

        //计算图片总宽
        $w_sum = $base['border'];
        foreach ($params['field_width'] as $key => $value) {
            //图片总宽
            $w_sum += $value;
            //计算每一列的位置
            $base['column_x_arr'][$key] = $w_sum;
        }

        $base['img_width'] = $w_sum + $base['border'] * 2 - $base['border']; //图片宽度
        $base['img_height'] = ($params['row'] + 1) * $base['row_hight'] + $base['border'] * 2 + $base['title_height']; //图片高度
        $border_top = $base['border'] + $base['title_height']; //表格顶部高度
        $border_bottom = $base['img_height'] - $base['border']; //表格底部高度


        $img = imagecreatetruecolor($base['img_width'], $base['img_height']); //创建指定尺寸图片
        $bg_color = imagecolorallocate($img, 24, 98, 229); //设定图片背景色


        $yellow = imagecolorallocate($img, 238, 228, 0); //设定图片背景色
        $text_coler = imagecolorallocate($img, 0, 0, 0); //设定文字颜色
        $text_coler2 = imagecolorallocate($img, 255, 255, 255); //设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150); //设定边框颜色
        $xb  = imagecolorallocate($img, 255, 255, 255); //设定图片背景色

        $red = imagecolorallocate($img, 255, 0, 0); //设定图片背景色
        $red2 = imagecolorallocate($img, 251, 89, 62); //设定图片背景色
        $blue1 = imagecolorallocate($img, 168, 203, 255); //设定图片背景色
        $blue2 = imagecolorallocate($img, 66, 182, 255); //设定图片背景色
        $yellow2 = imagecolorallocate($img, 250, 233, 84); //设定图片背景色
        $yellow3 = imagecolorallocate($img, 230, 244, 0); //设定图片背景色
        $green = imagecolorallocate($img, 24, 98, 0); //设定图片背景色
        $green2 = imagecolorallocate($img, 75, 234, 32); //设定图片背景色
        $chengse = imagecolorallocate($img, 255, 72, 22); //设定图片背景色
        $blue = imagecolorallocate($img, 0, 42, 212); //设定图片背景色
        $gray = imagecolorallocate($img, 37, 240, 240); //设定图片背景色
        $littleblue = imagecolorallocate($img, 22, 172, 176); //设定图片背景色
        $orange = imagecolorallocate($img, 255, 192, 0); //设定图片背景色

        imagefill($img, 0, 0, $bg_color); //填充图片背景色

        // 表面颜色（浅灰）
        $surface_color = imagecolorallocate($img, 235, 242, 255);
        // 标题字体颜色（白色）
        //先填充一个黑色的大块背景
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'], $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $bg_color); //画矩形

        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框
        imagefilledrectangle($img, $base['border'] + 2, $base['border'] + $base['title_height'] + 2, $base['img_width'] - $base['border'] - 2, $base['img_height'] - $base['border'] - 2, $surface_color); //画矩形
        //画表格纵线 及 写入表头文字

        $sum = $base['border'];

        // 1 统计上色
        foreach ($params['data'] as $key => $item) {
            if (isset($item['店铺名称']) && $item['店铺名称'] == '合计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
            }
            if (isset($item['省份']) && $item['省份'] == '合计') {
                imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
            }
            if ($params['banben'] == '图片报表编号: S107') {
                if (isset($item['二级分类']) && $item['二级分类'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['新老品']) && $item['新老品'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $littleblue);
                }
            }

            if (@$params['code'] == 'S103') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['经营']) && $item['经营'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }   
            }

            if (@$params['code'] == 'S104Z') {
                if (isset($item['店铺名称']) && $item['店铺名称'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                } 
            }

            if (@$params['code'] == 'S108A') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['督导']) && $item['督导'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }   
            }

            if (@$params['code'] == 'S109') {
                if (isset($item['省份']) && $item['省份'] == '合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
                if (isset($item['经营']) && $item['经营'] == '总计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
                }   
            }

            if (@$params['code'] == 'S012') {
                if (isset($item['分类']) && $item['分类'] == '袜子合计') {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow2);
                }

                if (count($params['data']) == $key + 1) {
                    imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $yellow);
                }
            }

            
        }
        
        // 117标题颜色特殊处理
        if (@$params['code'] == 'S117') { 
            $s117_y1 = 38;
            // 直营
            $s117_x1 = $params['field_width'][0] + $params['field_width'][1] + $params['field_width'][2];
            $s117_x2 = $s117_x1 + $params['field_width'][3] + $params['field_width'][4] + $params['field_width'][5] + $params['field_width'][6];
            // 加盟
            $s117_x3 = $s117_x2 + $params['field_width'][7] + $params['field_width'][8] + $params['field_width'][9] + $params['field_width'][10];
            // 合计
            $s117_x4 = $s117_x3 + $params['field_width'][11] + $params['field_width'][12] + $params['field_width'][13] + $params['field_width'][14];

            // 直营
            imagefilledrectangle($img, $s117_x1, $s117_y1, $s117_x2, $y2, $yellow);
            // 加盟
            imagefilledrectangle($img, $s117_x2, $s117_y1, $s117_x3, $y2, $gray);
            // 合计
            imagefilledrectangle($img, $s117_x3, $s117_y1, $s117_x4, $y2, $orange);
            // imagefilledrectangle($img, $params['field_width'][0] + $params['field_width'][1] + $params['field_width'][2], $s117_y1, 
            // $params['field_width'][0] + $params['field_width'][1] + $params['field_width'][2] + $params['field_width'][3] + $params['field_width'][4] + $params['field_width'][5], $y2, $chengse);
        }

        // 117标题颜色特殊处理
        if (@$params['code'] == 'S118A' || @$params['code'] == 'S118B' || @$params['code'] == 'S118C') { 
            $s118_y1 = 38;
            // 直营
            $s118_x1 = $params['field_width'][0] + $params['field_width'][1] + $params['field_width'][2];
            $s118_x2 = $s118_x1 + $params['field_width'][3] + $params['field_width'][4] + $params['field_width'][5] + $params['field_width'][6];
            // 加盟
            $s118_x3 = $s118_x2 + $params['field_width'][7] + $params['field_width'][8] + $params['field_width'][9] + $params['field_width'][10];
            // 累计
            $s118_x4 = $s118_x3 + $params['field_width'][11] + $params['field_width'][12] + $params['field_width'][13] + $params['field_width'][14];
            // 同比累计
            $s118_x5 = $s118_x4 + $params['field_width'][15] + $params['field_width'][16] + $params['field_width'][17] + $params['field_width'][18];

            // 直营
            imagefilledrectangle($img, $s118_x1, $s118_y1, $s118_x2, $y2, $blue1);
            // 加盟
            imagefilledrectangle($img, $s118_x2, $s118_y1, $s118_x3, $y2, $blue2);
            // 累计
            imagefilledrectangle($img, $s118_x3, $s118_y1, $s118_x4, $y2, $yellow3);
            // 同比累计
            imagefilledrectangle($img, $s118_x4, $s118_y1, $s118_x5, $y2, $orange);
            // imagefilledrectangle($img, $params['field_width'][0] + $params['field_width'][1] + $params['field_width'][2], $s117_y1, 
            // $params['field_width'][0] + $params['field_width'][1] + $params['field_width'][2] + $params['field_width'][3] + $params['field_width'][4] + $params['field_width'][5], $y2, $chengse);
        }
        

        // 2 单元格上色
        if (! empty($set_bgcolor)) {
             /* 获取开始x1结束x2
             ^ array:2 [▼
                    "今日达成率" => array:2 [▼
                        "start" => 120
                        "end" => 210
                    ]
                    "本月达成率" => array:2 [▼
                        "start" => 210
                        "end" => 300
                    ]
                ]
             */
            foreach ($set_bgcolor as $k => $v) {
                $site_arr = [
                    'x0' => 0,
                    'x1' => 0
                ];
                for ($i = 0; $i <= $v; $i ++) {
                    if ($i < $v) {
                        $site_arr['x0'] += $params['field_width'][$i]; 
                    } else {
                        $site_arr['x1'] = $site_arr['x0'] + $params['field_width'][$i];
                    }
             
                }
                $set_bgcolor[$k] = $site_arr;
                
            }
            // dump($set_bgcolor[$key]);
            
            foreach ($params['data'] as $key => $item) {
                // echo '<pre>';
                // print_r($set_bgcolor);
                foreach ($set_bgcolor as $key2 => $val2) {
                    // echo $key2;
                    if (@$params['code'] == 'S108A' || @$params['code'] =='S108B' || @$params['code'] == 'S109' || @$params['code'] == 'S109B' || @$params['code'] 
                    == 'S110A'|| @$params['code'] == 'S110B') {
                        // echo 1111111111;die;
                        if (!empty($item[$key2]) && $item[$key2] <= 60) {
                            imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $gray);
                        } elseif (!empty($item[$key2]) && ($item[$key2] > 60 && $item[$key2] <= 99)) {
                            imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $green2);
                        } elseif (!empty($item[$key2]) && $item[$key2] > 99) { 
                            imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);   
                        }
                    }

                    // 配饰
                    if (@$params['code'] == 'S012') {
                        if ($item['周转周'] < 15 && !empty($item['周转周'])) {
                            imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);
                        }
                        // 或者以二级分类的仓库可用库存总量/二级分类总库存量的占比小于5%时候，在分类那边标红色
                        foreach ($params['data2'] as $key3 => $item3) {
                            if ($item3['二级分类'] == $item['二级分类'] && $item3['占比'] < 0.05) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);
                            }
                        }
                    }

                    // 117
                    if (@$params['code'] == 'S117') {
                        foreach ($params['data2'] as $key3 => $item3) {
                            if ($key2 == '直营_毛利率' && $item3['日期'] == $item['日期'] && $item3['直营_毛利率'] < $item3['直营累计_毛利率']) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);
                                break;
                            }
                            if ($key2 == '加盟_毛利率' && $item3['日期'] == $item['日期'] && $item3['加盟_毛利率'] < $item3['加盟累计_毛利率']) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);
                                break;
                            }
                        }
                    }
                }
            }
        }

        // create_table_s105
        if ($params['banben'] == '图片报表编号: S105') {
            if (isset($item['一级分类']) && $item['一级分类'] == '总计') {
                imagefilledrectangle($img, 0, $y1 + 30 * ($key + 1), $x2 + 3000 * ($key + 1), $y2 + 30 * ($key + 1), $yellow);
            }
        }
        // s106
        if ($params['banben'] == '图片报表编号: S106') {
            imagefilledrectangle($img, 350, $y1, $x2 + 3000, $y2, $yellow);
        }
        foreach ($base['column_x_arr'] as $key => $x) {
            imageline($img, $x, $border_top, $x, $border_bottom, $border_coler); //画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            imagettftext($img, $base['text_size'], 0, $sum + (($x - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $params['table_header'][$key]); //写入表头文字
            $sum += $params['field_width'][$key];
        }

        //画表格横线
        foreach ($params['data'] as $key => $item) {
            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $font_west, $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0] / 2 - $first_len / 2 + $base['border'], $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $key + 1); //写入序号
            $sub = 0;
            $sum = $params['field_width'][0] + $base['border'];
            foreach ($item as $k => $value) {
                // dump($value);
                if (empty($value)) {
                    $value = '';
                }
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $value); //写入data数据
                // imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub] - $sum) / 2 - $title_x_len / 2), $border_top + ($base['row_hight'] + $base['text_size']) / 2, $text_coler, $font_west, $value); //写入data数据
                $sum += $params['field_width'][$sub];
            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $font_west, $params['title']); //imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0]; //右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7]; //左下角 Y 位置- 左上角 Y 位置 为文字高度
        $save_path = $base['file_path'] . $params['file_name'];
        if (!is_dir($base['file_path'])) //判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'], 0777, true);
        }

        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width) / 2, 30, $xb, $font_west, $params['title']);
        //设置图片左上角信息
        $a_hight = 10;
        if (!empty($params['table_explain'])) {
            foreach ($params['table_explain'] as $key => $value) {
                imagettftext($img, $base['text_size'], 0, 10, 20 + $a_hight, $yellow, $font_west, $value);
                imagettftext($img, $base['text_size'], 0, $base['img_width'] - 180, 20 + $a_hight, $xb, $font_west, $params['banben']);
                $a_hight += 20;
            }
        }

        imagepng($img, $save_path); //输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        echo '<img src="/' . $save_path . '"/>';
    }

    //导购图片生成 晚上10点
    public function table_pic_daogou_night($params, $if_write_num = 1, $if_write_header = 1) {

        $base = [
            'border' => 1,//图片外边框
            'file_path' => $params['file_path'],//图片保存路径
            'title_height' => 35,//报表名称高度
            'title_font_size' => 16,//报表名称字体大小
            'font_ulr' => app()->getRootPath().'/public/Medium.ttf',//字体文件路径
            'text_size' => 12,//正文字体大小
            'row_hight' => 30,//每行数据行高
        ];

        $y1 = 36;
        $x2 = 1542;
        $y2 = 65;
        $font_west =  realpath('./static/plugs/font-awesome-4.7.0/fonts/SimHei.ttf');//字体文件路径
        $save_path = $base['file_path'] . $params['file_name'];

        //如果表说明部分不为空，则增加表图片的高度
        if(!empty($params['table_explain'])){
            $base['title_height'] =   $base['title_height'] * count($params['table_explain']);
        }

        //计算图片总宽
        $w_sum = $base['border'];
        foreach ($params['field_width'] as $key => $value) {
            //图片总宽
            $w_sum += $value;
            //计算每一列的位置
            $base['column_x_arr'][$key] = $w_sum;
        }

        $base['img_width'] = $w_sum + $base['border'] * 2-$base['border'];//图片宽度
        $base['img_height'] = ($params['row']+1) * $base['row_hight'] + $base['border'] * 2 + $base['title_height'];//图片高度
        $border_top = $base['border'] + $base['title_height'];//表格顶部高度
        $border_bottom = $base['img_height'] - $base['border'];//表格底部高度


        $img = imagecreatetruecolor($base['img_width'], $base['img_height']);//创建指定尺寸图片
        $bg_color = imagecolorallocate($img, 24,98,229);//设定图片背景色



        $yellow = imagecolorallocate($img, 238,228,0);//设定图片背景色
        $text_coler = imagecolorallocate($img, 0, 0, 0);//设定文字颜色
        $border_coler = imagecolorallocate($img, 150, 150, 150);//设定边框颜色
        $xb  = imagecolorallocate($img, 255,255,255);//设定图片背景色

        $red = imagecolorallocate($img, 255,0,0);//设定图片背景色
        $green = imagecolorallocate($img, 24,98,0);//设定图片背景色
        $chengse = imagecolorallocate($img, 255,72,22);//设定图片背景色
        $blue = imagecolorallocate($img, 0,42,212);//设定图片背景色

        imagefill($img, 0, 0, $bg_color);//填充图片背景色

        // 表面颜色（浅灰）
        $surface_color = imagecolorallocate($img, 235, 242, 255);
        // 标题字体颜色（白色）
        //先填充一个黑色的大块背景
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'], $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $bg_color);//画矩形

        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框
        imagefilledrectangle($img, $base['border'] + 2, $base['border'] + $base['title_height'] + 2, $base['img_width'] - $base['border'] - 2, $base['img_height'] - $base['border'] - 2, $surface_color);//画矩形
        //画表格纵线 及 写入表头文字

        $sum = $base['border'];

        foreach($params['data'] as $key => $item){
            if(in_array('合计',$item) || in_array('总计',$item)){
                imagefilledrectangle($img, 0, $y1+30*($key+1), $x2+3000*($key+1), $y2+30*($key+1), $yellow);
            }
        }
        foreach($base['column_x_arr'] as $key => $x){
            imageline($img, $x, $border_top, $x, $border_bottom,$border_coler);//画纵线
            $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $params['table_header'][$key]);
            $title_x_len = $this_title_box[2] - $this_title_box[0];
            $header_text = '';
            if ($if_write_header == 1) {
                $header_text = $params['table_header'][$key];
            } else {
                if ($key == 0) {
                    $header_text = '';
                } else {
                    $header_text = $params['table_header'][$key];
                }
            }
            imagettftext($img, $base['text_size'], 0, $sum + (($x-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $header_text);//写入表头文字
            $sum += $params['field_width'][$key];
        }

        //画表格横线
        foreach($params['data'] as $key => $item){

            $border_top += $base['row_hight'];
            //画横线
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $this_first = imagettfbbox($base['text_size'], 0, $font_west, $key);
            $first_len = $this_first[2] - $this_first[0];
            imagettftext($img, $base['text_size'], 0, $params['field_width'][0]/2 - $first_len/2+$base['border'], $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $if_write_num ? ($key+1) : '');//写入序号
            $sub = 0;
            $sum = $params['field_width'][0]+$base['border'];
            foreach ($item as $k =>$value){
                $sub++;
                $this_title_box = imagettfbbox($base['text_size'], 0, $font_west, $value);
                $title_x_len = $this_title_box[2] - $this_title_box[0];
                imagettftext($img, $base['text_size'], 0, $sum + (($base['column_x_arr'][$sub]-$sum)/2 - $title_x_len/2), $border_top + ($base['row_hight']+$base['text_size'])/2, $text_coler, $font_west, $value);//写入data数据
                $sum += $params['field_width'][$sub];

            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $font_west, $params['title']);//imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0];//右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7];//左下角 Y 位置- 左上角 Y 位置 为文字高度
        $save_path = $base['file_path'] . $params['file_name'];
        if(!is_dir($base['file_path']))//判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'],0777,true);
        }

        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width)/2, 30, $xb,$font_west, $params['title']);
        //设置图片左上角信息
        $a_hight = 10;
        if(!empty($params['table_explain'])){
            foreach ($params['table_explain'] as $key => $value) {
                imagettftext($img, $base['text_size'], 0, 10, 20+$a_hight, $yellow,$font_west, $value);
                imagettftext($img, $base['text_size'], 0, $base['img_width'] - 180, 20+$a_hight, $xb,$font_west, $params['banben']);
                $a_hight += 20;
            }
        }
//        echo $save_path;die;

        imagepng($img,$save_path);//输出图片，输出png使用imagepng方法，输出gif使用imagegif方法

        return $save_path;
//        echo '<img src="/'.$save_path.'"/>';

    }


}
