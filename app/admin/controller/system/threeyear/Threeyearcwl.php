<?php
namespace app\admin\controller\system\threeyear;

use think\facade\Db;
use think\cache\driver\Redis;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class Threeyearcwl
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="三年趋势CWL")
 */
class Threeyearcwl extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->create_time = date('Y-m-d H:i:s', time());
    }

    public function index() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            if (!empty($input['年'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['年']);
                $map_年 = " AND m.`Year` IN ({$mapStr})";
            } else {
                $map_年 = "";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['云仓']);
                $map_云仓 = " AND m.`CustomItem15` IN ({$mapStr})";
            } else {
                $map_云仓 = "";
            }
            if (!empty($input['温带'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['温带']);
                $map_温带 = " AND 省份 IN ({$mapStr})";
            } else {
                $map3 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['经营模式']);
                $map4 = " AND 经营模式 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['店铺名称']);
                $map5 = " AND 店铺名称 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['大类']);
                $map6 = " AND 一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['中类']);
                $map7 = " AND 二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['分类'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['分类']);
                $map8 = " AND 分类 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['货号']);
                $map9 = " AND 货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['风格']);
                $map10 = " AND 风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            if (!empty($input['提醒备注'])) {
                // echo $input['商品负责人'];
                $map11Str = xmSelectInput($input['提醒备注']);
                $map11 = " AND 提醒备注 IN ({$map11Str})";
            } else {
                $map11 = "";
            }
            if (!empty($input['超量个数'])) {
                $map12 = " AND 超量个数 = {$input['超量个数']}";
            } else {
                $map12 = "";
            }
            if (!empty($input['季节'])) {
                $map13Str = xmSelectInput($input['季节']);
                $map13 = " AND 季节 IN ($map13Str)";
            } else {
                $map13 = "";
            }



            $count = count($select);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {

            return View('system/threeyearcwl/index', [

            ]);
        }
    }

    // sql组合
    public function sqlHandle($年份 = '') {
        if ($年份) {
            $表 = "_{$年份}";
        } else {
            $表 = "";
        }
        // t是分母部分
        $sql = "
            SELECT 
                res2.*,
                res2.业绩占比 / res2.库存占比 as 效率,
                ifnull(res2.店均库存量, 0) / res2.店均周销量 * 7 as `店周转(天)`
            from 
            (
                SELECT 
                    res.*,
                    res.分子销额 / res.分母销额 as `业绩占比`,
                    res.分子库存成本金额 / res.分母库存成本金额 as `库存占比`,
                    res.分子销量 / res.店铺数 as 店均周销量,
                    res.分子库存数量 / res.店铺数 / 7 as 店均库存量
                FROM (
                    SELECT
                        m.`Year`,m.`Month`,m.`Week`,c.店铺数,
                        CategoryName1,
                        sum(m.StockQuantity) as 分子库存数量,
                        sum(m.StockCost) as 分子库存成本金额,
                        sum(m.SaleQuantity) as 分子销量,
                        sum(m.SalesVolume) as 分子销额, 
                        sum(m.SalesVolume) / sum(m.RetailAmount) as 折扣,
                    
                        t.StockQuantity as 分母库存数量,
                        t.StockCost as 分母库存成本金额,
                        t.SaleQuantity as 分母销量,
                        t.SalesVolume as 分母销额
                    FROM
                        `sp_customer_stock_sale_threeyear2_week{$表}` as m
                    LEFT JOIN (
                            SELECT
                                `Year`,`Month`,`Week`,
                                sum(StockQuantity) as StockQuantity,
                                sum(StockCost) as StockCost,
                                sum(SaleQuantity) as SaleQuantity,
                                sum(SalesVolume) as SalesVolume 
                            FROM
                                `sp_customer_stock_sale_threeyear2_week`
                            where 1
                                and `Year` in ({$年份})
                                and CategoryName1 in ('内搭', '外套', '下装', '鞋履')
                                and StyleCategoryName in ('基本款', '引流款')
                    group by 
                        `Month`,`Week`
                    ) AS t on m.`Month` = t.`Month` and m.`Week`= t.`Week`
                    LEFT JOIN sp_customer_stock_sale_threeyear2_customer as c on m.Year = c.年 and m.Week = c.周 
                    where 1
                        and m.`Year` in ({$年份})
                    -- 	and m.`Month` in (1)
                    -- 	and m.`Week` in (1)
                    --  and m.CategoryName1 in ('内搭')
                    --  and m.StyleCategoryName in ('基本款')
                    group by 
                        m.`Year`,m.`Month`,m.`Week`,m.CategoryName1
                ) AS res
            ) AS res2 
        ";
        return $sql;
        // $select = $this->db_easyA->query($sql_2023);
    }

    public function test() {
        echo $this->sqlHandle(2021);
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        if (! cache('threeyear2_XmMapSelect')) {
            $WenQu = $this->db_easyA->query("
                SELECT WenQu as name, WenQu as value FROM sp_customer_stock_sale_threeyear2_week WHERE WenQu IS NOT NULL GROUP BY WenQu
            ");
            $State = $this->db_easyA->query("
                SELECT State as name, State as value FROM sp_customer_stock_sale_threeyear2_week WHERE State IS NOT NULL GROUP BY State
            ");
            $Season = $this->db_easyA->query("
                SELECT Season as name, Season as value FROM sp_customer_stock_sale_threeyear2_week WHERE Season IS NOT NULL AND Season NOT IN ('通季', '畅销季') GROUP BY Season
            ");
            $TimeCategoryName2 = $this->db_easyA->query("
                SELECT TimeCategoryName2 as name, TimeCategoryName2 as value FROM sp_customer_stock_sale_threeyear2_week WHERE TimeCategoryName2 IS NOT NULL GROUP BY TimeCategoryName2
            ");
            $CategoryName1 = $this->db_easyA->query("
                SELECT CategoryName1 as name, CategoryName1 as value FROM sp_customer_stock_sale_threeyear2_week WHERE CategoryName1 IS NOT NULL GROUP BY CategoryName1
            ");
            $CategoryName2 = $this->db_easyA->query("
                SELECT CategoryName2 as name, CategoryName2 as value FROM sp_customer_stock_sale_threeyear2_week WHERE CategoryName2 IS NOT NULL GROUP BY CategoryName2
            ");
            $CategoryName = $this->db_easyA->query("
                SELECT CategoryName as name, CategoryName as value FROM sp_customer_stock_sale_threeyear2_week WHERE CategoryName IS NOT NULL GROUP BY CategoryName
            ");
            // 波段
            $TimeCategoryName = $this->db_easyA->query("
                SELECT TimeCategoryName as name, TimeCategoryName as value FROM sp_customer_stock_sale_threeyear2_week WHERE TimeCategoryName IS NOT NULL and TimeCategoryName <> '' GROUP BY TimeCategoryName
            ");
            // 厚度
            $CustomItem17 = $this->db_easyA->query("
                SELECT CustomItem17 as name, CustomItem17 as value FROM sp_customer_stock_sale_threeyear2_week WHERE CustomItem17 IS NOT NULL and CustomItem17 <> '' GROUP BY CustomItem17
            ");
            // 适龄段
            $CustomItem1 = $this->db_easyA->query("
                SELECT CustomItem1 as name, CustomItem1 as value FROM sp_customer_stock_sale_threeyear2_week WHERE CustomItem1 IS NOT NULL and CustomItem1 <> '' GROUP BY CustomItem1
            ");
            // 时尚度
            $CustomItem45 = $this->db_easyA->query("
                SELECT CustomItem45 as name, CustomItem45 as value FROM sp_customer_stock_sale_threeyear2_week WHERE CustomItem45 IS NOT NULL and CustomItem45 <> '' GROUP BY CustomItem45
            ");

            $data = ['WenQu' => $WenQu, 'State' => $State, 'Season' => $Season, 'TimeCategoryName2' => $TimeCategoryName2,
                'CategoryName1' => $CategoryName1, 'CategoryName2' => $CategoryName2, 'CategoryName' => $CategoryName, 'TimeCategoryName' => $TimeCategoryName,
                'CustomItem17' => $CustomItem17, 'CustomItem1' => $CustomItem1, 'CustomItem45' => $CustomItem45
            ];
            cache('threeyear2_XmMapSelect', $data, 259200); 
        } 

        $data = cache('threeyear2_XmMapSelect'); 
        return json(["code" => "0", "msg" => "", "data" => $data]);
        
    }
}
