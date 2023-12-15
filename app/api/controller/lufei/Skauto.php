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
 * @ControllerAnnotation(title="售空提醒")
 */
class Skauto extends BaseController
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

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');

        $this->seasion = '夏季';
        $this->seasionStr = $this->seasionHandle($this->seasion);
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

    
    public function skauto() {
        $year = date('Y', time());
        $sql = "
            SELECT 
                sk.云仓,
                sk.商品负责人,
                sk.省份,
                sk.经营模式,
                sk.店铺名称,
                sk.季节, 
                CASE
                    sk.季节
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
                sk.一级分类,
                sk.二级分类,
                sk.分类,
                sk.风格,
                sk.货号,
                st.零售价,
                st.当前零售价,
                round(st.当前零售价 / st.零售价, 2) as 折率,
                bu.上市天数,
                sk.`总入量数量` AS 总入量,
                bu.累销量 as 累销数量,
                date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM `sp_sk` as sk
            LEFT JOIN customer as c ON sk.店铺名称=c.CustomerName
            LEFT JOIN sp_ww_chunxia_stock as st ON sk.省份=st.省份 AND sk.店铺名称=st.店铺名称 AND sk.一级分类=st.一级分类 AND sk.二级分类=st.二级分类 AND sk.分类=st.分类 AND sk.货号 = st.货号
            LEFT JOIN sp_ww_budongxiao_detail as bu ON sk.省份=bu.省份 AND sk.店铺名称=bu.店铺名称 AND sk.一级分类=bu.大类 AND sk.二级分类=bu.中类 AND sk.分类=bu.小类 AND sk.货号 = bu.货号
            WHERE 1
                AND c.Region <> '闭店区'
                AND sk.年份 = {$year}
            GROUP BY 
            sk.店铺名称, 
            sk.货号
        ";

        $select = $this->db_easyA->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_skauto;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_skauto')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_skauto 1 更新成功，数量：{$count}！"
            ]);
        }
    }   

    // 销售天数 = 卖的第一天开始算，到截止那天。例如前天开始卖，昨天不管有没有卖，都算作 2天
    public function skauto_first() {
        $sql = "
            SELECT
                TOP 3000000
                EC.State AS 省份,
                ER.CustomerName AS 店铺名称,
                EG.GoodsNo AS 货号,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                MIN(FORMAT(ER.RetailDate, 'yyyy-MM-dd')) AS 首单日期,
                DATEDIFF(day, MIN(ER.RetailDate), DATEADD(DAY, -1, CAST(GETDATE() AS DATE))) + 1 AS 销售天数
            FROM ErpRetail AS ER 
            LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
            LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
            LEFT JOIN ErpGoods AS EG ON EG.GoodsId = ERG.GoodsId
            LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
            WHERE
                ER.CodingCodeText = '已审结'
                AND EC.ShutOut = 0	
                AND EC.RegionId <> 55
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 = 2023
        --        AND EG.TimeCategoryName2 in ( {$this->seasionStr} )
                AND EG.CategoryName1 IN ('外套', '内搭','鞋履', '下装')
        -- 			AND EC.CustomerName in ('东至一店')
        -- 			AND EG.GoodsNo like 'B%'
        -- 			AND EG.GoodsNo = 'B12203002'
            GROUP BY 
                    EC.State
                    ,ER.CustomerName
                    ,EG.CategoryName1
                    ,EG.CategoryName2
                    ,EG.CategoryName
                    ,EG.GoodsNo
            -- ORDER BY ER.RetailDate ASC  
        ";

        $select = $this->db_sqlsrv->query($sql);
        $count = count($select);

        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_skauto_first;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_skauto_first')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_skauto_first 更新成功，数量：{$count}！"
            ]);
        }
    }

    // 获取销售天数  废除
    // public function getXiaoshouDay($customer, $goodsNo) {
    //     if (! empty($customer) && ! empty($goodsNo)) {
    //         // 康雷查首单日期，计算销售天数
    //         $sql = "
    //             SELECT
    //                 TOP 1
    //                 ER.CustomerName AS 店铺名称,
    //                 EG.GoodsNo AS 货号,
    //                 FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 首单日期,
    //                 DATEDIFF(day, ER.RetailDate, DATEADD(DAY, -1, CAST(GETDATE() AS DATE))) + 1 AS 销售天数
    //             FROM
    //                 ErpRetail AS ER 
    //             LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
    //             LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
    //             LEFT JOIN ErpGoods AS EG ON EG.GoodsId = ERG.GoodsId
    //             LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
    //             WHERE
    //                 ER.CodingCodeText = '已审结'
    //                 AND EC.ShutOut = 0	
    //                 AND EC.RegionId <> 55
    //                 AND EBC.Mathod IN ('直营', '加盟')
    //                 AND EC.CustomerName in ('{$customer}')
    //                 AND EG.GoodsNo = '{$goodsNo}'
    //             GROUP BY 
    //                 ER.CustomerName,EG.GoodsNo,ER.RetailDate
    //             ORDER BY 
    //                 ER.RetailDate ASC            
    //         ";
    //         $select = $this->db_sqlsrv->query($sql);
    //         if ($select) {
    //             return $select[0];
    //         } else {
    //             return false;
    //         }
    //     } else {
    //         return false;
    //     }
    // }

    // 获取店铺库存
    public function getKuchun() {
        $sql = "
            SELECT
                TOP 1000000
                EC.State AS 省份,
                EC.CustomerName As 店铺名称,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.GoodsNo AS 货号,
                SUM(ECS.Quantity) AS 店铺库存
                FROM ErpCustomerStock ECS 
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            WHERE  EC.ShutOut=0
                AND EG.TimeCategoryName1 in (2023,2024)
                AND EC.MathodId IN (4,7)
            GROUP BY 
                EC.State,
                EC.CustomItem17,
                EC.CustomerName,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.GoodsNo 
        ";

        $sql2 = "
            SELECT
                -- TOP 1000000
                EC.State AS 省份,
                EC.CustomerName As 店铺名称,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.GoodsNo AS 货号,
                SUM(ECSD.Quantity) AS 店铺库存
                        FROM ErpCustomerStock ECS 
                        left join ErpCustomerStockDetail ECSD ON ECS.StockId=ECSD.StockId
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
                        WHERE  EC.ShutOut=0
                                AND EG.TimeCategoryName1 in (2023,2024)
                                    AND EC.MathodId IN (4,7)
        -- 								AND EC.CustomerName IN ('宁德一店')
        -- 								AND EG.GoodsNo = 'B42503007'
            GROUP BY 
                EC.State,
                EC.CustomItem17,
                EC.CustomerName,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EG.GoodsNo      
        ";
        $select = $this->db_sqlsrv->query($sql2);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_skauto_kucun;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_skauto_kucun')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_skauto_kucun 更新成功！"
            ]);
        }
    }

    // 已配未发
    public function getWeifa() {
        $sql = "
            SELECT 
                EC.CustomItem15,
                EC.State AS 省份,
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName as 店铺名称,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EC.MathodId,
                EC.CustomerGrade,
                EG.GoodsNo as 货号,
                NULL AS 店铺库存,
                NULL AS 在途库存,
                SUM(ESG.Quantity) 已配未发,
                SUM(ESG.Quantity) 在途量合计,
                SUM(ESG.Quantity) 预计库存
            FROM ErpCustomer EC
            LEFT JOIN ErpSorting ES ON EC.CustomerId=ES.CustomerId
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            WHERE	EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                AND eg.TimeCategoryName1 in ('2023','2024')
            --    AND EG.TimeCategoryName2 IN ( {$this->seasionStr} ) 
                AND EC.ShutOut=0 
                AND EC.MathodId IN (4,7)
                AND ES.IsCompleted=0
            GROUP BY 
                EC.CustomItem15,
                EC.State,
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EC.MathodId,
                EC.CustomerGrade,
                EG.GoodsNo
        ";
        $select = $this->db_sqlsrv->query($sql);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_skauto_weifa;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_skauto_weifa')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_skauto_weifa 更新成功！"
            ]);
        }
    }

    // 在途
    public function getZaitu() {
        $sql_old = "
            SELECT
                EC.CustomItem15,
                EC.State  AS 省份,
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName as 店铺名称,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EC.MathodId,
                EC.CustomerGrade,
                EG.GoodsNo as 货号,
                NULL AS 店铺库存,
                SUM(EIG.Quantity) AS 在途库存,
                NULL 已配未发数量,
                SUM(EIG.Quantity) 在途量合计,
                SUM(EIG.Quantity) 预计库存
            FROM ErpCustOutbound EI 
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
            WHERE EI.CodingCodeText='已审结'
                AND EI.IsCompleted=0
                -- 排除店铺调入单 ErpCustReceipt
                 AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
                AND	EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
                AND eg.TimeCategoryName1 in ('2023', '2024')
            --    AND EG.TimeCategoryName2 IN ( {$this->seasionStr} ) 
                AND EC.ShutOut=0 
                AND EC.MathodId IN (4,7)
            GROUP BY 
                EC.CustomItem15,
                EC.State,
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName,
                EG.CategoryName1,
                EG.CategoryName2,
                EG.CategoryName,
                EC.MathodId,
                EC.CustomerGrade,
            EG.GoodsNo
        ";

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
            $this->db_easyA->execute('TRUNCATE cwl_skauto_zaitu;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_skauto_zaitu')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_skauto_zaitu 更新成功！"
            ]);
        }
    }

    // 7周销 14周销
    public function getRetail() {
        // 近一周 1-7
        $sql = "
            SELECT TOP
                1000000
                EC.State AS 省份,
                ER.CustomerName AS 店铺名称,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.GoodsNo  AS 货号,
                SUM(ERG.Quantity) AS 销量,
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
                AND ER.RetailDate >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
                AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
        -- 		AND ER.RetailDate < DATEADD(DAY, -1, CAST(GETDATE() AS DATE))
        --        AND EG.TimeCategoryName2 IN ( {$this->seasionStr} )
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023', '2024')
            GROUP BY
                ER.CustomerName
                ,EG.GoodsNo
                ,EC.State
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
            HAVING  SUM ( ERG.Quantity ) <> 0
        ";

        // 近两周 8-14
        $sql2 = "
            SELECT TOP
                1000000
                EC.State AS 省份,
                ER.CustomerName AS 店铺名称,
                EG.CategoryName1 AS 一级分类,
                EG.CategoryName2 AS 二级分类,
                EG.CategoryName AS 分类,
                EG.GoodsNo  AS 货号,
                SUM(ERG.Quantity) AS 销量,
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
                AND ER.RetailDate >= DATEADD(DAY, -14, CAST(GETDATE() AS DATE))
                AND ER.RetailDate < DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
        -- 		AND ER.RetailDate < DATEADD(DAY, -1, CAST(GETDATE() AS DATE))
        --        AND EG.TimeCategoryName2 IN ( {$this->seasionStr} )
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023', '2024')
            GROUP BY
                ER.CustomerName
                ,EG.GoodsNo
                ,EC.State
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
            HAVING  SUM ( ERG.Quantity ) <> 0
        ";
        // 7
        $select = $this->db_sqlsrv->query($sql);
        // 14
        $select2 = $this->db_sqlsrv->query($sql2);

        $this->db_easyA->execute('TRUNCATE cwl_skauto_retail7;');
        $this->db_easyA->execute('TRUNCATE cwl_skauto_retail14;');
        $chunk_list = array_chunk($select, 500);
        foreach($chunk_list as $key => $val) {
            // 基础结果 
            $insert = $this->db_easyA->table('cwl_skauto_retail7')->strict(false)->insertAll($val);
        }

        $chunk_list2 = array_chunk($select2, 500);
        foreach($chunk_list2 as $key2 => $val2) {
            // 基础结果 
            $insert = $this->db_easyA->table('cwl_skauto_retail14')->strict(false)->insertAll($val2);
        }

        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => "cwl_skauto_retail7、cwl_skauto_retail14 更新成功！"
        ]);
    }  

    // 更新云仓可用
    public function getYuncangkeyong() {
        $sql = "
            select 
                仓库名称 as 云仓,
                季节,
                货号,
                合计 as 云仓可用
            from sjp_warehouse_stock
        ";
        $select = $this->db_bi->query($sql);
        $this->db_easyA->execute('TRUNCATE cwl_skauto_ycky;');

        $chunk_list = array_chunk($select, 500);
        foreach($chunk_list as $key => $val) {
            // 基础结果 
            $insert = $this->db_easyA->table('cwl_skauto_ycky')->strict(false)->insertAll($val);
        }

        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => "cwl_skauto_ycky 更新成功！"
        ]);
    }

    public function updateSkauto() {
        // 更新销售天数
        $sql1 = "
            update cwl_skauto as s 
                left join cwl_skauto_first as f 
                    on s.`省份`=f.省份
                    and s.`店铺名称` = f.店铺名称 
                    and s.`一级分类`=f.`一级分类` 
                    and s.`二级分类`=f.`二级分类`
                    and s.`分类`=f.`分类`
                    and s.`货号`=f.`货号`
                set s.销售天数=f.销售天数, s.首单日期=f.首单日期 
                where s.销售天数 is null
        ";
        $this->db_easyA->execute($sql1);

        // 更新库存
        $sql2 = "
            update cwl_skauto as s 
            left join cwl_skauto_kucun as k 
                on s.`省份`= k.`省份` 
                and s.店铺名称= k.店铺名称
                and s.`一级分类`=k.`一级分类` 
                and s.`二级分类`=k.`二级分类`
                and s.`分类` = k.`分类`
                and s.`货号` = k.`货号`
            set s.店铺库存=k.店铺库存
            where s.店铺库存 is null
        ";
        $this->db_easyA->execute($sql2);

        // 更新已配未发
        $sql3 = "
            update cwl_skauto as s 
            left join cwl_skauto_weifa as w 
                on s.`省份`= w.省份
                and s.`店铺名称` = w.店铺名称 
                and s.`一级分类`=w.`一级分类` 
                and  s.`二级分类`=w.`二级分类`
                and s.`分类`=w.`分类`
                and s.`货号`=w.`货号`
            set s.已配未发=w.已配未发
            where s.已配未发 is null
        ";
        $this->db_easyA->execute($sql3);

        // 更新已配未发
        $sql4 = "
            update cwl_skauto as s 
            left join cwl_skauto_zaitu as z
                on s.`省份`=z.省份
                and s.`店铺名称` = z.店铺名称 
                and s.`一级分类`= z.`一级分类` 
                and  s.`二级分类`= z.`二级分类`
                and s.`分类`= z.`分类`
                and s.`货号`= z.`货号`
            set s.在途库存 = z.在途库存
            where s.在途库存 is null
        ";
        $this->db_easyA->execute($sql4);

        // 更新一周销
        $sql5 = "
            update cwl_skauto as s 
            left join cwl_skauto_retail7 as z
                on s.`省份`=z.省份
                and s.`店铺名称` = z.店铺名称 
                and s.`一级分类`= z.`一级分类` 
                and s.`二级分类`= z.`二级分类`
                and s.`分类`= z.`分类`
                and s.`货号`= z.`货号`
            set s.近一周销 = z.销量
            where s.近一周销 is null        
        ";
        $this->db_easyA->execute($sql5);

        // 更新两周销
        $sql6 = "
            update cwl_skauto as s 
            left join cwl_skauto_retail14 as z
                on s.`省份`=z.省份
                and s.`店铺名称` = z.店铺名称 
                and s.`一级分类`= z.`一级分类` 
                and s.`二级分类`= z.`二级分类`
                and s.`分类`= z.`分类`
                and s.`货号`= z.`货号`
            set s.近两周销 = z.销量
            where s.近两周销 is null      
        ";
        $this->db_easyA->execute($sql6);

        // 更云仓可用
        $sql7 = "
            update cwl_skauto as s 
            right join cwl_skauto_ycky as y
                on s.`云仓`=y.云仓
                and s.`货号` = y.货号
            set s.云仓可用 = y.云仓可用
            where s.云仓可用 is null        
        ";
        $this->db_easyA->execute($sql7);

        return json([
            'status' => 1,
            'msg' => 'success',
            'content' => "updateSkauto_1 更新成功！"
        ]);
    }

    // 店留量 30%
    public function updateSkautoRes() {
        $find_config = $this->db_easyA->table('cwl_skauto_config')->where('id=1')->find();
        $sql = "
        select 
            (t1.总入量 + t1.已配未发 + t1.`在途库存` - t1.`累销数量`) / (t1.总入量+t1.已配未发+t1.在途库存) as test,
            t1.*,
            case
            when t1.总入量 - t1.累销数量 <=0 and t1.店铺库存 <=0 then '售空'
            when t1.总入量 - t1.累销数量 > 0 and t1.总入量 - t1.累销数量 <= 5 and (t1.总入量 + t1.已配未发 + t1.`在途库存` - t1.`累销数量`) / (t1.总入量+t1.已配未发+t1.在途库存) <= {$find_config['店留量']}
                and t1.店铺库存>0 and t1.店铺库存 <=5 then '即将售空'
            end as 售空提醒
            from  
            (select 
                    云仓,商品负责人,省份,经营模式,店铺名称,一级分类,二级分类,分类,风格,货号,零售价,当前零售价,折率,上市天数,销售天数,总入量,累销数量,店铺库存,近一周销,近两周销,云仓可用,首单日期,更新日期,
                    IFNULL(在途库存, 0) as 在途库存,
                    IFNULL(已配未发, 0) as 已配未发,
                    季节,季节归集
                    from cwl_skauto 
            where 
            `销售天数`<= {$find_config['销售天数']} 
            and 总入量 > {$find_config['总入量']} 
            and ( 折率 >= {$find_config['折率']} || (折率 < {$find_config['折率']} AND 
                    (
                            (二级分类 = '短T' AND 当前零售价 > {$find_config['短T']}) 
                        OR (二级分类 = '休闲短衬' AND 当前零售价 > {$find_config['休闲短衬']})
                        OR (二级分类 = '休闲短裤' AND 当前零售价 > {$find_config['休闲短裤']}) 
                        OR (二级分类 = '松紧短裤' AND 当前零售价 > {$find_config['松紧短裤']}) 
                        OR (二级分类 = '牛仔短裤' AND 当前零售价 > {$find_config['牛仔短裤']}) 
                        OR (二级分类 = '休闲长裤' AND 当前零售价 > {$find_config['休闲长裤']}) 
                        OR (二级分类 = '牛仔长裤' AND 当前零售价 > {$find_config['牛仔长裤']}) 
                        OR (二级分类 = '松紧长裤' AND 当前零售价 > {$find_config['松紧长裤']}) 
                    ))
                )
            ) as t1   
        "; 
        $select = $this->db_easyA->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_skauto_res;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_skauto_res')->strict(false)->insertAll($val);
            }

            $sql_专员店铺预计库存 = "
                UPDATE
                    cwl_skauto_res as r
                LEFT JOIN (
                    select 商品负责人,货号,sum(预计库存数量) as 预计库存数量 from sp_sk group by 商品负责人,货号
                ) as t on r.商品负责人 = t.商品负责人 and r.货号 = t.货号
                SET
                    r.专员店铺预计库存 = t.预计库存数量
                WHERE 1
            ";
            $this->db_easyA->execute($sql_专员店铺预计库存);

            $this->db_easyA->table('cwl_skauto_config')->where('id=1')->strict(false)->update([
                'skauto_res_updatetime' => date('Y-m-d H:i:s')
            ]);  

            // 同步bi的表
            $sql_ww = "
                SELECT
                        *
                from cwl_skauto_res 
                where 1
                    AND 在途库存 + 已配未发 <= 0
                    AND 售空提醒 IN ('售空', '即将售空')
            ";
            $select_ww = $this->db_easyA->query($sql_ww);

            if ($select_ww) {
                $this->db_bi->execute('TRUNCATE ww_skauto_res;');

                $chunk_list2 = array_chunk($select_ww, 500);

                foreach($chunk_list2 as $key2 => $val2) {
                    // 基础结果 
                    $insert = $this->db_bi->table('ww_skauto_res')->strict(false)->insertAll($val2);
                }
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_skauto_res  更新成功，数量：{$count}！"
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => "cwl_skauto_res  更新失败，数量：{$count}！"
            ]);
        }       
    }

    public function updateTable() {
        $sql = "
            SELECT
                    *
            from cwl_skauto_res 
            where 1
                AND 在途库存 + 已配未发 <= 0
                AND 售空提醒 IN ('售空', '即将售空')
        ";
        $select = $this->db_easyA->query($sql);
        $count = count($select);
        if ($select) {
            $this->db_bi->execute('TRUNCATE ww_skauto_res;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_bi->table('ww_skauto_res')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "ww_skauto_res 1 更新成功，数量：{$count}！"
            ]);
        }
    }
}
