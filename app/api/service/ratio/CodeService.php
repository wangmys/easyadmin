<?php


namespace app\api\service\ratio;

use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use app\admin\model\code\SizeRanking;
use app\admin\model\code\Size7DaySale;
use app\admin\model\code\SizeAccumulatedSale;
use app\admin\model\code\SizeShopEstimatedStock;
use app\admin\model\code\SizeWarehouseAvailableStock;
use app\admin\model\code\SizeWarehouseTransitStock;
use app\admin\model\code\SizePurchaseStock;
use think\App;
use think\cache\driver\Redis;
use think\facade\Db;

/**
 * 尺码比数据服务
 * Class RatioService
 * @package app\api\service\bi\ratio
 */
class CodeService
{

    /**
     * 默认配置
     * @var array
     */
    protected $config = [

    ];

    protected $code = 0;
    protected $msg = '';

    /**
     * 初始化
     * CodeService constructor.
     */
    public function __construct()
    {
        // 设置内存
        ini_set('memory_limit', '1024M');
        // 码比-排名表
        $this->sizeModel = new SizeRanking;
        // 码比-周销表
        $this->size7DayModel = new Size7DaySale;
        // 码比-累销表
        $this->sizeAccumulatedModel = new SizeAccumulatedSale;
        // 码比-店铺预计库存
        $this->sizeShopEstimatedModel = new SizeShopEstimatedStock;
        // 码比-云仓可用库存
        $this->sizeWarehouseAvailableModel = new SizeWarehouseAvailableStock;
        // 码比-云仓在途库存
        $this->sizeWarehouseTransitModel = new SizeWarehouseTransitStock;
        // 码比-仓库采购表
        $this->sizePurchaseStockModel = new SizePurchaseStock;
        // 实例化redis客户端
        $this->redis = new Redis(['password' => 'sg2023-07']);
    }

    /**
     * 拉取排名数据
     */
    public function pullData()
    {
       $sql = "SELECT 
            EG.GoodsNo as 货号,
            MAX(eg.StyleCategoryName) AS 风格,
                MAX(eg.CategoryName1) AS 一级分类,
                MAX(eg.CategoryName2) AS 二级分类,
                MAX(eg.CategoryName) AS 分类,
                MAX(LEFT(eg.CategoryName, 2))	as 领型,
								MAX(eg.StyleCategoryName2) as 货品等级,
            COUNT(DISTINCT ECS.CustomerId) as 上柜家数,
            DATEDIFF(day, MIN(ECS.StockDate), GETDATE()) as 上市天数,
            -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-7,23)  AND CONVERT(VARCHAR(10),GETDATE()-1,23) THEN ECS.Quantity ELSE 0 END ) as 销量,
            
            CASE WHEN DATEDIFF(day, MIN(ECS.StockDate), GETDATE())<1 
              THEN 0
                WHEN DATEDIFF(day, MIN(ECS.StockDate), GETDATE())<7 
              THEN -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-7,23)  AND CONVERT(VARCHAR(10),GETDATE()-1,23) THEN ECS.Quantity ELSE 0 END ) / DATEDIFF(day, MIN(ECS.StockDate), GETDATE()) / COUNT(DISTINCT ECS.CustomerId)
               WHEN DATEDIFF(day, MIN(ECS.StockDate), GETDATE())>=7 
              THEN -SUM(CASE WHEN ECS.BillType='ErpRetail' AND CONVERT(VARCHAR(10),ECS.StockDate,23) BETWEEN CONVERT(VARCHAR(10),GETDATE()-7,23)  AND CONVERT(VARCHAR(10),GETDATE()-1,23) THEN ECS.Quantity ELSE 0 END ) / 7 / COUNT(DISTINCT ECS.CustomerId)
              ELSE 0 
            END  AS 日均销,
						MAX(EGI.Img) AS 图片
            FROM ErpCustomerStock ECS
            LEFT JOIN ErpGoods EG ON ECS.GoodsId = EG.GoodsId
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
						LEFT JOIN ErpGoodsImg EGI ON EG.GoodsId=EGI.GoodsId
            WHERE 
            EC.ShutOut=0 
            AND EC.MathodId IN (7,4)
            AND eg.TimeCategoryName1 = '2023'
            AND eg.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
            and eg.CategoryName1 IN ('下装','外套','内搭','鞋履')
