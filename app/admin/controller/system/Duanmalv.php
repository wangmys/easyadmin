<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;

/**
 * Class Budongxiao
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="断码率")
 */
class Duanmalv extends AdminController
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

    // 更新周推条件
    protected function updateWeekpushMap() {
        $this->db_easyA->table('cwl_budongxiao_weekpush_map')->insert([
            'create_time' => date('Y-m-d H:i:s', time()),
            'update_time' => date('Y-m-d H:i:s', time()),
            'map' => json_encode($this->params),
        ]); 
    }

    /**
     * @NodeAnotation(title="周销")
     */
    public function zhouxiao() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    * 
                FROM cwl_duanmalv_retail 
                WHERE 
                    1
                ORDER BY 
                    `商品负责人`, 省份, 店铺名称, 大类, 中类, 小类, 领型, 风格
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_retail 
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => $select[0]['更新日期']]);
        } else {

            return View('zhouxiao', [

            ]);
        }
    }

    // 下载周销
    public function excel_zhouxiao() {
        $sql = "
            SELECT 
                * 
            FROM cwl_duanmalv_retail 
            WHERE 
                1
            ORDER BY 
                `商品负责人`, 省份, 店铺名称, 大类, 中类, 小类, 领型, 风格
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '断码率周销明细_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

     /**
     * @NodeAnotation(title="云仓在途")
     */
    public function zt() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    * 
                FROM cwl_duanmalv_zt 
                WHERE 
                    1
                ORDER BY 
                    `云仓`, 季节, 一级分类, 二级分类
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_zt 
                WHERE 
                    1
                ORDER BY 
                    `云仓`, 季节, 一级分类, 二级分类
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => $select[0]['更新日期']]);
        } else {

            return View('zt', [

            ]);
        }
    }

    /**
     * @NodeAnotation(title="单店断码明细") 表7
     */
    public function sk() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (!empty($input['商品负责人'])) {
                // echo $input['商品负责人'];
                $map1Str = $this->xmSelectInput($input['商品负责人']);
                $map1 = " AND 商品负责人 IN ({$map1Str})";
            } else {
                $map1 = "";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = $this->xmSelectInput($input['云仓']);
                $map2 = " AND 云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = $this->xmSelectInput($input['省份']);
                $map3 = " AND 省份 IN ({$map3Str})";
            } else {
                $map3 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map4Str = $this->xmSelectInput($input['经营模式']);
                $map4 = " AND 经营模式 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map5Str = $this->xmSelectInput($input['店铺名称']);
                $map5 = " AND 店铺名称 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = $this->xmSelectInput($input['大类']);
                $map6 = " AND 一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = $this->xmSelectInput($input['中类']);
                $map7 = " AND 二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['领型'])) {
                // echo $input['商品负责人'];
                $map8Str = $this->xmSelectInput($input['领型']);
                $map8 = " AND 领型 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = $this->xmSelectInput($input['货号']);
                $map9 = " AND 货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = $this->xmSelectInput($input['风格']);
                $map10 = " AND 风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            if (!empty($input['是否TOP60'])) {
                // echo $input['商品负责人'];
                $map11Str = $this->xmSelectInput($input['是否TOP60']);
                $map11 = " AND 是否TOP60 IN ({$map11Str})";
            } else {
                $map11 = "";
            }
            if (!empty($input['是否TOP60考核款'])) {
                // echo $input['商品负责人'];
                $map12Str = $this->xmSelectInput($input['是否TOP60考核款']);
                $map12 = " AND 是否TOP60考核款 IN ({$map12Str})";
            } else {
                $map12 = "";
            }

            $sql = "
                SELECT 
                    云仓,
                    店铺名称,
                    商品负责人,
                    省份,
                    经营模式,
                    年份,
                    店铺等级,
                    季节,
                    一级分类,
                    二级分类,
                    分类,
                    领型,
                    风格,
                    货号,
                    预计库存连码个数,
                    标准齐码识别修订,
                    店铺SKC计数,
                    店铺近一周排名,
                    是否TOP60考核款,
                    是否TOP60,
                    零售价,
                    当前零售价,
                    折率,
                    销售金额,
                    总入量数量,
                    累销数量,
                    预计库存数量
                FROM cwl_duanmalv_sk WHERE 1
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
                    {$map7}
                    {$map8}
                    {$map9}
                    {$map10}
                    {$map11}
                    {$map12}
                ORDER BY 
                    云仓, `商品负责人` desc, 店铺名称, 风格, 季节, 一级分类, 二级分类, 分类, 领型
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_sk
                WHERE  1
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
                    {$map7}
                    {$map8}
                    {$map9}
                    {$map10}
                    {$map11}
                    {$map12}
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('sk', [

            ]);
        }
    }

    public function test() {
        return View('test', [

        ]); 
    }

    public function test2() {
            // 改良平均值
            $sql3 = "
            SELECT
                t1.云仓,
                t1.商品负责人,
                '' as `齐码排名-新`,
                t1.`直营-整体` as `直营-整体-新`,
                t1.`加盟-整体` as `加盟-整体-新`,
                t1.`合计-整体` as `合计-整体-新`,
                t1.`直营-TOP实际` as `直营-TOP实际-新`,        
                t1.`加盟-TOP实际` as `加盟-TOP实际-新`,
                t1.`合计-TOP实际` as `合计-TOP实际-新`,
                t1.`直营-TOP考核` as `直营-TOP考核-新`,
                t1.`加盟-TOP考核` as `加盟-TOP考核-新`,
                t1.`合计-TOP考核` as `合计-TOP考核-新`,
                t1.`更新日期` as `更新日期-新`,
                t2.*
                FROM
                cwl_duanmalv_table1_avg t1,  (				
                    SELECT
                        '' as `齐码排名-旧`,
                        `直营-整体` as `直营-整体-旧`,
                        `加盟-整体` as `加盟-整体-旧`,
                        `合计-整体` as `合计-整体-旧`,
                        `直营-TOP实际` as `直营-TOP实际-旧`,
                        `加盟-TOP实际` as `加盟-TOP实际-旧`,
                        `合计-TOP实际` as `合计-TOP实际-旧`,
                        `直营-TOP考核` as `直营-TOP考核-旧`,
                        `加盟-TOP考核` as `加盟-TOP考核-旧`,
                        `合计-TOP考核` as `合计-TOP考核-旧`,
                        `更新日期` as `更新日期-旧` 
                    FROM
                    cwl_duanmalv_table1_avg  
                    WHERE
                    `更新日期` ='2023-06-12'
                ) as t2 
                WHERE
                t1.`更新日期` ='2023-06-16'            
        "; 
        
        $select2 = $this->db_easyA->query($sql3);
        foreach ($select2 as $key => $val) {
            if (! empty($val['直营-整体-新'])) {
                $select2[$key]['直营-整体-新'] = $this->float1($val['直营-整体-新']);
            }
            if (! empty($val['加盟-整体-新'])) {
                $select2[$key]['加盟-整体-新'] = $this->float1($val['加盟-整体-新']);
            }
            if (! empty($val['合计-整体-新'])) {
                $select2[$key]['合计-整体-新'] = $this->float1($val['合计-整体-新']);
            }
            if (! empty($val['直营-TOP实际-新'])) {
                $select2[$key]['直营-TOP实际-新'] = $this->float1($val['直营-TOP实际-新']);
            }
            if (! empty($val['加盟-TOP实际-新'])) {
                $select2[$key]['加盟-TOP实际-新'] = $this->float1($val['加盟-TOP实际-新']);
            }
            if (! empty($val['合计-TOP实际-新'])) {
                $select2[$key]['合计-TOP实际-新'] = $this->float1($val['合计-TOP实际-新']);
            }
            if (! empty($val['直营-TOP考核-新'])) {
                $select2[$key]['直营-TOP考核-新'] = $this->float1($val['直营-TOP考核-新']);
            }
            if (! empty($val['加盟-TOP考核-新'])) {
                $select2[$key]['加盟-TOP考核-新'] = $this->float1($val['加盟-TOP考核-新']);
            }
            if (! empty($val['合计-TOP考核-新'])) {
                $select2[$key]['合计-TOP考核-新'] = $this->float1($val['合计-TOP考核-新']);
            }

            if (! empty($val['直营-整体-旧'])) {
                $select2[$key]['直营-整体-旧'] = $this->float1($val['直营-整体-旧']);
            }
            if (! empty($val['加盟-整体-旧'])) {
                $select2[$key]['加盟-整体-旧'] = $this->float1($val['加盟-整体-旧']);
            }
            if (! empty($val['合计-整体-旧'])) {
                $select2[$key]['合计-整体-旧'] = $this->float1($val['合计-整体-旧']);
            }
            if (! empty($val['直营-TOP实际-旧'])) {
                $select2[$key]['直营-TOP实际-旧'] = $this->float1($val['直营-TOP实际-旧']);
            }
            if (! empty($val['加盟-TOP实际-旧'])) {
                $select2[$key]['加盟-TOP实际-旧'] = $this->float1($val['加盟-TOP实际-旧']);
            }
            if (! empty($val['合计-TOP实际-旧'])) {
                $select2[$key]['合计-TOP实际-旧'] = $this->float1($val['合计-TOP实际-旧']);
            }
            if (! empty($val['直营-TOP考核-旧'])) {
                $select2[$key]['直营-TOP考核-旧'] = $this->float1($val['直营-TOP考核-旧']);
            }
            if (! empty($val['加盟-TOP考核-旧'])) {
                $select2[$key]['加盟-TOP考核-旧'] = $this->float1($val['加盟-TOP考核-旧']);
            }
            if (! empty($val['合计-TOP考核-旧'])) {
                $select2[$key]['合计-TOP考核-旧'] = $this->float1($val['合计-TOP考核-旧']);
            }
        }
        echo '<pre>';
        print_r($select2);
    }

    // 下载单店断码明细
    public function excel_sk() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_sk WHERE 1
            ORDER BY 
                云仓, `商品负责人` desc, 店铺名称, 风格, 季节, 一级分类, 二级分类, 分类, 领型
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店断码明细_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    /**
     * @NodeAnotation(title="单店TOP60及断码数") 
     */
    public function handle() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    *
                FROM cwl_duanmalv_handle_1 WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_handle_1
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('handle', [

            ]);
        }        
    }

    // 下载单店TOP60及断码数
    public function excel_handle() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_handle_1 WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店TOP60及断码数_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    /**
     * @NodeAnotation(title="单店品类断码情况") 
     */
    public function table6() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    *
                FROM cwl_duanmalv_table6 WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table6
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('table6', [

            ]);
        }        
    }

    // 下载单店品类断码情况
    public function excel_table6() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_table6 WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店品类断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

        /**
     * @NodeAnotation(title="单店断码情况") 
     */
    public function table5() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            $sql = "
                SELECT 
                    *
                FROM cwl_duanmalv_table5 WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table5
                WHERE 
                    1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('table5', [

            ]);
        }        
    }

    // 下载单店断码情况
    public function excel_table5() {
        $sql = "
            SELECT 
                *
            FROM cwl_duanmalv_table5 WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

     /**
     * @NodeAnotation(title="单店单款断码情况") 
     */
    public function table4() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = $this->xmSelectInput($input['大类']);
                $map6 = " AND t4.大类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = $this->xmSelectInput($input['中类']);
                $map7 = " AND t4.中类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['领型'])) {
                // echo $input['商品负责人'];
                $map8Str = $this->xmSelectInput($input['领型']);
                $map8 = " AND t4.领型 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = $this->xmSelectInput($input['货号']);
                $map9 = " AND t4.货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = $this->xmSelectInput($input['风格']);
                $map10 = " AND t4.风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }

            $sql = "
                SELECT
                    t4.风格,
                    t4.大类,
                    t4.中类,
                    t4.领型,
                    t4.货号,
                    yn.省份 as '省份-滇',
                    yn.上柜数 as '上柜数-滇',
                    yn.断码家数 as '断码家数-滇',
                    yn.断码率 as '断码率-滇',
                    yn.周转 as '周转-滇',
                    sc.省份 as '省份-蜀',
                    sc.上柜数 as '上柜数-蜀',
                    sc.断码家数 as '断码家数-蜀',
                    sc.断码率 as '断码率-蜀',
                    sc.周转 as '周转-蜀',
                    tj.省份 as '省份-津',
                    tj.上柜数 as '上柜数-津',
                    tj.断码家数 as '断码家数-津',
                    tj.断码率 as '断码率-津',
                    tj.周转 as '周转-津',
                    nx.省份 as '省份-宁',
                    nx.上柜数 as '上柜数-宁',
                    nx.断码家数 as '断码家数-宁',
                    nx.断码率 as '断码率-宁',
                    nx.周转 as '周转-宁',
                    ah.省份 as '省份-皖',
                    ah.上柜数 as '上柜数-皖',
                    ah.断码家数 as '断码家数-皖',
                    ah.断码率 as '断码率-皖',
                    ah.周转 as '周转-皖',
                    gd.省份 as '省份-粤',
                    gd.上柜数 as '上柜数-粤',
                    gd.断码家数 as '断码家数-粤',
                    gd.断码率 as '断码率-粤',
                    gd.周转 as '周转-粤',
                    gx.省份 as '省份-桂',
                    gx.上柜数 as '上柜数-桂',
                    gx.断码家数 as '断码家数-桂',
                    gx.断码率 as '断码率-桂',
                    gx.周转 as '周转-桂',
                    xj.省份 as '省份-新',
                    xj.上柜数 as '上柜数-新',
                    xj.断码家数 as '断码家数-新',
                    xj.断码率 as '断码率-新',
                    xj.周转 as '周转-新',
                    jx.省份 as '省份-赣',
                    jx.上柜数 as '上柜数-赣',
                    jx.断码家数 as '断码家数-赣',
                    jx.断码率 as '断码率-赣',
                    jx.周转 as '周转-赣',
                    henan.省份 as '省份-豫',
                    henan.上柜数 as '上柜数-豫',
                    henan.断码家数 as '断码家数-豫',
                    henan.断码率 as '断码率-豫',
                    henan.周转 as '周转-豫',
                    zj.省份 as '省份-浙',
                    zj.上柜数 as '上柜数-浙',
                    zj.断码家数 as '断码家数-浙',
                    zj.断码率 as '断码率-浙',
                    zj.周转 as '周转-浙',
                    hainan.省份 as '省份-琼',
                    hainan.上柜数 as '上柜数-琼',
                    hainan.断码家数 as '断码家数-琼',
                    hainan.断码率 as '断码率-琼',
                    hainan.周转 as '周转-琼',
                    hb.省份 as '省份-鄂',
                    hb.上柜数 as '上柜数-鄂',
                    hb.断码家数 as '断码家数-鄂',
                    hb.断码率 as '断码率-鄂',
                    hb.周转 as '周转-鄂',
                    hunan.省份 as '省份-湘',
                    hunan.上柜数 as '上柜数-湘',
                    hunan.断码家数 as '断码家数-湘',
                    hunan.断码率 as '断码率-湘',
                    hunan.周转 as '周转-湘',
                    gs.省份 as '省份-甘',
                    gs.上柜数 as '上柜数-甘',
                    gs.断码家数 as '断码家数-甘',
                    gs.断码率 as '断码率-甘',
                    gs.周转 as '周转-甘',
                    fj.省份 as '省份-闽',
                    fj.上柜数 as '上柜数-闽',
                    fj.断码家数 as '断码家数-闽',
                    fj.断码率 as '断码率-闽',
                    fj.周转 as '周转-闽',
                    gz.省份 as '省份-贵',
                    gz.上柜数 as '上柜数-贵',
                    gz.断码家数 as '断码家数-贵',
                    gz.断码率 as '断码率-贵',
                    gz.周转 as '周转-贵',
                    cq.省份 as '省份-渝',
                    cq.上柜数 as '上柜数-渝',
                    cq.断码家数 as '断码家数-渝',
                    cq.断码率 as '断码率-渝',
                    cq.周转 as '周转-渝',
                    xx.省份 as '省份-陕',
                    xx.上柜数 as '上柜数-陕',
                    xx.断码家数 as '断码家数-陕',
                    xx.断码率 as '断码率-陕',
                    xx.周转 as '周转-陕',
                    qh.省份 as '省份-青',
                    qh.上柜数 as '上柜数-青',
                    qh.断码家数 as '断码家数-青',
                    qh.断码率 as '断码率-青',
                    qh.周转 as '周转-青'
                FROM
                    cwl_duanmalv_table4 AS t4
                    left join cwl_duanmalv_table4 as yn on t4.货号=yn.货号 and yn.省份='云南省'
                    left join cwl_duanmalv_table4 as sc on t4.货号=sc.货号 and sc.省份='四川省'
                    left join cwl_duanmalv_table4 as tj on t4.货号=tj.货号 and tj.省份='天津'
                    left join cwl_duanmalv_table4 as nx on t4.货号=nx.货号 and nx.省份='宁夏回族自治区'
                    left join cwl_duanmalv_table4 as ah on t4.货号=ah.货号 and ah.省份='安徽省'
                    left join cwl_duanmalv_table4 as gd on t4.货号=gd.货号 and gd.省份='广东省'
                    left join cwl_duanmalv_table4 as gx on t4.货号=gx.货号 and gx.省份='广西壮族自治区'
                    left join cwl_duanmalv_table4 as xj on t4.货号=xj.货号 and xj.省份='新疆维吾尔自治区'
                    left join cwl_duanmalv_table4 as jx on t4.货号=jx.货号 and jx.省份='江西省'
                    left join cwl_duanmalv_table4 as henan on t4.货号=henan.货号 and henan.省份='河南省'
                    left join cwl_duanmalv_table4 as zj on t4.货号=zj.货号 and zj.省份='浙江省'
                    left join cwl_duanmalv_table4 as hainan on t4.货号=hainan.货号 and hainan.省份='海南省'
                    left join cwl_duanmalv_table4 as hb on t4.货号=hb.货号 and hb.省份='湖北省'
                    left join cwl_duanmalv_table4 as hunan on t4.货号=hunan.货号 and hunan.省份='湖南省'
                    left join cwl_duanmalv_table4 as gs on t4.货号=gs.货号 and gs.省份='甘肃省'
                    left join cwl_duanmalv_table4 as fj on t4.货号=fj.货号 and fj.省份='福建省'
                    left join cwl_duanmalv_table4 as gz on t4.货号=gz.货号 and gz.省份='贵州省'
                    left join cwl_duanmalv_table4 as cq on t4.货号=cq.货号 and cq.省份='重庆'
                    left join cwl_duanmalv_table4 as xx on t4.货号=xx.货号 and xx.省份='陕西省'
                    left join cwl_duanmalv_table4 as qh on t4.货号=qh.货号 and qh.省份='青海省'
                --  where sk.省份='浙江省'
                --  and sk.货号='B32502003'
                WHERE 1
                    {$map6}
                    {$map7}
                    {$map8}
                    {$map9}
                    {$map10}
                GROUP BY
                t4.风格, t4.大类, t4.中类, t4.货号
                ORDER BY t4.风格
                    LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                select count(*) as total from(
                   SELECT 
                    count(*) as total
                    FROM cwl_duanmalv_table4 AS t4
                    WHERE 1
                        {$map6}
                        {$map7}
                        {$map8}
                        {$map9}
                        {$map10}
                    GROUP BY
                t4.风格, t4.大类, t4.中类, t4.货号) as t1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('table4', [

            ]);
        }        
    }

    // 下载单店单款断码情况
    public function excel_table4() {
        $sql = "
            SELECT
                t4.风格,
                t4.大类,
                t4.中类,
                t4.领型,
                t4.货号,
                yn.省份 as '省份-滇',
                yn.上柜数 as '上柜数-滇',
                yn.断码家数 as '断码家数-滇',
                yn.断码率 as '断码率-滇',
                yn.周转 as '周转-滇',
                sc.省份 as '省份-蜀',
                sc.上柜数 as '上柜数-蜀',
                sc.断码家数 as '断码家数-蜀',
                sc.断码率 as '断码率-蜀',
                sc.周转 as '周转-蜀',
                tj.省份 as '省份-津',
                tj.上柜数 as '上柜数-津',
                tj.断码家数 as '断码家数-津',
                tj.断码率 as '断码率-津',
                tj.周转 as '周转-津',
                nx.省份 as '省份-宁',
                nx.上柜数 as '上柜数-宁',
                nx.断码家数 as '断码家数-宁',
                nx.断码率 as '断码率-宁',
                nx.周转 as '周转-宁',
                ah.省份 as '省份-皖',
                ah.上柜数 as '上柜数-皖',
                ah.断码家数 as '断码家数-皖',
                ah.断码率 as '断码率-皖',
                ah.周转 as '周转-皖',
                gd.省份 as '省份-粤',
                gd.上柜数 as '上柜数-粤',
                gd.断码家数 as '断码家数-粤',
                gd.断码率 as '断码率-粤',
                gd.周转 as '周转-粤',
                gx.省份 as '省份-桂',
                gx.上柜数 as '上柜数-桂',
                gx.断码家数 as '断码家数-桂',
                gx.断码率 as '断码率-桂',
                gx.周转 as '周转-桂',
                xj.省份 as '省份-新',
                xj.上柜数 as '上柜数-新',
                xj.断码家数 as '断码家数-新',
                xj.断码率 as '断码率-新',
                xj.周转 as '周转-新',
                jx.省份 as '省份-赣',
                jx.上柜数 as '上柜数-赣',
                jx.断码家数 as '断码家数-赣',
                jx.断码率 as '断码率-赣',
                jx.周转 as '周转-赣',
                henan.省份 as '省份-豫',
                henan.上柜数 as '上柜数-豫',
                henan.断码家数 as '断码家数-豫',
                henan.断码率 as '断码率-豫',
                henan.周转 as '周转-豫',
                zj.省份 as '省份-浙',
                zj.上柜数 as '上柜数-浙',
                zj.断码家数 as '断码家数-浙',
                zj.断码率 as '断码率-浙',
                zj.周转 as '周转-浙',
                hainan.省份 as '省份-琼',
                hainan.上柜数 as '上柜数-琼',
                hainan.断码家数 as '断码家数-琼',
                hainan.断码率 as '断码率-琼',
                hainan.周转 as '周转-琼',
                hb.省份 as '省份-鄂',
                hb.上柜数 as '上柜数-鄂',
                hb.断码家数 as '断码家数-鄂',
                hb.断码率 as '断码率-鄂',
                hb.周转 as '周转-鄂',
                hunan.省份 as '省份-湘',
                hunan.上柜数 as '上柜数-湘',
                hunan.断码家数 as '断码家数-湘',
                hunan.断码率 as '断码率-湘',
                hunan.周转 as '周转-湘',
                gs.省份 as '省份-甘',
                gs.上柜数 as '上柜数-甘',
                gs.断码家数 as '断码家数-甘',
                gs.断码率 as '断码率-甘',
                gs.周转 as '周转-甘',
                fj.省份 as '省份-闽',
                fj.上柜数 as '上柜数-闽',
                fj.断码家数 as '断码家数-闽',
                fj.断码率 as '断码率-闽',
                fj.周转 as '周转-闽',
                gz.省份 as '省份-贵',
                gz.上柜数 as '上柜数-贵',
                gz.断码家数 as '断码家数-贵',
                gz.断码率 as '断码率-贵',
                gz.周转 as '周转-贵',
                cq.省份 as '省份-渝',
                cq.上柜数 as '上柜数-渝',
                cq.断码家数 as '断码家数-渝',
                cq.断码率 as '断码率-渝',
                cq.周转 as '周转-渝',
                xx.省份 as '省份-陕',
                xx.上柜数 as '上柜数-陕',
                xx.断码家数 as '断码家数-陕',
                xx.断码率 as '断码率-陕',
                xx.周转 as '周转-陕',
                qh.省份 as '省份-青',
                qh.上柜数 as '上柜数-青',
                qh.断码家数 as '断码家数-青',
                qh.断码率 as '断码率-青',
                qh.周转 as '周转-青'
            FROM
                cwl_duanmalv_table4 AS t4
                left join cwl_duanmalv_table4 as yn on t4.货号=yn.货号 and yn.省份='云南省'
                left join cwl_duanmalv_table4 as sc on t4.货号=sc.货号 and sc.省份='四川省'
                left join cwl_duanmalv_table4 as tj on t4.货号=tj.货号 and tj.省份='天津'
                left join cwl_duanmalv_table4 as nx on t4.货号=nx.货号 and nx.省份='宁夏回族自治区'
                left join cwl_duanmalv_table4 as ah on t4.货号=ah.货号 and ah.省份='安徽省'
                left join cwl_duanmalv_table4 as gd on t4.货号=gd.货号 and gd.省份='广东省'
                left join cwl_duanmalv_table4 as gx on t4.货号=gx.货号 and gx.省份='广西壮族自治区'
                left join cwl_duanmalv_table4 as xj on t4.货号=xj.货号 and xj.省份='新疆维吾尔自治区'
                left join cwl_duanmalv_table4 as jx on t4.货号=jx.货号 and jx.省份='江西省'
                left join cwl_duanmalv_table4 as henan on t4.货号=henan.货号 and henan.省份='河南省'
                left join cwl_duanmalv_table4 as zj on t4.货号=zj.货号 and zj.省份='浙江省'
                left join cwl_duanmalv_table4 as hainan on t4.货号=hainan.货号 and hainan.省份='海南省'
                left join cwl_duanmalv_table4 as hb on t4.货号=hb.货号 and hb.省份='湖北省'
                left join cwl_duanmalv_table4 as hunan on t4.货号=hunan.货号 and hunan.省份='湖南省'
                left join cwl_duanmalv_table4 as gs on t4.货号=gs.货号 and gs.省份='甘肃省'
                left join cwl_duanmalv_table4 as fj on t4.货号=fj.货号 and fj.省份='福建省'
                left join cwl_duanmalv_table4 as gz on t4.货号=gz.货号 and gz.省份='贵州省'
                left join cwl_duanmalv_table4 as cq on t4.货号=cq.货号 and cq.省份='重庆'
                left join cwl_duanmalv_table4 as xx on t4.货号=xx.货号 and xx.省份='陕西省'
                left join cwl_duanmalv_table4 as qh on t4.货号=qh.货号 and qh.省份='青海省'
            --  where sk.省份='浙江省'
            --  and sk.货号='B32502003'
            GROUP BY
            t4.风格, t4.大类, t4.中类, t4.货号
            ORDER BY t4.风格
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店单款断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }    

    // 多选提交参数处理
    public function xmSelectInput($str = "") {
        // $str = "于燕华,周奇志,廖翠芳,张洋涛";

        $exploadDate = explode(',', $str);
        // dump($exploadDate);die;
        $map = "";
        foreach ($exploadDate as $key => $val) {
            $map .=  "'" . $val . "'" . ",";
        }
        // 删除最后的逗号
        $map = mb_substr($map, 0, -1, "UTF-8");
        return $map;
    }

    // 整体 1_1
    public function table1() {
        if (request()->isAjax()) {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);  
            // 筛选条件
            $input = input();
            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (!empty($input['商品负责人'])) {
                // echo $input['商品负责人'];
                $map1Str = $this->xmSelectInput($input['商品负责人']);
                $map1 = " AND t1.商品负责人 IN ({$map1Str})";
            } else {
                $map1 = "";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = $this->xmSelectInput($input['云仓']);
                $map2 = " AND t1.云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = $this->xmSelectInput($input['省份']);
                $map3 = " AND t1.省份 IN ({$map3Str})";
            } else {
                $map3 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map4Str = $this->xmSelectInput($input['经营模式']);
                $map4 = " AND t1.经营模式 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map5Str = $this->xmSelectInput($input['店铺名称']);
                $map5 = " AND t1.店铺名称 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            if (!empty($input['setTime1']) && !empty($input['setTime2'])) {
                // echo $input['商品负责人'];
                // $map0Str = $this->xmSelectInput($input['经营模式']);
                // $map0 = " AND t1.经营模式 IN ({$map4Str})";
                $map0 = "t1.更新日期 IN ('{$input['setTime1']}', '{$input['setTime2']}') ";
                $limitDate["newDate"] = $input['setTime1'];
                $limitDate["oldDate"] = $input['setTime2'];
            } else {
                $map0 = "t1.更新日期 IN ('{$limitDate["newDate"]}', '{$limitDate["oldDate"]}') ";
            }

            $sql = "
                SELECT  
                    t1.商品负责人,t1.云仓,t1.省份,t1.店铺名称,t1.经营模式,
                    t2.`单店排名` as `单店排名-新`,
                    t2.`SKC数-整体` as `SKC数-整体-新`,
                    t2.`齐码率-整体` as `齐码率-整体-新`,
                    t2.`SKC数-TOP实际` as `SKC数-TOP实际-新`,
                    t2.`齐码率-TOP实际` as `齐码率-TOP实际-新`,
                    t2.`SKC数-TOP考核` as `SKC数-TOP考核-新`,
                    t2.`齐码率-TOP实际` as `齐码率-TOP考核-新`,
                    t2.`更新日期` as `更新日期-新`,
                    t3.`单店排名` as `单店排名-旧`,
                    t3.`SKC数-整体` as `SKC数-整体-旧`,
                    t3.`齐码率-整体` as `齐码率-整体-旧`,
                    t3.`SKC数-TOP实际` as `SKC数-TOP实际-旧`,
                    t3.`齐码率-TOP实际` as `齐码率-TOP实际-旧`,
                    t3.`SKC数-TOP考核` as `SKC数-TOP考核-旧`,
                    t3.`齐码率-TOP实际` as `齐码率-TOP考核-旧`,
                    t3.`更新日期` as `更新日期-旧`  
                FROM
                    cwl_duanmalv_table1_1 t1 
                    left join cwl_duanmalv_table1_1 t2 ON t1.店铺名称=t2.店铺名称 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_1 t3 ON t1.店铺名称=t3.店铺名称 and t3.更新日期 = '{$limitDate["oldDate"]}'
                WHERE
                    {$map0}
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                GROUP BY
                    t1.店铺名称
                ORDER BY t2.商品负责人 DESC
                , t2.`单店排名` ASC
            ";
 
            $select = $this->db_easyA->query($sql);

            return json(["code" => "0", "msg" => "", "count" => count($select),  "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);
            return View('table1', [
                'limitDate' => $limitDate
            ]);
        }  
    }

    // 整体 1_2
    public function table1_2() {
        if (request()->isAjax()) {
        // if (1) {
            // 筛选条件
            $input = input();
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);

            if (!empty($input['setTime1']) && !empty($input['setTime2'])) {
                $map0 = "t1.更新日期 IN ('{$input['setTime1']}', '{$input['setTime2']}') ";
                $limitDate["newDate"] = $input['setTime1'];
                $limitDate["oldDate"] = $input['setTime2'];
            } else {
                $map0 = "t1.更新日期 IN ('{$limitDate["newDate"]}', '{$limitDate["oldDate"]}') ";
            }

            $sql = "
                SELECT
                    t1.云仓,t1.商品负责人,
                    t2.`齐码排名` as `齐码排名-新`,
                    t2.`直营-整体` as `直营-整体-新`,
                    t2.`加盟-整体` as `加盟-整体-新`,
                    t2.`合计-整体` as `合计-整体-新`,
                    t2.`直营-TOP实际` as `直营-TOP实际-新`,
                    t2.`加盟-TOP实际` as `加盟-TOP实际-新`,
                    t2.`合计-TOP实际` as `合计-TOP实际-新`,
                    t2.`直营-TOP考核` as `直营-TOP考核-新`,
                    t2.`加盟-TOP考核` as `加盟-TOP考核-新`,
                    t2.`合计-TOP考核` as `合计-TOP考核-新`,
                    t2.`更新日期` as `更新日期-新`,
                    t3.`齐码排名` as `齐码排名-旧`,
                    t3.`直营-整体` as `直营-整体-旧`,
                    t3.`加盟-整体` as `加盟-整体-旧`,
                    t3.`合计-整体` as `合计-整体-旧`,
                    t3.`直营-TOP实际` as `直营-TOP实际-旧`,
                    t3.`加盟-TOP实际` as `加盟-TOP实际-旧`,
                    t3.`合计-TOP实际` as `合计-TOP实际-旧`,
                    t3.`直营-TOP考核` as `直营-TOP考核-旧`,
                    t3.`加盟-TOP考核` as `加盟-TOP考核-旧`,
                    t3.`合计-TOP考核` as `合计-TOP考核-旧`,
                    t3.`更新日期` as `更新日期-旧` 
                FROM
                    cwl_duanmalv_table1_2 t1 
                    left join cwl_duanmalv_table1_2 t2 ON t1.云仓=t2.云仓 and t1.商品负责人=t2.商品负责人 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_2 t3 ON t1.云仓=t3.云仓 and t1.商品负责人=t3.商品负责人 and t3.更新日期 = '{$limitDate["oldDate"]}'
                WHERE
                    {$map0} 
                GROUP BY
                    t1.云仓, t1.商品负责人
                ORDER BY  t2.`齐码排名` ASC
            ";
            $select = $this->db_easyA->query($sql);

            // 统计平均值
            $sql2 = "
                SELECT
                    '合计' as 云仓,
                    '' as 商品负责人,
                    '' as `齐码排名-新`,
                    AVG(t0.`直营-整体-新`) as `直营-整体-新`,
                    AVG(t0.`加盟-整体-新`) as `加盟-整体-新`,
                    AVG(t0.`合计-整体-新`) as `合计-整体-新`,
                    AVG(t0.`直营-TOP实际-新`) as `直营-TOP实际-新`,
                    AVG(t0.`加盟-TOP实际-新`) as `加盟-TOP实际-新`,
                    AVG(t0.`合计-TOP实际-新`) as `合计-TOP实际-新`,
                    AVG(t0.`直营-TOP考核-新`) as `直营-TOP考核-新`,
                    AVG(t0.`加盟-TOP考核-新`) as `加盟-TOP考核-新`,
                    AVG(t0.`合计-TOP考核-新`) as `合计-TOP考核-新`,
                    '' as `更新日期-新`,
                    '' as `齐码排名-旧`,
                    AVG(t0.`直营-整体-旧`) as `直营-整体-旧`,
                    AVG(t0.`加盟-整体-旧`) as `加盟-整体-旧`,
                    AVG(t0.`合计-整体-旧`) as `合计-整体-旧`,
                    AVG(t0.`直营-TOP实际-旧`) as `直营-TOP实际-旧`,
                    AVG(t0.`加盟-TOP实际-旧`) as `加盟-TOP实际-旧`,
                    AVG(t0.`合计-TOP实际-旧`) as `合计-TOP实际-旧`,
                    AVG(t0.`直营-TOP考核-旧`) as `直营-TOP考核-旧`,
                    AVG(t0.`加盟-TOP考核-旧`) as `加盟-TOP考核-旧`,
                    AVG(t0.`合计-TOP考核-旧`) as `合计-TOP考核-旧`,
                    '' as `更新日期-旧`,
                    '' as `差值`
                FROM
                (SELECT
                    t1.云仓,t1.商品负责人,
                    t2.`齐码排名` as `齐码排名-新`,
                    t2.`直营-整体` as `直营-整体-新`,
                    t2.`加盟-整体` as `加盟-整体-新`,
                    t2.`合计-整体` as `合计-整体-新`,
                    t2.`直营-TOP实际` as `直营-TOP实际-新`,        
                    t2.`加盟-TOP实际` as `加盟-TOP实际-新`,
                    t2.`合计-TOP实际` as `合计-TOP实际-新`,
                    t2.`直营-TOP考核` as `直营-TOP考核-新`,
                    t2.`加盟-TOP考核` as `加盟-TOP考核-新`,
                    t2.`合计-TOP考核` as `合计-TOP考核-新`,
                    t2.`更新日期` as `更新日期-新`,
                    t3.`齐码排名` as `齐码排名-旧`,
                    t3.`直营-整体` as `直营-整体-旧`,
                    t3.`加盟-整体` as `加盟-整体-旧`,
                    t3.`合计-整体` as `合计-整体-旧`,
                    t3.`直营-TOP实际` as `直营-TOP实际-旧`,
                    t3.`加盟-TOP实际` as `加盟-TOP实际-旧`,
                    t3.`合计-TOP实际` as `合计-TOP实际-旧`,
                    t3.`直营-TOP考核` as `直营-TOP考核-旧`,
                    t3.`加盟-TOP考核` as `加盟-TOP考核-旧`,
                    t3.`合计-TOP考核` as `合计-TOP考核-旧`,
                    t3.`更新日期` as `更新日期-旧` 
                    FROM
                    cwl_duanmalv_table1_2 t1 
                    left join cwl_duanmalv_table1_2 t2 ON t1.云仓=t2.云仓 and t1.商品负责人=t2.商品负责人 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_2 t3 ON t1.云仓=t3.云仓 and t1.商品负责人=t3.商品负责人 and t3.更新日期 = '{$limitDate["oldDate"]}'
                    WHERE
                    {$map0} 
                    GROUP BY
                    t1.云仓, t1.商品负责人
                    ORDER BY  t2.`齐码排名` ASC) AS t0        
            ";

            // 改良平均值
            $sql3 = "
                SELECT
                    t1.云仓,
                    t1.商品负责人,
                    '' as `齐码排名-新`,
                    t1.`直营-整体` as `直营-整体-新`,
                    t1.`加盟-整体` as `加盟-整体-新`,
                    t1.`合计-整体` as `合计-整体-新`,
                    t1.`直营-TOP实际` as `直营-TOP实际-新`,        
                    t1.`加盟-TOP实际` as `加盟-TOP实际-新`,
                    t1.`合计-TOP实际` as `合计-TOP实际-新`,
                    t1.`直营-TOP考核` as `直营-TOP考核-新`,
                    t1.`加盟-TOP考核` as `加盟-TOP考核-新`,
                    t1.`合计-TOP考核` as `合计-TOP考核-新`,
                    t1.`更新日期` as `更新日期-新`,
                    t2.*
                    FROM
                    cwl_duanmalv_table1_avg t1,  (				
                        SELECT
                            '' as `齐码排名-旧`,
                            `直营-整体` as `直营-整体-旧`,
                            `加盟-整体` as `加盟-整体-旧`,
                            `合计-整体` as `合计-整体-旧`,
                            `直营-TOP实际` as `直营-TOP实际-旧`,
                            `加盟-TOP实际` as `加盟-TOP实际-旧`,
                            `合计-TOP实际` as `合计-TOP实际-旧`,
                            `直营-TOP考核` as `直营-TOP考核-旧`,
                            `加盟-TOP考核` as `加盟-TOP考核-旧`,
                            `合计-TOP考核` as `合计-TOP考核-旧`,
                            `更新日期` as `更新日期-旧` 
                        FROM
                        cwl_duanmalv_table1_avg  
                        WHERE
                        `更新日期` ='{$limitDate["oldDate"]}'
                    ) as t2 
                    WHERE
                    t1.`更新日期` ='{$limitDate["newDate"]}'            
            "; 
            
            $select2 = $this->db_easyA->query($sql3);
            

            // 差值计算
            foreach ($select as $key => $val) {
                if (! empty($val['合计-TOP考核-新']) && !empty($val['合计-TOP考核-旧'])) {
                    $select[$key]['差值'] = round($val['合计-TOP考核-新'] - $val['合计-TOP考核-旧'], 4);
                    if ($select[$key]['差值'] < 0 ) {
                        $select[$key]['差值'] = "<div style='color:red; font-weight:bold;'>" . $this->float1($select[$key]['差值']) . "</div>";
                    } else {
                        $select[$key]['差值'] = $this->float1($select[$key]['差值']);
                    }
                } else {
                    $select[$key]['差值'] = '';
                }
            }

            // 判断是否小于平均值
            foreach ($select as $key => $val) {
                if (!empty($val['直营-整体-新']) && $val['直营-整体-新'] < $select2[0]['直营-整体-新']) {
                    $select[$key]['直营-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-新']) . "</div>";
                } else {
                    $val['直营-整体-新'] ? $select[$key]['直营-整体-新'] = $this->float1($val['直营-整体-新']) :  $select[$key]['直营-整体-新'] = "";
                }
                if (!empty($val['加盟-整体-新']) && $val['加盟-整体-新'] < $select2[0]['加盟-整体-新']) {
                    $select[$key]['加盟-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-新']) . "</div>";
                } else {
                    $val['加盟-整体-新'] ? $select[$key]['加盟-整体-新'] = $this->float1($val['加盟-整体-新']) : $select[$key]['加盟-整体-新'] = "";
                }
                if (!empty($val['合计-整体-新']) && $val['合计-整体-新'] < $select2[0]['合计-整体-新']) {
                    $select[$key]['合计-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-新']) . "</div>";
                } else {
                    $val['合计-整体-新'] ? $select[$key]['合计-整体-新'] = $this->float1($val['合计-整体-新']) : $select[$key]['合计-整体-新'] = "";
                }
                if (!empty($val['直营-TOP实际-新']) && $val['直营-TOP实际-新'] < $select2[0]['直营-TOP实际-新']) {
                    $select[$key]['直营-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-新']) . "</div>";
                } else {
                    $val['直营-TOP实际-新'] ? $select[$key]['直营-TOP实际-新'] = $this->float1($val['直营-TOP实际-新']) : $select[$key]['直营-TOP实际-新'] = "";
                }
                if (!empty($val['加盟-TOP实际-新']) && $val['加盟-TOP实际-新'] < $select2[0]['加盟-TOP实际-新']) {
                    $select[$key]['加盟-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-新']) . "</div>";
                } else {
                    $val['加盟-TOP实际-新'] ? $select[$key]['加盟-TOP实际-新'] = $this->float1($val['加盟-TOP实际-新']) : $select[$key]['加盟-TOP实际-新'] = '';
                }
                if (!empty($val['合计-TOP实际-新']) && $val['合计-TOP实际-新'] < $select2[0]['合计-TOP实际-新']) {
                    $select[$key]['合计-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-新']) . "</div>";
                } else {
                    $val['合计-TOP实际-新'] ? $select[$key]['合计-TOP实际-新'] = $this->float1($val['合计-TOP实际-新']) : $select[$key]['合计-TOP实际-新'] = "";
                }
                if (!empty($val['直营-TOP考核-新']) && $val['直营-TOP考核-新'] < $select2[0]['直营-TOP考核-新']) {
                    $select[$key]['直营-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-新']) . "</div>";
                } else {
                    $val['直营-TOP考核-新'] ? $select[$key]['直营-TOP考核-新'] = $this->float1($val['直营-TOP考核-新']) : $select[$key]['直营-TOP考核-新'] = "";
                }
                if (!empty($val['加盟-TOP考核-新']) && $val['加盟-TOP考核-新'] < $select2[0]['加盟-TOP考核-新']) {
                    $select[$key]['加盟-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-新']) . "</div>";
                } else {
                    $val['加盟-TOP考核-新'] ? $select[$key]['加盟-TOP考核-新'] = $this->float1($val['加盟-TOP考核-新']) : $select[$key]['加盟-TOP考核-新'] = "";
                }
                if (!empty($val['合计-TOP考核-新']) && $val['合计-TOP考核-新'] < $select2[0]['合计-TOP考核-新']) {
                    $select[$key]['合计-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-新']) . "</div>";
                } else {
                    $val['合计-TOP考核-新'] ? $select[$key]['合计-TOP考核-新'] = $this->float1($val['合计-TOP考核-新']) : $select[$key]['合计-TOP考核-新'] = "";
                }

                if (!empty($val['直营-整体-旧']) && $val['直营-整体-旧'] < $select2[0]['直营-整体-旧']) {
                    $select[$key]['直营-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-旧']) . "</div>";
                } else {
                    $val['直营-整体-旧'] ? $select[$key]['直营-整体-旧'] = $this->float1($val['直营-整体-旧']) :  $select[$key]['直营-整体-旧'] = "";
                }
                if (!empty($val['加盟-整体-旧']) && $val['加盟-整体-旧'] < $select2[0]['加盟-整体-旧']) {
                    $select[$key]['加盟-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-旧']) . "</div>";
                } else {
                    $val['加盟-整体-旧'] ? $select[$key]['加盟-整体-旧'] = $this->float1($val['加盟-整体-旧']) : $select[$key]['加盟-整体-旧'] = "";
                }
                if (!empty($val['合计-整体-旧']) && $val['合计-整体-旧'] < $select2[0]['合计-整体-旧']) {
                    $select[$key]['合计-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-旧']) . "</div>";
                } else {
                    $val['合计-整体-旧'] ? $select[$key]['合计-整体-旧'] = $this->float1($val['合计-整体-旧']) : $select[$key]['合计-整体-新'] = "";
                }
                if (!empty($val['直营-TOP实际-旧']) && $val['直营-TOP实际-旧'] < $select2[0]['直营-TOP实际-旧']) {
                    $select[$key]['直营-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-旧']) . "</div>";
                } else {
                    $val['直营-TOP实际-旧'] ? $select[$key]['直营-TOP实际-旧'] = $this->float1($val['直营-TOP实际-旧']) : $select[$key]['直营-TOP实际-旧'] = "";
                }
                if (!empty($val['加盟-TOP实际-旧']) && $val['加盟-TOP实际-旧'] < $select2[0]['加盟-TOP实际-旧']) {
                    $select[$key]['加盟-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-旧']) . "</div>";
                } else {
                    $val['加盟-TOP实际-旧'] ? $select[$key]['加盟-TOP实际-旧'] = $this->float1($val['加盟-TOP实际-旧']) : $select[$key]['加盟-TOP实际-旧'] = '';
                }
                if (!empty($val['合计-TOP实际-旧']) && $val['合计-TOP实际-旧'] < $select2[0]['合计-TOP实际-旧']) {
                    $select[$key]['合计-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-旧']) . "</div>";
                } else {
                    $val['合计-TOP实际-旧'] ? $select[$key]['合计-TOP实际-旧'] = $this->float1($val['合计-TOP实际-旧']) : $select[$key]['合计-TOP实际-旧'] = "";
                }
                if (!empty($val['直营-TOP考核-旧']) && $val['直营-TOP考核-旧'] < $select2[0]['直营-TOP考核-旧']) {
                    $select[$key]['直营-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-旧']) . "</div>";
                } else {
                    $val['直营-TOP考核-旧'] ? $select[$key]['直营-TOP考核-旧'] = $this->float1($val['直营-TOP考核-旧']) : $select[$key]['直营-TOP考核-旧'] = "";
                }
                if (!empty($val['加盟-TOP考核-旧']) && $val['加盟-TOP考核-旧'] < $select2[0]['加盟-TOP考核-旧']) {
                    $select[$key]['加盟-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-旧']) . "</div>";
                } else {
                    $val['加盟-TOP考核-旧'] ? $select[$key]['加盟-TOP考核-旧'] = $this->float1($val['加盟-TOP考核-旧']) : $select[$key]['加盟-TOP考核-旧'] = "";
                }
                if (!empty($val['合计-TOP考核-旧']) && $val['合计-TOP考核-旧'] < $select2[0]['合计-TOP考核-旧']) {
                    $select[$key]['合计-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-旧']) . "</div>";
                } else {
                    $val['合计-TOP考核-旧'] ? $select[$key]['合计-TOP考核-旧'] = $this->float1($val['合计-TOP考核-旧']) : $select[$key]['合计-TOP考核-旧'] = "";
                }                
            }

            foreach ($select2 as $key => $val) {
                if (! empty($val['直营-整体-新'])) {
                    $select2[$key]['直营-整体-新'] = $this->float1($val['直营-整体-新']);
                }
                if (! empty($val['加盟-整体-新'])) {
                    $select2[$key]['加盟-整体-新'] = $this->float1($val['加盟-整体-新']);
                }
                if (! empty($val['合计-整体-新'])) {
                    $select2[$key]['合计-整体-新'] = $this->float1($val['合计-整体-新']);
                }
                if (! empty($val['直营-TOP实际-新'])) {
                    $select2[$key]['直营-TOP实际-新'] = $this->float1($val['直营-TOP实际-新']);
                }
                if (! empty($val['加盟-TOP实际-新'])) {
                    $select2[$key]['加盟-TOP实际-新'] = $this->float1($val['加盟-TOP实际-新']);
                }
                if (! empty($val['合计-TOP实际-新'])) {
                    $select2[$key]['合计-TOP实际-新'] = $this->float1($val['合计-TOP实际-新']);
                }
                if (! empty($val['直营-TOP考核-新'])) {
                    $select2[$key]['直营-TOP考核-新'] = $this->float1($val['直营-TOP考核-新']);
                }
                if (! empty($val['加盟-TOP考核-新'])) {
                    $select2[$key]['加盟-TOP考核-新'] = $this->float1($val['加盟-TOP考核-新']);
                }
                if (! empty($val['合计-TOP考核-新'])) {
                    $select2[$key]['合计-TOP考核-新'] = $this->float1($val['合计-TOP考核-新']);
                }
    
                if (! empty($val['直营-整体-旧'])) {
                    $select2[$key]['直营-整体-旧'] = $this->float1($val['直营-整体-旧']);
                }
                if (! empty($val['加盟-整体-旧'])) {
                    $select2[$key]['加盟-整体-旧'] = $this->float1($val['加盟-整体-旧']);
                }
                if (! empty($val['合计-整体-旧'])) {
                    $select2[$key]['合计-整体-旧'] = $this->float1($val['合计-整体-旧']);
                }
                if (! empty($val['直营-TOP实际-旧'])) {
                    $select2[$key]['直营-TOP实际-旧'] = $this->float1($val['直营-TOP实际-旧']);
                }
                if (! empty($val['加盟-TOP实际-旧'])) {
                    $select2[$key]['加盟-TOP实际-旧'] = $this->float1($val['加盟-TOP实际-旧']);
                }
                if (! empty($val['合计-TOP实际-旧'])) {
                    $select2[$key]['合计-TOP实际-旧'] = $this->float1($val['合计-TOP实际-旧']);
                }
                if (! empty($val['直营-TOP考核-旧'])) {
                    $select2[$key]['直营-TOP考核-旧'] = $this->float1($val['直营-TOP考核-旧']);
                }
                if (! empty($val['加盟-TOP考核-旧'])) {
                    $select2[$key]['加盟-TOP考核-旧'] = $this->float1($val['加盟-TOP考核-旧']);
                }
                if (! empty($val['合计-TOP考核-旧'])) {
                    $select2[$key]['合计-TOP考核-旧'] = $this->float1($val['合计-TOP考核-旧']);
                }
            }

            // 合并
            array_push($select, $select2[0]);
            // echo '<pre>';
            // print_r($select);die;

            return json(["code" => "0", "msg" => "", "count" => count($select),  "data" => $select, 'avg' => $select2, 'create_time' => date('Y-m-d')]);
        } else {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);
            return View('table1_2', [
                'limitDate' => $limitDate
            ]);
        }  
    }


    // 保留1位小数不进位
    public function float1($num = 0) {
        // $num = 12.86;
        $num *= 100;
        return sprintf("%.1f",substr(sprintf("%.2f", $num), 0, -1)) . "%";
    }

    // 整体 1_3
     public function table1_3() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // $pageParams1 = 0;
            // $pageParams2 = 100;

            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);

            if (!empty($input['setTime1']) && !empty($input['setTime2'])) {
                $map0 = "t1.更新日期 IN ('{$input['setTime1']}', '{$input['setTime2']}') ";
                $limitDate["newDate"] = $input['setTime1'];
                $limitDate["oldDate"] = $input['setTime2'];
            } else {
                $map0 = "t1.更新日期 IN ('{$limitDate["newDate"]}', '{$limitDate["oldDate"]}') ";
            }

            $sql = "
                SELECT
                    t1.省份,t1.商品负责人,
                    t2.`齐码排名` as `齐码排名-新`,
                    t2.`直营-整体` as `直营-整体-新`,
                    t2.`加盟-整体` as `加盟-整体-新`,
                    t2.`合计-整体` as `合计-整体-新`,
                    t2.`直营-TOP实际` as `直营-TOP实际-新`,
                    t2.`加盟-TOP实际` as `加盟-TOP实际-新`,
                    t2.`合计-TOP实际` as `合计-TOP实际-新`,
                    t2.`直营-TOP考核` as `直营-TOP考核-新`,
                    t2.`加盟-TOP考核` as `加盟-TOP考核-新`,
                    t2.`合计-TOP考核` as `合计-TOP考核-新`,
                    t2.`更新日期` as `更新日期-新`,
                    t3.`齐码排名` as `齐码排名-旧`,
                    t3.`直营-整体` as `直营-整体-旧`,
                    t3.`加盟-整体` as `加盟-整体-旧`,
                    t3.`合计-整体` as `合计-整体-旧`,
                    t3.`直营-TOP实际` as `直营-TOP实际-旧`,
                    t3.`加盟-TOP实际` as `加盟-TOP实际-旧`,
                    t3.`合计-TOP实际` as `合计-TOP实际-旧`,
                    t3.`直营-TOP考核` as `直营-TOP考核-旧`,
                    t3.`加盟-TOP考核` as `加盟-TOP考核-旧`,
                    t3.`合计-TOP考核` as `合计-TOP考核-旧`,
                    t3.`更新日期` as `更新日期-旧` 
                FROM
                    cwl_duanmalv_table1_3 t1 
                    left join cwl_duanmalv_table1_3 t2 ON t1.省份=t2.省份 and t1.商品负责人=t2.商品负责人 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_3 t3 ON t1.省份=t3.省份 and t1.商品负责人=t3.商品负责人 and t3.更新日期 = '{$limitDate["oldDate"]}'
                WHERE
                    {$map0}
                GROUP BY
                    t1.省份, t1.商品负责人
                ORDER BY  t2.`齐码排名` ASC
            ";

            $sql2 = "
                SELECT
                    '合计' as 省份,
                    '' as 商品负责人,
                    '' as `齐码排名-新`,

                    AVG(t0.`直营-整体-新`) as `直营-整体-新`,
                    AVG(t0.`加盟-整体-新`) as `加盟-整体-新`,
                    AVG(t0.`合计-整体-新`) as `合计-整体-新`,
                    AVG(t0.`直营-TOP实际-新`) as `直营-TOP实际-新`,
                    AVG(t0.`加盟-TOP实际-新`) as `加盟-TOP实际-新`,
                    AVG(t0.`合计-TOP实际-新`) as `合计-TOP实际-新`,
                    AVG(t0.`直营-TOP考核-新`) as `直营-TOP考核-新`,
                    AVG(t0.`加盟-TOP考核-新`) as `加盟-TOP考核-新`,
                    AVG(t0.`合计-TOP考核-新`) as `合计-TOP考核-新`,
                    '' as `更新日期-新`,
                    AVG(t0.`直营-整体-旧`) as `直营-整体-旧`,
                    AVG(t0.`加盟-整体-旧`) as `加盟-整体-旧`,
                    AVG(t0.`合计-整体-旧`) as `合计-整体-旧`,
                    AVG(t0.`直营-TOP实际-旧`) as `直营-TOP实际-旧`,
                    AVG(t0.`加盟-TOP实际-旧`) as `加盟-TOP实际-旧`,
                    AVG(t0.`合计-TOP实际-旧`) as `合计-TOP实际-旧`,
                    AVG(t0.`直营-TOP考核-旧`) as `直营-TOP考核-旧`,
                    AVG(t0.`加盟-TOP考核-旧`) as `加盟-TOP考核-旧`,
                    AVG(t0.`合计-TOP考核-旧`) as `合计-TOP考核-旧`,
                    '' as `更新日期-旧`
            FROM
            (SELECT
                    t1.省份,t1.商品负责人,
                    t2.`齐码排名` as `齐码排名-新`,
                    t2.`直营-整体` as `直营-整体-新`,
                    t2.`加盟-整体` as `加盟-整体-新`,
                    t2.`合计-整体` as `合计-整体-新`,
                    t2.`直营-TOP实际` as `直营-TOP实际-新`,
                    t2.`加盟-TOP实际` as `加盟-TOP实际-新`,
                    t2.`合计-TOP实际` as `合计-TOP实际-新`,
                    t2.`直营-TOP考核` as `直营-TOP考核-新`,
                    t2.`加盟-TOP考核` as `加盟-TOP考核-新`,
                    t2.`合计-TOP考核` as `合计-TOP考核-新`,
                    t2.`更新日期` as `更新日期-新`,
                    t3.`齐码排名` as `齐码排名-旧`,
                    t3.`直营-整体` as `直营-整体-旧`,
                    t3.`加盟-整体` as `加盟-整体-旧`,
                    t3.`合计-整体` as `合计-整体-旧`,
                    t3.`直营-TOP实际` as `直营-TOP实际-旧`,
                    t3.`加盟-TOP实际` as `加盟-TOP实际-旧`,
                    t3.`合计-TOP实际` as `合计-TOP实际-旧`,
                    t3.`直营-TOP考核` as `直营-TOP考核-旧`,
                    t3.`加盟-TOP考核` as `加盟-TOP考核-旧`,
                    t3.`合计-TOP考核` as `合计-TOP考核-旧`,
                    t3.`更新日期` as `更新日期-旧` 
                    FROM
                    cwl_duanmalv_table1_3 t1 
                    left join cwl_duanmalv_table1_3 t2 ON t1.省份=t2.省份 and t1.商品负责人=t2.商品负责人 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_3 t3 ON t1.省份=t3.省份 and t1.商品负责人=t3.商品负责人 and t3.更新日期 = '{$limitDate["oldDate"]}'
                    WHERE
                    {$map0}
                    GROUP BY
                    t1.省份, t1.商品负责人
                    ORDER BY  t2.`齐码排名` ASC) AS t0  
            ";
            $select = $this->db_easyA->query($sql);


            // 改良平均值
            $sql3 = "
            SELECT
                t1.云仓,
                t1.商品负责人,
                '' as `齐码排名-新`,
                t1.`直营-整体` as `直营-整体-新`,
                t1.`加盟-整体` as `加盟-整体-新`,
                t1.`合计-整体` as `合计-整体-新`,
                t1.`直营-TOP实际` as `直营-TOP实际-新`,        
                t1.`加盟-TOP实际` as `加盟-TOP实际-新`,
                t1.`合计-TOP实际` as `合计-TOP实际-新`,
                t1.`直营-TOP考核` as `直营-TOP考核-新`,
                t1.`加盟-TOP考核` as `加盟-TOP考核-新`,
                t1.`合计-TOP考核` as `合计-TOP考核-新`,
                t1.`更新日期` as `更新日期-新`,
                t2.*
                FROM
                cwl_duanmalv_table1_avg t1,  (				
                    SELECT
                        '' as `齐码排名-旧`,
                        `直营-整体` as `直营-整体-旧`,
                        `加盟-整体` as `加盟-整体-旧`,
                        `合计-整体` as `合计-整体-旧`,
                        `直营-TOP实际` as `直营-TOP实际-旧`,
                        `加盟-TOP实际` as `加盟-TOP实际-旧`,
                        `合计-TOP实际` as `合计-TOP实际-旧`,
                        `直营-TOP考核` as `直营-TOP考核-旧`,
                        `加盟-TOP考核` as `加盟-TOP考核-旧`,
                        `合计-TOP考核` as `合计-TOP考核-旧`,
                        `更新日期` as `更新日期-旧` 
                    FROM
                    cwl_duanmalv_table1_avg  
                    WHERE
                    `更新日期` ='{$limitDate["oldDate"]}'
                ) as t2 
                WHERE
                t1.`更新日期` ='{$limitDate["newDate"]}'            
            "; 

            $select2 = $this->db_easyA->query($sql3);

            // 差值计算
            foreach ($select as $key => $val) {
                if (! empty($val['合计-TOP考核-新']) && !empty($val['合计-TOP考核-旧'])) {
                    $select[$key]['差值'] = round($val['合计-TOP考核-新'] - $val['合计-TOP考核-旧'], 4);
                    if ($select[$key]['差值'] < 0 ) {
                        $select[$key]['差值'] = "<div style='color:red; font-weight:bold;'>" . $this->float1($select[$key]['差值']) . "</div>";
                    } else {
                        $select[$key]['差值'] = $this->float1($select[$key]['差值']);
                    }
                } else {
                    $select[$key]['差值'] = '';
                }
            }

            // 判断是否小于平均值
            foreach ($select as $key => $val) {
                if (!empty($val['直营-整体-新']) && $val['直营-整体-新'] < $select2[0]['直营-整体-新']) {
                    $select[$key]['直营-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-新']) . "</div>";
                } else {
                    $val['直营-整体-新'] ? $select[$key]['直营-整体-新'] = $this->float1($val['直营-整体-新']) :  $select[$key]['直营-整体-新'] = "";
                }
                if (!empty($val['加盟-整体-新']) && $val['加盟-整体-新'] < $select2[0]['加盟-整体-新']) {
                    $select[$key]['加盟-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-新']) . "</div>";
                } else {
                    $val['加盟-整体-新'] ? $select[$key]['加盟-整体-新'] = $this->float1($val['加盟-整体-新']) : $select[$key]['加盟-整体-新'] = "";
                }
                if (!empty($val['合计-整体-新']) && $val['合计-整体-新'] < $select2[0]['合计-整体-新']) {
                    $select[$key]['合计-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-新']) . "</div>";
                } else {
                    $val['合计-整体-新'] ? $select[$key]['合计-整体-新'] = $this->float1($val['合计-整体-新']) : $select[$key]['合计-整体-新'] = "";
                }
                if (!empty($val['直营-TOP实际-新']) && $val['直营-TOP实际-新'] < $select2[0]['直营-TOP实际-新']) {
                    $select[$key]['直营-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-新']) . "</div>";
                } else {
                    $val['直营-TOP实际-新'] ? $select[$key]['直营-TOP实际-新'] = $this->float1($val['直营-TOP实际-新']) : $select[$key]['直营-TOP实际-新'] = "";
                }
                if (!empty($val['加盟-TOP实际-新']) && $val['加盟-TOP实际-新'] < $select2[0]['加盟-TOP实际-新']) {
                    $select[$key]['加盟-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-新']) . "</div>";
                } else {
                    $val['加盟-TOP实际-新'] ? $select[$key]['加盟-TOP实际-新'] = $this->float1($val['加盟-TOP实际-新']) : $select[$key]['加盟-TOP实际-新'] = '';
                }
                if (!empty($val['合计-TOP实际-新']) && $val['合计-TOP实际-新'] < $select2[0]['合计-TOP实际-新']) {
                    $select[$key]['合计-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-新']) . "</div>";
                } else {
                    $val['合计-TOP实际-新'] ? $select[$key]['合计-TOP实际-新'] = $this->float1($val['合计-TOP实际-新']) : $select[$key]['合计-TOP实际-新'] = "";
                }
                if (!empty($val['直营-TOP考核-新']) && $val['直营-TOP考核-新'] < $select2[0]['直营-TOP考核-新']) {
                    $select[$key]['直营-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-新']) . "</div>";
                } else {
                    $val['直营-TOP考核-新'] ? $select[$key]['直营-TOP考核-新'] = $this->float1($val['直营-TOP考核-新']) : $select[$key]['直营-TOP考核-新'] = "";
                }
                if (!empty($val['加盟-TOP考核-新']) && $val['加盟-TOP考核-新'] < $select2[0]['加盟-TOP考核-新']) {
                    $select[$key]['加盟-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-新']) . "</div>";
                } else {
                    $val['加盟-TOP考核-新'] ? $select[$key]['加盟-TOP考核-新'] = $this->float1($val['加盟-TOP考核-新']) : $select[$key]['加盟-TOP考核-新'] = "";
                }
                if (!empty($val['合计-TOP考核-新']) && $val['合计-TOP考核-新'] < $select2[0]['合计-TOP考核-新']) {
                    $select[$key]['合计-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-新']) . "</div>";
                } else {
                    $val['合计-TOP考核-新'] ? $select[$key]['合计-TOP考核-新'] = $this->float1($val['合计-TOP考核-新']) : $select[$key]['合计-TOP考核-新'] = "";
                }

                if (!empty($val['直营-整体-旧']) && $val['直营-整体-旧'] < $select2[0]['直营-整体-旧']) {
                    $select[$key]['直营-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-旧']) . "</div>";
                } else {
                    $val['直营-整体-旧'] ? $select[$key]['直营-整体-旧'] = $this->float1($val['直营-整体-旧']) :  $select[$key]['直营-整体-旧'] = "";
                }
                if (!empty($val['加盟-整体-旧']) && $val['加盟-整体-旧'] < $select2[0]['加盟-整体-旧']) {
                    $select[$key]['加盟-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-旧']) . "</div>";
                } else {
                    $val['加盟-整体-旧'] ? $select[$key]['加盟-整体-旧'] = $this->float1($val['加盟-整体-旧']) : $select[$key]['加盟-整体-旧'] = "";
                }
                if (!empty($val['合计-整体-旧']) && $val['合计-整体-旧'] < $select2[0]['合计-整体-旧']) {
                    $select[$key]['合计-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-旧']) . "</div>";
                } else {
                    $val['合计-整体-旧'] ? $select[$key]['合计-整体-旧'] = $this->float1($val['合计-整体-旧']) : $select[$key]['合计-整体-新'] = "";
                }
                if (!empty($val['直营-TOP实际-旧']) && $val['直营-TOP实际-旧'] < $select2[0]['直营-TOP实际-旧']) {
                    $select[$key]['直营-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-旧']) . "</div>";
                } else {
                    $val['直营-TOP实际-旧'] ? $select[$key]['直营-TOP实际-旧'] = $this->float1($val['直营-TOP实际-旧']) : $select[$key]['直营-TOP实际-旧'] = "";
                }
                if (!empty($val['加盟-TOP实际-旧']) && $val['加盟-TOP实际-旧'] < $select2[0]['加盟-TOP实际-旧']) {
                    $select[$key]['加盟-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-旧']) . "</div>";
                } else {
                    $val['加盟-TOP实际-旧'] ? $select[$key]['加盟-TOP实际-旧'] = $this->float1($val['加盟-TOP实际-旧']) : $select[$key]['加盟-TOP实际-旧'] = '';
                }
                if (!empty($val['合计-TOP实际-旧']) && $val['合计-TOP实际-旧'] < $select2[0]['合计-TOP实际-旧']) {
                    $select[$key]['合计-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-旧']) . "</div>";
                } else {
                    $val['合计-TOP实际-旧'] ? $select[$key]['合计-TOP实际-旧'] = $this->float1($val['合计-TOP实际-旧']) : $select[$key]['合计-TOP实际-旧'] = "";
                }
                if (!empty($val['直营-TOP考核-旧']) && $val['直营-TOP考核-旧'] < $select2[0]['直营-TOP考核-旧']) {
                    $select[$key]['直营-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-旧']) . "</div>";
                } else {
                    $val['直营-TOP考核-旧'] ? $select[$key]['直营-TOP考核-旧'] = $this->float1($val['直营-TOP考核-旧']) : $select[$key]['直营-TOP考核-旧'] = "";
                }
                if (!empty($val['加盟-TOP考核-旧']) && $val['加盟-TOP考核-旧'] < $select2[0]['加盟-TOP考核-旧']) {
                    $select[$key]['加盟-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-旧']) . "</div>";
                } else {
                    $val['加盟-TOP考核-旧'] ? $select[$key]['加盟-TOP考核-旧'] = $this->float1($val['加盟-TOP考核-旧']) : $select[$key]['加盟-TOP考核-旧'] = "";
                }
                if (!empty($val['合计-TOP考核-旧']) && $val['合计-TOP考核-旧'] < $select2[0]['合计-TOP考核-旧']) {
                    $select[$key]['合计-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-旧']) . "</div>";
                } else {
                    $val['合计-TOP考核-旧'] ? $select[$key]['合计-TOP考核-旧'] = $this->float1($val['合计-TOP考核-旧']) : $select[$key]['合计-TOP考核-旧'] = "";
                }   
            }

            foreach ($select2 as $key => $val) {
                if (! empty($val['直营-整体-新'])) {
                    $select2[$key]['直营-整体-新'] = $this->float1($val['直营-整体-新']);
                }
                if (! empty($val['加盟-整体-新'])) {
                    $select2[$key]['加盟-整体-新'] = $this->float1($val['加盟-整体-新']);
                }
                if (! empty($val['合计-整体-新'])) {
                    $select2[$key]['合计-整体-新'] = $this->float1($val['合计-整体-新']);
                }
                if (! empty($val['直营-TOP实际-新'])) {
                    $select2[$key]['直营-TOP实际-新'] = $this->float1($val['直营-TOP实际-新']);
                }
                if (! empty($val['加盟-TOP实际-新'])) {
                    $select2[$key]['加盟-TOP实际-新'] = $this->float1($val['加盟-TOP实际-新']);
                }
                if (! empty($val['合计-TOP实际-新'])) {
                    $select2[$key]['合计-TOP实际-新'] = $this->float1($val['合计-TOP实际-新']);
                }
                if (! empty($val['直营-TOP考核-新'])) {
                    $select2[$key]['直营-TOP考核-新'] = $this->float1($val['直营-TOP考核-新']);
                }
                if (! empty($val['加盟-TOP考核-新'])) {
                    $select2[$key]['加盟-TOP考核-新'] = $this->float1($val['加盟-TOP考核-新']);
                }
                if (! empty($val['合计-TOP考核-新'])) {
                    $select2[$key]['合计-TOP考核-新'] = $this->float1($val['合计-TOP考核-新']);
                }
    
                if (! empty($val['直营-整体-旧'])) {
                    $select2[$key]['直营-整体-旧'] = $this->float1($val['直营-整体-旧']);
                }
                if (! empty($val['加盟-整体-旧'])) {
                    $select2[$key]['加盟-整体-旧'] = $this->float1($val['加盟-整体-旧']);
                }
                if (! empty($val['合计-整体-旧'])) {
                    $select2[$key]['合计-整体-旧'] = $this->float1($val['合计-整体-旧']);
                }
                if (! empty($val['直营-TOP实际-旧'])) {
                    $select2[$key]['直营-TOP实际-旧'] = $this->float1($val['直营-TOP实际-旧']);
                }
                if (! empty($val['加盟-TOP实际-旧'])) {
                    $select2[$key]['加盟-TOP实际-旧'] = $this->float1($val['加盟-TOP实际-旧']);
                }
                if (! empty($val['合计-TOP实际-旧'])) {
                    $select2[$key]['合计-TOP实际-旧'] = $this->float1($val['合计-TOP实际-旧']);
                }
                if (! empty($val['直营-TOP考核-旧'])) {
                    $select2[$key]['直营-TOP考核-旧'] = $this->float1($val['直营-TOP考核-旧']);
                }
                if (! empty($val['加盟-TOP考核-旧'])) {
                    $select2[$key]['加盟-TOP考核-旧'] = $this->float1($val['加盟-TOP考核-旧']);
                }
                if (! empty($val['合计-TOP考核-旧'])) {
                    $select2[$key]['合计-TOP考核-旧'] = $this->float1($val['合计-TOP考核-旧']);
                }
            }

            // 合并
            array_push($select, $select2[0]);

            return json(["code" => "0", "msg" => "", "count" => count($select),  "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);
            return View('table1_3', [
                'limitDate' => $limitDate
            ]);
        }  
    }


    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_duanmalv_sk WHERE 商品负责人 IS NOT NULL GROUP BY 商品负责人
        ");
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_duanmalv_sk WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer = $this->db_easyA->query("
            SELECT CustomerName as name, CustomerName as value FROM customer  GROUP BY CustomerName
        ");
        $zhonglei = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM cwl_duanmalv_sk WHERE  二级分类 IS NOT NULL GROUP BY 二级分类
        ");
        $lingxing = $this->db_easyA->query("
            SELECT 领型 as name, 领型 as value FROM cwl_duanmalv_sk WHERE  领型 IS NOT NULL GROUP BY 领型
        ");
        $huohao = $this->db_easyA->query("
        SELECT 货号 as name, 货号 as value FROM cwl_duanmalv_sk WHERE  货号 IS NOT NULL GROUP BY 货号
    ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer17' => $customer17, 'province' => $province, 'customer' => $customer, 'zhonglei' => $zhonglei, 
        'lingxing' => $lingxing, 'huohao' => $huohao]]);
    }

    // public function 

    // 获取每周的周五，周一
    public function duanmalvDateHandle($defaultTime = false) {
        if (! $defaultTime) {
            // echo 111;
            $input = input();
            $setTime1Str = '';
            $setTime2Str = '';
            if (! empty($input['setTime1'])) {
                $setTime1Str = date_to_week($input['setTime1']);
            } 
            if (! empty($input['setTime2'])) {
                $setTime2Str = date_to_week($input['setTime2']);
            } 

            return json([
                'setTime1' => $input['setTime1'],
                'setTime1Str' => $setTime1Str,
                'setTime2' => $input['setTime2'],
                'setTime2Str' => $setTime2Str,
            ]);
        } else {
            // echo 222;
            $time = time();
            $date = date('w', $time);
    
            $newDate = '';
            $oldDate = '';
            
            if ($date == 0) { // 周日
                $newDate = date('Y-m-d', strtotime('-2day', $time)); // 本周五
                $oldDate = date('Y-m-d', strtotime('-6day', $time)); // 本周一
            } elseif ($date == 6) { //周六
                $newDate = date('Y-m-d', strtotime('-1day', $time)); // 本周五
                $oldDate = date('Y-m-d', strtotime('-5day', $time)); // 本周一
            } elseif ($date == 5) { // 周五
                $newDate = date('Y-m-d', strtotime('-0day', $time)); // 本周五
                $oldDate = date('Y-m-d', strtotime('-4day', $time)); // 本周一
            } elseif ($date == 4) { // 周四
                $newDate = date('Y-m-d', strtotime('-3day', $time)); // 本周一
                $oldDate = date('Y-m-d', strtotime('-6day', $time)); // 上周五
            } elseif ($date == 3) { // 周三
                $newDate = date('Y-m-d', strtotime('-2day', $time)); // 本周一
                $oldDate = date('Y-m-d', strtotime('-5day', $time)); // 上周五
            } elseif ($date == 2) { // 周二
                $newDate = date('Y-m-d', strtotime('-1day', $time)); // 本周一
                $oldDate = date('Y-m-d', strtotime('-4day', $time)); // 上周五
            }  elseif ($date == 1) { // 周一
                $newDate = date('Y-m-d', strtotime('-0day', $time)); // 本周一
                $oldDate = date('Y-m-d', strtotime('-3day', $time)); // 上周五
            }     
    
            return [
                'newDate' => $newDate,
                'oldDate' => $oldDate,
                'newDateStr' => date_to_week($newDate),
                'oldDateStr' => date_to_week($oldDate),
            ];
        }
    }
}
