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

    public function changku()
    {
        $sql = "
            SELECT 
                m.*, 
                m.上柜家数 / m.店铺个数 AS 上柜率,
                date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM
            (
                SELECT
                    sk.云仓,sk.年份,sk.季节,sk.一级分类,sk.二级分类,sk.分类,
                    sk.货号,
                    count(sk.预计库存数量) as 上柜家数,
                    sum(sk.预计库存数量) as 已铺件数,
                    zysgs.直营上柜数,
                    jmsgs.加盟上柜数,
                    dpgs.店铺个数
                FROM
                    sp_sk as sk
                LEFT JOIN (
                    SELECT
                        云仓,一级分类,二级分类,分类,货号,
                        预计库存数量,count(预计库存数量) as 直营上柜数
                    FROM
                        sp_sk 
                    WHERE 1
                        AND 预计库存数量 > 0
                        AND 经营模式 = '直营'
                    GROUP BY
                        云仓,季节,一级分类,二级分类,分类,货号
                ) AS zysgs ON zysgs.云仓 = sk.云仓 AND zysgs.一级分类 = sk.一级分类 AND zysgs.二级分类 = sk.二级分类 AND zysgs.货号 = sk.货号
                LEFT JOIN (
                    SELECT
                        云仓,一级分类,二级分类,分类,货号,
                        预计库存数量,count(预计库存数量) as 加盟上柜数
                    FROM
                        sp_sk 
                    WHERE 1
                        AND 预计库存数量 > 0
                        AND 经营模式 = '加盟'
                    GROUP BY
                        云仓,季节,一级分类,二级分类,分类,货号
                ) AS jmsgs ON jmsgs.云仓 = sk.云仓 AND jmsgs.一级分类 = sk.一级分类 AND jmsgs.二级分类 = sk.二级分类 AND jmsgs.货号 = sk.货号
                RIGHT JOIN (
                    select t.云仓,count(*) AS 店铺个数 from 
                    (	
                        SELECT
                            云仓,店铺名称
                        FROM
                            sp_sk 
                        WHERE 1
                        GROUP BY
                            云仓,店铺名称
                    ) as t
                    GROUP BY t.云仓
                ) AS dpgs ON sk.云仓 = dpgs.云仓
                WHERE 1
                    AND sk.预计库存数量 > 0
                GROUP BY
                    sk.云仓,sk.季节,sk.一级分类,sk.二级分类,sk.分类,sk.货号
            ) AS m		
        ";
		
        $select = $this->db_easyA->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            $this->db_easyA->execute('TRUNCATE cwl_shangguitips_cangku;');
            $chunk_list = array_chunk($select, 500);


            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_shangguitips_cangku')->strict(false)->insertAll($val);
            }

            // 更新销售占比
            // $sql2 = "
            //     UPDATE cwl_shangguitips_retail AS r
            //     RIGHT JOIN
            //     (
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '春季' and 风格='基本款'
            //         UNION ALL
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '春季' and 风格='引流款'
            //         UNION ALL
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '夏季' and 风格='基本款'
            //         UNION ALL
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '夏季' and 风格='引流款'
            //         UNION ALL
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '秋季' and 风格='基本款'
            //         UNION ALL
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '秋季' and 风格='引流款'
            //         UNION ALL
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '冬季' and 风格='基本款'
            //         UNION ALL
            //         SELECT 
            //             季节归集,风格, sum(销售金额) as 销售总金额 FROM `cwl_shangguitips_retail` where 季节归集 = '冬季' and 风格='引流款'
            //     ) as t ON r.季节归集 = t.季节归集 AND r.风格 = t.风格
            //     SET 
            //         r.`销售总金额` = t.销售总金额,
            //         r.`销售占比` = round(r.销售金额 / t.销售总金额, 3)
            // ";
            // $this->db_easyA->execute($sql2);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_shangguitips_cangku 更新成功，数量：{$count}！"
            ]);

        }
    }

    public function changku_2() {
        $find_config = $this->db_easyA->table('cwl_shangguitips_config')->where("id=1")->find();
        // 更新风格 一级风格 二级风格
        $sql1 = "
            UPDATE
                cwl_shangguitips_cangku AS ck 
            LEFT JOIN sp_ww_budongxiao_yuncangkeyong AS ky ON  ck.一级分类 = ky.一级分类 AND ck.二级分类 = ky.二级分类 AND ck.分类 = ky.分类 AND ck.货号 = ky.货号
            SET 
                ck.风格 = ky.风格,
                ck.一级风格 = ky.一级风格,
                ck.二级风格 = ky.二级风格
            WHERE 
                1
        ";

        // 更新仓库可用
        $sql2 = "
            UPDATE
                cwl_shangguitips_cangku AS ck 
            LEFT JOIN sp_ww_budongxiao_yuncangkeyong AS ky ON ck.云仓 = ky.仓库名称 AND ck.一级分类 = ky.一级分类 AND ck.二级分类 = ky.二级分类 AND ck.分类 = ky.分类 AND ck.货号 = ky.货号
            SET 
                ck.`可用库存_00/28/37/44/100/160/S` = ky.`可用库存_00/28/37/44/100/160/S`,
                ck.`可用库存_29/38/46/105/165/M` = ky.`可用库存_29/38/46/105/165/M`,
                ck.`可用库存_30/39/48/110/170/L` = ky.`可用库存_30/39/48/110/170/L`,
                ck.`可用库存_31/40/50/115/175/XL` = ky.`可用库存_31/40/50/115/175/XL`,
                ck.`可用库存_32/41/52/120/180/2XL` = ky.`可用库存_32/41/52/120/180/2XL`,
                ck.`可用库存_33/42/54/125/185/3XL` = ky.`可用库存_33/42/54/125/185/3XL`,
                ck.`可用库存_34/43/56/190/4XL` = ky.`可用库存_34/43/56/190/4XL`,
                ck.`可用库存_35/44/58/195/5XL` = ky.`可用库存_35/44/58/195/5XL`,
                ck.`可用库存_36/6XL` = ky.`可用库存_36/6XL`,
                ck.`可用库存_38/7XL` = ky.`可用库存_38/7XL`,
                ck.`可用库存_40` = ky.`可用库存_40`,
                ck.`可用数量` = ky.`可用库存Quantity`
            WHERE 
                1
        ";

        // 主码齐码情况
        $sql_主码 = "
             UPDATE `cwl_shangguitips_cangku` 
                SET 
                    主码齐码个数 = CASE 
                        WHEN `一级分类` IN ('下装') THEN 
                            CASE
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AAAAAA%' THEN 6 
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AAAAA%' THEN 5	
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AAAA%' THEN 4	
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AAA%' THEN 3	
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AA%' THEN 2	
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%A%' THEN 1		
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%BBBBBB%' THEN 0
                        END
                        WHEN `一级分类` IN ('内搭', '外套', '鞋履') OR `二级分类` IN ('松紧长裤', '松紧短裤') THEN 
                            CASE
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AAAA%' THEN 4 
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AAA%' THEN 3	
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%AA%' THEN 2	
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%A%' THEN 1		
                                WHEN CONCAT(
                                                CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                                CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END
                                        ) LIKE '%BBBB%' THEN 0
                        END
                    END
                WHERE 1
        ";

        $sql_主码2 = "
            UPDATE cwl_shangguitips_cangku
            SET 
                主码齐码情况 = case
                    when 二级分类 = '松紧长裤' AND 主码齐码个数 >= {$find_config['松紧长裤']} THEN '可配'
                    when 二级分类 = '松紧短裤' AND 主码齐码个数 >= {$find_config['松紧短裤']} THEN '可配'
                    when 一级分类 = '内搭' AND 主码齐码个数 >= {$find_config['内搭']} THEN '可配'
                    when 一级分类 = '外套' AND 主码齐码个数 >= {$find_config['外套']} THEN '可配'
                    when 一级分类 = '鞋履' AND 主码齐码个数 >= {$find_config['鞋履']} THEN '可配'
                    when 一级分类 = '下装' AND 主码齐码个数 >= {$find_config['下装']} THEN '可配'
                    else NULL
                end
        ";

        $sql_主码最小值 = "
            update cwl_shangguitips_cangku 
            set 主码最小值 = 
                            case 
                                when
                                    二级分类 IN ('松紧长裤', '松紧短裤')
                                then	
                                    least(`可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`)
                                when
                                    一级分类 in ('内搭', '外套', '鞋履')
                                then	
                                    case
                                        when 
                                            `可用库存_30/39/48/110/170/L` = 0 OR `可用库存_30/39/48/110/170/L` is null
                                        then
                                            least(`可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`)
                                        when 
                                            `可用库存_33/42/54/125/185/3XL` = 0 OR `可用库存_33/42/54/125/185/3XL` is null
                                        then
                                            least(`可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`)
                                        else
                                            least(`可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`)
                                    end
                                when
                                    一级分类 in ('下装') AND 二级分类 NOT IN ('松紧长裤', '松紧短裤')
                                then	
                                    case
                                        when 
                                            `可用库存_29/38/46/105/165/M` = 0 OR `可用库存_29/38/46/105/165/M` is null
                                        then
                                                least(`可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`, `可用库存_34/43/56/190/4XL`)
                                        when 
                                            `可用库存_34/43/56/190/4XL` = 0 OR `可用库存_34/43/56/190/4XL` is null
                                        then
                                                least(`可用库存_29/38/46/105/165/M`, `可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`)
                                        ELSE
                                                least(`可用库存_29/38/46/105/165/M`, `可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`
                                                , `可用库存_34/43/56/190/4XL`)
                                    end
                            end
            where 1
                and 主码齐码情况 = '可配' 
        ";

        $sql_预计最大可加店数 = "
            update cwl_shangguitips_cangku as m1 
            left join (select 
                t.*,
                case
                    WHEN TRUNCATE(t.主码最小值 / 1.5, 0) > t.未上柜家数 THEN t.未上柜家数
                    WHEN TRUNCATE(t.主码最小值 / 1.5, 0) <= t.未上柜家数 THEN TRUNCATE(t.主码最小值 / 1.5, 0)
                end as 预计最大可加铺店数
            from (
                select 
                    云仓,一级分类,二级分类,分类,货号,主码最小值,
                    店铺个数 - 上柜家数 as 未上柜家数
                    from cwl_shangguitips_cangku
                    where 1
                        and 主码齐码情况 = '可配'
            ) AS t) m2 on m1.云仓 = m2.云仓 and m1.一级分类 = m2.一级分类 and m1.二级分类 = m2.二级分类 and m1.分类 = m2.分类 and m1.货号 = m2.货号
            set 
                m1.预计最大可加铺店数 = m2.预计最大可加铺店数 	
            where 
                m1.预计最大可加铺店数 is null        
        ";
        $this->db_easyA->execute($sql1);
        $this->db_easyA->execute($sql2);
        $this->db_easyA->execute($sql_主码);
        $this->db_easyA->execute($sql_主码2);
        $this->db_easyA->execute($sql_主码最小值);
        $this->db_easyA->execute($sql_预计最大可加店数);
    }
}
