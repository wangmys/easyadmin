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
 * @ControllerAnnotation(title="断码率")
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

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_sqlsrv = Db::connect('sqlsrv');
    }

    public function skauto() {
        $sql = "
            SELECT 
                '1' as status,
                sk.云仓,
                sk.商品负责人,
                sk.省份,
                sk.经营模式,
                sk.店铺名称,
                --      sk.年份,
                --      sk.季节, 
                sk.一级分类,
                sk.二级分类,
                sk.分类,
                sk.风格,
                --      SUBSTRING(sk.分类, 1, 2) as 领型,
                sk.货号,
                        st.零售价,
                        st.当前零售价,
                        bu.上市天数,
                sk.`总入量数量` AS 总入量,
                bu.累销量 as 累销数量,
                date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM `sp_sk` as sk
            LEFT JOIN customer as c ON sk.店铺名称=c.CustomerName
            LEFT JOIN sp_ww_chunxia_stock as st ON sk.省份=st.省份 AND sk.店铺名称=st.店铺名称 AND sk.分类=st.分类 AND sk.货号 = st.货号
            LEFT JOIN sp_ww_budongxiao_detail as bu ON sk.省份=bu.省份 AND sk.店铺名称=bu.店铺名称 AND sk.分类=bu.小类 AND sk.货号 = bu.货号
            WHERE
                sk.季节 IN ('初夏', '盛夏', '夏季') 
                AND c.Region <> '闭店区'
                -- AND sk.商品负责人='曹太阳'
                -- AND sk.店铺名称 IN ('东至一店')
                AND sk.年份 = 2023
                -- 	AND sk.省份='广东省'
                -- 	AND sk.货号='B32101027'
            GROUP BY 
            sk.店铺名称, 
            --          sk.季节, 
            sk.货号
        ";

        $select = $this->db_bi->query($sql);
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
                TOP 20000000
                ER.CustomerName AS 店铺名称,
                EG.GoodsNo AS 货号
                ,
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
                AND EG.TimeCategoryName2 in ('初夏', '夏季', '盛夏')
                AND EG.CategoryName1 IN ('外套', '内搭','鞋履', '下装')
    -- 			AND EC.CustomerName in ('东至一店')
    -- 			AND EG.GoodsNo like 'B%'
    -- 			AND EG.GoodsNo = 'B12203002'
            GROUP BY 
                    ER.CustomerName
                    ,EG.GoodsNo
                    ,FORMAT(ER.RetailDate, 'yyyy-MM-dd')
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

    // 获取销售天数
    public function getXiaoshouDay($customer, $goodsNo) {
        if (! empty($customer) && ! empty($goodsNo)) {
            // 康雷查首单日期，计算销售天数
            $sql = "
                SELECT
                    TOP 1
                    ER.CustomerName AS 店铺名称,
                    EG.GoodsNo AS 货号,
                    FORMAT(ER.RetailDate, 'yyyy-MM-dd') AS 首单日期,
                    DATEDIFF(day, ER.RetailDate, DATEADD(DAY, -1, CAST(GETDATE() AS DATE))) + 1 AS 销售天数
                FROM
                    ErpRetail AS ER 
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN ErpGoods AS EG ON EG.GoodsId = ERG.GoodsId
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                WHERE
                    ER.CodingCodeText = '已审结'
                    AND EC.ShutOut = 0	
                    AND EC.RegionId <> 55
                    AND EBC.Mathod IN ('直营', '加盟')
                    AND EC.CustomerName in ('{$customer}')
                    AND EG.GoodsNo = '{$goodsNo}'
                GROUP BY 
                    ER.CustomerName,EG.GoodsNo,ER.RetailDate
                ORDER BY 
                    ER.RetailDate ASC            
            ";
            $select = $this->db_sqlsrv->query($sql);
            if ($select) {
                return $select[0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
