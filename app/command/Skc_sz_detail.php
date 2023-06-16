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
use app\admin\model\bi\SpSkcWinNumModel;
use app\admin\model\bi\SpSkcSzDetailModel;
use app\api\model\kl\ErpRetailModel;

class Skc_sz_detail extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('skc_sz_detail')
            ->setDescription('the skc_sz_detail command');
    }

    protected function execute(Input $input, Output $output)
    {

        ini_set('memory_limit','500M');

        $skc_win_nums = SpSkcWinNumModel::where([])->column('*', 'key_str');

        $all_customers = SpWwCustomerModel::where([['经营模式', 'in', ['直营', '加盟']]])->select()->toArray();
        $dt_price = 50;
        $dc_price = 80;
        if ($all_customers) {
            foreach ($all_customers as $v_customer) {
                // print_r($v_customer);die;
                //test....
                // $v_customer['店铺名称'] = '开平二店';
                // $v_customer['经营模式'] = '加盟';
                // $v_customer['省份'] = '广东省';

                $sql = "select TOP 1 RetailDate from ErpRetail where CustomerId='{$v_customer['店铺ID']}' order by RetailDate asc;";
		        $RetailInfo = Db::connect("sqlsrv")->Query($sql);
                // print_r($RetailInfo);die;

                $arr['area_range'] = (strstr($v_customer['省份'], '广东') || strstr($v_customer['省份'], '广西')) ? '二广' : '内陆';
                $arr['province'] = $v_customer['省份'] ?: '';
                $arr['store_type'] = $v_customer['经营模式'] ?: '';
                $arr['goods_manager'] = $v_customer['商品负责人'] ?: '';
                $arr['store_name'] = $v_customer['店铺名称'] ?: '';
                $arr['start_date'] = $RetailInfo ? $RetailInfo[0]['RetailDate'] : '';
                $arr['store_level'] = $v_customer['店铺等级'] ?: '';
                $arr['store_square'] = $v_customer['营业面积'] ?: '';
                //多少个五件窗计算
                $two_win = $v_customer['二件窗'] ?: 0;
                $three_win = $v_customer['三件窗'] ?: 0;
                $four_win = $v_customer['四件窗'] ?: 0;
                $five_win = $v_customer['五件窗'] ?: 0;
                $six_win = $v_customer['六件窗'] ?: 0;
                $seven_win = $v_customer['七件窗'] ?: 0;
                $arr['five_item_num'] = ceil( (2*60*$two_win + 3*60*$three_win + 4*60*$four_win + 5*60*$five_win + 6*60*$six_win + 7*60*$seven_win) / 300);

                $key_str = $arr['five_item_num'].$arr['area_range'];

                $arr['total_require'] = isset($skc_win_nums[$key_str]) ? $skc_win_nums[$key_str]['skc_num']*$arr['five_item_num'] : 0;//总需求
                
                //总：
                $all_sum = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['大类', 'in', ['内搭', '外套']]])->sum('销售金额');
                if (!$all_sum) continue;//周销为0，不计入统计

                $week_sales_dt_sum = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '短T']])->sum('销售金额');
                $week_sales_fl = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '短T'], ['小类', 'like', "%翻领%"]])->sum('销售金额');
                $week_sales_yl = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '短T'], ['小类', 'like', "%圆领%"]])->sum('销售金额');
                $week_sales_qt = $week_sales_dt_sum-$week_sales_fl-$week_sales_yl;
                $week_sales_xxdc = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '休闲短衬']])->sum('销售金额');
                $week_sales_ztdc = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '正统短衬']])->sum('销售金额');
                //外套-夏季
                $week_sales_jk = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '夏季'], ['大类', '=', '外套'], ['中类', '=', '夹克']])->sum('销售金额');
                $week_sales_tz = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '夏季'], ['大类', '=', '外套'], ['中类', '=', '套装']])->sum('销售金额');
                //短袖小计
                $week_sales_dxxj = $week_sales_dt_sum + $week_sales_xxdc + $week_sales_ztdc + $week_sales_jk + $week_sales_tz;
                //外套-春季
                $week_sales_dx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '春季'], ['大类', '=', '外套'], ['中类', '=', '单西']])->sum('销售金额');
                $week_sales_wtjk = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '春季'], ['大类', '=', '外套'], ['中类', '=', '夹克']])->sum('销售金额');
                $week_sales_nzy = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '春季'], ['大类', '=', '外套'], ['中类', '=', '牛仔衣']])->sum('销售金额');
                $week_sales_py = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '春季'], ['大类', '=', '外套'], ['中类', '=', '皮衣']])->sum('销售金额');
                $week_sales_txk = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '春季'], ['大类', '=', '外套'], ['中类', '=', '套西裤']])->sum('销售金额');
                $week_sales_tx = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['季节归集', '=', '春季'], ['大类', '=', '外套'], ['中类', '=', '套西']])->sum('销售金额');
                //外套小计
                $week_sales_wtxj = $week_sales_dx + $week_sales_wtjk + $week_sales_nzy + $week_sales_py + $week_sales_txk + $week_sales_tx;
                //长T
                $week_sales_ct = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '长T']])->sum('销售金额');
                $week_sales_ztcc = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '正统长衬']])->sum('销售金额');
                $week_sales_xxcc = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '休闲长衬']])->sum('销售金额');
                $week_sales_zzs = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '针织衫']])->sum('销售金额');
                $week_sales_wy = SpWwChunxiaSalesModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['中类', '=', '卫衣']])->sum('销售金额');
                //长袖小计
                $week_sales_cxxj = $week_sales_ct + $week_sales_ztcc + $week_sales_xxcc + $week_sales_zzs + $week_sales_wy;
                // print_r([$week_sales_ct, $week_sales_ztcc, $week_sales_xxcc, $week_sales_zzs, $week_sales_wy, $week_sales_cxxj]);die;
                
                //预计在途skc数
                $skc_fl = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dt_price], ['二级分类', '=', '短T'], ['领型', '=', '翻领']])->sum('库存SKC数');
                $skc_yl = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dt_price], ['二级分类', '=', '短T'], ['领型', '=', '圆领']])->sum('库存SKC数');
                $skc_qt = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dt_price], ['二级分类', '=', '短T'], ['领型', '=', '其他']])->sum('库存SKC数');
                $skc_xxdc = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dc_price], ['二级分类', '=', '休闲短衬'], ['领型', '=', '休闲短衬']])->sum('库存SKC数');
                $skc_ztdc = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dc_price], ['二级分类', '=', '正统短衬'], ['领型', '=', '正统短衬']])->sum('库存SKC数');
                //外套-夏季
                $skc_jk = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['一级分类', '=', '外套'], ['领型', '=', '夹克']])->sum('库存SKC数');
                $skc_tz = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['一级分类', '=', '外套'], ['领型', '=', '套装']])->sum('库存SKC数');
                $skc_dxxj = $skc_fl + $skc_yl + $skc_qt + $skc_xxdc + $skc_ztdc + $skc_jk + $skc_tz;
                //外套-春季
                $skc_dx = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '单西']])->sum('库存SKC数');
                $skc_wtjk = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '夹克']])->sum('库存SKC数');
                $skc_nzy = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '牛仔衣']])->sum('库存SKC数');
                $skc_py = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '皮衣']])->sum('库存SKC数');
                $skc_tx = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '套西']])->sum('库存SKC数');
                $skc_wtxj = $skc_dx + $skc_wtjk + $skc_nzy + $skc_py + $skc_tx;
                $skc_ct = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '长T']])->sum('库存SKC数');
                $skc_ztcc = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '正统长衬']])->sum('库存SKC数');
                $skc_xxcc = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '休闲长衬']])->sum('库存SKC数');
                $skc_zzs = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '针织衫']])->sum('库存SKC数');
                $skc_wy = SpWwChunxiaStockModel::where([['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '卫衣']])->sum('库存SKC数');
                $skc_cxxj = $skc_ct + $skc_ztcc + $skc_xxcc + $skc_zzs + $skc_wy;
                // print_r([$skc_ct, $skc_ztcc, $skc_xxcc, $skc_zzs, $skc_wy, $skc_cxxj]);die;
                //如果是加盟店，要加上2022年库存
                if ($v_customer['经营模式'] == '加盟') {

                    $skc_fl = $skc_fl + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dt_price], ['二级分类', '=', '短T'], ['领型', '=', '翻领']])->sum('库存SKC数');
                    $skc_yl = $skc_yl + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dt_price], ['二级分类', '=', '短T'], ['领型', '=', '圆领']])->sum('库存SKC数');
                    $skc_qt = $skc_qt + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dt_price], ['二级分类', '=', '短T'], ['领型', '=', '其他']])->sum('库存SKC数');
                    $skc_xxdc = $skc_xxdc + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dc_price], ['二级分类', '=', '休闲短衬'], ['领型', '=', '休闲短衬']])->sum('库存SKC数');
                    $skc_ztdc = $skc_ztdc + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['当前零售价', '>', $dc_price], ['二级分类', '=', '正统短衬'], ['领型', '=', '正统短衬']])->sum('库存SKC数');
                    //外套-夏季
                    $skc_jk = $skc_jk + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['一级分类', '=', '外套'], ['领型', '=', '夹克']])->sum('库存SKC数');
                    $skc_tz = $skc_tz + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '夏季'], ['一级分类', '=', '外套'], ['领型', '=', '套装']])->sum('库存SKC数');
                    $skc_dxxj = $skc_fl + $skc_yl + $skc_qt + $skc_xxdc + $skc_ztdc + $skc_jk + $skc_tz;
                    //外套-春季
                    $skc_dx = $skc_dx + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '单西']])->sum('库存SKC数');
                    $skc_wtjk = $skc_wtjk + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '夹克']])->sum('库存SKC数');
                    $skc_nzy = $skc_nzy + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '牛仔衣']])->sum('库存SKC数');
                    $skc_py = $skc_py + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '皮衣']])->sum('库存SKC数');
                    $skc_tx = $skc_tx + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['季节归集', '=', '春季'], ['一级分类', '=', '外套'], ['领型', '=', '套西']])->sum('库存SKC数');
                    $skc_wtxj = $skc_dx + $skc_wtjk + $skc_nzy + $skc_py + $skc_tx;
                    $skc_ct = $skc_ct + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '长T']])->sum('库存SKC数');
                    $skc_ztcc = $skc_ztcc + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '正统长衬']])->sum('库存SKC数');
                    $skc_xxcc = $skc_xxcc + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '休闲长衬']])->sum('库存SKC数');
                    $skc_zzs = $skc_zzs + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '针织衫']])->sum('库存SKC数');
                    $skc_wy = $skc_wy + SpWwXiaStock2022Model::where([['经营模式', '=', '加盟'], ['店铺名称', '=', $v_customer['店铺名称']], ['风格', '=', '基本款'], ['一级分类', '=', '内搭'], ['领型', '=', '卫衣']])->sum('库存SKC数');
                    $skc_cxxj = $skc_ct + $skc_ztcc + $skc_xxcc + $skc_zzs + $skc_wy;

                }

                //预计窗数
                $win_num_fl = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_fl / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_yl = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round(($skc_yl+$skc_qt) / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_xxdc = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_xxdc / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_ztdc = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_ztdc / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_dxxj = $win_num_fl + $win_num_yl + $win_num_xxdc + $win_num_ztdc;
                $win_num_ct = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_ct / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_ztcc = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_ztcc / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_xxcc = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_xxcc / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_zzs = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_zzs / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_wy = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_wy / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_cxxj = $win_num_ct + $win_num_ztcc + $win_num_xxcc + $win_num_zzs + $win_num_wy;
                $win_num_dx = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_dx / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_wtjk = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_wtjk / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_nzy = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_nzy / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_py = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_py / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_tx = isset($skc_win_nums[$key_str]) ? $this->return_right_num( (string)round($skc_tx / $skc_win_nums[$key_str]['skc_num'], 2) ) : 0;
                $win_num_wtxj = $win_num_dx + $win_num_wtjk + $win_num_nzy + $win_num_py + $win_num_tx;

                //溢出
                $overflow_fl = isset($skc_win_nums[$key_str]) ? ($skc_win_nums[$key_str]['skc_fl'] - $skc_fl) : 0;
                $overflow_yl = isset($skc_win_nums[$key_str]) ? ($skc_win_nums[$key_str]['skc_yl'] - $skc_yl - $skc_qt) : 0;
                $overflow_xxdc = isset($skc_win_nums[$key_str]) ? ($skc_win_nums[$key_str]['skc_xxdc'] - $skc_xxdc - $skc_ztdc) : 0;
                $overflow_dxxj = $overflow_fl + $overflow_yl + $overflow_xxdc;

                //统一赋值：
                $arr['week_sales_fl'] = $all_sum ? round($week_sales_fl/$all_sum, 3) * 100 : 0;
                $arr['week_sales_yl'] = $all_sum ? round($week_sales_yl/$all_sum, 3) * 100 : 0;
                $arr['week_sales_qt'] = $all_sum ? round($week_sales_qt/$all_sum, 3) * 100 : 0;
                $arr['week_sales_xxdc'] = $all_sum ? round($week_sales_xxdc/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ztdc'] = $all_sum ? round($week_sales_ztdc/$all_sum, 3) * 100 : 0;
                $arr['week_sales_jk'] = $all_sum ? round($week_sales_jk/$all_sum, 3) * 100 : 0;
                $arr['week_sales_tz'] = $all_sum ? round($week_sales_tz/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dxxj'] = $all_sum ? round($week_sales_dxxj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ct'] = $all_sum ? round($week_sales_ct/$all_sum, 3) * 100 : 0;
                $arr['week_sales_ztcc'] = $all_sum ? round($week_sales_ztcc/$all_sum, 3) * 100 : 0;
                $arr['week_sales_xxcc'] = $all_sum ? round($week_sales_xxcc/$all_sum, 3) * 100 : 0;
                $arr['week_sales_zzs'] = $all_sum ? round($week_sales_zzs/$all_sum, 3) * 100 : 0;
                $arr['week_sales_wy'] = $all_sum ? round($week_sales_wy/$all_sum, 3) * 100 : 0;
                $arr['week_sales_cxxj'] = $all_sum ? round($week_sales_cxxj/$all_sum, 3) * 100 : 0;
                $arr['week_sales_dx'] = $all_sum ? round($week_sales_dx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_wtjk'] = $all_sum ? round($week_sales_wtjk/$all_sum, 3) * 100 : 0;
                $arr['week_sales_nzy'] = $all_sum ? round($week_sales_nzy/$all_sum, 3) * 100 : 0;
                $arr['week_sales_py'] = $all_sum ? round($week_sales_py/$all_sum, 3) * 100 : 0;
                $arr['week_sales_txk'] = $all_sum ? round($week_sales_txk/$all_sum, 3) * 100 : 0;
                $arr['week_sales_tx'] = $all_sum ? round($week_sales_tx/$all_sum, 3) * 100 : 0;
                $arr['week_sales_wtxj'] = $all_sum ? round($week_sales_wtxj/$all_sum, 3) * 100 : 0;

                $arr['skc_fl'] = $skc_fl;
                $arr['skc_yl'] = $skc_yl;
                $arr['skc_qt'] = $skc_qt;
                $arr['skc_xxdc'] = $skc_xxdc;
                $arr['skc_ztdc'] = $skc_ztdc;
                $arr['skc_jk'] = $skc_jk;
                $arr['skc_tz'] = $skc_tz;
                $arr['skc_dxxj'] = $skc_dxxj;
                $arr['skc_ct'] = $skc_ct;
                $arr['skc_ztcc'] = $skc_ztcc;
                $arr['skc_xxcc'] = $skc_xxcc;
                $arr['skc_zzs'] = $skc_zzs;
                $arr['skc_wy'] = $skc_wy;
                $arr['skc_cxxj'] = $skc_cxxj;
                $arr['skc_dx'] = $skc_dx;
                $arr['skc_wtjk'] = $skc_wtjk;
                $arr['skc_nzy'] = $skc_nzy;
                $arr['skc_py'] = $skc_py;
                $arr['skc_tx'] = $skc_tx;
                $arr['skc_wtxj'] = $skc_wtxj;
                
                $arr['win_num_fl'] = $win_num_fl;
                $arr['win_num_yl'] = $win_num_yl;
                $arr['win_num_xxdc'] = $win_num_xxdc;
                $arr['win_num_ztdc'] = $win_num_ztdc;
                $arr['win_num_dxxj'] = $win_num_dxxj;
                $arr['win_num_ct'] = $win_num_ct;
                $arr['win_num_ztcc'] = $win_num_ztcc;
                $arr['win_num_xxcc'] = $win_num_xxcc;
                $arr['win_num_zzs'] = $win_num_zzs;
                $arr['win_num_wy'] = $win_num_wy;
                $arr['win_num_cxxj'] = $win_num_cxxj;
                $arr['win_num_dx'] = $win_num_dx;
                $arr['win_num_wtjk'] = $win_num_wtjk;
                $arr['win_num_nzy'] = $win_num_nzy;
                $arr['win_num_py'] = $win_num_py;
                $arr['win_num_tx'] = $win_num_tx;
                $arr['win_num_wtxj'] = $win_num_wtxj;

                $arr['overflow_fl'] = $overflow_fl;
                $arr['overflow_yl'] = $overflow_yl;
                $arr['overflow_xxdc'] = $overflow_xxdc;
                $arr['overflow_dxxj'] = $overflow_dxxj;
                
                $arr['fill_rate'] = round($arr['win_num_dxxj']/$arr['five_item_num'], 1);
                // print_r($arr);die;

                //入库
                SpSkcSzDetailModel::create($arr);
                // echo 'okk';die;
            }
        }

        echo 'okk';die;
        
    }

    protected function return_right_num($num) {

        $ex = explode('.', $num);
        $return = 0;
        if ($ex && count($ex)>1) {
            if (strlen($ex[1]) == 1) {
                $ex[1] = $ex[1]*10;
            }
            if ($ex[1] >= 30) {
                $return = $ex[0]+1;
            } else {
                $return = $ex[0];
            }
        } else {
            $return = $ex[0];
        }
        return $return;

    }

}
