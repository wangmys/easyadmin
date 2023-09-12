<?php
namespace app\admin\controller\system\dingding;

use AlibabaCloud\SDK\Dingtalk\Vworkflow_1_0\Models\QuerySchemaByProcessCodeResponseBody\result\schemaContent\items\props\push;
use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * 店铺天气
 * Class Weather
 * @package app\dingtalk
 */
class Weather extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';
    
    /**
     * 构造函数
     * Dingtalk constructor.
     */
    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');
    }

    // 更新店铺cid 几秒
    public function getCustomerCid() {
        $sql_1 = "
            SELECT cid,customerName AS 店铺名称,State AS 省份,date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM `customers` where cid is not null and RegionId <> 55 AND Mathod in ('直营', '加盟')
        ";
        $selet_weather_customer = $this->db_tianqi->query($sql_1);
        // dump($selet_weather_customer);
        if ($selet_weather_customer) {
            $this->db_easyA->execute('TRUNCATE dd_weather_customer;');
            $chunk_list = array_chunk($selet_weather_customer, 500);
            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('dd_weather_customer')->strict(false)->insertAll($val);
            }
        }
    }

    // 天气历史 10天 几秒
    public function getWeather() {
        $dateList = getWeatherDateList(1); 
        // dump($dateList ); die;
        // cid列表
        $cidList = $this->db_easyA->query("
            select cid from dd_weather_customer where cid is not null group by cid
        ");

        $dateListStr = '';
        $updateSql = '';
        $cidListStr = '';
        // 日期处理
        foreach ($dateList as $key => $val) {
            if ($key < count($dateList) - 1) {
                $dateListStr .= $val . ',';
            } else {
                $dateListStr .= $val;
            }
            $updateSql .= " when weather_time = '{$val}' then '{$key}'";
        }
        
        // $updateSql2 = "
        //     day_index = case
        //         {$updateSql}
        //     end
        // ";
        // echo $updateSql2; 
        // die;
        // cid处理
        foreach ($cidList as $key => $val) {
            if ($key < count($cidList) - 1) {
                $cidListStr .= $val['cid'] . ',';
            } else {
                $cidListStr .= $val['cid'];
            }
        }
        // 天气日期列表
        $dateList = xmSelectInput($dateListStr);
        // cid列表
        $cidList = xmSelectInput($cidListStr);

        $sql = "
            SELECT 
                MAX(id) AS id, `cid`,`min_c`,`max_c`,ave_c,`weather_time`,`text_weather` FROM `weather2345` 
            WHERE `cid` IN ($cidList) 
                AND `weather_time` IN ($dateList)
            GROUP BY cid,weather_time
        ";
        $select = $this->db_tianqi->query($sql);
        $count = count($select);
        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE dd_weather;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('dd_weather')->strict(false)->insertAll($val);
            }

            // 更新 day_index
            $sql2 = "
                update dd_weather
                    set 
                        day_index = case
                            {$updateSql}
                        end
                where 
                day_index is null
            ";
            $this->db_easyA->execute($sql2);

            // 更新天气值和颜色
            $sql_天气值 = "
                update dd_weather 
                    set colorVal = 
                        case 
                            when `max_c` > 30 then `max_c`
                            when (`max_c` - `min_c`) <= 5 then (`max_c` + `min_c`) / 2
                            when (`max_c` - `min_c`) > 5 and (`max_c` - `min_c`) <= 10 then (`max_c` + `min_c`) / 2 + 2 
                            when (`max_c` - `min_c`) > 10 then (`max_c` + `min_c`) / 2 + 4
                        end
                where 
                    colorVal is null
            ";

            $sql_颜色 = "
                update dd_weather 
                    set colorNum = 
                        case 
                            when `colorVal` < 10 then 1
                            when `colorVal` < 18 then 2
                            when `colorVal` < 22 then 3
                            when `colorVal` < 26 then 4
                            when `colorVal` <= 30 then 5
                            when `colorVal` > 30 then 6
                        end
                where 
                    colorNum is null
            ";
            $this->db_easyA->execute($sql_天气值);
            $this->db_easyA->execute($sql_颜色);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "dd_weather 更新成功，数量：{$count}！"
            ]);

        }
    }

    // 更新天气值和颜色
    // public function getWeather2() {
    //     $sql_天气值 = "
    //         update dd_weather 
    //             set colorVal = 
    //                 case 
    //                     when `max_c` > 30 then `max_c`
    //                     when (`max_c` - `min_c`) <= 5 then (`max_c` + `min_c`) / 2
    //                     when (`max_c` - `min_c`) > 5 and (`max_c` - `min_c`) <= 10 then (`max_c` + `min_c`) / 2 + 2 
    //                     when (`max_c` - `min_c`) > 10 then (`max_c` + `min_c`) / 2 + 4
    //                 end
    //         where 
    //             colorVal is null
    //     ";

    //     $sql_颜色 = "
    //         update dd_weather 
    //             set colorNum = 
    //                 case 
    //                     when `colorVal` < 10 then 1
    //                     when `colorVal` < 18 then 2
    //                     when `colorVal` < 22 then 3
    //                     when `colorVal` < 26 then 4
    //                     when `colorVal` <= 30 then 5
    //                     when `colorVal` > 30 then 6
    //                 end
    //         where 
    //             colorNum is null
    //     ";
    //     $this->db_easyA->execute($sql_天气值);
    //     $this->db_easyA->execute($sql_颜色);

    // }

    // 店铺天气 几秒
    public function getCustomerWeather() {
        // 每日天气日期，最高最低温度
        $sql = "
            UPDATE dd_weather_customer as m 
                LEFT JOIN dd_weather AS d0 ON d0.day_index = 0 AND d0.cid = m.cid
                LEFT JOIN dd_weather AS d1 ON d1.day_index = 1 AND d1.cid = m.cid
                LEFT JOIN dd_weather AS d2 ON d2.day_index = 2 AND d2.cid = m.cid
                LEFT JOIN dd_weather AS d3 ON d3.day_index = 3 AND d3.cid = m.cid
                LEFT JOIN dd_weather AS d4 ON d4.day_index = 4 AND d4.cid = m.cid
                LEFT JOIN dd_weather AS d5 ON d5.day_index = 5 AND d5.cid = m.cid
                LEFT JOIN dd_weather AS d6 ON d6.day_index = 6 AND d6.cid = m.cid
                LEFT JOIN dd_weather AS d7 ON d7.day_index = 7 AND d7.cid = m.cid
                LEFT JOIN dd_weather AS d8 ON d8.day_index = 8 AND d8.cid = m.cid
                LEFT JOIN dd_weather AS d9 ON d9.day_index = 9 AND d9.cid = m.cid
                LEFT JOIN dd_weather AS d10 ON d10.day_index = 10 AND d10.cid = m.cid
            set 
                day0 = d0.weather_time,
                day1 = d1.weather_time,
                day2 = d2.weather_time,
                day3 = d3.weather_time,
                day4 = d4.weather_time,
                day5 = d5.weather_time,
                day6 = d6.weather_time,
                day7 = d7.weather_time,
                day8 = d8.weather_time,
                day9 = d9.weather_time,
                day10 = d10.weather_time,
                day0_min = d0.min_c,
                day0_max = d0.max_c,
                day1_min = d1.min_c,
                day1_max = d1.max_c,
                day2_min = d2.min_c,
                day2_max = d2.max_c,
                day3_min = d3.min_c,
                day3_max = d3.max_c,
                day4_min = d4.min_c,
                day4_max = d4.max_c,
                day5_min = d5.min_c,
                day5_max = d5.max_c,
                day6_min = d6.min_c,
                day6_max = d6.max_c,
                day7_min = d7.min_c,
                day7_max = d7.max_c,
                day8_min = d8.min_c,
                day8_max = d8.max_c,
                day9_min = d9.min_c,
                day9_max = d9.max_c,
                day10_min = d10.min_c,
                day10_max = d10.max_c,
                day0_col = d0.colorNum,
                day1_col = d1.colorNum,
                day2_col = d2.colorNum,
                day3_col = d3.colorNum,
                day4_col = d4.colorNum,
                day5_col = d5.colorNum,
                day6_col = d6.colorNum,
                day7_col = d7.colorNum,
                day8_col = d8.colorNum,
                day9_col = d9.colorNum,
                day10_col = d10.colorNum,
                更新日期 = date_format(now(),'%Y-%m-%d')
            WHERE
                1
        ";
        // die;
        $update = $this->db_easyA->execute($sql);
    }   

    // 天气颜色统计
    public function getWeatherColor() {
        $sql = "
            SELECT
                省份,
                店铺名称,
                `day0_col`,
                `day1_col`,
                `day2_col`,
                `day3_col`,
                `day4_col`,
                `day5_col`,
                `day6_col`,
                `day7_col`,
                `day8_col`,
                `day9_col`
            FROM
                dd_weather_customer 
            WHERE 1
                -- and 省份 IN ('广东省')
                -- and 店铺名称 in ('翁源一店')
            ORDER BY 省份
        ";
        $select = $this->db_easyA->query($sql);

        $this->db_easyA->execute('TRUNCATE dd_weather_color;');
        $insertData = [];
        foreach ($select as $key => $val) {
            $前三天颜色1 = 0;
            $前三天颜色2 = 0;
            $前三天颜色3 = 0;
            $前三天颜色4 = 0;
            $前三天颜色5 = 0;
            $前三天颜色6 = 0;
            $七天颜色1 = 0;
            $七天颜色2 = 0;
            $七天颜色3 = 0;
            $七天颜色4 = 0;
            $七天颜色5 = 0;
            $七天颜色6 = 0;
            if ($val['day0_col']  == 1) {
                $前三天颜色1 += 1;  
            } elseif ($val['day0_col']  == 2) {
                $前三天颜色2 += 1;
            } elseif ($val['day0_col']  == 3) {
                $前三天颜色3 += 1;
            } elseif ($val['day0_col']  == 4 ) {
                $前三天颜色4 += 1;
            } elseif ($val['day0_col']  == 5) {
                $前三天颜色5 += 1;
            } elseif ($val['day0_col']  == 6) {
                $前三天颜色6 += 1;
            }

            if ($val['day1_col']  == 1 ) {
                $前三天颜色1 += 1;  
            } elseif ($val['day1_col']  == 2 ) {
                $前三天颜色2 += 1;
            } elseif ( $val['day1_col']  == 3) {
                $前三天颜色3 += 1;
            } elseif ( $val['day1_col']  == 4 ) {
                $前三天颜色4 += 1;
            } elseif ( $val['day1_col']  == 5 ) {
                $前三天颜色5 += 1;
            } elseif ( $val['day1_col']  == 6 ) {
                $前三天颜色6 += 1;
            }

            if ($val['day2_col']  == 1) {
                $前三天颜色1 += 1;  
            } elseif ($val['day2_col']  == 2) {
                $前三天颜色2 += 1;
            } elseif ($val['day2_col']  == 3) {
                $前三天颜色3 += 1;
            } elseif ($val['day2_col']  == 4) {
                $前三天颜色4 += 1;
            } elseif ($val['day2_col']  == 5) {
                $前三天颜色5 += 1;
            } elseif ( $val['day2_col']  == 6) {
                $前三天颜色6 += 1;
            }

            // 今天
            if ($val['day3_col']  == 1) {
                $七天颜色1 += 1;  
            } elseif ($val['day3_col']  == 2) {
                $七天颜色2 += 1;
            } elseif ($val['day3_col']  == 3) {
                $七天颜色3 += 1;
            } elseif ($val['day3_col']  == 4) {
                $七天颜色4 += 1;
            } elseif ($val['day3_col']  == 5) {
                $七天颜色5 += 1;
            } elseif ( $val['day3_col']  == 6) {
                $七天颜色6 += 1;
            }

            if ($val['day4_col']  == 1) {
                $七天颜色1 += 1;  
            } elseif ($val['day4_col']  == 2) {
                $七天颜色2 += 1;
            } elseif ($val['day4_col']  == 3) {
                $七天颜色3 += 1;
            } elseif ($val['day4_col']  == 4) {
                $七天颜色4 += 1;
            } elseif ($val['day4_col']  == 5) {
                $七天颜色5 += 1;
            } elseif ( $val['day4_col']  == 6) {
                $七天颜色6 += 1;
            }

            if ($val['day5_col']  == 1) {
                $七天颜色1 += 1;  
            } elseif ($val['day5_col']  == 2) {
                $七天颜色2 += 1;
            } elseif ($val['day5_col']  == 3) {
                $七天颜色3 += 1;
            } elseif ($val['day5_col']  == 4) {
                $七天颜色4 += 1;
            } elseif ($val['day5_col']  == 5) {
                $七天颜色5 += 1;
            } elseif ( $val['day5_col']  == 6) {
                $七天颜色6 += 1;
            }

            if ($val['day6_col']  == 1) {
                $七天颜色1 += 1;  
            } elseif ($val['day6_col']  == 2) {
                $七天颜色2 += 1;
            } elseif ($val['day6_col']  == 3) {
                $七天颜色3 += 1;
            } elseif ($val['day6_col']  == 4) {
                $七天颜色4 += 1;
            } elseif ($val['day6_col']  == 5) {
                $七天颜色5 += 1;
            } elseif ( $val['day6_col']  == 6) {
                $七天颜色6 += 1;
            }

            if ($val['day7_col']  == 1) {
                $七天颜色1 += 1;  
            } elseif ($val['day7_col']  == 2) {
                $七天颜色2 += 1;
            } elseif ($val['day7_col']  == 3) {
                $七天颜色3 += 1;
            } elseif ($val['day7_col']  == 4) {
                $七天颜色4 += 1;
            } elseif ($val['day7_col']  == 5) {
                $七天颜色5 += 1;
            } elseif ( $val['day7_col']  == 6) {
                $七天颜色6 += 1;
            }

            if ($val['day8_col']  == 1) {
                $七天颜色1 += 1;  
            } elseif ($val['day8_col']  == 2) {
                $七天颜色2 += 1;
            } elseif ($val['day8_col']  == 3) {
                $七天颜色3 += 1;
            } elseif ($val['day8_col']  == 4) {
                $七天颜色4 += 1;
            } elseif ($val['day8_col']  == 5) {
                $七天颜色5 += 1;
            } elseif ( $val['day8_col']  == 6) {
                $七天颜色6 += 1;
            }

            if ($val['day9_col']  == 1) {
                $七天颜色1 += 1;  
            } elseif ($val['day9_col']  == 2) {
                $七天颜色2 += 1;
            } elseif ($val['day9_col']  == 3) {
                $七天颜色3 += 1;
            } elseif ($val['day9_col']  == 4) {
                $七天颜色4 += 1;
            } elseif ($val['day9_col']  == 5) {
                $七天颜色5 += 1;
            } elseif ( $val['day9_col']  == 6) {
                $七天颜色6 += 1;
            }
            array_push($insertData, [
                '省份' => $val['省份'],
                '店铺名称' => $val['店铺名称'],
                '前三天颜色1' => @$前三天颜色1,
                '前三天颜色2' => @$前三天颜色2,
                '前三天颜色3' => @$前三天颜色3,
                '前三天颜色4' => @$前三天颜色4,
                '前三天颜色5' => @$前三天颜色5,
                '前三天颜色6' => @$前三天颜色6,
                '七天颜色1' => @$七天颜色1,
                '七天颜色2' => @$七天颜色2,
                '七天颜色3' => @$七天颜色3,
                '七天颜色4' => @$七天颜色4,
                '七天颜色5' => @$七天颜色5,
                '七天颜色6' => @$七天颜色6,
                '更新日期' => date('Y-m-d'),
            ]);
        }

        $this->db_easyA->table('dd_weather_color')->insertAll($insertData);

        $sql_省份汇总 = "
            SELECT
                省份,
                '汇总' as 店铺名称,
                round(sum(`前三天颜色1`) / 3, 0) as `前三天颜色1`,
                round(sum(`前三天颜色2`) / 3, 0) as `前三天颜色2`,
                round(sum(`前三天颜色3`) / 3, 0) as `前三天颜色3`,
                round(sum(`前三天颜色4`) / 3, 0) as `前三天颜色4`,
                round(sum(`前三天颜色5`) / 3, 0) as `前三天颜色5`,
                round(sum(`前三天颜色6`) / 3, 0) as `前三天颜色6`,
                round(sum(`七天颜色1`) / 7, 0) as `七天颜色1`,
                round(sum(`七天颜色2`) / 7, 0) as `七天颜色2`,
                round(sum(`七天颜色3`) / 7, 0) as `七天颜色3`,
                round(sum(`七天颜色4`) / 7, 0)as `七天颜色4`,
                round(sum(`七天颜色5`) / 7, 0) as `七天颜色5`,
                round(sum(`七天颜色6`) / 7, 0) as `七天颜色6`,
                date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM
                dd_weather_color 
            WHERE
                1
            GROUP BY 省份
        ";
        $select_汇总 = $this->db_easyA->query($sql_省份汇总);
        $this->db_easyA->table('dd_weather_color')->insertAll($select_汇总);
    }


    // 图片生成1
    public function weather_pic() {
        $select = $this->db_easyA->query("
            SELECT 
                店铺名称
            FROM
                dd_weather_customer
            where 1
                -- AND 店铺名称 in ('仁寿一店')
        ");

        foreach ($select as $key => $val) {
            $this->create_table_weather($val['店铺名称']);
        }
    }

    // 发送测试2
    public function sendDingImg() {
        $model = new DingTalk;
        $select = $this->db_easyA->query("
            SELECT 
                u.*
            FROM
                dd_customer_push as u
            LEFT JOIN dd_weather_customer as c on u.店铺名称 = c.店铺名称
            where 1
                and u.店铺名称 = c.店铺名称
                and u.isCustomer = '是'
                
        ");

        // print_r($select);die;

        $datatime = date('Ymd');
        foreach ($select as $key => $val) {
            $path = "http://im.babiboy.com/upload/dd_weather/{$datatime}/{$val['店铺名称']}.jpg?v=" . time();

            // echo $val['userid'];
            $res = $model->sendMarkdownImg_pro($val['userid'], "{$val['店铺名称']} 未来7天天气", $path);
            // print_r($res);
        }
    }

    // 图片生成2
    protected function create_table_weather($customer = '', $date = '')
    {
        $date = $date ?: date('Y-m-d', time());
        
        $sql = "
            SELECT 
                店铺名称,
                day4,
                day5,
                day6,
                day7,
                day8,
                day9,
                day10,
                day4_min,
                day5_min,
                day6_min,
                day7_min,
                day8_min,
                day9_min,
                day10_min,
                day0_max,
                day1_max,
                day2_max,
                day3_max,
                day4_max,
                day5_max,
                day6_max,
                day7_max,
                day8_max,
                day9_max,
                day10_max
            FROM
                dd_weather_customer
            where 店铺名称 in ('{$customer}')
        ";

        $select = $this->db_easyA->query($sql);

        $data = $select[0];


        // echo '<pre>';
        // print_r($data);
        
        $weather_data = [];
        $weather_data[0]['日期'] = $data['day4'];
        $weather_data[0]['星期'] = date_to_week3($data['day4']);
        $weather_data[0]['最低温~最高温']= $data['day4_min'] . '~' . $data['day4_max']; 
        $weather_data[1]['日期'] = $data['day5'];
        $weather_data[1]['星期'] = date_to_week3($data['day5']);
        $weather_data[1]['最低温~最高温']= $data['day5_min'] . '~' . $data['day5_max']; 
        $weather_data[2]['日期'] = $data['day6'];
        $weather_data[2]['星期'] = date_to_week3($data['day6']);
        $weather_data[2]['最低温~最高温']= $data['day6_min'] . '~' . $data['day6_max']; 
        $weather_data[3]['日期'] = $data['day7'];
        $weather_data[3]['星期'] = date_to_week3($data['day7']);
        $weather_data[3]['最低温~最高温']= $data['day7_min'] . '~' . $data['day7_max']; 
        $weather_data[4]['日期'] = $data['day8'];
        $weather_data[4]['星期'] = date_to_week3($data['day8']);
        $weather_data[4]['最低温~最高温']= $data['day8_min'] . '~' . $data['day8_max']; 
        $weather_data[5]['日期'] = $data['day9'];
        $weather_data[5]['星期'] = date_to_week3($data['day9']);
        $weather_data[5]['最低温~最高温']= $data['day9_min'] . '~' . $data['day9_max']; 
        $weather_data[6]['日期'] = $data['day10'];
        $weather_data[6]['星期'] = date_to_week3($data['day10']);
        $weather_data[6]['最低温~最高温']= $data['day10_min'] . '~' . $data['day10_max']; 

        $weather_data_对比 = [];
        $weather_data_对比[0]['日期'] = $data['day4'];
        $weather_data_对比[0]['最低温']= $data['day4_min']; 
        $weather_data_对比[0]['最高温']= $data['day4_max']; 
        $weather_data_对比[1]['日期'] = $data['day5'];
        $weather_data_对比[1]['最低温']= $data['day5_min']; 
        $weather_data_对比[1]['最高温']= $data['day5_max']; 

        $weather_data_对比[2]['日期'] = $data['day6'];
        $weather_data_对比[2]['最低温']= $data['day6_min']; 
        $weather_data_对比[2]['最高温']= $data['day6_max']; 

        $weather_data_对比[3]['日期'] = $data['day7'];
        $weather_data_对比[3]['最低温']= $data['day7_min']; 
        $weather_data_对比[3]['最高温']= $data['day7_max']; 
        $weather_data_对比[4]['日期'] = $data['day8'];
        $weather_data_对比[4]['最低温']= $data['day8_min']; 
        $weather_data_对比[4]['最高温']= $data['day8_max']; 
        $weather_data_对比[5]['日期'] = $data['day9'];
        $weather_data_对比[5]['最低温']= $data['day9_min']; 
        $weather_data_对比[5]['最高温']= $data['day9_max']; 
        $weather_data_对比[6]['日期'] = $data['day10'];
        $weather_data_对比[6]['最低温'] = $data['day10_min'];
        $weather_data_对比[6]['最高温'] = $data['day10_max'];


        foreach ($weather_data as $key => $val) {
            $weather_data[$key]['日期'] = date('m-d', strtotime($val['日期']));
        }

        foreach ($weather_data_对比 as $key => $val) {
            $weather_data_对比[$key]['日期'] = date('m-d', strtotime($val['日期']));
        }

        if ($weather_data) {

            $table_header = ['ID'];
            $table_header = array_merge($table_header, array_keys($weather_data[0]));
            foreach ($table_header as $v => $k) {
                $field_width[$v] = 150;
            }

            $field_width[0] = 35;
            $field_width[1] = 60;
            $field_width[2] = 60;
 
            $table_explain = [
                // 0 => "昨天:".$week. "  .  去年昨天:".$last_year_week_today."  .  前年昨日:".$the_year_week_today,
                0 => " ",
            ];
            //参数 $save_path = app()->getRootPath() . 'public/upload/dd_img/' . date('Ymd',time()).'/';   //文件保存路径
            $params = [
                'row' => count($weather_data),          //数据的行数
                'file_name' => $data['店铺名称'] . '.jpg',      //保存的文件名
                'title' => $data['店铺名称'] . ' 未来七天天气',
                'table_time' => date("Y-m-d H:i:s"),
                'data' => $weather_data,
                'data_对比' => $weather_data_对比,
                'table_explain' => $table_explain,
                'table_header' => $table_header,
                'field_width' => $field_width,
                'banben' => '',
                'file_path' => app()->getRootPath() . 'public/upload/dd_weather/' . date('Ymd', time()) . '/'  //文件保存路径
            ];

            // 生成图片
            return $this->create_image_bgcolor($params,
                [
                    '最低温~最高温' => 3,
                    // '去年日增长' => 4,
                    // '前年月增长' => 5,
                    // '去年月增长' => 6,
                ]
            );
        }
    }

    // 气温颜色图
    public function create_table_weather_color($date = '') {
        $date = $date ?: date('Y-m-d', time());
        $sql = "
            SELECT
                t.`省份`,
                t.`七天颜色1`,
                t.`七天颜色2`,
                t.`七天颜色3`,
                t.`七天颜色4`,
                t.`七天颜色5`,
                t.`七天颜色6`,
                concat( ROUND(t.`七天颜色1` / 七天颜色总数 * 100, 1), '%' ) as `七天颜色1占比`,
                concat( ROUND(t.`七天颜色2` / 七天颜色总数 * 100, 1), '%' ) as `七天颜色2占比`,
                concat( ROUND(t.`七天颜色3` / 七天颜色总数 * 100, 1), '%' ) as `七天颜色3占比`,
                concat( ROUND(t.`七天颜色4` / 七天颜色总数 * 100, 1), '%' ) as `七天颜色4占比`,
                concat( ROUND(t.`七天颜色5` / 七天颜色总数 * 100, 1), '%' ) as `七天颜色5占比`,
                concat( ROUND(t.`七天颜色6` / 七天颜色总数 * 100, 1), '%' ) as `七天颜色6占比`,
                t.`七天颜色1` - `前三天颜色1` AS `家数环比1`,
                t.`七天颜色2` - `前三天颜色2` AS `家数环比2`,
                t.`七天颜色3` - `前三天颜色3` AS `家数环比3`,
                t.`七天颜色4` - `前三天颜色4` AS `家数环比4`,
                t.`七天颜色5` - `前三天颜色5` AS `家数环比5`,
                t.`七天颜色6` - `前三天颜色6` AS `家数环比6`
            FROM (
                SELECT
                    `省份`,
                    `前三天颜色1`,
                    `前三天颜色2`,
                    `前三天颜色3`,
                    `前三天颜色4`,
                    `前三天颜色5`,
                    `前三天颜色6`,
                    `七天颜色1`,
                    `七天颜色2`,
                    `七天颜色3`,
                    `七天颜色4`,
                    `七天颜色5`,
                    `七天颜色6`,
                    `七天颜色1` + `七天颜色2` + `七天颜色3` + `七天颜色4` + `七天颜色5` + `七天颜色6` as 七天颜色总数
                FROM
                    `dd_weather_color`
                WHERE 1
             		AND 省份 in ('重庆')
                    AND 店铺名称 = '汇总'
                    AND 更新日期 = '{$date}'
                GROUP BY 省份
            ) as t
        ";
        $select = $this->db_easyA->query($sql);
        echo '<pre>';
        print_r($select);


        // dump($data);
        foreach($select as $key => $val) {
            $data = [
                [
                    '家数' => $val['七天颜色1'],
                    '占比' => $val['七天颜色1占比'],
                    '家数.' => $val['七天颜色4'],
                    '占比.' => $val['七天颜色4占比'],
                    '家数环比（三天）' => $val['家数环比1'],
                    '家数环比（三天）.' => $val['家数环比4'],
                ],
                [
                    '家数' => $val['七天颜色2'],
                    '占比' => $val['七天颜色2占比'],
                    '家数.' => $val['七天颜色5'],
                    '占比.' => $val['七天颜色5占比'],
                    '家数环比（三天）' => $val['家数环比2'],
                    '家数环比（三天）.' => $val['家数环比5'],
                ],
                [
                    '家数' => $val['七天颜色3'],
                    '占比' => $val['七天颜色3占比'],
                    '家数.' => $val['七天颜色4'],
                    '占比.' => $val['七天颜色4占比'],
                    '家数环比（三天）' => $val['家数环比3'],
                    '家数环比（三天）.' => $val['家数环比6'],
                ],
                [
                    '家数' => '秋天增加家数',
                    '占比' => $val['家数环比3'],
                    '家数.' => '',
                    '占比.' => '',
                    '家数环比（三天）' => '冬天新增家数',
                    '家数环比（三天）.' => $val['家数环比6'],
                ],
            ];
        }
        echo '<pre>';
        dump($data);

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

        // 天气温度
        $color1 = imagecolorallocate($img, 22, 108, 221); //设定图片背景色
        $color2 = imagecolorallocate($img, 103, 184, 249); //设定图片背景色
        $color3 = imagecolorallocate($img, 248, 243, 162); //设定图片背景色
        $color4 = imagecolorallocate($img, 253, 206, 74); //设定图片背景色
        $color5 = imagecolorallocate($img, 241, 124, 0); //设定图片背景色
        $color6 = imagecolorallocate($img, 204, 41, 49); //设定图片背景色

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
        // foreach ($params['data'] as $key => $item) {
        //     if (isset($item['督导']) && $item['督导'] == '总计') {
        //         imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
        //     } 
        //     imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
        // }
        

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
                foreach ($set_bgcolor as $key2 => $val2) {
                    // dump($val2);
                    // dump($item);

                    foreach ($params['data_对比'] as $key3 => $item3) {
                        // dump($item3);

                        if ($item3['日期'] == $item['日期']) {
                            $colorVal = '';
                            if ($item3['最高温'] > 30 ) {
                                // imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $orange);
                                $colorVal = 38;
                            } elseif ($item3['最高温'] - $item3['最低温'] <= 5) {
                                $colorVal = ($item3['最高温'] + $item3['最低温']) / 2;
                            } elseif ( ($item3['最高温'] - $item3['最低温']) > 5 && ($item3['最高温'] - $item3['最低温']) <= 10) {
                                $colorVal = ($item3['最高温'] + $item3['最低温']) / 2 + 2;
                            } elseif ( ($item3['最高温'] - $item3['最低温']) > 10) {
                                $colorVal = ($item3['最高温'] + $item3['最低温']) / 2 + 4;
                            }

                            if ($colorVal < 10) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color1);
                            } elseif ($colorVal < 18) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color2);
                            } elseif ($colorVal < 22) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color3);
                            } elseif ($colorVal < 26) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color4);
                            } elseif ($colorVal <= 30) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color5);
                            } elseif ($colorVal > 30) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color6);
                            }
                        }

                        // elseif($item3['最高温'] > 30) {
                        //     imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);
                        // }
                    }
                }
            }
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

        // echo '<img src="/' . $save_path . '"/>';
    }

    // 天气颜色 gd生产
    public function create_image_bgcolor_pro($params, $set_bgcolor = [])
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

        // 天气温度
        $color1 = imagecolorallocate($img, 22, 108, 221); //设定图片背景色
        $color2 = imagecolorallocate($img, 103, 184, 249); //设定图片背景色
        $color3 = imagecolorallocate($img, 248, 243, 162); //设定图片背景色
        $color4 = imagecolorallocate($img, 253, 206, 74); //设定图片背景色
        $color5 = imagecolorallocate($img, 241, 124, 0); //设定图片背景色
        $color6 = imagecolorallocate($img, 204, 41, 49); //设定图片背景色

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
        // foreach ($params['data'] as $key => $item) {
        //     if (isset($item['督导']) && $item['督导'] == '总计') {
        //         imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
        //     } 
        //     imagefilledrectangle($img, 3, $y1 + 30 * ($key + 1), $base['img_width'] - 3, $y2 + 30 * ($key + 1), $orange);
        // }
        

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
                foreach ($set_bgcolor as $key2 => $val2) {
                    // dump($val2);
                    // dump($item);

                    foreach ($params['data_对比'] as $key3 => $item3) {
                        // dump($item3);

                        if ($item3['日期'] == $item['日期']) {
                            $colorVal = '';
                            if ($item3['最高温'] > 30 ) {
                                // imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $orange);
                                $colorVal = 38;
                            } elseif ($item3['最高温'] - $item3['最低温'] <= 5) {
                                $colorVal = ($item3['最高温'] + $item3['最低温']) / 2;
                            } elseif ( ($item3['最高温'] - $item3['最低温']) > 5 && ($item3['最高温'] - $item3['最低温']) <= 10) {
                                $colorVal = ($item3['最高温'] + $item3['最低温']) / 2 + 2;
                            } elseif ( ($item3['最高温'] - $item3['最低温']) > 10) {
                                $colorVal = ($item3['最高温'] + $item3['最低温']) / 2 + 4;
                            }

                            if ($colorVal < 10) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color1);
                            } elseif ($colorVal < 18) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color2);
                            } elseif ($colorVal < 22) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color3);
                            } elseif ($colorVal < 26) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color4);
                            } elseif ($colorVal <= 30) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color5);
                            } elseif ($colorVal > 30) {
                                imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $color6);
                            }
                        }

                        // elseif($item3['最高温'] > 30) {
                        //     imagefilledrectangle($img, $val2['x0'], $y1 + 30 * ($key + 1), $val2['x1'], $y2 + 30 * ($key + 1), $red2);
                        // }
                    }
                }
            }
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

        // echo '<img src="/' . $save_path . '"/>';
    }
    
}
