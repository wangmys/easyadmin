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
 * @ControllerAnnotation(title="调价")
 */
class Tiaojia extends BaseController
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

    // 季节
    protected $seasion = '';
    protected $seasionStr = '';
    protected $check二级分类 = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');

        $this->seasion = '夏季';
        $this->seasionStr = $this->seasionHandle($this->seasion);
        $this->check二级分类 = $this->checkTiaojiaFenleiHandle($this->seasion);
    }

    public function seasionHandle($seasion = "夏季") {
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

    // 基本款调价 二级分类
    public function checkTiaojiaFenleiHandle($seasion = "夏季") {
        if ($seasion == '春季') {
            $check二级分类 = "";
        } elseif ($seasion == '夏季') {
            $check二级分类 = " AND (
                (二级分类 = '短T' AND 当前零售价 <= 50) 
                OR (二级分类 = '休闲短衬' AND 当前零售价 <= 80)
                OR (二级分类 = '正统短衬' AND 当前零售价 <= 80) 
                OR (二级分类 = '牛仔短裤' AND 当前零售价 <= 70) 
                OR (二级分类 = '松紧短裤' AND 当前零售价 <= 70) 
                OR (二级分类 = '松紧短裤' AND 当前零售价 <= 70) 
                OR (二级分类 = '牛仔长裤' AND 当前零售价 <= 100) 
                OR (二级分类 = '休闲长裤' AND 当前零售价 <= 100) 
                OR (二级分类 = '松紧长裤' AND 当前零售价 <= 100) 
                OR (二级分类 = '西裤' AND 当前零售价 <= 100) 
                OR (二级分类 = '凉鞋' AND 当前零售价 <= 80) 
                OR (二级分类 = '休闲鞋' AND 当前零售价 <= 80) 
                OR (二级分类 = '正统皮鞋' AND 当前零售价 <= 80)
            )";
        } elseif ($seasion == '秋季') {
            $check二级分类 = "";
        } elseif ($seasion == '冬季') {
            $check二级分类 = "";
        }
        return $check二级分类;
    }   

    // 日销
    public function retail_1day_by_wangwei() {
        $sql = "
            SELECT TOP
                    3000000
                    EC.State AS 省份,
                    EBC.Mathod AS 渠道属性,
                    EC.CustomItem15 AS 店铺云仓,
                    ER.CustomerName AS 店铺名称,
                    EG.TimeCategoryName1 as 年份,
                CASE
                        EG.TimeCategoryName2
                        WHEN '初春' THEN
                        '春季'
                        WHEN '正春' THEN
                        '春季'
                        WHEN '春季' THEN
                        '春季'
                        WHEN '初秋' THEN
                        '秋季'
                        WHEN '深秋' THEN
                        '秋季'
                        WHEN '秋季' THEN
                        '秋季'
                        WHEN '初夏' THEN
                        '夏季'
                        WHEN '盛夏' THEN
                        '夏季'
                        WHEN '夏季' THEN
                        '夏季'
                        WHEN '冬季' THEN
                        '冬季'
                        WHEN '初冬' THEN
                        '冬季'
                        WHEN '深冬' THEN
                        '冬季'
                    END AS 季节归集,
                    EG.TimeCategoryName2 AS 二级时间分类,
                    EG.CategoryName1 AS 一级分类,
                    EG.CategoryName2 AS 二级分类,
                    EG.CategoryName AS 分类,
                    SUBSTRING ( EG.CategoryName, 1, 2 ) AS 领型,
                    EG.StyleCategoryName AS 风格,
                    EG.GoodsNo AS 货号,
                    SUM ( ERG.Quantity * ERG.DiscountPrice ) / SUM (ERG.Quantity) AS 当前零售价,
                    SUM ( ERG.Quantity ) AS 销售数量,
                    SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额,
                    CONVERT(varchar(10),GETDATE(),120) AS 更新日期
                FROM
                    ErpRetail AS ER
                    LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                    LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                    LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                    LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
                WHERE
                    ER.CodingCodeText = '已审结'
                    AND ER.RetailDate >= DATEADD(DAY, -1, CAST(GETDATE() AS DATE))
                    AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                    AND EG.TimeCategoryName2 IN ( {$this->seasionStr} )
                    AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                    AND EC.CustomItem17 IS NOT NULL
                    AND EBC.Mathod IN ('直营', '加盟')
                    AND EG.TimeCategoryName1 IN ('2023')
                GROUP BY
                    EC.CustomItem17
                    ,ER.CustomerName
                    ,EG.GoodsNo
                    ,EC.State
                    ,EC.CustomItem15
                    ,EBC.Mathod
                    ,EG.TimeCategoryName1
                    ,EG.TimeCategoryName2
                    ,EG.CategoryName1
                    ,EG.CategoryName2
                    ,EG.CategoryName
                    ,EG.StyleCategoryName
                HAVING  SUM ( ERG.Quantity ) <> 0
        ";

        $select = $this->db_sqlsrv->query($sql);
        // dump($select);die;
        if ($select) {
            $this->db_bi->execute('TRUNCATE retail_1day_by_wangwei;');

            $select_chunk = array_chunk($select, 500);
    
            foreach($select_chunk as $key => $val) {
                $status = $this->db_bi->table('retail_1day_by_wangwei')->insertAll($val);
            }
            // $this->db_bi->table('retail_1day_by_wangwei')->insertAll($select);

            // 零售价
            $sql2 = "
                update retail_1day_by_wangwei as r left join sjp_goods as g on r.货号 = g.货号 
                    set r.零售价 = g.零售价
                where r.零售价 is null";
            $this->db_bi->execute($sql2);

            // 折率
            $sql3 = "
                update retail_1day_by_wangwei 
                    set 折率 = ROUND(`当前零售价` / 零售价, 2)
                where 折率 is null";
            $this->db_bi->execute($sql3);

             // 引流款
            $sql4 = "
                update retail_1day_by_wangwei set 是否调价款 = '是' where 风格='引流款' AND 是否调价款 is null
            ";
            $this->db_bi->execute($sql4);

            // 基本款
            $sql5 = "
                UPDATE retail_1day_by_wangwei 
                SET 是否调价款 = '是' 
                WHERE
                    风格 = '基本款' 
                    AND 折率 < 1
                    AND 是否调价款 IS NULL
                    {$this->check二级分类}
            ";
            $this->db_bi->execute($sql5);
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'retail_1day_by_wangwei 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'retail_1day_by_wangwei 更新失败！'
            ]);   
        }
    }

    // 数据源加工1
    // 引流款不管打不打折全算调价，基本款折率<1并且低于以上标准的才算调价
    public function sjp_customer_stock_cwl_1() {
        $sql = "
            SELECT
                scs.*,
                ROUND(scs.`当前零售价` / scs.零售价, 2) as 折率,
                sg.一级分类,
                sg.二级分类, 
                sg.分类,
                sg.风格,
                c.State as 省份
            FROM
                sjp_customer_stock AS scs
            RIGHT JOIN sjp_goods AS sg ON sg.货号=scs.`货号`
            RIGHT JOIN customer AS c ON c.CustomerName = scs.店铺名称
            WHERE 
                scs.货号 is not null
                AND scs.季节 IN ({$this->seasionStr})
        ";

        $select = $this->db_bi->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_bi->execute('TRUNCATE sjp_customer_stock_cwl;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_bi->table('sjp_customer_stock_cwl')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "sjp_customer_stock_cwl_1 更新成功，数量：{$count}！"
            ]);
        }
    }   

    // 数据源加工2 是否调价款 调价金额
    // 引流款不管打不打折全算调价，基本款折率<1并且低于以上标准的才算调价
    public function sjp_customer_stock_cwl_2() {
        // 引流款
        $sql1 = "
            update sjp_customer_stock_cwl set 是否调价款 = '是' where 风格='引流款' AND 是否调价款 is null
        ";
        $update = $this->db_bi->execute($sql1);
        // 基本款
        $sql2 = "
            UPDATE sjp_customer_stock_cwl 
            SET 是否调价款 = '是' 
            WHERE
                风格 = '基本款' 
                AND 折率 < 1
                AND 是否调价款 IS NULL
                {$this->check二级分类}
        ";
        $update2 = $this->db_bi->execute($sql2);
        // 调价金额
        $sql3 = "
            UPDATE 
                sjp_customer_stock_cwl 
            SET 
                调价金额 = round(当前零售价 * 合计) 
            WHERE
                调价金额 IS NULL
        ";
        $update3 = $this->db_bi->execute($sql3);

        if ($update || $update2 || $update3) {
            $count = $update + $update2;
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "sjp_customer_stock_cwl_2 更新成功！"
            ]);
        } else {
            return json([
                'status' => 2,
                'msg' => 'success',
                'content' => "sjp_customer_stock_cwl_2 更新失败！"
            ]);           
        }
    }  
}
