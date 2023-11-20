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

    public function autoUpdate() {
        $this->biaozhun();
        $this->biaozhun_pro();
        $this->sk_1();
        $this->sk_2();
        $this->retail();
        $this->cangku();
        $this->cangku_2();
        $this->handle_1();
        $this->handle_2();
        $this->handle_3();
        $this->handle_5();
        $this->handle_6();
        $this->handle_7();
        // 可上店铺最后
        $this->handle_4();

        echo date('Y-m-d H:i:s');
    }

    // 标准1  标准文件上传到 cwl_shangguitips_biaozhun_no 通过该方法更新到 cwl_shangguitips_biaozhun
    public function biaozhun() {
        // 去掉未开业，闭店
        $sql = "
            select * from cwl_shangguitips_biaozhun_no where B级 in (
                select CustomerName from customer_pro where 1
            )
        ";
        $select = $this->db_easyA->query($sql);
        
        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_shangguitips_biaozhun;');
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_shangguitips_biaozhun')->strict(false)->insertAll($val);
            }
        }

    }

    // 更新标准 经营模式
    public function biaozhun_pro() {
        $this->db_easyA->execute('TRUNCATE cwl_shangguitips_biaozhun_pro;'); 
        $sql_B级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'B级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `B级` IS NOT NULL AND `B级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_A1级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'A1级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `A1级` IS NOT NULL AND `A1级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_A2级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'A2级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `A2级` IS NOT NULL AND `A2级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_A3级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'A3级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `A3级` IS NOT NULL AND `A3级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_N级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'N级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `N级` IS NOT NULL AND `N级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_H3级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'H3级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `H3级` IS NOT NULL AND `H3级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_H6级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'H6级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `H6级` IS NOT NULL AND `H6级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_K1级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'K1级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `K1级` IS NOT NULL AND `K1级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_K2级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'K2级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `K2级` IS NOT NULL AND `K2级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_X1级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'X1级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `X1级` IS NOT NULL AND `X1级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_X2级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'X2级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `X2级` IS NOT NULL AND `X2级` != '0' 
            group by 云仓,经营模式,B级
        ";
        $sql_X3级 = "
            SELECT 云仓,经营模式,B级 as 店铺名称,'X3级' AS 二级风格 FROM cwl_shangguitips_biaozhun WHERE `X3级` IS NOT NULL AND `X3级` != '0' 
            group by 云仓,经营模式,B级
        ";

        $select_B级 = $this->db_easyA->query($sql_B级);
        $select_A1级 = $this->db_easyA->query($sql_A1级);
        $select_A2级 = $this->db_easyA->query($sql_A2级);
        $select_A3级 = $this->db_easyA->query($sql_A3级);
        $select_N级 = $this->db_easyA->query($sql_N级);
        $select_H3级 = $this->db_easyA->query($sql_H3级);
        $select_H6级 = $this->db_easyA->query($sql_H6级);
        $select_K1级 = $this->db_easyA->query($sql_K1级);
        $select_K2级 = $this->db_easyA->query($sql_K2级);
        $select_X1级 = $this->db_easyA->query($sql_X1级);
        $select_X2级 = $this->db_easyA->query($sql_X2级);
        $select_X3级 = $this->db_easyA->query($sql_X3级);

        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_B级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_A1级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_A2级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_A3级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_N级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_H3级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_H6级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_K1级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_K2级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_X1级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_X2级);
        $this->db_easyA->table('cwl_shangguitips_biaozhun_pro')->strict(false)->insertAll($select_X3级);


        $sql_店铺剔除 = "
            (select 店铺名称 from cwl_shangguitips_biaozhun_pro where 店铺名称 not in (
                select CustomerName from customer_pro where 1
            )) 
        ";
        $店铺剔除 = $this->db_easyA->query($sql_店铺剔除);
        // dump($店铺剔除);die;
        if ($店铺剔除) {
            $mapStr = '';
            foreach ($店铺剔除 as $key => $val) {
                if ($key < count($店铺剔除) -1 ) {
                    $mapStr .= "'{$val["店铺名称"]}'" . ",";
                } else {
                    $mapStr .= "'{$val["店铺名称"]}'";
                }
            }
                    // dump($mapStr);die;
            $this->db_easyA->execute("
                delete from cwl_shangguitips_biaozhun_pro where 店铺名称 in ($mapStr)
            ");
        }
    }

    public function sk_1() {
        // customer_pro已开业 未闭店
        $sql = "
            SELECT
                * 
            FROM
                sp_sk 
            WHERE 1
                AND 季节 IN ( '初秋', '深秋', '秋季', '初冬', '深冬', '冬季')
                AND 店铺名称 IN (
                    SELECT CustomerName FROM customer_pro GROUP BY CustomerName
                )
        ";
        $select = $this->db_easyA->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            $this->db_easyA->execute('TRUNCATE cwl_shangguitips_sk;');
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_shangguitips_sk')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_shangguitips_sk 更新成功，数量：{$count}！"
            ]);

        }
    }

    public function sk_2() {
        // 更新二级风格
        $sql_二级风格 = "
            UPDATE
                cwl_shangguitips_sk AS sk 
            LEFT JOIN sp_ww_hpzl AS hpzl ON  sk.一级分类 = hpzl.一级分类 AND sk.二级分类 = hpzl.二级分类 AND sk.分类 = hpzl.分类 AND sk.货号 = hpzl.货号
            SET 
                sk.二级风格 = hpzl.二级风格
            WHERE 
                1
        ";
        
        $sql_二级风格修正 = "
            UPDATE cwl_shangguitips_sk 
                SET 二级风格 = 'B级' 
            WHERE
                二级风格 NOT IN ( SELECT 二级风格 FROM `cwl_shangguitips_biaozhun_pro` GROUP BY 二级风格 )
        ";

        $updateTime = date('Y-m-d H:i:s');
        $sql_更新时间 = "
        UPDATE cwl_shangguitips_config 
                SET 更新日期 = '{$updateTime}' 
            WHERE
                id = 1
        ";

        $this->db_easyA->execute($sql_二级风格);
        $this->db_easyA->execute($sql_二级风格修正);
        $this->db_easyA->execute($sql_更新时间);
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
                        AND EG.TimeCategoryName1 IN ('2023')
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

    public function cangku()
    {
        $sql = "
            SELECT
                yc.仓库名称 as 云仓,yc.一级时间分类 as 年份,yc.二级时间分类 as 季节,
                CASE
                        yc.二级时间分类
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
                yc.一级分类,yc.二级分类,yc.分类,
                yc.货号,
                上柜和预计.上柜家数,
                上柜和预计.已铺件数,
                zysgs.直营上柜数,
                jmsgs.加盟上柜数,
                dpgs.店铺个数,
                dpgszy.`店铺个数_直营`,
                dpgsjm.`店铺个数_加盟`,
                date_format(now(),'%Y-%m-%d') as 更新日期 
            FROM
                sp_ww_budongxiao_yuncangkeyong as yc 
            left join (
                SELECT
                    云仓, 货号, count(预计库存数量) as 上柜家数, sum(预计库存数量) as 已铺件数
                FROM
                    cwl_shangguitips_sk 
                WHERE
                    预计库存数量 > 0 
                GROUP BY
                    云仓,季节,一级分类,二级分类,分类,货号
            ) as 上柜和预计 ON 上柜和预计.云仓 = yc.仓库名称 AND 上柜和预计.货号 = yc.货号
            LEFT JOIN (
                    SELECT
                            云仓,一级分类,二级分类,分类,货号,
                            预计库存数量,count(预计库存数量) as 直营上柜数
                    FROM
                            cwl_shangguitips_sk  
                    WHERE 1
                            AND 预计库存数量 > 0
                            AND 经营模式 = '直营'
                    GROUP BY
                            云仓,季节,一级分类,二级分类,分类,货号
            ) AS zysgs ON zysgs.云仓 = yc.仓库名称 AND zysgs.一级分类 = yc.一级分类 AND zysgs.二级分类 = yc.二级分类 AND zysgs.货号 = yc.货号
            LEFT JOIN (
                    SELECT
                            云仓,一级分类,二级分类,分类,货号,
                            预计库存数量,count(预计库存数量) as 加盟上柜数
                    FROM
                            cwl_shangguitips_sk 
                    WHERE 1
                            AND 预计库存数量 > 0
                            AND 经营模式 = '加盟'
                    GROUP BY
                            云仓,季节,一级分类,二级分类,分类,货号
            ) AS jmsgs ON jmsgs.云仓 = yc.仓库名称 AND jmsgs.一级分类 = yc.一级分类 AND jmsgs.二级分类 = yc.二级分类 AND jmsgs.货号 = yc.货号
            RIGHT JOIN (
                    select t.云仓,count(*) AS 店铺个数 from 
                    (	
                            SELECT
                                    sk.云仓,sk.店铺名称,f.首单日期
                            FROM
                                    cwl_shangguitips_sk as sk 
                            LEFT JOIN customer_pro AS f ON sk.店铺名称 = f.CustomerName 
                            WHERE 1
                                    AND f.首单日期 IS NOT NULL
                            GROUP BY
                                    sk.云仓,sk.店铺名称
                    ) as t
                    GROUP BY t.云仓
            ) AS dpgs ON yc.仓库名称 = dpgs.云仓
            RIGHT JOIN (
                    select t.云仓,count(*) AS `店铺个数_直营` from 
                    (	
                            SELECT
                                    sk.云仓,sk.店铺名称,f.首单日期
                            FROM
                                    cwl_shangguitips_sk as sk
                            LEFT JOIN customer_pro AS f ON sk.店铺名称 = f.CustomerName 
                            WHERE 1
                                    AND f.首单日期 IS NOT NULL
                                    AND sk.经营模式='直营'
                            GROUP BY
                                    sk.云仓,sk.店铺名称
                    ) as t
                    GROUP BY t.云仓
            ) AS dpgszy ON yc.仓库名称 = dpgszy.云仓
            RIGHT JOIN (
                    select t.云仓,count(*) AS `店铺个数_加盟` from 
                    (	
                            SELECT
                            sk.云仓,sk.店铺名称,f.首单日期
                            FROM
                                    cwl_shangguitips_sk as sk
                            LEFT JOIN customer_pro AS f ON sk.店铺名称 = f.CustomerName 
                            WHERE 1
                                    AND f.首单日期 IS NOT NULL
                                    AND sk.经营模式='加盟'
                            GROUP BY
                                    sk.云仓,sk.店铺名称
                    ) as t
                    GROUP BY t.云仓
            ) AS dpgsjm ON yc.仓库名称 = dpgsjm.云仓
            WHERE 1 
                AND yc.二级时间分类 IN ('初秋', '深秋', '秋季', '初冬', '深冬', '冬季')
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

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_shangguitips_cangku 更新成功，数量：{$count}！"
            ]);

        }
    }

    public function cangku_2() {
        $find_config = $this->db_easyA->table('cwl_shangguitips_config')->where("id=1")->find();
        // 更新风格  二级风格
        $sql1 = "
            UPDATE
                cwl_shangguitips_cangku AS ck 
            LEFT JOIN sp_ww_hpzl AS hpzl ON ck.一级分类 = hpzl.一级分类 AND ck.二级分类 = hpzl.二级分类 AND ck.分类 = hpzl.分类 AND ck.货号 = hpzl.货号
            SET 
                ck.风格 = hpzl.风格,
                ck.二级风格 = hpzl.二级风格
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
                                    case
                                         when 
                                            `可用库存_30/39/48/110/170/L` <= 0 OR `可用库存_30/39/48/110/170/L` is null
                                        then
                                            least(`可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`)
                                        when 
                                            `可用库存_33/42/54/125/185/3XL` <= 0 OR `可用库存_33/42/54/125/185/3XL` is null
                                        then
                                            least(`可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`)
                                        else
                                            least(`可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`)
                                    end
                                when
                                    一级分类 in ('内搭', '外套', '鞋履')
                                then	
                                    case
                                        when 
                                            `可用库存_30/39/48/110/170/L` <= 0 OR `可用库存_30/39/48/110/170/L` is null
                                        then
                                            least(`可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`)
                                        when 
                                            `可用库存_33/42/54/125/185/3XL` <= 0 OR `可用库存_33/42/54/125/185/3XL` is null
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
                                            `可用库存_29/38/46/105/165/M` <= 0 OR `可用库存_29/38/46/105/165/M` is null
                                        then
                                            least(`可用库存_30/39/48/110/170/L`, `可用库存_31/40/50/115/175/XL`, `可用库存_32/41/52/120/180/2XL`, `可用库存_33/42/54/125/185/3XL`, `可用库存_34/43/56/190/4XL`)
                                        when 
                                            `可用库存_34/43/56/190/4XL` <= 0 OR `可用库存_34/43/56/190/4XL` is null
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

        $sql_二级风格修正 = "
            UPDATE cwl_shangguitips_cangku 
                SET 二级风格 = 'B级' 
            WHERE
                二级风格 NOT IN ( SELECT 二级风格 FROM `cwl_shangguitips_biaozhun_pro` GROUP BY 二级风格 )
        ";

        $sql_仓库齐码个数 = "
            UPDATE `cwl_shangguitips_cangku` 
            SET 
                仓库齐码个数 = 
                        CASE
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAAAAAAAAA%' THEN 11 
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAAAAAAAA%' THEN 10	
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAAAAAAA%' THEN 9
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAAAAAA%' THEN 8	
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAAAAA%' THEN 7	
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAAAA%' THEN 6		
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAAA%' THEN 5		
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAAA%' THEN 4		
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AAA%' THEN 3		
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%AA%' THEN 2		
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%A%' THEN 1																																																																																
                            WHEN CONCAT(
                                            CASE WHEN `可用库存_00/28/37/44/100/160/S` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_29/38/46/105/165/M` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_30/39/48/110/170/L` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_31/40/50/115/175/XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_32/41/52/120/180/2XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_33/42/54/125/185/3XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_34/43/56/190/4XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_35/44/58/195/5XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_36/6XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_38/7XL` >0 THEN 'A' ELSE 'B' END,
                                            CASE WHEN `可用库存_40` >0 THEN 'A' ELSE 'B' END
                                    ) LIKE '%BBBBBBBBBBB%' THEN 0
                    END  
            WHERE 1
        ";

        $sql_克重 = "
            UPDATE `cwl_shangguitips_cangku` as c
            LEFT JOIN (select 货号,克重 from cwl_shangguitips_sk group by 货号) as sk ON c.货号 = sk.货号
            SET 
                    c.克重 = CASE
                        WHEN sk.克重>0 THEN sk.克重 ELSE NULL
                    END
            WHERE 1                        
        ";
        $this->db_easyA->execute($sql1);
        $this->db_easyA->execute($sql_二级风格修正);
        $this->db_easyA->execute($sql2);
        $this->db_easyA->execute($sql_主码);
        $this->db_easyA->execute($sql_主码2);
        $this->db_easyA->execute($sql_主码最小值);
        $this->db_easyA->execute($sql_预计最大可加店数);
        $this->db_easyA->execute($sql_仓库齐码个数);
        $this->db_easyA->execute($sql_克重);
    }

    public function handle_1()
    {
        $sql = "
            SELECT
                云仓,年份,季节,
                CASE
                    季节
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
                一级分类,二级分类,分类,风格,一级风格,二级风格,货号,
                可用数量 as 云仓_可用数量,
                主码齐码情况 as `云仓_主码齐码情况`, 
                主码最小值,
                仓库齐码个数,
                店铺个数 as 店铺个数_合计,
                `店铺个数_直营`,
                `店铺个数_加盟`,
                上柜家数 as 实际上柜_上柜家数,
                直营上柜数 as 实际上柜_直营上柜数,
                加盟上柜数 as 实际上柜_加盟上柜数,
                预计最大可加铺店数,
                克重,
                date_format(now(),'%Y-%m-%d') AS 更新日期
            FROM
                cwl_shangguitips_cangku
            WHERE 
                二级风格 is NOT NULL
        ";
		
        $select = $this->db_easyA->query($sql);
        $count = count($select);

        if ($select) {
            // 删除历史数据
            $this->db_easyA->execute('TRUNCATE cwl_shangguitips_handle;');
            $chunk_list = array_chunk($select, 500);


            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_shangguitips_handle')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_shangguitips_cangku 更新成功，数量：{$count}！"
            ]);

        }
    }

    public function handle_2() {
        $sql_货品等级_计划 = "
            update cwl_shangguitips_handle as h
                set 
                货品等级_计划_直营 = 
                    case
                        h.二级风格
                        when 'B级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `B级` IS NOT NULL AND `B级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营') 
                        when 'A1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A1级` IS NOT NULL AND `A1级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营') 
                        when 'A2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A2级` IS NOT NULL AND `A2级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'A3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A3级` IS NOT NULL AND `A3级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'N级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `N级` IS NOT NULL AND `N级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'H3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `H3级` IS NOT NULL AND `H3级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'H6级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `H6级` IS NOT NULL AND `H6级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'K1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `K1级` IS NOT NULL AND `K1级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'K2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `K2级` IS NOT NULL AND `K2级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'X1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X1级` IS NOT NULL AND `X1级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'X2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X2级` IS NOT NULL AND `X2级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        when 'X3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X3级` IS NOT NULL AND `X3级` != '0' AND 云仓 = h.云仓 and 经营模式 = '直营')
                        ELSE 店铺个数_直营
                    end,
                货品等级_计划_加盟 = 
                    case
                        h.二级风格
                        when 'B级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `B级` IS NOT NULL AND `B级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟') 
                        when 'A1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A1级` IS NOT NULL AND `A1级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟') 
                        when 'A2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A2级` IS NOT NULL AND `A2级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'A3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A3级` IS NOT NULL AND `A3级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'N级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `N级` IS NOT NULL AND `N级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'H3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `H3级` IS NOT NULL AND `H3级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'H6级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `H6级` IS NOT NULL AND `H6级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'K1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `K1级` IS NOT NULL AND `K1级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'K2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `K2级` IS NOT NULL AND `K2级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'X1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X1级` IS NOT NULL AND `X1级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'X2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X2级` IS NOT NULL AND `X2级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        when 'X3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X3级` IS NOT NULL AND `X3级` != '0' AND 云仓 = h.云仓 and 经营模式 = '加盟')
                        ELSE 店铺个数_加盟
                    end,
                货品等级_计划_合计 = 
                    case
                        h.二级风格
                        when 'B级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `B级` IS NOT NULL AND `B级` != '0' AND 云仓 = h.云仓) 
                        when 'A1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A1级` IS NOT NULL AND `A1级` != '0' AND 云仓 = h.云仓) 
                        when 'A2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A2级` IS NOT NULL AND `A2级` != '0' AND 云仓 = h.云仓)
                        when 'A3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `A3级` IS NOT NULL AND `A3级` != '0' AND 云仓 = h.云仓)
                        when 'N级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `N级` IS NOT NULL AND `N级` != '0' AND 云仓 = h.云仓)
                        when 'H3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `H3级` IS NOT NULL AND `H3级` != '0' AND 云仓 = h.云仓)
                        when 'H6级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `H6级` IS NOT NULL AND `H6级` != '0' AND 云仓 = h.云仓)
                        when 'K1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `K1级` IS NOT NULL AND `K1级` != '0' AND 云仓 = h.云仓)
                        when 'K2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `K2级` IS NOT NULL AND `K2级` != '0' AND 云仓 = h.云仓)
                        when 'X1级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X1级` IS NOT NULL AND `X1级` != '0' AND 云仓 = h.云仓)
                        when 'X2级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X2级` IS NOT NULL AND `X2级` != '0' AND 云仓 = h.云仓)
                        when 'X3级' then (SELECT count(*) FROM cwl_shangguitips_biaozhun WHERE `X3级` IS NOT NULL AND `X3级` != '0' AND 云仓 = h.云仓)
                        ELSE 店铺个数_合计
                    end 
            
            WHERE 1
            -- 	AND h.货号 = 'B52502002'
            -- 	AND h.云仓 = '南昌云仓'
                AND h.季节归集 in ('秋季', '冬季')      
        ";
        $this->db_easyA->execute($sql_货品等级_计划);
    }

    public function handle_3() {
        // 货品等级实际 
        // 二级风格标准的店铺在sk表中出现了多少
        $sql = "
            select t.*, p.店铺名称 as pname, p.二级风格 as pstyle from 
            (
                SELECT
                    h.云仓,h.二级分类,h.一级分类,h.分类,h.季节归集,h.货号,h.二级风格,
                    sk.店铺名称,sk.经营模式,sk.预计库存数量
                FROM
                    `cwl_shangguitips_handle` as h 
                LEFT JOIN cwl_shangguitips_sk AS sk ON h.云仓 = sk.云仓 AND h.一级分类 = sk.一级分类 AND h.二级分类 = sk.二级分类 AND h.分类 = sk.分类 AND h.货号 = sk.货号 AND sk.`预计库存数量` > 0
                WHERE 1
            ) as t
            left join cwl_shangguitips_biaozhun_pro as p on p.店铺名称 = t.店铺名称 and p.二级风格=t.二级风格
            where 
                p.店铺名称 = t.店铺名称 and p.二级风格=t.二级风格
        ";
        $select = $this->db_easyA->query($sql);

        if ($select) {
            // 删除历史数据
            $this->db_easyA->execute('TRUNCATE cwl_shangguitips_biaozhun_customer;');
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_shangguitips_biaozhun_customer')->strict(false)->insertAll($val);
            }
        } 




        // 货品等级_实际_直营 货品等级_计划_加盟 货品等级_计划_合计
        $sql_货品等级_实际 = "
            update `cwl_shangguitips_handle` as h 
            set 
                货品等级_实际_直营 = 
                (select count(*) from cwl_shangguitips_biaozhun_customer where 云仓=h.云仓 and 二级分类=h.二级分类 and 一级分类=h.一级分类 and 分类=h.分类 
                and 货号=h.货号 and 季节归集 = h.季节归集 and 经营模式='直营'),
                货品等级_实际_加盟 = 
                (select count(*) from cwl_shangguitips_biaozhun_customer where 云仓=h.云仓 and 二级分类=h.二级分类 and 一级分类=h.一级分类 and 分类=h.分类
                and 货号=h.货号 and 季节归集 = h.季节归集 and 经营模式='加盟'), 
                货品等级_实际_合计 = 	
                (select count(*) from cwl_shangguitips_biaozhun_customer where 云仓=h.云仓 and 二级分类=h.二级分类 and 一级分类=h.一级分类 and 分类=h.分类 and 货号=h.货号 and 季节归集 = h.季节归集) 
            WHERE 1
                AND h.`季节归集` IN ('秋季', '冬季')	
        ";

        $sql_实际铺货 = "
            update `cwl_shangguitips_handle` as h 
            set 
                实际铺货_直营 = 
                (select sum(预计库存数量) from cwl_shangguitips_sk where 云仓=h.云仓 and 二级分类=h.二级分类 and 一级分类=h.一级分类 and 分类=h.分类 and 货号=h.货号 and 季节 = h.季节
                and 经营模式='直营'),
                实际铺货_加盟 = 
                (select sum(预计库存数量) from cwl_shangguitips_sk where 云仓=h.云仓 and 二级分类=h.二级分类 and 一级分类=h.一级分类 and 分类=h.分类 and 货号=h.货号 and 季节 = h.季节
                and 经营模式='加盟'), 
                实际铺货_合计 = 	
                (select sum(预计库存数量) from cwl_shangguitips_sk where 云仓=h.云仓 and 二级分类=h.二级分类 and 一级分类=h.一级分类 and 分类=h.分类 and 货号=h.货号 and 季节 = h.季节
                ) 
            WHERE 1
                AND h.`季节归集` IN ('秋季', '冬季')	
        ";

        $sql_铺货率 = "
            update `cwl_shangguitips_handle` as h 
            set 
                铺货率_直营 = round(h.实际铺货_直营 / (h.实际铺货_合计 + `云仓_可用数量`), 3),
                铺货率_加盟 = round(h.实际铺货_加盟 / (h.实际铺货_合计 + `云仓_可用数量`), 3) , 
                铺货率_合计 = round(h.实际铺货_合计 / (h.实际铺货_合计 + `云仓_可用数量`), 3)
            WHERE 1
                AND h.`季节归集` IN ('秋季', '冬季')	
        ";

        $sql_上柜率 = "
            update `cwl_shangguitips_handle` as h 
            set 
                上柜率_直营 = round(h.`实际上柜_直营上柜数` / h.`店铺个数_直营`, 3),
                上柜率_加盟 = round(h.`实际上柜_加盟上柜数` / h.`店铺个数_加盟`, 3), 
                上柜率_合计 = round(h.`实际上柜_上柜家数` / h.`店铺个数_合计`, 3)
            WHERE 1
                AND h.`季节归集` IN ('秋季', '冬季')
        ";

        $sql_货品等级上柜率 = "
            update `cwl_shangguitips_handle` as h 
            set 
                货品等级上柜率_直营 = round(h.`货品等级_实际_直营` / h.`货品等级_计划_直营`, 3),
                货品等级上柜率_加盟 = round(h.`货品等级_实际_加盟` / h.`货品等级_计划_加盟`, 3), 
                货品等级上柜率_合计 = round(h.`货品等级_实际_合计` / h.`货品等级_计划_合计`, 3)
            WHERE 1
                AND h.`季节归集` IN ('秋季', '冬季')	
        ";

        $sql_全国累销数量 = "
            update cwl_shangguitips_handle as h
            right join (
                    SELECT
                    货号, 
                    sum(累销数量) as 累销数量
                FROM
                    cwl_shangguitips_sk 
                WHERE 1
                    AND 累销数量 is not null
                GROUP BY 货号
            ) as t on h.货号 = t.货号
            set h.全国累销数量 = t.累销数量
            WHERE 1
        ";

        $sql_近1周中类销售占比 = "
            UPDATE cwl_shangguitips_handle AS h 
            LEFT JOIN cwl_shangguitips_retail AS r ON h.季节归集 = r.季节归集 AND h.一级分类 = r.一级分类 AND h.二级分类 = r.二级分类 AND h.风格 = r.风格
            SET h.`近1周中类销售占比` = r.销售占比
            WHERE 1
                AND h.季节归集 = r.季节归集
        ";

        $sql_全国上柜家数 = "
            update cwl_shangguitips_handle as h
            right join (
                SELECT
                    货号,
                    sum(`实际上柜_上柜家数`) as `实际上柜_上柜家数`
                FROM
                    cwl_shangguitips_handle
                WHERE 1
                GROUP BY 货号
            ) as t on h.货号 = t.货号
            set 全国上柜家数 = t.实际上柜_上柜家数
        ";

        $sql_最早上市天数 = "
            update cwl_shangguitips_handle as h
            right join (
                SELECT
                    货号, 
                    MAX(上市天数) AS 上市天数
                FROM
                    sp_ww_budongxiao_detail 
                WHERE 1
                GROUP BY 货号
            ) as t on h.货号 = t.货号
            set h.最早上市天数 = t.上市天数
            WHERE 1
        ";

        $sql_单款全国日均销得分 = "
            update cwl_shangguitips_handle as h
            right join (
                SELECT
                    云仓,货号,
                    (全国累销数量 / 全国上柜家数 / 最早上市天数) as 单款全国日均销得分
                FROM
                    cwl_shangguitips_handle AS h 
                LEFT JOIN cwl_shangguitips_retail AS r ON h.季节归集 = r.季节归集 AND h.一级分类 = r.一级分类 AND h.二级分类 = r.二级分类 AND h.风格 = r.风格
                WHERE 1
                    AND h.季节归集 in ('秋季', '冬季')
                ) as t on h.云仓 = t.云仓 AND h.货号 = t.货号
            set h.单款全国日均销得分 = t.单款全国日均销得分
            WHERE 1
        ";

        $sql_单款全国日均销排名_不分组 = "
            UPDATE cwl_shangguitips_handle as h
            LEFT JOIN (
                SELECT t.*,
                    @rank := @rank + 1 as 'rank' 
                FROM (
                SELECT
                    货号,
                    单款全国日均销得分
                FROM
                    cwl_shangguitips_handle
                WHERE
                    单款全国日均销得分 IS NOT NULL
                group by 货号
                ORDER BY 单款全国日均销得分 DESC 
                ) as t, (SELECT @rank:=0) r 
            ) AS m ON h.货号 = m.货号
            SET h.单款全国日均销排名 = m.rank
        ";

        $sql_单款全国日均销排名_分组 = "
            UPDATE cwl_shangguitips_handle as h
            LEFT JOIN (
                SELECT
                a.货号,
                a.单款全国日均销得分,
                CASE
                    WHEN 
                        a.二级分类 = @二级分类 and 
                        a.风格 = @风格 
                    THEN
                        @rank := @rank + 1 ELSE @rank := 1
                END AS 排名,
                @二级分类 := a.二级分类 AS 二级分类,
                @风格 := a.风格 AS 风格
                FROM
                    (
                        SELECT
                            货号,
                            二级分类,
                            风格,
                            单款全国日均销得分
                            FROM
                                cwl_shangguitips_handle
                            where 最早上市天数 > 7
                        GROUP BY 货号
                    ) as a,
                    ( SELECT @二级分类 := null,  @风格 := null, @rank := 0 ) TT
                WHERE
                    1
                ORDER BY
                    a.风格 ASC,a.二级分类 ASC,a.单款全国日均销得分 DESC
            ) AS m ON h.货号 = m.货号
            SET 
                h.单款全国日均销排名 = m.排名
            WHERE 1       
        ";

        $sql_仓库可配中类SKC数 = "
            UPDATE cwl_shangguitips_handle as h
            LEFT JOIN (
                SELECT
                    云仓,季节归集,二级分类,风格,
                    sum(
                        case
                            when `云仓_主码齐码情况`='可配' then 1 else 0
                        end
                    ) as 仓库可配中类SKC数
                FROM
                    `cwl_shangguitips_handle` 
                WHERE 1
                    AND 二级风格 is NOT NULL
                GROUP BY
                    云仓,季节归集,风格,二级分类
            ) AS t ON h.云仓 = t.云仓 AND h.季节归集=t.季节归集 AND h.二级分类 = t.二级分类 AND h.风格 = t.风格 
            set 
                h.仓库可配中类SKC数 = t.仓库可配中类SKC数
            where 1        
        ";

        $this->db_easyA->execute($sql_货品等级_实际);
        $this->db_easyA->execute($sql_实际铺货);
        $this->db_easyA->execute($sql_铺货率);
        $this->db_easyA->execute($sql_上柜率);
        $this->db_easyA->execute($sql_货品等级上柜率);
        $this->db_easyA->execute($sql_全国累销数量);
        $this->db_easyA->execute($sql_近1周中类销售占比);
        $this->db_easyA->execute($sql_全国上柜家数);
        $this->db_easyA->execute($sql_最早上市天数);
        $this->db_easyA->execute($sql_单款全国日均销得分);
        $this->db_easyA->execute($sql_单款全国日均销排名_分组);
        $this->db_easyA->execute($sql_仓库可配中类SKC数);
    }

    // 可上店铺 这个最后跑
    public function handle_4() {
        // $select = $this->db_easyA->table('cwl_shangguitips_handle')->where([
        //     ['季节归集', '=', '秋季'],
        //     // ['货号', '=', 'B52502002'],
        // ])->select()->toArray();
        $select = $this->db_easyA->query("
            select * from cwl_shangguitips_handle where 季节归集 in ('秋季', '冬季')
        ");

        $this->db_easyA->execute('TRUNCATE cwl_shangguitips_keshang_customer;');
        foreach ($select as $key => $val) {
            $sql_可上 = "
                SELECT
                    p.云仓,
                    p.店铺名称,
                    经营模式,
                    '{$val['二级风格']}' AS 二级风格,
                    '{$val['货号']}' AS 货号
                FROM
                    cwl_shangguitips_biaozhun_pro as p
                WHERE
                    p.云仓 = '{$val['云仓']}' 
                    AND p.二级风格 = '{$val['二级风格']}'
                    AND p.店铺名称 NOT IN (
                        SELECT
                            店铺名称 
                        FROM
                            cwl_shangguitips_biaozhun_customer 
                        WHERE
                            云仓 = p.云仓 
                            AND 货号 = '{$val['货号']}'
                  			AND 季节归集 = '{$val['季节归集']}'
                    )            
            ";  
            $select_可上 = $this->db_easyA->query($sql_可上);
            // dump($select_可上);
            $this->db_easyA->table('cwl_shangguitips_keshang_customer')->strict(false)->insertAll($select_可上);            
        }
    }

    // 请上柜
    public function handle_5() {
        $sql_请上柜 = "
            UPDATE cwl_shangguitips_handle as h
            RIGHT JOIN (
                SELECT
                    云仓,货号,季节归集
                FROM
                    cwl_shangguitips_handle 
                WHERE 1
                    AND 季节归集 in ('秋季', '冬季')
                    AND `云仓_主码齐码情况` = '可配'
                    AND (`货品等级上柜率_合计` <= 0.85 OR 货品等级上柜率_合计 is null)
                    AND (`铺货率_合计` <= 0.75 OR 上柜率_合计 is null)
                    AND
                        case
                            when 一级分类 in ('内搭', '外套', '鞋履') then 仓库齐码个数 >= 4 
                            when 一级分类 in ('下装') and 二级分类 in ('松紧长裤', '松紧短裤') then 仓库齐码个数 >= 5 
                            when 一级分类 in ('下装') and 二级分类 not in ('松紧长裤', '松紧短裤') then 仓库齐码个数 >= 6 
                        end
            ) as t on h.云仓 = t.云仓 and h.货号 = t.货号 and h.季节归集 = t.季节归集 
            set
                h.上柜提醒 = '请上柜'
            where 1
        ";

        $sql_重点上柜 = "
            UPDATE cwl_shangguitips_handle as h
            RIGHT JOIN
            (SELECT
                    云仓,季节归集,货号,单款全国日均销排名,仓库可配中类SKC数, `近1周中类销售占比`,
                    仓库可配中类SKC数 * `近1周中类销售占比` as 计算条件
            FROM
                    cwl_shangguitips_handle 
            WHERE 1
                    AND 上柜提醒 = '请上柜'
                    AND (单款全国日均销排名 > 0 AND 单款全国日均销排名 <= 仓库可配中类SKC数 * `近1周中类销售占比`)
            ) AS t ON h.云仓 = t.云仓 AND h.季节归集=t.季节归集 AND h.货号 = t.货号
            SET
                h.上柜提醒 = '重点上柜'
            
        ";
        $this->db_easyA->execute($sql_请上柜);
        $this->db_easyA->execute($sql_重点上柜);


    }

    // 主码最小值码数
    public function handle_6() {
        $sql_handle = " SELECT 云仓,一级分类,二级分类,分类,货号,主码最小值 FROM cwl_shangguitips_handle  WHERE  `主码最小值` IS NOT NULL ";
        $select_handle = $this->db_easyA->query($sql_handle);
        foreach($select_handle as $key => $val) {
            $主码最小值码数 = '';
            $find_cangku = $this->db_easyA->table('cwl_shangguitips_cangku')->where([
                ['云仓', '=', $val['云仓']],
                ['一级分类', '=', $val['一级分类']],
                ['二级分类', '=', $val['二级分类']],
                ['分类', '=', $val['分类']],
                ['货号', '=', $val['货号']],
            ])->find();
            if ($find_cangku['可用库存_00/28/37/44/100/160/S'] == $val['主码最小值']) {
                $主码最小值码数 = '00';
            } elseif ($find_cangku['可用库存_29/38/46/105/165/M'] == $val['主码最小值']) {
                $主码最小值码数 = '29';
            } elseif ($find_cangku['可用库存_30/39/48/110/170/L'] == $val['主码最小值']) {
                $主码最小值码数 = '30';
            } elseif ($find_cangku['可用库存_31/40/50/115/175/XL'] == $val['主码最小值']) {
                $主码最小值码数 = '31';
            } elseif ($find_cangku['可用库存_32/41/52/120/180/2XL'] == $val['主码最小值']) {
                $主码最小值码数 = '32';
            } elseif ($find_cangku['可用库存_33/42/54/125/185/3XL'] == $val['主码最小值']) {
                $主码最小值码数 = '33';
            } elseif ($find_cangku['可用库存_34/43/56/190/4XL'] == $val['主码最小值']) {
                $主码最小值码数 = '34';
            } elseif ($find_cangku['可用库存_35/44/58/195/5X'] == $val['主码最小值']) {
                $主码最小值码数 = '35';
            } elseif ($find_cangku['可用库存_36/6XL'] == $val['主码最小值']) {
                $主码最小值码数 = '36';
            } elseif ($find_cangku['可用库存_38/7XL'] == $val['主码最小值']) {
                $主码最小值码数 = '38';
            } elseif ($find_cangku['可用库存_40'] == $val['主码最小值']) {
                $主码最小值码数 = '40';
            }
            $find_cangku = $this->db_easyA->table('cwl_shangguitips_handle')->where([
                ['云仓', '=', $val['云仓']],
                ['一级分类', '=', $val['一级分类']],
                ['二级分类', '=', $val['二级分类']],
                ['分类', '=', $val['分类']],
                ['货号', '=', $val['货号']],
            ])->update([
                '主码最小值码数' => $主码最小值码数
            ]);
        }
    }

    // 钉钉推送
    public function handle_7() {
        $更新日期 = date('Y-m-d', time());
        $sql_handle_push = "
            SELECT 货号,克重,风格,一级分类,二级分类,分类,季节,年份,季节归集,'{$更新日期}' as 更新日期 FROM `cwl_shangguitips_handle`where 上柜提醒 in ('请上柜','重点上柜') GROUP BY 货号 
        ";
        $select = $this->db_easyA->query($sql_handle_push);

        if ($select) {
            $this->db_easyA->execute('TRUNCATE cwl_shangguitips_handle_push;');
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                $this->db_easyA->table('cwl_shangguitips_handle_push')->strict(false)->insertAll($val);
            }

            $sql_更新1 = "
                update cwl_shangguitips_handle_push as p
                left join sp_ww_hpzl as h on p.货号 = h.货号
                set
                    p.颜色 = h.颜色,
                    p.零售价 = h.零售价,
                    p.货品名称 = h.货品名称
            ";
            $this->db_easyA->execute($sql_更新1);


            $sql_货号 = "
                SELECT
                    货号 
                FROM
                    `cwl_shangguitips_handle_push` 
                WHERE
                    1
            ";
            $select_货号 = $this->db_easyA->query($sql_货号);
            $goodsNos = '';
            foreach ($select_货号 as $key => $val) {
                if ($key + 1 < count($select_货号)) {
                    $goodsNos .= "'".$val['货号']."',";
                } else {
                    $goodsNos .= "'".$val['货号']."'";
                }
            }
            // echo $goodsNos;die;
            $sql_图片_上市波段 = "
                SELECT
                    EG.GoodsNo as 货号,
                    EG.TimeCategoryName AS 上市波段,
                    EG.CustomItem49,
                    EG.CustomItem52,
                    EGI.Img 
                FROM ErpGoods AS EG
                LEFT JOIN ErpGoodsImg AS EGI ON EGI.GoodsId = EG.GoodsId
                WHERE
                    EG.GoodsNo IN ({$goodsNos})
            ";
            $select_图片_上市波段 = $this->db_sqlsrv->query($sql_图片_上市波段);
    
            foreach ($select_货号 as $k1 => $v1) {
                foreach ($select_图片_上市波段 as $k2 => $v2) {
                    if ($v1['货号'] == $v2['货号']) {
                            $this->db_easyA->table('cwl_shangguitips_handle_push')->where(['货号' => $v1['货号']])->update([
                                '图片' => $v2['Img'],
                                '上市波段' => $v2['上市波段'],
                                'CustomItem49' => $v2['CustomItem49'],
                                'CustomItem52' => $v2['CustomItem52'],
                            ]);
                        break;
                    }
                }
            }

            // 入库时间
            $this->ruku($goodsNos);            
        }
    }

    // 入库
    public function ruku($goodsNos = "") {
        if ($goodsNos) {
            $sql_入库时间 = "
                SELECT
                        EG.GoodsName 货品名称,
                        EG.GoodsNo 货号,
                        EW.WarehouseName 云仓,
                        CONVERT(varchar(10), ER.ReceiptDate, 120) AS 入库时间
                FROM
                        ErpReceipt AS ER
                        LEFT JOIN ErpWarehouse AS EW ON ER.WarehouseId = EW.WarehouseId
                        LEFT JOIN ErpReceiptGoods AS ERG ON ER.ReceiptId = ERG.ReceiptId
                        LEFT JOIN erpGoods AS EG ON ERG.GoodsId = EG.GoodsId
                        LEFT JOIN ErpSupply AS ES ON ER.SupplyId = ES.SupplyId 
                WHERE
                        ER.CodingCodeText = '已审结' 
                        AND ER.Type= 1 
                        -- AND ES.SupplyName <> '南昌岳歌服饰' 
                        AND EG.TimeCategoryName1 IN ( '2023','2024') 
                        AND EW.WarehouseName IN ( '南昌云仓', '武汉云仓', '广州云仓', '贵阳云仓', '长沙云仓') 
                        AND EG.GoodsNo IN ({$goodsNos})
                GROUP BY
                        EG.GoodsNo
                        ,EG.GoodsId
                        ,EG.GoodsName 
                        ,EW.WarehouseName
                        ,CONVERT(varchar(10), ER.ReceiptDate, 120)
            ";
            $select_入库时间 = $this->db_sqlsrv->query($sql_入库时间);
            if ($select_入库时间) {
                $this->db_easyA->execute('TRUNCATE cwl_shangguitips_handle_push_ruku;');
                $chunk_list = array_chunk($select_入库时间, 500);

                foreach($chunk_list as $key => $val) {
                    $this->db_easyA->table('cwl_shangguitips_handle_push_ruku')->strict(false)->insertAll($val);
                }
            }   

            // $this->db_easyA->table('cwl_shangguitips_pc')->field('云仓,货号')->where()->select();
            $sql_最近日期 = "
                SELECT
                    货号,
                    云仓,
                    max(入库时间) as 入库时间
                FROM
                    `cwl_shangguitips_handle_push_ruku` 
                WHERE 1
                GROUP BY
                    云仓,货号
            ";
            $select_最近日期 = $this->db_easyA->query($sql_最近日期);
            if ($select_最近日期) {
                $this->db_easyA->execute('TRUNCATE cwl_shangguitips_handle_push_ruku;');
                $chunk_list2 = array_chunk($select_最近日期, 500);

                foreach($chunk_list2 as $key2 => $val2) {
                    $this->db_easyA->table('cwl_shangguitips_handle_push_ruku')->strict(false)->insertAll($val2);
                }
            }
        }


    }
}
