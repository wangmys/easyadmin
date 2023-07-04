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
    public function yinliuzhanbi_retail_1day() {
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
            $this->db_bi->execute('TRUNCATE cwl_yinliuzhanbi_retail_1day;');

            $select_chunk = array_chunk($select, 500);
    
            foreach($select_chunk as $key => $val) {
                $status = $this->db_bi->table('cwl_yinliuzhanbi_retail_1day')->strict(false)->insertAll($val);
            }
            // $this->db_bi->table('cwl_yinliuzhanbi_retail_1day')->insertAll($select);

            // 零售价
            $sql2 = "
                update cwl_yinliuzhanbi_retail_1day as r left join sjp_goods as g on r.货号 = g.货号 
                    set r.零售价 = g.零售价
                where r.零售价 is null";
            $this->db_bi->execute($sql2);

            // 折率
            $sql3 = "
                update cwl_yinliuzhanbi_retail_1day 
                    set 折率 = ROUND(`当前零售价` / 零售价, 2)
                where 折率 is null";
            $this->db_bi->execute($sql3);

             // 引流款
            $sql4 = "
                update cwl_yinliuzhanbi_retail_1day set 是否调价款 = '是' where 风格='引流款' AND 是否调价款 is null
            ";
            $this->db_bi->execute($sql4);

            // 基本款
            $sql5 = "
                UPDATE cwl_yinliuzhanbi_retail_1day 
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
                'content' => 'cwl_yinliuzhanbi_retail_1day 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_yinliuzhanbi_retail_1day 更新失败！'
            ]);   
        }
    }

    // 数据源加工1
    // 引流款不管打不打折全算调价，基本款折率<1并且低于以上标准的才算调价
    public function yinliuzhanbi_data_1() {
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
                cwl_yinliuzhanbi_data AS scs
            RIGHT JOIN sjp_goods AS sg ON sg.货号=scs.`货号`
            RIGHT JOIN customer AS c ON c.CustomerName = scs.店铺名称
            WHERE 
                scs.货号 is not null
                AND scs.季节 IN ({$this->seasionStr})
        ";

        $select = $this->db_bi->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_bi->execute('TRUNCATE cwl_yinliuzhanbi_data;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_bi->table('cwl_yinliuzhanbi_data')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_yinliuzhanbi_data 1 更新成功，数量：{$count}！"
            ]);
        }
    }   

    // 数据源加工2 是否调价款 调价金额
    // 引流款不管打不打折全算调价，基本款折率<1并且低于以上标准的才算调价
    public function yinliuzhanbi_data_2() {
        // 引流款
        $sql1 = "
            update cwl_yinliuzhanbi_data set 是否调价款 = '是' where 风格='引流款' AND 是否调价款 is null
        ";
        $update = $this->db_bi->execute($sql1);
        // 基本款
        $sql2 = "
            UPDATE cwl_yinliuzhanbi_data 
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
                cwl_yinliuzhanbi_data 
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
                'content' => "cwl_yinliuzhanbi_data 更新成功！"
            ]);
        } else {
            return json([
                'status' => 2,
                'msg' => 'success',
                'content' => "cwl_yinliuzhanbi_data 更新失败！"
            ]);           
        }
    }  

    // 表1 店铺
    public function yinliuzhanbi_customer() {
        $sql = "
            SELECT 
                m.*,
                concat(round((m.引流款_库存 / m.总额_库存 * 100), 2), '%') as 引流占比_库存,
                concat(round((m.引流款_销售 / m.总额_销售 * 100), 2), '%') as 引流占比_销售,
                date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM
            (SELECT
                d.省份,
                d.店铺名称,
                d.一级分类,
                d.二级分类,
                d.分类,
                d.折率,
                d.货号,
                (select sum(t1.调价金额)  from cwl_yinliuzhanbi_data t1 where t1.店铺名称=d.店铺名称 and t1.一级分类=d.一级分类 and t1.二级分类=d.二级分类 and t1.分类=d.分类 and t1.是否调价款='是' ) as 引流款_库存,
                sum(调价金额) as 总额_库存,
                (select sum(t2.销售金额)  from cwl_yinliuzhanbi_retail_1day t2 where t2.店铺名称=d.店铺名称 and t2.一级分类=d.一级分类 and t2.二级分类=d.二级分类 and t2.分类=d.分类 and t2.是否调价款='是' ) as 引流款_销售,
                sum(r.销售金额) as 总额_销售  
            FROM
                cwl_yinliuzhanbi_data as d LEFT JOIN cwl_yinliuzhanbi_retail_1day as r on d.店铺名称 = r.店铺名称 and d.货号=r.货号
            WHERE 1
            --    AND d.店铺名称 = '大石二店'
            group by d.店铺名称, d.一级分类, d.二级分类, d.分类) as m        
        ";

        $select = $this->db_bi->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_bi->table('cwl_yinliuzhanbi_customer')->where([
                ['更新日期', '=', date('Y-m-d')]
            ])->delete();

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_bi->table('cwl_yinliuzhanbi_customer')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_yinliuzhanbi_customer 更新成功，数量：{$count}！"
            ]);
        }
    }

    // 表2 省份
    public function yinliuzhanbi_province() {
        $sql = "
            SELECT 
                m.*,
                concat(round((m.引流款_库存 / m.总额_库存 * 100), 2), '%') as 引流占比_库存,
                concat(round((m.引流款_销售 / m.总额_销售 * 100), 2), '%') as 引流占比_销售,
                date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM
            (SELECT
                d.省份,
            -- 	d.店铺名称,
                d.一级分类,
                d.二级分类,
                d.分类,
                d.折率,
                d.货号,
                (select sum(t1.调价金额)  from cwl_yinliuzhanbi_data t1 where t1.省份=d.省份 and t1.一级分类=d.一级分类 and t1.二级分类=d.二级分类 and t1.分类=d.分类 and t1.是否调价款='是' ) as 引流款_库存,
                sum(调价金额) as 总额_库存,
                (select sum(t2.销售金额)  from cwl_yinliuzhanbi_retail_1day t2 where t2.省份=d.省份 and t2.一级分类=d.一级分类 and t2.二级分类=d.二级分类 and t2.分类=d.分类 and t2.是否调价款='是' ) as 引流款_销售,
                sum(r.销售金额) as 总额_销售
                
            FROM
                cwl_yinliuzhanbi_data as d LEFT JOIN cwl_yinliuzhanbi_retail_1day as r on d.店铺名称 = r.店铺名称 and d.货号=r.货号
            WHERE 1
            group by d.省份, d.一级分类, d.二级分类, d.分类) as m    
        ";

        $select = $this->db_bi->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_bi->table('cwl_yinliuzhanbi_province')->where([
                ['更新日期', '=', date('Y-m-d')]
            ])->delete();

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_bi->table('cwl_yinliuzhanbi_province')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_yinliuzhanbi_province 更新成功，数量：{$count}！"
            ]);
        }
    }
}
