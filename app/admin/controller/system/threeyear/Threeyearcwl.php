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

            $sql_2023 = "
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
                        -- 		`sp_customer_stock_sale_threeyear2_week` as m
                                `sp_customer_stock_sale_threeyear2_week_2023` as m
                            LEFT JOIN (
                                    SELECT
                                        `Year`,`Month`,`Week`,
                                        sum(StockQuantity) as StockQuantity,
                                        sum(StockCost) as StockCost,
                                        sum(SaleQuantity) as SaleQuantity,
                                        sum(SalesVolume) as SalesVolume 
                                    FROM
                                        `sp_customer_stock_sale_threeyear2_week`
                -- 						`sp_customer_stock_sale_threeyear2_week_2023`
                                    where 1
                                        and `Year` in (2023)
                                        and CategoryName1 in ('内搭', '外套', '下装', '鞋履')
                                        and StyleCategoryName in ('基本款', '引流款')
                            group by 
                                `Month`,`Week`
                            ) AS t on m.`Month` = t.`Month` and m.`Week`= t.`Week`
                            LEFT JOIN sp_customer_stock_sale_threeyear2_customer as c on m.Year = c.年 and m.Week = c.周 
                            where 1
                                and m.`Year` in (2023)
                            -- 	and `Month` in (1)
                            -- 	and `Week` in (1)
                                and m.CategoryName1 in ('内搭')
                                and m.StyleCategoryName in ('基本款')
                            group by 
                                m.`Year`,m.`Month`,m.`Week`,m.CategoryName1
                        ) AS res
                ) AS res2 
            
            
            ";
            $select = $this->db_easyA->query($sql);

            $count = count($select);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {

            return View('system/threeyearcwl/index', [

            ]);
        }
    }


}
