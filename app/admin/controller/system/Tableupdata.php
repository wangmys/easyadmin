<?php
namespace app\admin\controller\system;

use think\facade\Db;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\BaseController;
use app\api\controller\lufei\updatatable\sp_customer_stock_skc_2 as Sk2;
use app\api\controller\lufei\updatatable\SpWwShopStockSales as Stock_sales;

/**
 * 报表自动更新
 * Class Tableupdata
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="报表自动更新")
 */
class Tableupdata extends BaseController
{
    protected $db_easyA = '';
    protected $db_bi = '';
    protected $db_binew = '';
    protected $db_sqlsrv = '';
    protected $db_tianqi = '';
    
    /**
     * 构造函数
     * Dingtalk constructor.
     */
    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->db_binew = Db::connect('bi_new');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_tianqi = Db::connect('tianqi');
    }

    /**
     * @NodeAnotation(title="报表自动更新列表")
     */
    public function list() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            $pageParams1 = ($input['page'] - 1) * $input['limit'];
            $pageParams2 = input('limit');

            $sql = "
                SELECT 
                   *
                FROM 
                    cwl_table_update
                WHERE 1

                LIMIT {$pageParams1}, {$pageParams2}  
            ";
            $select = $this->db_easyA->query($sql);

            $sql_sp_customer_stock_skc_2_count = "
                SELECT 
                    count(*) as total
                FROM 
                    sp_customer_stock_skc_2
                WHERE 1
            ";
            $sql_sp_ww_shop_stock_sales_count = "
                SELECT 
                    count(*) as total
                FROM 
                    sp_ww_shop_stock_sales
                WHERE 1
            ";
            $sql_sp_ww_customer_count = "
                SELECT 
                    count(*) as total
                FROM 
                    sp_ww_customer
                WHERE 1
            ";
            $总数_sql_sp_customer_stock_skc_2 = $this->db_binew->query($sql_sp_customer_stock_skc_2_count);
            $总数_sql_sp_ww_shop_stock_sales = $this->db_binew->query($sql_sp_ww_shop_stock_sales_count);
            $总数_sql_sp_ww_customer = $this->db_binew->query($sql_sp_ww_customer_count);
            foreach ($select as $key=>$val) {
                if ($val['表名'] == 'sp_customer_stock_skc_2') {
                    $select[$key]['实时数据'] = $总数_sql_sp_customer_stock_skc_2[0]['total'];
                } elseif ($val['表名'] == 'sp_ww_shop_stock_sales') {
                    $select[$key]['实时数据'] = $总数_sql_sp_ww_shop_stock_sales[0]['total'];
                } elseif ($val['表名'] == 'sp_ww_customer') {
                    $select[$key]['实时数据'] = $总数_sql_sp_ww_customer[0]['total'];
                }
            }

            $sql2 = "
                SELECT 
                    count(*) as total
                FROM 
                cwl_table_update
                WHERE 1
            ";
            $count = $this->db_easyA->query($sql2);

            return json(["code" => "0", "msg" => "", "count" => $count[0]['total'], "data" => $select]);
        } else {
            // $time = time();
            // if ($time < strtotime(date('Y-m-d 20:30:00'))) {
            //     // echo '显示昨天';
            //     $today = date('Y-m-d', strtotime('-1 day', $time));
            // } else {
            //     // echo '显示今天';
            //     $today = date('Y-m-d');
            // }
            return View('list', [
                // 'today' => $today,
            ]);
        }        
    }

    public function updataHandle() {
        if (request()->isAjax()) {
            // 筛选条件
            $input = input();
            // return json(['status' => 1, 'msg' => '执行成功']);
            if (!empty($input['表名'])) {
                if ($input['表名'] == 'sp_customer_stock_skc_2') {
                    if (! cache('sp_customer_stock_skc_2')) {
                        cache('sp_customer_stock_skc_2', true, 300);
                        $sk2 = new Sk2;
                        $res = $sk2->doris();
                        if ($res) {
                            $this->db_easyA->table('cwl_table_update')->where(['表名' => $input['表名']])->update(['跑数完成时间' => date('Y-m-d H:i:s')]);
                            return json(['status' => 1, 'msg' => '执行成功']);
                        } else {
                            return json(['status' => 2, 'msg' => '执行失败，请稍后再试']);
                        }
    
                        // sleep(5);
                        // return json(['status' => 1, 'msg' => '执行成功']);
                    } else {
                        return json(['status' => 4, 'msg' => '别人正在更新此表格，请稍后再试']);    
                    }

                } elseif ($input['表名'] == 'sp_ww_shop_stock_sales') {
                    if (! cache('sp_ww_shop_stock_sales')) {
                        cache('sp_ww_shop_stock_sales', true, 600);
                        $stock_sales = new Stock_sales;
                        $res = $stock_sales->updateDb();
                        if ($res) {
                            $this->db_easyA->table('cwl_table_update')->where(['表名' => $input['表名']])->update(['跑数完成时间' => date('Y-m-d H:i:s')]);
                            return json(['status' => 1, 'msg' => '执行成功']);
                        } else {
                            return json(['status' => 2, 'msg' => '执行失败（线程死锁），请稍后再试']);
                        }
    
                        // sleep(5);
                        // return json(['status' => 1, 'msg' => '执行成功']);
                    } else {
                        return json(['status' => 4, 'msg' => '别人正在更新此表格，请稍后再试']);    
                    }
                } elseif ($input['表名'] == 'sp_ww_customer') {
                    if (! cache('sp_ww_customer')) {
                        cache('sp_ww_customer', true, 600);

                        $res = $this->sp_ww_customer();
                        if ($res) {
                            $this->db_easyA->table('cwl_table_update')->where(['表名' => $input['表名']])->update(['跑数完成时间' => date('Y-m-d H:i:s')]);
                            return json(['status' => 1, 'msg' => '执行成功']);
                        } else {
                            return json(['status' => 2, 'msg' => '执行失败（线程死锁），请稍后再试']);
                        }
    
                        // sleep(5);
                        // return json(['status' => 1, 'msg' => '执行成功']);
                    } else {
                        return json(['status' => 4, 'msg' => '别人正在更新此表格，请稍后再试']);    
                    }
                } else {
                    return json(['status' => 3, 'msg' => '表名不存在']);
                }
                
            }
        }
    }

    public function sp_ww_customer() {
        $sql = "
            SELECT 
                EC.CustomerName AS 店铺名称,
                ECM.Mathod AS 经营模式,
                EC.State AS 省份,
                EC.City AS 城市,
                EC.District AS 区县,
                CustomItem14 AS 仓库面积,
                CustomItem27 AS 营业面积,
                CustomItem3 AS 二件窗,
                CustomItem4 AS 三件窗,
                CustomItem5 AS 四件窗,
                CustomItem6 AS 五件窗,
                CustomItem7 AS 六件窗,
                CustomItem8 AS 七件窗,
                CustomItem9 AS 特价台,
                CustomItem10 AS 休闲裤台,
                CustomItem11 AS 牛仔裤台,
                CustomItem12 AS 单杆,
                CustomItem13 AS 鞋柜,
                CustomItem15 AS 云仓,
                CustomItem17 AS 商品负责人,
                CustomerGrade AS 店铺等级,
                CustomItem30 AS 温度带,
                EC.CustomItem36 AS 温区,
                EC.CustomerId AS 店铺ID,
                EC.CustomerCode 店铺代码,
                EC.CustomItem37 AS 鞋中岛,
                EC.CustomItem38 AS 鞋墙
            FROM ErpCustomer EC 
            LEFT JOIN ErpBaseCustomerMathod ECM ON EC.MathodId=ECM.MathodId
            WHERE EC.ShutOut=0
        ";
        $select = $this->db_sqlsrv->query($sql);
        if ($select) {
            $this->db_bi->execute('TRUNCATE sp_ww_customer;');
            $chunk_list = array_chunk($select, 500);
            // $this->db_easyA->startTrans();

            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_bi->table('sp_ww_customer')->strict(false)->insertAll($val);
            }
        }
        cache('sp_ww_customer', null);
        return true;
    }

    public function test() {
        $sk2 = new Sk2;
        $sk2->doris();
    }

    // 强制清空缓存
    public function clean() {
        cache('sp_customer_stock_skc_2', null);
        cache('sp_ww_shop_stock_sales', null);
        cache('sp_ww_customer', null);
    }
}
