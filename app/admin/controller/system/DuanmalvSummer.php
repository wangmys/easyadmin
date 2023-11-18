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
 * Class DuanmalvSummer
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="断码率-夏季")
 */
class DuanmalvSummer extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    // 创建时间
    protected $create_time = '';

    // 配置信息
    protected $config = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->create_time = date('Y-m-d H:i:s', time());
        $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where(['id' => 4])->find();
        $this->config = $select_config;
    }

    /**
     * @NodeAnotation(title="断码率系统配置")
     */
    public function config() {
        // $typeQima = $this->getTypeQiMa('in ("下装","内搭","外套","鞋履","松紧长裤","松紧短裤")');
        
        // // 商品负责人
        // $people = SpWwBudongxiaoDetail::getPeople([
        //     ['商品负责人', 'exp', new Raw('IS NOT NULL')]
        // ]);

        // // 
        // $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
        $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where(['id' => 4])->find();
        // $this->top = $config['top'];
        
        // dump($select_config );die;

        return View('config', [
            'config' => $select_config,
        ]);
    }

    public function getConfigMapData() {
        $customer_all = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM customer_first WHERE  RegionID <> 55
        ");

        $select_config = $this->db_easyA->table('cwl_duanmalv_config')->field('不考核门店,不考核货号,季节归集')->where('id=4')->find();
        $select_noCustomer = explode(',', $select_config['不考核门店']);

        // 不考核门店选中
        foreach ($select_noCustomer as $key => $val) {
            foreach ($customer_all as $key2 => $val2) {
                if ($val == $val2['name']) {
                    $customer_all[$key2]['selected'] = true;
                }
            } 
        }

        $goodsNo_all = $this->db_bi->query("
            SELECT
                货号 as name,
                货号 as value
            FROM
                sp_sk 
            GROUP BY
                货号
        ");
        $select_noGoodsNo = explode(',', $select_config['不考核货号']);

        // 不考核货号选中
        foreach ($select_noGoodsNo as $key => $val) {
            foreach ($goodsNo_all as $key2 => $val2) {
                if ($val == $val2['name']) {
                    $goodsNo_all[$key2]['selected'] = true;
                }
            } 
        }

        // 季节
        $season = [
            ['name' => '春季', 'value' => '春季'],
            ['name' => '夏季', 'value' => '夏季'],
            ['name' => '秋季', 'value' => '秋季'],
            ['name' => '冬季', 'value' => '冬季'],
        ];

        $select_season = explode(',', $select_config['季节归集']);
        foreach ($select_season as $key => $val) {
            foreach ($season as $key2 => $val2) {
                if ($val == $val2['name']) {
                    $season[$key2]['selected'] = true;
                }
            } 
        }
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer' => $customer_all, 'goodsNo' => $goodsNo_all, 'season' => $season]]);
    }

    public function saveMap() {
        if (request()->isAjax() && checkAdmin()) {
            $params = input();
            if ($params['折率'] > 1.5) return json(['status' => 0, 'msg' => '折率设置不能大于1.5']);
            if ($params['折率'] < 0.5) return json(['status' => 0, 'msg' => '折率设置不能小于0.5']);

            // dump($params);die;
            $this->db_easyA->table('cwl_duanmalv_config')->where('id=4')->strict(false)->update($params);     
            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
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
                FROM cwl_duanmalv_retail_summer 
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
                FROM cwl_duanmalv_retail_summer 
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
            FROM cwl_duanmalv_retail_summer 
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
     * @NodeAnotation(title="sk") 
     */
    public function sk() {
        if (request()->isAjax()) {
            // $find_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
            $find_config = $this->config;
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
                $map1 = " AND sk.商品负责人 IN ({$map1Str})";
            } else {
                $map1 = "";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = $this->xmSelectInput($input['云仓']);
                $map2 = " AND sk.云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = $this->xmSelectInput($input['省份']);
                $map3 = " AND sk.省份 IN ({$map3Str})";
            } else {
                $map3 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map4Str = $this->xmSelectInput($input['经营模式']);
                $map4 = " AND sk.经营模式 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map5Str = $this->xmSelectInput($input['店铺名称']);
                $map5 = " AND sk.店铺名称 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = $this->xmSelectInput($input['大类']);
                $map6 = " AND sk.一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = $this->xmSelectInput($input['中类']);
                $map7 = " AND sk.二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['领型'])) {
                // echo $input['商品负责人'];
                $map8Str = $this->xmSelectInput($input['领型']);
                $map8 = " AND sk.领型 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = $this->xmSelectInput($input['货号']);
                $map9 = " AND sk.货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = $this->xmSelectInput($input['风格']);
                $map10 = " AND sk.风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            if (!empty($input['是否TOP60'])) {
                // echo $input['商品负责人'];
                $map11Str = $this->xmSelectInput($input['是否TOP60']);
                $map11 = " AND sk.是否TOP60 IN ({$map11Str})";
            } else {
                $map11 = "";
            }
            if (!empty($input['是否TOP60考核款'])) {
                // echo $input['商品负责人'];
                $map12Str = $this->xmSelectInput($input['是否TOP60考核款']);
                $map12 = " AND sk.是否TOP60考核款 IN ({$map12Str})";
            } else {
                $map12 = "";
            }

            $sql = "
                SELECT 
                    left(sk.云仓, 2) as 云仓,
                    sk.店铺名称,
                    sk.商品负责人,
                    left(sk.省份, 2) as 省份,
                    sk.经营模式,
                    sk.年份,
                    sk.店铺等级,
                    sk.季节,
                    sk.一级分类,
                    sk.二级分类,
                    sk.分类,
                    sk.领型,
                    sk.风格,
                    sk.货号,
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
                    sk.预计库存数量,
                    sk.预计库存连码个数,
                    sk.标准齐码识别修订,
                    sk.店铺SKC计数,
                    sk.店铺近一周排名,
                    sk.是否TOP60考核款,
                    sk.是否TOP60,
                    sk.零售价,
                    sk.当前零售价,
                    sk.折率,
                    sk.销售金额,
                    sk.总入量数量,
                    sk.累销数量,
                    h.`实际分配TOP`                
                FROM cwl_duanmalv_sk_summer AS sk
                RIGHT JOIN cwl_duanmalv_handle_1_summer h ON sk.店铺名称 = h.`店铺名称`
                WHERE 1
                    AND sk.`一级分类` = h.`一级分类` 
                    AND sk.`二级分类` = h.`二级分类` 
                    AND sk.领型 = h.领型 
                    AND sk.风格 = h.风格 
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
                    sk.云仓, sk.`商品负责人` desc, sk.店铺名称, sk.风格, sk.季节, sk.一级分类, sk.二级分类, sk.分类, sk.领型
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_sk_summer as sk
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

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => $find_config['sk_updatetime']]);
        } else {
            // 非系统管理员
            if (! checkAdmin()) { 
                $admin = session('admin.name');       
            } else {
                $admin = '';
            }
            return View('sk', [
                'admin' => $admin
            ]);
        }
    }

    // 
    public function test() {
        return View('test', [

        ]);
    }

    /**
     * @NodeAnotation(title="报表规则提醒") 
     */
    public function tips() {
        return View('tips', [

        ]);
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
                FROM cwl_duanmalv_handle_1_summer WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_handle_1_summer
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
            FROM cwl_duanmalv_handle_1_summer WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店TOP60及断码数_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

    /**
     * @NodeAnotation(title="单店品类断码情况table6") 
     */
    public function table6() {
        if (request()->isAjax()) {
            $find_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=4')->find();
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

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

            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = $this->xmSelectInput($input['风格']);
                $map10 = " AND 风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }

            if (!empty($input['领型齐码率'])) {
                // echo $input['商品负责人'];
                $map13Str = $this->xmSelectInput($input['领型齐码率']);
                $map13 = " AND 领型齐码率 < ({$map13Str})";
            } else {
                $map13 = "";
            }

            $map13 = " AND 领型齐码率 >= " . $input['qimalvStart'] / 100 . ' AND 领型齐码率 <= ' . $input['qimalvEnd'] / 100 ;
            
            $sql = "
                SELECT 
                    云仓,
                    left(省份, 2) as 省份,
                    商品负责人,
                    店铺名称,
                    经营模式,
                    风格,
                    一级分类,
                    二级分类,
                    领型,
                    领型SKC数,
                    领型断码数,
                    concat(round(领型齐码率 * 100, 1), '%') as 领型齐码率
                FROM cwl_duanmalv_table6_summer WHERE 1
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
                    {$map7}
                    {$map8}
                    {$map10}
                    {$map13}
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table6_summer
                WHERE 
                    1
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
                    {$map7}
                    {$map8}
                    {$map10}
                    {$map13}
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => $find_config['table6_updatetime']]);
        } else {
            // 非系统管理员
            if (! checkAdmin()) { 
                $admin = session('admin.name');       
            } else {
                $admin = '';
            }
            return View('table6', [
                'admin' => $admin
            ]);
        }        
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
                FROM cwl_duanmalv_table5_summer WHERE 1
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table5_summer
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
            FROM cwl_duanmalv_table5_summer WHERE 1
        ";
        $select = $this->db_easyA->query($sql);
        $header = [];
        foreach($select[0] as $key => $val) {
            $header[] = [$key, $key];
        }
        return Excel::exportData($select, $header, '单店断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');
    }

     /**
     * @NodeAnotation(title="单省单款断码情况table4") 
     */
    public function table4() {
        if (request()->isAjax()) {
            $find_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=4')->find();
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
                    concat(round(yn.断码率 * 100, 1), '%') as '断码率-滇',
                    round(yn.周转, 1) as '周转-滇',
                    sc.省份 as '省份-蜀',
                    sc.上柜数 as '上柜数-蜀',
                    sc.断码家数 as '断码家数-蜀',
                    concat(round(sc.断码率 * 100, 1), '%') as '断码率-蜀',
                    round(sc.周转, 1) as '周转-蜀',
                    tj.省份 as '省份-津',
                    tj.上柜数 as '上柜数-津',
                    tj.断码家数 as '断码家数-津',
                    concat(round(tj.断码率 * 100, 1), '%') as '断码率-津',
                    round(tj.周转, 1) as '周转-津',
                    nx.省份 as '省份-宁',
                    nx.上柜数 as '上柜数-宁',
                    nx.断码家数 as '断码家数-宁',
                    concat(round(nx.断码率 * 100, 1), '%') as '断码率-宁',
                    round(nx.周转, 1) as '周转-宁',
                    ah.省份 as '省份-皖',
                    ah.上柜数 as '上柜数-皖',
                    ah.断码家数 as '断码家数-皖',
                    concat(round(ah.断码率 * 100, 1), '%') as '断码率-皖',
                    round(ah.周转, 1) as '周转-皖',
                    gd.省份 as '省份-粤',
                    gd.上柜数 as '上柜数-粤',
                    gd.断码家数 as '断码家数-粤',
                    concat(round(gd.断码率 * 100, 1), '%') as '断码率-粤',
                    round(gd.周转, 1) as '周转-粤',
                    gx.省份 as '省份-桂',
                    gx.上柜数 as '上柜数-桂',
                    gx.断码家数 as '断码家数-桂',
                    concat(round(gx.断码率 * 100, 1), '%') as '断码率-桂',
                    round(gx.周转, 1) as '周转-桂',
                    xj.省份 as '省份-新',
                    xj.上柜数 as '上柜数-新',
                    xj.断码家数 as '断码家数-新',
                    concat(round(xj.断码率 * 100, 1), '%') as '断码率-新',
                    round(xj.周转, 1) as '周转-新',
                    jx.省份 as '省份-赣',
                    jx.上柜数 as '上柜数-赣',
                    jx.断码家数 as '断码家数-赣',
                    concat(round(jx.断码率 * 100, 1), '%') as '断码率-赣',
                    round(jx.周转, 1) as '周转-赣',
                    henan.省份 as '省份-豫',
                    henan.上柜数 as '上柜数-豫',
                    henan.断码家数 as '断码家数-豫',
                    concat(round(henan.断码率 * 100, 1), '%') as '断码率-豫',
                    round(henan.周转, 1) as '周转-豫',
                    zj.省份 as '省份-浙',
                    zj.上柜数 as '上柜数-浙',
                    zj.断码家数 as '断码家数-浙',
                    concat(round(zj.断码率 * 100, 1), '%') as '断码率-浙',
                    round(zj.周转, 1) as '周转-浙',
                    hainan.省份 as '省份-琼',
                    hainan.上柜数 as '上柜数-琼',
                    hainan.断码家数 as '断码家数-琼',
                    concat(round(hainan.断码率 * 100, 1), '%') as '断码率-琼',
                    round(hainan.周转, 1) as '周转-琼',
                    hb.省份 as '省份-鄂',
                    hb.上柜数 as '上柜数-鄂',
                    hb.断码家数 as '断码家数-鄂',
                    concat(round(hb.断码率 * 100, 1), '%') as '断码率-鄂',
                    round(hb.周转, 1) as '周转-鄂',
                    hunan.省份 as '省份-湘',
                    hunan.上柜数 as '上柜数-湘',
                    hunan.断码家数 as '断码家数-湘',
                    concat(round(hunan.断码率 * 100, 1), '%') as '断码率-湘',
                    round(hunan.周转, 1) as '周转-湘',
                    gs.省份 as '省份-甘',
                    gs.上柜数 as '上柜数-甘',
                    gs.断码家数 as '断码家数-甘',
                    concat(round(gs.断码率 * 100, 1), '%') as '断码率-甘',
                    round(gs.周转, 1) as '周转-甘',
                    fj.省份 as '省份-闽',
                    fj.上柜数 as '上柜数-闽',
                    fj.断码家数 as '断码家数-闽',
                    concat(round(fj.断码率 * 100, 1), '%') as '断码率-闽',
                    round(fj.周转, 1) as '周转-闽',
                    gz.省份 as '省份-贵',
                    gz.上柜数 as '上柜数-贵',
                    gz.断码家数 as '断码家数-贵',
                    concat(round(gz.断码率 * 100, 1), '%') as '断码率-贵',
                    round(gz.周转, 1) as '周转-贵',
                    cq.省份 as '省份-渝',
                    cq.上柜数 as '上柜数-渝',
                    cq.断码家数 as '断码家数-渝',
                    concat(round(cq.断码率 * 100, 1), '%') as '断码率-渝',
                    round(cq.周转, 1) as '周转-渝',
                    xx.省份 as '省份-陕',
                    xx.上柜数 as '上柜数-陕',
                    xx.断码家数 as '断码家数-陕',
                    concat(round(xx.断码率 * 100, 1), '%') as '断码率-陕',
                    round(xx.周转, 1) as '周转-陕',
                    qh.省份 as '省份-青',
                    qh.上柜数 as '上柜数-青',
                    qh.断码家数 as '断码家数-青',
                    concat(round(qh.断码率 * 100, 1), '%') as '断码率-青',
                    round(qh.周转, 1) as '周转-青'
                FROM
                    cwl_duanmalv_table4_summer AS t4
                    left join cwl_duanmalv_table4_summer as yn on t4.货号=yn.货号 and yn.省份='云南省'
                    left join cwl_duanmalv_table4_summer as sc on t4.货号=sc.货号 and sc.省份='四川省'
                    left join cwl_duanmalv_table4_summer as tj on t4.货号=tj.货号 and tj.省份='天津'
                    left join cwl_duanmalv_table4_summer as nx on t4.货号=nx.货号 and nx.省份='宁夏回族自治区'
                    left join cwl_duanmalv_table4_summer as ah on t4.货号=ah.货号 and ah.省份='安徽省'
                    left join cwl_duanmalv_table4_summer as gd on t4.货号=gd.货号 and gd.省份='广东省'
                    left join cwl_duanmalv_table4_summer as gx on t4.货号=gx.货号 and gx.省份='广西壮族自治区'
                    left join cwl_duanmalv_table4_summer as xj on t4.货号=xj.货号 and xj.省份='新疆维吾尔自治区'
                    left join cwl_duanmalv_table4_summer as jx on t4.货号=jx.货号 and jx.省份='江西省'
                    left join cwl_duanmalv_table4_summer as henan on t4.货号=henan.货号 and henan.省份='河南省'
                    left join cwl_duanmalv_table4_summer as zj on t4.货号=zj.货号 and zj.省份='浙江省'
                    left join cwl_duanmalv_table4_summer as hainan on t4.货号=hainan.货号 and hainan.省份='海南省'
                    left join cwl_duanmalv_table4_summer as hb on t4.货号=hb.货号 and hb.省份='湖北省'
                    left join cwl_duanmalv_table4_summer as hunan on t4.货号=hunan.货号 and hunan.省份='湖南省'
                    left join cwl_duanmalv_table4_summer as gs on t4.货号=gs.货号 and gs.省份='甘肃省'
                    left join cwl_duanmalv_table4_summer as fj on t4.货号=fj.货号 and fj.省份='福建省'
                    left join cwl_duanmalv_table4_summer as gz on t4.货号=gz.货号 and gz.省份='贵州省'
                    left join cwl_duanmalv_table4_summer as cq on t4.货号=cq.货号 and cq.省份='重庆'
                    left join cwl_duanmalv_table4_summer as xx on t4.货号=xx.货号 and xx.省份='陕西省'
                    left join cwl_duanmalv_table4_summer as qh on t4.货号=qh.货号 and qh.省份='青海省'
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
                    FROM cwl_duanmalv_table4_summer AS t4
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

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => $find_config['table4_updatetime']]);
        } else {
            // 非系统管理员
            if (! checkAdmin()) { 
                $admin = session('admin.name');       
            } else {
                $admin = '';
            }
            return View('table4', [
                'admin' => $admin
            ]);
        }        
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

     /**
     * @NodeAnotation(title="整体-单店 统计table1") 
     */
    public function table1() {
        if (request()->isAjax()) {
            $find_config = $this->config;
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
                    t2.`齐码率-TOP考核` as `齐码率-TOP考核-新`,
                    t2.`更新日期` as `更新日期-新`,
                    t3.`单店排名` as `单店排名-旧`,
                    t3.`SKC数-整体` as `SKC数-整体-旧`,
                    t3.`齐码率-整体` as `齐码率-整体-旧`,
                    t3.`SKC数-TOP实际` as `SKC数-TOP实际-旧`,
                    t3.`齐码率-TOP实际` as `齐码率-TOP实际-旧`,
                    t3.`SKC数-TOP考核` as `SKC数-TOP考核-旧`,
                    t3.`齐码率-TOP考核` as `齐码率-TOP考核-旧`,
                    t3.`更新日期` as `更新日期-旧`  
                FROM
                    cwl_duanmalv_table1_1 t1 
                    left join cwl_duanmalv_table1_1_summer t2 ON t1.店铺名称=t2.店铺名称 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_1_summer t3 ON t1.店铺名称=t3.店铺名称 and t3.更新日期 = '{$limitDate["oldDate"]}'
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

            return json(["code" => "0", "msg" => "", "count" => count($select),  "data" => $select,  'create_time' => $find_config['table1_updatetime']]);
        } else {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);
            // 非系统管理员
            if (! checkAdmin()) { 
                $admin = session('admin.name');       
            } else {
                $admin = '';
            }
            return View('table1', [
                'limitDate' => $limitDate,
                'config' => $this->config,
                'admin' => $admin
            ]);
        }  
    }

    /**
     * @NodeAnotation(title="整体-单店月份 统计 table1_month") 
     */
    public function table1_month() {
        $find_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=4')->find();
        $today = date('Y-m-d');
        $dayLast30 = date('Y-m-d', strtotime('-1 month'));
        $sql1= "
            SELECT
                    * 
                FROM
                    cwl_duanmalv_week 
                WHERE
                    更新日期 >= '{$dayLast30}' 
                    AND 更新日期 <= '{$today}' 
                ORDER BY
                    更新日期 ASC
            ";
        $select_date = $this->db_easyA->query($sql1);

        // dump($select_date);die;
        // die;
    
        if (request()->isAjax()) {
            // dump($select_date);
            // 9个展示
            if (count($select_date) == 9) {
                $count_more_join = "left join cwl_duanmalv_table1_1_summer t9 ON t0.店铺名称=t9.店铺名称 and t9.更新日期 = '{$select_date[8]["更新日期"]}'";
                $count_more_field = "
                    , concat(round(t9.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t9`
                    , concat(round(t9.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t9`
                    , concat(round(t9.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t9`
                ";
            } elseif (count($select_date) == 10) {
                $count_more_join = "
                    left join cwl_duanmalv_table1_1_summer t9 ON t0.店铺名称=t9.店铺名称 and t9.更新日期 = '{$select_date[8]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t10 ON t0.店铺名称=t10.店铺名称 and t10.更新日期 = '{$select_date[9]["更新日期"]}'
                ";
                $count_more_field = "
                    , concat(round(t9.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t9`
                    , concat(round(t9.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t9`
                    , concat(round(t9.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t9`

                    , concat(round(t10.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t10`
                    , concat(round(t10.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t10`
                    , concat(round(t10.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t10`
                ";
            } else {
                $count_more_join = "";
                $count_more_field = "";
            }

            $sql2 = "
                SELECT  
                    t0.商品负责人,t0.云仓,
                    left(t0.省份, 2) as 省份,
                    t0.店铺名称,t0.经营模式,
                    concat(round(t1.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t1`,
                    concat(round(t1.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t1`,
                    concat(round(t1.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t1`,
    
                    concat(round(t2.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t2`,
                    concat(round(t2.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t2`,
                    concat(round(t2.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t2`,

                    concat(round(t3.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t3`,
                    concat(round(t3.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t3`,
                    concat(round(t3.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t3`,

                    concat(round(t4.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t4`,
                    concat(round(t4.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t4`,
                    concat(round(t4.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t4`,

                    concat(round(t5.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t5`,
                    concat(round(t5.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t5`,
                    concat(round(t5.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t5`,

                    concat(round(t6.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t6`,
                    concat(round(t6.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t6`,
                    concat(round(t6.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t6`,

                    concat(round(t7.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t7`,
                    concat(round(t7.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t7`,
                    concat(round(t7.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t7`,

                    concat(round(t8.`齐码率-整体` * 100, 1), '%') as `齐码率-整体-t8`,
                    concat(round(t8.`齐码率-TOP实际` * 100, 1), '%') as `齐码率-TOP实际-t8`,
                    concat(round(t8.`齐码率-TOP考核` * 100, 1), '%') as `齐码率-TOP考核-t8`

                    {$count_more_field}
                FROM
                    cwl_duanmalv_table1_1_summer t0 
                    left join cwl_duanmalv_table1_1_summer t1 ON t0.店铺名称=t1.店铺名称 and t1.更新日期 = '{$select_date[0]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t2 ON t0.店铺名称=t2.店铺名称 and t2.更新日期 = '{$select_date[1]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t3 ON t0.店铺名称=t3.店铺名称 and t3.更新日期 = '{$select_date[2]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t4 ON t0.店铺名称=t4.店铺名称 and t4.更新日期 = '{$select_date[3]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t5 ON t0.店铺名称=t5.店铺名称 and t5.更新日期 = '{$select_date[4]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t6 ON t0.店铺名称=t6.店铺名称 and t6.更新日期 = '{$select_date[5]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t7 ON t0.店铺名称=t7.店铺名称 and t7.更新日期 = '{$select_date[6]["更新日期"]}'
                    left join cwl_duanmalv_table1_1_summer t8 ON t0.店铺名称=t8.店铺名称 and t8.更新日期 = '{$select_date[7]["更新日期"]}'
                    {$count_more_join}
                WHERE 1

                GROUP BY
                    t0.店铺名称
                ORDER BY t2.商品负责人 DESC, t2.`单店排名` ASC
            ";

            // die;

            $select = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => count($select),  "data" => $select,  'create_time' => $find_config['table1_month_updatetime']]);
        } else {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);
            // dump(count($select_date));die;
            foreach ($select_date as $key => $val) {
                $select_date[$key]['weekStr'] = date_to_week($val['更新日期']);
            }

            // dump($select_date,);die;
            return View('table1_month', [
                'select_date' => $select_date,
            ]);
        }

    }

     /**
     * @NodeAnotation(title="整体-负责人 统计table1_2") 
     */
    public function table1_2() {
        if (request()->isAjax()) {
            $find_config = $this->config;
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
                    cwl_duanmalv_table1_2_summer t1 
                    left join cwl_duanmalv_table1_2_summer t2 ON t1.云仓=t2.云仓 and t1.商品负责人=t2.商品负责人 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_2_summer t3 ON t1.云仓=t3.云仓 and t1.商品负责人=t3.商品负责人 and t3.更新日期 = '{$limitDate["oldDate"]}'
                WHERE
                    {$map0} 
                GROUP BY
                    t1.云仓, t1.商品负责人
                ORDER BY  t2.`齐码排名` ASC
            "; 
            $select = $this->db_easyA->query($sql);

            // echo '<pre>';
            // print_r($select);die;

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
                    cwl_duanmalv_table1_avg_summer t1,  (				
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
                        cwl_duanmalv_table1_avg_summer  
                        WHERE
                        `更新日期` ='{$limitDate["oldDate"]}'
                    ) as t2 
                    WHERE
                    t1.`更新日期` ='{$limitDate["newDate"]}'            
            "; 

            // 合计-新日期
            $select2_new = $this->db_easyA->query("
                SELECT
                    '合计' as 云仓,
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
                    t1.`更新日期` as `更新日期-新`
                FROM
                    cwl_duanmalv_table1_avg_summer t1
                WHERE
                t1.`更新日期` ='{$limitDate["newDate"]}' 
            ");

            // 合计-旧日期
            $select2_old = $this->db_easyA->query("
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
                cwl_duanmalv_table1_avg_summer  
                WHERE
                `更新日期` ='{$limitDate["oldDate"]}'
            ");
            
            // 新旧日期合计不不存在情况处理
            if (!$select2_new) {
                $select2_new[0]['云仓'] = "合计";
                $select2_new[0]['商品负责人'] = "";
                $select2_new[0]['齐码排名-新'] = "";
                $select2_new[0]['直营-整体-新'] = "";
                $select2_new[0]['加盟-整体-新'] = "";
                $select2_new[0]['合计-整体-新'] = "";
                $select2_new[0]['直营-TOP实际-新'] = "";
                $select2_new[0]['加盟-TOP实际-新'] = "";
                $select2_new[0]['合计-TOP实际-新'] = "";
                $select2_new[0]['直营-TOP考核-新'] = "";
                $select2_new[0]['加盟-TOP考核-新'] = "";
                $select2_new[0]['合计-TOP考核-新'] = "";
                $select2_new[0]['更新日期-新'] = "";
            }
            if (!$select2_old) {
                $select2_old[0]['齐码排名-旧'] = "";
                $select2_old[0]['直营-整体-旧'] = "";
                $select2_old[0]['加盟-整体-旧'] = "";
                $select2_old[0]['合计-整体-旧'] = "";
                $select2_old[0]['直营-TOP实际-旧'] = "";
                $select2_old[0]['加盟-TOP实际-旧'] = "";
                $select2_old[0]['合计-TOP实际-旧'] = "";
                $select2_old[0]['直营-TOP考核-旧'] = "";
                $select2_old[0]['加盟-TOP考核-旧'] = "";
                $select2_old[0]['合计-TOP考核-旧'] = "";
                $select2_old[0]['更新日期-旧'] = "";
            }
            // 新旧日期的合计组合
            $select2[0]['云仓'] = "合计";
            $select2[0]['商品负责人'] = $select2_new[0]['商品负责人'];
            $select2[0]['齐码排名-新'] = $select2_new[0]['齐码排名-新'];
            $select2[0]['直营-整体-新'] = $select2_new[0]['直营-整体-新'];
            $select2[0]['加盟-整体-新'] = $select2_new[0]['加盟-整体-新'];
            $select2[0]['合计-整体-新'] = $select2_new[0]['合计-整体-新'];
            $select2[0]['直营-TOP实际-新'] = $select2_new[0]['直营-TOP实际-新'];
            $select2[0]['加盟-TOP实际-新'] = $select2_new[0]['加盟-TOP实际-新'];
            $select2[0]['合计-TOP实际-新'] = $select2_new[0]['合计-TOP实际-新'];
            $select2[0]['直营-TOP考核-新'] = $select2_new[0]['直营-TOP考核-新'];
            $select2[0]['加盟-TOP考核-新'] = $select2_new[0]['加盟-TOP考核-新'];
            $select2[0]['合计-TOP考核-新'] = $select2_new[0]['合计-TOP考核-新'];
            $select2[0]['更新日期-新'] = $select2_new[0]['更新日期-新'];
            $select2[0]['齐码排名-旧'] = $select2_old[0]['齐码排名-旧'];
            $select2[0]['直营-整体-旧'] = $select2_old[0]['直营-整体-旧'];
            $select2[0]['加盟-整体-旧'] = $select2_old[0]['加盟-整体-旧'];
            $select2[0]['合计-整体-旧'] = $select2_old[0]['合计-整体-旧'];
            $select2[0]['直营-TOP实际-旧'] = $select2_old[0]['直营-TOP实际-旧'];
            $select2[0]['加盟-TOP实际-旧'] = $select2_old[0]['加盟-TOP实际-旧'];
            $select2[0]['合计-TOP实际-旧'] = $select2_old[0]['合计-TOP实际-旧'];
            $select2[0]['直营-TOP考核-旧'] = $select2_old[0]['直营-TOP考核-旧'];
            $select2[0]['加盟-TOP考核-旧'] = $select2_old[0]['加盟-TOP考核-旧'];
            $select2[0]['合计-TOP考核-旧'] = $select2_old[0]['合计-TOP考核-旧'];
            $select2[0]['更新日期-旧'] = $select2_old[0]['更新日期-旧'];
    

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

            $直营整体新_Sort = [];
            $加盟整体新_Sort = [];
            $合计整体新_Sort = [];
            $直营TOP实际新_Sort = [];
            $加盟TOP实际新_Sort = [];
            $合计TOP实际新_Sort = [];
            $直营TOP考核新_Sort = [];
            $加盟TOP考核新_Sort = [];
            $合计TOP考核新_Sort = [];

            $直营整体旧_Sort = [];
            $加盟整体旧_Sort = [];
            $合计整体旧_Sort = [];
            $直营TOP实际旧_Sort = [];
            $加盟TOP实际旧_Sort = [];
            $合计TOP实际旧_Sort = [];
            $直营TOP考核旧_Sort = [];
            $加盟TOP考核旧_Sort = [];
            $合计TOP考核旧_Sort = [];

            // 获取各指标排序
            foreach ($select as $key => $val) {
                // 新
                if (! empty($val['直营-整体-新'])) array_push($直营整体新_Sort, $val['直营-整体-新']);
                if (! empty($val['加盟-整体-新'])) array_push($加盟整体新_Sort, $val['加盟-整体-新']);
                if (! empty($val['合计-整体-新'])) array_push($合计整体新_Sort, $val['合计-整体-新']);
                if (! empty($val['直营-TOP实际-新'])) array_push($直营TOP实际新_Sort, $val['直营-TOP实际-新']);
                if (! empty($val['加盟-TOP实际-新'])) array_push($加盟TOP实际新_Sort, $val['加盟-TOP实际-新']);
                if (! empty($val['合计-TOP实际-新'])) array_push($合计TOP实际新_Sort, $val['合计-TOP实际-新']);
                if (! empty($val['直营-TOP考核-新'])) array_push($直营TOP考核新_Sort, $val['直营-TOP考核-新']);
                if (! empty($val['加盟-TOP考核-新'])) array_push($加盟TOP考核新_Sort, $val['加盟-TOP考核-新']);
                if (! empty($val['合计-TOP考核-新'])) array_push($合计TOP考核新_Sort, $val['合计-TOP考核-新']);

                // 旧
                if (! empty($val['直营-整体-旧'])) array_push($直营整体旧_Sort, $val['直营-整体-旧']);
                if (! empty($val['加盟-整体-旧'])) array_push($加盟整体旧_Sort, $val['加盟-整体-旧']);
                if (! empty($val['合计-整体-旧'])) array_push($合计整体旧_Sort, $val['合计-整体-旧']);
                if (! empty($val['直营-TOP实际-旧'])) array_push($直营TOP实际旧_Sort, $val['直营-TOP实际-旧']);
                if (! empty($val['加盟-TOP实际-旧'])) array_push($加盟TOP实际旧_Sort, $val['加盟-TOP实际-旧']);
                if (! empty($val['合计-TOP实际-旧'])) array_push($合计TOP实际旧_Sort, $val['合计-TOP实际-旧']);
                if (! empty($val['直营-TOP考核-旧'])) array_push($直营TOP考核旧_Sort, $val['直营-TOP考核-旧']);
                if (! empty($val['加盟-TOP考核-旧'])) array_push($加盟TOP考核旧_Sort, $val['加盟-TOP考核-旧']);
                if (! empty($val['合计-TOP考核-旧'])) array_push($合计TOP考核旧_Sort, $val['合计-TOP考核-旧']);
            }

            // echo '<pre>';

            // 获取各指标排序
            $直营整体新_Sort = $this->getLastRank($直营整体新_Sort);
            $加盟整体新_Sort = $this->getLastRank($加盟整体新_Sort);
            $合计整体新_Sort = $this->getLastRank($合计整体新_Sort);
            $直营TOP实际新_Sort = $this->getLastRank($直营TOP实际新_Sort);
            $加盟TOP实际新_Sort = $this->getLastRank($加盟TOP实际新_Sort);
            $合计TOP实际新_Sort = $this->getLastRank($合计TOP实际新_Sort);
            $直营TOP考核新_Sort = $this->getLastRank($直营TOP考核新_Sort);
            $加盟TOP考核新_Sort = $this->getLastRank($加盟TOP考核新_Sort);
            $合计TOP考核新_Sort = $this->getLastRank($合计TOP考核新_Sort);

            $直营整体旧_Sort = $this->getLastRank($直营整体旧_Sort);
            $加盟整体旧_Sort = $this->getLastRank($加盟整体旧_Sort);
            $合计整体旧_Sort = $this->getLastRank($合计整体旧_Sort);
            $直营TOP实际旧_Sort = $this->getLastRank($直营TOP实际旧_Sort);
            $加盟TOP实际旧_Sort = $this->getLastRank($加盟TOP实际旧_Sort);
            $合计TOP实际旧_Sort = $this->getLastRank($合计TOP实际旧_Sort);
            $直营TOP考核旧_Sort = $this->getLastRank($直营TOP考核旧_Sort);
            $加盟TOP考核旧_Sort = $this->getLastRank($加盟TOP考核旧_Sort);
            $合计TOP考核旧_Sort = $this->getLastRank($合计TOP考核旧_Sort);

            // 遍历每个元素找最低分
            foreach ($select as $key => $val) {
                // 新时间
                if ($val['直营-整体-新'] == $直营整体新_Sort[0] || $val['直营-整体-新'] == $直营整体新_Sort[1] || $val['直营-整体-新'] == $直营整体新_Sort[2]) {
                    $select[$key]['直营-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-新']) . "</div>";
                } else {
                    $val['直营-整体-新'] ? $select[$key]['直营-整体-新'] = $this->float1($val['直营-整体-新']) : $select[$key]['直营-整体-新'] = "";
                }

                if ($val['加盟-整体-新'] == $加盟整体新_Sort[0] || $val['加盟-整体-新'] == $加盟整体新_Sort[1] || $val['加盟-整体-新'] == $加盟整体新_Sort[2]) {
                    $select[$key]['加盟-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-新']) . "</div>";
                } else {
                    $val['加盟-整体-新'] ? $select[$key]['加盟-整体-新'] = $this->float1($val['加盟-整体-新']) : $select[$key]['加盟-整体-新'] = "";
                }

                if ($val['合计-整体-新'] == $合计整体新_Sort[0] || $val['合计-整体-新'] == $合计整体新_Sort[1] || $val['合计-整体-新'] == $合计整体新_Sort[2]) {
                    $select[$key]['合计-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-新']) . "</div>";
                } else {
                    $val['合计-整体-新'] ? $select[$key]['合计-整体-新'] = $this->float1($val['合计-整体-新']) : $select[$key]['合计-整体-新'] = "";
                }

                if ($val['直营-TOP实际-新'] == $直营TOP实际新_Sort[0] || $val['直营-TOP实际-新'] == $直营TOP实际新_Sort[1] || $val['直营-TOP实际-新'] == $直营TOP实际新_Sort[2]) {
                    $select[$key]['直营-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-新']) . "</div>";
                } else {
                    $val['直营-TOP实际-新'] ? $select[$key]['直营-TOP实际-新'] = $this->float1($val['直营-TOP实际-新']) : $select[$key]['直营-TOP实际-新'] = "";
                }

                if ($val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[0] || $val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[1] || $val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[2]) {
                    $select[$key]['加盟-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-新']) . "</div>";
                } else {
                    $val['加盟-TOP实际-新'] ? $select[$key]['加盟-TOP实际-新'] = $this->float1($val['加盟-TOP实际-新']) : $select[$key]['加盟-TOP实际-新'] = "";
                }

                if ($val['合计-TOP实际-新'] == $合计TOP实际新_Sort[0] || $val['合计-TOP实际-新'] == $合计TOP实际新_Sort[1] || $val['合计-TOP实际-新'] == $合计TOP实际新_Sort[2]) {
                    $select[$key]['合计-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-新']) . "</div>";
                } else {
                    $val['合计-TOP实际-新'] ? $select[$key]['合计-TOP实际-新'] = $this->float1($val['合计-TOP实际-新']) : $select[$key]['合计-TOP实际-新'] = "";
                }

                if ($val['直营-TOP考核-新'] == $直营TOP考核新_Sort[0] || $val['直营-TOP考核-新'] == $直营TOP考核新_Sort[1] || $val['直营-TOP考核-新'] == $直营TOP考核新_Sort[2]) {
                    $select[$key]['直营-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-新']) . "</div>";
                } else {
                    $val['直营-TOP考核-新'] ? $select[$key]['直营-TOP考核-新'] = $this->float1($val['直营-TOP考核-新']) : $select[$key]['直营-TOP考核-新'] = "";
                }

                if ($val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[0] || $val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[1] || $val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[2]) {
                    $select[$key]['加盟-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-新']) . "</div>";
                } else {
                    $val['加盟-TOP考核-新'] ? $select[$key]['加盟-TOP考核-新'] = $this->float1($val['加盟-TOP考核-新']) : $select[$key]['加盟-TOP考核-新'] = "";
                }

                if ($val['合计-TOP考核-新'] == $合计TOP考核新_Sort[0] || $val['合计-TOP考核-新'] == $合计TOP考核新_Sort[1] || $val['合计-TOP考核-新'] == $合计TOP考核新_Sort[2]) {
                    $select[$key]['合计-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-新']) . "</div>";
                } else {
                    $val['合计-TOP考核-新'] ? $select[$key]['合计-TOP考核-新'] = $this->float1($val['合计-TOP考核-新']) : $select[$key]['合计-TOP考核-新'] = "";
                }



                // 旧时间
                if ((!empty($直营整体旧_Sort[0]) && $val['直营-整体-旧'] == $直营整体旧_Sort[0]) || (!empty($直营整体旧_Sort[1]) && $val['直营-整体-旧'] == $直营整体旧_Sort[1]) ||
                 (!empty($直营整体旧_Sort[2]) && $val['直营-整体-旧'] == $直营整体旧_Sort[2])) {
                    $select[$key]['直营-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-旧']) . "</div>";
                } else {
                    $val['直营-整体-旧'] ? $select[$key]['直营-整体-旧'] = $this->float1($val['直营-整体-旧']) : $select[$key]['直营-整体-旧'] = "";
                }
                

                if ((!empty($加盟整体旧_Sort[0]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[0]) || (!empty($加盟整体旧_Sort[1]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[1]) ||
                 (!empty($加盟整体旧_Sort[2]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[2])) {
                    $select[$key]['加盟-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-旧']) . "</div>";
                } else {
                    $val['加盟-整体-旧'] ? $select[$key]['加盟-整体-旧'] = $this->float1($val['加盟-整体-旧']) : $select[$key]['加盟-整体-旧'] = "";
                }

                if ((!empty($合计整体旧_Sort[0]) && $val['合计-整体-旧'] == $合计整体旧_Sort[0]) || (!empty($合计整体旧_Sort[1]) && $val['合计-整体-旧'] == $合计整体旧_Sort[1]) || 
                (!empty($合计整体旧_Sort[2]) && $val['合计-整体-旧'] == $合计整体旧_Sort[2])) {
                    $select[$key]['合计-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-旧']) . "</div>";
                } else {
                    $val['合计-整体-旧'] ? $select[$key]['合计-整体-旧'] = $this->float1($val['合计-整体-旧']) : $select[$key]['合计-整体-旧'] = "";
                }

                if ((!empty($直营TOP实际旧_Sort[0]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[0]) || (!empty($直营TOP实际旧_Sort[1]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[1]) ||
                 (!empty($直营TOP实际旧_Sort[2]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[2])) {
                    $select[$key]['直营-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-旧']) . "</div>";
                } else {
                    $val['直营-TOP实际-旧'] ? $select[$key]['直营-TOP实际-旧'] = $this->float1($val['直营-TOP实际-旧']) : $select[$key]['直营-TOP实际-旧'] = "";
                }

                if ((!empty($加盟TOP实际旧_Sort[0]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[0] )|| (!empty($加盟TOP实际旧_Sort[1]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[1]) ||
                 (!empty($加盟TOP实际旧_Sort[2]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[2])) {
                    $select[$key]['加盟-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-旧']) . "</div>";
                } else {
                    $val['加盟-TOP实际-旧'] ? $select[$key]['加盟-TOP实际-旧'] = $this->float1($val['加盟-TOP实际-旧']) : $select[$key]['加盟-TOP实际-旧'] = "";
                }

                if ((!empty($合计TOP实际旧_Sort[0]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[0]) || (!empty($合计TOP实际旧_Sort[1]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[1]) || 
                (!empty($合计TOP实际旧_Sort[2]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[2])) {
                    $select[$key]['合计-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-旧']) . "</div>";
                } else {
                    $val['合计-TOP实际-旧'] ? $select[$key]['合计-TOP实际-旧'] = $this->float1($val['合计-TOP实际-旧']) : $select[$key]['合计-TOP实际-旧'] = "";
                }

                if ((!empty($直营TOP考核旧_Sort[0]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[0]) || (!empty($直营TOP考核旧_Sort[1]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[1]) || 
                (!empty($直营TOP考核旧_Sort[2]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[2])) {
                    $select[$key]['直营-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-旧']) . "</div>";
                } else {
                    $val['直营-TOP考核-旧'] ? $select[$key]['直营-TOP考核-旧'] = $this->float1($val['直营-TOP考核-旧']) : $select[$key]['直营-TOP考核-旧'] = "";
                }

                if ((!empty($加盟TOP考核旧_Sort[0]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[0]) || (!empty($加盟TOP考核旧_Sort[1]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[1]) || 
                (!empty($加盟TOP考核旧_Sort[2]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[2])) {
                    $select[$key]['加盟-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-旧']) . "</div>";
                } else {
                    $val['加盟-TOP考核-旧'] ? $select[$key]['加盟-TOP考核-旧'] = $this->float1($val['加盟-TOP考核-旧']) : $select[$key]['加盟-TOP考核-旧'] = "";
                }

                if ((!empty($合计TOP考核旧_Sort[0]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[0]) || (!empty($合计TOP考核旧_Sort[1]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[1]) ||
                 (!empty($合计TOP考核旧_Sort[2]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[2])) {
                    $select[$key]['合计-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-旧']) . "</div>";
                } else {
                    $val['合计-TOP考核-旧'] ? $select[$key]['合计-TOP考核-旧'] = $this->float1($val['合计-TOP考核-旧']) : $select[$key]['合计-TOP考核-旧'] = "";
                }
            }


            // die;
            // 底部平均值设置百分百
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

            // die;
            return json(["code" => "0", "msg" => "", "count" => count($select),  "data" => $select, 'avg' => $select2, 'create_time' => $find_config['table1_2_updatetime']]);
        } else {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);
            // 非系统管理员
            if (! checkAdmin()) { 
                $admin = session('admin.name');       
            } else {
                $admin = '';
            }
            // dump($this->config);die;
            return View('table1_2', [
                'limitDate' => $limitDate,
                'config' => $this->config,
                'admin' => $admin
            ]);
        }  
    }

    // 获取倒数排名
    public function getLastRank($arr) {
        // 按值顺序排
        asort($arr);
        // 数字下标重排
        $arr = array_values($arr);
        // print_r($arr); 
        // die;
        return $arr;
    }

    // 保留1位小数不进位
    public function float1($num = 0) {
        // return $num;
        // $num = 12.86;
        $num *= 100;

        // 不四舍五入保留一位
        // return sprintf("%.1f",substr(sprintf("%.2f", $num), 0, -1)) . "%";

        // 四舍五入保留一位
        return round($num, 1) . "%";

    }

     /**
     * @NodeAnotation(title="整体-省份 统计table1_3") 
     */
     public function table1_3() {
        if (request()->isAjax()) {
            $find_config = $this->config;
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
                    left(t1.省份, 2) as 省份,
                    t1.商品负责人,
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
                    cwl_duanmalv_table1_3_summer t1 
                    left join cwl_duanmalv_table1_3_summer t2 ON t1.省份=t2.省份 and t1.商品负责人=t2.商品负责人 and t2.更新日期 = '{$limitDate["newDate"]}'
                    left join cwl_duanmalv_table1_3_summer t3 ON t1.省份=t3.省份 and t1.商品负责人=t3.商品负责人 and t3.更新日期 = '{$limitDate["oldDate"]}'
                WHERE
                    {$map0}
                GROUP BY
                    t1.省份, t1.商品负责人
                ORDER BY  t2.`齐码排名` ASC
                -- LIMIT 2
            ";

            // 旧版统计 平均值
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
                        cwl_duanmalv_table1_3_summer t1 
                        left join cwl_duanmalv_table1_3_summer t2 ON t1.省份=t2.省份 and t1.商品负责人=t2.商品负责人 and t2.更新日期 = '{$limitDate["newDate"]}'
                        left join cwl_duanmalv_table1_3_summer t3 ON t1.省份=t3.省份 and t1.商品负责人=t3.商品负责人 and t3.更新日期 = '{$limitDate["oldDate"]}'
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
                '合计' as 省份,
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
                cwl_duanmalv_table1_avg_summer t1,  (				
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
                    cwl_duanmalv_table1_avg_summer  
                    WHERE
                    `更新日期` ='{$limitDate["oldDate"]}'
                ) as t2 
                WHERE
                t1.`更新日期` ='{$limitDate["newDate"]}'            
            "; 


            // 合计-新日期
            $select2_new = $this->db_easyA->query("
                SELECT
                    '合计' as 省份,
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
                    t1.`更新日期` as `更新日期-新`
                FROM
                    cwl_duanmalv_table1_avg_summer t1
                WHERE
                t1.`更新日期` ='{$limitDate["newDate"]}' 
            ");

            // 合计-旧日期
            $select2_old = $this->db_easyA->query("
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
                cwl_duanmalv_table1_avg_summer  
                WHERE
                `更新日期` ='{$limitDate["oldDate"]}'
            ");
            
            // 新旧日期合计不不存在情况处理
            if (!$select2_new) {
                $select2_new[0]['省份'] = "合计";
                $select2_new[0]['商品负责人'] = "";
                $select2_new[0]['齐码排名-新'] = "";
                $select2_new[0]['直营-整体-新'] = "";
                $select2_new[0]['加盟-整体-新'] = "";
                $select2_new[0]['合计-整体-新'] = "";
                $select2_new[0]['直营-TOP实际-新'] = "";
                $select2_new[0]['加盟-TOP实际-新'] = "";
                $select2_new[0]['合计-TOP实际-新'] = "";
                $select2_new[0]['直营-TOP考核-新'] = "";
                $select2_new[0]['加盟-TOP考核-新'] = "";
                $select2_new[0]['合计-TOP考核-新'] = "";
                $select2_new[0]['更新日期-新'] = "";
            }
            if (!$select2_old) {
                $select2_old[0]['齐码排名-旧'] = "";
                $select2_old[0]['直营-整体-旧'] = "";
                $select2_old[0]['加盟-整体-旧'] = "";
                $select2_old[0]['合计-整体-旧'] = "";
                $select2_old[0]['直营-TOP实际-旧'] = "";
                $select2_old[0]['加盟-TOP实际-旧'] = "";
                $select2_old[0]['合计-TOP实际-旧'] = "";
                $select2_old[0]['直营-TOP考核-旧'] = "";
                $select2_old[0]['加盟-TOP考核-旧'] = "";
                $select2_old[0]['合计-TOP考核-旧'] = "";
                $select2_old[0]['更新日期-旧'] = "";
            }
            // 新旧日期的合计组合
            $select2[0]['省份'] = "合计";
            $select2[0]['商品负责人'] = $select2_new[0]['商品负责人'];
            $select2[0]['齐码排名-新'] = $select2_new[0]['齐码排名-新'];
            $select2[0]['直营-整体-新'] = $select2_new[0]['直营-整体-新'];
            $select2[0]['加盟-整体-新'] = $select2_new[0]['加盟-整体-新'];
            $select2[0]['合计-整体-新'] = $select2_new[0]['合计-整体-新'];
            $select2[0]['直营-TOP实际-新'] = $select2_new[0]['直营-TOP实际-新'];
            $select2[0]['加盟-TOP实际-新'] = $select2_new[0]['加盟-TOP实际-新'];
            $select2[0]['合计-TOP实际-新'] = $select2_new[0]['合计-TOP实际-新'];
            $select2[0]['直营-TOP考核-新'] = $select2_new[0]['直营-TOP考核-新'];
            $select2[0]['加盟-TOP考核-新'] = $select2_new[0]['加盟-TOP考核-新'];
            $select2[0]['合计-TOP考核-新'] = $select2_new[0]['合计-TOP考核-新'];
            $select2[0]['更新日期-新'] = $select2_new[0]['更新日期-新'];
            $select2[0]['齐码排名-旧'] = $select2_old[0]['齐码排名-旧'];
            $select2[0]['直营-整体-旧'] = $select2_old[0]['直营-整体-旧'];
            $select2[0]['加盟-整体-旧'] = $select2_old[0]['加盟-整体-旧'];
            $select2[0]['合计-整体-旧'] = $select2_old[0]['合计-整体-旧'];
            $select2[0]['直营-TOP实际-旧'] = $select2_old[0]['直营-TOP实际-旧'];
            $select2[0]['加盟-TOP实际-旧'] = $select2_old[0]['加盟-TOP实际-旧'];
            $select2[0]['合计-TOP实际-旧'] = $select2_old[0]['合计-TOP实际-旧'];
            $select2[0]['直营-TOP考核-旧'] = $select2_old[0]['直营-TOP考核-旧'];
            $select2[0]['加盟-TOP考核-旧'] = $select2_old[0]['加盟-TOP考核-旧'];
            $select2[0]['合计-TOP考核-旧'] = $select2_old[0]['合计-TOP考核-旧'];
            $select2[0]['更新日期-旧'] = $select2_old[0]['更新日期-旧'];

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

            $直营整体新_Sort = [];
            $加盟整体新_Sort = [];
            $合计整体新_Sort = [];
            $直营TOP实际新_Sort = [];
            $加盟TOP实际新_Sort = [];
            $合计TOP实际新_Sort = [];
            $直营TOP考核新_Sort = [];
            $加盟TOP考核新_Sort = [];
            $合计TOP考核新_Sort = [];

            $直营整体旧_Sort = [];
            $加盟整体旧_Sort = [];
            $合计整体旧_Sort = [];
            $直营TOP实际旧_Sort = [];
            $加盟TOP实际旧_Sort = [];
            $合计TOP实际旧_Sort = [];
            $直营TOP考核旧_Sort = [];
            $加盟TOP考核旧_Sort = [];
            $合计TOP考核旧_Sort = [];

            // 获取各指标排序
            foreach ($select as $key => $val) {
                // 新
                if (! empty($val['直营-整体-新'])) array_push($直营整体新_Sort, $val['直营-整体-新']);
                if (! empty($val['加盟-整体-新'])) array_push($加盟整体新_Sort, $val['加盟-整体-新']);
                if (! empty($val['合计-整体-新'])) array_push($合计整体新_Sort, $val['合计-整体-新']);
                if (! empty($val['直营-TOP实际-新'])) array_push($直营TOP实际新_Sort, $val['直营-TOP实际-新']);
                if (! empty($val['加盟-TOP实际-新'])) array_push($加盟TOP实际新_Sort, $val['加盟-TOP实际-新']);
                if (! empty($val['合计-TOP实际-新'])) array_push($合计TOP实际新_Sort, $val['合计-TOP实际-新']);
                if (! empty($val['直营-TOP考核-新'])) array_push($直营TOP考核新_Sort, $val['直营-TOP考核-新']);
                if (! empty($val['加盟-TOP考核-新'])) array_push($加盟TOP考核新_Sort, $val['加盟-TOP考核-新']);
                if (! empty($val['合计-TOP考核-新'])) array_push($合计TOP考核新_Sort, $val['合计-TOP考核-新']);

                // 旧
                if (! empty($val['直营-整体-旧'])) array_push($直营整体旧_Sort, $val['直营-整体-旧']);
                if (! empty($val['加盟-整体-旧'])) array_push($加盟整体旧_Sort, $val['加盟-整体-旧']);
                if (! empty($val['合计-整体-旧'])) array_push($合计整体旧_Sort, $val['合计-整体-旧']);
                if (! empty($val['直营-TOP实际-旧'])) array_push($直营TOP实际旧_Sort, $val['直营-TOP实际-旧']);
                if (! empty($val['加盟-TOP实际-旧'])) array_push($加盟TOP实际旧_Sort, $val['加盟-TOP实际-旧']);
                if (! empty($val['合计-TOP实际-旧'])) array_push($合计TOP实际旧_Sort, $val['合计-TOP实际-旧']);
                if (! empty($val['直营-TOP考核-旧'])) array_push($直营TOP考核旧_Sort, $val['直营-TOP考核-旧']);
                if (! empty($val['加盟-TOP考核-旧'])) array_push($加盟TOP考核旧_Sort, $val['加盟-TOP考核-旧']);
                if (! empty($val['合计-TOP考核-旧'])) array_push($合计TOP考核旧_Sort, $val['合计-TOP考核-旧']);
            }

            // echo '<pre>';

            // 获取各指标排序
            $直营整体新_Sort = $this->getLastRank($直营整体新_Sort);
            $加盟整体新_Sort = $this->getLastRank($加盟整体新_Sort);
            $合计整体新_Sort = $this->getLastRank($合计整体新_Sort);
            $直营TOP实际新_Sort = $this->getLastRank($直营TOP实际新_Sort);
            $加盟TOP实际新_Sort = $this->getLastRank($加盟TOP实际新_Sort);
            $合计TOP实际新_Sort = $this->getLastRank($合计TOP实际新_Sort);
            $直营TOP考核新_Sort = $this->getLastRank($直营TOP考核新_Sort);
            $加盟TOP考核新_Sort = $this->getLastRank($加盟TOP考核新_Sort);
            $合计TOP考核新_Sort = $this->getLastRank($合计TOP考核新_Sort);

            $直营整体旧_Sort = $this->getLastRank($直营整体旧_Sort);
            $加盟整体旧_Sort = $this->getLastRank($加盟整体旧_Sort);
            $合计整体旧_Sort = $this->getLastRank($合计整体旧_Sort);
            $直营TOP实际旧_Sort = $this->getLastRank($直营TOP实际旧_Sort);
            $加盟TOP实际旧_Sort = $this->getLastRank($加盟TOP实际旧_Sort);
            $合计TOP实际旧_Sort = $this->getLastRank($合计TOP实际旧_Sort);
            $直营TOP考核旧_Sort = $this->getLastRank($直营TOP考核旧_Sort);
            $加盟TOP考核旧_Sort = $this->getLastRank($加盟TOP考核旧_Sort);
            $合计TOP考核旧_Sort = $this->getLastRank($合计TOP考核旧_Sort);

            // 遍历每个元素找最低分
            foreach ($select as $key => $val) {
                // 新时间
                if ($val['直营-整体-新'] == $直营整体新_Sort[0] || $val['直营-整体-新'] == $直营整体新_Sort[1] || $val['直营-整体-新'] == $直营整体新_Sort[2] 
                || $val['直营-整体-新'] == $直营整体新_Sort[3] || $val['直营-整体-新'] == $直营整体新_Sort[4]) {
                    $select[$key]['直营-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-新']) . "</div>";
                } else {
                    $val['直营-整体-新'] ? $select[$key]['直营-整体-新'] = $this->float1($val['直营-整体-新']) : $select[$key]['直营-整体-新'] = "";
                }

                if ($val['加盟-整体-新'] == $加盟整体新_Sort[0] || $val['加盟-整体-新'] == $加盟整体新_Sort[1] || $val['加盟-整体-新'] == $加盟整体新_Sort[2]
                || $val['加盟-整体-新'] == $加盟整体新_Sort[3] || $val['加盟-整体-新'] == $加盟整体新_Sort[4]) {
                    $select[$key]['加盟-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-新']) . "</div>";
                } else {
                    $val['加盟-整体-新'] ? $select[$key]['加盟-整体-新'] = $this->float1($val['加盟-整体-新']) : $select[$key]['加盟-整体-新'] = "";
                }

                if ($val['合计-整体-新'] == $合计整体新_Sort[0] || $val['合计-整体-新'] == $合计整体新_Sort[1] || $val['合计-整体-新'] == $合计整体新_Sort[2]
                || $val['合计-整体-新'] == $合计整体新_Sort[3] || $val['合计-整体-新'] == $合计整体新_Sort[4]) {
                    $select[$key]['合计-整体-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-新']) . "</div>";
                } else {
                    $val['合计-整体-新'] ? $select[$key]['合计-整体-新'] = $this->float1($val['合计-整体-新']) : $select[$key]['合计-整体-新'] = "";
                }

                if ($val['直营-TOP实际-新'] == $直营TOP实际新_Sort[0] || $val['直营-TOP实际-新'] == $直营TOP实际新_Sort[1] || $val['直营-TOP实际-新'] == $直营TOP实际新_Sort[2]
                || $val['直营-TOP实际-新'] == $直营TOP实际新_Sort[3] || $val['直营-TOP实际-新'] == $直营TOP实际新_Sort[4]) {
                    $select[$key]['直营-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-新']) . "</div>";
                } else {
                    $val['直营-TOP实际-新'] ? $select[$key]['直营-TOP实际-新'] = $this->float1($val['直营-TOP实际-新']) : $select[$key]['直营-TOP实际-新'] = "";
                }

                if ($val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[0] || $val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[1] || $val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[2]
                || $val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[3] || $val['加盟-TOP实际-新'] == $加盟TOP实际新_Sort[4]) {
                    $select[$key]['加盟-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-新']) . "</div>";
                } else {
                    $val['加盟-TOP实际-新'] ? $select[$key]['加盟-TOP实际-新'] = $this->float1($val['加盟-TOP实际-新']) : $select[$key]['加盟-TOP实际-新'] = "";
                }

                if ($val['合计-TOP实际-新'] == $合计TOP实际新_Sort[0] || $val['合计-TOP实际-新'] == $合计TOP实际新_Sort[1] || $val['合计-TOP实际-新'] == $合计TOP实际新_Sort[2]
                || $val['合计-TOP实际-新'] == $合计TOP实际新_Sort[3] || $val['合计-TOP实际-新'] == $合计TOP实际新_Sort[4]) {
                    $select[$key]['合计-TOP实际-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-新']) . "</div>";
                } else {
                    $val['合计-TOP实际-新'] ? $select[$key]['合计-TOP实际-新'] = $this->float1($val['合计-TOP实际-新']) : $select[$key]['合计-TOP实际-新'] = "";
                }

                if ($val['直营-TOP考核-新'] == $直营TOP考核新_Sort[0] || $val['直营-TOP考核-新'] == $直营TOP考核新_Sort[1] || $val['直营-TOP考核-新'] == $直营TOP考核新_Sort[2]
                || $val['直营-TOP考核-新'] == $直营TOP考核新_Sort[3] || $val['直营-TOP考核-新'] == $直营TOP考核新_Sort[4]) {
                    $select[$key]['直营-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-新']) . "</div>";
                } else {
                    $val['直营-TOP考核-新'] ? $select[$key]['直营-TOP考核-新'] = $this->float1($val['直营-TOP考核-新']) : $select[$key]['直营-TOP考核-新'] = "";
                }

                if ($val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[0] || $val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[1] || $val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[2]
                || $val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[3] || $val['加盟-TOP考核-新'] == $加盟TOP考核新_Sort[4]) {
                    $select[$key]['加盟-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-新']) . "</div>";
                } else {
                    $val['加盟-TOP考核-新'] ? $select[$key]['加盟-TOP考核-新'] = $this->float1($val['加盟-TOP考核-新']) : $select[$key]['加盟-TOP考核-新'] = "";
                }

                if ($val['合计-TOP考核-新'] == $合计TOP考核新_Sort[0] || $val['合计-TOP考核-新'] == $合计TOP考核新_Sort[1] || $val['合计-TOP考核-新'] == $合计TOP考核新_Sort[2]
                || $val['合计-TOP考核-新'] == $合计TOP考核新_Sort[3] || $val['合计-TOP考核-新'] == $合计TOP考核新_Sort[4]) {
                    $select[$key]['合计-TOP考核-新'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-新']) . "</div>";
                } else {
                    $val['合计-TOP考核-新'] ? $select[$key]['合计-TOP考核-新'] = $this->float1($val['合计-TOP考核-新']) : $select[$key]['合计-TOP考核-新'] = "";
                }


                
                // 旧时间
                if (!empty($直营整体旧_Sort[0]) && $val['直营-整体-旧'] == $直营整体旧_Sort[0] || 
                    !empty($直营整体旧_Sort[1]) && $val['直营-整体-旧'] == $直营整体旧_Sort[1] || 
                    !empty($直营整体旧_Sort[2]) && $val['直营-整体-旧'] == $直营整体旧_Sort[2] || 
                    !empty($直营整体旧_Sort[3]) && $val['直营-整体-旧'] == $直营整体旧_Sort[3] || 
                    !empty($直营整体旧_Sort[4]) && $val['直营-整体-旧'] == $直营整体旧_Sort[4]) {
                    $select[$key]['直营-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-整体-旧']) . "</div>";
                } else {
                    $val['直营-整体-旧'] ? $select[$key]['直营-整体-旧'] = $this->float1($val['直营-整体-旧']) : $select[$key]['直营-整体-旧'] = "";
                }

                if (!empty($加盟整体旧_Sort[0]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[0] || 
                    !empty($加盟整体旧_Sort[1]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[1] || 
                    !empty($加盟整体旧_Sort[2]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[2] || 
                    !empty($加盟整体旧_Sort[3]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[3] || 
                    !empty($加盟整体旧_Sort[4]) && $val['加盟-整体-旧'] == $加盟整体旧_Sort[4]) {
                    $select[$key]['加盟-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-整体-旧']) . "</div>";
                } else {
                    $val['加盟-整体-旧'] ? $select[$key]['加盟-整体-旧'] = $this->float1($val['加盟-整体-旧']) : $select[$key]['加盟-整体-旧'] = "";
                }

                if (!empty($合计整体旧_Sort[0]) && $val['合计-整体-旧'] == $合计整体旧_Sort[0] || 
                    !empty($合计整体旧_Sort[1]) && $val['合计-整体-旧'] == $合计整体旧_Sort[1] ||
                    !empty($合计整体旧_Sort[2]) && $val['合计-整体-旧'] == $合计整体旧_Sort[2] || 
                    !empty($合计整体旧_Sort[3]) && $val['合计-整体-旧'] == $合计整体旧_Sort[3] || 
                    !empty($合计整体旧_Sort[4]) && $val['合计-整体-旧'] == $合计整体旧_Sort[4]) {
                    $select[$key]['合计-整体-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-整体-旧']) . "</div>";
                } else {
                    $val['合计-整体-旧'] ? $select[$key]['合计-整体-旧'] = $this->float1($val['合计-整体-旧']) : $select[$key]['合计-整体-旧'] = "";
                }

                if (!empty($直营TOP实际旧_Sort[0]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[0] ||
                    !empty($直营TOP实际旧_Sort[1]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[1] || 
                    !empty($直营TOP实际旧_Sort[2]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[2] || 
                    !empty($直营TOP实际旧_Sort[3]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[3] || 
                    !empty($直营TOP实际旧_Sort[4]) && $val['直营-TOP实际-旧'] == $直营TOP实际旧_Sort[4]) {
                    $select[$key]['直营-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP实际-旧']) . "</div>";
                } else {
                    $val['直营-TOP实际-旧'] ? $select[$key]['直营-TOP实际-旧'] = $this->float1($val['直营-TOP实际-旧']) : $select[$key]['直营-TOP实际-旧'] = "";
                }

                if (!empty($加盟TOP实际旧_Sort[0]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[0] || 
                    !empty($加盟TOP实际旧_Sort[1]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[1] || 
                    !empty($加盟TOP实际旧_Sort[2]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[2] || 
                    !empty($加盟TOP实际旧_Sort[3]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[3] || 
                    !empty($加盟TOP实际旧_Sort[4]) && $val['加盟-TOP实际-旧'] == $加盟TOP实际旧_Sort[4]) {
                    $select[$key]['加盟-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP实际-旧']) . "</div>";
                } else {
                    $val['加盟-TOP实际-旧'] ? $select[$key]['加盟-TOP实际-旧'] = $this->float1($val['加盟-TOP实际-旧']) : $select[$key]['加盟-TOP实际-旧'] = "";
                }

                if (!empty($合计TOP实际旧_Sort[0]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[0] || 
                    !empty($合计TOP实际旧_Sort[1]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[1] || 
                    !empty($合计TOP实际旧_Sort[2]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[2] || 
                    !empty($合计TOP实际旧_Sort[3]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[3] || 
                    !empty($合计TOP实际旧_Sort[4]) && $val['合计-TOP实际-旧'] == $合计TOP实际旧_Sort[4]) {
                    $select[$key]['合计-TOP实际-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP实际-旧']) . "</div>";
                } else {
                    $val['合计-TOP实际-旧'] ? $select[$key]['合计-TOP实际-旧'] = $this->float1($val['合计-TOP实际-旧']) : $select[$key]['合计-TOP实际-旧'] = "";
                }

                if (!empty($直营TOP考核旧_Sort[0]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[0] || 
                    !empty($直营TOP考核旧_Sort[1]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[1] || 
                    !empty($直营TOP考核旧_Sort[2]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[2] || 
                    !empty($直营TOP考核旧_Sort[3]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[3] || 
                    !empty($直营TOP考核旧_Sort[4]) && $val['直营-TOP考核-旧'] == $直营TOP考核旧_Sort[4]) {
                    $select[$key]['直营-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['直营-TOP考核-旧']) . "</div>";
                } else {
                    $val['直营-TOP考核-旧'] ? $select[$key]['直营-TOP考核-旧'] = $this->float1($val['直营-TOP考核-旧']) : $select[$key]['直营-TOP考核-旧'] = "";
                }

                if (!empty($加盟TOP考核旧_Sort[0]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[0] ||
                    !empty($加盟TOP考核旧_Sort[1]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[1] || 
                    !empty($加盟TOP考核旧_Sort[2]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[2] || 
                    !empty($加盟TOP考核旧_Sort[3]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[3] || 
                    !empty($加盟TOP考核旧_Sort[4]) && $val['加盟-TOP考核-旧'] == $加盟TOP考核旧_Sort[4]) {
                    $select[$key]['加盟-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['加盟-TOP考核-旧']) . "</div>";
                } else {
                    $val['加盟-TOP考核-旧'] ? $select[$key]['加盟-TOP考核-旧'] = $this->float1($val['加盟-TOP考核-旧']) : $select[$key]['加盟-TOP考核-旧'] = "";
                }

                if (!empty($合计TOP考核旧_Sort[0]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[0] || 
                !empty($合计TOP考核旧_Sort[1]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[1] || 
                !empty($合计TOP考核旧_Sort[2]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[2] || 
                !empty($合计TOP考核旧_Sort[3]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[3] || 
                !empty($合计TOP考核旧_Sort[4]) && $val['合计-TOP考核-旧'] == $合计TOP考核旧_Sort[4]) {
                    $select[$key]['合计-TOP考核-旧'] = "<div style='color:red; font-weight:bold; background:yellow;'>" . $this->float1($val['合计-TOP考核-旧']) . "</div>";
                } else {
                    $val['合计-TOP考核-旧'] ? $select[$key]['合计-TOP考核-旧'] = $this->float1($val['合计-TOP考核-旧']) : $select[$key]['合计-TOP考核-旧'] = "";
                }
            }

            // 底部统计使用百分比
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

            return json(["code" => "0", "msg" => "", "count" => count($select),  "data" => $select,  'create_time' => $find_config['table1_3_updatetime']]);
        } else {
            // 目前时间该展示的两个时间 
            $limitDate = $this->duanmalvDateHandle(true);
            // 非系统管理员
            if (! checkAdmin()) { 
                $admin = session('admin.name');       
            } else {
                $admin = '';
            }
            return View('table1_3', [
                'limitDate' => $limitDate,
                'config' => $this->config,
                'admin' => $admin
            ]);
        }  
    }


    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // $customer_all = $this->db_easyA->query("
        //     SELECT 店铺名称 as name, 店铺名称 as value FROM customer_first WHERE  RegionID <> 55
        // ");

        // $select_config = $this->db_easyA->table('cwl_duanmalv_config')->field('不考核门店')->where('id=1')->find();
        // $select_noCustomer = explode(',', $select_config['不考核门店']);

        // // 不考核门店选中
        // foreach ($select_noCustomer as $key => $val) {
        //     foreach ($customer_all as $key2 => $val2) {
        //         if ($val == $val2['name']) {
        //             $customer_all[$key2]['selected'] = true;
        //         }
        //     } 
        // }

        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_duanmalv_sk_summer WHERE 商品负责人 IS NOT NULL GROUP BY 商品负责人
        ");
        // 商品负责人
        if (! checkAdmin()) { 
            // $admin = session('admin.name');
            // 不考核门店选中
            foreach ($customer17 as $key => $val) {
                if ($val['name'] == session('admin.name')) {
                    $customer17[$key]['selected'] = true;
                    break;
                }
            }            
        }
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_duanmalv_sk_summer WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer = $this->db_easyA->query("
            SELECT CustomerName as name, CustomerName as value FROM customer  GROUP BY CustomerName
        ");
        $zhonglei = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM cwl_duanmalv_sk_summer WHERE  二级分类 IS NOT NULL GROUP BY 二级分类
        ");
        $lingxing = $this->db_easyA->query("
            SELECT 领型 as name, 领型 as value FROM cwl_duanmalv_sk_summer WHERE  领型 IS NOT NULL GROUP BY 领型
        ");
        $huohao = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM cwl_duanmalv_sk_summer WHERE  货号 IS NOT NULL GROUP BY 货号
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