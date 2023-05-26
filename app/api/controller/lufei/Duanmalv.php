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
class Duanmalv extends BaseController
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

    // 更新周销 断码率专用 初步加工康雷表 groub by合并插入自己的retail表里
    public function retail_first() {
        // 康雷查询周销
        $sql = "   
            SELECT TOP
                200000 EC.CustomItem17 AS 商品负责人,
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
                ERG.UnitPrice AS 零售价,
                ERG.DiscountPrice AS 当前零售价,
                SUM ( ERG.Quantity ) AS 销售数量,
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额,
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
                AND EG.TimeCategoryName2 IN ( '初夏', '盛夏', '夏季' )
                AND EC.CustomItem17 IS NOT NULL
                AND EBC.Mathod IN ('直营', '加盟')
                AND EG.TimeCategoryName1 IN ('2023')
                --AND ER.CustomerName = '九江六店'
                --AND EG.GoodsNo= 'B32503009'
            GROUP BY
                EC.CustomItem17
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
                ,ERG.UnitPrice
                ,ERG.DiscountPrice
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
            $this->db_easyA->table('cwl_duanmalv_retail')->where(1)->delete();

            $chunk_list = array_chunk($select, 1000);
            $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_retail')->strict(false)->insertAll($val);
                if (! $insert) {
                    $status = false;
                    break;
                }
            }

            if ($status) {
                $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_duanmalv_retail first 更新成功，数量：{$count}！"
                ]);
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_retail first 更新失败！'
                ]);
            }
        }
    }

    // 更新周销 进行排名
    public function retail_second() {
        // 计算折率
        $sql1 = "
            UPDATE cwl_duanmalv_retail 
            SET 折率= ROUND(`当前零售价` / 零售价, 2)
            WHERE 
            `折率` IS NULL
        ";
        $this->db_easyA->execute($sql1);
        
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
                a.中类,
                a.小类,
                a.风格,
                a.商品代码,
                a.零售价,
                a.当前零售价,
                a.销售数量,
                a.销售金额,
                a.折率,
                a.更新日期,
                (
                    @rank :=
                IF
                ( @GROUP = a.领型, @rank + 1, 1 )) AS 排名,
                ( @GROUP := a.领型 ) AS 领型 
            FROM
                (
                SELECT
                    * 
                FROM
                    cwl_duanmalv_retail 
                WHERE
                    折率 >= 1 
                 -- AND 省份 = '安徽省' 
                 -- AND 店铺名称 = '巢湖二店' 
                ORDER BY
                    店铺名称 ASC,风格 ASC,季节归集 ASC,中类 ASC,
                    领型 ASC,
                    销售数量 DESC 
                ) a,
                ( SELECT @rank := 0, @GROUP := '' ) AS b
        ");

        // echo '<pre>';
        // print_r($select); die;

        if ($select) {
            // $this->db_easyA->startTrans();

            // 删除 需要计算排名的
            $this->db_easyA->table('cwl_duanmalv_retail')->where([
                ['折率', '>=', 1]
            ])->delete();

            $chunk_list = array_chunk($select, 1000);
            

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_retail')->strict(false)->insertAll($val);
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
                    'content' => 'cwl_duanmalv_retail second 更新成功！'
                ]);
            } else {
                // $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_retail second 更新失败！'
                ]);
            }
        } else {
            // $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_retail 排名执行失败！'
            ]);
        }
    }

    public function sk_first()
    {
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
                    sk.季节 IN ('初夏', '盛夏', '夏季') 
                -- 	AND sk.年份 = 2023
                -- 	AND sk.省份='广东省'
                -- 	AND sk.店铺名称='东莞三店'
                -- 	AND sk.货号='B32101027'
                GROUP BY 
                    sk.店铺名称, 
                    sk.季节, 
                    sk.货号
                -- limit 100    
        ";
		
        $select_sk = $this->db_bi->query($sql);
        $count = count($select_sk);

        if ($select_sk) {
            // 删除历史数据
            $this->db_easyA->table('cwl_duanmalv_sk')->where(1)->delete();
            $chunk_list = array_chunk($select_sk, 1000);
            $this->db_easyA->startTrans();

            $status = true;
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $this->db_easyA->table('cwl_duanmalv_sk')->strict(false)->insertAll($val);
                if (! $insert) {
                    $status = false;
                    break;
                }
            }

            if ($status) {
                $this->db_easyA->commit();
                return json([
                    'status' => 1,
                    'msg' => 'success',
                    'content' => "cwl_duanmalv_sk first 更新成功，数量：{$count}！"
                ]);
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_sk first 更新失败！'
                ]);
            }
        }
    }

    // 更新店铺排名 零售价 当前零售价  (可能不需要)
    public function sk_second() {
        $sql = "
            UPDATE cwl_duanmalv_sk AS sk
                INNER JOIN cwl_duanmalv_retail AS dr ON sk.货号 = dr.`商品代码` AND sk.`店铺名称` = dr.`店铺名称` 
                SET sk.店铺近一周排名 = dr.排名,
                sk.零售价 = dr.零售价,
                sk.当前零售价 = dr.当前零售价 
                WHERE sk.店铺近一周排名 is null
        ";

        $this->db_easyA->startTrans();
        $status = $this->db_easyA->execute($sql);
        if ($status) {
            $this->db_easyA->commit();
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_duanmalv_sk 店铺排名 零售价 当前零售价 更新成功，数量：{$status}！"
            ]);
        } else {
            $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_sk 店铺排名 零售价 当前零售价 更新失败！'
            ]);
        }
    }

    // 断码 无效库存判定
    public function sk_third() {
        $sql1 = "set @内搭 = 4, @外套=4, @鞋履=4, @松紧长裤=5, @下装=6;";
        $this->db_easyA->execute($sql1);

        $sql2 = "
            UPDATE cwl_duanmalv_sk 
            SET 标准齐码识别修订 = 
                CASE 
                    WHEN (`累销数量`<= 0 || `累销数量` IS NULL) && (预计库存数量 <= 1 || 预计库存数量 IS NULL) THEN '无效库存'
                    
                    WHEN (`一级分类`='下装' && `预计库存连码个数` < @下装) THEN '断码'
                    WHEN (`一级分类`='松紧长裤' && `预计库存连码个数` < @松紧长裤) THEN '断码' 
                    WHEN (`一级分类`='鞋履' && `预计库存连码个数` < @鞋履) THEN '断码' 
                    WHEN (`一级分类`='外套' && `预计库存连码个数` < @外套) THEN '断码' 
                    WHEN (`一级分类`='内搭' && `预计库存连码个数` < @内搭) THEN '断码'
                    ELSE '无'
                END	
            WHERE 
            `标准齐码识别修订` IS NULL";
        $this->db_easyA->startTrans();
        $status = $this->db_easyA->execute($sql2);
        if ($status) {
            $this->db_easyA->commit();
            return json([
                'status' => 1,
                'msg' => 'success',
                'content' => "cwl_duanmalv_sk 标准齐码识别修订 更新成功，数量：{$status}！"
            ]);
        } else {
            $this->db_easyA->rollback();
            return json([
                'status' => 0,
                'msg' => 'error',
                'content' => 'cwl_duanmalv_sk 标准齐码识别修订 更新失败！'
            ]);
        }
    }

}
