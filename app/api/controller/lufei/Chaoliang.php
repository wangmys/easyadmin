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
 * @ControllerAnnotation(title="单店单款超量提醒")
 */
class Chaoliang extends BaseController
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

    // 更新周销 断码率专用 初步加工康雷表 groub by合并插入自己的retail表里
    public function retail_first() {
        $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
        $seasion = $this->seasionHandle($select_config['季节归集']); 
        // 不考核门店
        $noCustomer = xmSelectInput($select_config['不考核门店']);

        $sql = "   
            SELECT TOP
                300000 EC.CustomItem17 AS 商品负责人,
        -- 		ER.RetailID,
        -- 		ER.RetailDate,
                EC.State AS 省份,
                EBC.Mathod AS 渠道属性,
                EC.CustomItem15 AS 店铺云仓,
                ER.CustomerName AS 店铺名称,
            --  DATEPART( yy, ER.RetailDate ) AS 年份,
            --  DATEPART( yy, GETDATE() ) AS 年份,
                EG.TimeCategoryName1 as 年份,
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
                EG.TimeCategoryName2 AS 二级时间分类,
                EG.CategoryName1 AS 大类,
                EG.CategoryName2 AS 中类,
                EG.CategoryName AS 小类,
                SUBSTRING ( EG.CategoryName, 1, 2 ) AS 领型,
                EG.StyleCategoryName AS 风格,
                EG.GoodsNo  AS 商品代码,
    --          ERG.UnitPrice AS 零售价,
    -- 								CASE
    -- 									WHEN SUM (ERG.Quantity)>0 THEN SUM ( ERG.Quantity * ERG.DiscountPrice ) / SUM (ERG.Quantity)
    -- 									ELSE 0
    -- 								END  AS 当前零售价,
                SUM ( ERG.Quantity * ERG.DiscountPrice ) / SUM (ERG.Quantity) AS 当前零售价,
                SUM ( ERG.Quantity ) AS 销售数量,
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
                AND EG.TimeCategoryName2 IN ( {$seasion} )
                AND EG.CategoryName1 NOT IN ('配饰', '人事物料')
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023')
                AND ER.CustomerName NOT IN ( {$noCustomer} )
        --      AND ERG.Quantity  > 0
        --      AND ERG.DiscountPrice > 0
        -- 		AND ER.CustomerName = '舒城一店'
        -- 		AND EG.GoodsNo= 'B42513005'
            GROUP BY
                EC.CustomItem17
        -- 		,ER.RetailID
        -- 		,ER.RetailDate
                ,ER.CustomerName
                ,EG.GoodsNo
                ,EC.State
                ,EC.CustomItem15
                ,EBC.Mathod
                ,EG.TimeCategoryName1
                ,EG.TimeCategoryName2
                ,EG.CategoryName1
                ,EG.CategoryName2
                ,EG.CategoryName
                ,EG.StyleCategoryName
        --      ,ERG.UnitPrice
            HAVING  SUM ( ERG.Quantity ) <> 0
        ";

        
        $select = $this->db_sqlsrv->query($sql);
        $count = count($select);
        // if (! cache('duanmalv_retail_data')) {
        //     $select = $this->db_sqlsrv->query($sql);
        //     cache('duanmalv_retail_data', $select, 3600);
        // } else {
        //     $select = cache('duanmalv_retail_data');
        // }
        // echo count($select);die;
        // dump($select);die;
        if ($select) {
            // 删除
            // $this->db_easyA->table('cwl_duanmalv_retail')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_retail')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_duanmalv_retail first 更新成功，数量：{$count}！"
            ]);
        }
    }

    // 更新周销 进行排名  折率0.9
    public function retail_second() {
        // 更新零售价
        $sql0 = "update cwl_duanmalv_retail as r left join sjp_goods as g on r.`商品代码` = g.货号 
        set r.零售价 = g.零售价
        where r.零售价 is null";
        $this->db_easyA->execute($sql0);

        // 计算折率
        $sql1 = "
            UPDATE cwl_duanmalv_retail 
            SET 折率= ROUND(`当前零售价` / 零售价, 4)
            WHERE 
            `折率` IS NULL
        ";
        $this->db_easyA->execute($sql1);

        // 获取配置项的折率
        $find_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
        
        // 分组排名
        $select = $this->db_easyA->query("
            SELECT
                a.商品负责人,
                a.省份,
                a.渠道属性,
                a.店铺云仓,
                a.店铺名称,
                a.年份,
                a.季节归集,
                a.二级时间分类,
                a.大类,
                -- a.中类,
                a.小类,
                -- a.风格,
                a.商品代码,
                a.零售价,
                a.当前零售价,
                a.销售数量,
                a.销售金额,
                a.折率,
                a.更新日期,
                CASE
                    WHEN 
                        a.中类 = @中类 and 
                        a.风格 = @风格 and 
                        a.领型 = @领型 
                    THEN
                        @rank := @rank + 1 ELSE @rank := 1
                END AS 排名,
                @中类 := a.中类 AS 中类,
                @风格 := a.风格 AS 风格,
                @领型 := a.领型 AS 领型
                FROM
                    cwl_duanmalv_retail a,
                    ( SELECT @中类 := null,  @风格 := null,  @领型 := null, @rank := 0 ) T
                WHERE
                    折率 >= {$find_config['折率']}
                --  折率 >= 0.9    
                -- 	AND 中类='休闲长裤'
                -- 	AND 店铺名称 = '三江一店'
                ORDER BY
                    a.店铺名称 ASC,a.中类 ASC, a.风格 ASC,a.领型 ASC,a.销售数量 DESC
        "); 

        // echo '<pre>';
        // print_r($select); die;

        if ($select) {
            // $this->db_easyA->startTrans();

            // 删除 需要计算排名的
            $this->db_easyA->table('cwl_duanmalv_retail')->where([
                // ['折率', '>=', 0.9]
                ['折率', '>=', $find_config['折率']]
            ])->delete();

            $chunk_list = array_chunk($select, 500);
            
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_retail')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_retail second 更新成功！'
            ]);
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_retail 排名执行失败！'
            ]);
        }
    }

    public function test() {
        $this->db_easyA->name('cwl_chaoliang_test')->insert([
            '更新时间' => date('Y-m-d H:i:s', time())
        ]);
    }

    public function sk_first()
    {
        // $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
        // $seasion = $this->seasionHandle($select_config['季节归集']); 
        // // 不考核门店
        // $noCustomer = xmSelectInput($select_config['不考核门店']);
        $sql = "
            SELECT
                t.*,
                truncate(t.`预计00/28/37/44/100/160/S` / t.`平均每日销量00/28/37/44/100/160/S`, 1) as `周转00/28/37/44/100/160/S`,
                truncate(t.`预计29/38/46/105/165/M` / t.`平均每日销量29/38/46/105/165/M`, 1) as `周转29/38/46/105/165/M`,
                truncate(t.`预计30/39/48/110/170/L` / t.`平均每日销量30/39/48/110/170/L`, 1) as `周转30/39/48/110/170/L`,
                truncate(t.`预计31/40/50/115/175/XL` / t.`平均每日销量31/40/50/115/175/XL`, 1) as `周转31/40/50/115/175/XL`,
                truncate(t.`预计32/41/52/120/180/2XL` / t.`平均每日销量32/41/52/120/180/2XL`, 1) as `周转32/41/52/120/180/2XL`,
                truncate(t.`预计33/42/54/125/185/3XL` / t.`平均每日销量33/42/54/125/185/3XL`, 1) as `周转33/42/54/125/185/3XL`,
                truncate(t.`预计34/43/56/190/4XL` / t.`平均每日销量34/43/56/190/4XL`, 1) as `周转34/43/56/190/4XL`,
                truncate(t.`预计35/44/58/195/5XL` / t.`平均每日销量35/44/58/195/5XL`, 1) as `周转35/44/58/195/5XL`,
                truncate(t.`预计36/6XL` / t.`平均每日销量36/6XL`, 1) as `周转36/6XL`,
                truncate(t.`预计38/7XL` / t.`平均每日销量38/7XL`, 1) as `周转38/7XL`,
                truncate(t.`预计_40` / t.`平均每日销量_40`, 1) as `周转_40`
            FROM
                (
                SELECT
                    sk.云仓,
                    sk.店铺名称,
                    c.CustomerGrade AS 店铺等级,
                    sk.商品负责人,
                    sk.省份,
                    sk.经营模式,
                    sk.年份,
                    sk.季节,
                    sk.一级分类,
                    sk.二级分类,
                    sk.分类,
                    sk.风格,
                    SUBSTRING( sk.分类, 1, 2 ) AS 领型,
                    sk.货号,
                    lz.上市天数,
                    sk.`预计00/28/37/44/100/160/S`,
                    sk.`预计29/38/46/105/165/M`,
                    sk.`预计30/39/48/110/170/L`,
                    sk.`预计31/40/50/115/175/XL`,
                    sk.`预计32/41/52/120/180/2XL`,
                    sk.`预计33/42/54/125/185/3XL`,
                    sk.`预计34/43/56/190/4XL`,
                    sk.`预计35/44/58/195/5XL`,
                    sk.`预计36/6XL`,
                    sk.`预计38/7XL`,
                    sk.`预计_40`,
                    sk.`预计库存数量` AS 预计库存合计,
                    lz.`00/28/37/44/100/160/S` AS `两周销00/28/37/44/100/160/S`,
                    lz.`29/38/46/105/165/M` AS `两周销29/38/46/105/165/M`,
                    lz.`30/39/48/110/170/L` AS `两周销30/39/48/110/170/L`,
                    lz.`31/40/50/115/175/XL` AS `两周销31/40/50/115/175/XL`,
                    lz.`32/41/52/120/180/2XL` AS `两周销32/41/52/120/180/2XL`,
                    lz.`33/42/54/125/185/3XL` AS `两周销33/42/54/125/185/3XL`,
                    lz.`34/43/56/190/4XL` AS `两周销34/43/56/190/4XL`,
                    lz.`35/44/58/195/5XL` AS `两周销35/44/58/195/5XL`,
                    lz.`36/6XL` AS `两周销36/6XL`,
                    lz.`38/7XL` AS `两周销38/7XL`,
                    lz.`_40` AS `两周销_40`,
                    lz.`合计` AS 两周销合计,
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`00/28/37/44/100/160/S` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`00/28/37/44/100/160/S` / lz.上市天数 * 7 
                    END AS `平均每日销量00/28/37/44/100/160/S`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`29/38/46/105/165/M` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`29/38/46/105/165/M` / lz.上市天数 * 7 
                    END AS `平均每日销量29/38/46/105/165/M`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`30/39/48/110/170/L` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`30/39/48/110/170/L` / lz.上市天数 * 7 
                    END AS `平均每日销量30/39/48/110/170/L`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`31/40/50/115/175/XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`31/40/50/115/175/XL` / lz.上市天数 * 7 
                    END AS `平均每日销量31/40/50/115/175/XL`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`32/41/52/120/180/2XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`32/41/52/120/180/2XL` / lz.上市天数 * 7 
                    END AS `平均每日销量32/41/52/120/180/2XL`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`33/42/54/125/185/3XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`33/42/54/125/185/3XL` / lz.上市天数 * 7 
                    END AS `平均每日销量33/42/54/125/185/3XL`, 
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`34/43/56/190/4XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`34/43/56/190/4XL` / lz.上市天数 * 7 
                    END AS `平均每日销量34/43/56/190/4XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`35/44/58/195/5XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`35/44/58/195/5XL` / lz.上市天数 * 7 
                    END AS `平均每日销量35/44/58/195/5XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`36/6XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`36/6XL` / lz.上市天数 * 7 
                    END AS `平均每日销量36/6XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`38/7XL` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`38/7XL` / lz.上市天数 * 7 
                    END AS `平均每日销量38/7XL`, 	
                    CASE
                        WHEN lz.上市天数 >= 14 THEN lz.`_40` / 14 * 7 
                        WHEN lz.上市天数 < 14 THEN lz.`_40` / lz.上市天数 * 7 
                    END AS `平均每日销量_40`															
                FROM
                    `sp_sk` AS sk
                    RIGHT JOIN customer AS c ON sk.店铺名称 = c.CustomerName
                    LEFT JOIN sjp_liangzhou AS lz ON sk.云仓 = lz.云仓 
                    AND sk.店铺名称 = lz.店铺名称 
                    AND sk.货号 = lz.货号 
                WHERE
                    sk.季节 IN ( '初夏', '盛夏', '夏季' ) 
                    AND c.Region <> '闭店区' 
                    AND sk.店铺名称 NOT IN ( '' ) --    AND sk.店铺名称 IN ('三江一店', '安化二店', '南宁二店')
                    
                    AND sk.年份 = 2023 
                    AND sk.一级分类 = '内搭' 
                GROUP BY
                    sk.店铺名称,
                    sk.季节,
                    sk.货号 
            -- 	LIMIT 100 
                ) AS t
        ";
		
        $select_sk = $this->db_bi->query($sql);
        $count = count($select_sk);

        if ($select_sk) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_chaoliang_sk;');
            $chunk_list = array_chunk($select_sk, 500);
            // $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_chaoliang_sk')->strict(false)->insertAll($val);
                if (! $insert) {
                    $status = false;
                    break;
                }
            }

            if ($status) {
                // $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_chaoliang_sk first 更新成功，数量：{$count}！"
                ]);
            } else {
                // $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_chaoliang_sk first 更新失败！'
                ]);
            }
        }
    }

    // 更新上市天数 
    public function sk_second() {
        // 上市天数更新
        // $sql = "
        //     UPDATE 
        //         cwl_chaoliang_sk AS sk
        //     INNER JOIN
        //         sp_ww_budongxiao_detail AS bu 
        //     ON 
        //         sk.省份 = bu.`省份` 
        //         AND sk.`店铺名称` = bu.`店铺名称` 
        //         AND sk.`一级分类` = bu.`大类` 
        //         AND sk.`二级分类` = bu.`中类` 
        //         AND sk.`货号` = bu.`货号` 
        //     SET 
        //         sk.上市天数 = bu.上市天数
        //     WHERE 
        //     sk.上市天数 is null
        // ";

        // // $this->db_easyA->startTrans();
        // $status = $this->db_easyA->execute($sql);
        // if ($status) {
        //     // $this->db_easyA->commit();
        //     return json([
        //         'status' => 1,
        //         'msg' => 'success',
        //         'content' => "cwl_chaoliang_sk 上市天数 更新成功，数量：{$status}！"
        //     ]);
        // } else {
        //     // $this->db_easyA->rollback();
        //     return json([
        //         'status' => 0,
        //         'msg' => 'error',
        //         'content' => 'cwl_chaoliang_sk 上市天数 当前零售价 更新失败！'
        //     ]);
        // }
    }

    // 断码 无效库存判定
    public function sk_third() {
        $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
        // $sql1 = "set @内搭 = 4, @外套=4, @鞋履=4, @松紧长裤=5, @松紧短裤=5, @下装=6;";
        $sql1 = "set @内搭 = {$select_config['内搭']}, @外套={$select_config['外套']}, @鞋履={$select_config['鞋履']}, @松紧长裤={$select_config['松紧长裤']}, @松紧短裤={$select_config['松紧短裤']}, @下装={$select_config['下装']};";
        
        $this->db_easyA->execute($sql1);

        $sql2 = "
            UPDATE cwl_duanmalv_sk 
            SET 标准齐码识别修订 = 
                CASE 
                    WHEN (`累销数量`<= 0 || `累销数量` IS NULL) && (预计库存数量 <= 1 || 预计库存数量 IS NULL) THEN '无效库存'
                    -- WHEN (`一级分类`='下装' && 店铺SKC计数=1 && `预计库存连码个数` < @下装) THEN '断码'
                    WHEN (`二级分类`='松紧长裤' && 店铺SKC计数=1 && `预计库存连码个数` < @松紧长裤) THEN '断码' 
                    WHEN (`二级分类`='松紧短裤' && 店铺SKC计数=1 && `预计库存连码个数` < @松紧短裤) THEN '断码' 
                    WHEN (`一级分类`='下装' && `二级分类`!='松紧短裤' &&  `二级分类`!='松紧长裤' && 店铺SKC计数=1 && `预计库存连码个数` < @下装) THEN '断码' 
                    WHEN (`一级分类`='鞋履' && 店铺SKC计数=1 && `预计库存连码个数` < @鞋履) THEN '断码' 
                    WHEN (`一级分类`='外套' && 店铺SKC计数=1 && `预计库存连码个数` < @外套) THEN '断码' 
                    WHEN (`一级分类`='内搭' && 店铺SKC计数=1 && `预计库存连码个数` < @内搭) THEN '断码'
                    ELSE ''
                END	
            WHERE 
            `标准齐码识别修订` IS NULL";
        // $this->db_easyA->startTrans();
        $status = $this->db_easyA->execute($sql2);
        if ($status) {
            // $this->db_easyA->commit();
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_duanmalv_sk 标准齐码识别修订 更新成功，数量：{$status}！"
            ]);
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_sk 标准齐码识别修订 更新失败！'
            ]);
        }
    }

    // 更新在途表
    public function zt_1() {
        $date = date('Y-m-d');
        $sql = "
            SELECT
                zt.云仓,
                zt.合计,
                zt.货号,
                sk.一级分类, 
                sk.二级分类,
                zt.年份,
                zt.季节, 
                zt.`00/28/37/44/100/160/S`,
                zt.`29/38/46/105/165/M`,
                zt.`30/39/48/110/170/L`,
                zt.`31/40/50/115/175/XL`,
                zt.`32/41/52/120/180/2XL`,
                zt.`33/42/54/125/185/3XL`,
                zt.`34/43/56/190/4XL`,
                zt.`35/44/58/195/5XL`,
                zt.`36/6XL`,
                zt.`38/7XL`,
                zt.`_40`,
                CASE
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAAAAAAAAA%' THEN 11 
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAAAAAAAA%' THEN 10 
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAAAAAAA%' THEN 9 
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAAAAAA%' THEN 8 
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAAAAA%' THEN 7 
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAAAA%' THEN 6	
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAAA%' THEN 5	
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAAA%' THEN 4	
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AAA%' THEN 3	
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%AA%' THEN 2		
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%A%' THEN 1		
                    WHEN CONCAT(
                                    CASE WHEN SUM(zt.`00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`36/6XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`38/7XL`) >0 THEN 'A' ELSE 'B' END,
                                    CASE WHEN SUM(zt.`_40`) >0 THEN 'A' ELSE 'B' END
                            ) LIKE '%BBBBBBBBBBB%' THEN 0
                END AS 在途连码个数,
                '{$date}' AS 更新日期
            FROM
                `sp_ww_zt` AS zt
                
                left JOIN (select 货号,一级分类, 二级分类 from sjp_goods where 一级分类 is not null and 二级分类 is not null GROUP BY 货号 ) AS sk ON zt.货号 = sk.货号
                group by zt.货号,zt.云仓        
        ";
                        
        $select = $this->db_bi->query($sql);
        // echo '<pre>';
        // print_r($select);
        if ($select) {
            $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
            $qimaArr = [
                '内搭' => "{$select_config['内搭']}",
                '外套' => "{$select_config['外套']}",
                '鞋履' => "{$select_config['鞋履']}",
                '松紧长裤' => "{$select_config['松紧长裤']}",
                '松紧短裤' => "{$select_config['松紧短裤']}",
                '下装' => "{$select_config['下装']}",
            ];
            foreach ($select as $key => $val) {
                if ($val['二级分类'] == '松紧长裤' || $val['二级分类'] == '松紧短裤') {

                    $select[$key]['连码要求个数'] = $qimaArr[$val['二级分类']];
                } else {
                    $select[$key]['连码要求个数'] = $qimaArr[$val['一级分类']];
                }
                
            }
            // echo '<pre>';
            // print_r($select);
            // die;
            // 删除
            $this->db_easyA->table('cwl_duanmalv_zt')->where(1)->delete();
            // 插入
            $status = $this->db_easyA->table('cwl_duanmalv_zt')->insertAll($select);
            if ($status) {
                // $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => 'cwl_duanmalv_zt 在途 更新成功！'
                ]);
            } else {
                // $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_zt 在途 更新失败！'
                ]);
            }
        }
    }

}
