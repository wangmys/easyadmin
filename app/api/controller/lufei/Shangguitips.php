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
 * @ControllerAnnotation(title="新品上柜提醒跑数")
 */
class Shangguitips extends BaseController
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

    public function test() {
        $data = [
            [
                '省份' => '广东省',
                '经营模式' => '直营',
            ],
            [
                '省份' => '湖南省',
                '经营模式' => '加盟',
            ],
            [
                '省份' => '四川省',
                '经营模式' => '加盟',
            ],
        ];

        return json($data);
    }

    public function retail()
    {
        $sql = "
            SELECT   
                t.季节归集,
                t.风格,
                t.一级分类,
                t.二级分类,
                sum(t.销售数量) as 销售数量,
                sum(t.销售金额) as 销售金额,
                t.更新日期
            FROM ( 								
                SELECT
                        EG.TimeCategoryName2 as 季节,
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
                        EG.CategoryName1 AS 一级分类,
                        EG.CategoryName2 AS 二级分类,
                        EG.StyleCategoryName AS 风格,
                        SUM(ERG.Quantity) AS 销售数量,
                        SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额,
                        CONVERT(varchar(10),GETDATE(),120) AS 更新日期
                FROM
                        ErpRetail AS ER 
                LEFT JOIN erpRetailGoods AS ERG ON ER.RetailID = ERG.RetailID
                LEFT JOIN ErpCustomer AS EC ON ER.CustomerId = EC.CustomerId
                LEFT JOIN ErpBaseCustomerMathod AS EBC ON EC.MathodId = EBC.MathodId
                LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
                WHERE
                        ER.CodingCodeText = '已审结' AND EC.ShutOut = 0 AND ER.RetailDate >= DATEADD(DAY, -7, CAST(GETDATE() AS DATE))
                        AND ER.RetailDate < DATEADD(DAY, 0, CAST(GETDATE() AS DATE))
                        AND EC.RegionId NOT IN ('40', '55', '84', '85',  '97')
                        AND EG.TimeCategoryName2 NOT IN ('通季', '畅销季')
                        AND EG.CategoryName1 IN ('内搭', '外套','下装', '鞋履')
                        AND EBC.Mathod IN ('直营', '加盟')
                GROUP BY 
                        EG.CategoryName1,
                        EG.CategoryName2,
                        EG.StyleCategoryName,
                        EG.TimeCategoryName2
            ) AS t
            GROUP BY 
                t.季节归集,
                t.一级分类,
                t.二级分类,
                t.风格,
                t.更新日期
            ORDER BY 季节归集, 风格, 一级分类, 二级分类
        
        ";
		
        $select = $this->db_sqlsrv->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_shangguitips_retail;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_shangguitips_retail')->strict(false)->insertAll($val);
            }

            // 更新销售占比
            $sql2 = "
                UPDATE cwl_shangguitips_retail AS r
                RIGHT JOIN
                (
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '春季' and 风格='基本款'
                    UNION ALL
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '春季' and 风格='引流款'
                    UNION ALL
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '夏季' and 风格='基本款'
                    UNION ALL
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '夏季' and 风格='引流款'
                    UNION ALL
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '秋季' and 风格='基本款'
                    UNION ALL
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '秋季' and 风格='引流款'
                    UNION ALL
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '冬季' and 风格='基本款'
                    UNION ALL
                    SELECT 
                        季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '冬季' and 风格='引流款'
                ) as t ON r.季节归集 = t.季节归集 AND r.风格 = t.风格
                SET 
                    r.`销售总金额` = t.销售总金额,
                    r.`销售占比` = round(r.销售金额 / t.销售总金额, 3)
            ";
            $this->db_easyA->execute($sql2);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_shangguitips_retail 更新成功，数量：{$count}！"
            ]);

        }
    }

}
