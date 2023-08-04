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
 * @ControllerAnnotation(title="零售核销单报表")
 */
class hexiao extends BaseController
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
        $today = input('date') ? input('date') : date('Y-m-d', time());
        $tomorrow = date('Y-m-d', strtotime('+1day', strtotime($today)));
        
        $year = date('Y', time());
        $sql = "
            SELECT
                EC.State AS 省份,
                EBC.Mathod AS 经营属性,
                EG.TimeCategoryName1 AS 年份,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.GoodsNo AS 货号,
                EG.GoodsId AS 货品编号,
                EGPT_cbj.UnitPrice AS '成本价',
                EGPT_dqjsj.UnitPrice AS '当前结算价',
                EGPT_lsfxj.UnitPrice AS '历史分销价',
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额,
                SUM(ERG.Quantity) AS 销售数量,
                CASE
                    WHEN EG.TimeCategoryName1 = {$year} AND EBC.Mathod = '加盟' THEN EGPT_lsfxj.UnitPrice * SUM(ERG.Quantity) ELSE EGPT_cbj.UnitPrice * SUM(ERG.Quantity)
                END AS 成本金额,
                FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 销售日期
            FROM
                ErpRetail AS ER 
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            LEFT JOIN ErpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            LEFT JOIN ErpGoodsPriceType AS EGPT_cbj ON EGPT_cbj.GoodsId = EG.GoodsId AND EGPT_cbj.PriceName = '成本价'
            LEFT JOIN ErpGoodsPriceType AS EGPT_dqjsj ON EGPT_dqjsj.GoodsId = EG.GoodsId AND EGPT_dqjsj.PriceName = '当前结算价'
            LEFT JOIN ErpGoodsPriceType AS EGPT_lsfxj ON EGPT_lsfxj.GoodsId = EG.GoodsId AND EGPT_lsfxj.PriceName = '历史分销价'
            WHERE
                ER.RetailDate >= '{$today}'
                AND ER.RetailDate < '{$tomorrow}' 
                AND ER.CodingCodeText = '已审结'
                AND EC.ShutOut = 0
                AND EC.RegionId NOT IN ('8','40', '55', '84', '85',  '97')
                AND EG.CategoryName1 IN ('内搭', '外套','下装', '鞋履', '配饰')
                AND EBC.Mathod IN ('直营', '加盟')
            GROUP BY 
                EC.State,
                EBC.Mathod,
                EG.TimeCategoryName1,
                EG.GoodsNo,
                EG.GoodsId,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EGPT_cbj.UnitPrice,
                EGPT_dqjsj.UnitPrice,
                EGPT_lsfxj.UnitPrice,
                FORMAT(ER.RetailDate, 'yyyy-MM-dd')	
            ORDER BY EC.State ASC, EBC.Mathod ASC, FORMAT(ER.RetailDate, 'yyyy-MM-dd') ASC 	
        ";

        // die;
		
        $select = $this->db_sqlsrv->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->table('cwl_hexiao_data')->where([
                ['销售日期', '=', $today]
            ])->delete();

            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_hexiao_data')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_hexiao_data 更新成功，数量：{$count}！"
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "cwl_hexiao_data 更新失败！"
            ]);
        }
    }

    // 日 结果
    public function dayHandle()
    {
        $today = input('date') ? input('date') : date('Y-m-d', time());
        $this->db_easyA->table('cwl_hexiao_res_day')->where([
            ['销售日期', '=', $today]
        ])->delete();

        // 省份加盟、直营
        $sql = "
            SELECT 
                t1.*,
                ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
            FROM (
                SELECT
                    省份, 
                    经营属性,
                    销售日期,
                    ROUND(SUM(销售金额), 2) AS 销售金额,
                    SUM(当前结算价) AS 当前结算价
                FROM
                    `cwl_hexiao_data`
                WHERE 
                    销售日期='{$today}'
                    AND 经营属性 IN ('加盟', '直营')
                GROUP BY 省份,经营属性
                ORDER By 经营属性
            ) AS t1	
        ";

        // 单省 合计
        $sql2 = "
            SELECT 
                t1.*,
                ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
            FROM (
                SELECT
                    省份, 
                    '合计' AS 经营属性,
                    销售日期,
                    ROUND(SUM(销售金额), 2) AS 销售金额,
                    SUM(当前结算价) AS 当前结算价
                FROM
                    `cwl_hexiao_res_day`
                WHERE 
                    销售日期='{$today}'
                    GROUP BY 省份
            ) AS t1	        
        ";

        // 单日统计 直营
        $sql3 = "
            SELECT 
                t1.*,
                ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
            FROM (
                SELECT
                    '1' AS `index`,
                    '单日总计' AS 省份, 
                    经营属性,
                    销售日期,
                    ROUND(SUM(销售金额), 2) AS 销售金额,
                    SUM(当前结算价) AS 当前结算价
                FROM
                    `cwl_hexiao_res_day`
                WHERE 
                    销售日期='{$today}'
                    AND 经营属性 IN ('直营')
                    AND 省份 != '单日总计'
                ORDER BY 经营属性
            ) AS t1	        
        ";

        // 单日统计 加盟
        $sql4 = "
            SELECT 
                t1.*,
                ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
            FROM (
                SELECT
                    '1' AS `index`,
                    '单日总计' AS 省份, 
                    经营属性,
                    销售日期,
                    ROUND(SUM(销售金额), 2) AS 销售金额,
                    SUM(当前结算价) AS 当前结算价
                FROM
                    `cwl_hexiao_res_day`
                WHERE 
                    销售日期='{$today}'
                    AND 经营属性 IN ('加盟')
                    AND 省份 != '单日总计'
                ORDER BY 经营属性
            ) AS t1	        
        ";

        // 单日统计 合计
        $sql5 = "
            SELECT 
                t1.*,
                ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
            FROM (
                SELECT
                    '1' AS `index`,
                    '单日总计' AS 省份, 
                    '合计' AS 经营属性,
                    销售日期,
                    ROUND(SUM(销售金额), 2) AS 销售金额,
                    SUM(当前结算价) AS 当前结算价
                FROM
                    `cwl_hexiao_res_day`
                WHERE 
                    销售日期='{$today}'
                    AND 省份 != '单日总计'
                    AND `经营属性` != '合计'
            ) AS t1	      
        ";
        
        $select = $this->db_easyA->query($sql);

        if ($select) {
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_hexiao_res_day')->strict(false)->insertAll($val);
            }

            // 单省合计
            $select2 = $this->db_easyA->query($sql2);
            $chunk_list0 = array_chunk($select2, 500);
            foreach($chunk_list0 as $key => $val) {
                $this->db_easyA->table('cwl_hexiao_res_day')->strict(false)->insertAll($val);
            }

            // 单日总计
            $select3= $this->db_easyA->query($sql3);
            $select4 = $this->db_easyA->query($sql4);
            $select5 = $this->db_easyA->query($sql5);
            $merge2 = array_merge($select3, $select4, $select5);

            $chunk_list2 = array_chunk($merge2, 500);


            foreach($chunk_list2 as $key => $val) {
                $this->db_easyA->table('cwl_hexiao_res_day')->strict(false)->insertAll($val);
            }    

            // 本月开始
            $current_month  = date("Y-m-01", time()); 

            // 月累计 直营  
            $total_sql1 = "
                SELECT 
                    t1.*,
                    ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                    ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
                FROM (
                    SELECT
                        '2' AS `index`,
                        '本月总计' AS 省份, 
                        经营属性,
                        '{$today}' AS 销售日期,
                        ROUND(SUM(销售金额), 2) AS 销售金额,
                        SUM(当前结算价) AS 当前结算价
                    FROM
                        `cwl_hexiao_res_day`
                    WHERE 
                        销售日期 >= '{$current_month}'
                        AND 销售日期 <= '{$today}'
                        AND 省份 = '单日总计'
                        AND 经营属性 = '直营'
                    ORDER BY 经营属性
                ) AS t1	
            ";

            // 月累计 加盟  
            $total_sql2 = "
                SELECT 
                    t1.*,
                    ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                    ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
                FROM (
                    SELECT
                        '2' AS `index`,
                        '本月总计' AS 省份, 
                        经营属性,
                        '{$today}' AS 销售日期,
                        ROUND(SUM(销售金额), 2) AS 销售金额,
                        SUM(当前结算价) AS 当前结算价
                    FROM
                        `cwl_hexiao_res_day`
                    WHERE 
                        销售日期 >= '{$current_month}'
                        AND 销售日期 <= '{$today}'
                        AND 省份 = '单日总计'
                        AND 经营属性 = '加盟'
                    ORDER BY 经营属性
                ) AS t1	
            ";

            // 月累计 总计  
            $total_sql3 = "
                SELECT 
                    t1.*,
                    ROUND( (t1.销售金额 - t1.当前结算价) / t1.销售金额, 3 ) AS 毛利率,
                    ROUND( (t1.销售金额 - t1.当前结算价) / 10000, 2 )  AS 毛利
                FROM (
                    SELECT
                        '2' AS `index`,
                        '本月总计' AS 省份, 
                        '合计' AS 经营属性,
                        '{$today}' AS 销售日期,
                        ROUND(SUM(销售金额), 2) AS 销售金额,
                        SUM(当前结算价) AS 当前结算价
                    FROM
                        `cwl_hexiao_res_day`
                    WHERE 
                        销售日期 >= '{$current_month}'
                        AND 销售日期 <= '{$today}'
                        AND 省份 = '单日总计'
                        AND `经营属性` = '合计'
                ) AS t1	
            ";

            // 月累计
            $select_total1= $this->db_easyA->query($total_sql1);
            $select_total2 = $this->db_easyA->query($total_sql2);
            $select_total3 = $this->db_easyA->query($total_sql3);
            $merge3 = array_merge($select_total1, $select_total2, $select_total3);

            $chunk_list3 = array_chunk($merge3, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list3 as $key => $val) {
                $this->db_easyA->table('cwl_hexiao_res_day')->strict(false)->insertAll($val);
            }    

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_hexiao_data 更新成功！"
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "cwl_hexiao_data 更新失败！"
            ]);
        }
    }

    public function test() {
        echo  (18808174.38 - 7620489.18) / 10000;
    }
}
