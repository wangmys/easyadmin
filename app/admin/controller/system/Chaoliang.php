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
 * Class Chaoliang
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="超量提醒")
 */
class Chaoliang extends AdminController
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

    /**
     * 
     */
    public function config() {
        // $find_config = $this->db_easyA->table('cwl_chaoliang_config')->where('id=1')->find();
        
        // dump($select_config );die;

        return View('config', [
            // 'config' => $find_config,
        ]);
    }

    public function getMapData() {
        // guojingli_shujuyuan_all
    }

    public function saveMap() {
        if (request()->isAjax() && checkAdmin()) {
            $params = input();

            $this->db_easyA->table('cwl_chaoliang_config')->where('id=1')->strict(false)->update($params);     

            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
    }

    /**
     * @NodeAnotation(title="参数标准") 
     * 
     */
    public function biaozhun() {
        if (request()->isAjax()) {
            // 筛选条件
            $sql = "
                SELECT
                    *,

                    IFNULL(`单码量00/28/37/44/100/160/S`, 0) + 
                    IFNULL(`单码量29/38/46/105/165/M`, 0) + 
                    IFNULL(`单码量30/39/48/110/170/L`, 0) + 
                    IFNULL(`单码量31/40/50/115/175/XL`, 0) + 
                    IFNULL(`单码量32/41/52/120/180/2XL`, 0) +
                    IFNULL(`单码量33/42/54/125/185/3XL`, 0) + 
                    IFNULL(`单码量34/43/56/190/4XL`, 0) + 
                    IFNULL(`单码量35/44/58/195/5XL`, 0) + 
                    IFNULL(`单码量36/6XL`, 0) + 
                    IFNULL(`单码量38/7XL`, 0) + 
                    IFNULL(`单码量_40`, 0) AS 单码量合计,

                    IFNULL(`周转00/28/37/44/100/160/S`, 0) + 
                    IFNULL(`周转29/38/46/105/165/M`, 0) + 
                    IFNULL(`周转30/39/48/110/170/L`, 0) + 
                    IFNULL(`周转31/40/50/115/175/XL`, 0) + 
                    IFNULL(`周转32/41/52/120/180/2XL`, 0) +
                    IFNULL(`周转33/42/54/125/185/3XL`, 0) + 
                    IFNULL(`周转34/43/56/190/4XL`, 0) + 
                    IFNULL(`周转35/44/58/195/5XL`, 0) + 
                    IFNULL(`周转36/6XL`, 0) + 
                    IFNULL(`周转38/7XL`, 0) + 
                    IFNULL(`周转_40`, 0) AS 周转合计
                from cwl_chaoliang_biaozhun 
                where 1
            ";
            // die;

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                from cwl_chaoliang_biaozhun 
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('biaozhun', [

            ]);
        }
    }

    /**
     * @NodeAnotation(title="单店单款超量") 
     * 
     */
    public function chaoliang() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            foreach ($input as $key => $val) {
                // echo $val;
                if ( $key != '在途库存') {
                    if (empty($val)) {
                        unset($input[$key]);
                    }
                }
            }

            // dump($input);die;
            if (checkAdmin()) {
                if (!empty($input['商品负责人'])) {
                    // echo $input['商品负责人'];
                    $map1Str = xmSelectInput($input['商品负责人']);
                    $map1 = " AND 商品负责人 IN ({$map1Str})";
                } else {
                    $map1 = "";
                }
            } else {
                $admin = session('admin.name');
                $map1 = " AND 商品负责人 IN ('{$admin}')";
            }

            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['云仓']);
                $map2 = " AND 云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['省份']);
                $map3 = " AND 省份 IN ({$map3Str})";
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

            $sql = "
                SELECT
                    left(云仓, 2) AS 云仓,
                    left(省份, 2) AS 省份,
                    商品负责人 AS 商品专员,
                    店铺名称,店铺等级,经营模式,风格,季节,
                    一级分类 as 大类,
                    二级分类 as 中类,
                    分类,
                    领型,
                    货号,
                    上市天数, 
                    `预计00/28/37/44/100/160/S`,
                    `预计29/38/46/105/165/M`,
                    `预计30/39/48/110/170/L`,
                    `预计31/40/50/115/175/XL`,
                    `预计32/41/52/120/180/2XL`,
                    `预计33/42/54/125/185/3XL`,
                    `预计34/43/56/190/4XL`,
                    `预计35/44/58/195/5XL`,
                    `预计36/6XL`,
                    `预计38/7XL`,
                    `预计_40`,
                    `预计库存合计`,
                    `周转00/28/37/44/100/160/S`,
                    `周转29/38/46/105/165/M`,
                    `周转30/39/48/110/170/L`,
                    `周转31/40/50/115/175/XL`,
                    `周转32/41/52/120/180/2XL`,
                    `周转33/42/54/125/185/3XL`,
                    `周转34/43/56/190/4XL`,
                    `周转35/44/58/195/5XL`,
                    `周转36/6XL`,
                    `周转38/7XL`,
                    `周转_40`,
                    `周转合计`,
                    `提醒00/28/37/44/100/160/S`,
                    `提醒29/38/46/105/165/M`,
                    `提醒30/39/48/110/170/L`,
                    `提醒31/40/50/115/175/XL`,
                    `提醒32/41/52/120/180/2XL`,
                    `提醒33/42/54/125/185/3XL`,
                    `提醒34/43/56/190/4XL`,
                    `提醒35/44/58/195/5XL`,
                    `提醒36/6XL`,
                    `提醒38/7XL`,
                    `提醒_40`,
                    当前零售价,
                    零售价,
                    提醒备注
                FROM
                    cwl_chaoliang_sk 
                WHERE 1	
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
                LIMIT {$pageParams1}, {$pageParams2}  
            ";

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_chaoliang_sk
                WHERE 1
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
            ";
            $count = $this->db_easyA->query($sql2);
            $find_config = $this->db_easyA->table('cwl_skauto_config')->where('id=1')->find();
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => $find_config['skauto_res_updatetime']]);
        } else {
            return View('chaoliang', [

            ]);
        }
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_chaoliang_sk WHERE 商品负责人 IS NOT NULL GROUP BY 商品负责人
        ");
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_chaoliang_sk WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_chaoliang_sk GROUP BY 店铺名称
        ");
        $zhonglei = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM cwl_chaoliang_sk WHERE  二级分类 IS NOT NULL GROUP BY 二级分类
        ");
        $fenlei = $this->db_easyA->query("
            SELECT 分类 as name, 分类 as value FROM cwl_chaoliang_sk WHERE 分类 IS NOT NULL GROUP BY 分类
        ");
        $huohao = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM cwl_chaoliang_sk WHERE  货号 IS NOT NULL GROUP BY 货号
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer17' => $customer17, 'province' => $province, 'customer' => $customer, 'zhonglei' => $zhonglei, 
        'fenlei' => $fenlei, 'huohao' => $huohao]]);
    }

    // 下载超量明细  sk
    public function excel_chaoliang() {
        if (request()->isAjax()) {
            $input = input();
            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (checkAdmin()) {
                if (!empty($input['商品负责人'])) {
                    // echo $input['商品负责人'];
                    $map1Str = xmSelectInput($input['商品负责人']);
                    $map1 = " AND 商品负责人 IN ({$map1Str})";
                } else {
                    $map1 = "";
                }
            } else {
                $admin = session('admin.name');
                $map1 = " AND 商品负责人 IN ('{$admin}')";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['云仓']);
                $map2 = " AND 云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['省份']);
                $map3 = " AND 省份 IN ({$map3Str})";
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


            $map = "{$map1}{$map2}{$map3}{$map4}{$map5}{$map6}{$map7}{$map8}{$map9}{$map10}{$map11}";
            $code = rand_code(6);
            cache($code, $map, 3600);

            $sql = "
                SELECT 
                    count(*) as total              
                FROM 
                    cwl_chaoliang_sk
                WHERE 1
                    {$map}            
            ";
            $count = $this->db_easyA->query($sql);
            // dump($count[0]['total']);
            // die;
            // $select = $this->db_easyA->query($sql);
            return json([
                'status' => 1,
                'code' => $code,
                'count' => $count[0]['total']
            ]);
        } else {
            $code = input('code');
            $map = cache($code);
            if (empty($map)) {
                $map = '';
            }
            $sql = "
                SELECT
                    *
                from cwl_chaoliang_sk 
                where 1
                    {$map}
            ";
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '单店单款超量提醒表_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');

            // 下载
            
        }
    }
}
