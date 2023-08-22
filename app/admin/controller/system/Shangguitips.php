<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class Chaoliang
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="新品上柜提醒")
 */
class Shangguitips extends AdminController
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
        $find_config = $this->db_easyA->table('cwl_shangguitips_config')->where('id=1')->find();
        
        // dump($select_config );die;

        return View('config', [
            'config' => $find_config,
        ]);
    }

    /**
     * @NodeAnotation(title="新品上柜提醒") 
     * 
     */
    public function handle() {
        $find_config = $this->db_easyA->table('cwl_shangguitips_config')->where('id=1')->find();
        $keshang_url = $_SERVER['REQUEST_SCHEME'] . '://'. $_SERVER['HTTP_HOST'] . url('admin/system.Shangguitips/keshang_customer');
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
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['云仓']);
                $map1 = " AND 云仓 IN ({$map1Str})";
            } else {
                $map1 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['风格']);
                $map2 = " AND 风格 IN ({$map2Str})";
            } else {
                $map2 = "";
            }   
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['货号']);
                $map3 = " AND 货号 IN ({$map3Str})";
            } else {
                $map3 = "";
            }
            
            if (!empty($input['上柜提醒'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['上柜提醒']);
                $map4 = " AND 上柜提醒 IN ({$map4Str})";
            } else {
                $map4 = "";
            }

            

            $sql = "
                SELECT
                    left(云仓, 2) as 云仓,
                    年份,
                    季节归集,一级分类,二级分类,
                    分类,风格,二级风格,货号,
                    店铺个数_直营,店铺个数_加盟,店铺个数_合计,
                    云仓_可用数量,云仓_主码齐码情况,
                    实际上柜_直营上柜数,实际上柜_加盟上柜数,实际上柜_上柜家数,
                    货品等级_计划_直营,货品等级_计划_加盟,货品等级_计划_合计,
                    货品等级_实际_直营,货品等级_实际_加盟,货品等级_实际_合计,
                    实际铺货_直营,实际铺货_加盟,实际铺货_合计,
                    铺货率_直营,铺货率_加盟,铺货率_合计,
                    上柜率_直营,上柜率_加盟,上柜率_合计,
                    货品等级上柜率_直营,货品等级上柜率_加盟,货品等级上柜率_合计,
                    预计最大可加铺店数,单款全国日均销排名,近1周中类销售占比,
                    上柜提醒,
                    更新日期
                FROM cwl_shangguitips_handle
                WHERE 1	
                    {$map1} 
                    {$map2} 
                    {$map3} 
                    {$map4} 
                ORDER BY 
                    云仓,风格
                LIMIT {$pageParams1}, {$pageParams2}  
            ";  

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                    FROM cwl_shangguitips_handle
                WHERE 1
                    {$map1} 
                    {$map2} 
                    {$map3} 
                    {$map4} 
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => $find_config['更新日期']]);
        } else {
            return View('handle', [
                'config' => $find_config,
                'keshang_url' => $keshang_url,
            ]);
        }
    }

    public function keshang_customer() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');
            $云仓 = $input['yc'];
            $货号 = $input['gdno'];
            if (!empty($input['yc'])) {
                $map1 = " AND `云仓` = '{$云仓}云仓'";                
            } else {
                $map1 = "";
            }
            if (!empty($input['gdno'])) {
                $map2 = " AND `货号` = '{$货号}'";                
            } else {
                $map2 = "";
            }

            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['云仓']);
                $map3 = " AND 云仓 IN ({$map3Str})";
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
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['货号']);
                $map5 = " AND 货号 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['店铺名称']);
                $map6 = " AND 店铺名称 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            $sql = "
                SELECT 
                    *
                    FROM cwl_shangguitips_keshang_customer
                WHERE 1
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
                ORDER BY
                    云仓,货号,经营模式
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                    FROM cwl_shangguitips_keshang_customer
                WHERE 1
                    {$map1} 
                    {$map2} 
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('keshang', [
                // 'config' => ,
            ]);
        }
    }

    public function keshang_customer_all() {
        return View('keshang_all', [
            // 'config' => ,
        ]);
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $goodsno = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM cwl_shangguitips_sk WHERE 1 GROUP BY 货号
        ");
        // $province = $this->db_easyA->query("
        //     SELECT 省份 as name, 省份 as value FROM cwl_weathertips_customer WHERE 省份 IS NOT NULL GROUP BY 省份
        // ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_shangguitips_sk GROUP BY 店铺名称
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['goodsno' => $goodsno, 'customer' => $customer]]);
    }
}
