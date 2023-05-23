<?php
namespace app\api\controller\lufei;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatisticsSys;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;

/**
 * @ControllerAnnotation(title="店铺业绩环比")
 */
class Dianpuyejihuanbi extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }

    public function dianpuyejihuanbi_date_handle() {
        $date = input('date');

        // die;
        $res = $this->dianpuyejihuanbi_date($date);
        // dump(json_decode($res->toArray(), true));
        return json($res);
    }

    // 店铺业绩环比数据源入库        
    public function dianpuyejihuanbi_date($date = '') {
        if (empty($date)) return [
            'status' => 0,
            'msg' => 'error',
            'content' => '店铺业绩环比数据源 更新失败, 日期时间不能为空！'
        ];
        $sql = "
            declare @retail_date DATE
            set @retail_date = '{$date}'

            SELECT
                SUBSTRING(EC.State, 1, 2)  AS 省份,
                ER.CustomerName AS 店铺名称,
                EBC.Mathod AS 经营属性,
                @retail_date AS 日期,   
            CASE
                DATEPART( weekday, @retail_date )
                WHEN 1 THEN
                '星期日'
                WHEN 2 THEN
                '星期一'
                WHEN 3 THEN
                '星期二'
                WHEN 4 THEN
                '星期三'
                WHEN 5 THEN
                '星期四'
                WHEN 6 THEN
                '星期五'
                WHEN 7 THEN
                '星期六'
                END AS 星期,
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额
            FROM
                ErpRetail AS ER 
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            WHERE
                ER.RetailDate >= @retail_date 
                AND ER.RetailDate < DATEADD( DAY, +1, @retail_date ) 
                AND ER.CodingCodeText = '已审结'
                AND EC.ShutOut = 0
                AND EC.RegionId <> 55
                AND EC.RegionId IN ('91', '92', '93', '94', '95', '96')
                AND EBC.Mathod IN ('直营', '加盟')
            GROUP BY 
                ER.CustomerName,
                EC.State,
                EBC.Mathod	
            ORDER BY EC.State ASC
        ";
        // 查康雷
        $select_data = $this->db_sqlsrv->query($sql);
        if ($select_data) {
            // dump($select_data);
            // 删 easyadmin2
            $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->where([
                ['日期', '=', $date]
            ])->delete();

            $this->db_easyA->startTrans();
            $insertAll = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->strict(false)->insertAll($select_data);
            if ($insertAll) {
                $this->db_easyA->commit();
                return [
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "店铺业绩环比数据源 更新成功，{$date}！"
                ];
            } else {
                $this->db_easyA->rollback();
                return [
                    'status' => 0,
                    'msg' => 'error',
                    'content' => "店铺业绩环比数据源 更新失败，{$date}！"
                ];
            }   
        }
    }

    // 上月环比数据整理   cwl_dianpuyejihuanbi_lastmonth   
    public function dianpuyejihuanbi_lastmonth($date = '') {
        $date_str = "2023-04-01";
        $date = date('Y-m', strtotime($date_str));
        $sql0 = "set @date_str = '{$date_str}';";

        $this->db_easyA->query($sql0);
        $sql = "
            SELECT
                a.省份,a.店铺名称,a.经营属性,DATE_FORMAT( @date_str, '%Y-%m') AS 日期,
                (
                SELECT
                    ROUND(SUM(w1.销售金额) / count(w1.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi_data AS w1
                WHERE
                    w1.日期 >= @date_str
                    AND w1.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w1.星期='星期一'
                    AND w1.`店铺名称`=a.店铺名称
                ) as 星期一,
                (
                SELECT
                    ROUND(SUM(w2.销售金额) / count(w2.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi_data AS w2
                WHERE
                    w2.日期 >= @date_str
                    AND w2.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w2.星期='星期二'
                    AND w2.`店铺名称`=a.店铺名称
                ) as 星期二,
                (
                SELECT
                    ROUND(SUM(w3.销售金额) / count(w3.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi_data AS w3
                WHERE
                    w3.日期 >= @date_str
                    AND w3.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w3.星期='星期三'
                    AND w3.`店铺名称`=a.店铺名称
                ) as 星期三,
                (
                SELECT
                    ROUND(SUM(w4.销售金额) / count(w4.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi_data AS w4
                WHERE
                    w4.日期 >= @date_str
                    AND w4.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w4.星期='星期四'
                    AND w4.`店铺名称`=a.店铺名称
                ) as 星期四,
                (
                SELECT
                    ROUND(SUM(w5.销售金额) / count(w5.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi_data AS w5
                WHERE
                    w5.日期 >= @date_str
                    AND w5.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w5.星期='星期五'
                    AND w5.`店铺名称`=a.店铺名称
                ) as 星期五,
                (
                SELECT
                    ROUND(SUM(w6.销售金额) / count(w6.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi_data AS w6
                WHERE
                    w6.日期 >= @date_str
                    AND w6.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w6.星期='星期六'
                    AND w6.`店铺名称`=a.店铺名称
                ) as 星期六,
                (
                SELECT
                    ROUND(SUM(w7.销售金额) / count(w7.星期), 2) 
                FROM
                    cwl_dianpuyejihuanbi_data AS w7
                WHERE
                    w7.日期 >= @date_str
                    AND w7.日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
                    AND w7.星期='星期日'
                    AND w7.`店铺名称`=a.店铺名称
                ) as 星期日	
            FROM
                cwl_dianpuyejihuanbi_data AS a
            WHERE
                日期 >= @date_str
                AND 日期 < DATE_ADD(@date_str, INTERVAL 1 MONTH) 
            GROUP BY `店铺名称`
            ORDER BY 省份
        "; 

        // 查easyadmin2
        $select_data = $this->db_easyA->query($sql);


        if ($select_data) {
            // dump($select_data);
            // 删 easyadmin2
            $this->db_easyA->table('cwl_dianpuyejihuanbi_lastmonth')->where([
                ['日期', '=', $date]
            ])->delete();

            $this->db_easyA->startTrans();
            $insertAll = $this->db_easyA->table('cwl_dianpuyejihuanbi_lastmonth')->strict(false)->insertAll($select_data);
            if ($insertAll) {
                $this->db_easyA->commit();

                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "店铺业绩环比 上月环比数据整理 更新成功，{$date}！"
                ]);
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => "店铺业绩环比 上月环比数据整理  更新失败，{$date}！"
                ]);
            }   
        }
    }

    public function testDay($date = '2023-05-23') {
        if ($date) {
            // 今天是星期几
            echo $today = date_to_week2($date);
            echo '<br>';
            // 上月开始
            echo $last_month  = date("Y-m-01", strtotime('-1month')); 
            echo '<br>';
            // 上月今天
            echo $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", strtotime($date)); 
            echo '<br>';
            // 本月开始
            echo $current_month  = date("Y-m-01", time()); 
            echo '<br>';
            // 本月今天
            echo $today_date = $date;
        } else {
            // 今天是星期几
            echo  $today =  date_to_week2(date("Y-m-d", strtotime("-0 day")));
            echo '<br>';
            // 上月开始
            echo $last_month  = date("Y-m-01", strtotime('-1month')); 
            echo '<br>';
            // 上月今天
            echo $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", time()); 
            echo '<br>';
            // 本月开始
            echo $current_month  = date("Y-m-01", time()); 
            echo '<br>';
            // 本月今天
            echo $today_date = date("Y-m-d", time());
            echo '<br>';
        }

        die;


        $first = '2023-04-05';
        // $first = '2023-04-27';
        if (strtotime($first) <= strtotime($last_month)) {
            echo '首单<=' . $last_month; 
        } elseif (strtotime($first) > strtotime($last_month) && strtotime($first) <= strtotime($last_month_today)) {
            echo '首单>' . $last_month; 
            echo '<br>';
            echo '首单<=' . $last_month_today; 
            echo '<br>';
            echo '结果:' . $first . '-' . $last_month_today;
            echo '<br>';
            echo date("Y-m-", time()) . date('d', strtotime($first));
        } else {
            echo '其他情况';
            
        }
    }

    // 展示表数据计算
    public function dianpuyejihuanbi_handle() {
        $date = input('date') ? input('date') : '';
        if (! empty($date)) {
            // 今天是星期几
            $today = date_to_week2($date);
            // 上月开始
            $last_month  = date("Y-m-01", strtotime('-1month')); 
            // 上月今天
            $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", strtotime($date)); 
            // 本月开始
            $current_month  = date("Y-m-01", time()); 
            // 本月今天
            $today_date = $date;
        } else {
            // 今天是星期几
            $today =  date_to_week2(date("Y-m-d", strtotime("-0 day")));
            // 上月开始
            $last_month  = date("Y-m-01", strtotime('-1month')); 
            // 上月今天
            $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", time()); 
            // 本月开始
            $current_month  = date("Y-m-01", time()); 
            // 本月今天
            $today_date = date("Y-m-d", time());
        }

        // // 今天是星期几
        // $today =  date_to_week2(date("Y-m-d", strtotime("-0 day")));
        // // 上月开始
        // $last_month  = date("Y-m-01", strtotime('-1month')); 
        // // 上月今天
        // $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", time()); 
        // // 本月开始
        // $current_month  = date("Y-m-01", time()); 
        // // 本月今天
        // $today_date = date("Y-m-d", time());
     
        $sql1 = "
        SELECT
            a.省份,
            a.店铺名称,
            a.经营属性,
            b.RegionId,
            b.首单日期,
            '{$today_date}' AS 更新日期
        FROM
            `cwl_dianpuyejihuanbi_data` AS a
        LEFT JOIN customer_first as b ON a.店铺名称 = b.店铺名称
        WHERE
            a.日期 >= '{$current_month}' 
            AND a.日期 <= '{$today_date}' 
        GROUP BY
            a.店铺名称
        ";
        // 数据初始化开始
        $select_1 = $this->db_easyA->query($sql1);
        $delete_1 = $this->db_easyA->table('cwl_dianpuyejihuanbi_handle')->where([
            ['更新日期', '=', $today_date]
        ])->delete();
        $insert_1 = $this->db_easyA->table('cwl_dianpuyejihuanbi_handle')->insertAll($select_1);
        // 数据初始化结束
 

        $ym = date("Y-m", strtotime('-1month'));
        $select_dianpuyejihuanbi_lastmonth = $this->db_easyA->query("
            SELECT
                a.*,
                b.`首单日期` AS 首单日期 
            FROM
                cwl_dianpuyejihuanbi_lastmonth AS a
                LEFT JOIN cwl_dianpuyejihuanbi_handle AS b ON a.`店铺名称` = b.`店铺名称`
            WHERE 日期='{$ym}'   
        ");

        // dump($select_dianpuyejihuanbi_lastmonth);die;

        foreach ($select_dianpuyejihuanbi_lastmonth as $key => $val) {
            // $updateData = [];
            $updateData = $val;
            // dump($updateData);die;
            // 今日流水
            $find_dianpuyejihuanbi = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->where([
                ['日期', '=', $today_date],
                ['店铺名称', '=', $val['店铺名称']]
            ])->find();
 
            // 今日流水
            if ($find_dianpuyejihuanbi) {
                $updateData['今日流水'] = $find_dianpuyejihuanbi['销售金额'];
            } else {
                $updateData['今日流水'] = '';
            }

            // 今日环比： (今天的店铺流水 / 上个月周N平均值) -1
            if ($updateData['今日流水'] && $val[$today]) {
                $updateData['今日环比'] = ($updateData['今日流水'] / $val[$today]) - 1;
            } else {
                $updateData['今日环比'] = '';
            }

            // 环比流水: 上个月周n平均值
            $updateData['环比流水'] = $val[$today];

            // 本月累计流水：新店（5月5-22） 环比累计流水 ：新店（4月5-4月22）
            // 首单在上个月1号前
            if (strtotime($val['首单日期']) <= strtotime($last_month)) {
                // 本月累计流水
                $benyueliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $current_month],
                    ['日期', '<=', $today_date],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                // dump($select_benyueliushui);
                $updateData['本月累计流水'] = $benyueliushui['销售金额'];

                // 环比累计流水   
                $huanbiliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $last_month],
                    ['日期', '<=', $last_month_today],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                $updateData['环比累计流水'] = $huanbiliushui['销售金额'];
                // 月度环比： (本月累计流水 /环比累计流水 )- 1
                $updateData['月度环比'] = round(($updateData['本月累计流水'] / $updateData['环比累计流水']) - 1, 2);
            // 第5-22天 
            } elseif (strtotime($val['首单日期']) > strtotime($last_month) && strtotime($val['首单日期']) <= strtotime($last_month_today)) {
                $current_month_start = date("Y-m-", time()) . date('d', strtotime($val['首单日期']));
                // 本月累计流水
                $benyueliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $current_month_start],
                    ['日期', '<=', $today_date],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                // dump($select_benyueliushui);
                $updateData['本月累计流水'] = $benyueliushui['销售金额'];

                // 环比累计流水   
                $huanbiliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $val['首单日期']],
                    ['日期', '<=', $last_month_today],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                $updateData['环比累计流水'] = $huanbiliushui['销售金额'];
                // 月度环比： (本月累计流水 /环比累计流水 )- 1
                $updateData['月度环比'] = round(($updateData['本月累计流水'] / $updateData['环比累计流水']) - 1, 2);
            } else {
                // echo '其他情况';
            }    
            if (empty(@$updateData['环比流水']) || empty(@$updateData['环比累计流水'])) {
                $updateData['use'] = 0;
            } else {
                $updateData['use'] = 1;
            }


            // dump($updateData);die;
            $this->db_easyA->table('cwl_dianpuyejihuanbi_handle')->where([
                ['店铺名称', '=', $updateData['店铺名称']]
            ])->strict(false)->update($updateData);
        }
    }


    // 展示表数据计算
    public function dianpuyejihuanbi_handle_test() {
        $date = input('date') ? input('date') : '';
        if (! empty($date)) {
            // 今天是星期几
            $today = date_to_week2($date);
            // 上月开始
            $last_month  = date("Y-m-01", strtotime('-1month')); 
            // 上月今天
            $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", strtotime($date)); 
            // 本月开始
            $current_month  = date("Y-m-01", time()); 
            // 本月今天
            $today_date = $date;
        } else {
            // 今天是星期几
            $today =  date_to_week2(date("Y-m-d", strtotime("-0 day")));
            // 上月开始
            $last_month  = date("Y-m-01", strtotime('-1month')); 
            // 上月今天
            $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", time()); 
            // 本月开始
            $current_month  = date("Y-m-01", time()); 
            // 本月今天
            $today_date = date("Y-m-d", time());
        }

        // // 今天是星期几
        // $today =  date_to_week2(date("Y-m-d", strtotime("-0 day")));
        // // 上月开始
        // $last_month  = date("Y-m-01", strtotime('-1month')); 
        // // 上月今天
        // $last_month_today = date("Y-m", strtotime('-1month')) . date("-d", time()); 
        // // 本月开始
        // $current_month  = date("Y-m-01", time()); 
        // // 本月今天
        // $today_date = date("Y-m-d", time());
        
        $sql1 = "
        SELECT
            a.省份,
            a.店铺名称,
            a.经营属性,
            b.RegionId,
            b.首单日期,
            '{$today_date}' AS 更新日期
        FROM
            `cwl_dianpuyejihuanbi_data` AS a
        LEFT JOIN customer_first as b ON a.店铺名称 = b.店铺名称
        WHERE
            a.日期 >= '{$current_month}' 
            AND a.日期 <= '{$today_date}' 
        GROUP BY
            a.店铺名称
        ";
        // 数据初始化开始
        $select_1 = $this->db_easyA->query($sql1);
        $delete_1 = $this->db_easyA->table('cwl_dianpuyejihuanbi_handle_test')->where([
            ['更新日期', '=', $today_date]
        ])->delete();
        $insert_1 = $this->db_easyA->table('cwl_dianpuyejihuanbi_handle_test')->insertAll($select_1);
        // 数据初始化结束
    

        $ym = date("Y-m", strtotime('-1month'));

        // 获取上月数据
        $select_dianpuyejihuanbi_lastmonth = $this->db_easyA->query("
            SELECT
                a.*,
                b.`首单日期` AS 首单日期 
            FROM
                cwl_dianpuyejihuanbi_lastmonth AS a
                LEFT JOIN cwl_dianpuyejihuanbi_handle_test AS b ON a.`店铺名称` = b.`店铺名称`
            WHERE 日期='{$ym}'   
        ");

        // dump($select_dianpuyejihuanbi_lastmonth);die;

        foreach ($select_dianpuyejihuanbi_lastmonth as $key => $val) {
            // $updateData = [];
            $updateData = $val;
            // dump($updateData);die;
            // 今日流水
            $find_dianpuyejihuanbi = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->where([
                ['日期', '=', $today_date],
                ['店铺名称', '=', $val['店铺名称']]
            ])->find();
    
            // 今日流水
            if ($find_dianpuyejihuanbi) {
                $updateData['今日流水'] = $find_dianpuyejihuanbi['销售金额'];
            } else {
                $updateData['今日流水'] = '';
            }

            // 今日环比： (今天的店铺流水 / 上个月周N平均值) -1
            if ($updateData['今日流水'] && $val[$today]) {
                $updateData['今日环比'] = ($updateData['今日流水'] / $val[$today]) - 1;
            } else {
                $updateData['今日环比'] = '';
            }

            // 环比流水: 上个月周n平均值
            $updateData['环比流水'] = $val[$today];

            // 本月累计流水：新店（5月5-22） 环比累计流水 ：新店（4月5-4月22）
            // 首单在上个月1号前
            if (strtotime($val['首单日期']) <= strtotime($last_month)) {
                // 本月累计流水
                $benyueliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $current_month],
                    ['日期', '<=', $today_date],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                // dump($select_benyueliushui);
                $updateData['本月累计流水'] = $benyueliushui['销售金额'];

                // 环比累计流水   
                $huanbiliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $last_month],
                    ['日期', '<=', $last_month_today],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                $updateData['环比累计流水'] = $huanbiliushui['销售金额'];
                // 月度环比： (本月累计流水 /环比累计流水 )- 1
                $updateData['月度环比'] = round(($updateData['本月累计流水'] / $updateData['环比累计流水']) - 1, 2);
            // 第5-22天 
            } elseif (strtotime($val['首单日期']) > strtotime($last_month) && strtotime($val['首单日期']) <= strtotime($last_month_today)) {
                $current_month_start = date("Y-m-", time()) . date('d', strtotime($val['首单日期']));
                // 本月累计流水
                $benyueliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $current_month_start],
                    ['日期', '<=', $today_date],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                // dump($select_benyueliushui);
                $updateData['本月累计流水'] = $benyueliushui['销售金额'];

                // 环比累计流水   
                $huanbiliushui  = $this->db_easyA->table('cwl_dianpuyejihuanbi_data')->field("sum(销售金额) as 销售金额")->where([
                    ['日期', '>=', $val['首单日期']],
                    ['日期', '<=', $last_month_today],
                    ['店铺名称', '=', $val['店铺名称']]
                ])->group('店铺名称')->find();
                $updateData['环比累计流水'] = $huanbiliushui['销售金额'];
                // 月度环比： (本月累计流水 /环比累计流水 )- 1
                $updateData['月度环比'] = round(($updateData['本月累计流水'] / $updateData['环比累计流水']) - 1, 2);
            } else {
                // echo '其他情况';
            }    
            if (empty(@$updateData['环比流水']) || empty(@$updateData['环比累计流水'])) {
                $updateData['use'] = 0;
            } else {
                $updateData['use'] = 1;
            }


            // dump($updateData);die;
            $this->db_easyA->table('cwl_dianpuyejihuanbi_handle_test')->where([
                ['店铺名称', '=', $updateData['店铺名称']]
            ])->strict(false)->update($updateData);
        }
    }
}
