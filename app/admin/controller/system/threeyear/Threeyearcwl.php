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
                $map年_fm = " AND `Year` IN ({$mapStr})";
                $年 = $input['年'];
            } else {
                $map年 = "AND m.`Year` IN ('2023', '2022', '2021')";
                $map年_fm = " AND `Year` IN ('2023', '2022', '2021')";
                $年 = "";
            }

            #################### 分子1 ####################
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['云仓']);
                $map云仓 = " AND m.`CustomItem15` IN ({$mapStr})";
                $温度表_云仓 = " AND yuncang IN ({$mapStr})";
            } else {
                $map云仓 = "";
                $温度表_云仓 = "";
            }
            if (!empty($input['温带'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['温带']);
                $map温带 = " AND m.WenDai IN ({$mapStr})";
                $温度表_温带 = " AND wendai IN ({$mapStr})";
            } else {
                $map温带 = "";
                $温度表_温带 = "";
            }   
            if (!empty($input['温区'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['温区']);
                $map温区 = " AND m.WenQu IN ({$mapStr})";
                $温度表_温区 = " AND wenqu IN ({$mapStr})";
            } else {
                $map温区 = "";
                $温度表_温区 = "";
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
                $温度表_经营模式 = " AND mathod IN ({$mapStr})";
            } else {
                $map经营模式 = "";
                $温度表_经营模式 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['风格']);
                $map风格 = " AND m.StyleCategoryName IN ({$mapStr})";
            } else {
                $map风格 = "";
            }

            #################### 分子2 ####################
            if (!empty($input['一级分类'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['一级分类']);
                $map一级分类 = " AND m.`CategoryName1` IN ({$mapStr})";
            } else {
                $map一级分类 = "";
            } 
            if (!empty($input['二级分类'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['二级分类']);
                $map二级分类 = " AND m.CategoryName2 IN ({$mapStr})";
            } else {
                $map二级分类 = "";
            }
            if (!empty($input['分类'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['分类']);
                $map分类 = " AND m.CategoryName IN ({$mapStr})";
            } else {
                $map分类 = "";
            }
            if (!empty($input['波段'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['波段']);
                $map波段 = " AND m.TimeCategoryName IN ({$mapStr})";
            } else {
                $map波段 = "";
            }
            if (!empty($input['厚度'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['厚度']);
                $map厚度 = " AND m.CustomItem17 IN ({$mapStr})";
            } else {
                $map厚度 = "";
            } 
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['省份']);
                $map省份 = " AND m.State IN ({$mapStr})";
                $温度表_省份 = " AND state IN ({$mapStr})";
            } else {
                $map省份 = "";
                $温度表_省份 = "";
            }
            if (!empty($input['大区'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['大区']);
                $map大区 = " AND m.YunCang IN ({$mapStr})";

                $daquDate = explode(',', $input['大区']);
                // echo '<pre>';
                // print_r($daquDate); 
                $温度表_大区1 = "";
                foreach ($daquDate as $key => $val) {
                    if ($val == '两广') {
                        $温度表_大区1 .= "'广州云仓',";
                    } elseif ($val == '长江以南') {
                        $温度表_大区1 .= "'长沙云仓','南昌云仓',";
                    } elseif ($val == '长江以北') {
                        $温度表_大区1 .= "'武汉云仓','西安云仓',";
                    } elseif ($val == '西南片区') {
                        $温度表_大区1 .= "'贵阳云仓',";
                    }        
                }
                $温度表_大区1 = mb_substr($温度表_大区1, 0, -1);
                $温度表_大区 = " AND yuncang IN ($温度表_大区1)";
            } else {
                $map大区 = "";
                $温度表_大区 = "";
            } 
            if (!empty($input['新旧品'])) {
                // echo $input['商品负责人'];
                $mapStr = $input['新旧品'];
                // print_r($mapStr); die;
                if ($mapStr == '新品') {
                    // $map新旧品 = " AND m.TimeCategoryName1 IN ('2024', '2023')";
                    // $map新旧品 = " AND TimeCategoryName1 = `Year`";

                    // 新品条件
                    $map新旧品 = " 
                        AND (
                            m.TimeCategoryName1 >= m.`Year` 
                            OR (
                                m.TimeCategoryName1 = m.`Year` - 1 
                                AND m.`Month` <= 6 
                                AND ( m.Season = '冬季' )
                            ) 
                        )
                    ";
                } else {
                    // $map新旧品 = " AND m.TimeCategoryName1 IN ('2022', '2021', '2020')";
                    // 旧品条件
                    $map新旧品 = " 
                        AND (
                            m.TimeCategoryName1 < m.`Year` - 1
                            OR (
                                m.TimeCategoryName1 = m.`Year` - 1 
                                AND m.`Month` > 6 
                                AND m.Season = '冬季'
                            ) 
                            OR (
                                m.TimeCategoryName1 = m.`Year` - 1 
                                AND ( m.Season <> '冬季')
                            ) 
                        )
                    ";
                }
            } else {
                $map新旧品 = "";
            }

            #################### 分子3 ####################
            if (!empty($input['月份'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['月份']);
                $map月份 = " AND m.`Month` IN ({$mapStr})";
            } else {
                $map月份 = "";
            } 

            if (!empty($input['春夏云仓'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['春夏云仓']);
                $map春夏云仓 = " AND m.`CustomItem66` IN ({$mapStr})";
            } else {
                $map春夏云仓 = "";
            } 

            if (!empty($input['秋冬云仓'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['秋冬云仓']);
                $map秋冬云仓 = " AND m.`CustomItem65` IN ({$mapStr})";
            } else {
                $map秋冬云仓 = "";
            } 

            if (!empty($input['深浅色'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['深浅色']);
                $map深浅色 = " AND m.`CustomItem46` IN ({$mapStr})";
            } else {
                $map深浅色 = "";
            } 

            if (!empty($input['适龄段'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['适龄段']);
                $map适龄段 = " AND m.`CustomItem1` IN ({$mapStr})";
            } else {
                $map适龄段 = "";
            } 

            if (!empty($input['时尚度'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['时尚度']);
                $map时尚度 = " AND m.`CustomItem45` IN ({$mapStr})";
            } else {
                $map时尚度 = "";
            } 

            if (!empty($input['色感'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['色感']);
                $map色感 = " AND m.`CustomItem47` IN ({$mapStr})";
            } else {
                $map色感 = "";
            } 

            if (!empty($input['色系'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['色系']);
                $map色系 = " AND m.`CustomItem48` IN ({$mapStr})";
            } else {
                $map色系 = "";
            } 
            
            #################### 分母1 ####################
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

            #################### 分母2 ####################
            if (!empty($input['一级分类_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['一级分类_fm']);
                $map一级分类_fm = " AND `CategoryName1` IN ({$mapStr})";
            } else {
                $map一级分类_fm = "";
            }
            if (!empty($input['二级分类_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['二级分类_fm']);
                $map二级分类_fm = " AND CategoryName2 IN ({$mapStr})";
            } else {
                $map二级分类_fm = "";
            }
            if (!empty($input['分类_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['分类_fm']);
                $map分类_fm = " AND CategoryName IN ({$mapStr})";
            } else {
                $map分类_fm = "";
            }
            if (!empty($input['波段_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['波段_fm']);
                $map波段_fm = " AND TimeCategoryName IN ({$mapStr})";
            } else {
                $map波段_fm = "";
            }
            if (!empty($input['厚度_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['厚度_fm']);
                $map厚度_fm = " AND CustomItem17 IN ({$mapStr})";
            } else {
                $map厚度_fm = "";
            } 
            if (!empty($input['省份_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['省份_fm']);
                $map省份_fm = " AND State IN ({$mapStr})";
            } else {
                $map省份_fm = "";
            }
            if (!empty($input['大区_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['大区_fm']);
                $map大区_fm = " AND YunCang IN ({$mapStr})";
            } else {
                $map大区_fm = "";
            } 
            if (!empty($input['新旧品_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = $input['新旧品_fm'];
                // print_r($mapStr); die;
                if ($mapStr == '新品') {
                    // $map新旧品_fm = " AND TimeCategoryName1 IN ('2024', '2023')";
                    // 新品条件
                    $map新旧品_fm = " 
                        AND (
                            TimeCategoryName1 >= `Year` 
                            OR (
                                TimeCategoryName1 = `Year` - 1 
                                AND `Month` <= 6 
                                AND ( Season = '冬季')
                            ) 
                        )
                    ";
                } else {
                    // 旧品条件
                    $map新旧品_fm = " 
                        AND (
                            TimeCategoryName1 < `Year` - 1
                            OR (
                                TimeCategoryName1 = `Year` - 1 
                                AND `Month` > 6 
                                AND Season = '冬季'
                            ) 
                            OR (
                                TimeCategoryName1 = `Year` - 1 
                                AND ( Season <> '冬季')
                            ) 
                        )
                    ";
                }
            } else {
                $map新旧品_fm = "";
            }

            #################### 分母3 ####################

            if (!empty($input['春夏云仓_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['春夏云仓_fm']);
                $map春夏云仓_fm = " AND `CustomItem66` IN ({$mapStr})";
            } else {
                $map春夏云仓_fm = "";
            } 

            if (!empty($input['秋冬云仓_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['秋冬云仓_fm']);
                $map秋冬云仓_fm = " AND `CustomItem65` IN ({$mapStr})";
            } else {
                $map秋冬云仓_fm = "";
            } 

            if (!empty($input['深浅色_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['深浅色_fm']);
                $map深浅色_fm = " AND `CustomItem46` IN ({$mapStr})";
            } else {
                $map深浅色_fm = "";
            } 

            if (!empty($input['适龄段_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['适龄段_fm']);
                $map适龄段_fm = " AND `CustomItem1` IN ({$mapStr})";
            } else {
                $map适龄段_fm = "";
            } 

            if (!empty($input['时尚度_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['时尚度_fm']);
                $map时尚度_fm = " AND `CustomItem45` IN ({$mapStr})";
            } else {
                $map时尚度_fm = "";
            } 

            if (!empty($input['色感_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['色感_fm']);
                $map色感_fm = " AND `CustomItem47` IN ({$mapStr})";
            } else {
                $map色感_fm = "";
            } 

            if (!empty($input['色系_fm'])) {
                // echo $input['商品负责人'];
                $mapStr = xmSelectInput($input['色系_fm']);
                $map色系_fm = " AND `CustomItem48` IN ({$mapStr})";
            } else {
                $map色系_fm = "";
            } 
            
            // 分子
            $map1 = $map年 . $map云仓 . $map温带 . $map温区 . $map经营模式 . $map季节归集 . $map季节 . $map风格;
            $map2 = $map一级分类 . $map二级分类 . $map分类 . $map波段 . $map厚度 . $map省份 . $map大区 . $map新旧品;
            $map3 = $map月份 . $map春夏云仓 . $map秋冬云仓 . $map深浅色 . $map适龄段 . $map时尚度 . $map色感 . $map色系;
            $map = $map1 . $map2 . $map3;

            // 分母
            $map_fm_1 = $map年_fm . $map云仓_fm . $map温带_fm . $map温区_fm . $map经营模式_fm . $map季节归集_fm . $map季节_fm . $map风格_fm;
            $map_fm_2 = $map一级分类_fm . $map二级分类_fm . $map分类_fm . $map波段_fm . $map厚度_fm . $map省份_fm . $map大区_fm . $map新旧品_fm;
            $map_fm_3 = $map春夏云仓_fm . $map秋冬云仓_fm . $map深浅色_fm . $map适龄段_fm . $map时尚度_fm . $map色感_fm .$map色系_fm;
            $map_fm = $map_fm_1 . $map_fm_2 . $map_fm_3;

            // 温度表条件
            $map_weather = $温度表_温带 . $温度表_温区 . $温度表_云仓 . $温度表_大区 . $温度表_省份 . $温度表_经营模式;

            $执行sql = $this->sqlHandle($年, $map, $map_fm, $map_weather);
            
            $select = $this->db_easyA->query($执行sql);
          
            
            // if (empty(cache('threeyear_test'))) {
            //     $select = $this->db_easyA->query($执行sql);
            //     cache('threeyear_test', $select, 259200);
            // } 

            // $select = cache('threeyear_test');
            // echo '<pre>';
            // print_r($select);
            $res = $this->dataHandle($select);
            // $count = count($select);
            return json(["code" => "0", "msg" => "", "count" => count($res), "data" => $res, 'sql' => $执行sql]);
        } else {

            return View('system/threeyearcwl/index', [

            ]);
        }
    }

    // 测试
    public function index2() { 
        return View('system/threeyearcwl/index2', [

        ]);
    }

    // sql组合
    public function sqlHandle($年 = '', $map, $map_fm, $map_weather) {
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
                res2.`Year`, res2.`Month`, res2.`Week`, res2.店铺数, res2.周期, res2.分子库存数量, res2.分子库存成本金额, res2.分子销量, res2.分子销额, res2.分母库存数量,res2.分母库存成本金额,res2.分母销量,res2.分母销额,
                concat(round(res2.业绩占比 * 100, 1), '%') as 业绩占比,
                concat(round(res2.库存占比 * 100, 1), '%') as 库存占比,
                round(res2.店均周销量, 1) as 店均周销量,
                round(res2.店均库存量, 0) as 店均库存量,
                concat(round(res2.折扣 * 100, 1), '%') as 折扣,
                concat(round(res2.业绩占比 / res2.库存占比 * 100, 1), '%') as 效率,
                round(ifnull(res2.店均库存量, 0) / res2.店均周销量 * 7, 1) as `店周转(天)`,
                w.max_c as 最高温,
                w.min_c as 最低温
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
                        CONCAT(SUBSTRING(start_time, 6, 5), '/', SUBSTRING(end_time, 6, 5)) as '周期',
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
                        `Year`,`Month`,`Week`
                    ) AS t on m.`Year` = t.`Year` and m.`Month` = t.`Month` and m.`Week`= t.`Week`
                    LEFT JOIN (
                        select 
                            t11.YEAR as 年,
                            t11.WEEK as 周,
                            sum(t11.max_num) as 店铺数
                        from (
                            SELECT YEAR,
                                WEEK,
                                concat( YunCang, WenDai, WenQu, State, Mathod ) AS 云仓温带温区省份性质,
                                max( NUM ) AS max_num,
                                concat( YEAR, WEEK ) AS year_week 
                            FROM
                                `sp_customer_stock_sale_threeyear2_week` as m
                            WHERE 1
                                {$map}
                            GROUP BY
                                `云仓温带温区省份性质`,
                                `year_week`
                        ) as t11
                        group by t11.YEAR,t11.WEEK

                    ) as c on m.Year = c.年 and m.Week = c.周 
                    where 1
                        {$map}

                    group by 
                        m.`Year`,m.`Month`,m.`Week`
                ) AS res
            ) AS res2 
            LEFT JOIN (
                SELECT
                    Year,周期, 
                    round(AVG(max_c), 1) as max_c, 
                    round(AVG(min_c), 1) as min_c
                FROM
                    sp_customer_stock_sale_threeyear2_weather 
                WHERE 1
                    {$map_weather}
                GROUP BY YEAR, 周期
            ) as w on res2.`Year` = w.`Year` and res2.周期 = w.周期
        ";
        return $sql;
        // $select = $this->db_easyA->query($sql_2023);
    }

    // 头部数据缓存处理
    public function headerHandle() {
        // 表头信息
        if (empty(cache('threeyear_header_' . '2021')) || empty(cache('threeyear_header_' . '2022')) || empty(cache('threeyear_header_' . '2023'))) {
            $sql_2021 = "
                SELECT
                    m.`Year`,
                    m.`Month`,
                    m.`Week`,
                    CONCAT(
                        SUBSTRING( start_time, 6, 5 ),
                        '/',
                    SUBSTRING( end_time, 6, 5 )) AS '周期'
                FROM
                    `sp_customer_stock_sale_threeyear2_week` AS m
                WHERE
                    1 
                    AND m.`Year` IN ( '2021') 
                GROUP BY
                    m.`Year`,
                    m.`Month`,
                    m.`Week` 
            ";
            $sql_2022 = "
                SELECT
                    m.`Year`,
                    m.`Month`,
                    m.`Week`,
                    CONCAT(
                        SUBSTRING( start_time, 6, 5 ),
                        '/',
                    SUBSTRING( end_time, 6, 5 )) AS '周期'
                FROM
                    `sp_customer_stock_sale_threeyear2_week` AS m
                WHERE
                    1 
                    AND m.`Year` IN ( '2022') 
                GROUP BY
                    m.`Year`,
                    m.`Month`,
                    m.`Week` 
            ";
            $sql_2023 = "
                SELECT
                    m.`Year`,
                    m.`Month`,
                    m.`Week`,
                    CONCAT(
                        SUBSTRING( start_time, 6, 5 ),
                        '/',
                    SUBSTRING( end_time, 6, 5 )) AS '周期'
                FROM
                    `sp_customer_stock_sale_threeyear2_week` AS m
                WHERE
                    1 
                    AND m.`Year` IN ( '2023') 
                GROUP BY
                    m.`Year`,
                    m.`Month`,
                    m.`Week` 
            ";
            $select_header_2021 = $this->db_easyA->query($sql_2021);
            $select_header_2022 = $this->db_easyA->query($sql_2022);
            $select_header_2023 = $this->db_easyA->query($sql_2023);
            cache('threeyear_header_2021', $select_header_2021, 259200);
            cache('threeyear_header_2022', $select_header_2022, 259200);
            cache('threeyear_header_2023', $select_header_2023, 259200);
        }
    }

    // 组合结果
    public function dataHandle($select = []) {
        $this->headerHandle();
        $count = count($select);
        
        $展示年 = $select[$count - 1]['Year'];
        $header = cache('threeyear_header_' . $展示年);

        // 当年
        $current_year = '2023';
        foreach ($header as $key => $val) {
            $header[$key]['年'] = $展示年;
            $header[$key]['周'] = "第{$val['Week']}周";
            $header[$key]['月'] = "{$val['Month']}月";
            foreach ($select as $key2 => $val2) {
                if ($val2['Year'] == $current_year && $val['Week'] == $val2['Week']) {
                    $header[$key]['今年店铺数'] = $val2['店铺数']; 
                    $header[$key]['今年库存数量'] = $val2['分子库存数量']; 
                    $header[$key]['今年库存成本金额'] = $val2['分子库存成本金额']; 
                    $header[$key]['今年销量(周)'] = $val2['分子销量']; 
                    $header[$key]['今年销额'] = $val2['分子销额']; 
                    $header[$key]['今年分母库存数量'] = $val2['分母库存数量']; 
                    $header[$key]['今年分母库存成本金额'] = $val2['分母库存成本金额']; 
                    $header[$key]['今年分母销量'] = $val2['分母销量']; 
                    $header[$key]['今年分母销额'] = $val2['分母销额']; 
                    $header[$key]['今年业绩占比'] = $val2['业绩占比']; 
                    $header[$key]['今年库存占比'] = $val2['库存占比']; 
                    $header[$key]['今年店均周销量'] = $val2['店均周销量']; 
                    $header[$key]['今年店均库存量'] = $val2['店均库存量']; 
                    $header[$key]['今年折扣'] = $val2['折扣']; 
                    $header[$key]['今年效率'] = $val2['效率']; 
                    $header[$key]['今年店周转(天)'] = $val2['店周转(天)']; 
                    $header[$key]['今年最高温'] = $val2['最高温']; 
                    $header[$key]['今年最低温'] = $val2['最低温']; 
                    // break;
                }
                if ($val2['Year'] == $current_year - 1 && $val['Week'] == $val2['Week']) {
                    $header[$key]['去年店铺数'] = $val2['店铺数']; 
                    $header[$key]['去年库存数量'] = $val2['分子库存数量']; 
                    $header[$key]['去年库存成本金额'] = $val2['分子库存成本金额']; 
                    $header[$key]['去年销量(周)'] = $val2['分子销量']; 
                    $header[$key]['去年销额'] = $val2['分子销额']; 
                    $header[$key]['去年分母库存数量'] = $val2['分母库存数量']; 
                    $header[$key]['去年分母库存成本金额'] = $val2['分母库存成本金额']; 
                    $header[$key]['去年分母销量'] = $val2['分母销量']; 
                    $header[$key]['去年分母销额'] = $val2['分母销额']; 
                    $header[$key]['去年业绩占比'] = $val2['业绩占比']; 
                    $header[$key]['去年库存占比'] = $val2['库存占比']; 
                    $header[$key]['去年店均周销量'] = $val2['店均周销量']; 
                    $header[$key]['去年店均库存量'] = $val2['店均库存量']; 
                    $header[$key]['去年折扣'] = $val2['折扣']; 
                    $header[$key]['去年效率'] = $val2['效率']; 
                    $header[$key]['去年店周转(天)'] = $val2['店周转(天)']; 
                    $header[$key]['去年最高温'] = $val2['最高温']; 
                    $header[$key]['去年最低温'] = $val2['最低温']; 
                    // break;
                }
                if ($val2['Year'] == $current_year - 2 && $val['Week'] == $val2['Week']) {
                    $header[$key]['前年店铺数'] = $val2['店铺数']; 
                    $header[$key]['前年库存数量'] = $val2['分子库存数量']; 
                    $header[$key]['前年库存成本金额'] = $val2['分子库存成本金额']; 
                    $header[$key]['前年销量(周)'] = $val2['分子销量']; 
                    $header[$key]['前年销额'] = $val2['分子销额']; 
                    $header[$key]['前年分母库存数量'] = $val2['分母库存数量']; 
                    $header[$key]['前年分母库存成本金额'] = $val2['分母库存成本金额']; 
                    $header[$key]['前年分母销量'] = $val2['分母销量']; 
                    $header[$key]['前年分母销额'] = $val2['分母销额']; 
                    $header[$key]['前年业绩占比'] = $val2['业绩占比']; 
                    $header[$key]['前年库存占比'] = $val2['库存占比']; 
                    $header[$key]['前年店均周销量'] = $val2['店均周销量']; 
                    $header[$key]['前年店均库存量'] = $val2['店均库存量']; 
                    $header[$key]['前年折扣'] = $val2['折扣']; 
                    $header[$key]['前年效率'] = $val2['效率']; 
                    $header[$key]['前年店周转(天)'] = $val2['店周转(天)']; 
                    $header[$key]['前年最高温'] = $val2['最高温']; 
                    $header[$key]['前年最低温'] = $val2['最低温']; 
                    // break;
                }
            }
        }

        // dump($header);
        return $header;
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
