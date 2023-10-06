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
class Customitem17 extends AdminController
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
     * @NodeAnotation(title="门店业绩达成情况") 
     * 
     */
    public function yeji() {
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

            if (!empty($input['目标月份'])) {
                $map0 = " AND 目标月份 = '{$input['目标月份']}'";
            } else {
                $目标月份 = date('Y-m');
                $map0 = " AND 目标月份 = '{$目标月份}'";;
            }

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

            if (!empty($input['商品专员'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['商品专员']);
                $map1 = " AND 商品专员 IN ({$map1Str})";
            } else {
                $map1 = "";
            }  
            

            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['省份']);
                $map2 = " AND 省份 IN ({$map2Str})";
            } else {
                $map2 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['经营模式']);
                $map3 = " AND 经营模式 IN ({$map3Str})";
            } else {
                $map3 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['店铺名称']);
                $map4 = " AND 店铺名称 IN ({$map4Str})";
            } else {
                $map4 = "";
            }

            $sql = "
                SELECT
                    商品专员,
                    left(省份, 2) as 省份,
                    经营模式,店铺名称,本月目标,实际累计流水,累计流水截止日期,
                    concat(round(目标达成率 * 100, 1), '%') as 目标达成率,
                    `100%缺口额`,`100%缺口_日均额`,`85%缺口额`,`85%缺口_日均额`,近七天日均销,
                    concat(round(`100%进度落后` * 100, 1), '%') as `100%进度落后`,
                    concat(round(`85%进度落后` * 100, 1), '%') as `85%进度落后`,
                    concat(round(老店业绩同比, 1), '%') as 老店业绩同比
                FROM
                    cwl_customitem17_yeji 
                WHERE 1	
                    {$map0}
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
                ORDER BY 商品专员, 本月目标 DESC
                LIMIT {$pageParams1}, {$pageParams2}  
            ";

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_customitem17_yeji
                WHERE 1
                    {$map0}
                    {$map1}
                    {$map2}
                    {$map3}
                    {$map4}
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => date('Y-m-d')]);
        } else {
            $目标月份 = date('Y-m');
            if (checkAdmin()) {
                $isAdmin = true;
                $a_name = '';
            } elseif (session('admin.auth_ids') == '7') { // 商品专员
                $isAdmin = false;
                $a_name  = session('admin.name');
            } else {
                $isAdmin = false;
                $a_name  = '';    
            }
            return View('yeji', [
                'mubiaoMonth'=> $目标月份,
                'isAdmin' => $isAdmin,
                'a_name' => $a_name
            ]);
        }
    }

    /**
     * @NodeAnotation(title="商品负责人业绩目标达成情况") 
     * 
     */
    public function zhuanyuan() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');


            if (!empty($input['目标月份'])) {
                $map0 = " AND 目标月份 = '{$input['目标月份']}'";
            } else {
                $目标月份 = date('Y-m');
                $map0 = " AND 目标月份 = '{$目标月份}'";;
            }


            $sql = "
                SELECT
                    商品专员,
                    目标_直营,目标_加盟,目标_合计,累计流水_直营,累计流水_加盟,累计流水_合计,
                    累计流水截止日期,
                    concat(round(达成率_直营 * 100, 1), '%') as 达成率_直营,
                    concat(round(达成率_加盟 * 100, 1), '%') as 达成率_加盟,
                    concat(round(达成率_合计 * 100, 1), '%') as 达成率_合计,
                    `100%日均需销额_直营`,`100%日均需销额_加盟`,`100%日均需销额_合计`,`85%日均需销额_直营`,`85%日均需销额_加盟`,`85%日均需销额_合计`,
                    `近七天日均销额_直营`,`近七天日均销额_加盟`,`近七天日均销额_合计`
                FROM
                cwl_customitem17_zhuanyuan 
                WHERE 1	
                    {$map0}
            ";

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_customitem17_zhuanyuan
                WHERE 1
                    {$map0}
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => date('Y-m-d')]);
        } else {
            $目标月份 = date('Y-m');
            if (checkAdmin()) {
                $isAdmin = true;
            } else {
                $isAdmin = false;
            }
            return View('zhuanyuan', [
                'mubiaoMonth'=> $目标月份,
                'isAdmin' => $isAdmin,
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
       
        if (!checkAdmin()) {
            // 省份选中
            foreach ($customer17 as $key => $val) {
                if (session('admin.name') == $val['name']) {
                    $customer17[$key]['selected'] = true;
                }
            } 
        }
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();
        
        return json(["code" => "0", "msg" => "", "data" => ['customer17' => $customer17, 'province' => $province, 'customer' => $customer]]);
    }

    public function test() {
        $sql = "
            SELECT
                商品专员,
                目标_直营,目标_加盟,目标_合计,累计流水_直营,累计流水_加盟,累计流水_合计,
                累计流水截止日期,
                concat(round(达成率_直营 * 100, 1), '%') as 达成率_直营,
                concat(round(达成率_加盟 * 100, 1), '%') as 达成率_加盟,
                concat(round(达成率_合计 * 100, 1), '%') as 达成率_合计,
                `100%日均需销额_直营`,`100%日均需销额_加盟`,`100%日均需销额_合计`,`85%日均需销额_直营`,`85%日均需销额_加盟`,`85%日均需销额_合计`,
                `近七天日均销额_直营`,`近七天日均销额_加盟`,`近七天日均销额_合计`
            FROM
            cwl_customitem17_zhuanyuan 
            WHERE 1	
                and 目标月份='2023-10'
        ";

        $select = $this->db_easyA->query($sql);  
        $合计 = [
            '目标_直营' => 0,
            '目标_加盟' => 0,
            '目标_合计' => 0,
            '累计流水_直营' => 0,
            '累计流水_加盟' => 0,
            '累计流水_合计' => 0,

        ];
        foreach ($select as $key => $val) {
            $合计['目标_直营'] += $val['目标_直营'];
            $合计['目标_加盟'] += $val['目标_加盟'];
            $合计['目标_合计'] += $val['目标_合计'];
            $合计['累计流水_直营'] += $val['累计流水_直营'];
            $合计['累计流水_加盟'] += $val['累计流水_加盟'];
            $合计['累计流水_合计'] += $val['累计流水_合计'];
        }
        $合计['达成率_直营'] = $合计['累计流水_直营'] / $合计['目标_直营'];
        $合计['达成率_加盟'] = $合计['累计流水_加盟'] / $合计['目标_加盟'];
        $合计['达成率_合计'] = $合计['累计流水_合计'] / $合计['目标_合计'];
        $合计['累计流水截止日期'] = $select[0]['累计流水截止日期'];

        // `100%日均需销额_直营` = (`目标_直营` - `累计流水_直营`) / {$到结束剩余天数},
        // `100%日均需销额_加盟` = (`目标_加盟` - `累计流水_加盟`) / {$到结束剩余天数},
        // `100%日均需销额_合计` = (`目标_合计` - `累计流水_合计`) / {$到结束剩余天数}
        dump($合计);
    }

}
