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
 * @ControllerAnnotation(title="断码率_春")
 * 改成 top50 了
 */
class DuanmalvSpring extends BaseController
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

    // top50
    private $top = 50;
    private $config = [];

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_binew = Db::connect('bi_new');
        $this->db_sqlsrv = Db::connect('sqlsrv');

        // 冬
        $config = $this->db_easyA->table('cwl_duanmalv_config')->where(['status' => 1, 'id' => 3])->find();
        $this->config = $config;
        $this->top = $config['top'];
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

    // 自动更新
    public function autoUpdate() {
        $this->zt_1();
        $this->retail_first();
        $this->retail_first();
        $this->retail_second();

        $this->sk_first();
        $this->sk_second();
        $this->sk_third();

        $this->handle_1_new(); 
        $this->handle_2(); 
        $this->handle_3();

        $this->table6();
        $this->table4();
        $this->table1();
        $this->table1_2();
        $this->table1_3();

        $log_data = $this->config;
        $log_data['更新时间'] = date('Y-m-d H:i:s');
        $log_data['更新日期'] = date('Y-m-d');
        $log_data['cid'] = $this->config['id'];
        $this->db_easyA->table('cwl_duanmalv_config_log')->where(['更新日期' => $log_data['更新日期'], 'cid' => $this->config['id']])->delete();
        $this->db_easyA->table('cwl_duanmalv_config_log')->strict(false)->insert(
            $log_data
        );
    }

    // 更新周销 断码率专用 初步加工康雷表 groub by合并插入自己的retail表里
    public function retail_first() {
        $select_config = $this->config;
        $seasion = $this->seasionHandle($select_config['季节归集']); 
        // $seasion = explode(',', $select_config['季节归集']);

        // 不考核门店
        $noCustomer = xmSelectInput($select_config['不考核门店']);
        // 不考核货号
        $noGoodsNo = xmSelectInput($select_config['不考核货号']);

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
                AND EG.TimeCategoryName1 IN ('{$select_config['年份']}')
                AND ER.CustomerName NOT IN ( {$noCustomer} )
                AND EG.GoodsNo NOT IN ( {$noGoodsNo} )
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
            $this->db_easyA->execute('TRUNCATE cwl_duanmalv_retail_spring;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_retail_spring')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_duanmalv_retail_spring first 更新成功，数量：{$count}！"
            ]);
        }
    }

    // 更新周销 进行排名  折率0.9
    public function retail_second() {
        // 更新零售价
        $sql0 = "update cwl_duanmalv_retail_spring as r left join sjp_goods as g on r.`商品代码` = g.货号 
        set r.零售价 = g.零售价
        where r.零售价 is null";
        $this->db_easyA->execute($sql0);

        // 计算折率
        $sql1 = "
            UPDATE cwl_duanmalv_retail_spring 
            SET 折率= ROUND(`当前零售价` / 零售价, 4)
            WHERE 
            `折率` IS NULL
        ";
        $this->db_easyA->execute($sql1);

        // 获取配置项的折率
        $find_config = $this->config;
        
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
                    cwl_duanmalv_retail_spring a,
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
            $this->db_easyA->table('cwl_duanmalv_retail_spring')->where([
                // ['折率', '>=', 0.9]
                ['折率', '>=', $find_config['折率']]
            ])->delete();

            $chunk_list = array_chunk($select, 500);
            
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_retail_spring')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_retail_spring second 更新成功！'
            ]);
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_retail_spring 排名执行失败！'
            ]);
        }
    }

    public function sk_first()
    {
        $select_config = $this->config;
        $seasion = $this->seasionHandle($select_config['季节归集']); 
        // 不考核门店
        $noCustomer = xmSelectInput($select_config['不考核门店']);
        $noGoodsNo = xmSelectInput($select_config['不考核货号']);
        $sql = "
            SELECT 
                sk.云仓,
                sk.店铺名称,
                c.CustomerGrade as 店铺等级,
                sk.商品负责人,
                sk.省份,
                sk.经营模式,
                sk.年份,
                sk.季节, 
                sk.一级分类,
                sk.二级分类,
                sk.分类,
                sk.风格,
                SUBSTRING(sk.分类, 1, 2) as 领型,
                sk.货号,
                sk.`总入量00/28/37/44/100/160/S`,
                sk.`总入量29/38/46/105/165/M`,
                sk.`总入量30/39/48/110/170/L`,
                sk.`总入量31/40/50/115/175/XL`,
                sk.`总入量32/41/52/120/180/2XL`,
                sk.`总入量33/42/54/125/185/3XL`,
                sk.`总入量34/43/56/190/4XL`,
                sk.`总入量35/44/58/195/5XL`,
                sk.`总入量36/6XL`,
                sk.`总入量38/7XL`,
                sk.`总入量_40`,
                sk.`总入量数量`,
                sk.`累销00/28/37/44/100/160/S`,
                sk.`累销29/38/46/105/165/M`,
                sk.`累销30/39/48/110/170/L`,
                sk.`累销31/40/50/115/175/XL`,
                sk.`累销32/41/52/120/180/2XL`,
                sk.`累销33/42/54/125/185/3XL`,
                sk.`累销34/43/56/190/4XL`,
                sk.`累销35/44/58/195/5XL`,
                sk.`累销36/6XL`,
                sk.`累销38/7XL`,
                sk.`累销_40`,
                sk.`累销数量`,
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
                sk.`预计库存数量`,
                CASE
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAAAAA%' THEN 11 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAAAA%' THEN 10 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAAA%' THEN 9 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAAA%' THEN 8 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAAA%' THEN 7 
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAAA%' THEN 6	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAAA%' THEN 5	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAAA%' THEN 4	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AAA%' THEN 3	
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%AA%' THEN 2		
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%A%' THEN 1		
                    WHEN CONCAT(
                            CASE WHEN SUM(sk.`预计00/28/37/44/100/160/S`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计29/38/46/105/165/M`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计30/39/48/110/170/L`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计31/40/50/115/175/XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计32/41/52/120/180/2XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计33/42/54/125/185/3XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计34/43/56/190/4XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计35/44/58/195/5XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计36/6XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计38/7XL`) >0 THEN 'A' ELSE 'B' END,
                            CASE WHEN SUM(sk.`预计_40`) >0 THEN 'A' ELSE 'B' END
                        ) LIKE '%BBBBBBBBBBB%' THEN 0
                END AS 预计库存连码个数,
                CASE
                    WHEN sk.`预计库存数量` > 1
                    THEN 1
                    ELSE 0	
                END AS 店铺SKC计数

                FROM `sp_sk` as sk
                LEFT JOIN customer as c ON sk.店铺名称=c.CustomerName

                WHERE
                    sk.季节 IN ({$seasion}) 
                    AND c.Region <> '闭店区'
                    AND sk.店铺名称 NOT IN ({$noCustomer})
                    AND sk.货号 NOT IN ({$noGoodsNo})
                --    AND sk.店铺名称 IN ('三江一店', '安化二店', '南宁二店')
                -- 	AND sk.年份 = 2023
                -- 	AND sk.省份='广东省'
                -- 	AND sk.货号='B32101027'
                GROUP BY 
                    sk.店铺名称, 
                    sk.季节, 
                    sk.货号
                -- limit 100    
        ";

        // die;
		
        $select_sk = $this->db_easyA->query($sql);
        $count = count($select_sk);

        if ($select_sk) {
            // 删除历史数据
            // $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_duanmalv_sk_spring;');
            $chunk_list = array_chunk($select_sk, 500);
            // $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_sk_spring')->strict(false)->insertAll($val);
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
                    'content' => "cwl_duanmalv_sk_spring first 更新成功，数量：{$count}！"
                ]);
            } else {
                // $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_sk_spring first 更新失败！'
                ]);
            }
        }
    }

    // 更新店铺排名 零售价 当前零售价  (可能不需要)
    public function sk_second() {
        // 有排名才更新，折率1以下
        $sql = "
            UPDATE 
                cwl_duanmalv_sk_spring AS sk
            INNER JOIN
                cwl_duanmalv_retail_spring AS dr 
            ON 
                sk.货号 = dr.`商品代码` AND sk.`店铺名称` = dr.`店铺名称` 
            SET 
                sk.店铺近一周排名 = dr.排名,
                sk.零售价 = dr.零售价,
                sk.当前零售价 = dr.当前零售价, 
                sk.折率 = dr.折率,
                sk.销售金额 = dr.`销售金额`
            WHERE 
               -- dr.排名 IS NOT NULL
                dr.销售金额 > 0
        ";

        // $this->db_easyA->startTrans();
        $status = $this->db_easyA->execute($sql);
        if ($status) {
            // $this->db_easyA->commit();
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_duanmalv_sk_spring 店铺排名 零售价 当前零售价 更新成功，数量：{$status}！"
            ]);
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_sk_spring 店铺排名 零售价 当前零售价 更新失败！'
            ]);
        }
    }

    // 断码 无效库存判定
    public function sk_third() {
        $select_config = $this->config;
        // $sql1 = "set @内搭 = 4, @外套=4, @鞋履=4, @松紧长裤=5, @松紧短裤=5, @下装=6;";
        $sql1 = "set @内搭 = {$select_config['内搭']}, @外套={$select_config['外套']}, @鞋履={$select_config['鞋履']}, @松紧长裤={$select_config['松紧长裤']}, @松紧短裤={$select_config['松紧短裤']}, @下装={$select_config['下装']};";
        
        $this->db_easyA->execute($sql1);

        $sql2 = "
            UPDATE cwl_duanmalv_sk_spring 
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
                'content' => "cwl_duanmalv_sk_spring 标准齐码识别修订 更新成功，数量：{$status}！"
            ]);
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_sk_spring 标准齐码识别修订 更新失败！'
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
                
                left JOIN (select 货号,一级分类, 二级分类 from sjp_goods where 一级分类 is not null and 二级分类 is not null GROUP BY 货号,一级分类, 二级分类 ) AS sk ON zt.货号 = sk.货号
                group by 
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
                    zt.`_40`
        ";
                        
        $select = $this->db_bi->query($sql);
        // echo '<pre>';
        // print_r($select);
        if ($select) {
            $select_config = $this->config;
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

    public function handle_1_new() {
        $sql1_old = "
             SELECT 
                m1.*,
                m1.SKC数 / m1.店铺总SKC数 AS SKC占比,
                round(m1.销售金额 / m1.店铺总销售金额, 4) AS 销售占比,
                round(m1.SKC数 / m1.店铺总SKC数 * {$this->top}, 4) AS SKC数TOP分配,
                round(m1.销售金额 / m1.店铺总销售金额 * {$this->top}, 4) AS 销售TOP分配,
                round((m1.SKC数 / m1.店铺总SKC数 * {$this->top} + m1.销售金额 / m1.店铺总销售金额 * {$this->top}) / 4, 0) AS 实际分配TOP
            FROM
                (SELECT sk.经营模式,
                    sk.商品负责人,
                    sk.云仓,
                    sk.省份,
                    sk.店铺名称,
                    sk.风格,
                    sk.一级分类,
                    sk.二级分类,
                    sk.领型,
                    sum(case 
                        sk.`标准齐码识别修订`
                        when '断码' then 1 else 0
                    end) as 总断码数, 	
                    ( SELECT count(店铺SKC计数 ) FROM cwl_duanmalv_sk_spring WHERE 店铺名称 = sk.店铺名称 AND 风格 = sk.风格 AND sk.一级分类=一级分类 AND sk.二级分类=二级分类 AND
                    sk.领型=领型 and 店铺SKC计数=1) AS SKC数,
                    ( SELECT sum(店铺SKC计数 ) FROM cwl_duanmalv_sk_spring WHERE 店铺名称 = sk.店铺名称 AND 风格 = sk.风格 ) AS 店铺总SKC数,
                    sum(dr.销售金额) AS 销售金额,
                    (select sum(IFNULL(销售金额, 0)) from cwl_duanmalv_retail_spring where 店铺名称=sk.店铺名称 AND 风格=sk.风格) AS 店铺总销售金额
                    from cwl_duanmalv_sk_spring as sk
                    LEFT JOIN cwl_duanmalv_retail_spring as dr ON sk.货号 = dr.`商品代码` AND sk.`店铺名称` = dr.`店铺名称` 
                    where 
                    sk.风格 IN ('基本款', '引流款')
                    GROUP BY sk.店铺名称, sk.风格, sk.一级分类, sk.二级分类, sk.领型	
                    order by sk.`经营模式`, sk.云仓, sk.省份, sk.店铺名称, sk.风格, sk.`一级分类`, sk.`二级分类`, sk.领型) AS m1
        ";

        // top60 top50 销售top分配 skctop分配
        $sql2 = "
        SELECT 
            m1.*,
            case
                m1.风格
                when '基本款' then m1.SKC数 / m1.店铺SKC数_基本款
                when '引流款' then m1.SKC数 / m1.店铺SKC数_引流款
            end as SKC占比,
            case
                m1.风格
                when '基本款' then m1.销售金额 / m1.店铺总销售金额_基本款
                when '引流款' then m1.销售金额 / m1.店铺总销售金额_引流款
            end as 销售占比,
            case
                m1.风格
                when '基本款' then round(m1.SKC数 / m1.店铺SKC数_基本款 * {$this->top}, 4)
                when '引流款' then round(m1.SKC数 / m1.店铺SKC数_引流款 * {$this->top}, 4)
            end as SKC数TOP分配,
            case
                m1.风格
                when '基本款' then round(m1.销售金额 / m1.店铺总销售金额_基本款 * {$this->top}, 4)
                when '引流款' then round(m1.销售金额 / m1.店铺总销售金额_引流款 * {$this->top}, 4)
            end as 销售TOP分配,
            case
                m1.风格
                when '基本款' then round((m1.SKC数 / m1.店铺SKC数_基本款 * {$this->top} + m1.销售金额 / m1.店铺总销售金额_基本款 * {$this->top}) / 2, 0)
                when '引流款' then round((m1.SKC数 / m1.店铺SKC数_引流款 * {$this->top} + m1.销售金额 / m1.店铺总销售金额_引流款 * {$this->top}) / 2, 0)
            end as 实际分配TOP
        FROM
            (SELECT sk.经营模式,
                sk.商品负责人,
                sk.云仓,
                sk.省份,
                sk.店铺名称,
                sk.风格,
                sk.一级分类,
                sk.二级分类,
                sk.领型,
                sum(case 
                    sk.`标准齐码识别修订`
                    when '断码' then 1 else 0
                end) as 总断码数, 	
                ( SELECT count(店铺SKC计数 ) FROM cwl_duanmalv_sk_spring WHERE 店铺名称 = sk.店铺名称 AND 风格 = sk.风格 AND sk.一级分类=一级分类 AND sk.二级分类=二级分类 AND
                sk.领型=领型 and 店铺SKC计数=1) AS SKC数,
                ( SELECT sum(店铺SKC计数 ) FROM cwl_duanmalv_sk_spring WHERE 店铺名称 = sk.店铺名称 ) AS 店铺总SKC数,
                ( SELECT sum(店铺SKC计数 ) FROM cwl_duanmalv_sk_spring WHERE 店铺名称 = sk.店铺名称 AND 风格 = '基本款' ) AS 店铺SKC数_基本款,
                ( SELECT sum(店铺SKC计数 ) FROM cwl_duanmalv_sk_spring WHERE 店铺名称 = sk.店铺名称 AND 风格 = '引流款' ) AS 店铺SKC数_引流款,
                sum(dr.销售金额) AS 销售金额,
                (select sum(IFNULL(销售金额, 0)) from cwl_duanmalv_retail_spring where 店铺名称=sk.店铺名称 AND 风格='基本款') AS 店铺总销售金额_基本款,	
                (select sum(IFNULL(销售金额, 0)) from cwl_duanmalv_retail_spring where 店铺名称=sk.店铺名称 AND 风格='引流款') AS 店铺总销售金额_引流款,
                (select sum(IFNULL(销售金额, 0)) from cwl_duanmalv_retail_spring where 店铺名称=sk.店铺名称 AND 风格 in ('基本款', '引流款')) AS 店铺总销售金额
                from cwl_duanmalv_sk_spring as sk
                LEFT JOIN cwl_duanmalv_retail_spring as dr ON sk.货号 = dr.`商品代码` AND sk.`店铺名称` = dr.`店铺名称` 
            where 1
                AND sk.风格 IN ('基本款', '引流款')
                GROUP BY sk.店铺名称, sk.风格, sk.一级分类, sk.二级分类, sk.领型	
                order by sk.`经营模式`, sk.云仓, sk.省份, sk.店铺名称, sk.风格, sk.`一级分类`, sk.`二级分类`, sk.领型) AS m1
        ";
        $select = $this->db_easyA->query($sql2);
        if ($select) {
            // 删除 需要计算排名的
            // $this->db_easyA->table('cwl_duanmalv_handle_1')->where(1)->delete();
            $this->db_easyA->execute('TRUNCATE cwl_duanmalv_handle_1_spring;');

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $status = $this->db_easyA->table('cwl_duanmalv_handle_1_spring')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_handle_1_spring 更新成功！'
            ]);
        }
    }

    // 计算是否top 60考核款
    public function handle_2() {
        // $this->db_easyA->commit();die;
        // 1.先判断有排名 & 实际分配TOP 再判断在途情况
        $sql = "
            update cwl_duanmalv_handle_1_spring h
                LEFT JOIN cwl_duanmalv_sk_spring sk ON h.店铺名称 = sk.店铺名称 
                AND h.`二级分类` = sk.`二级分类` 
                AND h.风格=sk.风格
                AND h.领型 = sk.领型
                SET sk.`是否TOP60` = '是'
            WHERE	
                h.`店铺名称`=sk.`店铺名称` 
                AND h.风格=sk.风格
                and h.`一级分类`=sk.`一级分类`
                and h.二级分类=sk.`二级分类` 
                and h.领型=sk.领型
                and sk.`店铺近一周排名` > 0
                and sk.`店铺近一周排名` <= h.`实际分配TOP`
                and sk.标准齐码识别修订 = '断码'
        ";
        $status = $this->db_easyA->execute($sql);

        // 2.没有在途，是top60考核款
        $sql2 = "
            update cwl_duanmalv_sk_spring sk
                LEFT JOIN cwl_duanmalv_zt zt ON sk.货号 = zt.`货号` and sk.云仓 = zt.云仓
                set sk.是否TOP60考核款='是'
            WHERE
                sk.`是否TOP60` = '是'
                AND sk.是否TOP60考核款 IS NULL
                AND zt.货号 is null
        ";
        $status2 = $this->db_easyA->execute($sql2);

        // 3.有在途，库存<50 && 不连码 是top60考核款	
        $sql3 = "
            update cwl_duanmalv_sk_spring sk
                LEFT JOIN cwl_duanmalv_zt zt ON sk.货号 = zt.`货号` and sk.云仓 = zt.云仓
                set sk.是否TOP60考核款='是'
            WHERE
                sk.`是否TOP60` = '是'
                AND sk.是否TOP60考核款 IS NULL
                AND zt.货号 is not null
                AND zt.合计 < 50
                AND zt.在途连码个数 < zt.连码要求个数	
        ";
        $status3 = $this->db_easyA->execute($sql3);

        // 4.在途不满足的设置为top60考核款
        $sql4 = "
            update cwl_duanmalv_sk_spring sk
                set sk.是否TOP60考核款='否'
            WHERE
                sk.`是否TOP60` = '是'
                AND sk.是否TOP60考核款 IS NULL
        ";
        $status4 = $this->db_easyA->execute($sql4);

        // echo $status;die;
        if ($status) {
            // $this->db_easyA->commit();
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_sk_spring 是否top60考核款 更新成功！'
            ]);
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_sk_spring 是否top60考核款  更新失败！'
            ]);
        }
    }

    // 更新TOP断码数  全部短码数 
    public function handle_3() {
        // 分组查询
        $sql = "
            SELECT sk.店铺名称,
                sk.一级分类,
                sk.二级分类, 
                sk.领型,
                sk.风格, 
                sk.货号,
                sk.是否TOP60考核款,
                sk.是否TOP60,
                SUM(
                    case
                        sk.是否TOP60考核款
                    when '是' THEN 1 ELSE 0
                END 
                ) AS TOP断码SKC数,
                COUNT(1) AS 全部断码SKC数	
                from cwl_duanmalv_sk_spring sk 
            WHERE sk.`是否TOP60`='是'
            GROUP BY sk.`店铺名称`,sk.风格, sk.一级分类,sk.`二级分类`,sk.领型
        ";
        $select = $this->db_easyA->query($sql);
        
        if ($select) {
            $this->db_easyA->table('cwl_duanmalv_sk_group_spring')->where(1)->delete();
            $this->db_easyA->table('cwl_duanmalv_sk_group_spring')->strict(false)->insertAll($select);

            $sql2 = "
                UPDATE cwl_duanmalv_handle_1_spring AS h
                LEFT JOIN cwl_duanmalv_sk_group_spring skg ON h.`店铺名称` = skg.`店铺名称` 
                AND h.风格 = skg.风格 
                AND h.一级分类 = skg.`一级分类` 
                AND h.`二级分类` = skg.`二级分类` 
                AND h.领型 = skg.领型 
                SET 
                    h.TOP断码SKC数 = skg.TOP断码SKC数, 
                    h.全部断码SKC数 = skg.全部断码SKC数 
                WHERE
                    h.TOP断码SKC数 IS NULL
            ";
            $status = $this->db_easyA->execute($sql2);

            $this->db_easyA->table('cwl_duanmalv_config')->where('id=3')->strict(false)->update([
                'sk_updatetime' => date('Y-m-d H:i:s')
            ]);  

            if ($status) {
                // $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => 'cwl_duanmalv_handle_1_spring TOP断码数  全部断码数 更新成功！'
                ]);
            } else {
                // $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_handle_1_spring TOP断码数  全部断码数 更新失败！'
                ]);
            }
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_handle_1_spring TOP断码数  全部断码数 更新失败！'
            ]);
        }
    } 

    // 6.单店品类断码情况（商品专员可看）
    public function table6() {
        $sql = "
            SELECT
                云仓,
                省份,
                商品负责人,
                店铺名称,
                经营模式,
                风格,
                一级分类,
                二级分类,
                领型,
                SKC数 as 领型SKC数,
                总断码数 as 领型断码数,
                1 - 总断码数 / SKC数 as 领型齐码率
            FROM
                cwl_duanmalv_handle_1_spring 
            ORDER BY
                商品负责人 ASC
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $this->db_easyA->table('cwl_duanmalv_table6_spring')->where(1)->delete();
            $chunk_list = array_chunk($select, 500);
            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_duanmalv_table6_spring')->strict(false)->insertAll($val);
            }
            $this->db_easyA->table('cwl_duanmalv_config')->where('id=3')->strict(false)->update([
                'table6_updatetime' => date('Y-m-d H:i:s')
            ]);  
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_table6_spring 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_table6_spring 更新失败！'
            ]);
        }
    }

    // 5. 单店断码情况（商品专员可看）
    public function table5() {
        $sql = "
            select 
                h.云仓,
                h.省份,
                h.商品负责人,
                h.店铺名称,
                c.CustomerGrade AS 店铺等级,
                h.经营模式,
                h.店铺总SKC数,
                SUM( h.TOP断码SKC数 ) AS TOP断码SKC数,
                ROUND( SUM(h.TOP断码SKC数) / h.店铺总SKC数, 4 ) AS TOP断码率,
                SUM(h.全部断码SKC数) AS 全部断码SKC数,
                ROUND( SUM(h.全部断码SKC数) / h.店铺总SKC数, 4 ) AS 全部断码率
            from cwl_duanmalv_handle_1_spring as h
            left join customer as c on h.店铺名称 = c.CustomerName
            GROUP BY h.店铺名称
            ORDER BY h.商品负责人
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $this->db_easyA->table('cwl_duanmalv_table5_spring')->where(1)->delete();
            $chunk_list = array_chunk($select, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_duanmalv_table5_spring')->strict(false)->insertAll($val);
            }
            
            // 全部断码率排名sql
            $sql2 = "
                select 
                    t5.云仓,
                    t5.省份,
                    t5.店铺名称,
                    t5.店铺等级,
                    t5.经营模式,
                    t5.店铺总SKC数,
                    t5.TOP断码SKC数,
                    t5.TOP断码率,
                    t5.全部断码SKC数,
                    t5.全部断码率,
                    case 
                        when t5.商品负责人 = @商品负责人 then @rank := @rank + 1 ELSE @rank := 1
                    end as 全部断码排名,
                    @商品负责人 := t5.商品负责人 AS 商品负责人
                from cwl_duanmalv_table5_spring as t5,
                ( SELECT @商品负责人 := null, @rank := 0 ) T
                order by t5.商品负责人 ASC, t5.`全部断码SKC数` DESC	
            ";
            $select2 = $this->db_easyA->query($sql2);
            $this->db_easyA->table('cwl_duanmalv_table5_spring')->where(1)->delete();
            $chunk_list2 = array_chunk($select2, 500);
            foreach($chunk_list2 as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_duanmalv_table5_spring')->strict(false)->insertAll($val);
            }

            // 全部断码率排名sql
            $sql3 = "
                    select 
                    t5.云仓,
                    t5.省份,
                    t5.店铺名称,
                    t5.店铺等级,
                    t5.经营模式,
                    t5.店铺总SKC数,
                    t5.TOP断码SKC数,
                    t5.TOP断码率,
                    t5.全部断码SKC数,
                    t5.全部断码率,
                    t5.全部断码排名,
                    case 
                        when t5.商品负责人 = @商品负责人  then @rank := @rank + 1 ELSE @rank := 1
                    end as TOP断码排名,
                    @商品负责人 := t5.商品负责人 AS 商品负责人
                from cwl_duanmalv_table5_spring as t5,
                ( SELECT @商品负责人 := null, @rank := 0 ) T
                order by t5.商品负责人 ASC, t5.`TOP断码SKC数` DESC
            ";
            $select3 = $this->db_easyA->query($sql3);
            $this->db_easyA->table('cwl_duanmalv_table5_spring')->where(1)->delete();
            $chunk_list3 = array_chunk($select3, 500);
            foreach($chunk_list3 as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_duanmalv_table5_spring')->strict(false)->insertAll($val);
            }

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_table5_spring 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_table5_spring 更新失败！'
            ]);
        }
    }

    // 4.单省单款断码情况
    public function table4() {
        $sql = "
            SELECT
                sk.风格,
                sk.一级分类 AS 大类,
                sk.二级分类 AS 中类,
                sk.领型,
                sk.货号,
                sk.省份,
                sum(sk.`店铺SKC计数`) as 上柜数,
                sum(
                    case sk.标准齐码识别修订
                        when '断码' then 1 else 0
                    end
                ) as 断码家数,
                sum(
                    case sk.标准齐码识别修订
                        when '断码' then 1 else 0
                    end
                ) / sum(sk.`店铺SKC计数`) AS 断码率,
                (SELECT
                sum(销售数量) as 销售数量
                FROM
                    cwl_duanmalv_retail_spring where `商品代码` = sk.货号
                    AND 省份 = sk.省份
                    group by 省份,商品代码
                ) AS 销售数量,
                sum(sk.`预计库存数量`) as 预计库存数量,
                sum(sk.`预计库存数量`) / (SELECT
                sum(销售数量) as 销售数量
                FROM
                    cwl_duanmalv_retail_spring where `商品代码` = sk.货号
                    AND 省份 = sk.省份
                    group by 省份,商品代码
                ) as 周转
            FROM
                cwl_duanmalv_sk_spring AS sk
            --  where sk.省份='浙江省'
            --  and sk.货号='B32502003'
            GROUP BY
            sk.省份,sk.风格, sk.一级分类, sk.二级分类, sk.货号
            ORDER BY sk.省份
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $this->db_easyA->table('cwl_duanmalv_table4_spring')->where(1)->delete();
            $chunk_list = array_chunk($select, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_duanmalv_table4_spring')->strict(false)->insertAll($val);
            }

            $this->db_easyA->table('cwl_duanmalv_config')->where('id=3')->strict(false)->update([
                'table4_updatetime' => date('Y-m-d H:i:s')
            ]);  

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_table4_spring 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_table4_spring 更新失败！'
            ]);
        }
    }

    // 3.整体-单款断码情况
    public function table3() {
        $sql = "
            SELECT
                a.大类,
                a.货号,
                a.上柜数,
                a.断码家数,
                a.断码率,
            CASE	
                WHEN a.中类 = @中类 
                AND a.风格 = @风格 
                AND a.领型 = @领型 THEN
                    @rank := @rank + 1 ELSE @rank := 1 
                END AS 单款排名,
                @风格 := a.风格 AS 风格,
                @中类 := a.中类 AS 中类,
                @领型 := a.领型 AS 领型 
            FROM
                (
                SELECT
                    sk.风格,
                    sk.一级分类 AS 大类,
                    sk.二级分类 AS 中类,
                    sk.领型,
                    sk.货号,
                    sum( sk.`店铺SKC计数` ) AS 上柜数,
                    sum( CASE sk.标准齐码识别修订 WHEN '断码' THEN 1 ELSE 0 END ) AS 断码家数,
                    round(sum( CASE sk.标准齐码识别修订 WHEN '断码' THEN 1 ELSE 0 END ) / sum( sk.`店铺SKC计数` ), 4) AS 断码率
                FROM
                    cwl_duanmalv_sk_spring AS sk 
                GROUP BY
                    sk.货号 
                ORDER BY
                    风格,大类,中类,领型,断码率 DESC 
                ) AS a,
            ( SELECT @中类 := NULL, @风格 := NULL, @领型 := NULL, @rank := 0 ) T 
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $this->db_easyA->table('cwl_duanmalv_table3_spring')->where(1)->delete();
            $chunk_list = array_chunk($select, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_duanmalv_table3_spring')->strict(false)->insertAll($val);
            }

            $sql2 = "
                UPDATE cwl_duanmalv_table3_spring AS A
                LEFT JOIN (
                    SELECT
                        风格,大类,中类,领型,
                        MAX(单款排名) AS 最后排名 
                    FROM
                        `cwl_duanmalv_table3_spring` 
                    GROUP BY
                    风格,大类,中类,领型) AS B ON A.风格 = B.风格 
                    AND A.大类 = B.大类 
                    AND A.中类 = B.中类 
                    AND A.领型 = B.领型 
                    SET A.排名率 = ROUND(
                        A.单款排名 / B.最后排名, 4)
            ";
            $this->db_easyA->execute($sql2);

            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => 'cwl_duanmalv_table3_spring 更新成功！'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_table3_spring 更新失败！'
            ]);
        }
    }

    // 首页表1_1
    public function table1() {
        $date = date('Y-m-d');
        $sql = "
            SELECT 
                h1.*,
                -- (SELECT COUNT(*) FROM cwl_duanmalv_sk_spring WHERE 店铺名称=h1.店铺名称 AND `标准齐码识别修订`='断码') AS '断码数-整体',
                1 - (SELECT COUNT(*) FROM cwl_duanmalv_sk_spring WHERE 店铺名称=h1.店铺名称 AND `标准齐码识别修订`='断码' AND 风格 in ('基本款')) / `SKC数-整体` AS '齐码率-整体',
                1 - ROUND(`SKC数-TOP实际` / {$this->top}, 4)AS '齐码率-TOP实际',
                1 - ROUND(`SKC数-TOP考核` / {$this->top}, 4)AS '齐码率-TOP考核',
                date_format(now(),'%Y-%m-%d') AS 更新日期
                FROM 
                    (SELECT
                            f.首单日期,
                            h0.商品负责人,
                            h0.云仓,
                            h0.省份,
                            h0.店铺名称,
                            h0.经营模式,
                            h0.店铺总SKC数 AS 'SKC数-整体',
                            SUM(h0.`全部断码SKC数`) AS 'SKC数-TOP实际',
                            SUM(h0.`TOP断码SKC数`) AS 'SKC数-TOP考核'
                    FROM cwl_duanmalv_handle_1_spring h0 
                    LEFT JOIN customer_first f ON h0.店铺名称 = f.店铺名称 
                    WHERE 
                            h0.风格 in ('基本款')
                            AND h0.店铺总SKC数 > 0
                            AND f.首单日期 IS NOT NULL
                    GROUP BY
                            h0.商品负责人,
                            h0.店铺名称,
                            h0.经营模式
                    ORDER BY
                            h0.商品负责人,h0.店铺名称,h0.省份,h0.经营模式) AS h1 
                ORDER BY `齐码率-TOP实际` DESC                                             
        ";
        $select = $this->db_easyA->query($sql);
        // dump($select); die;
        if ($select) {
            // 只删除当天
            $this->db_easyA->table('cwl_duanmalv_table1_1_spring')->where([
                '更新日期' => $date
            ])->delete();

            $this->db_bi->table('cwl_duanmalv_table1_1_spring')->where([
                '更新日期' => $date
            ])->delete();
            // die;
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_table1_1_spring')->strict(false)->insertAll($val);
                $insert = $this->db_binew->table('cwl_duanmalv_table1_1_spring')->strict(false)->insertAll($val);
            }
            $this->table1_1_sort();   

            $this->table1_avg();

            $this->db_easyA->table('cwl_duanmalv_config')->where('id=3')->strict(false)->update([
                'table1_updatetime' => date('Y-m-d H:i:s'),
                'table1_month_updatetime' => date('Y-m-d H:i:s'),
            ]);  
        }
    }

    protected function table1_avg() {
        $date = date('Y-m-d');
        $sql_old = "
            SELECT
                t1.*,
                case
                    when t1.`直营-整体` > 0 AND t1.`加盟-整体` > 0 then (t1.`直营-整体` + t1.`加盟-整体`) / 2
                    when t1.`直营-整体` > 0 AND (t1.`加盟-整体` <= 0 || t1.`加盟-整体` is null) then (t1.`直营-整体`)
                    when (t1.`直营-整体` >= 0 || t1.`直营-整体` is null) AND t1.`加盟-整体` > 0 then (t1.`加盟-整体`)
                    else 0
                end as 	`合计-整体`,
                case
                    when t1.`直营-TOP实际` > 0 AND t1.`加盟-TOP实际` > 0 then (t1.`直营-TOP实际` + t1.`加盟-TOP实际`) / 2
                    when t1.`直营-TOP实际` > 0 AND (t1.`加盟-TOP实际` <= 0 || t1.`加盟-TOP实际` is null) then (t1.`直营-TOP实际`)
                    when (t1.`直营-TOP实际` >= 0 || t1.`直营-TOP实际` is null) AND t1.`加盟-TOP实际` > 0 then (t1.`加盟-TOP实际`)
                    else 0
                end as 	`合计-TOP实际`,
                case
                    when t1.`直营-TOP考核` > 0 AND t1.`加盟-TOP考核` > 0 then (t1.`直营-TOP考核` + t1.`加盟-TOP考核`) / 2
                    when t1.`直营-TOP考核` > 0 AND (t1.`加盟-TOP考核` <= 0 || t1.`加盟-TOP考核` is null) then (t1.`直营-TOP考核`)
                    when (t1.`直营-TOP考核` >= 0 || t1.`直营-TOP考核` is null) AND t1.`加盟-TOP考核` > 0 then (t1.`加盟-TOP考核`)
                    else 0
                end as 	`合计-TOP考核`,
                date_format(now(),'%Y-%m-%d') as 更新日期
            FROM
                (
                SELECT
                    '合计' as 云仓,
                    '' as 商品负责人,
                    AVG( zy.`齐码率-整体` ) AS `直营-整体`,
                    jm.`加盟-整体`,
                    AVG( zy.`齐码率-TOP实际` ) AS `直营-TOP实际`,
                    jm.`加盟-TOP实际`,
                    AVG( zy.`齐码率-TOP考核` ) AS `直营-TOP考核`,
                    jm.`加盟-TOP考核` 
                FROM
                    cwl_duanmalv_table1_1_spring_spring AS zy
                    LEFT JOIN (
                    SELECT
                        云仓,商品负责人,
                        AVG( `齐码率-整体` ) AS `加盟-整体`,
                        AVG( `齐码率-TOP实际` ) AS `加盟-TOP实际`,
                        AVG( `齐码率-TOP考核` ) AS `加盟-TOP考核` 
                    FROM
                        cwl_duanmalv_table1_1_spring 
                    WHERE
                        经营模式 = '加盟' 
                        AND `更新日期` = date_format(now(),'%Y-%m-%d')
                    ) AS jm ON zy.商品负责人 = jm.`商品负责人` 
                WHERE
                    zy.经营模式 = '直营' 
                    AND zy.`更新日期` = date_format(now(),'%Y-%m-%d')
                ) AS t1   
        ";
        $sql = "
            SELECT
                    '合计' as 云仓,
                    '' as 商品负责人,
                    AVG( case when hj.`齐码率-整体` >0 then hj.`齐码率-整体` end ) AS `合计-整体`,
                    jm.`加盟-整体`,
                    zy.`直营-整体`,
                    AVG( case when hj.`齐码率-TOP实际` > 0 then hj.`齐码率-TOP实际` end ) AS `合计-TOP实际`,
                    jm.`加盟-TOP实际`,
                    zy.`直营-TOP实际`,
                    AVG( case when hj.`齐码率-TOP考核` > 0 then hj.`齐码率-TOP考核` end ) AS `合计-TOP考核`,
                    jm.`加盟-TOP考核`,
                    zy.`直营-TOP考核`,
                    date_format(now(),'%Y-%m-%d') as 更新日期 
            FROM
                    cwl_duanmalv_table1_1_spring AS hj
                    LEFT JOIN (
                        SELECT
                                云仓,商品负责人,
                                AVG( case when `齐码率-整体` >0 then `齐码率-整体` end ) AS `加盟-整体`,
                                AVG( case when `齐码率-TOP实际` >0 then `齐码率-TOP实际` end ) AS `加盟-TOP实际`,
                                AVG( case when `齐码率-TOP考核` >0 then `齐码率-TOP考核` end ) AS `加盟-TOP考核` 
                        FROM
                                cwl_duanmalv_table1_1_spring 
                        WHERE
                                经营模式 = '加盟' 
                                AND `更新日期` = date_format(now(),'%Y-%m-%d')
                    ) AS jm ON hj.商品负责人 = jm.`商品负责人` 
                    LEFT JOIN (
                        SELECT
                                云仓,商品负责人,
                                AVG( case when `齐码率-整体` > 0 then `齐码率-整体` end ) AS `直营-整体`,
                                AVG( case when `齐码率-TOP实际` > 0 then `齐码率-TOP实际` end ) AS `直营-TOP实际`,
                                AVG( case when `齐码率-TOP考核` > 0 then `齐码率-TOP考核` end ) AS `直营-TOP考核` 
                        FROM
                                cwl_duanmalv_table1_1_spring 
                        WHERE
                                经营模式 = '直营' 
                                AND `更新日期` = date_format(now(),'%Y-%m-%d')
                    ) AS zy ON hj.商品负责人 = zy.`商品负责人` 
            WHERE
                    hj.`更新日期` = date_format(now(),'%Y-%m-%d') 
        ";
        $select = $this->db_easyA->query($sql);
        if ($select) {
            $this->db_easyA->table('cwl_duanmalv_table1_avg_spring')->where([
                '更新日期' => $date
            ])->delete();
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_table1_avg_spring')->strict(false)->insertAll($val);
            }
        }
    }

    protected function table1_1_sort() {
        $date = date('Y-m-d');
        $sql = "
            SELECT
                a.云仓,
                a.省份,
                a.店铺名称,
                a.经营模式,
                a.`SKC数-整体`,
                a.`齐码率-整体`,
                a.`SKC数-TOP实际`,
                a.`齐码率-TOP实际`,
                a.`SKC数-TOP考核`,
                a.`齐码率-TOP考核`,
                a.更新日期,
                CASE
                    WHEN 
                            a.商品负责人 = @商品负责人
                    THEN
                            @rank := @rank + 1
                    ELSE @rank := 1
                END AS 单店排名, 
                @商品负责人 := a.商品负责人 AS 商品负责人
            FROM
                cwl_duanmalv_table1_1_spring a,
                ( SELECT @商品负责人 := null, @rank := 0 ) T 
            WHERE a.更新日期='{$date}'
            ORDER BY
                商品负责人,
            `齐码率-TOP实际` DESC
        ";
        $select = $this->db_easyA->query($sql);
        // dump($select); die;
        if ($select) {
            // 只删除当天
            $this->db_easyA->table('cwl_duanmalv_table1_1_spring')->where([
                '更新日期' => $date
            ])->delete();

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_table1_1_spring')->strict(false)->insertAll($val);
            }
        }
    }

    // 首页表1_2
    public function table1_2() {
        $date = date('Y-m-d');
        $sql_bak = "
                SELECT
                    t1.*,
                    case
                        when t1.`直营-整体` > 0 AND t1.`加盟-整体` > 0 then (t1.`直营-整体` + t1.`加盟-整体`) / 2
                        when t1.`直营-整体` > 0 AND (t1.`加盟-整体` <= 0 || t1.`加盟-整体` is null) then (t1.`直营-整体`)
                        when (t1.`直营-整体` >= 0 || t1.`直营-整体` is null) AND t1.`加盟-整体` > 0 then (t1.`加盟-整体`)
                        else 0
                    end as 	`合计-整体`,
                    case
                        when t1.`直营-TOP实际` > 0 AND t1.`加盟-TOP实际` > 0 then (t1.`直营-TOP实际` + t1.`加盟-TOP实际`) / 2
                        when t1.`直营-TOP实际` > 0 AND (t1.`加盟-TOP实际` <= 0 || t1.`加盟-TOP实际` is null) then (t1.`直营-TOP实际`)
                        when (t1.`直营-TOP实际` >= 0 || t1.`直营-TOP实际` is null) AND t1.`加盟-TOP实际` > 0 then (t1.`加盟-TOP实际`)
                        else 0
                    end as 	`合计-TOP实际`,
                    case
                        when t1.`直营-TOP考核` > 0 AND t1.`加盟-TOP考核` > 0 then (t1.`直营-TOP考核` + t1.`加盟-TOP考核`) / 2
                        when t1.`直营-TOP考核` > 0 AND (t1.`加盟-TOP考核` <= 0 || t1.`加盟-TOP考核` is null) then (t1.`直营-TOP考核`)
                        when (t1.`直营-TOP考核` >= 0 || t1.`直营-TOP考核` is null) AND t1.`加盟-TOP考核` > 0 then (t1.`加盟-TOP考核`)
                        else 0
                    end as 	`合计-TOP考核`,
                    date_format(now(),'%Y-%m-%d') as 更新日期
                FROM
                    (
                    SELECT
                        zy.云仓,
                        zy.商品负责人,
                        AVG( zy.`齐码率-整体` ) AS `直营-整体`,
                        jm.`加盟-整体`,
                        AVG( zy.`齐码率-TOP实际` ) AS `直营-TOP实际`,
                        jm.`加盟-TOP实际`,
                        AVG( zy.`齐码率-TOP考核` ) AS `直营-TOP考核`,
                        jm.`加盟-TOP考核` 
                    FROM
                        cwl_duanmalv_table1_1_spring AS zy
                        LEFT JOIN (
                        SELECT
                            云仓,商品负责人,
                            AVG( `齐码率-整体` ) AS `加盟-整体`,
                            AVG( `齐码率-TOP实际` ) AS `加盟-TOP实际`,
                            AVG( `齐码率-TOP考核` ) AS `加盟-TOP考核` 
                        FROM
                            cwl_duanmalv_table1_1_spring 
                        WHERE
                            经营模式 = '加盟' 
                            AND `更新日期` = date_format(now(),'%Y-%m-%d')
                        GROUP BY
                            商品负责人 
                        ) AS jm ON zy.商品负责人 = jm.`商品负责人` 
                    WHERE
                        zy.经营模式 = '直营' 
                        AND zy.`更新日期` = date_format(now(),'%Y-%m-%d')
                    GROUP BY
                    zy.商品负责人 
                    ) AS t1                                               
        ";

        // 修改合计算法
        $sql = "
            SELECT
                    hj.云仓,
                    hj.商品负责人,
                    AVG( case when hj.`齐码率-整体`>0 then hj.`齐码率-整体` end ) AS `合计-整体`,
                    jm.`加盟-整体`,
                    zy.`直营-整体`,
                    AVG( case when hj.`齐码率-TOP实际`>0 then hj.`齐码率-TOP实际` end ) AS `合计-TOP实际`,
                    jm.`加盟-TOP实际`,
                    zy.`直营-TOP实际`,
                    AVG( case when hj.`齐码率-TOP考核`>0 then hj.`齐码率-TOP考核` end ) AS `合计-TOP考核`,
                    jm.`加盟-TOP考核`,
                    zy.`直营-TOP考核`,
                    date_format(now(),'%Y-%m-%d') as 更新日期
            FROM
                    cwl_duanmalv_table1_1_spring AS hj
                    LEFT JOIN (
                        SELECT
                                云仓,商品负责人,
                                AVG( case when `齐码率-整体`>0 then `齐码率-整体` end ) AS `加盟-整体`,
                                AVG( case when `齐码率-TOP实际`>0 then `齐码率-TOP实际` end ) AS `加盟-TOP实际`,
                                AVG( case when `齐码率-TOP考核`>0 then `齐码率-TOP考核` end ) AS `加盟-TOP考核` 
                        FROM
                                cwl_duanmalv_table1_1_spring 
                        WHERE
                                经营模式 = '加盟' 
                                AND `更新日期` = date_format(now(),'%Y-%m-%d')
                        GROUP BY
                                商品负责人 
                    ) AS jm ON hj.商品负责人 = jm.`商品负责人` 
                    LEFT JOIN (
                        SELECT
                                云仓,商品负责人,
                                AVG( case when `齐码率-整体`>0 then `齐码率-整体` end ) AS `直营-整体`,
                                AVG( case when `齐码率-TOP实际`>0 then `齐码率-TOP实际` end ) AS `直营-TOP实际`,
                                AVG( case when `齐码率-TOP考核`>0 then `齐码率-TOP考核` end ) AS `直营-TOP考核` 
                        FROM
                                cwl_duanmalv_table1_1_spring 
                        WHERE
                                经营模式 in ('直营') 
                                AND `更新日期` = date_format(now(),'%Y-%m-%d')
                        GROUP BY
                                商品负责人 
                    ) AS zy ON hj.商品负责人 = zy.`商品负责人` 
            WHERE
                    hj.`更新日期` = date_format(now(),'%Y-%m-%d')
            GROUP BY
                hj.商品负责人                                             
        ";
        $select = $this->db_easyA->query($sql);
        // dump($select); die;
        if ($select) {
            // 只删除当天
            $this->db_easyA->table('cwl_duanmalv_table1_2_spring')->where([
                '更新日期' => $date
            ])->delete();
            // die;
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_table1_2_spring')->strict(false)->insertAll($val);
            }
            $this->table1_2_sort();   
            $this->db_easyA->table('cwl_duanmalv_config')->where('id=3')->strict(false)->update([
                'table1_2_updatetime' => date('Y-m-d H:i:s')
            ]);  
        }
    }

    protected function table1_2_sort() {
        $date = date('Y-m-d');
        $sql = "
            SELECT
                a.云仓,
                a.商品负责人,
                a.`直营-整体`,
                a.`加盟-整体`,
                a.`合计-整体`,
                a.`直营-TOP实际`,
                a.`加盟-TOP实际`,
                a.`合计-TOP实际`,
                a.`直营-TOP考核`,
                a.`加盟-TOP考核`,
                a.`合计-TOP考核`,
                a.`更新日期`,
                @rank := @rank + 1 AS 齐码排名 
            FROM
                cwl_duanmalv_table1_2_spring a,
                ( SELECT @rank := 0 ) T 
            WHERE a.更新日期='{$date}'
            ORDER BY
                `合计-TOP考核` DESC
        ";
        $select = $this->db_easyA->query($sql);
        // dump($select); die;
        if ($select) {
            // 只删除当天
            $this->db_easyA->table('cwl_duanmalv_table1_2_spring')->where([
                '更新日期' => $date
            ])->delete();

            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_table1_2_spring')->strict(false)->insertAll($val);
            }
        }
    }

    // 首页表1_3
    public function table1_3() {
        $date = date('Y-m-d');
        $sql_old = "
            SELECT 
                t2.*,
            case
                when t2.`直营-整体` > 0 AND t2.`加盟-整体` > 0 then (t2.`直营-整体` + t2.`加盟-整体`) / 2
                when t2.`直营-整体` > 0 AND (t2.`加盟-整体` <= 0 || t2.`加盟-整体` is null) then (t2.`直营-整体`)
                when (t2.`直营-整体` >= 0 || t2.`直营-整体` is null) AND t2.`加盟-整体` > 0 then (t2.`加盟-整体`)
                else 0
            end as 	`合计-整体`,
            case
                when t2.`直营-TOP实际` > 0 AND t2.`加盟-TOP实际` > 0 then (t2.`直营-TOP实际` + t2.`加盟-TOP实际`) / 2
                when t2.`直营-TOP实际` > 0 AND (t2.`加盟-TOP实际` <= 0 || t2.`加盟-TOP实际` is null) then (t2.`直营-TOP实际`)
                when (t2.`直营-TOP实际` >= 0 || t2.`直营-TOP实际` is null) AND t2.`加盟-TOP实际` > 0 then (t2.`加盟-TOP实际`)
                else 0
            end as 	`合计-TOP实际`,
            case
                when t2.`直营-TOP考核` > 0 AND t2.`加盟-TOP考核` > 0 then (t2.`直营-TOP考核` + t2.`加盟-TOP考核`) / 2
                when t2.`直营-TOP考核` > 0 AND (t2.`加盟-TOP考核` <= 0 || t2.`加盟-TOP考核` is null) then (t2.`直营-TOP考核`)
                when (t2.`直营-TOP考核` >= 0 || t2.`直营-TOP考核` is null) AND t2.`加盟-TOP考核` > 0 then (t2.`加盟-TOP考核`)
                else 0
            end as 	`合计-TOP考核`,
            date_format(now(),'%Y-%m-%d') as 更新日期
            FROM (
            SELECT
                t1.省份,
                t1.商品负责人,
                zy.`直营-整体`,
                zy.`直营-TOP实际`,
                zy.`直营-TOP考核`,
                jm.`加盟-整体`,
                jm.`加盟-TOP实际`,
                jm.`加盟-TOP考核`
            FROM
                cwl_duanmalv_table1_1_spring t1
            LEFT JOIN (
            SELECT
                省份,
                商品负责人,
                AVG( `齐码率-整体` ) AS `直营-整体`,
                AVG( `齐码率-TOP实际` ) AS `直营-TOP实际`,
                AVG( `齐码率-TOP考核` ) AS `直营-TOP考核`
            FROM
                cwl_duanmalv_table1_1_spring 
            where 
                经营模式 = '直营' 
                AND 更新日期 = date_format(now(),'%Y-%m-%d')
            GROUP BY 省份, 商品负责人 )  AS zy  ON zy.商品负责人 = t1.`商品负责人` and zy.省份 = t1.`省份`
            LEFT JOIN (
                SELECT
                    省份,
                    商品负责人,
                        AVG( `齐码率-整体` ) AS `加盟-整体`,
                        AVG( `齐码率-TOP实际` ) AS `加盟-TOP实际`,
                        AVG( `齐码率-TOP考核` ) AS `加盟-TOP考核`
                FROM
                    cwl_duanmalv_table1_1_spring 
                where 
                    经营模式 = '加盟' 
                    AND 更新日期 = date_format(now(),'%Y-%m-%d')
                GROUP BY 省份, 商品负责人 )  AS jm  ON jm.商品负责人 = t1.`商品负责人` and jm.省份 = t1.`省份`	
            GROUP BY
                    t1.省份, t1.商品负责人 
            ) AS t2                                          
        ";
        $sql = "
                SELECT
                    t1.省份,
                    t1.商品负责人,
                    zy.`直营-整体`,
                    zy.`直营-TOP实际`,
                    zy.`直营-TOP考核`,
                    jm.`加盟-整体`,
                    jm.`加盟-TOP实际`,
                    jm.`加盟-TOP考核`,
                    hj.`合计-整体`,
                    hj.`合计-TOP实际`,
                    hj.`合计-TOP考核`,
                    date_format(now(),'%Y-%m-%d') as 更新日期
            FROM
                    cwl_duanmalv_table1_1_spring t1
            LEFT JOIN (
                SELECT
                        省份,
                        商品负责人,
                        AVG( `齐码率-整体` ) AS `直营-整体`,
                        AVG( `齐码率-TOP实际` ) AS `直营-TOP实际`,
                        AVG( `齐码率-TOP考核` ) AS `直营-TOP考核`
                FROM
                        cwl_duanmalv_table1_1_spring 
                where 
                        经营模式 = '直营' 
                        AND 更新日期 = date_format(now(),'%Y-%m-%d')
                GROUP BY 省份, 商品负责人 )  AS zy  ON zy.商品负责人 = t1.`商品负责人` and zy.省份 = t1.`省份`
            LEFT JOIN (
                    SELECT
                            省份,
                            商品负责人,
                            AVG( `齐码率-整体` ) AS `加盟-整体`,
                            AVG( `齐码率-TOP实际` ) AS `加盟-TOP实际`,
                            AVG( `齐码率-TOP考核` ) AS `加盟-TOP考核`
                    FROM
                            cwl_duanmalv_table1_1_spring 
                    where 
                            经营模式 = '加盟' 
                            AND 更新日期 = date_format(now(),'%Y-%m-%d')
                    GROUP BY 省份, 商品负责人 )  AS jm  ON jm.商品负责人 = t1.`商品负责人` and jm.省份 = t1.`省份`	
            LEFT JOIN (
                    SELECT
                            省份,
                            商品负责人,
                            AVG( case when `齐码率-整体` > 0 then `齐码率-整体` end ) AS `合计-整体`,
                            AVG( case when `齐码率-TOP实际` > 0 then `齐码率-TOP实际` end ) AS `合计-TOP实际`,
                            AVG( case when `齐码率-TOP考核` > 0 then `齐码率-TOP考核` end ) AS `合计-TOP考核`
                    FROM
                            cwl_duanmalv_table1_1_spring 
                    where 
                            更新日期 = date_format(now(),'%Y-%m-%d')
                    GROUP BY 省份, 商品负责人 )  AS hj  ON hj.商品负责人 = t1.`商品负责人` and hj.省份 = t1.`省份`	
            GROUP BY
                    t1.省份, t1.商品负责人 
        ";
        
        $select = $this->db_easyA->query($sql);
        // dump($select); die;
        if ($select) {
            // 只删除当天
            $this->db_easyA->table('cwl_duanmalv_table1_3_spring')->where([
                '更新日期' => $date
            ])->delete();
            // die;
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_table1_3_spring')->strict(false)->insertAll($val);
            }

            $sql_del = "
                DELETE 
                FROM
                    cwl_duanmalv_table1_3_spring 
                WHERE
                    `直营-整体` IS NULL 
                    AND `加盟-整体` IS NULL 
                    AND `合计-整体` IS NULL 
                    AND `直营-TOP实际` IS NULL
                    AND `加盟-TOP实际` IS NULL
                    AND `合计-TOP实际` IS NULL 
                    AND `直营-TOP考核` IS NULL
                    AND `加盟-TOP考核` IS NULL
                    AND `合计-TOP考核` IS NULL        
            ";
            $this->db_easyA->execute($sql_del);
            $this->table1_3_sort();   

            $this->db_easyA->table('cwl_duanmalv_config')->where('id=3')->strict(false)->update([
                'table1_3_updatetime' => date('Y-m-d H:i:s')
            ]); 
        }
    }

    protected function table1_3_sort() {
        $date = date('Y-m-d');
        $sql = "
            SELECT
                a.省份,
                a.商品负责人,
                a.`直营-整体`,
                a.`加盟-整体`,
                a.`合计-整体`,
                a.`直营-TOP实际`,
                a.`加盟-TOP实际`,
                a.`合计-TOP实际`,
                a.`直营-TOP考核`,
                a.`加盟-TOP考核`,
                a.`合计-TOP考核`,
                a.`更新日期`,
                @rank := @rank + 1 AS 齐码排名 
            FROM
                cwl_duanmalv_table1_3_spring a,
                ( SELECT @rank := 0 ) T 
            WHERE a.更新日期='{$date}'
            ORDER BY
                `合计-TOP考核` DESC
        ";
        $select = $this->db_easyA->query($sql);
        // dump($select); die;
        if ($select) {
            // 只删除当天
            $this->db_easyA->table('cwl_duanmalv_table1_3_spring')->where([
                '更新日期' => $date
            ])->delete();
                
            $chunk_list = array_chunk($select, 500);

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_table1_3_spring')->strict(false)->insertAll($val);
            }
        }
    }
}
