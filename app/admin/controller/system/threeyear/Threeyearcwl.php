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

            // 筛选项
            if (!empty($input['年'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['年']);
                $map年 = " AND m.`Year` IN ({$mapStr})";
                $年 = $input['年'];
            } else {
                $map年 = "AND m.`Year` IN ('2023', '2022', '2021')";
                $年 = "";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['云仓']);
                $map云仓 = " AND m.`CustomItem15` IN ({$mapStr})";
            } else {
                $map云仓 = "";
            }
            if (!empty($input['温带'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['温带']);
                $map温带 = " AND m.WenDai IN ({$mapStr})";
            } else {
                $map温带 = "";
            }   
            if (!empty($input['温区'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['温区']);
                $map温区 = " AND m.WenQu IN ({$mapStr})";
            } else {
                $map温区 = "";
            }
            if (!empty($input['季节归集'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['季节归集']);
                $map季节归集 = " AND m.Season IN ({$mapStr})";
            } else {
                $map季节归集 = "";
            }
            if (!empty($input['季节'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['季节']);
                $map季节 = " AND m.TimeCategoryName2 IN ({$mapStr})";
            } else {
                $map季节= "";
            }
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['经营模式']);
                $map经营模式 = " AND m.Mathod IN ({$mapStr})";
            } else {
                $map经营模式 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['风格']);
                $map风格 = " AND m.StyleCategoryName IN ({$mapStr})";
            } else {
                $map风格 = "";
            }

            // 分母
            if (!empty($input['年_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['年_fm']);
                $map年_fm = " AND `Year` IN ({$mapStr})";
                $年_fm = $input['年'];
            } else {
                // 年份全选
                $map年_fm  = "";
                $年_fm = "";
            }
            if (!empty($input['云仓_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['云仓_fm']);
                $map云仓_fm = " AND `CustomItem15` IN ({$mapStr})";
            } else {
                $map云仓_fm = "";
            }
            if (!empty($input['温带_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['温带_fm']);
                $map温带_fm = " AND WenDai IN ({$mapStr})";
            } else {
                $map温带_fm = "";
            }   
            if (!empty($input['温区_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['温区_fm']);
                $map温区_fm = " AND WenQu IN ({$mapStr})";
            } else {
                $map温区_fm = "";
            }
            if (!empty($input['季节归集_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['季节归集_fm']);
                $map季节归集_fm = " AND Season IN ({$mapStr})";
            } else {
                $map季节归集_fm = "";
            }
            if (!empty($input['季节_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['季节_fm']);
                $map季节_fm = " AND TimeCategoryName2 IN ({$mapStr})";
            } else {
                $map季节_fm = "";
            }
            if (!empty($input['经营模式_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['经营模式_fm']);
                $map经营模式_fm = " AND Mathod IN ({$mapStr})";
            } else {
                $map经营模式_fm = "";
            }
            if (!empty($input['风格_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['风格_fm']);
                $map风格_fm = " AND StyleCategoryName IN ({$mapStr})";
            } else {
                $map风格_fm = "";
            }

            $map = $map年 . $map云仓 . $map温带 . $map温区 . $map经营模式 . $map季节归集 . $map季节 . $map风格;

            // echo '<br>';
            
            $map_fm = $map年_fm . $map云仓_fm . $map温带_fm . $map温区_fm . $map经营模式_fm . $map季节归集_fm . $map季节_fm . $map风格_fm;

            // echo '<br>';
            

            $执行sql = $this->sqlHandle($年, $map, $map_fm);

            $select = $this->db_easyA->query($执行sql);
            echo '<pre>';
            print_r($select);
            // $count = count($select);
            // return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {

            return View('system/threeyearcwl/index', [

            ]);
        }
    }

    // sql组合
    public function sqlHandle($年 = '', $map, $map_fm) {
        if ($年) {
            // 如果是 2022 或 2021一个年份的
            if (strlen($年) == 4) {
                $表 = "_{$年}";
            } else {
                // 如果是 2022,2021这种多选的
                $表 = "";
            }
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
                                {$map_fm}

                    group by 
                        `Month`,`Week`
                    ) AS t on m.`Month` = t.`Month` and m.`Week`= t.`Week`
                    LEFT JOIN sp_customer_stock_sale_threeyear2_customer as c on m.Year = c.年 and m.Week = c.周 
                    where 1
                        {$map}

                    group by 
                        m.`Year`,m.`Month`,m.`Week`
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
