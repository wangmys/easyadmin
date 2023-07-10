<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\bi\SpWwCustomerModel;
use app\admin\model\bi\SpWwChunxiaSalesModel;
use app\admin\model\bi\SpWwChunxiaStockModel;
use app\admin\model\bi\SpWwXiaStock2022Model;
use app\admin\model\bi\SpSkcShoeNumModel;
use app\admin\model\bi\SpSkcShoeDetailModel;
use app\admin\model\bi\SpSkcConfigModel;
use app\admin\model\bi\CustomerKcSkByWangweiModel;
use app\api\model\kl\ErpRetailModel;

class Skc_shoe_detail extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('skc_shoe_detail')
            ->setDescription('the skc_shoe_detail command');
    }

    protected function execute(Input $input, Output $output)
    {

        ini_set('memory_limit','500M');

        //先清空旧数据再跑
        Db::connect("mysql2")->Query("truncate table sp_skc_shoe_detail;");

        $skc_shoe_nums = SpSkcShoeNumModel::where([])->column('*', 'key_str');

        $all_customers = Db::connect("mysql2")->Query("select c.*,cr.首单日期 from customer c inner join customer_regionid cr on c.CustomerName=cr.店铺名称 where c.Mathod in ('直营', '加盟') and cr.RegionId in ('91', '92', '93', '94', '95', '96', '98');");
        $skc_config = SpSkcConfigModel::where([['config_str', '=', 'skc_price_config']])->field('shoe_price')->find();
        $shoe_price = $skc_config ? $skc_config['shoe_price'] : 120;
        if ($all_customers) {
            foreach ($all_customers as $v_customer) {
                // print_r($v_customer);die;
                //test....
                // $v_customer['CustomerName'] = '龙南一店';
                // $v_customer['Mathod'] = '直营';
                // $v_customer['State'] = '江西省';

                $arr['area_range'] = (strstr($v_customer['State'], '广东') || strstr($v_customer['State'], '广西')) ? '二广' : '内陆';
                $arr['province'] = $v_customer['State'] ?: '';
                $arr['store_type'] = $v_customer['Mathod'] ?: '';
                $arr['goods_manager'] = $v_customer['CustomItem17'] ?: '';
                $arr['store_name'] = $v_customer['CustomerName'] ?: '';
                $arr['start_date'] = $v_customer['首单日期'] ?: null;
                $arr['store_nature'] = $v_customer['CustomItem39'] ?: '';
                $arr['store_level'] = $v_customer['CustomerGrade'] ?: '';
                $arr['store_square'] = $v_customer['StoreArea'] ?: '';
                $arr['warehouse_square'] = $v_customer['CustomItem14'] ?: '';
                $arr['xg_num'] = $v_customer['CustomItem13'] ?: '0';
                $arr['xq_num'] = $v_customer['CustomItem38'] ?: '0';
                $arr['xzd_num'] = $v_customer['CustomItem37'] ?: '0';
                $arr['xjxj_num'] = $arr['xg_num']+$arr['xq_num'];

                $require_num = $this->get_require_num($skc_shoe_nums, $arr['warehouse_square'], $arr['xjxj_num']);
                $arr['require_zt'] = $require_num['skc_zt'] ?: '0';
                $arr['require_xx'] = $require_num['skc_xx'] ?: '0';
                $arr['require_ydx'] = $require_num['skc_ydx'] ?: '0';
                $arr['require_lx'] = $require_num['skc_lx'] ?: '0';
                $require_total = $arr['require_zt'] + $arr['require_xx'] + $arr['require_ydx'] + $arr['require_lx'];
                // print_r($arr);die;

                //总：
                $all_sum = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '鞋履']])->sum('销售金额');
                $week_sales_new_ztpx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '=', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '正统皮鞋']])->sum('销售金额');
                $week_sales_new_xxx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '=', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '休闲鞋']])->sum('销售金额');
                $week_sales_new_ydx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '=', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '运动鞋']])->sum('销售金额');
                $week_sales_new_lx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '=', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '凉鞋']])->sum('销售金额');
                $week_sales_new_xj = $week_sales_new_ztpx + $week_sales_new_xxx + $week_sales_new_ydx + $week_sales_new_lx;
                $week_sales_old_ztpx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '<>', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '正统皮鞋']])->sum('销售金额');
                $week_sales_old_xxx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '<>', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '休闲鞋']])->sum('销售金额');
                $week_sales_old_ydx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '<>', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '运动鞋']])->sum('销售金额');
                $week_sales_old_lx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['年份', '<>', config('skc.shoe_year')], ['大类', '=', '鞋履'], ['中类', '=', '凉鞋']])->sum('销售金额');
                $week_sales_old_xj = $week_sales_old_ztpx + $week_sales_old_xxx + $week_sales_old_ydx + $week_sales_old_lx;
                //预计在店skc
                $skc_new_ztpx = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级时间分类', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '正统皮鞋']])->sum('库存SKC数');
                $skc_new_xxx = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级时间分类', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '休闲鞋']])->sum('库存SKC数');
                $skc_new_ydx = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级时间分类', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '运动鞋']])->sum('库存SKC数');
                $skc_new_lx = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级时间分类', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '凉鞋']])->sum('库存SKC数');
                $skc_new_xj = $skc_new_ztpx + $skc_new_xxx + $skc_new_ydx + $skc_new_lx;
                $skc_old_ztpx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '正统皮鞋']])->count();
                $skc_old_xxx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '休闲鞋']])->count();
                $skc_old_ydx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '运动鞋']])->count();
                $skc_old_lx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '凉鞋']])->count();
                $skc_old_xj = $skc_old_ztpx + $skc_old_xxx + $skc_old_ydx + $skc_old_lx;
                $skc_zj = $skc_new_xj + $skc_old_xj;
                $skc_fill_rate = 0;
                if ($v_customer['Mathod'] == '直营') {
                    $skc_fill_rate = $require_total ? round( $skc_new_xj/$require_total, 2 ) : 0;
                } else {
                    $skc_fill_rate = $require_total ? round( $skc_zj/$require_total, 2 ) : 0;
                }

                //预计在店断码个数
                $duanma_new_ztpx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '正统皮鞋']])->count();
                $duanma_new_xxx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '休闲鞋']])->count();
                $duanma_new_ydx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '运动鞋']])->count();
                $duanma_new_lx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '凉鞋']])->count();
                $duanma_new_xj = $duanma_new_ztpx + $duanma_new_xxx + $duanma_new_ydx + $duanma_new_lx;
                $duanma_old_ztpx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '正统皮鞋']])->count();
                $duanma_old_xxx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '休闲鞋']])->count();
                $duanma_old_ydx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '运动鞋']])->count();
                $duanma_old_lx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '凉鞋']])->count();
                $duanma_old_xj = $duanma_old_ztpx + $duanma_old_xxx + $duanma_old_ydx + $duanma_old_lx;
                $duanma_zsl = $duanma_new_xj + $duanma_old_xj;

                //预计在店数量
                $instore_new_ztpx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '正统皮鞋']])->sum('预计库存Quantity');
                $instore_new_xxx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '休闲鞋']])->sum('预计库存Quantity');
                $instore_new_ydx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '运动鞋']])->sum('预计库存Quantity');
                $instore_new_lx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '=', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '凉鞋']])->sum('预计库存Quantity');
                $instore_new_xj = $instore_new_ztpx + $instore_new_xxx + $instore_new_ydx + $instore_new_lx;
                $instore_old_ztpx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '正统皮鞋']])->sum('预计库存Quantity');
                $instore_old_xxx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '休闲鞋']])->sum('预计库存Quantity');
                $instore_old_ydx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '运动鞋']])->sum('预计库存Quantity');
                $instore_old_lx = CustomerKcSkByWangweiModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')],   ['CustomerName', '=', $v_customer['CustomerName']], ['TimeCategoryName1', '<>', config('skc.shoe_year')], ['当前零售价', '>', $shoe_price], ['预计库存Quantity', '>', config('skc.shoe_expect_stock')], ['齐码情况', '<', config('skc.shoe_lianma_num')], ['一级分类', '=', '鞋履'], ['二级分类', '=', '凉鞋']])->sum('预计库存Quantity');
                $instore_old_xj = $instore_old_ztpx + $instore_old_xxx + $instore_old_ydx + $instore_old_lx;
                $instore_zsl = $instore_new_xj + $instore_old_xj;
                $instore_dksd = $skc_zj ? round($instore_zsl/$skc_zj, 0) : 0;

                //溢出提醒
                if ($v_customer['Mathod'] == '直营') {
                    $overflow_ztpx = $arr['require_zt'] - $skc_new_ztpx;
                    $overflow_xxx = $arr['require_xx'] - $skc_new_xxx;
                    $overflow_ydx = $arr['require_ydx'] - $skc_new_ydx;
                } else {
                    $overflow_ztpx = $arr['require_zt'] - ($skc_new_ztpx + $skc_old_ztpx);
                    $overflow_xxx = $arr['require_xx'] - ($skc_new_xxx + $skc_old_xxx);
                    $overflow_ydx = $arr['require_ydx'] - ($skc_new_ydx + $skc_old_ydx);
                }
                $overflow_zj = $overflow_ztpx + $overflow_xxx + $overflow_ydx;

                //统一赋值：
                $arr['week_sales_new_ztpx'] = $all_sum ? round($week_sales_new_ztpx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_new_xxx'] = $all_sum ? round($week_sales_new_xxx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_new_ydx'] = $all_sum ? round($week_sales_new_ydx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_new_lx'] = $all_sum ? round($week_sales_new_lx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_new_xj'] = $all_sum ? round($week_sales_new_xj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_old_ztpx'] = $all_sum ? round($week_sales_old_ztpx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_old_xxx'] = $all_sum ? round($week_sales_old_xxx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_old_ydx'] = $all_sum ? round($week_sales_old_ydx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_old_lx'] = $all_sum ? round($week_sales_old_lx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_old_xj'] = $all_sum ? round($week_sales_old_xj/$all_sum, 3) * 100 : 0;

                //预计在店skc
                $arr['skc_new_ztpx'] = $skc_new_ztpx;
                $arr['skc_new_xxx'] = $skc_new_xxx;
                $arr['skc_new_ydx'] = $skc_new_ydx;
                $arr['skc_new_lx'] = $skc_new_lx;
                $arr['skc_new_xj'] = $skc_new_xj;
                $arr['skc_old_ztpx'] = $skc_old_ztpx;
                $arr['skc_old_xxx'] = $skc_old_xxx;
                $arr['skc_old_ydx'] = $skc_old_ydx;
                $arr['skc_old_lx'] = $skc_old_lx;
                $arr['skc_old_xj'] = $skc_old_xj;
                $arr['skc_zj'] = $skc_zj;
                $arr['skc_fill_rate'] = $skc_fill_rate;

                //预计在点断码个数
                $arr['duanma_new_ztpx'] = $duanma_new_ztpx;
                $arr['duanma_new_xxx'] = $duanma_new_xxx;
                $arr['duanma_new_ydx'] = $duanma_new_ydx;
                $arr['duanma_new_lx'] = $duanma_new_lx;
                $arr['duanma_new_xj'] = $duanma_new_xj;
                $arr['duanma_old_ztpx'] = $duanma_old_ztpx;
                $arr['duanma_old_xxx'] = $duanma_old_xxx;
                $arr['duanma_old_ydx'] = $duanma_old_ydx;
                $arr['duanma_old_lx'] = $duanma_old_lx;
                $arr['duanma_old_xj'] = $duanma_old_xj;
                $arr['duanma_zsl'] = $duanma_zsl;
                
                //预计在店数量
                $arr['instore_new_ztpx'] = $instore_new_ztpx;
                $arr['instore_new_xxx'] = $instore_new_xxx;
                $arr['instore_new_ydx'] = $instore_new_ydx;
                $arr['instore_new_lx'] = $instore_new_lx;
                $arr['instore_new_xj'] = $instore_new_xj;
                $arr['instore_old_ztpx'] = $instore_old_ztpx;
                $arr['instore_old_xxx'] = $instore_old_xxx;
                $arr['instore_old_ydx'] = $instore_old_ydx;
                $arr['instore_old_lx'] = $instore_old_lx;
                $arr['instore_old_xj'] = $instore_old_xj;
                $arr['instore_zsl'] = $instore_zsl;
                $arr['instore_dksd'] = $instore_dksd;
                
                //溢出
                $arr['overflow_ztpx'] = $overflow_ztpx;
                $arr['overflow_xxx'] = $overflow_xxx;
                $arr['overflow_ydx'] = $overflow_ydx;
                $arr['overflow_zj'] = $overflow_zj;

                // print_r($arr);die;
                // //入库
                SpSkcShoeDetailModel::create($arr);
                // echo 'okk';die;
            }
        }

        echo 'okk';die;
        
    }

    protected function get_require_num($skc_shoe_nums, $warehouse_square, $xjxj_num) {

        $res = [
            'skc_zt' => '0',
            'skc_xx' => '0',
            'skc_ydx' => '0',
            'skc_lx' => '0',
        ];
        $key_str = config('skc.shoe_max_warehouse_square').$xjxj_num;
        if ($warehouse_square) {
            $warehouse_square = trim($warehouse_square);
            if (preg_match("/[\x7f-\xff]/", $warehouse_square)) {//包含中文的，按最大仓库面积算

                if (isset($skc_shoe_nums[$key_str])) {
                    $res['skc_zt'] = $skc_shoe_nums[$key_str]['skc_zt'];
                    $res['skc_xx'] = $skc_shoe_nums[$key_str]['skc_xx'];
                    $res['skc_ydx'] = $skc_shoe_nums[$key_str]['skc_ydx'];
                    $res['skc_lx'] = $skc_shoe_nums[$key_str]['skc_lx'];
                }

            } else {

                if (is_numeric($warehouse_square)) {//纯数值

                    if ($warehouse_square >= 30) {//30-39平

                        $res = $this->return_skc_shoe_num('30-39平'.$xjxj_num, $skc_shoe_nums);

                    } elseif ($warehouse_square >= 20 && $warehouse_square <= 29) {//20-29平

                        $res = $this->return_skc_shoe_num('20-29平'.$xjxj_num, $skc_shoe_nums);

                    }  elseif ($warehouse_square >= 11 && $warehouse_square <= 19) {//11-19平

                        $res = $this->return_skc_shoe_num('11-19平'.$xjxj_num, $skc_shoe_nums);
                        
                    } else {//10平以下
                        
                        $res = $this->return_skc_shoe_num('10平以下'.$xjxj_num, $skc_shoe_nums);

                    }
 
                } else {//非纯数值，按最大仓库面积算

                    if (isset($skc_shoe_nums[$key_str])) {
                        $res['skc_zt'] = $skc_shoe_nums[$key_str]['skc_zt'];
                        $res['skc_xx'] = $skc_shoe_nums[$key_str]['skc_xx'];
                        $res['skc_ydx'] = $skc_shoe_nums[$key_str]['skc_ydx'];
                        $res['skc_lx'] = $skc_shoe_nums[$key_str]['skc_lx'];
                    }

                }

            }

        }

        return $res;

    }

    protected function return_skc_shoe_num($key_str, $skc_shoe_nums) {

        $res = [
            'skc_zt' => '0',
            'skc_xx' => '0',
            'skc_ydx' => '0',
            'skc_lx' => '0',
        ];
        if (isset($skc_shoe_nums[$key_str])) {
            $res['skc_zt'] = $skc_shoe_nums[$key_str]['skc_zt'];
            $res['skc_xx'] = $skc_shoe_nums[$key_str]['skc_xx'];
            $res['skc_ydx'] = $skc_shoe_nums[$key_str]['skc_ydx'];
            $res['skc_lx'] = $skc_shoe_nums[$key_str]['skc_lx'];
        }
        return $res;

    }

}
