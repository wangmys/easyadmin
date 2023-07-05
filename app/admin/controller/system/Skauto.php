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
 * @ControllerAnnotation(title="自动售空")
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
     * @NodeAnotation(title="断码率系统配置")
     */
    public function config() {
        // $typeQima = $this->getTypeQiMa('in ("下装","内搭","外套","鞋履","松紧长裤","松紧短裤")');
        
        // // 商品负责人
        // $people = SpWwBudongxiaoDetail::getPeople([
        //     ['商品负责人', 'exp', new Raw('IS NOT NULL')]
        // ]);

        // // 
        $select_config = $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->find();
        
        // dump($select_config );die;

        return View('config', [
            'config' => $select_config,
        ]);
    }

    public function saveMap() {
        if (request()->isAjax() && checkAdmin()) {
            $params = input();

            $this->db_easyA->table('cwl_duanmalv_config')->where('id=1')->strict(false)->update($params);     

            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => '权限不足，请勿非法访问']);
        }   
    }

    /**
     * @NodeAnotation(title="单店断码明细") 表7
     */
    public function skauto() {
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

            $sql = "
                SELECT
                    云仓,
                    left(省份, 2) as 省份,
                    商品负责人,经营模式,店铺名称,
                    一级分类,
                    二级分类,
                    分类,风格,货号,零售价,当前零售价,折率,上市天数,
                    首单日期,销售天数,总入量,累销数量,店铺库存,在途库存,已配未发,近一周销,近两周销,云仓数量,售空提醒
                from cwl_skauto_res 
                where 1

                order by 
                    省份 asc
                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            
            $select = $this->db_easyA->query($sql);

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM cwl_skauto_res
                WHERE  1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select,  'create_time' => date('Y-m-d')]);
        } else {
            return View('skauto', [

            ]);
        }
    }
}
