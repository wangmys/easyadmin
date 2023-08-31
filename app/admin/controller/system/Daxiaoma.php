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
 * Class Daxiaoma
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="超量提醒")
 */
class Daxiaoma extends AdminController
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
        $find_config = $this->db_easyA->table('cwl_weathertips_config')->where('id=1')->find();
        
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

            $this->db_easyA->table('cwl_chaoliang_config')->where('id=1')->strict(false)->update($params);     

            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
    }

    /**
     * @NodeAnotation(title="大小码列表内搭") 
     * 
     */
    public function handle_neida() {
        $find_config = $this->db_easyA->table('cwl_weathertips_config')->where('id=1')->find();
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
                    // $map1 = " AND c.商品负责人 IN ({$map1Str})";
                } else {
                    // $map1 = "";
                }
            } else {
                $admin = session('admin.name');
                // $map1 = " AND c.商品负责人 IN ('{$admin}')";
            }

            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['省份']);
                $map1 = " AND 省份 IN ({$map1Str})";
            } else {
                $map1 = "";
            }

            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['店铺名称']);
                $map2 = " AND 店铺名称 IN ({$map2Str})";
            } else {
                $map2 = "";
            }

            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['风格']);
                $map3 = " AND 风格 IN ({$map3Str})";
            } else {
                $map3 = "";
            }

            if (!empty($input['一级风格'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['一级风格']);
                $map4 = " AND 一级风格 IN ({$map4Str})";
            } else {
                $map4 = "";
            }

            if (!empty($input['未上柜提醒'])) {
                // echo $input['商品负责人'];
                $exploadDate = explode(',', $input['未上柜提醒']);

                $map5 = '';
                foreach ($exploadDate as $key => $val) {
                    $map5 .= " AND `". $val . "`='缺' ";
                }
                // echo $map5;
            } else {
                $map5 = "";
            }

            if (!empty($input['大小码提醒'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['大小码提醒']);
                $map6 = " AND 大小码提醒 IN ({$map6Str})";
            } else {
                $map6 = "";
            }

            $sql = "
                SELECT 
                    * 
                FROM 
                    cwl_daxiao_handle
                WHERE 1	
                    AND 一级分类 IN ('内搭')
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
                LIMIT {$pageParams1}, {$pageParams2}  
            ";  

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                    cwl_daxiao_handle
                WHERE 1	
                    AND 一级分类 IN ('内搭')
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                    {$map5}
                    {$map6}
            ";
            $count = $this->db_easyA->query($sql2);
            $find_config = $this->db_easyA->table('cwl_skauto_config')->where('id=1')->find();
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            return View('handle_neida', [

            ]);
        }
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        // $customer17 = $this->db_easyA->query("
        //     SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_weathertips_customer WHERE 商品负责人 IS NOT NULL AND 商品负责人 !='0' GROUP BY 商品负责人
        // ");
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_daxiao_handle WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_daxiao_handle GROUP BY 店铺名称
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['province' => $province, 'customer' => $customer]]);
    }


}
