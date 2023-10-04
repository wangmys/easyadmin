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
 * Class Customitem17
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="专员业绩跟进表")
 */
class Summary extends AdminController
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
     * @NodeAnotation(title="") 
     * 
     */
    public function index() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            // foreach ($input as $key => $val) {
            //     // echo $val;
            //     if ( $key != '在途库存') {
            //         if (empty($val)) {
            //             unset($input[$key]);
            //         }
            //     }
            // }

            // if (!empty($input['目标月份'])) {
            //     $map0 = " AND 目标月份 = '{$input['目标月份']}'";
            // } else {
            //     $目标月份 = date('Y-m');
            //     $map0 = " AND 目标月份 = '{$目标月份}'";;
            // }

            // if (checkAdmin()) {
            //     if (!empty($input['商品专员'])) {
            //         // echo $input['商品负责人'];
            //         $map1Str = xmSelectInput($input['商品专员']);
            //         $map1 = " AND 商品专员 IN ({$map1Str})";
            //     } else {
            //         $map1 = "";
            //     }
            // } else {
            //     $admin = session('admin.name');
            //     $map1 = " AND 商品专员 IN ('{$admin}')";
            // }
            

            // if (!empty($input['省份'])) {
            //     // echo $input['商品负责人'];
            //     $map2Str = xmSelectInput($input['省份']);
            //     $map2 = " AND 省份 IN ({$map2Str})";
            // } else {
            //     $map2 = "";
            // }   
            // if (!empty($input['经营模式'])) {
            //     // echo $input['商品负责人'];
            //     $map3Str = xmSelectInput($input['经营模式']);
            //     $map3 = " AND 经营模式 IN ({$map3Str})";
            // } else {
            //     $map3 = "";
            // }
            // if (!empty($input['店铺名称'])) {
            //     // echo $input['商品负责人'];
            //     $map4Str = xmSelectInput($input['店铺名称']);
            //     $map4 = " AND 店铺名称 IN ({$map4Str})";
            // } else {
            //     $map4 = "";
            // }

            $sql = "
                SELECT
                    商品专员,省份,经营模式,店铺名称,首单日期,本月目标,当前流水,目标达成率,
                    round(日均流水, 2) as 日均流水,
                    剩余日均流水,环比,同比,
                    concat(round(上装春占比 * 100, 1), '%') as 上装春占比,
                    concat(round(上装夏占比 * 100, 1), '%') as 上装夏占比,
                    concat(round(上装秋占比 * 100, 1), '%') as 上装秋占比,
                    concat(round(上装冬占比 * 100, 1), '%') as 上装冬占比,
                    concat(round(下装占比 * 100, 1), '%') as 下装占比,
                    concat(round(鞋履占比 * 100, 1), '%') as 鞋履占比,
                    上新提醒,引流是否提醒,配饰是否提醒,大小码缺少提醒
                    
                FROM
                    cwl_summary 
                WHERE 1	

                ORDER BY 商品专员, 本月目标 DESC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_summary
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => date('Y-m-d')]);
        } else {
            // $目标月份 = date('Y-m');
            // if (checkAdmin()) {
            //     $isAdmin = true;
            // } else {
            //     $isAdmin = false;
            // }
            return View('index', [
                // 'mubiaoMonth'=> $目标月份,
                // 'isAdmin' => $isAdmin
            ]);
        }
    }


    // 获取筛选栏多选参数
    public function getXmMapSelect() {

        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品专员 as name, 商品专员 as value FROM cwl_customitem17_yeji WHERE 商品专员 IS NOT NULL GROUP BY 商品专员
        ");
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_customitem17_yeji WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_customitem17_yeji GROUP BY 店铺名称
        ");
       
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer17' => $customer17, 'province' => $province, 'customer' => $customer]]);
    }

}
