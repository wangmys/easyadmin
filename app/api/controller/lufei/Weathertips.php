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
    protected $db_tianqi = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');
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
                c.CustomItem36 AS 温区,
                cf.`首单日期`,
                date_format(now(),'%Y-%m-%d') AS 更新日期 
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

            // 更新秋冬最早日期
            $sql2 = "
            UPDATE 	cwl_weathertips_customer AS c
            LEFT JOIN cwl_weathertips_retail_customer_history AS ch_autumn ON c.店铺名称 = ch_autumn.店铺名称 AND ch_autumn.季节 = '秋季' AND ch_autumn.大类归集 = '内外'
            LEFT JOIN cwl_weathertips_retail_customer_history AS ch_winter ON c.店铺名称 = ch_winter.店铺名称 AND ch_winter.季节 = '冬季' AND ch_winter.大类归集 = '内外'
            LEFT JOIN cwl_weathertips_retail_province_history AS ph_autumn ON c.省份 = ph_autumn.省份 AND c.温区 = ph_autumn.温区 AND ph_autumn.季节 = '秋季' AND ph_autumn.大类归集 = '内外'
            LEFT JOIN cwl_weathertips_retail_province_history AS ph_winter ON c.省份 = ph_winter.省份 AND c.温区 = ph_winter.温区 AND ph_winter.季节 = '冬季' AND ph_winter.大类归集 = '内外'
            SET
                c.`秋季历史最早` = ch_autumn.最早日期,
                c.`冬季历史最早` = ch_winter.最早日期,
                c.`秋季温区最早` = ph_autumn.最早日期,
                c.`冬季温区最早` = ph_winter.最早日期	
        ";
        $this->db_easyA->execute($sql2);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_customer first 更新成功，数量：{$count}！"
            ]);

        }
    }

    
    // 获取天气表 cid
    public function customer_cid() {
        $sql = "
            SELECT
                c.CustomerCode,
                c.CustomerName,
                c.RegionId,
                c.cid,
                u.city AS BdCity,
                date_format(now(),'%Y-%m-%d') AS 更新日期 
            FROM
                customers as c 
            LEFT JOIN city_url AS u ON c.cid = u.cid
            WHERE
                c.cid IS NOT NULL    
        ";
        $select = $this->db_tianqi->query($sql);
        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE customer_cid;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('customer_cid')->strict(false)->insertAll($val);
            }

            // 更新cid和regionid
            $sql2 = "
                update cwl_weathertips_customer as c1
                LEFT JOIN customer_cid as c2 ON c1.店铺编码 = c2.CustomerCode
                set 
                    c1.cid = c2.cid,
                    c1.RegionId = c2.RegionId,
                    c1.绑定城市 = c2.Bdcity
            ";
            $this->db_easyA->execute($sql2);

            // 更新开业日期 
            $sql3 = "
                UPDATE cwl_weathertips_customer AS c1
                LEFT JOIN ( SELECT 开业日期,店铺编码 FROM cwl_weathertips_stock GROUP BY 店铺编码 ) AS s ON c1.店铺编码 = s.`店铺编码` 
                SET c1.`开业日期` = s.开业日期 
                WHERE
                    c1.`开业日期` IS NULL
            ";
            $this->db_easyA->execute($sql3);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "customer_cid 更新成功！"
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
                EC.CustomerName AS 店铺名称,
                EC.CustomerCode AS 店铺编码,
                EC.State AS 省份,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.StyleCategoryName AS 风格,
                EG.GoodsName AS 货品名称,
                EG.GoodsNo AS 货号,
            -- 	EG.GoodsId AS 货品ID,
                EG.UnitPrice / 2 AS 零售价,
                EC.CustomItem17 AS 商品负责人,
                SUM(ECS.Quantity) AS 店铺库存,
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
                FORMAT(EC.OpeningDate, 'yyyy-MM-dd') as 开业日期,
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
                -- AND EC.CustomerName = '亳州一店'
                AND EG.TimeCategoryName1 IN ('2023')
                AND EG.TimeCategoryName2 IN ( '初春', '正春', '春季', '初秋', '深秋', '秋季' )
                AND EG.CategoryName1 IN ('内搭', '外套', '下装')
            GROUP BY 
                EC.CustomerName,
                EG.GoodsNo,
            -- 	EG.GoodsId,
                EG.UnitPrice,
                EBR.Region,
                EC.State,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.StyleCategoryName,
                EC.CustomerCode,
                EC.RegionId,
                EC.CustomItem17,
                EG.GoodsName,
                FORMAT(EC.OpeningDate, 'yyyy-MM-dd'),
                EG.TimeCategoryName2
            -- HAVING  SUM ( ECS.Quantity ) > 0               
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

        // 更新调价款
        $sql2 = "
            UPDATE cwl_weathertips_stock AS st1
            LEFT JOIN cwl_weathertips_rn AS rn 
                ON st1.店铺编码 = rn.店铺编码
                AND st1.货号 = rn.货号 
            SET 
                st1.是否调价款 = case
                        when rn.是否调价款 = 1 then '是' else '否'
                end,
                st1.当前零售价 = case
                    when rn.是否调价款 = 1 then rn.价格 else st1.零售价
                end
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

    // 3天销售
    public function retail_1() {
        $sql = "
            SELECT
                EC.State AS 省份,
                ER.CustomerName AS 店铺名称,
                EC.CustomItem17 AS 商品负责人,
                CASE
                    EG.TimeCategoryName2
                    WHEN '初春' THEN '春季'
                    WHEN '正春' THEN '春季'
                    WHEN '春季' THEN '春季'
                    WHEN '初秋' THEN '秋季'
                    WHEN '深秋' THEN '秋季'
                    WHEN '秋季' THEN '秋季'
                    WHEN '初夏' THEN '夏季'
                    WHEN '盛夏' THEN '夏季'
                    WHEN '夏季' THEN '夏季'
                    WHEN '冬季' THEN '冬季'
                    WHEN '初冬' THEN '冬季'
                    WHEN '深冬' THEN '冬季'
                END AS 季节归集,
                CASE
                    EG.TimeCategoryName2
                    WHEN '初春' THEN '春秋季'
                    WHEN '正春' THEN '春秋季'
                    WHEN '春季' THEN '春秋季'
                    WHEN '初秋' THEN '春秋季'
                    WHEN '深秋' THEN '春秋季'
                    WHEN '秋季' THEN '春秋季'
                    WHEN '初夏' THEN '夏季'
                    WHEN '盛夏' THEN '夏季'
                    WHEN '夏季' THEN '夏季'
                    WHEN '冬季' THEN '冬季'
                    WHEN '初冬' THEN '冬季'
                    WHEN '深冬' THEN '冬季'
                END AS 季节修订,
                EBC.Mathod AS 经营属性,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.StyleCategoryName AS 风格,
                SUM(ERG.Quantity) AS 销售数量,
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额,
                FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 销售日期,   
                CASE
                    WHEN DATEADD(DAY, -3, CAST(GETDATE() AS DATE)) = FORMAT(ER.RetailDate, 'yyyy-MM-dd') THEN '大前天'
                    WHEN DATEADD(DAY, -2, CAST(GETDATE() AS DATE)) = FORMAT(ER.RetailDate, 'yyyy-MM-dd') THEN '前天'
                    WHEN DATEADD(DAY, -1, CAST(GETDATE() AS DATE)) = FORMAT(ER.RetailDate, 'yyyy-MM-dd') THEN '昨天'
                END 日期识别,
                CASE
                    WHEN DATEADD(DAY, -3, CAST(GETDATE() AS DATE)) = FORMAT(ER.RetailDate, 'yyyy-MM-dd') THEN 3
                    WHEN DATEADD(DAY, -2, CAST(GETDATE() AS DATE)) = FORMAT(ER.RetailDate, 'yyyy-MM-dd') THEN 2
                    WHEN DATEADD(DAY, -1, CAST(GETDATE() AS DATE)) = FORMAT(ER.RetailDate, 'yyyy-MM-dd') THEN 1
                END 日期识别B,
                CASE
                    WHEN EG.CategoryName2 NOT IN ('正统长衬', '套西', '套西裤') THEN '是' ELSE '否'
                END AS 是否计算SKC, 
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpRetail AS ER 
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.RetailDate >= DATEADD(DAY, -3, CAST(GETDATE() AS DATE)) 
                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE)) 
                AND ER.CodingCodeText = '已审结'
                AND EC.ShutOut = 0
                AND EC.RegionId NOT IN ('40', '55', '84', '85',  '97')
                AND EG.CategoryName1 IN ('内搭', '外套','下装')
                AND EBC.Mathod IN ('直营', '加盟')
            --    AND EC.CustomerName = '亳州一店'
            GROUP BY 
                ER.CustomerName,
                EC.State,
                EC.CustomItem17,
                EBC.Mathod,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.StyleCategoryName,
                EG.TimeCategoryName2,
                FORMAT(ER.RetailDate, 'yyyy-MM-dd')	
            ORDER BY EC.State ASC, FORMAT(ER.RetailDate, 'yyyy-MM-dd') ASC 
        ";
        $select = $this->db_sqlsrv->query($sql);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_retail_1;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_weathertips_retail_1')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_retail_1 更新成功！"
            ]);
        }
    }

    // 3天销售 计算销售占比
    public function retail_2() {
        $sql = "
            SELECT 
                t.*,
                round(t.销售金额 / t.销售总金额, 2) AS 销售占比
            FROM (
                SELECT
                    省份,
                    商品负责人,
                    经营属性,
                    是否计算SKC,
                    更新日期,
                    日期识别,
                    日期识别B,
                    销售日期,
                    店铺名称,
                    季节修订,
                    一级分类,
                    SUM(销售数量) AS 销售数量,
                    SUM(销售金额) AS 销售金额,
                    (SELECT SUM(销售金额) FROM cwl_weathertips_retail_1 AS m2 WHERE m1.省份=m2.省份 AND m1.店铺名称=m2.店铺名称 AND m1.销售日期 = m2.销售日期) 销售总金额
                FROM
                    `cwl_weathertips_retail_1` AS m1
                WHERE 
                    1
                GROUP BY 
                    店铺名称,
                    季节修订,
                    一级分类,
                    日期识别
                ORDER BY 日期识别B DESC
            ) AS t
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_retail_2;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_weathertips_retail_2')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_retail_2 更新成功！"
            ]);
        }
    }

    // 历史销售启动数据
    public function retail_customer_history() {
        // $sql = "
        //     select 
        //         t.省份,
        //         t.店铺名称,
        //         t.季节归集,
        //         t.一级分类,
        //         sum(t.销售数量) AS 销售数量,
        //         t.销售日期
        //     from (
        //         SELECT
        //             EC.State AS 省份,
        //             ER.CustomerName AS 店铺名称,
        //             CASE
        //                 EG.TimeCategoryName2
        //                 WHEN '初春' THEN
        //                 '春季'
        //                 WHEN '正春' THEN
        //                 '春季'
        //                 WHEN '春季' THEN
        //                 '春季'
        //                 WHEN '初秋' THEN
        //                 '秋季'
        //                 WHEN '深秋' THEN
        //                 '秋季'
        //                 WHEN '秋季' THEN
        //                 '秋季'
        //                 WHEN '初夏' THEN
        //                 '夏季'
        //                 WHEN '盛夏' THEN
        //                 '夏季'
        //                 WHEN '夏季' THEN
        //                 '夏季'
        //                 WHEN '冬季' THEN
        //                 '冬季'
        //                 WHEN '初冬' THEN
        //                 '冬季'
        //                 WHEN '深冬' THEN
        //                 '冬季'
        //             END AS 季节归集,
        //             EG.CategoryName1 AS 一级分类,
        //             SUM(ERG.Quantity) AS 销售数量,
        //             FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 销售日期
        //         FROM
        //                 ErpRetail AS ER 
        //         LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
        //         LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
        //         LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
        //         LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
        //         WHERE
        //             ER.RetailDate >= '2022-07-01'
        //             AND ER.RetailDate < '2023-01-01' 
        //             AND ER.CodingCodeText = '已审结'
        //             AND EC.ShutOut = 0
        //             AND EC.RegionId NOT IN ('40', '55', '84', '85',  '97')
        //             AND EG.CategoryName1 IN ('内搭', '外套','下装')
        //             AND EG.TimeCategoryName2 IN ('初秋', '深秋', '秋季', '初冬', '深冬', '冬季')
        //             AND EBC.Mathod IN ('直营', '加盟')
        //             AND EC.CustomerName = '亳州一店'
        //         GROUP BY 
        //             ER.CustomerName,
        //             EC.State,
        //             EG.TimeCategoryName2,
        //             EG.CategoryName1,
        //             FORMAT(ER.RetailDate, 'yyyy-MM-dd')	
        //     ) as t
        //     GROUP BY t.省份, t.店铺名称, t.季节归集, t.一级分类, t.销售日期
        //     ORDER BY t.省份 ASC, t.销售日期 ASC 
        // ";
        // $select = $this->db_sqlsrv->query($sql);
        // if ($select) {
        //     $this->db_easyA->execute('TRUNCATE cwl_weathertips_retail_customer_history;');

        //     $chunk_list = array_chunk($select, 500);

        //     foreach($chunk_list as $key => $val) {
        //         // 基础结果 
        //         $insert = $this->db_easyA->table('cwl_weathertips_retail_customer_history')->strict(false)->insertAll($val);
        //     }

        //     return json([
        //         'status' => 1,
        //         'msg' => 'success',
        //         'content' => "cwl_weathertips_retail_customer_history 更新成功！"
        //     ]);
        // }
    }

    // 天气历史
    public function getWeather() {
        $dateList = getWeatherDateList(1); 
        // dump($dateList ); die;
        // cid列表
        $cidList = $this->db_easyA->query("
            select cid from cwl_weathertips_customer where cid is not null group by cid
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
                MAX(id) AS id, `cid`,`min_c`,`max_c`,ave_c,`weather_time`,`text_weather` FROM `weather` 
            WHERE `cid` IN ($cidList) 
                AND `weather_time` IN ($dateList)
            GROUP BY cid,weather_time
        ";
        $select = $this->db_tianqi->query($sql);
        $count = count($select);
        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_weathertips_weather;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_weathertips_weather')->strict(false)->insertAll($val);
            }

            // 更新 day_index
            $sql2 = "
                update cwl_weathertips_weather
                    set 
                        day_index = case
                            {$updateSql}
                        end
                where 
                day_index is null
            ";
            $this->db_easyA->execute($sql2);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_weathertips_weather 更新成功，数量：{$count}！"
            ]);

        }
    }

     // 店铺天气
     public function customer_weather() {
        // 每日天气日期，最高最低温度
        $sql = "
            UPDATE cwl_weathertips_customer as m 
                LEFT JOIN cwl_weathertips_weather AS d0 ON d0.day_index = 0 AND d0.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d1 ON d1.day_index = 1 AND d1.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d2 ON d2.day_index = 2 AND d2.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d3 ON d3.day_index = 3 AND d3.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d4 ON d4.day_index = 4 AND d4.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d5 ON d5.day_index = 5 AND d5.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d6 ON d6.day_index = 6 AND d6.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d7 ON d7.day_index = 7 AND d7.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d8 ON d8.day_index = 8 AND d8.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d9 ON d9.day_index = 9 AND d9.cid = m.cid
                LEFT JOIN cwl_weathertips_weather AS d10 ON d10.day_index = 10 AND d10.cid = m.cid
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
                day0_max = d0.max_c + 2,
                day1_min = d1.min_c + 2,
                day1_max = d1.max_c + 2,
                day2_min = d2.min_c + 2,
                day2_max = d2.max_c + 2,
                day3_min = d3.min_c + 2,
                day3_max = d3.max_c + 2,
                day4_min = d4.min_c + 2,
                day4_max = d4.max_c + 2,
                day5_min = d5.min_c + 2,
                day5_max = d5.max_c + 2,
                day6_min = d6.min_c + 2,
                day6_max = d6.max_c + 2,
                day7_min = d7.min_c + 2,
                day7_max = d7.max_c + 2,
                day8_min = d8.min_c + 2,
                day8_max = d8.max_c + 2,
                day9_min = d9.min_c + 2,
                day9_max = d9.max_c + 2,
                day10_min = d10.min_c + 2,
                day10_max = d10.max_c + 2
            WHERE
                1
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
                'content' => "cwl_weathertips_customer 更新成功，数量：{$count}！"
            ]);

        }
    }   

    // 春秋连续天数
    public function weather_handle_1() {
        $select_customer = $this->db_easyA->table('cwl_weathertips_customer')->field('省份')->group('省份')->select()->toArray();
        foreach ($select_customer as $key => $val) {
            $biaozhun_秋 = $this->db_easyA->table('cwl_weathertips_biaozhun')->where([
                ['省份', '=', $val['省份']],
                ['季节', '=', '秋季'],
            ])->find();
            $biaozhun_冬 = $this->db_easyA->table('cwl_weathertips_biaozhun')->where([
                ['省份', '=', $val['省份']],
                ['季节', '=', '冬季'],
            ])->find();
            // echo $this->db_easyA->getLastSql();
            // echo $val['省份'];
            // dump($biaozhun_秋);
            // die;

            // 标准A
            $sqlA_lianxu = "
            update cwl_weathertips_customer
                set 秋季连续天数A = 
                    CASE
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYYY%' THEN 11
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYY%' THEN 10
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 9
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 8
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYY%' THEN 7
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYY%' THEN 6
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYY%' THEN 5
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYY%' THEN 4
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYY%' THEN 3
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YY%' THEN 2
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%Y%' THEN 1
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%NNNNNNNNNNN%' THEN 0
                END,
                冬季连续天数A = 
                    CASE
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYYY%' THEN 11
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYY%' THEN 10
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 9
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 8
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYY%' THEN 7
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYY%' THEN 6
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYY%' THEN 5
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYY%' THEN 4
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYY%' THEN 3
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%YY%' THEN 2
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%Y%' THEN 1
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} then 'Y' ELSE 'N' end
                        ) LIKE '%NNNNNNNNNNN%' THEN 0
                    END
                WHERE 1
                    AND 省份 = '{$val['省份']}'  
                    AND 冬季连续天数A IS NULL OR 秋季连续天数A IS NULL    
            ";
            $this->db_easyA->execute($sqlA_lianxu);

            // 标准B
            $sqlB_lianxu = "
            update cwl_weathertips_customer
                set 秋季连续天数B = 
                    CASE
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYYY%' THEN 11
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYY%' THEN 10
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 9
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 8
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYY%' THEN 7
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYY%' THEN 6
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYY%' THEN 5
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYY%' THEN 4
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYY%' THEN 3
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YY%' THEN 2
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%Y%' THEN 1
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%NNNNNNNNNNN%' THEN 0
                    END,
                冬季连续天数B = 
                    CASE
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYYY%' THEN 11
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYYY%' THEN 10
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 9
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYYYY%' THEN 8
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYYY%' THEN 7
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYYY%' THEN 6
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYYY%' THEN 5
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYYY%' THEN 4
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YYY%' THEN 3
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%YY%' THEN 2
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%Y%' THEN 1
                        WHEN CONCAT(
                            case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE 'N' end,
                            case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} then 'Y' ELSE 'N' end
                        ) LIKE '%NNNNNNNNNNN%' THEN 0
                    END
                WHERE 1
                    AND 省份 = '{$val['省份']}'  
                    AND 冬季连续天数B IS NULL OR 秋季连续天数B IS NULL    
            ";
            $this->db_easyA->execute($sqlB_lianxu);
        }

    }

    // 每天温度满足单独标记  季节提醒识别
    public function weather_handle_2() { 
        $select_customer = $this->db_easyA->table('cwl_weathertips_customer')->field('省份,店铺名称,秋季连续天数A,秋季连续天数B,冬季连续天数A,冬季连续天数B')
        ->where(1)->select()->toArray();
        foreach ($select_customer as $key => $val) {
            $biaozhun_秋 = $this->db_easyA->table('cwl_weathertips_biaozhun')->where([
                ['省份', '=', $val['省份']],
                ['季节', '=', '秋季'],
            ])->find();
            $biaozhun_冬 = $this->db_easyA->table('cwl_weathertips_biaozhun')->where([
                ['省份', '=', $val['省份']],
                ['季节', '=', '冬季'],
            ])->find();

            // 秋 登记哪天
            if ($val['秋季连续天数A'] >= $biaozhun_秋['连续天数A']) {
                // echo 111;
                $sql_秋 = "
                    update cwl_weathertips_customer 
                    set 
                        day0_秋 =  case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day1_秋 =  case when day1_max <= {$biaozhun_秋['最高温A']} AND day1_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day2_秋 =  case when day2_max <= {$biaozhun_秋['最高温A']} AND day2_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day3_秋 =  case when day3_max <= {$biaozhun_秋['最高温A']} AND day3_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day4_秋 =  case when day4_max <= {$biaozhun_秋['最高温A']} AND day4_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day5_秋 =  case when day5_max <= {$biaozhun_秋['最高温A']} AND day5_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day6_秋 =  case when day6_max <= {$biaozhun_秋['最高温A']} AND day6_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day7_秋 =  case when day7_max <= {$biaozhun_秋['最高温A']} AND day7_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day8_秋 =  case when day8_max <= {$biaozhun_秋['最高温A']} AND day8_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day9_秋 =  case when day9_max <= {$biaozhun_秋['最高温A']} AND day9_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end,
                        day10_秋 =  case when day10_max <= {$biaozhun_秋['最高温A']} AND day10_min <= {$biaozhun_秋['最低温A']} THEN 'Y' ELSE NULL end
                    where    
                        省份='{$val['省份']}'
                        AND 店铺名称='{$val['店铺名称']}'
                ";  

                $this->db_easyA->execute($sql_秋);
            } elseif ($val['秋季连续天数B'] >= $biaozhun_秋['连续天数B']) {
                $sql_秋 = "
                    update cwl_weathertips_customer 
                    set 
                        day0_秋 = case when day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day1_秋 = case when day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day2_秋 = case when day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day3_秋 = case when day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day4_秋 = case when day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day5_秋 = case when day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day6_秋 = case when day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day7_秋 = case when day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day8_秋 = case when day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day9_秋 = case when day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day10_秋 = case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end
                    where    
                        省份='{$val['省份']}'
                        AND 店铺名称='{$val['店铺名称']}'
                ";   

                $this->db_easyA->execute($sql_秋);
            } else { // 不满足A、B情况的时候执行C
                $sql_秋_C = "
                    update cwl_weathertips_customer 
                    set 
                        day0_秋 = case when day0_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day0_max <= {$biaozhun_秋['最高温B']} AND day0_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day1_秋 = case when day1_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day1_max <= {$biaozhun_秋['最高温B']} AND day1_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day2_秋 = case when day2_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day2_max <= {$biaozhun_秋['最高温B']} AND day2_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day3_秋 = case when day3_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day3_max <= {$biaozhun_秋['最高温B']} AND day3_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day4_秋 = case when day4_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day4_max <= {$biaozhun_秋['最高温B']} AND day4_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day5_秋 = case when day5_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day5_max <= {$biaozhun_秋['最高温B']} AND day5_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day6_秋 = case when day6_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day6_max <= {$biaozhun_秋['最高温B']} AND day6_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day7_秋 = case when day7_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day7_max <= {$biaozhun_秋['最高温B']} AND day7_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day8_秋 = case when day8_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day8_max <= {$biaozhun_秋['最高温B']} AND day8_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day9_秋 = case when day9_max <= {$biaozhun_秋['最高温A']} AND day0_min <= {$biaozhun_秋['最低温A']} OR day9_max <= {$biaozhun_秋['最高温B']} AND day9_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end,
                        day10_秋 = case when day10_max <= {$biaozhun_秋['最高温B']} AND day10_min <= {$biaozhun_秋['最低温B']} THEN 'Y' ELSE NULL end
                    where    
                        省份='{$val['省份']}'
                        AND 店铺名称='{$val['店铺名称']}'
                ";   

                $this->db_easyA->execute($sql_秋_C); 
            }

            // 冬 登记哪天
            if ($val['冬季连续天数A'] >= $biaozhun_冬['连续天数A']) {
                $sql_冬 = "
                    update cwl_weathertips_customer 
                    set 
                        day0_冬 =  case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day1_冬 =  case when day1_max <= {$biaozhun_冬['最高温A']} AND day1_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day2_冬 =  case when day2_max <= {$biaozhun_冬['最高温A']} AND day2_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day3_冬 =  case when day3_max <= {$biaozhun_冬['最高温A']} AND day3_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day4_冬 =  case when day4_max <= {$biaozhun_冬['最高温A']} AND day4_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day5_冬 =  case when day5_max <= {$biaozhun_冬['最高温A']} AND day5_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day6_冬 =  case when day6_max <= {$biaozhun_冬['最高温A']} AND day6_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day7_冬 =  case when day7_max <= {$biaozhun_冬['最高温A']} AND day7_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day8_冬 =  case when day8_max <= {$biaozhun_冬['最高温A']} AND day8_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day9_冬 =  case when day9_max <= {$biaozhun_冬['最高温A']} AND day9_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end,
                        day10_冬 =  case when day10_max <= {$biaozhun_冬['最高温A']} AND day10_min <= {$biaozhun_冬['最低温A']} THEN 'Y' ELSE NULL end
                    where    
                        省份='{$val['省份']}'
                        AND 店铺名称='{$val['店铺名称']}'
                ";   
                $this->db_easyA->execute($sql_冬);
            } elseif ($val['冬季连续天数B'] >= $biaozhun_冬['连续天数B']) {
                $sql_冬 = "
                    update cwl_weathertips_customer 
                    set 
                        day0_冬 =  case when day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day1_冬 =  case when day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day2_冬 =  case when day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day3_冬 =  case when day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day4_冬 =  case when day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day5_冬 =  case when day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day6_冬 =  case when day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day7_冬 =  case when day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day8_冬 =  case when day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day9_冬 =  case when day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day10_冬 =  case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end
                    where    
                        省份='{$val['省份']}'
                        AND 店铺名称='{$val['店铺名称']}'
                ";   
                $this->db_easyA->execute($sql_冬);
            } else { // 不满足A、B情况的时候执行C
                $sql_冬_C = "
                    update cwl_weathertips_customer 
                    set 
                        day0_冬 = case when day0_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day0_max <= {$biaozhun_冬['最高温B']} AND day0_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day1_冬 = case when day1_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day1_max <= {$biaozhun_冬['最高温B']} AND day1_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day2_冬 = case when day2_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day2_max <= {$biaozhun_冬['最高温B']} AND day2_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day3_冬 = case when day3_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day3_max <= {$biaozhun_冬['最高温B']} AND day3_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day4_冬 = case when day4_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day4_max <= {$biaozhun_冬['最高温B']} AND day4_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day5_冬 = case when day5_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day5_max <= {$biaozhun_冬['最高温B']} AND day5_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day6_冬 = case when day6_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day6_max <= {$biaozhun_冬['最高温B']} AND day6_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day7_冬 = case when day7_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day7_max <= {$biaozhun_冬['最高温B']} AND day7_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day8_冬 = case when day8_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day8_max <= {$biaozhun_冬['最高温B']} AND day8_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day9_冬 = case when day9_max <= {$biaozhun_冬['最高温A']} AND day0_min <= {$biaozhun_冬['最低温A']} OR day9_max <= {$biaozhun_冬['最高温B']} AND day9_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end,
                        day10_冬 = case when day10_max <= {$biaozhun_冬['最高温B']} AND day10_min <= {$biaozhun_冬['最低温B']} THEN 'Y' ELSE NULL end
                    where    
                        省份='{$val['省份']}'
                        AND 店铺名称='{$val['店铺名称']}'
                ";   

                $this->db_easyA->execute($sql_冬_C); 
            }

        }
    }

    // 每天温度满足单独标记  季节提醒识别
    public function weather_handle_3() { 
        $select_customer = $this->db_easyA->table('cwl_weathertips_customer')->field('省份,店铺名称,day3_秋,day4_秋,day5_秋,day6_秋,day7_秋,day8_秋,day9_秋,day10_秋
        ,day3_冬,day4_冬,day5_冬,day6_冬,day7_冬,day8_冬,day9_冬,day10_冬,秋季连续天数A,秋季连续天数B,冬季连续天数A,冬季连续天数B')
        ->where(
            1
            // ['店铺名称' => '广安一店']
        )->select()->toArray();
        foreach ($select_customer as $key => $val) {
            $biaozhun_秋 = $this->db_easyA->table('cwl_weathertips_biaozhun')->where([
                ['省份', '=', $val['省份']],
                ['季节', '=', '秋季'],
            ])->find();
            $biaozhun_冬 = $this->db_easyA->table('cwl_weathertips_biaozhun')->where([
                ['省份', '=', $val['省份']],
                ['季节', '=', '冬季'],
            ])->find();

            

            // 今日起8天 , 满足6天
            $秋季天数C = strlen($val['day3_秋'] . $val['day4_秋'] . $val['day5_秋'] . $val['day6_秋'] . $val['day7_秋'] . $val['day8_秋'] . $val['day9_秋'] . $val['day10_秋']) ;  
            
            $冬季天数C = strlen($val['day3_冬'] . $val['day4_冬'] . $val['day5_冬'] . $val['day6_冬'] . $val['day7_冬'] . $val['day8_冬'] . $val['day9_冬'] . $val['day10_冬']) ;  
           
            $tips1 = '';
            $tips2 = '';
            $tips3 = NULL;
            if ($val['秋季连续天数A'] >= $biaozhun_秋['连续天数A'] || $val['秋季连续天数B'] >= $biaozhun_秋['连续天数B'] || $秋季天数C >= 6) {
                $tips1 = '上秋';
            }

            if ($val['冬季连续天数A'] >= $biaozhun_冬['连续天数A'] || $val['冬季连续天数B'] >= $biaozhun_冬['连续天数B'] || $冬季天数C >= 6) {
                $tips2 = '上冬';
            }

            if ($tips1 && $tips2) {
                $tips3 = '秋冬可上'; 
            } elseif ($tips1) {
                $tips3 = $tips1; 
            } elseif ($tips2) {
                $tips3 = $tips2; 
            }

           
            $sql = "
                update cwl_weathertips_customer 
                set 
                    秋季天数C = {$秋季天数C},
                    冬季天数C = {$冬季天数C},
                    提醒 = '{$tips3}'
                where    
                    省份='{$val['省份']}'
                    AND 店铺名称='{$val['店铺名称']}'
            ";   

            $this->db_easyA->execute($sql); 
        }
    }

    // 各季节基本款skc数计算
    public function weather_handle4() {
        $sql1_春 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 春季SKC基本_内搭
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '春季'
                AND 一级分类 = '内搭'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.春季SKC基本_内搭 = t.`春季SKC基本_内搭`
        ";

        $sql2_春 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 春季SKC基本_外套
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '春季'
                AND 一级分类 = '外套'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.春季SKC基本_外套 = t.`春季SKC基本_外套`
        ";

        $sql3_春 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 春季SKC基本_下装
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '春季'
                AND 一级分类 = '下装'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.春季SKC基本_下装 = t.`春季SKC基本_下装`
        ";

        $sql1_秋 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 秋季SKC基本_内搭
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '秋季'
                AND 一级分类 = '内搭'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.秋季SKC基本_内搭 = t.`秋季SKC基本_内搭`
        ";

        $sql2_秋 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 秋季SKC基本_外套
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '秋季'
                AND 一级分类 = '外套'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.秋季SKC基本_外套 = t.`秋季SKC基本_外套`
        ";

        $sql3_秋 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 秋季SKC基本_下装
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '秋季'
                AND 一级分类 = '下装'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.秋季SKC基本_下装 = t.`秋季SKC基本_下装`
        ";

        $sql1_冬 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 冬季SKC基本_内搭
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '冬季'
                AND 一级分类 = '内搭'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.冬季SKC基本_内搭 = t.`冬季SKC基本_内搭`
        ";

        $sql2_冬 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 冬季SKC基本_外套
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '冬季'
                AND 一级分类 = '外套'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.冬季SKC基本_外套 = t.`冬季SKC基本_外套`
        ";

        $sql3_冬 = "
            update cwl_weathertips_customer as c 
            left join (
            SELECT
                店铺名称,
                店铺编码,
                sum(case when 是否调价款 = '否' then 1 else 0 end) as 冬季SKC基本_下装
            FROM
                `cwl_weathertips_stock` 
            WHERE 1
                AND 预计库存 > 0
                AND 是否计算SKC = '是'
                AND 季节归集 = '冬季'
                AND 一级分类 = '下装'
                AND 风格 in ('基本款')
            GROUP BY 店铺名称
            ) AS t ON c.`店铺编码` = t.`店铺编码`
            set c.冬季SKC基本_下装 = t.`冬季SKC基本_下装`
        ";

        $this->db_easyA->execute($sql1_春);
        $this->db_easyA->execute($sql2_春);
        $this->db_easyA->execute($sql3_春);
        $this->db_easyA->execute($sql1_秋);
        $this->db_easyA->execute($sql2_秋);
        $this->db_easyA->execute($sql3_秋);
        $this->db_easyA->execute($sql1_冬);
        $this->db_easyA->execute($sql2_冬);
        $this->db_easyA->execute($sql3_冬);
    }

}
