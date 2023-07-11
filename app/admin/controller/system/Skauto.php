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
 * Class Skauto
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="售空自动提醒")
 */
class Skauto extends AdminController
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
        $find_config = $this->db_easyA->table('cwl_skauto_config')->where('id=1')->find();
        
        // dump($select_config );die;

        return View('config', [
            'config' => $find_config,
        ]);
    }

    public function getMapData() {
        // guojingli_shujuyuan_all
    }

    public function saveMap() {
        if (request()->isAjax() && checkAdmin()) {
            $params = input();

            $this->db_easyA->table('cwl_skauto_config')->where('id=1')->strict(false)->update($params);     

            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
    }

    /**
     * @NodeAnotation(title="售空自动跟进表") 
     * 表7
     */
    public function skauto() {
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
            if (!empty($input['售空提醒'])) {
                // echo $input['商品负责人'];
                $map11Str = xmSelectInput($input['售空提醒']);
                $map11 = " AND 售空提醒 IN ({$map11Str})";
            } else {
                $map11 = "";
            }
            if ($input['在途库存'] === '0') {
                // echo $input['商品负责人'];
                $map12Str = xmSelectInput($input['在途库存']);
                $map12 = " AND 在途库存 + 已配未发 <= {$map12Str}";
            } else {
                $map12 = "";
            }

            $sql = "
                SELECT
                    云仓,
                    left(省份, 2) as 省份,
                    商品负责人,经营模式,店铺名称,
                    一级分类,
                    二级分类,
                    分类,风格,货号,零售价,当前零售价,折率,上市天数,
                    首单日期,销售天数,总入量,累销数量,店铺库存,在途库存,已配未发,近一周销,近两周销,云仓可用,售空提醒
                from cwl_skauto_res 
                where 1
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
                order by 
                    省份 asc
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            // die;

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_skauto_res
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
            $find_config = $this->db_easyA->table('cwl_skauto_config')->where('id=1')->find();
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => $find_config['skauto_res_updatetime']]);
        } else {
            return View('skauto', [

            ]);
        }
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_skauto_res WHERE 商品负责人 IS NOT NULL GROUP BY 商品负责人
        ");
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_skauto_res WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_skauto_res GROUP BY 店铺名称
        ");
        $zhonglei = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM cwl_skauto_res WHERE  二级分类 IS NOT NULL GROUP BY 二级分类
        ");
        $fenlei = $this->db_easyA->query("
            SELECT 分类 as name, 分类 as value FROM cwl_duanmalv_sk WHERE 分类 IS NOT NULL GROUP BY 分类
        ");
        $huohao = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM cwl_skauto_res WHERE  货号 IS NOT NULL GROUP BY 货号
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer17' => $customer17, 'province' => $province, 'customer' => $customer, 'zhonglei' => $zhonglei, 
        'fenlei' => $fenlei, 'huohao' => $huohao]]);
    }

    // 下载单店断码明细  sk
    public function excel_skauto() {
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
            if (!empty($input['售空提醒'])) {
                // echo $input['商品负责人'];
                $map11Str = xmSelectInput($input['售空提醒']);
                $map11 = " AND 售空提醒 IN ({$map11Str})";
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
                    cwl_skauto_res
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
                    云仓,
                    left(省份, 2) as 省份,
                    商品负责人,经营模式,店铺名称,
                    一级分类,
                    二级分类,
                    分类,风格,货号,零售价,当前零售价,折率,上市天数,
                    首单日期,销售天数,总入量,累销数量,店铺库存,在途库存,已配未发,近一周销,近两周销,云仓可用,售空提醒
                from cwl_skauto_res 
                where 1
                    {$map}
                order by 省份 asc
            ";
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '售空自动跟进表_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');

            // 下载
            
        }
    }
}
