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

            if (!empty($input['季节归集'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['季节归集']);
                $map5 = " AND 季节归集 IN ({$map5Str})";
            } else {
                $map5 = "";
            }

            if (!empty($input['一级分类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['一级分类']);
                $map6 = " AND 一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }

            if (!empty($input['二级分类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['二级分类']);
                $map7 = " AND 二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }

            if (!empty($input['二级风格'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['二级风格']);
                $map8 = " AND 二级风格 IN ({$map8Str})";
            } else {
                $map8 = "";
            }

            if (!empty($input['云仓_主码齐码情况'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['云仓_主码齐码情况']);
                $map9 = " AND 云仓_主码齐码情况 IN ({$map9Str})";
            } else {
                $map9 = "";
            }

            $sql = "
                SELECT
                    left(云仓, 2) as 云仓,
                    季节归集,一级分类,二级分类,
                    分类,风格,二级风格,货号,
                    店铺个数_直营,店铺个数_加盟,店铺个数_合计,
                    云仓_可用数量,云仓_主码齐码情况,
                    实际上柜_直营上柜数,实际上柜_加盟上柜数,实际上柜_上柜家数,
                    货品等级_计划_直营,货品等级_计划_加盟,货品等级_计划_合计,
                    货品等级_实际_直营,货品等级_实际_加盟,货品等级_实际_合计,
                    实际铺货_直营,实际铺货_加盟,实际铺货_合计,
                    concat(round(铺货率_直营 * 100, 1), '%') as `铺货率_直营`,
                    concat(round(铺货率_加盟 * 100, 1), '%') as 铺货率_加盟,
                    concat(round(铺货率_合计 * 100, 1), '%') as 铺货率_合计,
                    concat(round(上柜率_直营 * 100, 1), '%') as 上柜率_直营,
                    concat(round(上柜率_加盟 * 100, 1), '%') as 上柜率_加盟,
                    concat(round(上柜率_合计 * 100, 1), '%') as 上柜率_合计,
                    concat(round(货品等级上柜率_直营 * 100, 1), '%') as 货品等级上柜率_直营,
                    concat(round(货品等级上柜率_加盟 * 100, 1), '%') as 货品等级上柜率_加盟,
                    concat(round(货品等级上柜率_合计 * 100, 1), '%') as 货品等级上柜率_合计,
                    预计最大可加铺店数,单款全国日均销排名,
                    concat(round(近1周中类销售占比 * 100, 1), '%') as 近1周中类销售占比,
                    上柜提醒,克重,主码最小值,主码最小值码数,
                    更新日期
                FROM cwl_shangguitips_handle
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
                ORDER BY 
                    云仓,季节归集 DESC,风格
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
                    {$map5} 
                    {$map6} 
                    {$map7} 
                    {$map8} 
                    {$map9} 
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

    // 下载handle
    public function excel_handle() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();

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

            if (!empty($input['季节归集'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['季节归集']);
                $map5 = " AND 季节归集 IN ({$map5Str})";
            } else {
                $map5 = "";
            }

            if (!empty($input['一级分类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['一级分类']);
                $map6 = " AND 一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }

            if (!empty($input['二级分类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['二级分类']);
                $map7 = " AND 二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }

            if (!empty($input['二级风格'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['二级风格']);
                $map8 = " AND 二级风格 IN ({$map8Str})";
            } else {
                $map8 = "";
            }

            if (!empty($input['云仓_主码齐码情况'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['云仓_主码齐码情况']);
                $map9 = " AND 云仓_主码齐码情况 IN ({$map9Str})";
            } else {
                $map9 = "";
            }
            $map = "{$map1}{$map2}{$map3}{$map4}{$map5}{$map6}{$map7}{$map8}{$map9}";
            $code = rand_code(6);
            cache($code, $map, 3600);
            return json([
                'status' => 1,
                'code' => $code,
            ]); 

        } else {
            $code = input('code');
            $map = cache($code);
            if (empty($map)) {
                $map = '';
            }
            $sql = "
                SELECT
                    left(云仓, 2) as 云仓,
                    季节归集,一级分类,二级分类,
                    分类,风格,二级风格,货号,
                    店铺个数_直营,店铺个数_加盟,店铺个数_合计,
                    云仓_可用数量,云仓_主码齐码情况,
                    实际上柜_直营上柜数,实际上柜_加盟上柜数,实际上柜_上柜家数,
                    货品等级_计划_直营,货品等级_计划_加盟,货品等级_计划_合计,
                    货品等级_实际_直营,货品等级_实际_加盟,货品等级_实际_合计,
                    实际铺货_直营,实际铺货_加盟,实际铺货_合计,
                    concat(round(铺货率_直营 * 100, 1), '%') as `铺货率_直营`,
                    concat(round(铺货率_加盟 * 100, 1), '%') as 铺货率_加盟,
                    concat(round(铺货率_合计 * 100, 1), '%') as 铺货率_合计,
                    concat(round(上柜率_直营 * 100, 1), '%') as 上柜率_直营,
                    concat(round(上柜率_加盟 * 100, 1), '%') as 上柜率_加盟,
                    concat(round(上柜率_合计 * 100, 1), '%') as 上柜率_合计,
                    concat(round(货品等级上柜率_直营 * 100, 1), '%') as 货品等级上柜率_直营,
                    concat(round(货品等级上柜率_加盟 * 100, 1), '%') as 货品等级上柜率_加盟,
                    concat(round(货品等级上柜率_合计 * 100, 1), '%') as 货品等级上柜率_合计,
                    预计最大可加铺店数,单款全国日均销排名,
                    concat(round(近1周中类销售占比 * 100, 1), '%') as 近1周中类销售占比,
                    上柜提醒,克重,主码最小值,主码最小值码数,
                    更新日期
                FROM cwl_shangguitips_handle
                WHERE 1	
                    {$map} 
                ORDER BY 
                    云仓,季节归集 DESC,风格
            ";  

            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '新品上柜提醒_' . date('Ymd') . '_' . time() , 'xlsx');
        }
    }    

    // 下载可上
    public function excel_keshang() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            // $云仓 = $input['yc'];
            // $货号 = $input['gdno'];
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

            $map = "{$map1}{$map2}{$map3}{$map4}{$map5}{$map6}";
            $code = rand_code(6);
            cache($code, $map, 3600);
            return json([
                'status' => 1,
                'code' => $code,
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
                    FROM cwl_shangguitips_keshang_customer
                WHERE 1
                    {$map}
                ORDER BY
                    云仓,货号,经营模式
            ";
            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '可上店铺明细_' . date('Ymd') . '_' . time() , 'xlsx');
        }
    } 

    // 下载handle_push
    public function excel_handle_push() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();

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
                $map2 = " AND p.风格 IN ({$map2Str})";
            } else {
                $map2 = "";
            }   
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['货号']);
                $map3 = " AND p.货号 IN ({$map3Str})";
            } else {
                $map3 = "";
            }
            
            if (!empty($input['上柜提醒'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['上柜提醒']);
                $map4 = " AND (gz.上柜提醒 IN ({$map4Str}) OR  nc.上柜提醒 IN ({$map4Str}) OR  gy.上柜提醒 IN ({$map4Str}) OR  cs.上柜提醒 IN ({$map4Str}) OR  wh.上柜提醒 IN ({$map4Str}))";
            } else {
                $map4 = "";
            }

            if (!empty($input['季节归集'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['季节归集']);
                $map5 = " AND p.季节归集 IN ({$map5Str})";
            } else {
                $map5 = "";
            }

            if (!empty($input['一级分类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['一级分类']);
                $map6 = " AND p.一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }

            if (!empty($input['二级分类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['二级分类']);
                $map7 = " AND p.二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }

            if (!empty($input['上市波段'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['上市波段']);
                $map8 = " AND p.上市波段 IN ({$map8Str})";
            } else {
                $map8 = "";
            }

            if (!empty($input['CustomItem49'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['CustomItem49']);
                $map9 = " AND p.CustomItem49 IN ({$map9Str})";
            } else {
                $map9 = "";
            }

            if (!empty($input['分类'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['分类']);
                $map10 = " AND p.分类 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            $map = "{$map1}{$map2}{$map3}{$map4}{$map5}{$map6}{$map7}{$map8}{$map9}{$map10}";
            $code = rand_code(6);
            cache($code, $map, 3600);
            return json([
                'status' => 1,
                'code' => $code,
            ]); 

        } else {
            $code = input('code');
            $map = cache($code);
            if (empty($map)) {
                $map = '';
            }
            $sql = "
                select
                    p.*,
                    
                    right(gzrk.入库时间, 5) as `广州云仓_入库时间`,
                    gz.`云仓_可用数量` as `广州云仓_可用数量`,
                    gz.`实际上柜_上柜家数` as `广州云仓_实际上柜_上柜家数`,
                    gz.上柜提醒 as `广州云仓_上柜提醒`,
                    
                    right(ncrk.入库时间, 5) as `南昌云仓_入库时间`,
                    nc.`云仓_可用数量` as `南昌云仓_可用数量`,
                    nc.`实际上柜_上柜家数` as `南昌云仓_实际上柜_上柜家数`,
                    nc.上柜提醒 as `南昌云仓_上柜提醒`,
                    
                    right(gyrk.入库时间, 5) as `贵阳云仓_入库时间`,
                    gy.`云仓_可用数量` as `贵阳云仓_可用数量`,
                    gy.`实际上柜_上柜家数` as `贵阳云仓_实际上柜_上柜家数`,
                    gy.上柜提醒 as `贵阳云仓_上柜提醒`,
                    
                    right(csrk.入库时间, 5) as `长沙云仓_入库时间`,
                    cs.`云仓_可用数量` as `长沙云仓_可用数量`,
                    cs.`实际上柜_上柜家数` as `长沙云仓_实际上柜_上柜家数`,
                    cs.上柜提醒 as `长沙云仓_上柜提醒`,
                    
                    right(whrk.入库时间, 5) as `武汉云仓_入库时间`,
                    wh.`云仓_可用数量` as `武汉云仓_可用数量`,
                    wh.`实际上柜_上柜家数` as `武汉云仓_实际上柜_上柜家数`,
                    wh.上柜提醒 as `武汉云仓_上柜提醒`
                from
                cwl_shangguitips_handle_push as p
                left join cwl_shangguitips_handle as gz on gz.云仓 = '广州云仓' and p.一级分类 = gz.一级分类 and p.二级分类 = gz.二级分类 and p.分类 = gz.分类 and p.货号 = gz.货号
                left join cwl_shangguitips_handle as nc on nc.云仓 = '南昌云仓' and p.一级分类 = nc.一级分类 and p.二级分类 = nc.二级分类 and p.分类 = nc.分类 and p.货号 = nc.货号
                left join cwl_shangguitips_handle as gy on gy.云仓 = '贵阳云仓' and p.一级分类 = gy.一级分类 and p.二级分类 = gy.二级分类 and p.分类 = gy.分类 and p.货号 = gy.货号
                left join cwl_shangguitips_handle as cs on cs.云仓 = '长沙云仓' and p.一级分类 = cs.一级分类 and p.二级分类 = cs.二级分类 and p.分类 = cs.分类 and p.货号 = cs.货号
                left join cwl_shangguitips_handle as wh on wh.云仓 = '武汉云仓' and p.一级分类 = wh.一级分类 and p.二级分类 = wh.二级分类 and p.分类 = wh.分类 and p.货号 = wh.货号
                left join cwl_shangguitips_handle_push_ruku as gzrk on gzrk.云仓 = '广州云仓' and p.货号=gzrk.货号
                left join cwl_shangguitips_handle_push_ruku as ncrk on ncrk.云仓 = '南昌云仓' and p.货号=ncrk.货号
                left join cwl_shangguitips_handle_push_ruku as gyrk on gyrk.云仓 = '贵阳云仓' and p.货号=gyrk.货号
                left join cwl_shangguitips_handle_push_ruku as csrk on csrk.云仓 = '长沙云仓' and p.货号=csrk.货号
                left join cwl_shangguitips_handle_push_ruku as whrk on whrk.云仓 = '武汉云仓' and p.货号=whrk.货号
                where 1
                    {$map}
                ORDER BY
                    季节归集 ASC,上市波段 ASC
            ";  

            $select = $this->db_easyA->query($sql);
            $header = [];
            foreach($select[0] as $key => $val) {
                $header[] = [$key, $key];
            }
            return Excel::exportData($select, $header, '新品上柜提醒_' . date('Ymd') . '_' . time() , 'xlsx');
        }
    } 
    
    public function handle2() {
        $find_config = $this->db_easyA->table('cwl_shangguitips_config')->where('id=1')->find();
        $keshang_url = $_SERVER['REQUEST_SCHEME'] . '://'. $_SERVER['HTTP_HOST'] . url('admin/system.Shangguitips/keshang_customer');
        return View('handle2', [
            'config' => $find_config,
            'keshang_url' => $keshang_url,
        ]);
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

    /**
     * @NodeAnotation(title="新品上柜提醒_钉钉推送") 
     *  辛斌旧版地址：https://bx.babiboy.com/bi/spnewproductlaunchwinterwarning
     */
    public function handle_push() {
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
                $map2 = " AND p.风格 IN ({$map2Str})";
            } else {
                $map2 = "";
            }   
            if (!empty($input['货号'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['货号']);
                $map3 = " AND p.货号 IN ({$map3Str})";
            } else {
                $map3 = "";
            }
            
            if (!empty($input['上柜提醒'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['上柜提醒']);
                $map4 = " AND (gz.上柜提醒 IN ({$map4Str}) OR  nc.上柜提醒 IN ({$map4Str}) OR  gy.上柜提醒 IN ({$map4Str}) OR  cs.上柜提醒 IN ({$map4Str}) OR  wh.上柜提醒 IN ({$map4Str}))";
            } else {
                $map4 = "";
            }

            if (!empty($input['季节归集'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['季节归集']);
                $map5 = " AND p.季节归集 IN ({$map5Str})";
            } else {
                $map5 = "";
            }

            if (!empty($input['一级分类'])) {
                // echo $input['商品负责人'];
                $map6Str = xmSelectInput($input['一级分类']);
                $map6 = " AND p.一级分类 IN ({$map6Str})";
            } else {
                $map6 = "";
            }

            if (!empty($input['二级分类'])) {
                // echo $input['商品负责人'];
                $map7Str = xmSelectInput($input['二级分类']);
                $map7 = " AND p.二级分类 IN ({$map7Str})";
            } else {
                $map7 = "";
            }

            if (!empty($input['上市波段'])) {
                // echo $input['商品负责人'];
                $map8Str = xmSelectInput($input['上市波段']);
                $map8 = " AND p.上市波段 IN ({$map8Str})";
            } else {
                $map8 = "";
            }

            if (!empty($input['CustomItem49'])) {
                // echo $input['商品负责人'];
                $map9Str = xmSelectInput($input['CustomItem49']);
                $map9 = " AND p.CustomItem49 IN ({$map9Str})";
            } else {
                $map9 = "";
            }

            if (!empty($input['分类'])) {
                // echo $input['商品负责人'];
                $map10Str = xmSelectInput($input['分类']);
                $map10 = " AND p.分类 IN ({$map10Str})";
            } else {
                $map10 = "";
            }
            // echo  $map8;die;
            $sql = "
                select
                    p.*,
                    
                    right(gzrk.入库时间, 5) as `广州云仓_入库时间`,
                    gz.`云仓_可用数量` as `广州云仓_可用数量`,
                    gz.`实际上柜_上柜家数` as `广州云仓_实际上柜_上柜家数`,
                    gz.上柜提醒 as `广州云仓_上柜提醒`,
                    
                    right(ncrk.入库时间, 5) as `南昌云仓_入库时间`,
                    nc.`云仓_可用数量` as `南昌云仓_可用数量`,
                    nc.`实际上柜_上柜家数` as `南昌云仓_实际上柜_上柜家数`,
                    nc.上柜提醒 as `南昌云仓_上柜提醒`,
                    
                    right(gyrk.入库时间, 5) as `贵阳云仓_入库时间`,
                    gy.`云仓_可用数量` as `贵阳云仓_可用数量`,
                    gy.`实际上柜_上柜家数` as `贵阳云仓_实际上柜_上柜家数`,
                    gy.上柜提醒 as `贵阳云仓_上柜提醒`,
                    
                    right(csrk.入库时间, 5) as `长沙云仓_入库时间`,
                    cs.`云仓_可用数量` as `长沙云仓_可用数量`,
                    cs.`实际上柜_上柜家数` as `长沙云仓_实际上柜_上柜家数`,
                    cs.上柜提醒 as `长沙云仓_上柜提醒`,
                    
                    right(whrk.入库时间, 5) as `武汉云仓_入库时间`,
                    wh.`云仓_可用数量` as `武汉云仓_可用数量`,
                    wh.`实际上柜_上柜家数` as `武汉云仓_实际上柜_上柜家数`,
                    wh.上柜提醒 as `武汉云仓_上柜提醒`
                from
                cwl_shangguitips_handle_push as p
                left join cwl_shangguitips_handle as gz on gz.云仓 = '广州云仓' and p.一级分类 = gz.一级分类 and p.二级分类 = gz.二级分类 and p.分类 = gz.分类 and p.货号 = gz.货号
                left join cwl_shangguitips_handle as nc on nc.云仓 = '南昌云仓' and p.一级分类 = nc.一级分类 and p.二级分类 = nc.二级分类 and p.分类 = nc.分类 and p.货号 = nc.货号
                left join cwl_shangguitips_handle as gy on gy.云仓 = '贵阳云仓' and p.一级分类 = gy.一级分类 and p.二级分类 = gy.二级分类 and p.分类 = gy.分类 and p.货号 = gy.货号
                left join cwl_shangguitips_handle as cs on cs.云仓 = '长沙云仓' and p.一级分类 = cs.一级分类 and p.二级分类 = cs.二级分类 and p.分类 = cs.分类 and p.货号 = cs.货号
                left join cwl_shangguitips_handle as wh on wh.云仓 = '武汉云仓' and p.一级分类 = wh.一级分类 and p.二级分类 = wh.二级分类 and p.分类 = wh.分类 and p.货号 = wh.货号
                left join cwl_shangguitips_handle_push_ruku as gzrk on gzrk.云仓 = '广州云仓' and p.货号=gzrk.货号
                left join cwl_shangguitips_handle_push_ruku as ncrk on ncrk.云仓 = '南昌云仓' and p.货号=ncrk.货号
                left join cwl_shangguitips_handle_push_ruku as gyrk on gyrk.云仓 = '贵阳云仓' and p.货号=gyrk.货号
                left join cwl_shangguitips_handle_push_ruku as csrk on csrk.云仓 = '长沙云仓' and p.货号=csrk.货号
                left join cwl_shangguitips_handle_push_ruku as whrk on whrk.云仓 = '武汉云仓' and p.货号=whrk.货号
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
                ORDER BY
                    季节归集 ASC,上市波段 ASC
                LIMIT {$pageParams1}, {$pageParams2}  
            "; 

            $select = $this->db_easyA->query($sql);

            $sql2 = "
                select
                    count(*) as total
                from
                cwl_shangguitips_handle_push as p
                left join cwl_shangguitips_handle as gz on gz.云仓 = '广州云仓' and p.一级分类 = gz.一级分类 and p.二级分类 = gz.二级分类 and p.分类 = gz.分类 and p.货号 = gz.货号
                left join cwl_shangguitips_handle as nc on nc.云仓 = '南昌云仓' and p.一级分类 = nc.一级分类 and p.二级分类 = nc.二级分类 and p.分类 = nc.分类 and p.货号 = nc.货号
                left join cwl_shangguitips_handle as gy on gy.云仓 = '贵阳云仓' and p.一级分类 = gy.一级分类 and p.二级分类 = gy.二级分类 and p.分类 = gy.分类 and p.货号 = gy.货号
                left join cwl_shangguitips_handle as cs on cs.云仓 = '长沙云仓' and p.一级分类 = cs.一级分类 and p.二级分类 = cs.二级分类 and p.分类 = cs.分类 and p.货号 = cs.货号
                left join cwl_shangguitips_handle as wh on wh.云仓 = '武汉云仓' and p.一级分类 = wh.一级分类 and p.二级分类 = wh.二级分类 and p.分类 = wh.分类 and p.货号 = wh.货号
                left join cwl_shangguitips_handle_push_ruku as gzrk on gzrk.云仓 = '广州云仓' and p.货号=gzrk.货号
                left join cwl_shangguitips_handle_push_ruku as ncrk on ncrk.云仓 = '南昌云仓' and p.货号=ncrk.货号
                left join cwl_shangguitips_handle_push_ruku as gyrk on gyrk.云仓 = '贵阳云仓' and p.货号=gyrk.货号
                left join cwl_shangguitips_handle_push_ruku as csrk on csrk.云仓 = '长沙云仓' and p.货号=csrk.货号
                left join cwl_shangguitips_handle_push_ruku as whrk on whrk.云仓 = '武汉云仓' and p.货号=whrk.货号
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
            ";
            $count = $this->db_easyA->query($sql2);
            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select, 'create_time' => $find_config['更新日期']]);
        } else {
            $gzyc = $this->db_easyA->table('cwl_shangguitips_handle')->field('店铺个数_合计')->where(['云仓' => '广州云仓'])->find();
            $ncyc = $this->db_easyA->table('cwl_shangguitips_handle')->field('店铺个数_合计')->where(['云仓' => '南昌云仓'])->find();
            $gyyc = $this->db_easyA->table('cwl_shangguitips_handle')->field('店铺个数_合计')->where(['云仓' => '贵阳云仓'])->find();
            $csyc = $this->db_easyA->table('cwl_shangguitips_handle')->field('店铺个数_合计')->where(['云仓' => '长沙云仓'])->find();
            $whyc = $this->db_easyA->table('cwl_shangguitips_handle')->field('店铺个数_合计')->where(['云仓' => '武汉云仓'])->find();
            return View('handle_push', [
                // 'config' => $find_config,
                'gzyc' => $gzyc,
                'ncyc' => $ncyc,
                'gyyc' => $gyyc,
                'csyc' => $csyc,
                'whyc' => $whyc,
                'keshang_url' => $keshang_url,
            ]);
        }
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $goodsno = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM cwl_shangguitips_sk WHERE 1 GROUP BY 货号
        ");
        $yjfl = $this->db_easyA->query("
            SELECT 一级分类 as name, 一级分类 as value FROM cwl_shangguitips_handle WHERE 一级分类 IS NOT NULL GROUP BY 一级分类
        ");
        $ejfl = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM cwl_shangguitips_handle WHERE 二级分类 IS NOT NULL GROUP BY 二级分类
        ");
        $ejfg = $this->db_easyA->query("
            SELECT 二级风格 as name, 二级风格 as value FROM cwl_shangguitips_handle WHERE 二级风格 IS NOT NULL GROUP BY 二级风格
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_shangguitips_sk GROUP BY 店铺名称
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['goodsno' => $goodsno, 'customer' => $customer, 'yjfl' => $yjfl, 'ejfl' => $ejfl, 'ejfg' => $ejfg]]);
    }

    // 获取筛选栏多选参数
    public function getXmMapSelect_push() {
        // 商品负责人
        $goodsno = $this->db_easyA->query("
            SELECT 货号 as name, 货号 as value FROM cwl_shangguitips_handle_push WHERE 货号 IS NOT NULL GROUP BY 货号
        ");
        $yjfl = $this->db_easyA->query("
            SELECT 一级分类 as name, 一级分类 as value FROM cwl_shangguitips_handle_push WHERE 一级分类 IS NOT NULL GROUP BY 一级分类
        ");
        $ejfl = $this->db_easyA->query("
            SELECT 二级分类 as name, 二级分类 as value FROM cwl_shangguitips_handle_push WHERE 二级分类 IS NOT NULL GROUP BY 二级分类
        ");
        $fl = $this->db_easyA->query("
            SELECT 分类 as name, 分类 as value FROM cwl_shangguitips_handle_push WHERE 分类 IS NOT NULL GROUP BY 分类
        ");
        $ssbd = $this->db_easyA->query("
            SELECT 上市波段 as name, 上市波段 as value FROM cwl_shangguitips_handle_push GROUP BY 上市波段
        ");
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['goodsno' => $goodsno, 'yjfl' => $yjfl, 'ejfl' => $ejfl, 'fl' => $fl, 'ssbd' => $ssbd]]);
    }
}
