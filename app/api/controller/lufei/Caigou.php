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
 * @ControllerAnnotation(title="采购自动推")
 */
class Caigou extends BaseController
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

    // 每天00点前跑 采购订单
    public function first() {
        // 采购订单  订单云仓：4春夏秋冬正品仓+过账+过季
        $sql_采购订单 = "
                SELECT 
                    T.GoodsNo AS 货号,
                    SUM(T.Quantity) AS 累计下单数,
                    -- SUM(CASE WHEN T.RN=1 THEN T.Quantity END ) AS 首单数量,
                    SUM(CASE WHEN T.NatureName LIKE '%首单%' OR T.NatureName LIKE '%加单%' THEN T.Quantity END ) AS 首单数量,
                    -- SUM(CASE WHEN T.RN!=1 THEN T.Quantity END ) AS 总补单数,
                    SUM(CASE WHEN T.NatureName LIKE '%补单%' THEN T.Quantity END ) AS 总补单数,
                    -- MAX(T.RN) -1 AS 补单次数,
                    COUNT(CASE WHEN T.NatureName LIKE '%补单%' THEN 1 END ) 补单次数,
                    STUFF(
                        (
                            SELECT ',' + T1.PurchaseDate
                            FROM  (
                                                SELECT 
                                                    EG.GoodsNo,
                                                    EPG.Quantity,
                                                    CONVERT(VARCHAR(10),EP.PurchaseDate,23) PurchaseDate,
                                                    NatureName,
                                                    EP.PurchaseID,
                                                    rank() OVER (PARTITION BY EPG.GoodsId ORDER BY EP.PurchaseDate) RN,
                                                    CONVERT(VARCHAR(10),EPG.DeliveryDate,23) DeliveryDate,
                                                    EP.IsCompleted
                                                FROM ErpPurchase EP
                                                LEFT JOIN ErpPurchaseGoods EPG ON EP.PurchaseID=EPG.PurchaseID
                                                LEFT JOIN ErpGoods EG ON EPG.GoodsId=EG.GoodsId
                                                WHERE EG.TimeCategoryName1 IN (2022,2023,2024)
                                                    -- AND EG.TimeCategoryName2 IN ('初春','正春','春季','初夏','盛夏','夏季','初秋','深秋','秋季','初冬','深冬','冬季')
                                                    AND EP.PurchaseDate >= '2023-01-01'
                                                    AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                                                    AND EP.ReceiptWareId IN ('K391000031','K391000032','K391000033','K391000034','K391000046','K391000014','K391000055')
                                                    AND EP.CodingCodeText='已审结'
                                                ) T1
                                WHERE T.GoodsNo = T1.GoodsNo AND T1.IsCompleted=0
                                FOR XML PATH('')
                            ), 1, 1, ''
                        ) AS 单据日期 ,
                    STUFF(
                            (
                                SELECT ',' + T1.DeliveryDate
                                FROM  (
                                                SELECT 
                                                    EG.GoodsNo,
                                                    EPG.Quantity,
                                                    CONVERT(VARCHAR(10),EP.PurchaseDate,23) PurchaseDate,
                                                    NatureName,
                                                    EP.PurchaseID,
                                                    rank() OVER (PARTITION BY EPG.GoodsId ORDER BY EP.PurchaseDate) RN,
                                                    CONVERT(VARCHAR(10),EPG.DeliveryDate,23) DeliveryDate,
                                                    EP.IsCompleted
                                                FROM ErpPurchase EP
                                                LEFT JOIN ErpPurchaseGoods EPG ON EP.PurchaseID=EPG.PurchaseID
                                                LEFT JOIN ErpGoods EG ON EPG.GoodsId=EG.GoodsId
                                                WHERE EG.TimeCategoryName1 IN (2022,2023,2024)
                                                    -- AND EG.TimeCategoryName2 IN ('初春','正春','春季','初夏','盛夏','夏季','初秋','深秋','秋季','初冬','深冬','冬季')
                                                    AND EP.PurchaseDate >= '2023-01-01'
                                                    AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                                                    AND EP.ReceiptWareId IN ('K391000031','K391000032','K391000033','K391000034','K391000046','K391000014','K391000055')
                                                    AND EP.CodingCodeText='已审结'
                                                ) T1
                                WHERE T.GoodsNo = T1.GoodsNo AND T1.IsCompleted=0
                                FOR XML PATH('')
                            ), 1, 1, ''
                        ) AS 交货日期 ,
                        CONVERT(varchar(10),GETDATE(),120)  AS 查询日期,
                        DATEADD(DAY, +1, CAST(GETDATE() AS DATE)) AS 更新日期
                FROM 
                (
                SELECT 
                    EG.GoodsNo,
                    EPG.Quantity,
                    CONVERT(VARCHAR(10),EP.PurchaseDate,23) PurchaseDate,
                    NatureName,
                    EP.PurchaseID,
                    rank() OVER (PARTITION BY EPG.GoodsId ORDER BY EP.PurchaseDate) RN,
                    CONVERT(VARCHAR(10),EPG.DeliveryDate,23) DeliveryDate,
                    EP.IsCompleted
                FROM ErpPurchase EP
                LEFT JOIN ErpPurchaseGoods EPG ON EP.PurchaseID=EPG.PurchaseID
                LEFT JOIN ErpGoods EG ON EPG.GoodsId=EG.GoodsId
                WHERE EG.TimeCategoryName1 IN (2022,2023,2024)
                    -- AND EG.TimeCategoryName2 IN ('初春','正春','春季','初夏','盛夏','夏季','初秋','深秋','秋季','初冬','深冬','冬季')
                    AND EP.PurchaseDate >= '2023-01-01'
                    AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                    AND EP.ReceiptWareId IN ('K391000031','K391000032','K391000033','K391000034','K391000046','K391000014','K391000055')
                    AND EP.CodingCodeText='已审结'
                ) T
                GROUP BY 
                    T.GoodsNo
        ";

        $select_采购订单 = $this->db_sqlsrv->query($sql_采购订单);
        if ($select_采购订单) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_order;');
            $chunk_list = array_chunk($select_采购订单, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_order')->strict(false)->insertAll($val);
            }
        }
    }

    // 每天0点前跑  总库存量
    public function second() {
        // 总库存量：仓库库存（五大+过账+广州过季）+门店库存+仓库发货在途+店店调拨在途+门店退仓未完成
        $sql_总库存量 = "
                    select 
                        t.货号,SUM(t.Quantity) AS 总库存量,
                        CONVERT(varchar(10),GETDATE(),120)  AS 查询日期,
                        DATEADD(DAY, +1, CAST(GETDATE() AS DATE)) AS 更新日期
                    from 
                    (
                                -- 8大云仓 			
                                SELECT
                                        m1.GoodsNo AS 货号,
                                        SUM(m1.Quantity) AS Quantity
                                FROM
                                (
                                    SELECT 
                                            EG.GoodsNo,
                                            SUM(EWSD.Quantity) AS Quantity
                                    FROM ErpWarehouseStock EWS
                                    LEFT JOIN ErpWarehouseStockDetail EWSD ON EWS.StockId=EWSD.StockId
                                    LEFT JOIN ErpWarehouse EW ON EWS.WarehouseId=EW.WarehouseId
                                    LEFT JOIN ErpGoods EG ON EG.GoodsId=EWS.GoodsId
                                    WHERE EWS.WarehouseId IN ('K391000040','K391000041','K391000042','K391000043','K391000044','K391000014', 'K391000046', 'K391000015')
                                            AND EG.TimeCategoryName1 in (2023, 2024)
                                            AND EG.TimeCategoryName2 LIKE '%冬%'
                                            AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                                    GROUP BY 
                                            EG.GoodsNo
                                ) m1
                                
                                GROUP BY
                                        m1.GoodsNo
                    
                            UNION ALL
                            
                            -- 店铺库存
                            SELECT 
                                EG.GoodsNo AS 货号,
                                SUM(ECSD.Quantity) AS Quantity
                            FROM ErpCustomerStock ECS 
                            LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId = ECSD.StockId
                            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
                            WHERE EC.MathodId IN (4,7)
                            AND EC.ShutOut=0
                            AND EG.TimeCategoryName1 IN ( '2023', '2024' ) 
                            AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                            GROUP BY 
                                EG.GoodsNo
                            HAVING SUM(ECSD.Quantity)!=0
                            
                            UNION ALL
                            
                            --仓库发货在途
                            SELECT  
                                EG.GoodsNo AS 货号,
                                SUM(EDGD.Quantity) AS Quantity
                            FROM ErpDelivery ED 
                            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
                            LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
                            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
                            WHERE ED.CodingCodeText='已审结'
                                AND ED.IsCompleted=0
                                AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
                                AND EC.MathodId IN (4,7)
                                AND EC.ShutOut=0
                                AND EG.TimeCategoryName1 IN ( '2023', '2024' ) 
                                AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')	
                            GROUP BY  
                                EG.GoodsNo
                                
                            UNION ALL
                    
                            --店铺调拨在途
                            SELECT 
                                EG.GoodsNo AS 货号,
                                SUM(EIGD.Quantity) AS Quantity
                            FROM ErpCustOutbound EI 
                            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
                            LEFT JOIN ErpCustOutboundGoodsDetail EIGD ON EIG.CustOutboundGoodsId=EIGD.CustOutboundGoodsId
                            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
                            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
                            WHERE EI.CodingCodeText='已审结'
                                AND EI.IsCompleted=0
                                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' 
                                AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                                AND EC.MathodId IN (4,7)
                                AND EC.ShutOut=0
                                AND EG.TimeCategoryName1 IN ( '2023', '2024' ) 
                                AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')	
                            GROUP BY  
                                EG.GoodsNo
                                
                            UNION ALL
                    
                            -- 门店退仓未完成	
                            SELECT 
                                    EG.GoodsNo AS 货号,
                                    SUM (ERGD.Quantity ) AS Quantity
                            FROM ErpReturn ER 
                            LEFT JOIN ErpReturnGoods ERG ON ER.ReturnID=ERG.ReturnID
                            LEFT JOIN ErpGoods EG ON EG.GoodsId=ERG.GoodsId
                            LEFT JOIN ErpReturnGoodsDetail ERGD ON ERG.ReturnGoodsID=ERGD.ReturnGoodsID
                            LEFT JOIN ErpBarCode EBC ON ERG.GoodsId = EBC.GoodsId AND ERGD.ColorId= EBC.ColorId AND ERGD.SizeId = EBC.SizeId
                            WHERE ER.CodingCodeText='已审结' 
                                    AND (ER.IsCompleted=0 OR ER.IsCompleted IS NULL)	
                                    AND EG.TimeCategoryName1 IN ( '2023', '2024' ) 
                    -- 				AND EG.TimeCategoryName2 IN ('初冬', '深冬', '冬季')
                                    AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')				
                                    AND ER.ReturnID NOT IN (SELECT ER.ReturnId FROM ErpReceipt ER WHERE ER.CodingCodeText='已审结' AND ER.ReturnId!='' AND ER.ReturnId IS NOT NULL )
                            GROUP BY EG.GoodsNo 
                    ) AS t
                    GROUP BY 
                        t.货号
    
        ";
        $select_总库存量 = $this->db_sqlsrv->query($sql_总库存量);
        if ($select_总库存量) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_stock;');
            $chunk_list = array_chunk($select_总库存量, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_stock')->strict(false)->insertAll($val);
            }
        }
    }

    // 采购收货
    public function caigoushouhuo() {
        // 1月1至 昨天的
        $sql_采购收货 = "
        --  采购收货
            SELECT
                EG.TimeCategoryName1 AS 年份,
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
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.GoodsName AS 货品名称,
                EG.CategoryName AS 分类,
                SUBSTRING ( EG.CategoryName, 1, 2 ) AS 领型,
                EG.StyleCategoryName AS 风格,
                EG.GoodsNo AS 货号,
                EG.GoodsId,
                -- SUM(ERG.Quantity) AS 采购入库量,
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpReceipt AS ER
                LEFT JOIN ErpWarehouse AS EW ON ER.WarehouseId = EW.WarehouseId
                LEFT JOIN ErpReceiptGoods AS ERG ON ER.ReceiptId = ERG.ReceiptId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
                LEFT JOIN erpGoodsColor AS EGC ON ERG.GoodsId = EGC.GoodsId
                LEFT JOIN ErpSupply AS ES ON ER.SupplyId = ES.SupplyId 
            WHERE
                ER.CodingCodeText = '已审结' 
                AND ER.ReceiptDate >= '2023-01-01'
                AND ER.ReceiptDate < DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) )
                AND ER.Type= 1 
                AND ES.SupplyName <> '南昌岳歌服饰' 
                AND EG.TimeCategoryName1 IN ( '2023','2024' ) 
                AND EG.TimeCategoryName2 IN ('初冬', '深冬', '冬季')
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EG.StyleCategoryName = '基本款'
                AND EW.WarehouseName IN ( '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓','广州过季仓','过账虚拟仓', '常熟正品仓库') 
            GROUP BY
                EG.GoodsNo
                ,EG.GoodsId
                ,EG.GoodsName 
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
                ,EG.StyleCategoryName
        ";
        $select = $this->db_sqlsrv->query($sql_采购收货);
        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_caigoushouhuo;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_caigoushouhuo')->strict(false)->insertAll($val);
            }
        }

        $sql_采购收货pro = "
                --仓库收采购单
                SELECT 
                    t.GoodsNo as 货号,
                    SUM(t.采购收货量) AS 采购收货量,
                    SUM(t.采购退货量) AS 采购退货量,
                    SUM(t.采购入库量) AS 采购入库量,
                    CONVERT(varchar(10),GETDATE(),120) AS 更新日期
                FROM 
                (
                    SELECT
                        EG.GoodsNo,
                        SUM(ERG.Quantity) AS 采购收货量,
                        0 AS 采购退货量,
                        SUM(ERG.Quantity) AS 采购入库量
                    FROM ErpReceipt ER 
                    LEFT JOIN ErpWarehouse AS EW ON ER.WarehouseId = EW.WarehouseId
                    LEFT JOIN ErpReceiptGoods ERG ON ER.ReceiptId=ERG.ReceiptId
                    LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
                    WHERE EG.TimeCategoryName1=2023
                        AND ER.ReceiptDate >= '2023-01-01'
                        AND ER.ReceiptDate < DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) )
                        AND EG.TimeCategoryName2 IN ('初冬','深冬','冬季')
                        AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                        AND ER.Type=1 
                        AND ER.CodingCodeText='已审结'
                        AND EG.StyleCategoryName = '基本款'
                        AND EW.WarehouseName IN ( '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓','广州过季仓','过账虚拟仓', '常熟正品仓库') 
                        AND ER.SupplyId !='K191000638'
                    GROUP BY EG.GoodsNo

                    UNION ALL
                    --采购退货

                    SELECT 
                        EG.GoodsNo,
                        0 AS 采购收货量,
                        SUM(EPRG.Quantity) AS 采购退货量,
                        -SUM(EPRG.Quantity)  AS 采购入库量
                    FROM ErpPurchaseReturn EPR 
                    LEFT JOIN ErpWarehouse AS EW ON EPR.WarehouseId = EW.WarehouseId
                    LEFT JOIN ErpPurchaseReturnGoods EPRG ON EPR.PurchaseReturnId=EPRG.PurchaseReturnId
                    LEFT JOIN ErpGoods EG ON EPRG.GoodsId=EG.GoodsId
                    WHERE EG.TimeCategoryName1=2023
                        AND EPR.PurchaseReturnDate >= '2023-01-01'
                        AND EPR.PurchaseReturnDate < DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) )
                        AND EG.TimeCategoryName2 IN ('初冬','深冬','冬季')
                        AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                        AND EPR.CodingCodeText='已审结'
                        AND EG.StyleCategoryName = '基本款'
                        AND EW.WarehouseName IN ( '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓','广州过季仓','过账虚拟仓', '常熟正品仓库') 
                        AND EPR.SupplyId !='K191000638'
                    GROUP BY EG.GoodsNo
                ) as t
                GROUP BY t.GoodsNo
        ";
        $select_采购收货pro = $this->db_sqlsrv->query($sql_采购收货pro);
        if ($select_采购收货pro) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_caigoushouhuo_data;');
            $chunk_list5 = array_chunk($select_采购收货pro, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list5 as $key5 => $val5) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_caigoushouhuo_data')->strict(false)->insertAll($val5);
            }
        }

        $sql_更新采购入库量  = "
            update cwl_cgzdt_caigoushouhuo as c
            left join cwl_cgzdt_caigoushouhuo_data as d on c.货号=d.货号
            set
                c.采购入库量 = d.采购入库量
            where 
                c.货号=d.货号
        ";
        $this->db_easyA->execute($sql_更新采购入库量);

        // die;

        $sql_采购入库指令单未完成 = "
                SELECT
                EG.TimeCategoryName1 AS 年份,
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
                END AS 季节,
                EG.TimeCategoryName2 AS 二级时间,
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.GoodsName AS 货号名称,
                EG.CategoryName AS 分类,
                SUBSTRING ( EG.CategoryName, 1, 2 ) AS 领型,
                EG.StyleCategoryName AS 风格,
                EG.GoodsNo AS 货号,
                EGC.ColorDesc AS 颜色,
                SUM(ERNG.Quantity) AS 数量,
                                CONVERT(varchar(10),GETDATE(),120) AS 更新日期,
                ES.SupplyName AS 供应商 
            FROM
                ErpReceiptNotice AS ERN
                LEFT JOIN ErpWarehouse AS EW ON ERN.WarehouseId = EW.WarehouseId
                LEFT JOIN ErpReceiptNoticeGoods AS ERNG ON ERN.ReceiptNoticeId = ERNG.ReceiptNoticeId
                LEFT JOIN erpGoods AS EG ON ERNG.GoodsId = EG.GoodsId
                LEFT JOIN erpGoodsColor AS EGC ON ERNG.GoodsId = EGC.GoodsId
                LEFT JOIN ErpSupply AS ES ON ERN.SupplyId = ES.SupplyId 
            WHERE
                ERN.CodingCodeText = '已审结' 
                AND ERN.ReceiptNoticeDate < DATEADD( DAY, 0, CAST ( GETDATE( ) AS DATE ) ) 
                AND ERN.IsCompleted != 1
                AND ES.SupplyName <> '南昌岳歌服饰' 
                AND EG.TimeCategoryName1 IN ( '2023', '2024' ) 
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EW.WarehouseName IN ( '过账虚拟仓', '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓','广州过季仓', '常熟正品仓库') 
            GROUP BY
                ES.SupplyName
                ,EG.GoodsNo
                ,EG.GoodsName 
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
                ,EG.StyleCategoryName
                ,EGC.ColorDesc
        ";
        $select_采购入库指令单未完成 = $this->db_sqlsrv->query($sql_采购入库指令单未完成);
        if ($select_采购入库指令单未完成) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_weiwancheng;');
            $chunk_list2 = array_chunk($select_采购入库指令单未完成, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list2 as $key2 => $val2) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_weiwancheng')->strict(false)->insertAll($val2);
            }
        }

        $sql_订单总量 = "
            update cwl_cgzdt_caigoushouhuo as c
            left join cwl_cgzdt_order as o on c.货号 = o.货号
            set c.订单总量 = o.累计下单数
            where
                c.货号 = o.货号
        ";
        $this->db_easyA->execute($sql_订单总量);

        $sql_订单未入量 = "
            update cwl_cgzdt_caigoushouhuo 
            set 订单未入量 = 订单总量 - 采购入库量
            where
                订单总量 is not null
                and 采购入库量 is not null
        ";
        $this->db_easyA->execute($sql_订单未入量);

        $sql_总库存量 = "
            update cwl_cgzdt_caigoushouhuo as c 
            left join cwl_cgzdt_stock as s on c.货号 = s.货号
            SET
                c.总库存量 = s.总库存量
            where
                c.货号 = s.货号
        ";
        $this->db_easyA->execute($sql_总库存量);

        $sql_零售价_成本价_编码 = "
            update cwl_cgzdt_caigoushouhuo as c 
            left join sjp_goods as g on c.货号 = g.货号
            SET
                c.简码 = g.简码,
                c.零售价 = g.零售价,
                c.成本价 = g.成本价
            where
                c.货号 = g.货号
        ";
        $this->db_easyA->execute($sql_零售价_成本价_编码);
    }

    // 今日销，两周销，累销
    public function retail()
    {
        $sql_累销 = "
            SELECT TOP
                1000000
                EG.GoodsNo  AS 货号,
                EG.GoodsName AS 货品名称,
                EG.TimeCategoryName1 AS 年份,
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
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.CategoryName AS 分类,
                EG.StyleCategoryName AS 风格,
                SUM(ERG.Quantity) AS 累销量,
                SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 累销金额,
                concat('2023-07-01 至 ', DATEADD(DAY, -1, CAST(GETDATE() AS DATE))) AS 累销日期,				
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpRetail AS ER
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.CodingCodeText = '已审结'
                AND ER.RetailDate >=  '2023-07-01'
                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EG.TimeCategoryName2 IN ('初冬', '深冬', '冬季')
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023', '2024')
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EG.StyleCategoryName = '基本款'
            GROUP BY
                EG.GoodsNo
                ,EG.GoodsName
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.StyleCategoryName
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
            HAVING SUM ( ERG.Quantity ) <> 0
        ";
        $select_累销 = $this->db_sqlsrv->query($sql_累销);
        if ($select_累销) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_retail;');
            $chunk_list = array_chunk($select_累销, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_retail')->strict(false)->insertAll($val);
            }
        }


        $sql_日销 = "
                SELECT TOP
                    1000000
                    EG.GoodsNo  AS 货号,
                    EG.GoodsName AS 货品名称,
                    EG.TimeCategoryName1 AS 年份,
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
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.CategoryName AS 分类,
                EG.StyleCategoryName AS 风格,
                SUM(ERG.Quantity) AS 销量,
                SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额,
                DATEADD(DAY, -1, CAST(GETDATE() AS DATE)) AS 销售日期,				
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpRetail AS ER
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.CodingCodeText = '已审结'
                AND ER.RetailDate >=  DATEADD(DAY, -1, CAST(GETDATE() AS DATE))
                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EG.TimeCategoryName2 IN ('初冬', '深冬', '冬季')
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023', '2024')
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EG.StyleCategoryName = '基本款'
            GROUP BY
                EG.GoodsNo
                ,EG.GoodsName
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.StyleCategoryName
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
            HAVING SUM ( ERG.Quantity ) <> 0
        ";
        $select_日销 = $this->db_sqlsrv->query($sql_日销);
        if ($select_日销) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_retail_day;');
            $chunk_list2 = array_chunk($select_日销, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list2 as $key2 => $val2) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_retail_day')->strict(false)->insertAll($val2);
            }
        }

        $sql_7天销 = "
                SELECT TOP
                    1000000
                    EG.GoodsNo  AS 货号,
                    EG.GoodsName AS 货品名称,
                    EG.TimeCategoryName1 AS 年份,
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
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.CategoryName AS 分类,
                EG.StyleCategoryName AS 风格,
                SUM(ERG.Quantity) AS 销量,
                SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额,
                concat(DATEADD(DAY, -7, CAST(GETDATE() AS DATE)), ' 至 ' ,DATEADD(DAY, -1, CAST(GETDATE() AS DATE))) AS 销售日期,				
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpRetail AS ER
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.CodingCodeText = '已审结'
                AND ER.RetailDate >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EG.TimeCategoryName2 IN ('初冬', '深冬', '冬季')
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023', '2024')
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EG.StyleCategoryName = '基本款'
            GROUP BY
                EG.GoodsNo
                ,EG.GoodsName
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.StyleCategoryName
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
            HAVING SUM ( ERG.Quantity ) <> 0
        ";
        $select_7天销 = $this->db_sqlsrv->query($sql_7天销);
        if ($select_7天销) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_retail_7day;');
            $chunk_list3 = array_chunk($select_7天销, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list3 as $key3 => $val3) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_retail_7day')->strict(false)->insertAll($val3);
            }
        }

        $sql_14天销 = "
                SELECT TOP
                    1000000
                    EG.GoodsNo  AS 货号,
                    EG.GoodsName AS 货品名称,
                    EG.TimeCategoryName1 AS 年份,
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
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.CategoryName AS 分类,
                EG.StyleCategoryName AS 风格,
                SUM(ERG.Quantity) AS 销量,
                SUM ( ERG.Quantity * ERG.DiscountPrice ) AS 销售金额,
                concat(DATEADD(DAY, -14, CAST(GETDATE() AS DATE)), ' 至 ' ,DATEADD(DAY, -8, CAST(GETDATE() AS DATE))) AS 销售日期,				
                CONVERT(varchar(10),GETDATE(),120) AS 更新日期
            FROM
                ErpRetail AS ER
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
            WHERE
                ER.CodingCodeText = '已审结'
                AND ER.RetailDate >= DATEADD(DAY, -14, CAST(GETDATE() AS DATE))
                AND ER.RetailDate < DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EG.TimeCategoryName2 IN ('初冬', '深冬', '冬季')
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023', '2024')
                AND EG.CategoryName1 IN ( '内搭', '外套', '下装', '鞋履' ) 
                AND EG.StyleCategoryName = '基本款'
            GROUP BY
                EG.GoodsNo
                ,EG.GoodsName
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.StyleCategoryName
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
            HAVING SUM ( ERG.Quantity ) <> 0
        ";
        $select_14天销 = $this->db_sqlsrv->query($sql_14天销);
        if ($select_14天销) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_cgzdt_retail_14day;');
            $chunk_list4 = array_chunk($select_14天销, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list4 as $key4 => $val4) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_cgzdt_retail_14day')->strict(false)->insertAll($val4);
            }
        }
    }

    // 售罄率 排名等
    public function handle_1() {
        $sql_售罄率_累销量_当天销量_云仓在途量 = "
            update `cwl_cgzdt_caigoushouhuo` as c 
            left join cwl_cgzdt_retail_day as d on c.货号=d.货号
            left join cwl_cgzdt_retail_7day as d7 on c.货号=d7.货号
            left join cwl_cgzdt_retail_14day as d14 on c.货号=d14.货号
            left join cwl_cgzdt_retail as r on c.货号=r.货号
            left join cwl_cgzdt_weiwancheng as w on c.货号=w.货号
            set
                c.当天销量 = d.销量,
                c.累销量 = r.累销量,
                c.近一周销量 = d7.销量,
                c.近两周销量 = d14.销量,
                c.售罄率 = case
                    when r.累销量 > 0 then r.累销量 / c.采购入库量 else null
                end,
                c.云仓在途量 = w.数量
        ";
        $this->db_easyA->execute($sql_售罄率_累销量_当天销量_云仓在途量);

        $sql_二级分类售罄 = "
            update cwl_cgzdt_caigoushouhuo as m1 left join 
            (
                SELECT 
                        a.货号,a.大类,a.售罄率,
                        CASE
                            WHEN 
                                a.中类 = @中类
                            THEN
                                    @rank := @rank + 1 ELSE @rank := 1
                        END AS rank,
                        @中类 := a.中类 AS 中类
                FROM 
                (
                        SELECT
                            货号,大类,中类,售罄率 
                        FROM
                            cwl_cgzdt_caigoushouhuo 
                        WHERE
                            中类 IN ( '牛仔长裤', '休闲长裤', '松紧长裤', '卫衣', '保暖内衣', '针织衫', '夹克', '羽绒服', '大衣', '皮衣', '真皮衣')
                            AND 售罄率 is not null
                ) as a,
                ( SELECT @中类 := null, @rank := 0 ) T
                    ORDER BY
                        a.中类, a.售罄率 desc
            ) as m2 on m1.货号 = m2.货号
            set 
                m1.排名 = m2.rank
            where 
                m1.货号 = m2.货号
        ";
        $this->db_easyA->execute($sql_二级分类售罄);        

        $sql_鞋履售罄率 = "
            update cwl_cgzdt_caigoushouhuo as m1 left join 
            (
                SELECT 
                    a.货号,a.中类,a.售罄率,
                    CASE
                        WHEN 
                            a.大类 = @大类
                        THEN
                            @rank := @rank + 1 ELSE @rank := 1
                    END AS rank,
                    @大类 := a.大类 AS 大类
                FROM 
                (
                    SELECT
                        货号,大类,中类,售罄率 
                    FROM
                        cwl_cgzdt_caigoushouhuo 
                    WHERE
                        大类 IN ('鞋履')
                        AND 售罄率 is not null
                ) as a,
                ( SELECT @大类 := null, @rank := 0 ) T
                    ORDER BY
                            a.大类, a.售罄率 desc
            ) as m2 on m1.货号 = m2.货号
            set 
                m1.排名 = m2.rank
            where 
                m1.货号 = m2.货号
        ";
        $this->db_easyA->query($sql_鞋履售罄率); 

        $sql_上柜数 = "
            update cwl_cgzdt_caigoushouhuo as c
            left join (
                SELECT
                    一级分类,二级分类,分类,货号,
                    count(预计库存数量) as 上柜数
                FROM
                    sp_sk  
                WHERE 1
                    AND 预计库存数量 > 0
                GROUP BY
                    货号
            ) as sk on c.货号 = sk.货号
            set
                c.上柜数 = sk.上柜数
            where
                c.货号 = sk.货号        
        ";  
        $this->db_easyA->execute($sql_上柜数);        
        
        $select_config = $this->db_easyA->table('cwl_cgzdt_config')->select();
        // dump($select_config);
        foreach ($select_config as $key => $val) {
            $值 = xmSelectInput($val['值']);
            $sql = "
                update cwl_cgzdt_caigoushouhuo 
                    set TOP = 'Y'
                where {$val['列']} in ({$值}) and 排名 <= {$val['排名']}
            ";
            $this->db_easyA->query($sql);
        }
    }

    // 更新图片路径
    public function handle_2() {
        $sql_TOP = "
            SELECT
                GoodsId 
            FROM
                `cwl_cgzdt_caigoushouhuo` 
            WHERE
                TOP = 'Y'
        ";
        $select_TOP = $this->db_easyA->query($sql_TOP);
        $goodsId = '';
        foreach ($select_TOP as $key => $val) {
            if ($key + 1 < count($select_TOP)) {
                $goodsId .= $val['GoodsId'].',';
            } else {
                $goodsId .= $val['GoodsId'];
            }
        }

        $sql_图片 = "
            SELECT
                GoodsId,Img 
            FROM
                ErpGoodsImg 
            WHERE
                GoodsId IN ( {$goodsId} )
        ";
        $select_图片 = $this->db_sqlsrv->query($sql_图片);
        $select_data = $this->db_easyA->table('cwl_cgzdt_caigoushouhuo')->field('GoodsId')->where(['TOP' => 'Y'])->select();

        foreach ($select_data as $k1 => $v1) {
            foreach ($select_图片 as $k2 => $v2) {
                if ($v1['GoodsId'] == $v2['GoodsId']) {
                    $this->db_easyA->table('cwl_cgzdt_caigoushouhuo')->where(['GoodsId' => $v1['GoodsId']])->update([
                        '图片路径' => $v2['Img']
                    ]);
                    break;
                }
            }
        }
        
    }

    // 创建图片
    public function createImg() {
        $sql = "select * from cwl_cgzdt_config";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            foreach ($select as $key => $val) {
                // $path = "/data/web/cwl/img/cgzdt_{$val['值']}.jpg";

                $path = "/data/web/easyadmin2/easyadmin/public/img/".date('Ymd').'/'. "cgzdt_{$val['值']}.jpg";

                // echo "wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?{$val['列']}={$val['值']} {$path}";
                // echo '<br>';
                // wkhtmltoimage --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?中类=羽绒服 /data/web/cwl/cgzdt_test1.jpg

                $res = system("wkhtmltoimage  --encoding utf-8 http://im.babiboy.com/admin/system.Caigou/zdt1?{$val['列']}={$val['值']} {$path}", $result);
                print $result;//输出命令的结果状态码
                print $res;//输出命令输出的最后一行
            }
        }
    }

}
