<?php
namespace app\api\controller\lufei\updatatable;

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
 * sp_ww_shop_stock_sales更新
 */
class SpWwShopStockSales extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_binew = '';
    protected $db_sqlsrv = '';

    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_binew = Db::connect('bi_new');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }

    // 数据更新
    public function updateDb(){
        $sql = "
            WITH T1 AS
            (
            SELECT 
                T.CustomerName,
                T.CustomerId,
                T.GoodsId,
                SUM(CASE WHEN EBGS.ViewOrder=1 THEN  T.expect_quantity ELSE 0 END) AS  [库存_00/28/37/44/100/160/S],
                SUM(CASE WHEN EBGS.ViewOrder=2 THEN  T.expect_quantity ELSE 0 END) AS  [库存_29/38/46/105/165/M],
                SUM(CASE WHEN EBGS.ViewOrder=3 THEN  T.expect_quantity ELSE 0 END) AS  [库存_30/39/48/110/170/L],
                SUM(CASE WHEN EBGS.ViewOrder=4 THEN  T.expect_quantity ELSE 0 END) AS  [库存_31/40/50/115/175/XL],
                SUM(CASE WHEN EBGS.ViewOrder=5 THEN  T.expect_quantity ELSE 0 END) AS  [库存_32/41/52/120/180/2XL],
                SUM(CASE WHEN EBGS.ViewOrder=6 THEN  T.expect_quantity ELSE 0 END) AS  [库存_33/42/54/125/185/3XL],
                SUM(CASE WHEN EBGS.ViewOrder=7 THEN  T.expect_quantity ELSE 0 END) AS  [库存_34/43/56/190/4XL],
                SUM(CASE WHEN EBGS.ViewOrder=8 THEN  T.expect_quantity ELSE 0 END) AS  [库存_35/44/58/195/5XL],
                SUM(CASE WHEN EBGS.ViewOrder=9 THEN  T.expect_quantity ELSE 0 END) AS  [库存_36/6XL],
                SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.expect_quantity ELSE 0 END) AS  [库存_38/7XL],
                SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.expect_quantity ELSE 0 END) AS  [库存_40],
                SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.expect_quantity ELSE 0 END) AS  [库存_42],
                SUM(T.expect_quantity) 店铺库存在途,
                SUM(T.not_sent_quantity) 已配未发,
                MIN(T.MIN_DATE) 上市天数
            FROM 
            (
            -- 店铺库存
            SELECT
                EC.CustomerId ,
                EC.CustomerName ,
                EG.GoodsId ,
                ECSD.SizeId,
                SUM(ECSD.Quantity) actual_quantity,
                0 AS intransit_quantity,
                0 not_sent_quantity,
                SUM(ECSD.Quantity) expect_quantity,
                DATEDIFF(day, MIN(ECS.StockDate), GETDATE()) MIN_DATE
            FROM ErpCustomerStock ECS 
            LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            WHERE EC.ShutOut=0
                AND EC.MathodId IN (4,7)
                -- AND EC.CustomItem15='广州云仓'
                AND EG.TimeCategoryName1=2023
                AND EG.TimeCategoryName2 IN ('秋季','初秋','深秋','冬季','初冬','深冬')
                AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
            -- 	AND EC.CustomerId='C991000717' AND EG.GoodsId='25763'
            GROUP BY 
                EC.CustomerId,
                EC.CustomerName,
                EG.GoodsId,
                ECSD.SizeId
            -- HAVING SUM(ECS.Quantity)!=0
            
            UNION ALL 
            -- 仓库发货在途
            SELECT 
                EC.CustomerId,
                EC.CustomerName AS dept_name,
                EDG.GoodsId,
                EDGD.SizeId,
                0 AS actual_quantity,
                SUM(EDGD.Quantity) AS intransit_quantity,
                0 not_sent_quantity,
                SUM(EDGD.Quantity) expect_quantity,
                NULL MIN_DATE
            FROM ErpDelivery ED 
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
            WHERE ED.CodingCode='EndNode2'
                AND ED.IsCompleted=0
                AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.DeliveryId IS NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
                AND EC.ShutOut=0
                AND EC.MathodId IN (4,7)
                -- AND EC.CustomItem15='广州云仓'
                AND EG.TimeCategoryName1=2023
                AND EG.TimeCategoryName2 IN ('秋季','初秋','深秋','冬季','初冬','深冬')
                AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
            GROUP BY
                EC.CustomerId,
                EC.CustomerName,
                EDG.GoodsId,
                EDGD.SizeId
                
            UNION ALL
            --店店调拨在途
            SELECT
                EC.CustomerId,
                EC.CustomerName AS dept_name,
                EIG.GoodsId,
                EIGD.SizeId,
                0 AS actual_quantity,
                SUM(EIGD.Quantity) AS intransit_quantity,
                0 not_sent_quantity,
                SUM(EIGD.Quantity) expect_quantity,
                NULL MIN_DATE
            FROM ErpCustOutbound EI 
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            LEFT JOIN ErpCustOutboundGoodsDetail EIGD ON EIG.CustOutboundGoodsId=EIGD.CustOutboundGoodsId
            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
            WHERE EI.CodingCodeText='已审结'
                AND EI.IsCompleted=0
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                AND EC.ShutOut=0
                AND EC.MathodId IN (4,7)
                -- AND EC.CustomItem15='广州云仓'
                AND EG.TimeCategoryName1=2023
                AND EG.TimeCategoryName2 IN ('秋季','初秋','深秋','冬季','初冬','深冬')
                AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
            GROUP BY 
                EC.CustomerId,
                EC.CustomerName,
                EIG.GoodsId,
                EIGD.SizeId
                
            UNION ALL 
            --出货指令单已配未发
            SELECT
                EC.CustomerId,
                EC.CustomerName AS dept_name,
                ESG.GoodsId,
                ESGD.SizeId,
                0 actual_quantity,
                0 intransit_quantity,
                SUM(ESGD.Quantity) not_sent_quantity,
                0 expect_quantity,
                NULL MIN_DATE
            FROM ErpSorting ES
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID= ESG.SortingID
            LEFT JOIN ErpSortingGoodsDetail ESGD ON ESG.SortingGoodsID=ESGD.SortingGoodsID
            LEFT JOIN ErpCustomer EC ON ES.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            WHERE	 
                ES.IsCompleted= 0 
                AND EC.ShutOut=0
                AND EC.MathodId IN (4,7)
                -- AND EC.CustomItem15='广州云仓'
                AND EG.TimeCategoryName1=2023
                AND EG.TimeCategoryName2 IN ('秋季','初秋','深秋','冬季','初冬','深冬')
                AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
            GROUP BY
                EC.CustomerId
                ,EC.CustomerName
                ,ESG.GoodsId
                ,ESGD.SizeId
            ) T
            LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId
            GROUP BY 
                T.CustomerName,
                T.CustomerId,
                T.GoodsId
            )
            ,
            
            T2 AS 
            (
            SELECT 
                EC.CustomerId
                ,EG.GoodsId
                ,SUM(ERG.Quantity) 累销
                ,SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-3,23) AND CONVERT(VARCHAR(10),GETDATE()-1,23) THEN ERG.Quantity END) 三天销
                ,SUM(CASE WHEN CONVERT(VARCHAR(10),ER.RetailDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-7,23) AND CONVERT(VARCHAR(10),GETDATE()-1,23) THEN ERG.Quantity END) 周销
            FROM ErpRetail ER 
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            LEFT JOIN ErpCustomer EC ON ER.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            WHERE ER.CodingCodeText='已审结'
                AND EC.ShutOut=0
                AND EC.MathodId IN (4,7)
                -- AND EC.CustomItem15='广州云仓'
                AND EG.TimeCategoryName1=2023
                AND EG.TimeCategoryName2 IN ('秋季','初秋','深秋','冬季','初冬','深冬')
                AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                AND ER.RetailDate>='2023-07-01 00:00:00'
                AND CONVERT(VARCHAR(10),ER.RetailDate,23)<CONVERT(VARCHAR(10),GETDATE(),23)
            GROUP BY
                EC.CustomerId
                ,EG.GoodsId
            )
            
            SELECT 
                EC.State 省份,
                EC.CustomItem15 云仓,
                EC.CustomItem17 商品负责人,
                CASE WHEN EC.MathodId=4 THEN '直营' WHEN EC.MathodId=7 THEN '加盟' END 经营模式,
                EC.CustomerName 店铺名称,
                CASE WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季' 
                        WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季' 
                    END 季节归集,
                EG.CategoryName1 一级分类,
                EG.CategoryName2 二级分类,
                EG.StyleCategoryName 风格,
                EG.GoodsNo 货号,
                T1.[上市天数],
                T1.[库存_00/28/37/44/100/160/S],
                T1.[库存_29/38/46/105/165/M],
                T1.[库存_30/39/48/110/170/L],
                T1.[库存_31/40/50/115/175/XL],
                T1.[库存_32/41/52/120/180/2XL],
                T1.[库存_33/42/54/125/185/3XL],
                T1.[库存_34/43/56/190/4XL],
                T1.[库存_35/44/58/195/5XL],
                T1.[库存_36/6XL],
                T1.[库存_38/7XL],
                T1.[库存_40],
                T1.[库存_42],
                T1.[店铺库存在途],
                T1.[已配未发],
                T2.[三天销],
                T2.[周销],
                DENSE_RANK() OVER (PARTITION BY T1.GoodsId ORDER BY T2.[周销] DESC) 周销排名 ,
                T2.[累销]
            FROM T1 
            LEFT JOIN T2 ON T1.CustomerId=T2.CustomerId AND T1.GoodsId=T2.GoodsId
            LEFT JOIN ErpCustomer EC ON T1.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON T1.GoodsId=EG.GoodsId
            -- WHERE EC.CustomerName='德化一店'
            -- 	AND EG.GoodsNo='B52105001'
            ;    
        ";

        $select = $this->db_sqlsrv->query($sql);
        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_bi->execute('TRUNCATE sp_ww_shop_stock_sales;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_bi->table('sp_ww_shop_stock_sales')->strict(false)->insertAll($val);
            }

            cache('sp_customer_stock_skc_2', null);
            return true;
        } else {
            return false;
        }
    }
}
