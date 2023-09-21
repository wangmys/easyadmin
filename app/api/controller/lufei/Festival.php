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
 * @ControllerAnnotation(title="节日报表跑数")
 * 
 * duanwu_data2 需要提前跑数
 */
class Festival extends BaseController
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

    // 活动日期 单天 节日日期
    protected $节日日期_2023 = '';
    protected $节日日期_2022 = '';
    protected $节日日期_2021 = '';
    protected $节日天数 = '';
    protected $节日 = '';
    

    // 活动日期 数组，同比天数
    protected $festivalArr = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');

        $select_festivalDate = $this->db_easyA->table('cwl_festival_config')->where([
            ['节日', '=', '国庆']
        ])->select();
        $this->festivalArr = $select_festivalDate;    

        $date = date('Y-m-d');

        // 测试
        $date = '2023-09-18';

        $date_res = $this->isFestavalDate($date);
        if ($date_res) {
            $this->节日日期_2023 = $date_res['节日日期'];
            // echo '<br>';
            $this->节日天数 = $date_res['节日天数'];
            $this->节日 = $date_res['节日'];

            $find_2022 = $this->db_easyA->table('cwl_festival_config')->where([
                ['节日', '=', $this->节日],
                ['节日天数', '=', $this->节日天数],
                ['年份', '=', 2022],
            ])->find();

            $find_2021 = $this->db_easyA->table('cwl_festival_config')->where([
                ['节日', '=', $this->节日],
                ['节日天数', '=', $this->节日天数],
                ['年份', '=', 2021],
            ])->find();
            $this->节日日期_2022 = $find_2022['节日日期'];
            $this->节日日期_2021 = $find_2021['节日日期'];
        } else {
            echo '国庆活动已结束';
            die;
        }
        // if ($date == '2023-09-17' || $date == '2023-09-18' || $date == '2023-09-19') {
        //     $this->节日日期 = $date;
        // } else {
        //     echo '国庆活动已结束';
        //     die;
        // }
    }

    // 判断是不是节日日期
    public function isFestavalDate($date) {
        $current_year = date('Y');
        foreach ($this->festivalArr as $key => $val) {
            if ($date == $val['节日日期']) {
                return ['节日日期' => $val['节日日期'], '节日天数' => $val['节日天数'], '节日' => $val['节日']];
                break;
            }
        }
        return false;
    }

    // 跑数入口 1 cwl_festival_retail_data
    public function duanwu_data_handle1() {

        // 跑数据源
        $this->festival_data(true);

        // die;
        $this->createTable1();
        $this->createTable2();

        die;


        // echo '<pre>';
        // $this->duanwu_data(true);

        // echo $this->节日日期_2021;die;
        if ($this->festival_date == '2023-09-17') {
            $this->duanwu_handle_1day();
            $this->createTable2(1);
            $this->createTable3(1);
            $this->createTable4(1);
        } elseif ($this->festival_date == '2023-09-18') {
            $this->duanwu_handle_2day();
            $this->createTable2(2);
            $this->createTable3(2);
            $this->createTable4(2);
        } elseif ($this->festival_date == '2023-09-19') {
            $this->duanwu_handle_3day();
            $this->createTable2(3);
            $this->createTable3(3);
            $this->createTable4(3);
        }
    }

    // 节日3年数据源
    public function festival_data($computer = false) {
        if ($computer) {
            $date = $this->节日日期_2023;
        } else {
            $date = input('date');
        }
        
        // echo $this->节日;die;
        if (! empty($date)) {

            $select_festivalDate = $this->db_easyA->table('cwl_festival_config')->where([
                ['节日', '=', $this->节日],
                ['节日天数', '=', $this->节日天数]
            ])->select();

            // dump($select_festivalDate);die;

            // 跑3年活动天数数据
            foreach ($select_festivalDate as $k => $v) {
                // 康雷查询周销
                $sql = "         
                    SELECT
                        SUBSTRING(EC.State, 1, 2)  AS 省份,
                        ER.CustomerName AS 店铺名称,
                        (
                            SELECT TOP 
                                1 
                                FORMAT(RetailDate, 'yyyy-MM-dd') AS FormattedDate
                            FROM
                                ErpRetail  
                            WHERE CustomerName = ER.CustomerName
                        ) AS 首单日期,
                        EBC.Mathod AS 经营属性,
                        '{$v['节日']}' as 节日,
                        '{$v['节日日期']}' AS 节日日期,   
                        '{$v['节日天数']}' AS 节日天数,
                        SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额
                    FROM
                        ErpRetail AS ER 
                    LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                    LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                    LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                    WHERE
                        ER.RetailDate >= '{$v['节日日期']}' 
                        AND ER.RetailDate < DATEADD( DAY, +1, '{$v['节日日期']}' ) 
                        AND ER.CodingCodeText = '已审结'
                        AND EC.ShutOut = 0
                        AND EC.RegionId <> 55
                        AND EC.CustomerName NOT LIKE '%内购%'
                        AND EC.RegionId NOT IN ('8','40', '84', '85',  '97')
                        AND EBC.Mathod IN ('直营', '加盟')     
                        AND ER.CustomerName NOT IN ('湘潭二店')      
                    GROUP BY 
                        ER.CustomerName,
                        EC.State,
                        EBC.Mathod	
                    ORDER BY EC.State ASC
                ";
      
                

                $select = $this->db_sqlsrv->query($sql);
                $count = count($select);
        
                if ($select) {
                    // 删除
                    $this->db_easyA->table('cwl_festival_retail_data')->where([
                        ['节日日期', '=', $v['节日日期']]
                    ])->delete();
                    // $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');
        
                    $chunk_list = array_chunk($select, 500);
        
                    foreach($chunk_list as $key => $val) {
                        // 基础结果 
                        $insert = $this->db_easyA->table('cwl_festival_retail_data')->strict(false)->insertAll($val);
                    }
        
                }               
            }

        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "cwl_festival_retail_data 更新失败，节日日期不能为空！"
            ]);  
        }

    }

    // 端午同比
    // public function duanwu_data($computer = false) {
    //     if ($computer) {
    //         $date = $this->festival_date;
    //     } else {
    //         $date = input('date');
    //     }
        
    //     if (! empty($date)) {
    //         // 康雷查询周销
    //         $sql = "   
    //             declare @retail_date DATE
    //             set @retail_date = '{$date}'
                
    //             SELECT
    //                 SUBSTRING(EC.State, 1, 2)  AS 省份,
    //                 ER.CustomerName AS 店铺名称,
    //                 (
    //                     SELECT TOP 
    //                         1 
    //                         FORMAT(RetailDate, 'yyyy-MM-dd') AS FormattedDate
    //                     FROM
    //                         ErpRetail  
    //                     WHERE CustomerName = ER.CustomerName
    //                 ) AS 首单日期,
    //                 EBC.Mathod AS 经营属性,
    //                 '端午' as 节日,
    //                 @retail_date AS 节日日期,   
    //                 CASE
    //                     @retail_date
    //                     WHEN '2021-09-17' THEN '1'
    //                     WHEN '2021-09-18' THEN '2'
    //                     WHEN '2021-09-19' THEN '3'

    //                     WHEN '2022-09-17' THEN '1'
    //                     WHEN '2022-09-18' THEN '2'
    //                     WHEN '2022-09-19' THEN '3'
    //                     -- 测试用，今年端午假设
    //                     WHEN '2023-09-17' THEN '1'
    //                     WHEN '2023-09-18' THEN '2'
    //                     WHEN '2023-09-19' THEN '3'
    //                 END AS 节日天数,
    //                 SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额
    //             FROM
    //                 ErpRetail AS ER 
    //             LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
    //             LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
    //             LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
    //             WHERE
    //                 ER.RetailDate >= @retail_date 
    //                 AND ER.RetailDate < DATEADD( DAY, +1, @retail_date ) 
    //                 AND ER.CodingCodeText = '已审结'
    //                 AND EC.ShutOut = 0
    //                 AND EC.RegionId <> 55
    //                 AND EC.CustomerName NOT LIKE '%内购%'
    //                 AND EC.RegionId NOT IN ('8','40', '84', '85',  '97')
    //                 AND EBC.Mathod IN ('直营', '加盟')         
    //             GROUP BY 
    //                 ER.CustomerName,
    //                 EC.State,
    //                 EBC.Mathod	
    //             ORDER BY EC.State ASC
    //         ";

    //         $select = $this->db_sqlsrv->query($sql);
    //         $count = count($select);
    
    //         if ($select) {
    //             // 删除
    //             $this->db_easyA->table('cwl_festival_retail_data')->where([
    //                 ['节日日期', '=', $date]
    //             ])->delete();
    //             // $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');
    
    //             $chunk_list = array_chunk($select, 500);
    
    //             foreach($chunk_list as $key => $val) {
    //                 // 基础结果 
    //                 $insert = $this->db_easyA->table('cwl_festival_retail_data')->strict(false)->insertAll($val);
    //             }
    
    //             return json([
    //                 'status' => 1,
    //                 'msg' => 'success',
    //                 'content' => "cwl_festival_retail_data 更新成功，数量：{$count}！"
    //             ]);
    //         } else {
    //             return json([
    //                 'status' => 0,
    //                 'msg' => 'error',
    //                 'content' => "cwl_festival_retail_data 更新失败，请稍后再试！"
    //             ]);  
    //         }
    //     } else {
    //         return json([
    //             'status' => 0,
    //             'msg' => 'error',
    //             'content' => "cwl_festival_retail_data 更新失败，节日日期不能为空！"
    //         ]);  
    //     }

    // }

    // 端午同比  表二专用数据源
    // public function duanwu_data2($date = '') {
    //     $date = input('date');
    //     if (! empty($date)) {
    //         // 康雷查询周销
    //         $sql = "   
    //             declare @retail_date DATE
    //             set @retail_date = '{$date}'
                
    //             SELECT
    //                 SUBSTRING(EC.State, 1, 2)  AS 省份,
    //                 ER.CustomerName AS 店铺名称,
    //                 (
    //                     SELECT TOP 
    //                         1 
    //                         FORMAT(RetailDate, 'yyyy-MM-dd') AS FormattedDate
    //                     FROM
    //                         ErpRetail  
    //                     WHERE CustomerName = ER.CustomerName
    //                 ) AS 首单日期,
    //                 EBC.Mathod AS 经营属性,
    //                 '端午' as 节日,
    //                 @retail_date AS 节日日期,   
    //                 CASE
    //                     @retail_date
    //                     WHEN '2021-09-17' THEN '1'
    //                     WHEN '2021-09-18' THEN '2'
    //                     WHEN '2021-09-19' THEN '3'

    //                     WHEN '2022-09-17' THEN '1'
    //                     WHEN '2022-09-18' THEN '2'
    //                     WHEN '2022-09-19' THEN '3'
    //                     -- 测试用，今年端午假设
    //                     WHEN '2023-09-17' THEN '1'
    //                     WHEN '2023-09-18' THEN '2'
    //                     WHEN '2023-09-19' THEN '3'
    //                 END AS 节日天数,
    //                 SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额
    //             FROM
    //                 ErpRetail AS ER 
    //             LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
    //             LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
    //             LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
    //             WHERE
    //                 ER.RetailDate >= @retail_date 
    //                 AND ER.RetailDate < DATEADD( DAY, +1, @retail_date ) 
    //                 AND ER.CodingCodeText = '已审结'
    //                 AND EC.ShutOut = 0
    //                 -- AND EC.RegionId <> 55
    //                 AND EC.CustomerName NOT LIKE '%内购%'
    //                 AND EC.RegionId NOT IN ('8','40', '84', '85',  '97')
    //                 AND EBC.Mathod IN ('直营', '加盟')         
    //             GROUP BY 
    //                 ER.CustomerName,
    //                 EC.State,
    //                 EBC.Mathod	
    //             ORDER BY EC.State ASC
    //         ";

    //         $select = $this->db_sqlsrv->query($sql);
    //         $count = count($select);
    
    //         if ($select) {
    //             // 删除
    //             $this->db_easyA->table('cwl_festival_retail_data_2')->where([
    //                 ['节日日期', '=', $date]
    //             ])->delete();
    //             // $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');
    
    //             $chunk_list = array_chunk($select, 500);
    
    //             foreach($chunk_list as $key => $val) {
    //                 // 基础结果 
    //                 $insert = $this->db_easyA->table('cwl_festival_retail_data_2')->strict(false)->insertAll($val);
    //             }
    
    //             return json([
    //                 'status' => 1,
    //                 'msg' => 'success',
    //                 'content' => "cwl_festival_retail_data_2 更新成功，数量：{$count}！"
    //             ]);
    //         }
    //     } else {
    //         return json([
    //             'status' => 0,
    //             'msg' => 'error',
    //             'content' => "cwl_festival_retail_data_2 更新失败，节日日期不能为空！"
    //         ]);  
    //     }

    // }

    // 节日 表1
    public function createTable1($fday = '') {
        // $date = '2023-09-17';
        // $fday = 1; // 活动第几天
        if (! empty($this->节日天数)) {
            // $this->db_easyA->table('cwl_festival_statistics')->where([
            //     ['节日天数', '=', $this->节日天数 - 1],
            //     ['店铺', '=', $this->节日天数],
            // ])->find();
            $sql = "   
                SELECT
                    m.*,
                    m.`销售金额2023` / m.`销售金额2021` -1 AS `前年日增长`, 
                    m.`销售金额2023` / m.`销售金额2022` -1 AS `去年日增长`,
                    m.`销售金额2023` / m.`销售金额2021` -1 AS `前年累计增长`, 
                    m.`销售金额2023` / m.`销售金额2022` -1 AS `去年累计增长`,
                    case
                        when m.节日天数 > 1 then m.`销售金额2021` + (select 前年累销额 from cwl_festival_statistics where 店铺名称=m.店铺名称 and 节日天数 = m.节日天数-1 ) else m.`销售金额2021`
                    end AS 前年累销额,
                    case
                        when m.节日天数 > 1 then m.`销售金额2022` + (select 去年累销额 from cwl_festival_statistics where 店铺名称=m.店铺名称 and 节日天数 = m.节日天数-1 ) else m.`销售金额2022`
                    end AS 去年累销额,
                    case
                        when m.节日天数 > 1 then m.`销售金额2023` + (select 今年累销额 from cwl_festival_statistics where 店铺名称=m.店铺名称 and 节日天数 = m.节日天数-1 ) else m.`销售金额2023`
                    end AS 今年累销额
                FROM
                    (
                    SELECT
                        t0.省份,
                        t0.店铺名称,
                        t0.首单日期,
                        t0.经营属性,
                        '国庆' AS 节日,
                        t0.节日日期,
                        t0.节日天数,
                        t0.销售金额 AS `销售金额2023`,
                        t1.销售金额 AS `销售金额2022`,
                        t2.销售金额 AS `销售金额2021` 
                    FROM
                        `cwl_festival_retail_data` AS t0
                        LEFT JOIN cwl_festival_retail_data AS t1 ON t0.店铺名称 = t1.店铺名称 
                        AND t1.节日日期 = '{$this->节日日期_2022}' 
                        AND t1.节日天数 = t0.节日天数
                        LEFT JOIN cwl_festival_retail_data AS t2 ON t0.店铺名称 = t2.店铺名称 
                        AND t2.节日日期 = '{$this->节日日期_2021}' 
                        AND t2.节日天数 = t0.节日天数 
                    WHERE
                        t0.节日日期 = '{$this->节日日期_2023}' 
                        AND t0.节日天数 = '{$this->节日天数}' 
                    GROUP BY
                        `店铺名称` 
                    ORDER BY
                        t0.省份 ASC 
                    ) AS m 
                WHERE
                    m.`销售金额2022` IS NOT NULL || m.`销售金额2021` IS NOT NULL
            ";

            $select = $this->db_easyA->query($sql);
            $count = count($select);
    
            if ($select) {
                // 删除
                $this->db_easyA->table('cwl_festival_statistics')->where([
                    ['节日日期', '=', $this->节日日期_2023]
                ])->delete();
                // $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');
    
                $chunk_list = array_chunk($select, 500);
    
                foreach($chunk_list as $key => $val) {
                    // 基础结果 
                    $insert = $this->db_easyA->table('cwl_festival_statistics')->strict(false)->insertAll($val);
                }
    
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_festival_statistics 更新成功，数量：{$count}！"
                ]);
            }
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "cwl_festival_statistics 更新失败，活动第几天不能为空！"
            ]);  
        }

    }

    public function createTable2() {
        $this->db_easyA->table('cwl_festival_statistics_province')->where([
            ['节日天数', '=', $this->节日天数]
        ])->delete();

        // 加盟总省
        $sql_jiameng = "
            select t.
                t.省份,t.经营属性,t.节日日期,节日天数,
                t.今日销额同比前年 / t.前年同日销额 - 1 AS 前年日增长,
                t.今日销额同比去年 / t.去年同日销额 - 1 AS 去年日增长,
                t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
                t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
                t.前年同日销额,
                t.去年同日销额,
                t.今日销额,
                t.今日销额同比去年,
                t.今日销额同比前年,
                t.前年累销额,
                t.去年累销额,
                t.今年累销额
            from(
                SELECT
                    省份,经营属性,节日日期,节日天数,
                    sum(销售金额2021) as 前年同日销额,
                    sum(销售金额2022) as 去年同日销额,
                    sum(销售金额2023) as 今日销额,
                    (SELECT
                        sum(`销售金额2023`) 
                        FROM
                            cwl_festival_statistics 
                        WHERE
                            节日天数 = m.节日天数
                            AND 经营属性 = m.经营属性 
                            AND 省份 = m.省份
                            AND `销售金额2022` is not null
                    ) as 今日销额同比去年,
                    (SELECT
                        sum(`销售金额2023`) 
                        FROM
                            cwl_festival_statistics 
                        WHERE
                            节日天数 = m.节日天数
                            AND 经营属性 = m.经营属性 
                            AND 省份 = m.省份
                            AND `销售金额2021` is not null
                    ) as 今日销额同比前年,
                    sum(前年累销额) as 前年累销额,	
                    sum(去年累销额) as 去年累销额,
                    sum(今年累销额) as 今年累销额
                FROM
                    cwl_festival_statistics as m
                WHERE
                    节日天数 = '{$this->节日天数}' 
                    AND 经营属性 in ('加盟')
                group BY
                    省份, 经营属性
                order by 经营属性
            ) as t
        ";
        // 查    
        $select_jiameng = $this->db_easyA->query($sql_jiameng);
        // 插        
        $insert_jiameng = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_jiameng);
    
        // 加盟合计 
        $sql_jiameng_total = "
            select 
                '合计' AS 省份,
                '加盟' AS 经营属性,
                t.节日日期,t.节日天数,
                t.今日销额同比前年 / t.前年同日销额 - 1 AS 前年日增长,
                t.今日销额同比去年 / t.去年同日销额 - 1 AS 去年日增长,
                t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
                t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
                t.前年同日销额,
                t.去年同日销额,
                t.今日销额,
                t.今日销额同比前年,
                t.今日销额同比去年,
                t.前年累销额,
                t.去年累销额,
                t.今年累销额
            from
            (select 
                经营属性,节日日期,节日天数,
                sum(前年同日销额) AS 前年同日销额,
                sum(去年同日销额) AS 去年同日销额,
                sum(今日销额) AS 今日销额,
                sum(今日销额同比去年) AS 今日销额同比去年,
                sum(今日销额同比前年) as 今日销额同比前年,
                sum(前年累销额) AS 前年累销额,
                sum(去年累销额) AS 去年累销额,
                sum(今年累销额) AS 今年累销额
            from cwl_festival_statistics_province where 节日天数= {$this->节日天数} and 经营属性 in ('加盟')) as t       
        ";

        // 加盟合计 查
        $select_jiameng_total = $this->db_easyA->query($sql_jiameng_total);
        // 加盟合计 删
        $insert_jiameng_total = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_jiameng_total);
        
        
        // 直营总省
        $sql_zhiying = "
            select t.
                t.省份,t.经营属性,t.节日日期,节日天数,
                t.今日销额同比前年 / t.前年同日销额 - 1 AS 前年日增长,
                t.今日销额同比去年 / t.去年同日销额 - 1 AS 去年日增长,
                t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
                t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
                t.前年同日销额,
                t.去年同日销额,
                t.今日销额,
                t.今日销额同比去年,
                t.今日销额同比前年,
                t.前年累销额,
                t.去年累销额,
                t.今年累销额
            from(
                SELECT
                    省份,经营属性,节日日期,节日天数,
                    sum(销售金额2021) as 前年同日销额,
                    sum(销售金额2022) as 去年同日销额,
                    sum(销售金额2023) as 今日销额,
                    (SELECT
                        sum(`销售金额2023`) 
                        FROM
                            cwl_festival_statistics 
                        WHERE
                            节日天数 = m.节日天数
                            AND 经营属性 = m.经营属性 
                            AND 省份 = m.省份
                            AND `销售金额2021` is not null
                    ) as 今日销额同比前年,
                    (SELECT
                        sum(`销售金额2023`) 
                        FROM
                            cwl_festival_statistics 
                        WHERE
                            节日天数 = m.节日天数
                            AND 经营属性 = m.经营属性 
                            AND 省份 = m.省份
                            AND `销售金额2022` is not null
                    ) as 今日销额同比去年,
                    sum(前年累销额) as 前年累销额,	
                    sum(去年累销额) as 去年累销额,
                    sum(今年累销额) as 今年累销额
                FROM
                    cwl_festival_statistics as m 
                WHERE
                    节日天数 = '{$this->节日天数}' 
                    AND 经营属性 in ('直营')
                group BY
                    省份, 经营属性
                order by 经营属性
            ) as t
        ";
        // 查    
        $select_zhiying = $this->db_easyA->query($sql_zhiying);
        // 插        
        $insert_zhiying = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_zhiying);
        
        // 直营合计 
        $sql_zhiying_total = "
            select 
                '合计' AS 省份,
                '直营' AS 经营属性,
                t.节日日期,t.节日天数,
                t.今日销额同比前年 / t.前年同日销额 - 1 AS 前年日增长,
                t.今日销额同比去年 / t.去年同日销额 - 1 AS 去年日增长,
                t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
                t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
                t.前年同日销额,
                t.去年同日销额,
                t.今日销额,
                t.今日销额同比前年,
                t.今日销额同比去年,
                t.前年累销额,
                t.去年累销额,
                t.今年累销额
            from
            (select 
                经营属性,节日日期,节日天数,
                sum(前年同日销额) AS 前年同日销额,
                sum(去年同日销额) AS 去年同日销额,
                sum(今日销额) AS 今日销额,
                sum(今日销额同比前年) as 今日销额同比前年,
                sum(今日销额同比去年) as 今日销额同比去年,
                sum(前年累销额) AS 前年累销额,
                sum(去年累销额) AS 去年累销额,
                sum(今年累销额) AS 今年累销额
            from cwl_festival_statistics_province where 节日天数= {$this->节日天数} and 经营属性 in ('直营')) as t       
        ";

        // 直营合计 查
        $select_zhiying_total = $this->db_easyA->query($sql_zhiying_total);
        // 直营合计 插
        $insert_jiameng_total = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_zhiying_total);
        
        
        // 直营加盟总计
        $sql_zongji = "
            select 
                '总计' AS 省份,
                '' AS 经营属性,
                t.节日日期,t.节日天数,
                t.今日销额同比前年 / t.前年同日销额 - 1 AS 前年日增长,
                t.今日销额同比去年 / t.去年同日销额 - 1 AS 去年日增长,
                t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
                t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
                t.前年同日销额,
                t.去年同日销额,
                t.今日销额,
                t.今日销额同比前年,
                t.今日销额同比去年,
                t.前年累销额,
                t.去年累销额,
                t.今年累销额
            from
            (select 
                经营属性,节日日期,节日天数,
                sum(前年同日销额) AS 前年同日销额,
                sum(去年同日销额) AS 去年同日销额,
                sum(今日销额) AS 今日销额,
                sum(今日销额同比前年) as 今日销额同比前年,
                sum(今日销额同比去年) as 今日销额同比去年,
                sum(前年累销额) AS 前年累销额,
                sum(去年累销额) AS 去年累销额,
                sum(今年累销额) AS 今年累销额
            from cwl_festival_statistics_province 
                where 节日天数= {$this->节日天数} 
                and 经营属性 in ('直营', '加盟')
                and 省份 not in ('合计')
            ) as t       
        ";

        // 直营合计 查
        $select_zongji = $this->db_easyA->query($sql_zongji);
        // 直营合计 插
        $insert_zongji  = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_zongji);

        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => "cwl_festival_statistics_province 更新成功！"
        ]);
    }

    // 端午同比 第一天
    // public function duanwu_handle_1day($fday = '') {
    //     $date = '2023-09-17';
    //     $fday = 1; // 活动第几天
    //     if (! empty($fday)) {
    //         $sql = "   
    //             SELECT
    //                 m.*,
    //                 m.销售金额2023 / m.销售金额2021 -1 AS `前年日增长`, 
    //                 m.销售金额2023 / m.销售金额2022 -1 AS `去年日增长`,
    //                 m.销售金额2023 / m.销售金额2021 -1 AS `前年累计增长`, 
    //                 m.销售金额2023 / m.销售金额2022 -1 AS `去年累计增长`,
    //                 m.销售金额2021 as 前年累销额,
    //                 m.销售金额2022 as 去年累销额,
    //                 m.销售金额2023 as 今年累销额
    //             FROM
    //                 (
    //                 SELECT
    //                     t0.省份,
    //                     t0.店铺名称,
    //                     t0.首单日期,
    //                     t0.经营属性,
    //                     '国庆' AS 节日,
    //                     t0.节日日期,
    //                     t0.节日天数,
    //                     t0.销售金额 AS `销售金额2023`,
    //                     t1.销售金额 AS `销售金额2022`,
    //                     t2.销售金额 AS `销售金额2021` 
    //                 FROM
    //                     `cwl_festival_retail_data` AS t0
    //                     LEFT JOIN cwl_festival_retail_data AS t1 ON t0.店铺名称 = t1.店铺名称 
    //                     AND t1.节日日期 = '2022-09-17' 
    //                     AND t1.节日天数 = t0.节日天数
    //                     LEFT JOIN cwl_festival_retail_data AS t2 ON t0.店铺名称 = t2.店铺名称 
    //                     AND t2.节日日期 = '2021-09-17' 
    //                     AND t2.节日天数 = t0.节日天数 
    //                 WHERE
    //                     t0.节日日期 = '{$date}' 
    //                     AND t0.节日天数 = '{$fday}' 
    //                 GROUP BY
    //                     `店铺名称` 
    //                 ORDER BY
    //                     t0.省份 ASC 
    //                 ) AS m 
    //             WHERE
    //                 m.`销售金额2022` IS NOT NULL || m.`销售金额2021` IS NOT NULL
    //         ";

    //         $select = $this->db_easyA->query($sql);
    //         $count = count($select);
    
    //         if ($select) {
    //             // 删除
    //             $this->db_easyA->table('cwl_festival_statistics')->where([
    //                 ['节日日期', '=', $date]
    //             ])->delete();
    //             // $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');
    
    //             $chunk_list = array_chunk($select, 500);
    
    //             foreach($chunk_list as $key => $val) {
    //                 // 基础结果 
    //                 $insert = $this->db_easyA->table('cwl_festival_statistics')->strict(false)->insertAll($val);
    //             }
    
    //             return json([
    //                 'status' => 1,
    //                 'msg' => 'success',
    //                 'content' => "cwl_festival_statistics 更新成功，数量：{$count}！"
    //             ]);
    //         }
    //     } else {
    //         return json([
    //             'status' => 0,
    //             'msg' => 'error',
    //             'content' => "cwl_festival_statistics 更新失败，活动第几天不能为空！"
    //         ]);  
    //     }

    // }

    // // 端午同比 第二天
    // public function duanwu_handle_2day($fday = '') {
    //     $date = '2023-09-18';
    //     $fday = 2; // 活动第几天
    //     if (! empty($fday)) {
    //         $sql = "   
    //             select 
    //                 m0.*, 
    //                 m0.今年累销额 / m0.前年累销额 - 1 AS `前年累计增长`,
    //                 m0.今年累销额 / m0.去年累销额 - 1 AS `去年累计增长`
    //             from (
    //             SELECT
    //                     m1.*,
    //                     m1.销售金额2023 / m1.销售金额2021 -1 AS `前年日增长`, 
    //                     m1.销售金额2023 / m1.销售金额2022 -1 AS `去年日增长`,
    //                     (select sum(销售金额) from cwl_festival_retail_data where 店铺名称=m1.店铺名称 and 节日日期 in ('2021-09-17', '2021-09-18')) as 前年累销额,
    //                     (select sum(销售金额) from cwl_festival_retail_data where 店铺名称=m1.店铺名称 and 节日日期 in ('2022-09-17', '2022-09-18')) as 去年累销额,
    //                     (select sum(销售金额) from cwl_festival_retail_data where 店铺名称=m1.店铺名称 and 节日日期 in ('2023-09-17', '2023-06-18')) as 今年累销额
    //             FROM
    //                     (
    //                     SELECT
    //                             t0.省份,
    //                             t0.店铺名称,
    //                             t0.首单日期,
    //                             t0.经营属性,
    //                             '端午' AS 节日,
    //                             t0.节日日期,
    //                             t0.节日天数,
    //                             t0.销售金额 AS `销售金额2023`,
    //                             t1.销售金额 AS `销售金额2022`,
    //                             t2.销售金额 AS `销售金额2021` 
    //                     FROM
    //                             `cwl_festival_retail_data` AS t0
    //                             LEFT JOIN cwl_festival_retail_data AS t1 ON t0.店铺名称 = t1.店铺名称 
    //                             AND t1.节日日期 = '2022-09-18' 
    //                             AND t1.节日天数 = t0.节日天数
    //                             LEFT JOIN cwl_festival_retail_data AS t2 ON t0.店铺名称 = t2.店铺名称 
    //                             AND t2.节日日期 = '2021-09-18' 
    //                             AND t2.节日天数 = t0.节日天数 
    //                     WHERE
    //                             t0.节日日期 = '{$date}' 
    //                             AND t0.节日天数 = 2 
    //                     GROUP BY
    //                             `店铺名称` 
    //                     ORDER BY
    //                             t0.省份 ASC 
    //                     ) AS m1 
    //             WHERE
    //                     m1.`销售金额2022` IS NOT NULL || m1.`销售金额2021` IS NOT NULL
    //             ) as m0
    //         ";

    //         $select = $this->db_easyA->query($sql);
    //         $count = count($select);
    
    //         if ($select) {
    //             // 删除
    //             $this->db_easyA->table('cwl_festival_statistics')->where([
    //                 ['节日日期', '=', $date]
    //             ])->delete();
    //             // $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');
    
    //             $chunk_list = array_chunk($select, 500);
    
    //             foreach($chunk_list as $key => $val) {
    //                 // 基础结果 
    //                 $insert = $this->db_easyA->table('cwl_festival_statistics')->strict(false)->insertAll($val);
    //             }
    
    //             return json([
    //                 'status' => 1,
    //                 'msg' => 'success',
    //                 'content' => "cwl_festival_statistics 更新成功，数量：{$count}！"
    //             ]);
    //         } else {
    //             echo $sql;
    //         }
    //     } else {
    //         return json([
    //             'status' => 0,
    //             'msg' => 'error',
    //             'content' => "cwl_festival_statistics 更新失败，活动第几天不能为空！"
    //         ]);  
    //     }

    // }


    // // 端午同比 第3天
    // public function duanwu_handle_3day($fday = '') {
    //     $date = '2023-09-19';
    //     $fday = 3; // 活动第几天
    //     if (! empty($fday)) {
    //         $sql = "   
    //             select 
    //                 m0.*, 
    //                 m0.今年累销额 / m0.前年累销额 - 1 AS `前年累计增长`,
    //                 m0.今年累销额 / m0.去年累销额 - 1 AS `去年累计增长`
    //             from (
    //             SELECT
    //                     m1.*,
    //                     m1.销售金额2023 / m1.销售金额2021 -1 AS `前年日增长`, 
    //                     m1.销售金额2023 / m1.销售金额2022 -1 AS `去年日增长`,
    //                     (select sum(销售金额) from cwl_festival_retail_data where 店铺名称=m1.店铺名称 and 节日日期 in ('2021-09-17', '2021-09-18', '2021-09-19')) as 前年累销额,
    //                     (select sum(销售金额) from cwl_festival_retail_data where 店铺名称=m1.店铺名称 and 节日日期 in ('2022-09-17', '2022-09-18', '2022-09-19')) as 去年累销额,
    //                     (select sum(销售金额) from cwl_festival_retail_data where 店铺名称=m1.店铺名称 and 节日日期 in ('2023-09-17', '2023-09-18', '2023-09-19')) as 今年累销额
    //             FROM
    //                     (
    //                     SELECT
    //                             t0.省份,
    //                             t0.店铺名称,
    //                             t0.首单日期,
    //                             t0.经营属性,
    //                             '国庆' AS 节日,
    //                             t0.节日日期,
    //                             t0.节日天数,
    //                             t0.销售金额 AS `销售金额2023`,
    //                             t1.销售金额 AS `销售金额2022`,
    //                             t2.销售金额 AS `销售金额2021` 
    //                     FROM
    //                             `cwl_festival_retail_data` AS t0
    //                             LEFT JOIN cwl_festival_retail_data AS t1 ON t0.店铺名称 = t1.店铺名称 
    //                             AND t1.节日日期 = '2022-09-19' 
    //                             AND t1.节日天数 = t0.节日天数
    //                             LEFT JOIN cwl_festival_retail_data AS t2 ON t0.店铺名称 = t2.店铺名称 
    //                             AND t2.节日日期 = '2021-09-19' 
    //                             AND t2.节日天数 = t0.节日天数 
    //                     WHERE
    //                             t0.节日日期 = '{$date}' 
    //                             AND t0.节日天数 = 3 
    //                     GROUP BY
    //                             `店铺名称` 
    //                     ORDER BY
    //                             t0.省份 ASC 
    //                     ) AS m1 
    //             WHERE
    //                     m1.`销售金额2022` IS NOT NULL || m1.`销售金额2021` IS NOT NULL
    //             ) as m0
    //         ";

    //         $select = $this->db_easyA->query($sql);
    //         $count = count($select);
    
    //         if ($select) {
    //             // 删除
    //             $this->db_easyA->table('cwl_festival_statistics')->where([
    //                 ['节日日期', '=', $date]
    //             ])->delete();
    //             // $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');
    
    //             $chunk_list = array_chunk($select, 500);
    
    //             foreach($chunk_list as $key => $val) {
    //                 // 基础结果 
    //                 $insert = $this->db_easyA->table('cwl_festival_statistics')->strict(false)->insertAll($val);
    //             }
    
    //             return json([
    //                 'status' => 1,
    //                 'msg' => 'success',
    //                 'content' => "cwl_festival_statistics 更新成功，数量：{$count}！"
    //             ]);
    //         } else {
    //             echo $sql;
    //         }
    //     } else {
    //         return json([
    //             'status' => 0,
    //             'msg' => 'error',
    //             'content' => "cwl_festival_statistics 更新失败，活动第几天不能为空！"
    //         ]);  
    //     }

    // }

    // public function createTable2($fday) {
    //     if (! $fday) {
    //         $fday = input('fday');
    //     }
        
    //     if ($fday == 1 || $fday == 2 || $fday == 3 ) {
    //         $this->db_easyA->table('cwl_festival_statistics_province')->where([
    //             ['节日天数', '=', $fday]
    //         ])->delete();
    
    //         // 加盟总省
    //         $sql_jiameng = "
    //             select t.
    //                 t.省份,t.经营属性,t.节日日期,节日天数,
    //                 t.今日销额 / t.前年同日销额 - 1 AS 前年日增长,
    //                 t.今日销额 / t.去年同日销额 - 1 AS 去年日增长,
    //                 t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
    //                 t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
    //                 t.前年同日销额,
    //                 t.去年同日销额,
    //                 t.今日销额,
    //                 t.前年累销额,
    //                 t.去年累销额,
    //                 t.今年累销额
    //             from(
    //                 SELECT
    //                     省份,经营属性,节日日期,节日天数,
    //                     sum(销售金额2021) as 前年同日销额,
    //                     sum(销售金额2022) as 去年同日销额,
    //                     sum(销售金额2023) as 今日销额,
    //                     sum(前年累销额) as 前年累销额,	
    //                     sum(去年累销额) as 去年累销额,
    //                     sum(今年累销额) as 今年累销额
    //                 FROM
    //                     cwl_festival_statistics 
    //                 WHERE
    //                     节日天数 = '{$fday}' 
    //                     AND 经营属性 in ('加盟')
    //                 group BY
    //                     省份, 经营属性
    //                 order by 经营属性
    //             ) as t
    //         ";
    //         // 查    
    //         $select_jiameng = $this->db_easyA->query($sql_jiameng);
    //         // 插        
    //         $insert_jiameng = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_jiameng);
      
    //         // 加盟合计 
    //         $sql_jiameng_total = "
    //             select 
    //                 '合计' AS 省份,
    //                 '' AS 经营属性,
    //                 t.节日日期,t.节日天数,
    //                 t.今日销额 / t.前年同日销额 - 1 AS 前年日增长,
    //                 t.今日销额 / t.去年同日销额 - 1 AS 去年日增长,
    //                 t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
    //                 t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
    //                     t.前年同日销额,
    //                 t.去年同日销额,
    //                 t.今日销额,
    //                 t.前年累销额,
    //                 t.去年累销额,
    //                 t.今年累销额
    //             from
    //             (select 
    //                 经营属性,节日日期,节日天数,
    //                 sum(前年同日销额) AS 前年同日销额,
    //                 sum(去年同日销额) AS 去年同日销额,
    //                 sum(今日销额) AS 今日销额,
    //                 sum(前年累销额) AS 前年累销额,
    //                 sum(去年累销额) AS 去年累销额,
    //                 sum(今年累销额) AS 今年累销额
    //             from cwl_festival_statistics_province where 节日天数= {$fday} and 经营属性 in ('加盟')) as t       
    //         ";
    
    //         // 加盟合计 查
    //         $select_jiameng_total = $this->db_easyA->query($sql_jiameng_total);
    //         // 加盟合计 删
    //         $insert_jiameng_total = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_jiameng_total);
            
            
    //         // 直营总省
    //         $sql_zhiying = "
    //             select t.
    //                 t.省份,t.经营属性,t.节日日期,节日天数,
    //                 t.今日销额 / t.前年同日销额 - 1 AS 前年日增长,
    //                 t.今日销额 / t.去年同日销额 - 1 AS 去年日增长,
    //                 t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
    //                 t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
    //                 t.前年同日销额,
    //                 t.去年同日销额,
    //                 t.今日销额,
    //                 t.前年累销额,
    //                 t.去年累销额,
    //                 t.今年累销额
    //             from(
    //                 SELECT
    //                     省份,经营属性,节日日期,节日天数,
    //                     sum(销售金额2021) as 前年同日销额,
    //                     sum(销售金额2022) as 去年同日销额,
    //                     sum(销售金额2023) as 今日销额,
    //                     sum(前年累销额) as 前年累销额,	
    //                     sum(去年累销额) as 去年累销额,
    //                     sum(今年累销额) as 今年累销额
    //                 FROM
    //                     cwl_festival_statistics 
    //                 WHERE
    //                     节日天数 = '{$fday}' 
    //                     AND 经营属性 in ('直营')
    //                 group BY
    //                     省份, 经营属性
    //                 order by 经营属性
    //             ) as t
    //         ";
    //         // 查    
    //         $select_zhiying = $this->db_easyA->query($sql_zhiying);
    //         // 插        
    //         $insert_zhiying = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_zhiying);
            
    //         // 直营合计 
    //         $sql_zhiying_total = "
    //             select 
    //                 '合计' AS 省份,
    //                 '' AS 经营属性,
    //                 t.节日日期,t.节日天数,
    //                 t.今日销额 / t.前年同日销额 - 1 AS 前年日增长,
    //                 t.今日销额 / t.去年同日销额 - 1 AS 去年日增长,
    //                 t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
    //                 t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
    //                 t.前年同日销额,
    //                 t.去年同日销额,
    //                 t.今日销额,
    //                 t.前年累销额,
    //                 t.去年累销额,
    //                 t.今年累销额
    //             from
    //             (select 
    //                 经营属性,节日日期,节日天数,
    //                 sum(前年同日销额) AS 前年同日销额,
    //                 sum(去年同日销额) AS 去年同日销额,
    //                 sum(今日销额) AS 今日销额,
    //                 sum(前年累销额) AS 前年累销额,
    //                 sum(去年累销额) AS 去年累销额,
    //                 sum(今年累销额) AS 今年累销额
    //             from cwl_festival_statistics_province where 节日天数= {$fday} and 经营属性 in ('直营')) as t       
    //         ";
    
    //         // 直营合计 查
    //         $select_zhiying_total = $this->db_easyA->query($sql_zhiying_total);
    //         // 直营合计 插
    //         $insert_jiameng_total = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_zhiying_total);
            
            
    //         // 直营加盟总计
    //         $sql_zongji = "
    //             select 
    //                 '总计' AS 省份,
    //                 '' AS 经营属性,
    //                 t.节日日期,t.节日天数,
    //                 t.今日销额 / t.前年同日销额 - 1 AS 前年日增长,
    //                 t.今日销额 / t.去年同日销额 - 1 AS 去年日增长,
    //                 t.今年累销额 / t.前年累销额 - 1 AS 前年累计增长,
    //                 t.今年累销额 / t.去年累销额 - 1 AS 去年累计增长,
    //                 t.前年同日销额,
    //                 t.去年同日销额,
    //                 t.今日销额,
    //                 t.前年累销额,
    //                 t.去年累销额,
    //                 t.今年累销额
    //             from
    //             (select 
    //                 经营属性,节日日期,节日天数,
    //                 sum(前年同日销额) AS 前年同日销额,
    //                 sum(去年同日销额) AS 去年同日销额,
    //                 sum(今日销额) AS 今日销额,
    //                 sum(前年累销额) AS 前年累销额,
    //                 sum(去年累销额) AS 去年累销额,
    //                 sum(今年累销额) AS 今年累销额
    //             from cwl_festival_statistics_province 
    //                 where 节日天数= {$fday} 
    //                 and 经营属性 in ('直营', '加盟')
    //                 and 省份 not in ('合计')
    //             ) as t       
    //         ";
    
    //         // 直营合计 查
    //         $select_zongji = $this->db_easyA->query($sql_zongji);
    //         // 直营合计 插
    //         $insert_zongji  = $this->db_easyA->table('cwl_festival_statistics_province')->strict(false)->insertAll($select_zongji);
    
    //         return json([
    //             'status' => 1,
    //             'msg' => 'success',
    //             'content' => "cwl_festival_statistics_province 更新成功！"
    //         ]);
    //     } else {
    //         return json([
    //             'status' => 0,
    //             'msg' => 'error',
    //             'content' => "cwl_festival_statistics_province 更新失败，活动天数必须是1,2,3！"
    //         ]);
    //     }
    // }

    // public function showTable1($fday) {
    //     if (! $fday) {
    //         $fday = input('fday');
    //     }
    //     $sql1 = "
    //         select * from cwl_festival_statistics WHERE 节日天数='{$fday}' order by 经营属性 DESC
    //     ";
    // }

    // 端午节平均单店流水对比-单日
    public function createTable3($fday) {
        // $fday = input('fday');
        if (! $fday) {
            $fday = input('fday');
        }
        if ($fday == 1 || $fday == 2 || $fday == 3 ) {
            $this->db_easyA->table('cwl_festival_duanwu_table3')->where([
                ['节日天数', '=', $fday]
            ])->delete();
            $sql = "
                select 
                 	t.经营属性,
                    t.节日日期,t.节日天数,
                    concat(round((t.今日销额 / t.去年同日销额 - 1) * 100, 1), '%') AS '23VS22',
                    concat(round((t.今日销额 / t.前年同日销额 - 1) * 100, 1), '%') AS '23VS21',
                    round(t.今日销额, 1) AS 今日流水,
                    round(t.前年同日销额, 1) AS `21年同期`,
                    round(t.去年同日销额, 1) AS `22年同期`
                from
                (select 
                    经营属性,节日日期,节日天数,
                    avg(销售金额2021) AS 前年同日销额,
                    avg(销售金额2022) AS 去年同日销额,
                    avg(销售金额2023) AS 今日销额
                from cwl_festival_statistics where 节日天数= '{$fday}' and 经营属性 in ('加盟')) as t  
                
                union all

                select 
                    t.经营属性,
                    t.节日日期,t.节日天数,
                    concat(round((t.今日销额 / t.去年同日销额 - 1) * 100, 1), '%') AS '23VS22',
                    concat(round((t.今日销额 / t.前年同日销额 - 1) * 100, 1), '%') AS '23VS21',
                    round(t.今日销额, 1) AS 今日流水,
                    round(t.前年同日销额, 1) AS `21年同期`,
                    round(t.去年同日销额, 1) AS `22年同期`
                from
                (select 
                    经营属性,节日日期,节日天数,
                    avg(销售金额2021) AS 前年同日销额,
                    avg(销售金额2022) AS 去年同日销额,
                    avg(销售金额2023) AS 今日销额
                from cwl_festival_statistics where 节日天数= '{$fday}' and 经营属性 in ('直营')) as t

                union all    

                select 
                    '合计' as 经营属性,
                    t.节日日期,t.节日天数,
                    concat(round((t.今日销额 / t.去年同日销额 - 1) * 100, 1), '%') AS '23VS22',
                    concat(round((t.今日销额 / t.前年同日销额 - 1) * 100, 1), '%') AS '23VS21',
                    round(t.今日销额, 1) AS 今日流水,
                    round(t.前年同日销额, 1) AS `21年同期`,
                    round(t.去年同日销额, 1) AS `22年同期`
                from
                (select 
                    节日日期,节日天数,
                    avg(销售金额2021) AS 前年同日销额,
                    avg(销售金额2022) AS 去年同日销额,
                    avg(销售金额2023) AS 今日销额
                from cwl_festival_statistics where 节日天数= '{$fday}'  and 经营属性 in ('直营', '加盟')) as t    
            ";
            $select = $this->db_easyA->query($sql);
            $status = $this->db_easyA->table('cwl_festival_duanwu_table3')->strict(false)->insertAll($select);
            if ($status) {
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_festival_duanwu_table3 更新成功！"
                ]);
            } else {
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => "cwl_festival_duanwu_table3 更新失败，请稍后再试！"
                ]);
            }
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "cwl_festival_duanwu_table3 更新失败，活动天数必须是1,2,3！"
            ]);
        }
    }

    // 端午节平均单店流水对比-累计
    public function createTable4($fday) {
        if (! $fday) {
            $fday = input('fday');
        }
        if ($fday == 1 || $fday == 2 || $fday == 3 ) {
            $this->db_easyA->table('cwl_festival_duanwu_table4')->where([
                ['节日天数', '=', $fday]
            ])->delete();

            // 累计
            $sql = "
                select 
                    t.经营属性,t.节日日期,t.节日天数,
                    concat(round((t.今年累销额 / t.去年累销额 - 1) * 100, 1), '%') AS '23VS22',
                    concat(round((t.今年累销额 / t.前年累销额 - 1) * 100, 1), '%') AS '23VS21',
                    round(t.今年累销额, 1) AS 今日流水,
                    round(t.前年累销额, 1) AS `21年同期`,
                    round(t.去年累销额, 1) AS `22年同期`
                from
                (select 
                    经营属性,节日日期,节日天数,
                    avg(前年累销额) AS 前年累销额,
                    avg(去年累销额) AS 去年累销额,
                    avg(今年累销额) AS 今年累销额
                from cwl_festival_statistics where 节日天数= '{$fday}' and 经营属性 in ('加盟')) as t
            
            union all    

                select 
                    t.经营属性,t.节日日期,t.节日天数,
                    concat(round((t.今年累销额 / t.去年累销额 - 1) * 100, 1), '%') AS '23VS22',
                    concat(round((t.今年累销额 / t.前年累销额 - 1) * 100, 1), '%') AS '23VS21',
                    round(t.今年累销额, 1) AS 今日流水,
                    round(t.前年累销额, 1) AS `21年同期`,
                    round(t.去年累销额, 1) AS `22年同期`
                from
                (select 
                    经营属性,节日日期,节日天数,
                    avg(前年累销额) AS 前年累销额,
                    avg(去年累销额) AS 去年累销额,
                    avg(今年累销额) AS 今年累销额
                from cwl_festival_statistics where 节日天数= '{$fday}' and 经营属性 in ('直营')) as t 
              
            union all      

                select 
                    '合计' as 经营属性,
                    t.节日日期,t.节日天数,
                    concat(round((t.今年累销额 / t.去年累销额 - 1) * 100, 1), '%') AS '23VS22',
                    concat(round((t.今年累销额 / t.前年累销额 - 1) * 100, 1), '%') AS '23VS21',
                    round(t.今年累销额, 1) AS 今日流水,
                    round(t.前年累销额, 1) AS `21年同期`,
                    round(t.去年累销额, 1) AS `22年同期`
                from
                (select 
                    经营属性,节日日期,节日天数,
                    avg(前年累销额) AS 前年累销额,
                    avg(去年累销额) AS 去年累销额,
                    avg(今年累销额) AS 今年累销额
                from cwl_festival_statistics where 节日天数= '{$fday}' and 经营属性 in ('加盟', '直营')) as t    
            ";
            $select = $this->db_easyA->query($sql);
            $status = $this->db_easyA->table('cwl_festival_duanwu_table4')->strict(false)->insertAll($select);
            if ($status) {
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_festival_duanwu_table4 更新成功！"
                ]);
            } else {
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => "cwl_festival_duanwu_table4 更新失败，请稍后再试！"
                ]);
            }
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "cwl_festival_duanwu_table4 更新失败，活动天数必须是1,2,3！"
            ]);
        }
    }


}
