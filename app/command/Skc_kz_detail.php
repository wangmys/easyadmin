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
use app\admin\model\bi\SpWwXiaJiamengStock2022Model;
use app\admin\model\bi\SpSkcKzNumModel;
use app\admin\model\bi\SpSkcKzDetailModel;
use app\admin\model\bi\SpSkcConfigModel;
use app\api\model\kl\ErpRetailModel;

class Skc_kz_detail extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('skc_kz_detail')
            ->setDescription('the skc_kz_detail command');
    }

    protected function execute(Input $input, Output $output)
    {

        ini_set('memory_limit','500M');

        //先清空旧数据再跑
        Db::connect("mysql2")->Query("truncate table sp_skc_kz_detail;");

        $skc_kt_nums = SpSkcKzNumModel::where([])->column('*', 'kt_num');

        $customer_regionid_notin = config('skc.customer_regionid_notin');
        $all_customers = Db::connect("mysql2")->Query("select c.*,cr.首单日期 from customer c inner join customer_regionid cr on c.CustomerName=cr.店铺名称 where c.Mathod in ('直营', '加盟') and cr.RegionId not in ($customer_regionid_notin);");
        $skc_config = SpSkcConfigModel::where([['config_str', '=', 'skc_price_config']])->field('dk_price,ck_price')->find();
        $dk_price = $skc_config ? $skc_config['dk_price'] : 70;//短裤
        $ck_price = $skc_config ? $skc_config['ck_price'] : 100;//长裤
        $stock_year_22_chun = 4;
        $stock_year_22_23 = 2;

        if ($all_customers) {
            foreach ($all_customers as $v_customer) {
                //test....
                // $v_customer['CustomerName'] = '开平二店';
                // $v_customer['店铺ID'] = 'C991000484';
                // $v_customer['Mathod'] = '加盟';
                // $v_customer['State'] = '广东省';

                $arr['area_range'] = (strstr($v_customer['State'], '广东') || strstr($v_customer['State'], '广西')) ? '二广' : '内陆';
                $arr['province'] = $v_customer['State'] ?: '';
                $arr['store_type'] = $v_customer['Mathod'] ?: '';
                $arr['goods_manager'] = $v_customer['CustomItem17'] ?: '';
                $arr['store_name'] = $v_customer['CustomerName'] ?: '';
                $arr['start_date'] = $v_customer['首单日期'] ?: null;
                $arr['store_level'] = $v_customer['CustomerGrade'] ?: '';
                $arr['store_square'] = $v_customer['StoreArea'] ?: '';
                $arr['xxkg_num'] = $v_customer['CustomItem10'] ?: '0';
                $arr['nzkg_num'] = $v_customer['CustomItem11'] ?: '0';   
                $arr['kthj_num'] = $arr['xxkg_num']+$arr['nzkg_num'];   
                $arr['require_nz'] = isset($skc_kt_nums[$arr['kthj_num']]) ? $skc_kt_nums[$arr['kthj_num']]['skc_cknz'] : '0';   
                $arr['require_xx'] = isset($skc_kt_nums[$arr['kthj_num']]) ? $skc_kt_nums[$arr['kthj_num']]['skc_ckxx'] : '0';   
                $arr['require_sj'] = isset($skc_kt_nums[$arr['kthj_num']]) ? $skc_kt_nums[$arr['kthj_num']]['skc_cksj'] : '0';
                //总：
                $all_sum = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装']])->sum('销售金额');
                //牛仔长裤
                $week_sales_ck_ckxj = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '牛仔长裤']])->sum('销售金额');
                $week_sales_ck_sw = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '牛仔长裤'], ['小类', '=', '商务牛仔长裤']])->sum('销售金额');
                $week_sales_ck_nz = $week_sales_ck_ckxj-$week_sales_ck_sw;
                //休闲长裤
                $week_sales_ck_xxck_sum = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '休闲长裤']])->sum('销售金额');
                $week_sales_ck_xxsw = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '休闲长裤'], ['小类', '=', '商务休闲长裤']])->sum('销售金额');
                $week_sales_ck_xx = $week_sales_ck_xxck_sum - $week_sales_ck_xxsw;
                //西裤-化纤
                $week_sales_ck_hx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '西裤']])->sum('销售金额');
                $week_sales_ck_xxxj = $week_sales_ck_xxck_sum + $week_sales_ck_hx;
                //松紧长裤
                $week_sales_ck_gz = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧长裤'], ['小类', 'like', '%工装%']])->sum('销售金额');
                $week_sales_ck_kk = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧长裤'], ['小类', 'like', '%宽口%']])->sum('销售金额');
                $week_sales_ck_sj = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧长裤'], ['小类', 'like', '%束脚%']])->sum('销售金额');
                $week_sales_ck_wk = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧长裤'], ['小类', 'like', '%卫裤%']])->sum('销售金额');
                $week_sales_ck_sjsj = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧长裤'], ['小类', '=', '松紧长裤']])->sum('销售金额');
                $week_sales_ck_lw = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧长裤'], ['小类', 'like', '%罗纹%']])->sum('销售金额');
                $week_sales_ck_sjxj = $week_sales_ck_gz + $week_sales_ck_kk + $week_sales_ck_sj + $week_sales_ck_wk + $week_sales_ck_sjsj + $week_sales_ck_lw;
                //长裤总计
                $week_sales_ck_zj = $week_sales_ck_ckxj + $week_sales_ck_xxxj + $week_sales_ck_sjxj;
                //短裤
                $week_sales_dk_nz = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '牛仔短裤'], ['小类', 'like', '%牛仔%']])->sum('销售金额');
                $week_sales_dk_sw = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '牛仔短裤'], ['小类', 'like', '%商务%']])->sum('销售金额');
                $week_sales_dk_sj = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧短裤'], ['小类', 'like', '%松紧%']])->sum('销售金额');
                $week_sales_dk_sjgz = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '松紧短裤'], ['小类', 'like', '%工装%']])->sum('销售金额');
                $week_sales_dk_xxgz = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '休闲短裤'], ['小类', 'like', '%工装%']])->sum('销售金额');
                $week_sales_dk_xx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['CustomerName']], ['大类', '=', '下装'], ['中类', '=', '休闲短裤'], ['小类', 'like', '%休闲%']])->sum('销售金额');
                //短裤总计
                $week_sales_dk_zj = $week_sales_dk_nz + $week_sales_dk_sw + $week_sales_dk_sj + $week_sales_dk_sjgz + $week_sales_dk_xxgz + $week_sales_dk_xx;

                //skc
                //skc-牛仔长裤
                $skc_ck_nzxj = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '牛仔长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_sw = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '牛仔长裤'], ['分类', '=', '商务牛仔长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_nz = $skc_ck_nzxj - $skc_ck_sw;
                //skc-休闲长裤
                $skc_ck_xxck_sum = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '休闲长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_xxsw = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '休闲长裤'], ['分类', '=', '商务休闲长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_xx = $skc_ck_xxck_sum - $skc_ck_xxsw;
                //skc-西裤
                $skc_ck_hx = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '西裤'], ['分类', '=', '化纤西裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_xxxj = $skc_ck_xxck_sum + $skc_ck_hx;
                //skc-松紧长裤
                $skc_ck_gz = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_kk = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%宽口%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_sjsj = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%束脚%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_wk = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%卫裤%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_sj = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧长裤'], ['分类', '=', '松紧长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_lw = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%罗纹%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_ck_sjxj = $skc_ck_gz + $skc_ck_kk + $skc_ck_sjsj + $skc_ck_wk + $skc_ck_sj + $skc_ck_lw;
                //skc-长裤总计
                $skc_ck_zj = $skc_ck_nzxj + $skc_ck_xxxj + $skc_ck_sjxj;
                //skc-短裤
                $skc_dk_nz = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '牛仔短裤'], ['分类', 'like', '%牛仔%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_dk_sw = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '牛仔短裤'], ['分类', 'like', '%商务%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_dk_sj = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧短裤'], ['分类', 'like', '%松紧%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_dk_sjgz = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '松紧短裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_dk_xxgz = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '休闲短裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_dk_xx = SpWwChunxiaStockModel::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['二级分类', '=', '休闲短裤'], ['分类', 'like', '%休闲%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');
                $skc_dk_zj = $skc_dk_nz + $skc_dk_sw + $skc_dk_sj + $skc_dk_sjgz + $skc_dk_xxgz + $skc_dk_xx;
                //skc-总计（长裤+短裤）
                $skc_zj = $skc_ck_zj + $skc_dk_zj;

                if ($v_customer['Mathod'] == '加盟') {

                    //skc-牛仔长裤
                    $skc_ck_nzxj = $skc_ck_nzxj 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '牛仔长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '牛仔长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_sw = $skc_ck_sw 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '牛仔长裤'], ['分类', '=', '商务牛仔长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '牛仔长裤'], ['分类', '=', '商务牛仔长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_nz = $skc_ck_nzxj - $skc_ck_sw;

                    //skc-休闲长裤
                    $skc_ck_xxck_sum = $skc_ck_xxck_sum
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '休闲长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '休闲长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_xxsw = $skc_ck_xxsw 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '休闲长裤'], ['分类', '=', '商务休闲长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '休闲长裤'], ['分类', '=', '商务休闲长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_xx = $skc_ck_xxck_sum - $skc_ck_xxsw;

                    //skc-西裤
                    $skc_ck_hx = $skc_ck_hx 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '西裤'], ['分类', '=', '化纤西裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '西裤'], ['分类', '=', '化纤西裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_xxxj = $skc_ck_xxck_sum + $skc_ck_hx;

                    //skc-松紧长裤
                    $skc_ck_gz = $skc_ck_gz 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_kk = $skc_ck_kk 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%宽口%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%宽口%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_sjsj = $skc_ck_sjsj 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%束脚%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%束脚%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_wk = $skc_ck_wk 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%卫裤%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%卫裤%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_sj = $skc_ck_sj 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧长裤'], ['分类', '=', '松紧长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧长裤'], ['分类', '=', '松紧长裤'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_lw = $skc_ck_lw 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%罗纹%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧长裤'], ['分类', 'like', '%罗纹%'], ['当前零售价', '>', $ck_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_ck_sjxj = $skc_ck_gz + $skc_ck_kk + $skc_ck_sjsj + $skc_ck_wk + $skc_ck_sj + $skc_ck_lw;
                    //skc-长裤总计
                    $skc_ck_zj = $skc_ck_nzxj + $skc_ck_xxxj + $skc_ck_sjxj;
                    //skc-短裤
                    $skc_dk_nz = $skc_dk_nz 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '牛仔短裤'], ['分类', 'like', '%牛仔%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '牛仔短裤'], ['分类', 'like', '%牛仔%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_dk_sw = $skc_dk_sw 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '牛仔短裤'], ['分类', 'like', '%商务%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '牛仔短裤'], ['分类', 'like', '%商务%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_dk_sj = $skc_dk_sj 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧短裤'], ['分类', 'like', '%松紧%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧短裤'], ['分类', 'like', '%松紧%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_dk_sjgz = $skc_dk_sjgz 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '松紧短裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '松紧短裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_dk_xxgz = $skc_dk_xxgz 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '休闲短裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '休闲短裤'], ['分类', 'like', '%工装%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_dk_xx = $skc_dk_xx 
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['二级分类', '=', '休闲短裤'], ['分类', 'like', '%休闲%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_chun]])->sum('库存SKC数')
                    + SpWwXiaJiamengStock2022Model::where([['`当前零售价`'.'/'.'`零售价`', '>=', config('skc.shoe_proportion')], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', '下装'],  ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['二级分类', '=', '休闲短裤'], ['分类', 'like', '%休闲%'], ['当前零售价', '>', $dk_price], ['预计库存', '>', $stock_year_22_23]])->sum('库存SKC数');

                    $skc_dk_zj = $skc_dk_nz + $skc_dk_sw + $skc_dk_sj + $skc_dk_sjgz + $skc_dk_xxgz + $skc_dk_xx;
                    //skc-总计（长裤+短裤）
                    $skc_zj = $skc_ck_zj + $skc_dk_zj;
                
                }

                //溢出
                $overflow_nzck = $arr['require_nz'] - $skc_ck_nzxj;
                $overflow_xxck = $arr['require_xx'] - $skc_ck_xxck_sum;
                $overflow_sjck = $arr['require_sj'] - $skc_ck_sjxj;
                $overflow_zj = $overflow_nzck + $overflow_xxck + $overflow_sjck;
                $require_total = $arr['require_nz'] + $arr['require_xx'] + $arr['require_sj'];
                $fill_rate = $require_total ? round( $skc_ck_zj / $require_total , 2) : 0;

                //统一赋值：
                $arr['week_sales_ck_nz'] = $all_sum ? round($week_sales_ck_nz/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_sw'] = $all_sum ? round($week_sales_ck_sw/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_ckxj'] = $all_sum ? round($week_sales_ck_ckxj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_xx'] = $all_sum ? round($week_sales_ck_xx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_xxsw'] = $all_sum ? round($week_sales_ck_xxsw/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_hx'] = $all_sum ? round($week_sales_ck_hx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_xxxj'] = $all_sum ? round($week_sales_ck_xxxj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_gz'] = $all_sum ? round($week_sales_ck_gz/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_kk'] = $all_sum ? round($week_sales_ck_kk/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_sj'] = $all_sum ? round($week_sales_ck_sj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_wk'] = $all_sum ? round($week_sales_ck_wk/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_sjsj'] = $all_sum ? round($week_sales_ck_sjsj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_lw'] = $all_sum ? round($week_sales_ck_lw/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_sjxj'] = $all_sum ? round($week_sales_ck_sjxj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ck_zj'] = $all_sum ? round($week_sales_ck_zj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dk_nz'] = $all_sum ? round($week_sales_dk_nz/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dk_sw'] = $all_sum ? round($week_sales_dk_sw/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dk_sj'] = $all_sum ? round($week_sales_dk_sj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dk_sjgz'] = $all_sum ? round($week_sales_dk_sjgz/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dk_xxgz'] = $all_sum ? round($week_sales_dk_xxgz/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dk_xx'] = $all_sum ? round($week_sales_dk_xx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dk_zj'] = $all_sum ? round($week_sales_dk_zj/$all_sum, 3) * 100 : 0;
                
                $arr['skc_ck_nz'] = $skc_ck_nz;
                $arr['skc_ck_sw'] = $skc_ck_sw;
                $arr['skc_ck_nzxj'] = $skc_ck_nzxj;
                $arr['skc_ck_xx'] = $skc_ck_xx;
                $arr['skc_ck_xxsw'] = $skc_ck_xxsw;
                $arr['skc_ck_hx'] = $skc_ck_hx;
                $arr['skc_ck_xxxj'] = $skc_ck_xxxj;
                $arr['skc_ck_gz'] = $skc_ck_gz;
                $arr['skc_ck_kk'] = $skc_ck_kk;
                $arr['skc_ck_sjsj'] = $skc_ck_sjsj;
                $arr['skc_ck_wk'] = $skc_ck_wk;
                $arr['skc_ck_sj'] = $skc_ck_sj;
                $arr['skc_ck_lw'] = $skc_ck_lw;
                $arr['skc_ck_sjxj'] = $skc_ck_sjxj;
                $arr['skc_ck_zj'] = $skc_ck_zj;
                $arr['skc_dk_nz'] = $skc_dk_nz;
                $arr['skc_dk_sw'] = $skc_dk_sw;
                $arr['skc_dk_sj'] = $skc_dk_sj;
                $arr['skc_dk_sjgz'] = $skc_dk_sjgz;
                $arr['skc_dk_xxgz'] = $skc_dk_xxgz;
                $arr['skc_dk_xx'] = $skc_dk_xx;
                $arr['skc_dk_zj'] = $skc_dk_zj;
                $arr['skc_zj'] = $skc_zj;
                
                $arr['overflow_nzck'] = $overflow_nzck;
                $arr['overflow_xxck'] = $overflow_xxck;
                $arr['overflow_sjck'] = $overflow_sjck;
                $arr['overflow_zj'] = $overflow_zj;
                $arr['fill_rate'] = $fill_rate;

                // print_r($arr);die;

                //入库
                SpSkcKzDetailModel::create($arr);

            }
        }

        echo 'okk';die;
        
    }

}
