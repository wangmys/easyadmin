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
 * @ControllerAnnotation(title="天气提醒跑数")
 */
class Weathertips extends BaseController
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

    public function customer()
    {
        $sql = "
            select 
                c.CustomItem15 as 云仓,
                c.State as 省份,
                c.CustomItem17 as 商品负责人,
                c.CustomerName as 店铺名称,
                c.CustomerGrade as 店铺等级,
                c.customerCode as 店铺编码,
                c.Region as 区域修订,
                cf.RegionId,
                c.Mathod as 经营属性,
                c.CustomItem36 AS 温带,
                cf.`首单日期` as 开业日期
            From customer as c
            LEFT JOIN customer_first as cf on c.CustomerName = cf.`店铺名称` and c.customerCode = cf.CustomerCode 
            WHERE 
                c.CustomItem36 IS NOT NULL
            ORDER BY 
                c.State, c.Mathod
        ";
		
        $select = $this->db_easyA->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_customer;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_weathertips_customer')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_customer first 更新成功，数量：{$count}！"
            ]);

        }
    }

    // 店铺库存
    public function customerStock_1() {
        $sql = "
            SELECT 
            -- 	TOP 10000
                EBR.Region AS 区域,
                EC.RegionId,
                EC.State AS 省份,
                EC.CustomerName AS 店铺名称,
                EC.CustomerCode AS 店铺编码,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.GoodsName AS 货品名称,
                EG.StyleCategoryName AS 风格,
                EG.GoodsNo AS 货号,
                EC.CustomItem17 AS 商品负责人,
                SUM(ECS.Quantity) AS 店铺库存,
                FORMAT(EC.OpeningDate, 'yyyy-MM-dd') as 开业日期,
                EG.TimeCategoryName2 AS 季节,
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
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpCustomer EC
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            Right JOIN ErpBaseCustomerRegion AS EBR ON EC.RegionId = EBR.RegionId
            LEFT JOIN ErpCustomerStock AS ECS ON EC.CustomerId = ECS.CustomerId
            LEFT JOIN ErpGoods AS EG ON EG.GoodsId = ECS.GoodsId
            WHERE 
                EC.RegionId NOT IN ('8','40', '55', '84', '85',  '97')
                AND EBC.Mathod IN ('直营', '加盟')
                AND EC.ShutOut = 0
                AND EC.CustomerName = '亳州一店'
                AND EG.TimeCategoryName1 IN ('2023')
                AND EG.TimeCategoryName2 IN ( '初春', '正春', '春季', '初秋', '深秋', '秋季' )
                AND EG.CategoryName1 IN ('内搭', '外套', '下装')
            GROUP BY 
                EC.CustomerName,
                EG.GoodsNo,
                EBR.Region,
                EC.State,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.TimeCategoryName2,
                EC.CustomerCode,
                EC.RegionId,
                EC.CustomItem17,
                EG.StyleCategoryName,
                EG.GoodsName,
                FORMAT(EC.OpeningDate, 'yyyy-MM-dd')       
        ";
        $select = $this->db_sqlsrv->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_stock;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_weathertips_stock')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_stock 1 更新成功，数量：{$count}！"
            ]);

        }
    }

    // 在途
    public function getZaitu() {
        $sql2 = "
            SELECT
                T.State AS 省份,
                T.CustomItem17 AS 商品负责人,
                T.CustomerName AS 店铺名称,
                T.CategoryName1 AS 一级分类,
                T.CategoryName2 AS 二级分类,
                T.CategoryName AS 分类,
                T.GoodsNo AS 货号,
                SUM ( T.intransit_quantity ) AS 在途库存
            FROM
                (
                SELECT
                    EC.State,
                    EC.CustomItem17,
                    EC.CustomerId,
                    EC.CustomerName,
                    EG.CategoryName1,
                    EG.CategoryName2,
                    EG.CategoryName,
                    EG.GoodsNo,
                    SUM ( EDG.Quantity ) AS intransit_quantity 
                FROM
                    ErpDelivery ED
                    LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID= EDG.DeliveryID
                    LEFT JOIN ErpCustomer EC ON ED.CustomerId= EC.CustomerId
                    LEFT JOIN ErpGoods EG ON EDG.GoodsId= EG.GoodsId 
                WHERE
                    ED.CodingCode= 'EndNode2' 
                    AND ED.IsCompleted= 0 --AND ED.IsReceipt IS NULL
                    
                    AND ED.DeliveryID NOT IN (
                    SELECT
                        ERG.DeliveryId 
                    FROM
                        ErpCustReceipt ER
                        LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID= ERG.ReceiptID 
                    WHERE
                        ER.CodingCodeText= '已审结' 
                        AND ERG.DeliveryId IS NOT NULL 
                        AND ERG.DeliveryId!= '' 
                    GROUP BY
                        ERG.DeliveryId 
                    ) 
                GROUP BY
                    EC.State,
                    EC.CustomItem17,
                    EC.CustomerId,
                    EC.CustomerName,
                    EG.CategoryName1,
                    EG.CategoryName2,
                    EG.CategoryName,
                    EG.GoodsNo UNION ALL--店店调拨在途
                SELECT
                    EC.State,
                    EC.CustomItem17,
                    EC.CustomerId,
                    EC.CustomerName,
                    EG.CategoryName1,
                    EG.CategoryName2,
                    EG.CategoryName,
                    EG.GoodsNo,
                    SUM ( EIG.Quantity ) AS intransit_quantity 
                FROM
                    ErpCustOutbound EI
                    LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId= EIG.CustOutboundId
                    LEFT JOIN ErpCustomer EC ON EI.InCustomerId= EC.CustomerId
                    LEFT JOIN ErpGoods EG ON EIG.GoodsId= EG.GoodsId 
                WHERE
                    EI.CodingCodeText= '已审结' 
                    AND EI.IsCompleted= 0 
                    AND EI.CustOutboundId NOT IN (
                    SELECT
                        ERG.CustOutboundId 
                    FROM
                        ErpCustReceipt ER
                        LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID= ERG.ReceiptID 
                    WHERE
                        ER.CodingCodeText= '已审结' 
                        AND ERG.CustOutboundId IS NOT NULL 
                        AND ERG.CustOutboundId!= '' 
                    GROUP BY
                        ERG.CustOutboundId 
                    ) 
                    AND EC.ShutOut= 0 
                GROUP BY
                    EC.State,
                    EC.CustomItem17,
                    EC.CustomerId,
                    EC.CustomerName,
                    EG.CategoryName1,
                    EG.CategoryName2,
                    EG.CategoryName,
                    EG.GoodsNo 
                ) T 
            WHERE T.State IS NOT NULL
            GROUP BY
                T.State,
                T.CustomItem17,
                T.CustomerName,
                T.CategoryName1,
                T.CategoryName2,
                T.CategoryName,
                T.GoodsNo;  
        ";
        $select = $this->db_sqlsrv->query($sql2);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_zaitu;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_weathertips_zaitu')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_zaitu 更新成功！"
            ]);
        }
    }

    // 是否调价款 rn=1 是 调价款价格是当前零售价，非调价款当前零售价就是零售价
    public function getRN() {
        $sql = "
            SELECT 
                T.CustomerId AS 店铺ID,
                T.CustomerCode AS 店铺编码,
                T.GoodsNo AS 货号,
                T.Price AS 价格,
                T.RN AS 是否调价款
            FROM 
            (
            SELECT 
                        EC.CustomerId,
                        EC.CustomerCode,
                        EG.GoodsNo,
                        EPT.Price,
                        EPTT.BDate,
                        CONVERT(VARCHAR(10),EP.CreateTime,23) AS CreateTime,
                        Row_Number() OVER (partition by EPC.CustomerId,EPT.GoodsId ORDER BY EP.CreateTime desc) RN
                FROM ErpPromotion EP
                LEFT JOIN ErpPromotionCustomer EPC ON EP.PromotionId=EPC.PromotionId
                LEFT JOIN ErpCustomer EC ON EPC.CustomerId=EC.CustomerId
                LEFT JOIN ErpPromotionTypeEx1 EPT ON EP.PromotionId=EPT.PromotionId
                LEFT JOIN ErpGoods EG ON EPT.GoodsId=EG.GoodsId
                LEFT JOIN ErpPromotionTime  EPTT ON EP.PromotionId=EPTT.PromotionId 
                WHERE  EP.PromotionTypeId=1
                    AND EP.IsDisable=0
                    AND EP.CodingCodeText='已审结'
                    AND EC.MathodId IN (4,7)
                    AND EC.ShutOut=0
                    AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                    AND EG.TimeCategoryName1=2023
                    -- AND (EG.TimeCategoryName2 LIKE '%春%' OR EG.TimeCategoryName2 LIKE '%夏%')
                    AND CONVERT(VARCHAR,GETDATE(),23) BETWEEN EPTT.BDate AND EPTT.EDate
                    -- AND EC.RegionId='91'
            ) T
            WHERE 
            T.RN=1        
        ";
        $select = $this->db_sqlsrv->query($sql);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_rn;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_weathertips_rn')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_rn 更新成功！"
            ]);
        }
    }


    // 更新店铺库存
    public function updateStock() {
        // 是否计算skc
        $sql0 = "
            UPDATE cwl_weathertips_stock
            SET 
                是否计算SKC = 
                CASE
                    WHEN 二级分类 NOT IN ('正统长衬', '套西', '套西裤') THEN '是' ELSE '否'
                END 
            WHERE 
                是否计算SKC IS NULL
        ";
        $this->db_easyA->execute($sql0);  

        // 更新零售价 当前零售价
        // $sql1 = "
        //     UPDATE cwl_weathertips_stock AS st1
        //     LEFT JOIN sp_ww_chunxia_stock AS st2 ON st1.省份 = st2.省份 
        //         AND st1.店铺名称 = st2.店铺名称 
        //         AND st1.一级分类 = st2.一级分类
        //         AND st1.二级分类 = st2.二级分类
        //         AND st1.分类 = st2.分类
        //         AND st1.货号 = st2.货号
        //     SET 
        //         st1.零售价 = st2.零售价,
        //         st1.当前零售价 = st2.当前零售价
        //     WHERE 
        //         st1.零售价 IS NULL
        //         OR st1.当前零售价 IS NULL        
        // ";
        // $this->db_easyA->execute($sql1);

        // 更新调价款
        $sql2 = "
            UPDATE cwl_weathertips_stock AS st1
            LEFT JOIN cwl_weathertips_rn AS rn 
                ON st1.店铺编码 = rn.店铺编码
                AND st1.货号 = rn.货号 
            SET 
                st1.调价款价格 = rn.价格,
                st1.是否调价款 = case
                    when rn.是否调价款 = 1 then '是' else '否'
                end,
                st1.当前零售价 = rn.价格
            WHERE 
                st1.是否调价款 IS NULL  
        ";
        $this->db_easyA->execute($sql2);

        // 更新在途库存
        $sql3 = "
            update cwl_weathertips_stock as s 
            left join cwl_weathertips_zaitu as z
                on s.`省份`=z.省份
                and s.`店铺名称` = z.店铺名称 
                and s.`一级分类`= z.`一级分类` 
                and  s.`二级分类`= z.`二级分类`
                and s.`分类`= z.`分类`
                and s.`货号`= z.`货号`
            set s.在途库存 = z.在途库存,
                s.预计库存 = IFNULL(z.在途库存, 0) + IFNULL(s.店铺库存, 0)
            where 
                s.在途库存 is null
                or s.预计库存 is null
        ";
         $this->db_easyA->execute($sql3);
    }
}
