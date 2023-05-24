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
                SUM ( ERG.Quantity ) AS 销售数量,
                SUM ( ERG.Quantity* ERG.DiscountPrice ) AS 销售金额
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
                    'content' => 'cwl_duanmalv_retail first 更新成功，数量{$count}！'
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

    // 更新周销
    public function retail_second() {
        // 康雷查询周销
        $find_retail =$this->db_easyA->table('cwl_duanmalv_retail')->where([
            ['排名', 'exp', new Raw('IS NULL')]
        ])->find();
        // echo $this->db_easyA->getLastSql();
        // dump($find_retail);die;
        // echo count($select);

        // 需要进行排名
        if ($find_retail) {
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
                    a.小类,
                    a.领型,
                    a.风格,
                    a.商品代码,
                    a.销售数量,
                    a.销售金额, 
                (
                    @rank :=
                IF
                ( @GROUP = a.中类, @rank + 1, 1 )) AS 排名
                ,
                ( @GROUP := a.中类 ) AS 中类
            FROM
                (
                SELECT
                    *
                FROM
                    cwl_duanmalv_retail 
                WHERE
                    1
            -- 		省份='江西省'
            -- 		店铺名称 = '九江六店' 
                ORDER BY
                    店铺名称 ASC,风格 ASC,季节归集 ASC,中类 ASC, 排名 ASC,
                    销售数量 DESC 
                ) a,
                ( SELECT @rank := 0, @GROUP := '' ) AS b
            ");

            if ($select) {
                // dump($select[0]);
                // dump($select[1]);
                // dump($select[2]);
                // dump($select[3]);
                // die;
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
                        'content' => 'cwl_duanmalv_retail second 更新成功！'
                    ]);
                } else {
                    $this->db_easyA->rollback();
                    return json([
                        'status' => 0,
                        'msg' => 'error',
                        'content' => 'cwl_duanmalv_retail second 更新失败！'
                    ]);
                }
            } else {
                $this->db_easyA->rollback();
                return json([
                    'status' => 0,
                    'msg' => 'error',
                    'content' => 'cwl_duanmalv_retail 排名执行失败！'
                ]);
            }

        }
    }

}