-- 						and eg.GoodsNo = 'B42101021'
            GROUP BY 
             EG.GoodsNo order by 日均销 desc";
        $order_num = 1;
        Db::startTrans();
        $list = Db::connect("sqlsrv")->query($sql);
        try {
            foreach ($list as $k => &$v){
                $v['排名'] = $order_num++;
                $v['Date'] = date('Y-m-d');
                $v['create_time'] = time();
            }
            $res = $this->sizeModel->saveAll($list);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->msg = $e->getMessage();
            return ApiConstant::ERROR_CODE;
        }
        return ApiConstant::SUCCESS_CODE;
    }

    /**
     * 拉取7天周销数据到缓存
     * @return int
     */
    public function pull7DaySale()
    {
        $sql = " SELECT
            CAST(GETDATE() AS DATE) as Date,
            CAST(er.RetailDate AS DATE) AS 单据时间,
            ec.State AS 省份,
            ec.CustomItem15 AS 云仓,
            ec.CustomerName AS 店铺名称,
            eg.TimeCategoryName1 AS 一级时间分类,
            eg.TimeCategoryName2 AS 二级时间分类,
            eg.CategoryName AS 分类,
            eg.CategoryName1 AS 一级分类,
            eg.CategoryName2 AS 二级分类,
            eg.GoodsName AS 货品名称,
            eg.StyleCategoryName AS 风格,
            eg.GoodsNo AS 货号,
            MAX(egpt.UnitPrice) AS 零售价,
            EBC.Mathod AS 经营模式,
            SUM(CASE WHEN rbgs.ViewOrder=1 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_00/28/37/44/100/160/S],
            SUM(CASE WHEN rbgs.ViewOrder=2 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_29/38/46/105/165/M],
            SUM(CASE WHEN rbgs.ViewOrder=3 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_30/39/48/110/170/L],
            SUM(CASE WHEN rbgs.ViewOrder=4 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_31/40/50/115/175/XL],
            SUM(CASE WHEN rbgs.ViewOrder=5 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_32/41/52/120/180/2XL],
            SUM(CASE WHEN rbgs.ViewOrder=6 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_33/42/54/125/185/3XL],
            SUM(CASE WHEN rbgs.ViewOrder=7 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_34/43/56/190/4XL],
            SUM(CASE WHEN rbgs.ViewOrder=8 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_35/44/58/195/5XL],
            SUM(CASE WHEN rbgs.ViewOrder=9 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_36/6XL],
            SUM(CASE WHEN rbgs.ViewOrder=10 THEN ergd.Quantity ELSE NULL END ) AS  [库存_38/7XL],
            SUM(CASE WHEN rbgs.ViewOrder=11 THEN ergd.Quantity ELSE NULL END ) AS  [库存_40/8XL],
            SUM(ergd.Quantity) AS Quantity,
            SUM(ergd.Quantity * erg.UnitPrice) AS 零售额,
            SUM(ergd.Quantity * erg.RetailPrice) AS 金额,
            CASE WHEN CAST(er.RetailDate AS DATE) < DATEADD(DAY, -4, CAST(GETDATE() AS DATE))  THEN '否' ELSE '是' END as 近三天识别,
            COUNT(eg.GoodsNo) AS 货号数,
            LEFT(eg.CategoryName, 2)	as 领型
        FROM 
        
        ErpRetail er 
        LEFT JOIN ErpRetailGoods erg ON er.RetailID = erg.RetailID
        LEFT JOIN ErpRetailGoodsDetail ergd ON erg.RetailGoodsID = ergd.RetailGoodsID
        LEFT JOIN ErpBaseGoodsSize rbgs ON ergd.SizeId = rbgs.SizeId
        LEFT JOIN ErpGoods eg ON erg.GoodsId = eg.GoodsId
        LEFT JOIN ErpGoodsPriceType egpt ON erg.GoodsId = egpt.GoodsId AND egpt.PriceId = 1
        LEFT JOIN ErpCustomer ec ON er.CustomerId = ec.CustomerId
        LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
        
        WHERE

        er.CodingCodeText = '已审结' AND
        er.RetailDate >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE)) AND er.RetailDate <= CAST(GETDATE() AS DATE) AND
        -- er.RetailDate >= '2023-05-02' AND er.RetailDate < '2023-05-10' and
        eg.TimeCategoryName1 = '2023'
        AND EBC.Mathod IN ('直营', '加盟')
        AND EG.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
        and eg.CategoryName1 IN ('下装','外套','内搭','鞋履')
        GROUP BY
        CAST(er.RetailDate AS DATE),
        ec.State,
        ec.CustomItem15,
        eg.CategoryName1,
        eg.CategoryName2,
        eg.CategoryName,
        eg.GoodsName,
        eg.TimeCategoryName1,
        eg.TimeCategoryName2,
        eg.StyleCategoryName,
        ec.CustomerName,
        EBC.Mathod,
        ec.CustomerName,
        eg.GoodsNo
        ";
        // 查询数据并保存缓存
        $list = Db::connect("sqlsrv")->query($sql);
        // 将结果集按3000条数据切割
        $res = array_chunk($list,3000);
        $redisKey = ApiConstant::RATIO_PULL_REDIS_KEY[0];
        // 循环存入缓存
        foreach ($res as $k => $v){
            // 循环存入缓存
            $key = $redisKey.'_'.$k;
            // 将数据的key存入队列
            $this->redis->rpush($redisKey,$key);
            // 存入缓存
            $this->redis->set($key,json_encode($v));
        }
        return ApiConstant::SUCCESS_CODE;
    }

     /**
     * 拉取累销数据保存到缓存
     * @return int
     */
    public function pullAccumulatedSale()
    {
        // 查询累销数据
        $sql = " SELECT
                CAST(GETDATE() AS DATE) as Date,
                ec.State AS 省份,
                ec.CustomItem15 AS 云仓,
                ec.CustomerName AS 店铺名称,
                eg.TimeCategoryName1 AS 一级时间分类,
                eg.TimeCategoryName2 AS 二级时间分类,
                eg.CategoryName AS 分类,
                eg.CategoryName1 AS 一级分类,
                eg.CategoryName2 AS 二级分类,
                eg.GoodsName AS 货品名称,
                eg.StyleCategoryName AS 风格,
                eg.GoodsNo AS 货号,
                MAX(egpt.UnitPrice) AS 零售价,
                EBC.Mathod AS 经营模式,
                SUM(CASE WHEN rbgs.ViewOrder=1 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_00/28/37/44/100/160/S],
                SUM(CASE WHEN rbgs.ViewOrder=2 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_29/38/46/105/165/M],
                SUM(CASE WHEN rbgs.ViewOrder=3 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_30/39/48/110/170/L],
                SUM(CASE WHEN rbgs.ViewOrder=4 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_31/40/50/115/175/XL],
                SUM(CASE WHEN rbgs.ViewOrder=5 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_32/41/52/120/180/2XL],
                SUM(CASE WHEN rbgs.ViewOrder=6 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_33/42/54/125/185/3XL],
                SUM(CASE WHEN rbgs.ViewOrder=7 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_34/43/56/190/4XL],
                SUM(CASE WHEN rbgs.ViewOrder=8 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_35/44/58/195/5XL],
                SUM(CASE WHEN rbgs.ViewOrder=9 	THEN ergd.Quantity ELSE NULL END ) AS  [库存_36/6XL],
                SUM(CASE WHEN rbgs.ViewOrder=10 THEN ergd.Quantity ELSE NULL END ) AS  [库存_38/7XL],
                SUM(CASE WHEN rbgs.ViewOrder=11 THEN ergd.Quantity ELSE NULL END ) AS  [库存_40/8XL],
                SUM(ergd.Quantity) AS Quantity,
                SUM(ergd.Quantity * erg.UnitPrice) AS 零售额,
                SUM(ergd.Quantity * erg.RetailPrice) AS 金额,
                LEFT(eg.CategoryName, 2)	as 领型
            FROM 
            
            ErpRetail er 
            LEFT JOIN ErpRetailGoods erg ON er.RetailID = erg.RetailID
            LEFT JOIN ErpRetailGoodsDetail ergd ON erg.RetailGoodsID = ergd.RetailGoodsID
            LEFT JOIN ErpBaseGoodsSize rbgs ON ergd.SizeId = rbgs.SizeId
            LEFT JOIN ErpGoods eg ON erg.GoodsId = eg.GoodsId
            LEFT JOIN ErpGoodsPriceType egpt ON erg.GoodsId = egpt.GoodsId AND egpt.PriceId = 1
            LEFT JOIN ErpCustomer ec ON er.CustomerId = ec.CustomerId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            
            WHERE
            
            er.CodingCodeText = '已审结' AND
            er.RetailDate >= '2022-10-01' AND er.RetailDate <= CAST(GETDATE() AS DATE) AND
            eg.TimeCategoryName1 = '2023'
            AND EBC.Mathod IN ('直营', '加盟')
            AND EG.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
            and eg.CategoryName1 IN ('下装','外套','内搭','鞋履')
            GROUP BY
            ec.State,
            ec.CustomItem15,
            eg.CategoryName1,
            eg.CategoryName2,
            eg.CategoryName,
            eg.GoodsName,
            eg.TimeCategoryName1,
            eg.TimeCategoryName2,
            eg.StyleCategoryName,
            ec.CustomerName,
            EBC.Mathod,
            eg.GoodsNo

        ";
        // 查询数据并保存缓存
        $list = Db::connect("sqlsrv")->query($sql);
        // 将结果集按3000条数据切割
        $res = array_chunk($list,3000);
        $redisKey = ApiConstant::RATIO_PULL_REDIS_KEY[1];

        // 循环存入缓存
        foreach ($res as $k => $v){
            // 循环存入缓存
            $key = $redisKey.'_'.$k;
            // 将数据的key存入队列
            $this->redis->rpush($redisKey,$key);
            // 存入缓存
            $this->redis->set($key,json_encode($v));
        }
        return ApiConstant::SUCCESS_CODE;
    }

    /**
     * 拉取店铺预计库存数据保存到缓存
     * @return int
     */
    public function pullShopEstimatedStock()
    {
        // 设置内存
        ini_set('memory_limit', '1024M');
        // 查询累销数据
        $sql = "SELECT 
	        CAST(GETDATE() AS DATE) as Date,
            T.CustomItem15,
            T.State,
            T.CustomItem17,
            T.CustomerId,
            T.CustomerName,
            T.MathodId,
            MAX(EG.TimeCategoryName1) as TimeCategoryName1,
            MAX(EG.TimeCategoryName2) as TimeCategoryName2,
            MAX(EG.StyleCategoryName1) as StyleCategoryName1,
            MAX(EG.StyleCategoryName2) as StyleCategoryName2,
            MAX(EG.CategoryName1) as CategoryName1,
            MAX(EG.CategoryName2) as CategoryName2,
            MAX(EG.CategoryName) as CategoryName,
            MAX(EG.StyleCategoryName) as StyleCategoryName,
            T.GoodsNo as GoodsNo,
            MAX(EG.GoodsName) as GoodsName,
            SUM(CASE WHEN EBGS.ViewOrder=1 	THEN T.预计库存 ELSE NULL END ) AS  [库存_00/28/37/44/100/160/S],
            SUM(CASE WHEN EBGS.ViewOrder=2 	THEN T.预计库存 ELSE NULL END ) AS  [库存_29/38/46/105/165/M],
            SUM(CASE WHEN EBGS.ViewOrder=3 	THEN T.预计库存 ELSE NULL END ) AS  [库存_30/39/48/110/170/L],
            SUM(CASE WHEN EBGS.ViewOrder=4 	THEN T.预计库存 ELSE NULL END ) AS  [库存_31/40/50/115/175/XL],
            SUM(CASE WHEN EBGS.ViewOrder=5 	THEN T.预计库存 ELSE NULL END ) AS  [库存_32/41/52/120/180/2XL],
            SUM(CASE WHEN EBGS.ViewOrder=6 	THEN T.预计库存 ELSE NULL END ) AS  [库存_33/42/54/125/185/3XL],
            SUM(CASE WHEN EBGS.ViewOrder=7 	THEN T.预计库存 ELSE NULL END ) AS  [库存_34/43/56/190/4XL],
            SUM(CASE WHEN EBGS.ViewOrder=8 	THEN T.预计库存 ELSE NULL END ) AS  [库存_35/44/58/195/5XL],
            SUM(CASE WHEN EBGS.ViewOrder=9 	THEN T.预计库存 ELSE NULL END ) AS  [库存_36/6XL],
            SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.预计库存 ELSE NULL END ) AS  [库存_38/7XL],
            SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.预计库存 ELSE NULL END ) AS  [库存_40/8XL],
            SUM(T.[预计库存]) as Quantity,
            MAX(LEFT(EG.CategoryName, 2))	as Collar
        FROM 
        (
        -- 店铺库存
        SELECT 
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            ECSD.SizeId,
            SUM(ECSD.Quantity) 店铺库存,
            NULL 在途库存,
            NULL 已配未发数量,
            NULL 在途量合计,
            SUM(ECSD.Quantity) 预计库存
        FROM ErpCustomer EC 
        LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
        LEFT JOIN ErpCustomerStockDetail ECSD ON ECS.StockId = ECSD.StockId 
        LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
        WHERE EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
        AND eg.TimeCategoryName1 = '2023'
        AND EG.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
        AND EC.ShutOut=0 
        AND EC.MathodId IN (4,7) -- AND EG.GoodsNo = 'B32101321' AND EC.CustomerName = '阆中一店'
        GROUP BY
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            ECSD.SizeId
            
            UNION ALL 
            
            SELECT 
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            EDGD.SizeId,
            NULL AS 店铺库存,
            SUM(EDGD.Quantity) AS 在途库存,
            NULL 已配未发数量,
            SUM(EDGD.Quantity) 在途量合计,
            SUM(EDGD.Quantity) 预计库存
        FROM ErpDelivery ED 
        LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
        LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
        LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
        LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
        WHERE ED.CodingCode='EndNode2'
            AND ED.IsCompleted=0
            --AND ED.IsReceipt IS NULL
            AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.DeliveryId IS NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
        AND	EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
        AND eg.TimeCategoryName1 = '2023'
        AND EG.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
        AND EC.ShutOut=0 
        AND EC.MathodId IN (4,7)
        GROUP BY
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            EDGD.SizeId
            
            UNION ALL
        --店店调拨在途
        SELECT
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            ECOG.SizeId,
            NULL AS 店铺库存,
            SUM(ECOG.Quantity) AS 在途库存,
            NULL 已配未发数量,
            SUM(ECOG.Quantity) 在途量合计,
            SUM(ECOG.Quantity) 预计库存
        FROM ErpCustOutbound EI 
        LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
        LEFT JOIN ErpCustOutboundGoodsDetail ECOG ON EIG.CustOutboundGoodsId = ECOG.CustOutboundGoodsId
        LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
        LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
        WHERE EI.CodingCodeText='已审结'
            AND EI.IsCompleted=0
            AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
            AND	EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
            AND eg.TimeCategoryName1 = '2023'
            AND EG.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
            AND EC.ShutOut=0 
            AND EC.MathodId IN (4,7)   -- AND EG.GoodsNo = 'B32503014' AND EC.CustomerName = '鹰潭一店'
        GROUP BY 
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            ECOG.SizeId
            
            UNION ALL 
        --店铺已配未发
        SELECT 
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            ESGD.SizeId,
            NULL AS 店铺库存,
            NULL AS 在途库存,
            SUM(ESGD.Quantity) 已配未发数量,
            SUM(ESGD.Quantity) 在途量合计,
            SUM(ESGD.Quantity) 预计库存
        FROM ErpCustomer EC
        LEFT JOIN ErpSorting ES ON EC.CustomerId=ES.CustomerId
        LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
        LEFT JOIN ErpSortingGoodsDetail ESGD ON ESG.SortingGoodsID = ESGD.SortingGoodsID
        LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
        WHERE	EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
            AND eg.TimeCategoryName1 = '2023'
            AND EG.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
            AND EC.ShutOut=0 
            AND EC.MathodId IN (4,7)
            AND ES.IsCompleted=0
        GROUP BY 
            EC.CustomItem15,
            EC.State,
            EC.CustomItem17,
            EC.CustomerId,
            EC.CustomerName,
            EC.MathodId,
            EC.CustomerGrade,
            EG.GoodsNo,
            ESGD.SizeId
            ) T 
        LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId
        LEFT JOIN ErpGoods EG ON T.GoodsNo=EG.GoodsNo
        
         -- WHERE T.CustomerName = '兰州二店'	and T.GoodsNo = 'B32101294'
        GROUP BY
            T.CustomItem15,
            T.State,
            T.CustomItem17,
            T.CustomerId,
            T.CustomerName,
            T.MathodId,
            T.CustomerGrade,
            T.GoodsNo
        ";
        // 查询数据并保存缓存
        $list = Db::connect("sqlsrv")->query($sql);
        // 将结果集按3000条数据切割
        $res = array_chunk($list,3000);
        $redisKey = ApiConstant::RATIO_PULL_REDIS_KEY[2];
        // 循环存入缓存
        foreach ($res as $k => $v){
            // 循环存入缓存
            $key = $redisKey.'_'.$k;
            // 将数据的key存入队列
            $this->redis->rpush($redisKey,$key);
            // 存入缓存
            $this->redis->set($key,json_encode($v));
        }
        return ApiConstant::SUCCESS_CODE;
    }

    /**
     * 拉取云仓可用库存保存到缓存
     * @return int
     */
    public function pullWarehouseAvailableStock()
    {
        // 查询累销数据
        $sql = "--仓库可用库存数量到尺码齐码率
            SELECT 
                CAST(GETDATE() AS DATE) as Date,
                T.WarehouseName,
                EG.GoodsNo,
                EG.TimeCategoryName1,
                EG.TimeCategoryName2,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.GoodsName,
                EG.StyleCategoryName,
                EG.StyleCategoryName1,
                EG.StyleCategoryName2,
                SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END) AS [库存_00/28/37/44/100/160/S],
                SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0 END) AS [库存_29/38/46/105/165/M],
                SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0 END) AS [库存_30/39/48/110/170/L],
                SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0 END) AS [库存_31/40/50/115/175/XL],
                SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0 END) AS [库存_32/41/52/120/180/2XL],
                SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0 END) AS [库存_33/42/54/125/185/3XL],
                SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0 END) AS [库存_34/43/56/190/4XL],
                SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0 END) AS [库存_35/44/58/195/5XL],
                SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0 END) AS [库存_36/6XL],
                SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END) AS [库存_38/7XL],
                SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END) AS [库存_40/8XL],
                SUM(T.Quantity) AS Quantity,
                CASE WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11111111111%' THEN 11 
                        WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1111111111%' THEN 10 
                        WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%111111111%' THEN 9
                        WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11111111%' THEN 8
                        WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1111111%' THEN 7
                     WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%111111%' THEN 6
                        WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11111%' THEN 5
                         WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1111%' THEN 4
                         WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%111%' THEN 3
                         WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%11%' THEN 2
                         WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                             CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ) LIKE '%1%' THEN 1
                         ELSE 0
                    END AS 齐码情况,
                    MAX(LEFT(EG.CategoryName, 2))	as Collar
            FROM 
            (
            SELECT 
                EW.WarehouseName,
                EWS.GoodsId,
                EWSD.SizeId,
                SUM(EWSD.Quantity) AS Quantity
            FROM ErpWarehouseStock EWS
            LEFT JOIN ErpWarehouseStockDetail EWSD ON EWS.StockId=EWSD.StockId
            LEFT JOIN ErpWarehouse EW ON EWS.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EWS.GoodsId=EG.GoodsId
            WHERE EG.TimeCategoryName1>2022
                AND EG.CategoryName1 NOT IN ('物料','人事物料')
                AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY  
                EW.WarehouseName,
                EWS.GoodsId,
                EWSD.SizeId
            
            UNION ALL 
            --出货指令单占用库存
            SELECT
                EW.WarehouseName,
                ESG.GoodsId,
                ESGD.SizeId,
                -SUM ( ESGD.Quantity ) AS SumQuantity
            FROM ErpSorting ES
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID= ESG.SortingID
            LEFT JOIN ErpSortingGoodsDetail ESGD ON ESG.SortingGoodsID=ESGD.SortingGoodsID
            LEFT JOIN ErpWarehouse EW ON ES.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            WHERE ES.IsCompleted= 0
                AND EG.TimeCategoryName1>2022
                AND EG.CategoryName1 NOT IN ('物料','人事物料')
                AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                EW.WarehouseName,
                ESG.GoodsId,
                ESGD.SizeId
            
            UNION ALL
                --仓库出货单占用库存
            SELECT
                EW.WarehouseName,
                EDG.GoodsId,
                EDGD.SizeId,
                -SUM ( EDGD.Quantity ) AS SumQuantity
            FROM ErpDelivery ED
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID= EDG.DeliveryID
            LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
            LEFT JOIN ErpWarehouse EW ON ED.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
            WHERE ED.CodingCode= 'StartNode1' 
                AND EDG.SortingID IS NULL
                AND EG.TimeCategoryName1>2022
                AND EG.CategoryName1 NOT IN ('物料','人事物料')
                AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                EW.WarehouseName,
                EDG.GoodsId,
                EDGD.SizeId
            
            UNION ALL
                --采购退货指令单占用库存
            SELECT
                EW.WarehouseName,
                EPRNG.GoodsId,
                EPRNGD.SizeId,
                -SUM ( EPRNGD.Quantity ) AS SumQuantity
            FROM ErpPuReturnNotice EPRN
            LEFT JOIN ErpPuReturnNoticeGoods EPRNG ON EPRN.PuReturnNoticeId= EPRNG.PuReturnNoticeId
            LEFT JOIN ErpPuReturnNoticeGoodsDetail EPRNGD ON EPRNG.PuReturnNoticeGoodsId=EPRNGD.PuReturnNoticeGoodsId
            LEFT JOIN ErpWarehouse EW ON EPRN.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EPRNG.GoodsId=EG.GoodsId
            WHERE (EPRN.IsCompleted = 0 OR EPRN.IsCompleted IS NULL)
                AND EG.TimeCategoryName1>2022
                AND EG.CategoryName1 NOT IN ('物料','人事物料')
                AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                EW.WarehouseName,
                EPRNG.GoodsId,
                EPRNGD.SizeId
            
            UNION ALL
                --采购退货单占用库存
            SELECT
                EW.WarehouseName,
                EPCRG.GoodsId,
                EPCRGD.SizeId,
                -SUM ( EPCRGD.Quantity ) AS SumQuantity
            FROM ErpPurchaseReturn EPCR
            LEFT JOIN ErpPurchaseReturnGoods EPCRG ON EPCR.PurchaseReturnId= EPCRG.PurchaseReturnId
            LEFT JOIN ErpPurchaseReturnGoodsDetail EPCRGD ON EPCRG.PurchaseReturnGoodsId=EPCRGD.PurchaseReturnGoodsId
            LEFT JOIN ErpWarehouse EW ON EPCR.WarehouseId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EPCRG.GoodsId=EG.GoodsId
            WHERE EPCR.CodingCode= 'StartNode1'
                AND EG.TimeCategoryName1>2022
                AND EG.CategoryName1 NOT IN ('物料','人事物料')
                AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                EW.WarehouseName,
                EPCRG.GoodsId,
                EPCRGD.SizeId
            
            UNION ALL
                --仓库调拨占用库存
            SELECT
                EW.WarehouseName,
                EIG.GoodsId,
                EIGD.SizeId,
                -SUM ( EIGD.Quantity ) AS SumQuantity
            FROM ErpInstruction EI
            LEFT JOIN ErpInstructionGoods EIG ON EI.InstructionId= EIG.InstructionId
            LEFT JOIN ErpInstructionGoodsDetail EIGD ON EIG.InstructionGoodsId=EIGD.InstructionGoodsId
            LEFT JOIN ErpWarehouse EW ON EI.OutItemId=EW.WarehouseId
            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
            WHERE EI.Type= 1
                AND (EI.CodingCode= 'StartNode1' OR (EI.CodingCode= 'EndNode2' AND EI.IsCompleted=0 ))
                AND EG.TimeCategoryName1>2022
                AND EG.CategoryName1 NOT IN ('物料','人事物料')
                AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
            GROUP BY
                EW.WarehouseName,
                EIG.GoodsId,
                EIGD.SizeId
            
            ) T
            LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId
            LEFT JOIN ErpGoods EG ON T.GoodsId=EG.GoodsId
            GROUP BY 
                T.WarehouseName,
                EG.GoodsNo,
                EG.TimeCategoryName1,
                EG.TimeCategoryName2,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.GoodsName,
                EG.StyleCategoryName,
                EG.GoodsNo,
                EG.StyleCategoryName1,
                EG.StyleCategoryName2
            ";
        // 查询数据并保存缓存
        $list = Db::connect("sqlsrv")->query($sql);
        // 将结果集按8000条数据切割
        $res = array_chunk($list,2000);
        $redisKey = ApiConstant::RATIO_PULL_REDIS_KEY[3];
        // 循环存入缓存
        foreach ($res as $k => $v){
            // 循环存入缓存
            $key = $redisKey.'_'.$k;
            // 将数据的key存入队列
            $this->redis->rpush($redisKey,$key);
            // 存入缓存
            $this->redis->set($key,json_encode($v));
        }
        return ApiConstant::SUCCESS_CODE;
    }


    /**
     * 拉取云仓在途库存保存到缓存
     * @return int
     */
    public function pullWarehouseTransitStock()
    {
        // 查询累销数据
        $sql = "SELECT
            CAST(GETDATE() AS DATE) as Date,
            ew.WarehouseName,
            eg.TimeCategoryName1,
            eg.TimeCategoryName2,
            eg.CategoryName,
            eg.CategoryName1,
            eg.CategoryName2,
            eg.GoodsName,
            eg.StyleCategoryName,
            eg.GoodsNo,
            SUM(CASE WHEN rbgs.ViewOrder=1 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_00/28/37/44/100/160/S],
            SUM(CASE WHEN rbgs.ViewOrder=2 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_29/38/46/105/165/M],
            SUM(CASE WHEN rbgs.ViewOrder=3 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_30/39/48/110/170/L],
            SUM(CASE WHEN rbgs.ViewOrder=4 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_31/40/50/115/175/XL],
            SUM(CASE WHEN rbgs.ViewOrder=5 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_32/41/52/120/180/2XL],
            SUM(CASE WHEN rbgs.ViewOrder=6 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_33/42/54/125/185/3XL],
            SUM(CASE WHEN rbgs.ViewOrder=7 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_34/43/56/190/4XL],
            SUM(CASE WHEN rbgs.ViewOrder=8 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_35/44/58/195/5XL],
            SUM(CASE WHEN rbgs.ViewOrder=9 	THEN erngd.Quantity ELSE NULL END ) AS  [库存_36/6XL],
            SUM(CASE WHEN rbgs.ViewOrder=10 THEN erngd.Quantity ELSE NULL END ) AS  [库存_38/7XL],
            SUM(CASE WHEN rbgs.ViewOrder=11 THEN erngd.Quantity ELSE NULL END ) AS  [库存_40/8XL],
            SUM(erngd.Quantity) AS Quantity
        FROM 
        
        ErpReceiptNotice ern
        LEFT JOIN ErpReceiptNoticeGoods erng ON ern.ReceiptNoticeId = erng.ReceiptNoticeId
        LEFT JOIN ErpReceiptNoticeGoodsDetail erngd ON erng.ReceiptNoticeGoodsId = erngd.ReceiptNoticeGoodsId
        LEFT JOIN ErpBaseGoodsSize rbgs ON erngd.SizeId = rbgs.SizeId
        LEFT JOIN ErpGoods eg ON erng.GoodsId = eg.GoodsId
        LEFT JOIN ErpWarehouse ew ON ern.WarehouseId = ew.WarehouseId
        WHERE
        (ern.IsCompleted IS NULL OR ern.IsCompleted = 0) AND
        eg.TimeCategoryName1 = '2023'
        AND EG.TimeCategoryName2 IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
        and eg.CategoryName1 IN ('下装','外套','内搭','鞋履')
        and ew.WarehouseCode IN ('CK002','CK003','CK004','CK005','CK006')
        -- and ern.SupplyId != 'K191000638'
        GROUP BY
        ew.WarehouseName,
        eg.CategoryName1,
        eg.CategoryName2,
        eg.CategoryName,
        eg.TimeCategoryName1,
        eg.TimeCategoryName2,
        eg.StyleCategoryName,
        eg.GoodsNo,
        eg.GoodsName
        ";
        // 查询数据并保存缓存
        $list = Db::connect("sqlsrv")->query($sql);
        // 将结果集按2000条数据切割
        $res = array_chunk($list,2000);
        $redisKey = ApiConstant::RATIO_PULL_REDIS_KEY[4];
        // 循环存入缓存
        foreach ($res as $k => $v){
            // 循环存入缓存
            $key = $redisKey.'_'.$k;
            // 将数据的key存入队列
            $this->redis->rpush($redisKey,$key);
            // 存入缓存
            $this->redis->set($key,json_encode($v));
        }
        return ApiConstant::SUCCESS_CODE;
    }

    /**
     * 拉取采购数据保存到缓存
     */
    public function pullPurchaseStock()
    {
        // 查询仓库采购数据
        $sql = "SELECT
        CAST(GETDATE() AS DATE) as Date,
        sp.TimeCategoryName1,
        sp.TimeCategoryName2,
        sp.CategoryName1,
        sp.CategoryName2,
        sp.CategoryName,
        sp.GoodsName,
        sp.GoodsNo,
        sp.StyleCategoryName,
        T.WarehouseCode,
            SUM(CASE WHEN cm.ViewOrder=1 	THEN T.Quantity ELSE NULL END ) AS  [库存_00/28/37/44/100/160/S],
            SUM(CASE WHEN cm.ViewOrder=2 	THEN T.Quantity ELSE NULL END ) AS  [库存_29/38/46/105/165/M],
            SUM(CASE WHEN cm.ViewOrder=3 	THEN T.Quantity ELSE NULL END ) AS  [库存_30/39/48/110/170/L],
            SUM(CASE WHEN cm.ViewOrder=4 	THEN T.Quantity ELSE NULL END ) AS  [库存_31/40/50/115/175/XL],
            SUM(CASE WHEN cm.ViewOrder=5 	THEN T.Quantity ELSE NULL END ) AS  [库存_32/41/52/120/180/2XL],
            SUM(CASE WHEN cm.ViewOrder=6 	THEN T.Quantity ELSE NULL END ) AS  [库存_33/42/54/125/185/3XL],
            SUM(CASE WHEN cm.ViewOrder=7 	THEN T.Quantity ELSE NULL END ) AS  [库存_34/43/56/190/4XL],
            SUM(CASE WHEN cm.ViewOrder=8 	THEN T.Quantity ELSE NULL END ) AS  [库存_35/44/58/195/5XL],
            SUM(CASE WHEN cm.ViewOrder=9 	THEN T.Quantity ELSE NULL END ) AS  [库存_36/6XL],
            SUM(CASE WHEN cm.ViewOrder=10 THEN T.Quantity ELSE NULL END ) AS  [库存_38/7XL],
            SUM(CASE WHEN cm.ViewOrder=11 THEN T.Quantity ELSE NULL END ) AS  [库存_40/8XL],
        LEFT(sp.CategoryName, 2)	as Collar,
        SUM(T.Quantity) as Quantity
        
        FROM 
        (
        
        SELECT
        
        a.ReceiptWareid as WarehouseId,
        b.GoodsId,
        sp.GoodsNo,
        c.SizeId,
        ck.WarehouseCode,
        SUM(c.Quantity) as Quantity
        from
        ErpPurchase a
        left join 
        ErpPurchaseGoods b on a.PurchaseID=b.PurchaseID
        left join
        ErpPurchaseGoodsDetail c on b.PurchaseGoodsID=c.PurchaseGoodsID
        left join
        ErpWarehouse ck on a.ReceiptWareid=ck.WarehouseId
        left join
        ErpGoods sp on b.GoodsId=sp.GoodsId
        where 
        
        ck.WarehouseCode  IN ('C019','C020','C021','C022') and 
        a.CodingCodeText='已审结' and a.PurchaseDate BETWEEN '2022-05-01' and CAST(GETDATE() AS DATE)
        GROUP BY 
        a.ReceiptWareid,
        ck.WarehouseCode,
        b.GoodsId,
        sp.GoodsNo,
        c.SizeId
        
        
        UNION ALL
        
        SELECT
        
        a.WarehouseId,
        b.GoodsId,
        sp.GoodsNo,
        c.SizeId,
        ck.WarehouseCode,
        -SUM(c.Quantity) as Quantity
        from
        ErpPurchaseReturn a
        left join 
        ErpPurchaseReturnGoods b on a.PurchaseReturnId=b.PurchaseReturnId
        left join
        ErpPurchaseReturnGoodsDetail c on b.PurchaseReturnGoodsId=c.PurchaseReturnGoodsId
        left join
        ErpWarehouse ck on a.WarehouseId=ck.WarehouseId
        left join
        ErpGoods sp on b.GoodsId=sp.GoodsId
        where 
        a.SupplyId != 'K191000638' and
        a.CodingCodeText='已审结' and a.PurchaseReturnDate BETWEEN '2022-05-01' and CAST(GETDATE() AS DATE)
        -- and b.GoodsId = 24768
        GROUP BY 
        a.WarehouseId,
        ck.WarehouseCode,
        b.GoodsId,
        sp.GoodsNo,
        c.SizeId
        
        ) T 
        
        left join
        ErpBaseGoodsSize cm on T.SizeId=cm.SizeId
        left join
        ErpGoods sp on T.GoodsId=sp.GoodsId
        where 
        sp.CategoryName1 IN ('下装','外套','内搭','鞋履')
        and sp.TimeCategoryName2  IN ( '秋季', '初秋', '深秋', '冬季', '初冬', '深冬' ) 
        and sp.TimeCategoryName1 = '2023'
        -- and sp.GoodsNo = 'B32305004'
        GROUP BY 
        
        sp.TimeCategoryName1,
        sp.TimeCategoryName2,
        sp.CategoryName1,
        sp.CategoryName2,
        sp.CategoryName,
        sp.GoodsName,
        sp.GoodsNo,
        sp.StyleCategoryName,
        T.WarehouseCode
        order By Collar desc
        ";
        // 查询数据并保存缓存
        $list = Db::connect("sqlsrv")->query($sql);
        // 将结果集按2000条数据切割
        $res = array_chunk($list,2000);
        $redisKey = ApiConstant::RATIO_PULL_REDIS_KEY[5];
        // 循环存入缓存
        foreach ($res as $k => $v){
            // 循环存入缓存
            $key = $redisKey.'_'.$k;
            // 将数据的key存入队列
            $this->redis->rpush($redisKey,$key);
            // 存入缓存
            $this->redis->set($key,json_encode($v));
        }
        return ApiConstant::SUCCESS_CODE;
    }

    /**
     * 从缓存保存数据至数据库
     * @param $data_key  ApiConstant::RATIO_PULL_REDIS_KEY 根据类型不同,同步不同数据源到MYSQL
     * @return int
     */
    public function saveSaleData($data_key)
    {
        if(empty($data_key)) {
            $this->msg = '缺少必要参数';
            return ApiConstant::ERROR_CODE;
        }
        // 循环获取周销数据并同步至数据库
        while(true){
            // 获取储存销售数据的key
            $index_value = $this->redis->lindex($data_key,0);
            if(!empty($index_value)){
                // 获取销售数据
                $sale = $this->redis->get($index_value);
                // json数据格式化
                $sale_data = json_decode($sale,true);
                if(empty($sale_data)){
                    // 销毁缓存数据
                    $this->redis->lpop($data_key);
                    $this->msg = '当前数据为空';
                    return ApiConstant::ERROR_CODE;
                }
                $model = null;
                Db::startTrans();
                try {
                    switch ($data_key){
                        // 同步周销数据
                        case ApiConstant::RATIO_PULL_REDIS_KEY[0]:
                            $model = $this->size7DayModel;
                            break;
                        // 同步累销数据
                        case ApiConstant::RATIO_PULL_REDIS_KEY[1]:
                            $model = $this->sizeAccumulatedModel;
                            break;
                        // 同步店铺预计库存数据
                        case ApiConstant::RATIO_PULL_REDIS_KEY[2]:
                            $model = $this->sizeShopEstimatedModel;
                            break;
                        // 同步云仓可用库存数据
                        case ApiConstant::RATIO_PULL_REDIS_KEY[3]:
                            $model = $this->sizeWarehouseAvailableModel;
                            break;
                        // 同步云仓在途库存数据
                        case ApiConstant::RATIO_PULL_REDIS_KEY[4]:
                            $model = $this->sizeWarehouseTransitModel;
                            break;
                        // 同步仓库采购库存数据
                        case ApiConstant::RATIO_PULL_REDIS_KEY[5]:
                            $model = $this->sizePurchaseStockModel;
                            break;
                    }
                    if($model && $sale_data){
                        // 批量切割
                        $data = array_chunk($sale_data,1500);
                        foreach ($data as $key => $val){
                             // 执行插入操作
                            $model->insertAll($val);
                        }
                        // 插入完毕
                        Db::commit();
                        // 任务完成,从队列里取出任务
                        $this->redis->lpop($data_key);
                        // 销毁缓存数据
                        $this->redis->delete($index_value);
                    }else{
                        // 回滚
                        Db::rollback();
                        $this->msg = '任务类型不存在';
                        return ApiConstant::ERROR_CODE;
                    }
                }catch (\Exception $e){
                    Db::rollback();
                    $this->msg = $e->getMessage();
                    return ApiConstant::ERROR_CODE;
                }
            }else{
                return ApiConstant::SUCCESS_CODE;
            }
        }
    }

    /**
     * 获取错误提示
     */
    public function getError($code = 0)
    {
        return !empty($this->msg)?$this->msg:ApiConstant::ERROR_CODE_LIST[$code];
    }
}