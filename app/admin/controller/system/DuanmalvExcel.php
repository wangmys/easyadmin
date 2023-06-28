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
    断码率相关下载
 */
class DuanmalvExcel extends AdminController
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

    // 下载单店断码明细  sk
    public function excel_sk() {
        if (request()->isAjax()) {
            $input = input();
            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (!empty($input['商品负责人'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['商品负责人']);
                $map1 = " AND sk.商品负责人 IN ({$map1Str})";
            } else {
                $map1 = "";
            }
            if (!empty($input['云仓'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['云仓']);
                $map2 = " AND sk.云仓 IN ({$map2Str})";
            } else {
                $map2 = "";
            }
            if (!empty($input['省份'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['省份']);
                $map3 = " AND sk.省份 IN ({$map3Str})";
            } else {
                $map3 = "";
            }   
            if (!empty($input['经营模式'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['经营模式']);
                $map4 = " AND sk.经营模式 IN ({$map4Str})";
            } else {
                $map4 = "";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['店铺名称']);
                $map5 = " AND sk.店铺名称 IN ({$map5Str})";
            } else {
                $map5 = "";
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['大类']);
                $map6 = " AND sk.一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['中类']);
                $map7 = " AND sk.二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['领型'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['领型']);
                $map8 = " AND sk.领型 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['货号']);
                $map9 = " AND sk.货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['风格']);
                $map10 = " AND sk.风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            if (!empty($input['是否TOP60'])) {
                // echo $input['商品负责人'];
                $map11Str = xmSelectInput($input['是否TOP60']);
                $map11 = " AND sk.是否TOP60 IN ({$map11Str})";
            } else {
                $map11 = "";
            }
            if (!empty($input['是否TOP60考核款'])) {
                // echo $input['商品负责人'];
                $map12Str = xmSelectInput($input['是否TOP60考核款']);
                $map12 = " AND sk.是否TOP60考核款 IN ({$map12Str})";
            } else {
                $map12 = "";
            }


            $map = "{$map1}{$map2}{$map3}{$map4}{$map5}{$map6}{$map7}{$map8}{$map9}{$map10}{$map11}{$map12}";
            $code = rand_code(6);
            cache($code, $map, 3600);

            $sql = "
                SELECT 
                    count(*) as total              
                FROM 
                    cwl_duanmalv_sk as sk 
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
                FROM cwl_duanmalv_sk AS sk
                RIGHT JOIN cwl_duanmalv_handle_1 h ON sk.店铺名称 = h.`店铺名称`
                WHERE 1
                    AND sk.`一级分类` = h.`一级分类` 
                    AND sk.`二级分类` = h.`二级分类` 
                    AND sk.领型 = h.领型 
                    AND sk.风格 = h.风格 
                    {$map}
                ORDER BY 
                    sk.云仓, sk.`商品负责人` desc, sk.店铺名称, sk.风格, sk.季节, sk.一级分类, sk.二级分类, sk.分类, sk.领型
            ";
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '单店断码明细_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');

            // 下载
            
        }
    }

    // table6 单店品类断码情况
    public function excel_table6() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();

            if (!empty($input['商品负责人'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['商品负责人']);
                $map1 = " AND 商品负责人 IN ({$map1Str})";
            } else {
                $map1 = "";
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
            if (!empty($input['领型'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['领型']);
                $map8 = " AND 领型 IN ({$map8Str})";
            } else {
                $map8 = "";
            }

            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['风格']);
                $map10 = " AND 风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }

            if (!empty($input['领型齐码率'])) {
                // echo $input['商品负责人'];
                $map13Str = xmSelectInput($input['领型齐码率']);
                $map13 = " AND 领型齐码率 < ({$map13Str})";
            } else {
                $map13 = "";
            }

            $map13 = " AND 领型齐码率 >= " . $input['qimalvStart'] / 100 . ' AND 领型齐码率 <= ' . $input['qimalvEnd'] / 100 ;

            $map = "{$map1}{$map2}{$map3}{$map4}{$map5}{$map6}{$map7}{$map8}{$map10}{$map13}";
            $code = rand_code(6);
            cache($code, $map, 3600);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_duanmalv_table6
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
                FROM cwl_duanmalv_table6 WHERE 1
                    {$map}
            "; 
            
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '单店品类断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');

            // 下载
            
        }
    }

    // table6 单店品类断码情况
    public function excel_table4() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();

            foreach ($input as $key => $val) {
                if (empty($val)) {
                    unset($input[$key]);
                }
            }
            if (!empty($input['大类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['大类']);
                $map6 = " AND t4.大类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }
            if (!empty($input['中类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['中类']);
                $map7 = " AND t4.中类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }
            if (!empty($input['领型'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['领型']);
                $map8 = " AND t4.领型 IN ({$map8Str})";
            } else {
                $map8 = "";
            }
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['货号']);
                $map9 = " AND t4.货号 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            if (!empty($input['风格'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['风格']);
                $map10 = " AND t4.风格 IN ({$map10Str})";
            } else {
                $map10 = "";
            }

            $map = "{$map6}{$map7}{$map8}{$map9}{$map10}";
            $code = rand_code(6);
            cache($code, $map, 3600);

            $sql2 = "
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
                    t4.省份,t4.风格, t4.大类, t4.中类, t4.货号
            ";
        $count = $this->db_easyA->query($sql2);

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
                    t4.风格,
                    t4.大类,
                    t4.中类,
                    t4.领型,
                    t4.货号,
                    t4.省份,
                    t4.上柜数',
                    t4.断码家数',
                    concat(round(t4.断码率 * 100, 1), '%') as '断码率',
                    round(t4.周转, 1) as '周转'
                FROM
                    cwl_duanmalv_table4 AS t4
                WHERE 1
                    {$map}
                GROUP BY
                    t4.省份,t4.风格, t4.大类,t4.中类,t4.货号 
                ORDER BY
                    t4.省份,t4.风格
            "; 
            
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '单店单款断码情况_' . session('admin.name') . '_' . date('Ymd') . '_' . time() , 'xlsx');

            // 下载
            
        }
    }

    public function getCache($code = "") {
        if (empty($code)) {
            $code = input('code');
        }
        dump(cache($code));
    }
}
