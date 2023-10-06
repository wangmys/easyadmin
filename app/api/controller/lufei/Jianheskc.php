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
 * @ControllerAnnotation(title="检核SKC")
 */
class Jianheskc extends BaseController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_sqlsrv = '';
    protected $db_doris = '';
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_doris = Db::connect('doris');
    }

    public function seasionHandle($seasion = "夏季,秋季") {
        $seasionStr = "";
        $seasion = explode(',', $seasion);
        foreach ($seasion as $key => $val) {
            if ($key + 1 == count($seasion)) {
                if ($val == '春季') {
                    $seasionStr .= "'初春','正春','春季'";
                } elseif ($val == '夏季') {
                    $seasionStr .= "'初夏','盛夏','夏季'";
                } elseif ($val == '秋季') {
                    $seasionStr .= "'初秋','深秋','秋季'";
                } elseif ($val == '冬季') {
                    $seasionStr .= "'初冬','深冬','冬季'";
                }
            } else {
                if ($val == '春季') {
                    $seasionStr .= "'初春','正春','春季',";
                } elseif ($val == '夏季') {
                    $seasionStr .= "'初夏','盛夏','夏季',";
                } elseif ($val == '秋季') {
                    $seasionStr .= "'初秋','深秋','秋季',";
                } elseif ($val == '冬季') {
                    $seasionStr .= "'初冬','深冬','冬季',";
                }
            }
        }

        return $seasionStr;
    }

    // 数据源
    public function skc_data()
    {
        $year = date('Y', time());

        $sql = "
            select * from sp_customer_stock_skc_2 where 一级时间分类 in('2023')
        ";
		
        $select = $this->db_bi->query($sql);
        $count = count($select);

        // dump($select);die;

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_jianhe_stock_skc;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_jianhe_stock_skc')->strict(false)->insertAll($val);
            }

            $sql_update1 = "
                update cwl_jianhe_stock_skc
                    set 
                        修订季节 = right(二级时间分类, 1),
                        修订风格 = left(调整风格, 2),
                        合并 = concat(二级时间分类,调整风格,一级分类,二级分类,分类)
                    where 1
            ";
            $this->db_easyA->execute($sql_update1);

            $sql_update2 = "
                update cwl_jianhe_stock_skc as sk
                LEFT JOIN (
                    SELECT
                        分类,修订分类
                    FROM	cwl_jianhe_skc_biaozhun_1 where 分类 is not null group by 分类
                ) as b ON sk.分类 = b.分类
                set 
                    sk.修订分类 = b.修订分类
                where 
                    sk.修订分类 is null
            ";
            $this->db_easyA->execute($sql_update2);

            $sql_update_17_36 = "
                update cwl_jianhe_stock_skc as s
                left join customer as c on s.店铺名称 = c.CustomerName
                set
                    s.商品负责人 = CustomItem17,
                    s.温区 = c.CustomItem36
                where 
                    s.商品负责人 is null or s.温区 is null
            ";
            $this->db_easyA->execute($sql_update_17_36);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_jianhe_stock_skc 更新成功，数量：{$count}！"
            ]);
        }
    }

    public function test() {
        // sleep(10);
        $insert = $this->db_easyA->table('cwl_swoole_test')->where('id=1')->update([
            'num' => Db::raw('num+1'),
        ]);
        
    }

    public function test2() {
        $select = $this->db_doris->table('market_history_stock_week')->limit(10)->select();
        dump($select);
    }

    public function test3() {
        $host     = '192.168.9.230:9030';
        $username = 'root';
        $password = 'doris@2023';
        $dbname   = 'sg_dw';
        $mysql    = new Mysqli($host, $username, $password, $dbname);
        if($mysql -> connect_errno){
            die('connection fail'.$mysql->connect_errno);
        }else{
            echo 'successs';
            $mysql -> set_charset('UTF-8');            
            $sql = 'select * from test limit 1;';     
            $result = $mysql -> query($sql);     
            $data = $result -> fetch_all();
            $mysql -> close();
        }
        // echo '<pre>';    
        // print_r($data);
    }

    function doris(){
        $host     = '192.168.9.230:9030';
        $username = 'root';
        $password = 'doris@2023';
        $dbname   = 'sg_ods';
        $mysql    =  mysqli_connect($host, $username, $password, $dbname);
        if($mysql -> connect_errno){
            die('connection fail'.$mysql->connect_errno);
        }else{
            echo 'successs';
            $mysql -> set_charset('UTF-8');
            $sql = "WITH T1 AS 

            (
            
            SELECT 
            
                EC.CustomerCode,
            
                EG.GoodsNo,
            
                SUM(Quantity) AS Quantity,
            
                CASE WHEN SUM(Quantity)>0 THEN 1 ELSE 0 END AS SKC,
            
                CASE WHEN SUM(Quantity)>1 THEN 1 ELSE 0 END AS 大于1SKC,
            
                CASE WHEN SUM(Quantity)>2 THEN 1 ELSE 0 END AS 大于2SKC,
            
                CASE WHEN SUM(Quantity)>3 THEN 1 ELSE 0 END AS 大于3SKC,
            
                CASE WHEN SUM(Quantity)>4 THEN 1 ELSE 0 END AS 大于4SKC
            
            FROM ErpCustomerStock ECS 
            
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            
            WHERE EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
            GROUP BY 
            
                EC.CustomerCode,
            
                EG.GoodsNo
            
            HAVING SUM(Quantity)!=0
            
            ),
            
            
            
            
            
            T2 AS 
            
            (
            
            SELECT 
            
                T.CustomerCode,
            
                T.GoodsNo,
            
                SUM(T.Quantity) AS Quantity,
            
                CASE WHEN SUM(T.Quantity)>0 THEN 1 ELSE 0 END AS SKC,
            
                CASE WHEN SUM(T.Quantity)>1 THEN 1 ELSE 0 END AS 大于1SKC,
            
                CASE WHEN SUM(T.Quantity)>2 THEN 1 ELSE 0 END AS 大于2SKC,
            
                CASE WHEN SUM(T.Quantity)>3 THEN 1 ELSE 0 END AS 大于3SKC,
            
                CASE WHEN SUM(T.Quantity)>4 THEN 1 ELSE 0 END AS 大于4SKC
            
            FROM 
            
            (
            
            SELECT 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(ECS.Quantity) AS Quantity
            
            FROM ErpCustomerStock ECS 
            
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            
            WHERE EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
            HAVING SUM(Quantity)!=0
            
            
            
            UNION ALL
            
            SELECT 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(EDG.Quantity) AS Quantity
            
            FROM ErpDelivery ED 
            
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            
            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
            
            WHERE ED.CodingCode='EndNode2'
            
                AND ED.IsCompleted=0
            
                AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCode='EndNode2' 
            
                                                                    AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
                
            
            UNION ALL
            
            -- 店店调拨在途
            
            SELECT
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(EIG.Quantity) AS Quantity
            
            FROM ErpCustOutbound EI 
            
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            
            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
            
            WHERE EI.CodingCode='EndNode2'
            
                AND EI.IsCompleted=0
            
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCode='EndNode2' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY 
            
            EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
            
            
            UNION ALL
            
            SELECT 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(ESG.Quantity) AS Quantity
            
            FROM ErpSorting ES 
            
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
            
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON EC.CustomerId=ES.CustomerId
            
            WHERE ES.IsCompleted=0
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY	
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
            ) AS T
            
            GROUP BY 
            
                T.CustomerCode,
            
                T.GoodsNo
            
            HAVING SUM(T.Quantity)!=0
            
            ),
            
            
            
            
            
            
            
            T3 AS 
            
            (
            
            SELECT 
            
                T.CustomerCode,
            
                T.GoodsNo,
            
                SUM(T.Quantity) AS Quantity
            
            FROM 
            
            (
            
            SELECT 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(EDG.Quantity) AS Quantity
            
            FROM ErpDelivery ED 
            
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            
            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
            
            WHERE ED.CodingCode='EndNode2'
            
                AND ED.IsCompleted=0
            
                AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCode='EndNode2' 
            
                                                                    AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
                
            
            UNION ALL
            
            -- 店店调拨在途
            
            SELECT
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(EIG.Quantity) AS Quantity
            
            FROM ErpCustOutbound EI 
            
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            
            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
            
            WHERE EI.CodingCode='EndNode2'
            
                AND EI.IsCompleted=0
            
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCode='EndNode2' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY 
            
            EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
            ) AS T
            
            GROUP BY 
            
                T.CustomerCode,
            
                T.GoodsNo
            
            ),
            
            
            
            
            
            T4 AS 
            
            (
            
            SELECT 
            
                EC.CustomerCode,
            
                EG.GoodsNo,
            
                SUM(ESG.Quantity) AS Quantity
            
            FROM ErpSorting ES 
            
            LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
            
            LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON EC.CustomerId=ES.CustomerId
            
            WHERE ES.IsCompleted=0
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY	
            
                EC.CustomerCode,
            
                EG.GoodsNo
            
            )
            
            ,
            
            
            
            
            
            /*
            
            T5 AS 
            
            (
            
            SELECT 
            
                EC.CustomerCode,
            
                EG.GoodsNo,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -3 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -1 day),'%Y-%m-%d') THEN ERG.Quantity ELSE NULL END ) AS SAN,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -6 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -4 day),'%Y-%m-%d') THEN ERG.Quantity ELSE NULL END ) AS LIU,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -9 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -7 day),'%Y-%m-%d') THEN ERG.Quantity ELSE NULL END ) AS JIU,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -3 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -1 day),'%Y-%m-%d') THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END ) AS SAN_Price,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -6 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -4 day),'%Y-%m-%d') THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END ) AS LIU_Price,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -9 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -7 day),'%Y-%m-%d') THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END ) AS JIU_Price,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') = DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -1 day),'%Y-%m-%d') THEN ERG.Quantity ELSE NULL END ) AS 前一天销量,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') = DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -2 day),'%Y-%m-%d') THEN ERG.Quantity ELSE NULL END ) AS 前两天销量,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') = DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -3 day),'%Y-%m-%d') THEN ERG.Quantity ELSE NULL END ) AS 前三天销量,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') = DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -1 day),'%Y-%m-%d') THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END ) AS 前一天销额,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') = DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -2 day),'%Y-%m-%d') THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END ) AS 前两天销额,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') = DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -3 day),'%Y-%m-%d') THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END ) AS 前三天销额,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -7 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -1 day),'%Y-%m-%d') THEN ERG.Quantity ELSE NULL END ) AS 近一周销量,
            
                SUM(CASE WHEN DATE_FORMAT(ER.RetailDate,'%Y-%m-%d') BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -7 day),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -1 day),'%Y-%m-%d') THEN ERG.Quantity*ERG.DiscountPrice ELSE NULL END ) AS 近一周销额
            
            FROM ErpRetail ER 
            
            LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
            
            LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON EC.CustomerId=ER.CustomerId
            
            WHERE ER.CodingCode='EndNode2'
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套','配饰')
            
                AND DATE_FORMAT(ER.RetailDate,'%Y-%m-%d')>DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL -10 day),'%Y-%m-%d')
            
            GROUP BY	
            
                EC.CustomerCode,
            
                EG.GoodsNo
            
            )*/
            
            -- ,
            
            
            
            -- T6 AS 
            
            -- (
            
            -- SELECT 
            
            -- 	REPLACE(REPLACE(ECS.CustomerName,'(停用)',''),'(作废)','') AS CustomerName,
            
            -- 	EG.GoodsNo,
            
            -- 	SUM(Quantity) AS Quantity,
            
            -- 	CASE WHEN SUM(Quantity)>0 THEN 1 ELSE 0 END AS SKC
            
            -- FROM ff211bf.dbo.ErpCustomerStock ECS 
            
            -- LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            
            -- LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            
            -- WHERE /*EC.ShutOut=0
            
            -- 	AND EC.MathodId IN (4,7)
            
            -- 	AND CONVERT(VARCHAR,ECS.StockDate,23)<'2021-09-05'
            
            -- 	AND*/ CONVERT(VARCHAR,ECS.StockDate,23)<CONVERT(VARCHAR,GETDATE()-365,23)
            
            -- GROUP BY 
            
            -- 	ECS.CustomerName,
            
            -- 	EG.GoodsNo
            
            -- HAVING SUM(Quantity)!=0
            
            -- )
            
            
            
            T7 AS 
            
            (
            
            SELECT 
            
                T.CustomerCode,
            
                T.GoodsNo,
            
                SUM(T.Quantity) AS Quantity,
            
                CASE WHEN SUM(T.Quantity)>0 THEN 1 ELSE 0 END AS SKC,
            
                CASE WHEN SUM(T.Quantity)>1 THEN 1 ELSE 0 END AS 大于1SKC,
            
                CASE WHEN SUM(T.Quantity)>2 THEN 1 ELSE 0 END AS 大于2SKC,
            
                CASE WHEN SUM(T.Quantity)>3 THEN 1 ELSE 0 END AS 大于3SKC,
            
                CASE WHEN SUM(T.Quantity)>4 THEN 1 ELSE 0 END AS 大于4SKC
            
            FROM 
            
            (
            
            SELECT 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(ECS.Quantity) AS Quantity
            
            FROM ErpCustomerStock ECS 
            
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            
            WHERE EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
            
            
            UNION ALL
            
            SELECT 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(EDG.Quantity) AS Quantity
            
            FROM ErpDelivery ED 
            
            LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
            
            LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
            
            WHERE ED.CodingCode='EndNode2'
            
                AND ED.IsCompleted=0
            
                AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCode='EndNode2' 
            
                                                                    AND ERG.DeliveryId IS	NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY 
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
                
            
            UNION ALL
            
            -- 店店调拨在途
            
            SELECT
            
                EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo,
            
                SUM(EIG.Quantity) AS Quantity
            
            FROM ErpCustOutbound EI 
            
            LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
            
            LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
            
            LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
            
            WHERE EI.CodingCode='EndNode2'
            
                AND EI.IsCompleted=0
            
                AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCode='EndNode2' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
            
                AND EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.TimeCategoryName1 IN (2023,2022)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
            GROUP BY 
            
            EC.CustomerCode,
            
                EC.CustomerName,
            
                EG.GoodsNo
            
            ) AS T
            
            GROUP BY 
            
                T.CustomerCode,
            
                T.GoodsNo
            
            HAVING SUM(T.Quantity)!=0
            
            )
            
            
            
            
            
            SELECT 
            
                EC.CustomerName 店铺名称,
            
                EG.CategoryName1 一级分类,
            
                EG.CategoryName2 二级分类,
            
                EG.CategoryName 分类,
            
                CASE WHEN EG.CategoryName1  IN ('内搭','外套') AND IFNULL(get_json_value(T.property_content, 'title', '销售价'),EGPT.UnitPrice)/EGPT.UnitPrice<0.8 THEN '引流款'
            
                         WHEN EG.CategoryName2 LIKE '%长裤%' AND IFNULL(get_json_value(T.property_content, 'title', '销售价'),EGPT.UnitPrice)<100 THEN '引流款' 
            
                         WHEN EG.CategoryName2 IN ('卫衣','羊毛衣','针织衫','长T','休闲长衬') AND IFNULL(get_json_value(T.property_content, 'title', '销售价'),EGPT.UnitPrice)<=80 THEN '引流款'
            
                         ELSE EG.StyleCategoryName END 调整风格,
            
                -- EG.StyleCategoryName 风格,
            
                EG.TimeCategoryName1 一级时间分类,
            
                EG.TimeCategoryName2 二级时间分类,
            
                IFNULL(CASE WHEN EG.TimeCategoryName2 IN ('初夏','盛夏','夏季') THEN SUM(T2.大于3SKC)
            
                         WHEN EG.TimeCategoryName2 IN ('初秋','深秋','秋季') THEN SUM(T2.大于2SKC)
            
                         ELSE SUM(T2.SKC) END,0)	预计库存skc,
            
                IFNULL(CASE WHEN EG.TimeCategoryName2 IN ('初夏','盛夏','夏季') THEN SUM(T1.大于3SKC)
            
                         WHEN EG.TimeCategoryName2 IN ('初秋','深秋','秋季') THEN SUM(T1.大于2SKC)
            
                         ELSE SUM(T1.SKC) END,0)	店铺库存skc,
            
                         
            
                IFNULL(CASE WHEN EG.TimeCategoryName2 IN ('初夏','盛夏','夏季') THEN SUM(T7.大于3SKC)
            
                         WHEN EG.TimeCategoryName2 IN ('初秋','深秋','秋季') THEN SUM(T7.大于2SKC)
            
                         ELSE SUM(T7.SKC) END,0) 
            
                         - 
            
                IFNULL(CASE WHEN EG.TimeCategoryName2 IN ('初夏','盛夏','夏季') THEN SUM(T1.大于3SKC)
            
                         WHEN EG.TimeCategoryName2 IN ('初秋','深秋','秋季') THEN SUM(T1.大于2SKC)
            
                         ELSE SUM(T1.SKC) END,0) 在途库存skc,
            
                         
            
                IFNULL(CASE WHEN EG.TimeCategoryName2 IN ('初夏','盛夏','夏季') THEN SUM(T2.大于3SKC)
            
                         WHEN EG.TimeCategoryName2 IN ('初秋','深秋','秋季') THEN SUM(T2.大于2SKC)
            
                         ELSE SUM(T2.SKC) END,0) 
            
                         - 
            
                IFNULL(CASE WHEN EG.TimeCategoryName2 IN ('初夏','盛夏','夏季') THEN SUM(T7.大于3SKC)
            
                         WHEN EG.TimeCategoryName2 IN ('初秋','深秋','秋季') THEN SUM(T7.大于2SKC)
            
                         ELSE SUM(T7.SKC) END,0) 已配未发skc,
            
                SUM(T2.Quantity) 预计库存,
            
                SUM(T1.Quantity) 店铺库存,
            
                SUM(T3.Quantity) 在途库存,
            
                SUM(T4.Quantity) 已配未发,
            
                CURDATE() 更新时间
            
            FROM T2 
            
            LEFT JOIN ErpCustomer EC ON EC.CustomerCode=T2.CustomerCode
            
            LEFT JOIN ErpBaseCustomerRegion EBCR ON EC.RegionId=EBCR.RegionId
            
            LEFT JOIN  ErpGoods EG ON EG.GoodsNo=T2.GoodsNo
            
            -- LEFT JOIN T1 ON EC.CustomerCode=T1.CustomerCode AND EG.GoodsNo=T1.GoodsNo
            
            LEFT JOIN T1 ON EC.CustomerCode=T1.CustomerCode AND EG.GoodsNo=T1.GoodsNo
            
            LEFT JOIN T3 ON EC.CustomerCode=T3.CustomerCode AND EG.GoodsNo=T3.GoodsNo
            
            LEFT JOIN T4 ON EC.CustomerCode=T4.CustomerCode AND EG.GoodsNo=T4.GoodsNo
            
            LEFT JOIN T7 ON EC.CustomerCode=T7.CustomerCode AND EG.GoodsNo=T7.GoodsNo
            
            LEFT JOIN erp_marketing_unit EM ON kl_marketing_id=EC.CustomerId
            
            LEFT JOIN erp_marketing_unit_spu_price_relation T ON EM.id=T.marketing_unit_info_id AND EG.GoodsNo=T.spu
            
            LEFT JOIN ErpGoodsPriceType EGPT ON EG.GoodsId=EGPT.GoodsId
            
            WHERE EC.ShutOut=0
            
                AND EC.MathodId IN (4,7)
            
                AND EG.CategoryName1 IN ('内搭','下装','鞋履','外套')
            
                AND EC.RegionId!=55
            
                AND EGPT.PriceId=1
            
                AND EM.del_flag=0
            
                AND EG.GoodsNo !='B62111354'
            
                -- AND EC.CustomerName='南宁一店'
            
            GROUP BY
            
                EC.CustomerName,
            
                EG.CategoryName1,
            
                EG.CategoryName2,
            
                EG.CategoryName,
            
                EG.TimeCategoryName1,
            
                EG.TimeCategoryName2,
            
                CASE WHEN EG.CategoryName1  IN ('内搭','外套') AND IFNULL(get_json_value(T.property_content, 'title', '销售价'),EGPT.UnitPrice)/EGPT.UnitPrice<0.8 THEN '引流款'
            
                         WHEN EG.CategoryName2 LIKE '%长裤%' AND IFNULL(get_json_value(T.property_content, 'title', '销售价'),EGPT.UnitPrice)<100 THEN '引流款' 
            
                         WHEN EG.CategoryName2 IN ('卫衣','羊毛衣','针织衫','长T','休闲长衬') AND IFNULL(get_json_value(T.property_content, 'title', '销售价'),EGPT.UnitPrice)<=80 THEN '引流款'
            
                         ELSE EG.StyleCategoryName END
            
            ORDER BY EC.CustomerName
            
            ";
            $result = $mysql -> query($sql);
            $data = $result -> fetch_all();
            $mysql -> close();
        }

        // echo '<pre>';
        // print_r($data);
//        Db::connect('doris')->table('aa')->select();
    }

}
