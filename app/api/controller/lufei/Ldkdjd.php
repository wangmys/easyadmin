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
 * @ControllerAnnotation(title="连带客单件单单报表")
 */
class Ldkdjd extends BaseController
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

    public function seasionHandle($seasion = "春季") {
        $seasionStr = "";
        if ($seasion == '春季') {
            $seasionStr = "'初春','正春','春季'";
        } elseif ($seasion == '夏季') {
            $seasionStr = "'初夏','盛夏','夏季'";
        } elseif ($seasion == '秋季') {
            $seasionStr = "'初秋','深秋','秋季'";
        } elseif ($seasion == '冬季') {
            $seasionStr = "'初冬','深冬','冬季'";
        }
        return $seasionStr;
    }

    // 每日数据源
    public function dataHandle()
    {
        $current_today = input('date') ? input('date') : date('Y-m-d', time());
        $lastyear_today = date('Y-m-d', strtotime('-1YEAR', strtotime($current_today)));

        $sql_今年 = "
            SELECT 
                T.Region,
                T.RegionId,
                T.CustomerCode as 店铺编号,
                T.Mathod as 性质,
                T.CustomerName AS 店铺名称,
            SUM(T.[quantity]) AS 销量,
            SUM(T.[count]) AS 单数,
            SUM(T.[sales_f]) AS 正品业绩,
            T.销售日期,
            '{$lastyear_today}' AS 去年销售日期
            FROM 
            (
            SELECT  
                FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 销售日期,
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State,
                mathod.Mathod,
                ER.RetailID,
            SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END ) AS quantity,
            SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity*ERG.DiscountPrice ELSE 0 END ) AS sales_f,
                CASE WHEN SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END )>0 THEN 1 WHEN SUM(ERG.Quantity)<0 THEN -1 ELSE 0 END AS count
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            LEFT JOIN ErpBaseCustomerMathod mathod ON EC.MathodId = mathod.MathodId
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            WHERE EC.ShutOut = 0
                AND ER.CodingCodeText='已审结'
                AND EC.RegionId NOT IN ('8', '40', '55', '84', '85', '97')
                AND ER.RetailDate BETWEEN '{$current_today} 00:00:00'  AND '{$current_today} 23:59:59'
                AND ER.RetailID NOT IN (SELECT ER.RetailID FROM ErpRetail ER  LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID WHERE ERG.Status='退' AND ER.RetailDate BETWEEN '{$current_today} 00:00:00'  AND '{$current_today} 23:59:59'GROUP BY ER.RetailID )
                AND ERG.Status!='赠'
            GROUP BY 
                FORMAT(ER.RetailDate, 'yyyy-MM-dd'),
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State,
                mathod.Mathod,
                ER.RetailID
            HAVING SUM(ERG.Quantity)<5000
            ) T
            LEFT JOIN 
            (
            SELECT  
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            WHERE EC.ShutOut = 0
                AND ER.CodingCodeText='已审结'
                AND ER.RetailDate BETWEEN '{$current_today} 00:00:00'  AND '{$current_today} 23:59:59'
            GROUP BY 
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State
            ) T1 ON T.CustomerCode=T1.CustomerCode 
            GROUP BY 
                T.Region,
                T.RegionId,
                T.CustomerCode,
                T.Mathod,
                T.State,
                T.CustomerName,
                T.销售日期
            ORDER BY 
                T.Region,
                T.State,
                T.Mathod,
                T.CustomerCode;
        ";

        $sql_去年 = "
            SELECT 
                T.Region,
                T.RegionId,
                T.CustomerCode as 店铺编号,
                T.Mathod as 性质,
                T.CustomerName AS 店铺名称,
            SUM(T.[quantity]) AS 销量,
            SUM(T.[count]) AS 单数,
            SUM(T.[sales_f]) AS 正品业绩,
            T.销售日期
            FROM 
            (
            SELECT  
                FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 销售日期,
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State,
                mathod.Mathod,
                ER.RetailID,
            SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END ) AS quantity,
            SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity*ERG.DiscountPrice ELSE 0 END ) AS sales_f,
                CASE WHEN SUM(CASE WHEN EG.CategoryName1 IN ('内搭','外套','下装','鞋履') OR  (EG.CategoryName1='配饰' AND ERG.DiscountPrice>50) THEN ERG.Quantity ELSE 0 END )>0 THEN 1 WHEN SUM(ERG.Quantity)<0 THEN -1 ELSE 0 END AS count
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            LEFT JOIN ErpBaseCustomerMathod mathod ON EC.MathodId = mathod.MathodId
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            WHERE EC.ShutOut = 0
                AND ER.CodingCodeText='已审结'
                AND EC.RegionId NOT IN ('8', '40', '55', '84', '85', '97')
                AND ER.RetailDate BETWEEN '{$lastyear_today} 00:00:00'  AND '{$lastyear_today} 23:59:59'
                AND ER.RetailID NOT IN (SELECT ER.RetailID FROM ErpRetail ER  LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID WHERE ERG.Status='退' AND ER.RetailDate BETWEEN '{$lastyear_today} 00:00:00'  AND '{$lastyear_today} 23:59:59'GROUP BY ER.RetailID )
                AND ERG.Status!='赠'
            GROUP BY 
                FORMAT(ER.RetailDate, 'yyyy-MM-dd'),
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State,
                mathod.Mathod,
                ER.RetailID
            HAVING SUM(ERG.Quantity)<5000
            ) T
            LEFT JOIN 
            (
            SELECT  
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State
            FROM ErpRetail ER
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            WHERE EC.ShutOut = 0
                AND ER.CodingCodeText='已审结'
                AND ER.RetailDate BETWEEN '{$lastyear_today} 00:00:00'  AND '{$lastyear_today} 23:59:59'
            GROUP BY 
                EBCR.Region,
                EC.RegionId,
                EC.CustomerCode,
                EC.CustomerName,
                EC.State
            ) T1 ON T.CustomerCode=T1.CustomerCode 
            GROUP BY 
                T.Region,
                T.RegionId,
                T.CustomerCode,
                T.Mathod,
                T.State,
                T.CustomerName,
                T.销售日期
            ORDER BY 
                T.Region,
                T.State,
                T.Mathod,
                T.CustomerCode;
        ";

        $select今年 = $this->db_sqlsrv->query($sql_今年);
        $count = count($select今年);

        $select去年 = $this->db_sqlsrv->query($sql_去年);
        $count2 = count($select去年);
        // dump($select);die;
        if ($select今年) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->table('cwl_ldkdjd_current_data')->where([
                ['销售日期', '=', date('Y-m-d', strtotime($current_today))]
            ])->delete();

            $chunk_list = array_chunk($select今年, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_ldkdjd_current_data')->strict(false)->insertAll($val);
            }

        }

        if ($select去年) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->table('cwl_ldkdjd_lastyear_data')->where([
                ['销售日期', '=', date('Y-m-d', strtotime($lastyear_today))]
            ])->delete();

            $chunk_list = array_chunk($select去年, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_ldkdjd_lastyear_data')->strict(false)->insertAll($val);
            }
        }

        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => "cwl_Ldkdjd_current_data cwl_Ldkdjd_lastyear_data 更新成功！"
        ]);
    }

    // 直营 结果
    public function handle_zy()
    {
        $current_today = input('date') ? input('date') : date('Y-m-d', time());
        $lastyear_today = date('Y-m-d', strtotime('-1YEAR', strtotime($current_today)));

        $this->db_easyA->table('cwl_ldkdjd_handle_zy')->where([
            ['销售日期', '=', date('Y-m-d', strtotime($current_today))]
        ])->delete();

        // 直营-天
        $sql_直营天 = "
            SELECT 
                '直营天' AS 类型,
                性质,
                销量,
                单数,
                正品业绩,
                round(销量 / 单数, 1) as 连带,
                round(正品业绩 / 单数, 1) AS 客单,
                round(正品业绩 / 销量, 1) AS 件单,
                销售日期
            FROM (
                SELECT
                    性质,
                    sum(销量) as 销量,
                    sum(单数) as 单数,
                    sum(正品业绩) as 正品业绩,
                    销售日期
                FROM
                    `cwl_ldkdjd_current_data` 
                WHERE 1
                    AND 销售日期 = '$current_today'
                    AND 性质 = '直营'
            ) AS t
        ";

        

        $select_直营天 = $this->db_easyA->query($sql_直营天);
        // 上线时不要屏蔽
        $this->db_easyA->table('cwl_ldkdjd_handle_zy')->strict(false)->insertAll($select_直营天);

        // 同比今年
        $sql_同比今年 = "
            SELECT 
                '同比天' AS 类型,
                性质,
                销量,
                单数,
                正品业绩,
                round(销量 / 单数, 1) as 连带,
                round(正品业绩 / 单数, 1) AS 客单,
                round(正品业绩 / 销量, 1) AS 件单,
                销售日期
            FROM (
                SELECT
                    性质,
                    sum(销量) as 销量,
                    sum(单数) as 单数,
                    sum(正品业绩) as 正品业绩,
                    销售日期
                FROM
                    `cwl_ldkdjd_current_data` 
                WHERE 1
                    AND 销售日期 = '{$current_today}'
                    AND 性质 = '直营'
                    AND 店铺名称 in (
                        SELECT
                            c.店铺名称
                        FROM
                            `cwl_ldkdjd_current_data` as c
                        LEFT JOIN `cwl_ldkdjd_lastyear_data` as l ON c.店铺名称 = l.店铺名称 and c.去年销售日期 = l.销售日期
                        WHERE 1
                            AND c.销售日期 = '{$current_today}'
                            AND c.性质 = '直营'
                            AND c.店铺名称 = l.店铺名称
                            and c.去年销售日期 = l.销售日期
                        GROUP BY c.店铺名称
                    )
            ) AS t
        ";

        $sql_同比去年 = "
            SELECT 
                '同比天' AS 类型,
                性质,
                销量,
                单数,
                正品业绩,
                round(销量 / 单数, 1) as 连带,
                round(正品业绩 / 单数, 1) AS 客单,
                round(正品业绩 / 销量, 1) AS 件单,
                销售日期
            FROM (
                SELECT
                    性质,
                    sum(销量) as 销量,
                    sum(单数) as 单数,
                    sum(正品业绩) as 正品业绩,
                    销售日期
                FROM
                    `cwl_ldkdjd_lastyear_data` 
                WHERE 1
                    AND 销售日期 = '{$lastyear_today}'
                    AND 性质 = '直营'
                    AND 店铺名称 in (
                        SELECT
                            c.店铺名称
                        FROM
                            `cwl_ldkdjd_current_data` as c
                        LEFT JOIN `cwl_ldkdjd_lastyear_data` as l ON c.店铺名称 = l.店铺名称 and c.去年销售日期 = l.销售日期
                        WHERE 1
                            AND c.销售日期 = '{$current_today}'
                            AND c.性质 = '直营'
                            AND c.店铺名称 = l.店铺名称
                            and c.去年销售日期 = l.销售日期
                        GROUP BY c.店铺名称
                    )
            ) AS t        
        ";
        $select_同比今年 = $this->db_easyA->query($sql_同比今年);
        $select_同比去年 = $this->db_easyA->query($sql_同比去年);

        $同比今年 = [];  
        $同比今年['类型'] = '同比今年';
        $同比今年['性质'] = $select_同比今年[0]['性质'];
        $同比今年['销量'] = $select_同比今年[0]['销量'];
        $同比今年['单数'] = $select_同比今年[0]['单数'];
        $同比今年['正品业绩'] = $select_同比今年[0]['正品业绩'];
        $同比今年['连带'] = $select_同比今年[0]['连带'];
        $同比今年['客单'] = $select_同比今年[0]['客单'];
        $同比今年['件单'] = $select_同比今年[0]['件单'];
        $同比今年['销售日期'] = $select_同比今年[0]['销售日期'];   
        
        $同比去年 = [];  
        $同比去年['类型'] = '同比去年';
        $同比去年['性质'] = $select_同比去年[0]['性质'];
        $同比去年['销量'] = $select_同比去年[0]['销量'];
        $同比去年['单数'] = $select_同比去年[0]['单数'];
        $同比去年['正品业绩'] = $select_同比去年[0]['正品业绩'];
        $同比去年['连带'] = $select_同比去年[0]['连带'];
        $同比去年['客单'] = $select_同比去年[0]['客单'];
        $同比去年['件单'] = $select_同比去年[0]['件单'];
        // *** 去年的日期用今年的，方便匹配
        $同比去年['销售日期'] = $select_同比今年[0]['销售日期'];  
        // *** 去年的日期用今年的，方便匹配

        $同比天 = [];
        $同比天['类型'] = $select_同比今年[0]['类型'];
        $同比天['性质'] = $select_同比今年[0]['性质'];
        $同比天['销量'] = $同比今年['销量'] - $同比去年['销量'];
        $同比天['单数'] = $同比今年['单数'] - $同比去年['单数'];
        $同比天['正品业绩'] = $同比今年['正品业绩'] - $同比去年['正品业绩'];
        $同比天['连带'] = $同比今年['连带'] - $同比去年['连带'];
        $同比天['客单'] = $同比今年['客单'] - $同比去年['客单'];
        $同比天['件单'] = $同比今年['件单'] - $同比去年['件单'];
        $同比天['销售日期'] = $select_同比今年[0]['销售日期'];

        // 上线时不要屏蔽
        $this->db_easyA->table('cwl_ldkdjd_handle_zy')->insert($同比天);    
        $this->db_easyA->table('cwl_ldkdjd_handle_zy')->insert($同比今年);    
        $this->db_easyA->table('cwl_ldkdjd_handle_zy')->insert($同比去年);    
        


        // die;
        if (date('d', strtotime($current_today)) == 1) {
            echo '1号';
            $直营累计 = [];
            $直营累计['类型'] = '直营累计';
            $直营累计['性质'] = $select_直营天[0]['性质'];
            $直营累计['销量'] = $select_直营天[0]['销量'];
            $直营累计['单数'] = $select_直营天[0]['单数'];
            $直营累计['正品业绩'] = $select_直营天[0]['正品业绩'];
            $直营累计['连带'] = $select_直营天[0]['连带'];
            $直营累计['客单'] = $select_直营天[0]['客单'];
            $直营累计['件单'] = $select_直营天[0]['件单'];
            $直营累计['销售日期'] = $select_直营天[0]['销售日期'];

            // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_zy')->insert($直营累计);  

            $同比累计 = [];
            $同比累计['类型'] = '同比累计';
            $同比累计['性质'] = $同比天['性质'];
            $同比累计['销量'] = $同比天['销量'];
            $同比累计['单数'] = $同比天['单数'];
            $同比累计['正品业绩'] = $同比天['正品业绩'];
            $同比累计['连带'] = $同比天['连带'];
            $同比累计['客单'] = $同比天['客单'];
            $同比累计['件单'] = $同比天['件单'];
            $同比累计['销售日期'] = $同比天['销售日期'];

            // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_zy')->insert($同比累计);  
        } else {
            echo '不是1号';

            // 直营累计
            $find_昨天_直营累计 = $this->db_easyA->table('cwl_ldkdjd_handle_zy')->where([
                ['类型', '=', '直营累计'],
                ['销售日期', '=', date('Y-m-d', strtotime('-1DAY', strtotime($current_today)))]
            ])->find();  
            // $select_直营天

            // dump($find_昨天);
            $直营累计 = [];
            $直营累计['类型'] = '直营累计';
            $直营累计['性质'] = $select_直营天[0]['性质'];
            $直营累计['销量'] = $select_直营天[0]['销量'] + $find_昨天_直营累计['销量'];
            $直营累计['单数'] = $select_直营天[0]['单数'] + $find_昨天_直营累计['单数'];
            $直营累计['正品业绩'] = $select_直营天[0]['正品业绩'] + $find_昨天_直营累计['正品业绩'];
            $直营累计['连带'] = round($直营累计['销量'] / $直营累计['单数'], 1);
            $直营累计['客单'] = round($直营累计['正品业绩'] / $直营累计['单数'], 1);
            $直营累计['件单'] = round($直营累计['正品业绩'] / $直营累计['销量'], 1);
            $直营累计['销售日期'] = $select_直营天[0]['销售日期'];

            // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_zy')->insert($直营累计);  

            // 同比累计
            $month_start = date('Y-m-01', time());
            $同比今年 = "
                    SELECT 
                        sum(销量) AS 销量,
                        sum(单数) AS 单数,
                        sum(正品业绩) AS 正品业绩
                    FROM `cwl_ldkdjd_handle_zy` 
                    where 
                        类型= '同比今年'
                        AND 销售日期 >= '{$month_start}' 
                        AND 销售日期 <= '{$current_today}'
                ";
            $find_同比今年 = $this->db_easyA->query($同比今年);

            $同比去年 = "
                    SELECT 
                        sum(销量) AS 销量,
                        sum(单数) AS 单数,
                        sum(正品业绩) AS 正品业绩
                    FROM `cwl_ldkdjd_handle_zy` 
                    where 
                        类型= '同比去年'
                        AND 销售日期 >= '{$month_start}' 
                        AND 销售日期 <= '{$current_today}'
                ";
            $find_同比去年 = $this->db_easyA->query($同比去年);
 

            $同比累计 = [];
            $同比累计['类型'] = '同比累计';
            $同比累计['性质'] = $同比天['性质'];


            // $同比累计['销量'] = $find_同比今年[0]['销量'] - $find_同比去年[0]['销量'];
            $同比累计['单数'] = $find_同比今年[0]['单数'] - $find_同比去年[0]['单数'];
            // $同比累计['正品业绩'] = $find_同比今年[0]['正品业绩'] - $find_同比去年[0]['正品业绩'];
            // $同比累计['正品业绩'] = $同比天['正品业绩'] + $find_昨天_同比累计['正品业绩'];

            $同比累计['连带'] = round( $find_同比今年[0]['销量'] / $find_同比今年[0]['单数'] - $find_同比去年[0]['销量'] / $find_同比去年[0]['单数'], 1 );
            $同比累计['客单'] = round( $find_同比今年[0]['正品业绩'] / $find_同比今年[0]['单数'] - $find_同比去年[0]['正品业绩'] / $find_同比去年[0]['单数'], 1 );
            $同比累计['件单'] = round( $find_同比今年[0]['正品业绩'] / $find_同比今年[0]['销量'] - $find_同比去年[0]['正品业绩'] / $find_同比去年[0]['销量'], 1);
            $同比累计['销售日期'] = $同比天['销售日期'];

            // // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_zy')->insert($同比累计);  
        }

        $select_星期 = $this->db_easyA->query("
            select * from cwl_ldkdjd_handle_zy where 星期 is null
        ");
        if ($select_星期) {
            foreach ($select_星期 as $key => $val) {
                $星期 = date_to_week3($val['销售日期']);
                $this->db_easyA->table('cwl_ldkdjd_handle_zy')->where([
                    ['类型', '=', $val['类型']],
                    ['性质', '=', $val['性质']],
                    ['销售日期', '=', $val['销售日期']],
                ])->update(['星期' => $星期]);
            }
        }
    }


    public function test () {
        echo  (1305603.40 /  6755) - (1280921.24 / 6455);
    }



























    // 加盟 结果
    public function handle_jm()
    {
        $current_today = input('date') ? input('date') : date('Y-m-d', time());
        $lastyear_today = date('Y-m-d', strtotime('-1YEAR', strtotime($current_today)));

        $this->db_easyA->table('cwl_ldkdjd_handle_jm')->where([
            ['销售日期', '=', date('Y-m-d', strtotime($current_today))]
        ])->delete();

        // 加盟-天
        $sql_加盟天 = "
            SELECT 
                '加盟天' AS 类型,
                性质,
                销量,
                单数,
                正品业绩,
                round(销量 / 单数, 1) as 连带,
                round(正品业绩 / 单数, 1) AS 客单,
                round(正品业绩 / 销量, 1) AS 件单,
                销售日期
            FROM (
                SELECT
                    性质,
                    sum(销量) as 销量,
                    sum(单数) as 单数,
                    sum(正品业绩) as 正品业绩,
                    销售日期
                FROM
                    `cwl_ldkdjd_current_data` 
                WHERE 1
                    AND 销售日期 = '$current_today'
                    AND 性质 = '加盟'
            ) AS t
        ";

        $select_加盟天 = $this->db_easyA->query($sql_加盟天);
        // 上线时不要屏蔽
        $this->db_easyA->table('cwl_ldkdjd_handle_jm')->strict(false)->insertAll($select_加盟天);

        // 同比今年
        $sql_同比今年 = "
            SELECT 
                '同比天' AS 类型,
                性质,
                销量,
                单数,
                正品业绩,
                round(销量 / 单数, 1) as 连带,
                round(正品业绩 / 单数, 1) AS 客单,
                round(正品业绩 / 销量, 1) AS 件单,
                销售日期
            FROM (
                SELECT
                    性质,
                    sum(销量) as 销量,
                    sum(单数) as 单数,
                    sum(正品业绩) as 正品业绩,
                    销售日期
                FROM
                    `cwl_ldkdjd_current_data` 
                WHERE 1
                    AND 销售日期 = '{$current_today}'
                    AND 性质 = '加盟'
                    AND 店铺名称 in (
                        SELECT
                            c.店铺名称
                        FROM
                            `cwl_ldkdjd_current_data` as c
                        LEFT JOIN `cwl_ldkdjd_lastyear_data` as l ON c.店铺名称 = l.店铺名称 and c.去年销售日期 = l.销售日期
                        WHERE 1
                            AND c.销售日期 = '{$current_today}'
                            AND c.性质 = '加盟'
                            AND c.店铺名称 = l.店铺名称
                            and c.去年销售日期 = l.销售日期
                        GROUP BY c.店铺名称
                    )
            ) AS t
        ";

        $sql_同比去年 = "
            SELECT 
                '同比天' AS 类型,
                性质,
                销量,
                单数,
                正品业绩,
                round(销量 / 单数, 1) as 连带,
                round(正品业绩 / 单数, 1) AS 客单,
                round(正品业绩 / 销量, 1) AS 件单,
                销售日期
            FROM (
                SELECT
                    性质,
                    sum(销量) as 销量,
                    sum(单数) as 单数,
                    sum(正品业绩) as 正品业绩,
                    销售日期
                FROM
                    `cwl_ldkdjd_lastyear_data` 
                WHERE 1
                    AND 销售日期 = '{$lastyear_today}'
                    AND 性质 = '加盟'
                    AND 店铺名称 in (
                        SELECT
                            c.店铺名称
                        FROM
                            `cwl_ldkdjd_current_data` as c
                        LEFT JOIN `cwl_ldkdjd_lastyear_data` as l ON c.店铺名称 = l.店铺名称 and c.去年销售日期 = l.销售日期
                        WHERE 1
                            AND c.销售日期 = '{$current_today}'
                            AND c.性质 = '加盟'
                            AND c.店铺名称 = l.店铺名称
                            and c.去年销售日期 = l.销售日期
                        GROUP BY c.店铺名称
                    )
            ) AS t        
        ";
        $select_同比今年 = $this->db_easyA->query($sql_同比今年);
        $select_同比去年 = $this->db_easyA->query($sql_同比去年);

        $同比今年 = [];  
        $同比今年['类型'] = '同比今年';
        $同比今年['性质'] = $select_同比今年[0]['性质'];
        $同比今年['销量'] = $select_同比今年[0]['销量'];
        $同比今年['单数'] = $select_同比今年[0]['单数'];
        $同比今年['正品业绩'] = $select_同比今年[0]['正品业绩'];
        $同比今年['连带'] = $select_同比今年[0]['连带'];
        $同比今年['客单'] = $select_同比今年[0]['客单'];
        $同比今年['件单'] = $select_同比今年[0]['件单'];
        $同比今年['销售日期'] = $select_同比今年[0]['销售日期'];   
        
        $同比去年 = [];  
        $同比去年['类型'] = '同比去年';
        $同比去年['性质'] = $select_同比去年[0]['性质'];
        $同比去年['销量'] = $select_同比去年[0]['销量'];
        $同比去年['单数'] = $select_同比去年[0]['单数'];
        $同比去年['正品业绩'] = $select_同比去年[0]['正品业绩'];
        $同比去年['连带'] = $select_同比去年[0]['连带'];
        $同比去年['客单'] = $select_同比去年[0]['客单'];
        $同比去年['件单'] = $select_同比去年[0]['件单'];
        // *** 去年的日期用今年的，方便匹配
        $同比去年['销售日期'] = $select_同比今年[0]['销售日期'];  
        // *** 去年的日期用今年的，方便匹配

        $同比天 = [];
        $同比天['类型'] = $select_同比今年[0]['类型'];
        $同比天['性质'] = $select_同比今年[0]['性质'];
        $同比天['销量'] = $同比今年['销量'] - $同比去年['销量'];
        $同比天['单数'] = $同比今年['单数'] - $同比去年['单数'];
        $同比天['正品业绩'] = $同比今年['正品业绩'] - $同比去年['正品业绩'];
        $同比天['连带'] = $同比今年['连带'] - $同比去年['连带'];
        $同比天['客单'] = $同比今年['客单'] - $同比去年['客单'];
        $同比天['件单'] = $同比今年['件单'] - $同比去年['件单'];
        $同比天['销售日期'] = $select_同比今年[0]['销售日期'];

        // 上线时不要屏蔽
        $this->db_easyA->table('cwl_ldkdjd_handle_jm')->insert($同比天);    
        $this->db_easyA->table('cwl_ldkdjd_handle_jm')->insert($同比今年);    
        $this->db_easyA->table('cwl_ldkdjd_handle_jm')->insert($同比去年);    
        
        // die;
        if (date('d', strtotime($current_today)) == 1) {
            echo '1号';
            $加盟累计 = [];
            $加盟累计['类型'] = '加盟累计';
            $加盟累计['性质'] = $select_加盟天[0]['性质'];
            $加盟累计['销量'] = $select_加盟天[0]['销量'];
            $加盟累计['单数'] = $select_加盟天[0]['单数'];
            $加盟累计['正品业绩'] = $select_加盟天[0]['正品业绩'];
            $加盟累计['连带'] = $select_加盟天[0]['连带'];
            $加盟累计['客单'] = $select_加盟天[0]['客单'];
            $加盟累计['件单'] = $select_加盟天[0]['件单'];
            $加盟累计['销售日期'] = $select_加盟天[0]['销售日期'];

            // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_jm')->insert($加盟累计);  

            $同比累计 = [];
            $同比累计['类型'] = '同比累计';
            $同比累计['性质'] = $同比天['性质'];
            $同比累计['销量'] = $同比天['销量'];
            $同比累计['单数'] = $同比天['单数'];
            $同比累计['正品业绩'] = $同比天['正品业绩'];
            $同比累计['连带'] = $同比天['连带'];
            $同比累计['客单'] = $同比天['客单'];
            $同比累计['件单'] = $同比天['件单'];
            $同比累计['销售日期'] = $同比天['销售日期'];

            // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_jm')->insert($同比累计);  
        } else {
            echo '不是1号';

            // 加盟累计
            $find_昨天_加盟累计 = $this->db_easyA->table('cwl_ldkdjd_handle_jm')->where([
                ['类型', '=', '加盟累计'],
                ['销售日期', '=', date('Y-m-d', strtotime('-1DAY', strtotime($current_today)))]
            ])->find();  
            // $select_直营天

            // dump($find_昨天);
            $加盟累计 = [];
            $加盟累计['类型'] = '加盟累计';
            $加盟累计['性质'] = $select_加盟天[0]['性质'];
            $加盟累计['销量'] = $select_加盟天[0]['销量'] + $find_昨天_加盟累计['销量'];
            $加盟累计['单数'] = $select_加盟天[0]['单数'] + $find_昨天_加盟累计['单数'];
            $加盟累计['正品业绩'] = $select_加盟天[0]['正品业绩'] + $find_昨天_加盟累计['正品业绩'];
            $加盟累计['连带'] = round($加盟累计['销量'] / $加盟累计['单数'], 1);
            $加盟累计['客单'] = round($加盟累计['正品业绩'] / $加盟累计['单数'], 1);
            $加盟累计['件单'] = round($加盟累计['正品业绩'] / $加盟累计['销量'], 1);
            $加盟累计['销售日期'] = $select_加盟天[0]['销售日期'];

            // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_jm')->insert($加盟累计);  




            // 同比累计
            $month_start = date('Y-m-01', time());
            $同比今年 = "
                    SELECT 
                        sum(销量) AS 销量,
                        sum(单数) AS 单数,
                        sum(正品业绩) AS 正品业绩
                    FROM `cwl_ldkdjd_handle_jm` 
                    where 
                        类型= '同比今年'
                        AND 销售日期 >= '{$month_start}' 
                        AND 销售日期 <= '{$current_today}'
                ";
            $find_同比今年 = $this->db_easyA->query($同比今年);

            $同比去年 = "
                    SELECT 
                        sum(销量) AS 销量,
                        sum(单数) AS 单数,
                        sum(正品业绩) AS 正品业绩
                    FROM `cwl_ldkdjd_handle_jm` 
                    where 
                        类型= '同比去年'
                        AND 销售日期 >= '{$month_start}' 
                        AND 销售日期 <= '{$current_today}'
                ";
            $find_同比去年 = $this->db_easyA->query($同比去年);


            $同比累计 = [];
            $同比累计['类型'] = '同比累计';
            $同比累计['性质'] = $同比天['性质'];


            // $同比累计['销量'] = $find_同比今年[0]['销量'] - $find_同比去年[0]['销量'];
            $同比累计['单数'] = $find_同比今年[0]['单数'] - $find_同比去年[0]['单数'];
            // $同比累计['正品业绩'] = $find_同比今年[0]['正品业绩'] - $find_同比去年[0]['正品业绩'];
            // $同比累计['正品业绩'] = $同比天['正品业绩'] + $find_昨天_同比累计['正品业绩'];

            $同比累计['连带'] = round( $find_同比今年[0]['销量'] / $find_同比今年[0]['单数'] - $find_同比去年[0]['销量'] / $find_同比去年[0]['单数'], 1 );
            $同比累计['客单'] = round( $find_同比今年[0]['正品业绩'] / $find_同比今年[0]['单数'] - $find_同比去年[0]['正品业绩'] / $find_同比去年[0]['单数'], 1 );
            $同比累计['件单'] = round( $find_同比今年[0]['正品业绩'] / $find_同比今年[0]['销量'] - $find_同比去年[0]['正品业绩'] / $find_同比去年[0]['销量'], 1);
            $同比累计['销售日期'] = $同比天['销售日期'];

            // 上线时不要屏蔽
            $this->db_easyA->table('cwl_ldkdjd_handle_jm')->insert($同比累计);  
        }

        $select_星期 = $this->db_easyA->query("
            select * from cwl_ldkdjd_handle_jm where 星期 is null
        ");
        if ($select_星期) {
            foreach ($select_星期 as $key => $val) {
                $星期 = date_to_week3($val['销售日期']);
                $this->db_easyA->table('cwl_ldkdjd_handle_jm')->where([
                    ['类型', '=', $val['类型']],
                    ['性质', '=', $val['性质']],
                    ['销售日期', '=', $val['销售日期']],
                ])->update(['星期' => $星期]);
            }
        }
    }











   // 总计 结果
   public function handle()
   {
       $current_today = input('date') ? input('date') : date('Y-m-d', time());
       $lastyear_today = date('Y-m-d', strtotime('-1YEAR', strtotime($current_today)));

       $this->db_easyA->table('cwl_ldkdjd_handle')->where([
           ['销售日期', '=', date('Y-m-d', strtotime($current_today))]
       ])->delete();

       // 总计-天
       $sql_总计天 = "
           SELECT 
               '总计天' AS 类型,
               性质,
               销量,
               单数,
               正品业绩,
               round(销量 / 单数, 1) as 连带,
               round(正品业绩 / 单数, 1) AS 客单,
               round(正品业绩 / 销量, 1) AS 件单,
               销售日期
           FROM (
               SELECT
                   性质,
                   sum(销量) as 销量,
                   sum(单数) as 单数,
                   sum(正品业绩) as 正品业绩,
                   销售日期
               FROM
                   `cwl_ldkdjd_current_data` 
               WHERE 1
                   AND 销售日期 = '$current_today'
           ) AS t
       ";

       $select_总计天 = $this->db_easyA->query($sql_总计天);
       // 上线时不要屏蔽
       $this->db_easyA->table('cwl_ldkdjd_handle')->strict(false)->insertAll($select_总计天);

       // 同比今年
       $sql_同比今年 = "
           SELECT 
               '同比天' AS 类型,
               性质,
               销量,
               单数,
               正品业绩,
               round(销量 / 单数, 1) as 连带,
               round(正品业绩 / 单数, 1) AS 客单,
               round(正品业绩 / 销量, 1) AS 件单,
               销售日期
           FROM (
               SELECT
                   性质,
                   sum(销量) as 销量,
                   sum(单数) as 单数,
                   sum(正品业绩) as 正品业绩,
                   销售日期
               FROM
                   `cwl_ldkdjd_current_data` 
               WHERE 1
                   AND 销售日期 = '{$current_today}'
                   AND 店铺名称 in (
                       SELECT
                           c.店铺名称
                       FROM
                           `cwl_ldkdjd_current_data` as c
                       LEFT JOIN `cwl_ldkdjd_lastyear_data` as l ON c.店铺名称 = l.店铺名称 and c.去年销售日期 = l.销售日期
                       WHERE 1
                           AND c.销售日期 = '{$current_today}'
                           AND c.店铺名称 = l.店铺名称
                           and c.去年销售日期 = l.销售日期
                       GROUP BY c.店铺名称
                   )
           ) AS t
       ";

       $sql_同比去年 = "
           SELECT 
               '同比天' AS 类型,
               性质,
               销量,
               单数,
               正品业绩,
               round(销量 / 单数, 1) as 连带,
               round(正品业绩 / 单数, 1) AS 客单,
               round(正品业绩 / 销量, 1) AS 件单,
               销售日期
           FROM (
               SELECT
                   性质,
                   sum(销量) as 销量,
                   sum(单数) as 单数,
                   sum(正品业绩) as 正品业绩,
                   销售日期
               FROM
                   `cwl_ldkdjd_lastyear_data` 
               WHERE 1
                   AND 销售日期 = '{$lastyear_today}'
                   AND 店铺名称 in (
                       SELECT
                           c.店铺名称
                       FROM
                           `cwl_ldkdjd_current_data` as c
                       LEFT JOIN `cwl_ldkdjd_lastyear_data` as l ON c.店铺名称 = l.店铺名称 and c.去年销售日期 = l.销售日期
                       WHERE 1
                           AND c.销售日期 = '{$current_today}'
                           AND c.店铺名称 = l.店铺名称
                           and c.去年销售日期 = l.销售日期
                       GROUP BY c.店铺名称
                   )
           ) AS t        
       ";
       $select_同比今年 = $this->db_easyA->query($sql_同比今年);
       $select_同比去年 = $this->db_easyA->query($sql_同比去年);

       $同比今年 = [];  
       $同比今年['类型'] = '同比今年';
       $同比今年['性质'] = $select_同比今年[0]['性质'];
       $同比今年['销量'] = $select_同比今年[0]['销量'];
       $同比今年['单数'] = $select_同比今年[0]['单数'];
       $同比今年['正品业绩'] = $select_同比今年[0]['正品业绩'];
       $同比今年['连带'] = $select_同比今年[0]['连带'];
       $同比今年['客单'] = $select_同比今年[0]['客单'];
       $同比今年['件单'] = $select_同比今年[0]['件单'];
       $同比今年['销售日期'] = $select_同比今年[0]['销售日期'];   
       
       $同比去年 = [];  
       $同比去年['类型'] = '同比去年';
       $同比去年['性质'] = $select_同比去年[0]['性质'];
       $同比去年['销量'] = $select_同比去年[0]['销量'];
       $同比去年['单数'] = $select_同比去年[0]['单数'];
       $同比去年['正品业绩'] = $select_同比去年[0]['正品业绩'];
       $同比去年['连带'] = $select_同比去年[0]['连带'];
       $同比去年['客单'] = $select_同比去年[0]['客单'];
       $同比去年['件单'] = $select_同比去年[0]['件单'];
       // *** 去年的日期用今年的，方便匹配
       $同比去年['销售日期'] = $select_同比今年[0]['销售日期'];  
       // *** 去年的日期用今年的，方便匹配

       $同比天 = [];
       $同比天['类型'] = $select_同比今年[0]['类型'];
       $同比天['性质'] = $select_同比今年[0]['性质'];
       $同比天['销量'] = $同比今年['销量'] - $同比去年['销量'];
       $同比天['单数'] = $同比今年['单数'] - $同比去年['单数'];
       $同比天['正品业绩'] = $同比今年['正品业绩'] - $同比去年['正品业绩'];
       $同比天['连带'] = $同比今年['连带'] - $同比去年['连带'];
       $同比天['客单'] = $同比今年['客单'] - $同比去年['客单'];
       $同比天['件单'] = $同比今年['件单'] - $同比去年['件单'];
       $同比天['销售日期'] = $select_同比今年[0]['销售日期'];

       // 上线时不要屏蔽
       $this->db_easyA->table('cwl_ldkdjd_handle')->insert($同比天);    
       $this->db_easyA->table('cwl_ldkdjd_handle')->insert($同比今年);    
       $this->db_easyA->table('cwl_ldkdjd_handle')->insert($同比去年);    
       
       // die;
       if (date('d', strtotime($current_today)) == 1) {
           echo '1号';
           $总计累计 = [];
           $总计累计['类型'] = '总计累计';
           $总计累计['性质'] = $select_总计天[0]['性质'];
           $总计累计['销量'] = $select_总计天[0]['销量'];
           $总计累计['单数'] = $select_总计天[0]['单数'];
           $总计累计['正品业绩'] = $select_总计天[0]['正品业绩'];
           $总计累计['连带'] = $select_总计天[0]['连带'];
           $总计累计['客单'] = $select_总计天[0]['客单'];
           $总计累计['件单'] = $select_总计天[0]['件单'];
           $总计累计['销售日期'] = $select_总计天[0]['销售日期'];

           // 上线时不要屏蔽
           $this->db_easyA->table('cwl_ldkdjd_handle')->insert($总计累计);  

           $同比累计 = [];
           $同比累计['类型'] = '同比累计';
           $同比累计['性质'] = $同比天['性质'];
           $同比累计['销量'] = $同比天['销量'];
           $同比累计['单数'] = $同比天['单数'];
           $同比累计['正品业绩'] = $同比天['正品业绩'];
           $同比累计['连带'] = $同比天['连带'];
           $同比累计['客单'] = $同比天['客单'];
           $同比累计['件单'] = $同比天['件单'];
           $同比累计['销售日期'] = $同比天['销售日期'];

           // 上线时不要屏蔽
           $this->db_easyA->table('cwl_ldkdjd_handle')->insert($同比累计);  
       } else {
           echo '不是1号';

           // 总计累计
           $find_昨天_总计累计 = $this->db_easyA->table('cwl_ldkdjd_handle')->where([
               ['类型', '=', '总计累计'],
               ['销售日期', '=', date('Y-m-d', strtotime('-1DAY', strtotime($current_today)))]
           ])->find();  
           // $select_直营天

           // dump($find_昨天);
           $总计累计 = [];
           $总计累计['类型'] = '总计累计';
           $总计累计['性质'] = $select_总计天[0]['性质'];
           $总计累计['销量'] = $select_总计天[0]['销量'] + $find_昨天_总计累计['销量'];
           $总计累计['单数'] = $select_总计天[0]['单数'] + $find_昨天_总计累计['单数'];
           $总计累计['正品业绩'] = $select_总计天[0]['正品业绩'] + $find_昨天_总计累计['正品业绩'];
           $总计累计['连带'] = round($总计累计['销量'] / $总计累计['单数'], 1);
           $总计累计['客单'] = round($总计累计['正品业绩'] / $总计累计['单数'], 1);
           $总计累计['件单'] = round($总计累计['正品业绩'] / $总计累计['销量'], 1);
           $总计累计['销售日期'] = $select_总计天[0]['销售日期'];

           // 上线时不要屏蔽
           $this->db_easyA->table('cwl_ldkdjd_handle')->insert($总计累计);  

           // 同比累计
           $month_start = date('Y-m-01', time());
           $同比今年 = "
                   SELECT 
                       sum(销量) AS 销量,
                       sum(单数) AS 单数,
                       sum(正品业绩) AS 正品业绩
                   FROM `cwl_ldkdjd_handle` 
                   where 
                       类型= '同比今年'
                       AND 销售日期 >= '{$month_start}' 
                       AND 销售日期 <= '{$current_today}'
               ";
           $find_同比今年 = $this->db_easyA->query($同比今年);

           $同比去年 = "
                   SELECT 
                       sum(销量) AS 销量,
                       sum(单数) AS 单数,
                       sum(正品业绩) AS 正品业绩
                   FROM `cwl_ldkdjd_handle` 
                   where 
                       类型= '同比去年'
                       AND 销售日期 >= '{$month_start}' 
                       AND 销售日期 <= '{$current_today}'
               ";
           $find_同比去年 = $this->db_easyA->query($同比去年);


           $同比累计 = [];
           $同比累计['类型'] = '同比累计';
           $同比累计['性质'] = $同比天['性质'];


           // $同比累计['销量'] = $find_同比今年[0]['销量'] - $find_同比去年[0]['销量'];
           $同比累计['单数'] = $find_同比今年[0]['单数'] - $find_同比去年[0]['单数'];
           // $同比累计['正品业绩'] = $find_同比今年[0]['正品业绩'] - $find_同比去年[0]['正品业绩'];
           // $同比累计['正品业绩'] = $同比天['正品业绩'] + $find_昨天_同比累计['正品业绩'];

           $同比累计['连带'] = round( $find_同比今年[0]['销量'] / $find_同比今年[0]['单数'] - $find_同比去年[0]['销量'] / $find_同比去年[0]['单数'], 1 );
           $同比累计['客单'] = round( $find_同比今年[0]['正品业绩'] / $find_同比今年[0]['单数'] - $find_同比去年[0]['正品业绩'] / $find_同比去年[0]['单数'], 1 );
           $同比累计['件单'] = round( $find_同比今年[0]['正品业绩'] / $find_同比今年[0]['销量'] - $find_同比去年[0]['正品业绩'] / $find_同比去年[0]['销量'], 1);
           $同比累计['销售日期'] = $同比天['销售日期'];

           // 上线时不要屏蔽
           $this->db_easyA->table('cwl_ldkdjd_handle')->insert($同比累计);  
       }

       $select_星期 = $this->db_easyA->query("
           select * from cwl_ldkdjd_handle where 星期 is null
       ");
       if ($select_星期) {
           foreach ($select_星期 as $key => $val) {
               $星期 = date_to_week3($val['销售日期']);
               $this->db_easyA->table('cwl_ldkdjd_handle')->where([
                   ['类型', '=', $val['类型']],
                   ['性质', '=', $val['性质']],
                   ['销售日期', '=', $val['销售日期']],
               ])->update(['星期' => $星期]);
           }
       }
   }    
}
