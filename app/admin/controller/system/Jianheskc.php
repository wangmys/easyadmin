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

use app\api\controller\lufei\Jianheskc as JianheskcApi;

/**
 * Class Jianheskc
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="检核SKC")
 */
class Jianheskc extends AdminController
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
     *  获取表格头部字段
     */
    public function getHeader() {
        // if (request()->isAjax()) {
        if (1) {
            // 筛选条件
            $input = input();
            @$pageParams1 = ($input['page'] - 1) * $input['limit'];
            @$pageParams2 = input('limit');

            // foreach ($input as $key => $val) {
            //     // echo $val;
            //     if ( $key != '在途库存') {
            //         if (empty($val)) {
            //             unset($input[$key]);
            //         }
            //     }
            // }

            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['店铺名称']);
                $map1 = " AND m.店铺名称 IN ({$map1Str})";
            } else {
                $map1 = "";
            }

            if (!empty($input['商品负责人'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['商品负责人']);
                $map4 = " AND m.商品负责人 IN ({$map4Str})";
            } else {
                $map4 = "";
            }

            if (!empty($input['温区'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['温区']);
                $map5 = " AND m.温区 IN ({$map5Str})";
            } else {
                $map5 = "";
            }

            $sql0 = "
                SELECT
                    店铺名称
                FROM
                    cwl_jianhe_stock_skc as m
                WHERE 
                    1
                    {$map1}
                    {$map4}
                    {$map5}
                GROUP BY
                    店铺名称
                 LIMIT 100
            ";  
      
            $select_店铺名称 = $this->db_easyA->query($sql0);

            
            if ($select_店铺名称) {
                // print_r($select_店铺名称);die;
                $field = "";
                $customerName = "";
                $len = count($select_店铺名称);
                foreach ($select_店铺名称 as $key => $val) {
                    $field .= 
                    ",sum(
                        case 
                            when m.店铺名称 = '{$val['店铺名称']}' then m.店铺库存 else null
                        end
                    ) AS {$val['店铺名称']}";
                    
                    if ($key < $len -1 ) {
                        $customerName .= "'{$val['店铺名称']}'" . ",";
                    } else {
                        $customerName .= "'{$val['店铺名称']}'";
                    }
                }
    
                // echo $customerName;die;
                // -- 	m.店铺名称 in ('南宁一店','南宁二店')
                $sql = "
                    SELECT
                        m.一级分类,m.二级分类,m.修订分类
                        {$field}
                    FROM
                        cwl_jianhe_stock_skc as m
                    WHERE 1
                        AND m.店铺名称 in ({$customerName})
                    
                    GROUP BY
                        m.一级分类,m.二级分类,m.修订分类
                    limit 1
                ";  
    
                // die;
                $select = $this->db_easyA->query($sql);

                if ($select) {
                    // 查温区
                    $sql_customer = "
                        SELECT
                            c.`CustomerName`,
                            c.`CustomItem10`,
                            c.`CustomItem11`,
                            c.`CustomItem36`,
                            d.five_item_num 
                        FROM
                            customer AS c
                            LEFT JOIN sp_skc_sz_detail as d on c.CustomerName = d.store_name
                    ";
                    
                    
                    $select_customer = $this->db_easyA->query($sql_customer);
                    $res = @$select[0];
                    
                    // dump($select_customer);
                    // die;

                    foreach ($res as $key => $val) {
                        foreach ($select_customer as $key2 => $val2) {
                            if ($key == $val2['CustomerName']) {
                                $res[$key] = [
                                    '温区' => $val2['CustomItem36'],
                                    '裤台' => $val2['CustomItem10'] + $val2['CustomItem11'],
                                    '窗数' => $val2['five_item_num'],
                                ];
                                break;
                            }
                        }
                    }

                    // dump($res);
                    // die;
                    return json(["code" => "0", "msg" => "", "data" => $res]);
                } else {
                    return json(["code" => "0", "msg" => "", "data" => []]);
                }
    
   
            } else {

                return json(["code" => "0", "msg" => "", "data" => []]);
            }

            
        } 
    } 

        /**
     * @NodeAnotation(title="门店提醒-全") 
     * 
     */
    public function handle() {
        if (request()->isAjax()) {
        // if (1) {
            // 筛选条件
            $input = input();
            @$pageParams1 = ($input['page'] - 1) * $input['limit'];
            @$pageParams2 = input('limit');

            if (!empty($input['检核类型'])) {
                // echo $input['商品负责人'];
                // $map0Str = xmSelectInput($input['检核类型']);
                $map0 = "m.{$input['检核类型']}";
            } else {
                $map0 = "m.店铺库存";
            }
            if (!empty($input['店铺名称'])) {
                // echo $input['商品负责人'];
                $map1Str = xmSelectInput($input['店铺名称']);
                $map1 = " AND m.店铺名称 IN ({$map1Str})";
            } else {
                $map1 = "";
            }

            if (!empty($input['调整风格'])) {
                // echo $input['商品负责人'];
                $map2Str = xmSelectInput($input['调整风格']);
                $map2 = " AND m.调整风格 IN ({$map2Str})";
            } else {
                $map2 = "";
            }

            if (!empty($input['修订季节'])) {
                // echo $input['商品负责人'];
                $map3Str = xmSelectInput($input['修订季节']);
                $map3 = " AND m.修订季节 IN ({$map3Str})";
            } else {
                $map3 = "";
            }
            if (!empty($input['商品负责人'])) {
                // echo $input['商品负责人'];
                $map4Str = xmSelectInput($input['商品负责人']);
                $map4 = " AND m.商品负责人 IN ({$map4Str})";
            } else {
                $map4 = "";
            }

            if (!empty($input['温区'])) {
                // echo $input['商品负责人'];
                $map5Str = xmSelectInput($input['温区']);
                $map5 = " AND m.温区 IN ({$map5Str})";
            } else {
                $map5 = "";
            }

            $sql0 = "
                SELECT
                    店铺名称
                FROM
                    cwl_jianhe_stock_skc as m
                WHERE 
                    1
                    {$map1}
                    {$map4}
                    {$map5}
                GROUP BY
                    店铺名称
                 LIMIT 100
            ";  
            $select_店铺名称 = $this->db_easyA->query($sql0);

            if ($select_店铺名称) {
                $field = "";
                $customerName = "";
                $len = count($select_店铺名称);
                foreach ($select_店铺名称 as $key => $val) {
                    $field .= 
                    ",sum(
                        case 
                            -- when m.店铺名称 = '{$val['店铺名称']}' then m.店铺库存 else null
                            when m.店铺名称 = '{$val['店铺名称']}' then {$map0} else null
                        end
                    ) AS {$val['店铺名称']}";
                    
                    if ($key < $len -1 ) {
                        $customerName .= "'{$val['店铺名称']}'" . ",";
                    } else {
                        $customerName .= "'{$val['店铺名称']}'";
                    }
                }
    
                // echo $customerName;die;
                // -- 	m.店铺名称 in ('南宁一店','南宁二店')
                $sql = "
                    SELECT
                        IFNULL(m.一级分类,'合计') AS 一级分类,
                        IFNULL(m.二级分类,'合计') AS 二级分类, 
                        IFNULL(m.修订分类,'合计') AS 修订分类 
                        {$field}
                    FROM
                        cwl_jianhe_stock_skc as m
                    WHERE 1
                        AND m.店铺名称 in ({$customerName})
                        {$map2}
                        {$map3}
                        {$map4}
                        {$map5}
                    GROUP BY
                        m.一级分类,m.二级分类,m.修订分类
                        WITH ROLLUP
                    
                    -- LIMIT {$pageParams1}, {$pageParams2}  
                ";  
    
                // die;
                $select = $this->db_easyA->query($sql);
    
                $count = count($select);
    
                return json(["code" => "0", "msg" => "", "count" => $count, "data" => $select]);
            } else {
                return json(["code" => "0", "msg" => "", "count" => 0, "data" => []]);
            }


        } else {
            $customer17 = $this->db_easyA->query("
                SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_jianhe_stock_skc WHERE 商品负责人 IS NOT NULL AND 商品负责人 !='0' GROUP BY 商品负责人
            ");


            foreach ($customer17 as $key => $val) {
                if (checkAdmin()) {
                    if ($key == 0) {
                        $customer17 = $val['name'];
                    }
                } elseif (session('admin.name') == $val['name']) {
                    $customer17 = $val['name'];
                }
            } 
            return View('handle', [
                'customer17' => $customer17
            ]);
        }
    } 


    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_jianhe_stock_skc WHERE 商品负责人 IS NOT NULL AND 商品负责人 !='0' GROUP BY 商品负责人
        ");
        $customer36 = $this->db_easyA->query("
            SELECT 温区 as name, 温区 as value FROM cwl_jianhe_stock_skc WHERE 温区 IS NOT NULL GROUP BY 温区
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_jianhe_stock_skc GROUP BY 店铺名称
        ");

        foreach ($customer17 as $key => $val) {
            if (checkAdmin()) {
                if ($key == 0) {
                    $customer17[$key]['selected'] = true;
                }
            } elseif (session('admin.name') == $val['name']) {
                $customer17[$key]['selected'] = true;
            }
        } 
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer' => $customer, 'customer17' => $customer17, 'customer36' => $customer36]]);
    }

    // 实时数据更新
    public function updateDdata() {
        if (! cache('jianheskc_data_create')) {
            cache('jianheskc_data_create', true, 1800);
            $jianheskcapi = new JianheskcApi;
            $jianheskcapi->skc_data();
            return json(['status' => 1, 'msg' => '更新成功']);
        } else {
            return json(['status' => 0, 'msg' => '当前数据正在更新中，请稍后再试']);
        }

    }

    public function testRedis()
    {
        // $redis = new Redis;
        // echo '<pre>';
        // print_r($redis);
        // die;
        cache('jianheskc_data_create', null);
        // cache('jianheskc_data_create', true, 1800);
    }
}
