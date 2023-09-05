<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\bi\SpLypPuhuoYuncangkeyongModel;
use app\admin\model\bi\SpLypPuhuoLiangzhouModel;
use app\admin\model\bi\SpLypPuhuoConfigModel;
use app\admin\model\bi\SpLypPuhuoWaitGoodsModel;
use app\admin\model\bi\SpLypPuhuoTiModel;
use app\admin\model\bi\SpLypPuhuoTiGoodsModel;
use app\admin\model\bi\SpLypPuhuoCustomerSortModel;
use app\admin\model\bi\SpLypPuhuoScoreModel;
use app\admin\model\bi\SpLypPuhuoColdtohotModel;
use app\admin\model\bi\SpLypPuhuoHottocoldModel;
use app\admin\model\bi\SpLypPuhuoRuleAModel;
use app\admin\model\bi\SpLypPuhuoRuleBModel;
use app\admin\model\bi\SpLypPuhuoLogModel;
use app\admin\model\bi\SpLypPuhuoCurLogModel;
use app\admin\model\bi\SpLypPuhuoShangshidayModel;
use app\admin\model\bi\SpLypPuhuoDaxiaomaSkcnumModel;
use app\admin\model\bi\SpLypPuhuoDaxiaomaCustomerModel;
use app\admin\model\bi\SpLypPuhuoDaxiaomaCustomerSortModel;
use app\admin\model\bi\SpLypPuhuoZhidingGoodsModel;
use app\admin\model\bi\SpWwChunxiaStockModel;
// use app\admin\model\CustomerModel;
//每天凌晨04:00跑，预计10分钟跑完20个货号
//1.sp_lyp_puhuo_customer_sort(主码排序)  2.sp_lyp_puhuo_cur_log/sp_lyp_puhuo_log   3.sp_lyp_puhuo_daxiaoma_customer_sort（大小码排序） 4.sp_lyp_puhuo_end_data（最终铺货结果）
class Puhuo_start1 extends Command
{
    protected $db_easy;
    protected $list_rows=160;//每页条数
    protected $page=1;//当前页
    // protected $customer_model;
    protected $sp_ww_chunxia_stock_model;
    protected $puhuo_rule_model;
    protected $puhuo_customer_sort_model;
    protected $puhuo_log_model;
    protected $puhuo_cur_log_model;
    protected $puhuo_wait_goods_model;
    protected $puhuo_shangshiday_model;
    protected $puhuo_score_model;
    protected $puhuo_daxiaoma_skcnum_model;
    protected $puhuo_daxiaoma_customer_model;
    protected $puhuo_daxiaoma_customer_sort_model;
    protected $puhuo_ti_goods_model;
    protected $puhuo_zhiding_goods_model;

    protected function configure()
    {
        // 指令配置
        $this->setName('Puhuo_start1')
            ->addArgument('list_rows', Argument::OPTIONAL)//每页条数
            ->setDescription('the Puhuo_start1 command');
            $this->db_easy = Db::connect("mysql");

        // $this->customer_model = new CustomerModel();
        $this->sp_ww_chunxia_stock_model = new SpWwChunxiaStockModel();
        $this->puhuo_customer_sort_model = new SpLypPuhuoCustomerSortModel();
        $this->puhuo_log_model = new SpLypPuhuoLogModel();
        $this->puhuo_cur_log_model = new SpLypPuhuoCurLogModel();    
        $this->puhuo_wait_goods_model = new SpLypPuhuoWaitGoodsModel();
        $this->puhuo_shangshiday_model = new SpLypPuhuoShangshidayModel();
        $this->puhuo_score_model = new SpLypPuhuoScoreModel();
        $this->puhuo_daxiaoma_skcnum_model = new SpLypPuhuoDaxiaomaSkcnumModel();
        $this->puhuo_daxiaoma_customer_model = new SpLypPuhuoDaxiaomaCustomerModel();
        $this->puhuo_daxiaoma_customer_sort_model = new SpLypPuhuoDaxiaomaCustomerSortModel();
        $this->puhuo_ti_goods_model = new SpLypPuhuoTiGoodsModel();
        $this->puhuo_zhiding_goods_model = new SpLypPuhuoZhidingGoodsModel();

    }

    protected function execute(Input $input, Output $output) {

        ini_set('memory_limit','1024M');
        $db = Db::connect("mysql");

        //铺货规则获取
        $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();
        $puhuo_rule = $puhuo_config['puhuo_rule'];
        if ($puhuo_rule == 1) {//A方案
            $this->puhuo_rule_model = new SpLypPuhuoRuleAModel();
        } else {
            $this->puhuo_rule_model = new SpLypPuhuoRuleBModel();
        }

        $list_rows    = $input->getArgument('list_rows') ?: 2000;//每页条数
        
        $data = $this->get_wait_goods_data($list_rows);
        // print_r($data);die;
        if ($data) {

            //先清空旧数据再跑
            $this->db_easy->Query("truncate table sp_lyp_puhuo_customer_sort;");
            $this->db_easy->Query("truncate table sp_lyp_puhuo_daxiaoma_customer_sort;");
            $this->db_easy->Query("truncate table sp_lyp_puhuo_cur_log;");
            $this->db_easy->Query("truncate table sp_lyp_puhuo_log;");

            $customer_regionid_notin_text = config('skc.customer_regionid_notin_text');
            $new_customers = $this->db_easy->Query("select CustomerName from customer where Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 
            and CustomerName not in (select 店铺名称 from customer_first);");//剔除新店
            $new_customers = $new_customers ? array_column($new_customers, 'CustomerName') : [];
            $new_customers = get_goods_str($new_customers);
            $new_customers_sql = $new_customers ? " and CustomerName not in ($new_customers) " : '';

            $all_customers = $this->db_easy->Query("select * from customer where Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 $new_customers_sql;");
            //云仓归类
            $all_customer_arr = [];
            if ($all_customers) {
                $yuncangs = array_unique(array_column($all_customers, 'CustomItem15'));
                // print_r($yuncangs);die;
                foreach ($yuncangs as $v_yuncang) {
                    $each_yuncang = [];
                    foreach ($all_customers as $v_customer) {
                        if ($v_yuncang == $v_customer['CustomItem15']) {
                            $each_yuncang[] = $v_customer;
                        }
                    }
                    $all_customer_arr[$v_yuncang] = $each_yuncang;
                }
            }
            // print_r($all_customer_arr);die;

            $customer_level = $this->puhuo_score_model::where([['config_str', '=', 'customer_level']])->column('*', 'key');
            $fill_rate_level = $this->puhuo_score_model::where([['config_str', '=', 'fill_rate']])->column('*', 'key_level');
            $dongxiao_rate_level = $this->puhuo_score_model::where([['config_str', '=', 'dongxiao_rate']])->column('*', 'key_level');
            // print_r($fill_rate_level);die;
            //剔除的货品
            $ti_goods = $this->puhuo_ti_goods_model::where([])->column('GoodsNo');
            $ti_goods = $this->get_goods_str($ti_goods);
            // print_r($ti_goods);die;

            $qiwen_config = [];
            if ($puhuo_config['if_hottocold'] == 1) {//hottocold
                $qiwen_config = SpLypPuhuoHottocoldModel::where([])->select();   
                $qiwen_config = $qiwen_config ? $qiwen_config->toArray() : [];
            } else {//coldtohot
                $qiwen_config = SpLypPuhuoColdtohotModel::where([])->select();   
                $qiwen_config = $qiwen_config ? $qiwen_config->toArray() : [];
            }
            $qiwen_config_arr = [];
            if ($qiwen_config) {
                foreach ($qiwen_config as $v_qiwen_config) {
                    $qiwen_config_arr[$v_qiwen_config['yuncang'].$v_qiwen_config['province'].$v_qiwen_config['wenqu']] = $v_qiwen_config;
                }
            }
            // print_r($qiwen_config_arr);die;

            //开始尝试自动铺货模式
            foreach ($data as $v_data) {
                // print_r($v_data);die;

                $GoodsNo = $v_data['GoodsNo'] ?: '';//货号
                $WarehouseName = $v_data['WarehouseName'] ?: '';//云仓
                $TimeCategoryName1 = $v_data['TimeCategoryName1'] ?: '';//一级时间分类
                $TimeCategoryName2 = $v_data['TimeCategoryName2'] ?: '';//二级时间分类(季节)
                $CategoryName1 = $v_data['CategoryName1'] ?: '';//一级分类
                $CategoryName2 = $v_data['CategoryName2'] ?: '';//二级分类
                $StyleCategoryName = $v_data['StyleCategoryName'] ?: '';//(风格)基本款
                $StyleCategoryName1 = $v_data['StyleCategoryName1'] ?: '';//一级风格
                $StyleCategoryName2 = $v_data['StyleCategoryName2'] ?: '';//二级风格（货品等级）

                $all_customers = $all_customer_arr[$WarehouseName] ?? [];
                // print_r($all_customers);die;

                //大小码-满足率-分母
                $season = $this->get_season_str($TimeCategoryName2);
                $season = $season ? $season.'季' : '';
                $daxiaoma_skcnum_info = $this->puhuo_daxiaoma_skcnum_model::where([['WarehouseName', '=', $WarehouseName], ['TimeCategoryName1', '=', $TimeCategoryName1], ['season', '=', $season], 
                ['CategoryName1', '=', $CategoryName1], ['CategoryName2', '=', $CategoryName2], ['StyleCategoryName', '=', $StyleCategoryName]])->find();

                //是否已存在 daxiaoma_customer_sort
                $if_exist_daxiaoma_customer_sort = $this->puhuo_daxiaoma_customer_sort_model::where([['WarehouseName', '=', $WarehouseName], ['TimeCategoryName1', '=', $TimeCategoryName1], ['season', '=', $season], 
                ['CategoryName1', '=', $CategoryName1], ['CategoryName2', '=', $CategoryName2], ['StyleCategoryName', '=', $StyleCategoryName]])->column('*', 'CustomerName');

                if ($all_customers) {
                    $add_all_arr = $daxiaoma_customer_sort =  [];
                    foreach ($all_customers as $v_customer) {

                        //test...
                        // $v_customer['State'] = '广东省';
                        // $v_customer['CustomerGrade'] = 'S';
                        // $v_customer['CustomItem36'] = '南二';
                        // $v_customer['CustomerName'] = '广州一店';
                        // $WarehouseName = '广州云仓';
                        // $TimeCategoryName1 = '2023';
                        // $TimeCategoryName2 = '盛夏';
                        // $CategoryName1 = '内搭';
                        // $CategoryName2 = '短T';
                        // $StyleCategoryName = '基本款';

                        $CustomerGradeScore = isset($customer_level[$v_customer['CustomerGrade']]) ? $customer_level[$v_customer['CustomerGrade']]['score'] : 0;

                        $qiwen_score = 0;
                        $qiwen_str = ($v_customer['CustomItem15'] ? trim($v_customer['CustomItem15']) : '').($v_customer['State'] ? trim($v_customer['State']) : '').($v_customer['CustomItem36'] ? trim($v_customer['CustomItem36']) : '');
                        // echo $qiwen_str;die;
                        if (isset($qiwen_config_arr[$qiwen_str])) {
                            $qiwen_score = $qiwen_config_arr[$qiwen_str]['qiwen_score'];
                        }

                        //满足率计算
                        //满足率-单店skc
                        $dandian_skc = $this->db_easy->Query($this->get_dandian_skc(['一级时间分类' => $TimeCategoryName1, '二级时间分类' => $TimeCategoryName2, '一级分类' => $CategoryName1, '二级分类' => $CategoryName2, '风格' => $StyleCategoryName, '店铺名称' => $v_customer['CustomerName']], $ti_goods));
                        //查询该店已经铺的货
                        // $already_puhuo_goods = $this->puhuo_cur_log_model::where([['CustomerId', '=', $v_customer['CustomerId']], ['total', '>', 0]])->distinct(true)->column('GoodsNo');
                        $already_puhuo_goods = $this->return_already_puhuo_goods(['一级时间分类' => $TimeCategoryName1, '二级时间分类' => $TimeCategoryName2, '一级分类' => $CategoryName1, '二级分类' => $CategoryName2, '风格' => $StyleCategoryName, '店铺名称' => $v_customer['CustomerName']], $ti_goods);
                        if ($already_puhuo_goods) {
                            $dandian_skc_goods_str = $dandian_skc[0]['goods_str'] ?? 0;
                            $dandian_skc_goods_str = $dandian_skc_goods_str ? explode(',', $dandian_skc_goods_str) : [];
                            $merge_dandian_skc = array_unique(array_merge(array_column($already_puhuo_goods, 'GoodsNo'), $dandian_skc_goods_str));
                            $dandian_skc = count($merge_dandian_skc);
                        } else {
                            $dandian_skc = $dandian_skc[0]['store_skc_num'] ?? 0;
                        }
                        // print_r($dandian_skc);die;
                        //满足率-(云仓可用+云仓下门店预计库存的总skc数）
                        $yuncangkeyong_skc = $this->get_yuncangkeyong_skc(['一级时间分类' => $TimeCategoryName1, '二级时间分类' => $TimeCategoryName2, '一级分类' => $CategoryName1, '二级分类' => $CategoryName2, '风格' => $StyleCategoryName, '云仓' => $WarehouseName, '店铺名称' => $v_customer['CustomerName']], $ti_goods);
                        // print_r($yuncangkeyong_skc);die;
                        $fill_rate = $yuncangkeyong_skc ? round($dandian_skc/$yuncangkeyong_skc, 2) : 0;
                        $fill_rate_score = $this->get_fill_rate_score($fill_rate, $fill_rate_level);
                        // echo $fill_rate_score;die;

                        //动销率
                        //动销率-2周销skc数
                        $store_sale_skc_num = $this->db_easy->Query($this->get_store_sale_skc_num(['一级时间分类' => $TimeCategoryName1, '二级时间分类' => $TimeCategoryName2, '一级分类' => $CategoryName1, '二级分类' => $CategoryName2, '风格' => $StyleCategoryName, '店铺名称' => $v_customer['CustomerName']], $ti_goods));
                        // print_r($store_sale_skc_num);die;
                        $store_sale_skc_num = $store_sale_skc_num[0]['skc_num'] ?? 0;
                        //动销率-店铺预计skc数
                        $store_yuji_skc_num = $this->db_easy->Query($this->get_store_yuji_skc_num(['一级时间分类' => $TimeCategoryName1, '二级时间分类' => $TimeCategoryName2, '一级分类' => $CategoryName1, '二级分类' => $CategoryName2, '风格' => $StyleCategoryName, '店铺名称' => $v_customer['CustomerName']], $ti_goods));
                        $store_yuji_skc_num = $store_yuji_skc_num[0]['store_yuji_skc_num'] ?? 0;
                        // print_r($store_yuji_skc_num);die;
                        $dongxiao_rate = $store_yuji_skc_num ? round($store_sale_skc_num/$store_yuji_skc_num, 2) : 0;
                        $dongxiao_rate_score = $this->get_dongxiao_rate_score($dongxiao_rate, $dongxiao_rate_level);
                        // echo $dongxiao_rate_score;die;

                        $add_data = [
                            'Yuncang' => $WarehouseName,
                            'State' => $v_customer['State'] ?: '',
                            'GoodsNo' => $GoodsNo,
                            'CustomerName' => $v_customer['CustomerName'] ?: '',
                            'CustomerId' => $v_customer['CustomerId'] ?: '',
                            'CustomerGrade' => $v_customer['CustomerGrade'] ?: '',
                            'CustomerGradeScore' => $CustomerGradeScore,
                            'fill_rate' => $fill_rate,
                            'fill_rate_score' => $fill_rate_score,
                            'dongxiao_rate' => $dongxiao_rate,
                            'dongxiao_rate_score' => $dongxiao_rate_score,
                            'qiwen_score' => $qiwen_score,
                            'total_score' => $CustomerGradeScore + $fill_rate_score + $dongxiao_rate_score + $qiwen_score,
                            'score_sort' => 0,
                            'Mathod' => $v_customer['Mathod'] ?: '',
                            'StoreArea' => $v_customer['StoreArea'] ?: 0,
                            'xiuxian_num' => $v_customer['CustomItem10'] ?: 0,
                        ];
                        $add_all_arr[] = $add_data;


                        //大小码逻辑

                        //大小码店类型（大/正/小）
                        $daxiaodian_info = $this->puhuo_daxiaoma_customer_model::where([['customer_name', '=', $v_customer['CustomerName']]])->field('big_small_store')->find();
                        $daxiaodian_info = $daxiaodian_info ? $daxiaodian_info->toArray() : [];
                        $store_type = ($daxiaodian_info&&$daxiaodian_info['big_small_store']) ? ($this->puhuo_daxiaoma_customer_sort_model::store_type[$daxiaodian_info['big_small_store']] ?? 0) : 0;
                        
                        //大小码 满足率 分母
                        $daxiaoma_stock_skcnum = $this->return_daxiaoma_stock_skcnum($daxiaoma_skcnum_info, $daxiaodian_info);
                        // print_r($daxiaoma_stock_skcnum);die;

                        //已有的 店铺库存skc数
                        if (isset($if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']])) {

                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_00_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_00_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_00_goods_str_arr']) : [];
                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_29_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_29_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_29_goods_str_arr']) : [];
                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_34_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_34_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_34_goods_str_arr']) : [];
                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_35_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_35_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_35_goods_str_arr']) : [];
                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_36_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_36_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_36_goods_str_arr']) : [];
                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_38_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_38_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_38_goods_str_arr']) : [];
                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_40_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_40_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_40_goods_str_arr']) : [];
                            $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_42_goods_str_arr'] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_42_goods_str_arr'] ? explode(',', $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']]['Stock_42_goods_str_arr']) : [];
                            $daxiaoma_customer_sort[] = $if_exist_daxiaoma_customer_sort[$v_customer['CustomerName']];

                        } else {

                            $dandian_skc = $this->db_easy->Query($this->get_dandian_skc(['一级时间分类' => $TimeCategoryName1, '二级时间分类' => $TimeCategoryName2, '一级分类' => $CategoryName1, '二级分类' => $CategoryName2, '风格' => $StyleCategoryName, '店铺名称' => $v_customer['CustomerName']], $ti_goods, 
                            ' group_concat(case when stock_00_goods_str!="" then stock_00_goods_str else null end) as stock_00_goods_str
                            , group_concat(case when stock_29_goods_str!="" then stock_29_goods_str else null end) as stock_29_goods_str
                            , group_concat(case when stock_34_goods_str!="" then stock_34_goods_str else null end) as stock_34_goods_str
                            , group_concat(case when stock_35_goods_str!="" then stock_35_goods_str else null end) as stock_35_goods_str
                            , group_concat(case when stock_36_goods_str!="" then stock_36_goods_str else null end) as stock_36_goods_str
                            , group_concat(case when stock_38_goods_str!="" then stock_38_goods_str else null end) as stock_38_goods_str
                            , group_concat(case when stock_40_goods_str!="" then stock_40_goods_str else null end) as stock_40_goods_str  '));

                            // $dandian_skc = $yuncang_skc[$v_customer['CustomerName']] ?? [];

                            $daxiaoma_customer_sort[] = $this->return_add_daxiaoma_customer_sort($dandian_skc, $daxiaoma_stock_skcnum, $v_data, $v_customer, $store_type);

                        }

                    }
                    //排序
                    $add_all_arr = sort_arr($add_all_arr, 'total_score', SORT_DESC);
                    foreach ($add_all_arr as $k_add_all_arr => &$v_add_all_arr) {
                        $v_add_all_arr['score_sort'] = ++$k_add_all_arr;
                    }
                    // print_r($daxiaoma_customer_sort);die;
                    
                    if ($add_all_arr) {
                        $chunk_list = array_chunk($add_all_arr, 500);
                        foreach($chunk_list as $key => $val) {
                            $insert = $this->db_easy->table('sp_lyp_puhuo_customer_sort')->strict(false)->insertAll($val);
                        }

                        //大小码店 排序入库
                        $daxiaoma_skcnum_score_sort = $this->return_daxiaoma_skcnum_score_sort($daxiaoma_customer_sort);

                        //------------------------start铺货逻辑------------------------


                        $add_puhuo_log = $daxiaoma_puhuo_log = [];
                        Db::startTrans();
                        try {

                            foreach ($add_all_arr as $v_customer) {


                                //查询对应的铺货标准
                                $where = [
                                    ['Yuncang', '=', $WarehouseName], 
                                    ['State', '=', $v_customer['State']], 
                                    ['StyleCategoryName', '=', $v_data['StyleCategoryName']], 
                                    ['StyleCategoryName1', '=', $v_data['StyleCategoryName1']], 
                                    ['CategoryName1', '=', $v_data['CategoryName1']], 
                                    ['CategoryName2', '=', $v_data['CategoryName2']], 
                                    ['CustomerGrade', '=', $v_customer['CustomerGrade']]
                                ];
                                $rule = $this->puhuo_rule_model::where($where)->find();
                                $rule = $rule ? $rule->toArray() : [];
                                // print_r($where);die;

                                //该店该款 库存数
                                // print_r([['省份', '=', $v_customer['State']], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', $v_data['CategoryName1']], ['二级分类', '=', $v_data['CategoryName2']], ['分类', '=', $v_data['CategoryName']], ['货号', '=', $GoodsNo]]);die;
                                $goods_yuji_stock = $this->sp_ww_chunxia_stock_model::where([['省份', '=', $v_customer['State']], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', $v_data['CategoryName1']], ['二级分类', '=', $v_data['CategoryName2']], ['分类', '=', $v_data['CategoryName']], ['货号', '=', $GoodsNo]])->field('预计库存')->find();
                                $goods_yuji_stock = $goods_yuji_stock ? $goods_yuji_stock['预计库存'] : 0;
                                // print_r($goods_yuji_stock);die;
                                //近14天有无上柜过
                                $current_14days = $this->puhuo_shangshiday_model::where([['CustomerId', '=', $v_customer['CustomerId']], ['GoodsNo', '=', $GoodsNo],  ['StockDate', '>=', date('Y-m-d H:i:s', strtotime("-{$puhuo_config['listing_days']} days"))]])->field('GoodsNo')->find();
                                // print_r($current_14days);die;

                                //满足条件的才铺货
                                $can_puhuo = $this->check_can_puhuo($rule, $v_data, $puhuo_config, $goods_yuji_stock, $current_14days);
                                // print_r($can_puhuo);die;
                                $uuid = uuid();
                                if ($can_puhuo['if_can_puhuo']) {

                                    $data = $can_puhuo['data'] ?: [];
                                    $cut_stock = 0;
                                    if ($data) {
                                        foreach ($data as $k_puhuo_data=>$v_puhuo_data) {
                                            if (isset($v_data[$k_puhuo_data])) {
                                                $v_data[$k_puhuo_data] = $v_data[$k_puhuo_data]-$v_puhuo_data;
                                                $cut_stock += $v_puhuo_data;
                                            }
                                        }
                                        $v_data['Stock_Quantity_puhuo'] = $v_data['Stock_Quantity_puhuo']-$cut_stock;
                                        //sp_lyp_puhuo_wait_goods处理
                                        unset($v_data['create_time']);
                                        // print_r($v_data);die;
                                        $this->puhuo_wait_goods_model::where([['WarehouseName', '=', $WarehouseName], ['GoodsNo', '=', $GoodsNo]])->update($v_data);
                                    }

                                    //sp_lyp_puhuo_log、sp_lyp_puhuo_cur_log处理
                                    $puhuo_log = [
                                        'uuid' => $uuid,
                                        'WarehouseName' => $WarehouseName,
                                        'GoodsNo' => $GoodsNo,
                                        'CustomerName' => $v_customer['CustomerName'],
                                        'CustomerId' => $v_customer['CustomerId'],
                                        'rule_id' => $rule['id'],
                                        'Stock_00_puhuo' => $data['Stock_00_puhuo'] ?? 0,
                                        'Stock_29_puhuo' => $data['Stock_29_puhuo'] ?? 0,
                                        'Stock_30_puhuo' => $data['Stock_30_puhuo'] ?? 0,
                                        'Stock_31_puhuo' => $data['Stock_31_puhuo'] ?? 0,
                                        'Stock_32_puhuo' => $data['Stock_32_puhuo'] ?? 0,
                                        'Stock_33_puhuo' => $data['Stock_33_puhuo'] ?? 0,
                                        'Stock_34_puhuo' => $data['Stock_34_puhuo'] ?? 0,
                                        'Stock_35_puhuo' => $data['Stock_35_puhuo'] ?? 0,
                                        'Stock_36_puhuo' => $data['Stock_36_puhuo'] ?? 0,
                                        'Stock_38_puhuo' => $data['Stock_38_puhuo'] ?? 0,
                                        'Stock_40_puhuo' => $data['Stock_40_puhuo'] ?? 0,
                                        'Stock_42_puhuo' => $data['Stock_42_puhuo'] ?? 0,
                                        'total' => $cut_stock,
                                    ];

                                    //大小码
                                    $puhuo_log_tmp = $puhuo_log;
                                    $puhuo_log_tmp['rule'] = $rule;
                                    $daxiaoma_puhuo_log[] = $puhuo_log_tmp;
                                    
                                } else {//不铺
                                    
                                    $puhuo_log = [
                                        'uuid' => $uuid,
                                        'WarehouseName' => $WarehouseName,
                                        'GoodsNo' => $GoodsNo,
                                        'CustomerName' => $v_customer['CustomerName'],
                                        'CustomerId' => $v_customer['CustomerId'],
                                        'rule_id' => 0,
                                        'Stock_00_puhuo' => 0,
                                        'Stock_29_puhuo' => 0,
                                        'Stock_30_puhuo' => 0,
                                        'Stock_31_puhuo' => 0,
                                        'Stock_32_puhuo' => 0,
                                        'Stock_33_puhuo' => 0,
                                        'Stock_34_puhuo' => 0,
                                        'Stock_35_puhuo' => 0,
                                        'Stock_36_puhuo' => 0,
                                        'Stock_38_puhuo' => 0,
                                        'Stock_40_puhuo' => 0,
                                        'Stock_42_puhuo' => 0,
                                        'total' => 0,
                                    ];
                                    
                                }

                                $add_puhuo_log[] = $puhuo_log;
                                // print_r($add_puhuo_log);die;
                                $this->puhuo_customer_sort_model::where([['GoodsNo', '=', $GoodsNo], ['CustomerName', '=', $v_customer['CustomerName']]])->update(['cur_log_uuid' => $uuid]);

                            }
                            // print_r([$add_puhuo_log, $v_data]);die;

                            ######################大小码铺货逻辑start(只针对 主码 可铺的店进行大小码 铺货)############################
                            
                            $add_puhuo_log = $this->check_daxiaoma($daxiaoma_puhuo_log, $daxiaoma_skcnum_score_sort, $add_puhuo_log, $v_data, $puhuo_config);
                            
                            ######################大小码铺货逻辑end################################################################



                            //铺货日志批量入库
                            $chunk_list = $add_puhuo_log ? array_chunk($add_puhuo_log, 500) : [];
                            if ($chunk_list) {
                                foreach($chunk_list as $key => $val) {
                                    $this->db_easy->table('sp_lyp_puhuo_cur_log')->strict(false)->insertAll($val);
                                    $this->db_easy->table('sp_lyp_puhuo_log')->strict(false)->insertAll($val);
                                }
                            }

                            Db::commit();
                        } catch (\Exception $e) {
                            Db::rollback();
                        }


                        //-------------------------end铺货逻辑-------------------------------


                    }

                }

            }




            //最终铺货数据清洗
            $this->generate_end_data();



        }
        echo 'okk';die;
        
    }

    protected function return_already_puhuo_goods($where, $ti_goods='') {

        $season = $this->get_season_str($where['二级时间分类']);
        $not_in_str = $ti_goods ? " and lpwg.GoodsNo not in ({$ti_goods}) " : "";
        $sql = "select lpwg.GoodsNo from sp_lyp_puhuo_cur_log lycl 
        left join sp_lyp_puhuo_wait_goods lpwg on lycl.GoodsNo=lpwg.GoodsNo 
        where lycl.CustomerName='{$where['店铺名称']}' and lpwg.CategoryName1='{$where['一级分类']}' and lpwg.CategoryName2='{$where['二级分类']}' and lpwg.TimeCategoryName1='{$where['一级时间分类']}' and lpwg.TimeCategoryName2 like '%{$season}%' and lpwg.StyleCategoryName='{$where['风格']}' and lycl.total>0  $not_in_str group by lpwg.GoodsNo;";
        // echo $sql;die;
        return $this->db_easy->Query($sql);

    }

    protected function return_puhuo_log_param($puhuo_log) {

        $params = [
            'Stock_00_puhuo' => 0,
            'Stock_29_puhuo' => 0,
            'Stock_30_puhuo' => 0,
            'Stock_31_puhuo' => 0,
            'Stock_32_puhuo' => 0,
            'Stock_33_puhuo' => 0,
            'Stock_34_puhuo' => 0,
            'Stock_35_puhuo' => 0,
            'Stock_36_puhuo' => 0,
            'Stock_38_puhuo' => 0,
            'Stock_40_puhuo' => 0,
            'Stock_42_puhuo' => 0,
        ];
        foreach ($params as $k_param => $v_param) {
            !isset($puhuo_log[$k_param]) ? ($puhuo_log[$k_param]=$v_param) : '';
        }
        return $puhuo_log;

    }

    protected function get_wait_goods_data($list_rows) {

        // $res = SpLypPuhuoWaitGoodsModel::where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
        // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
            // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '针织衫')->where('GoodsNo', 'B52109039')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
            // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '针织衫')->where('GoodsNo', 'B82109001')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
            // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '针织衫')->where('GoodsNo', 'B62109212')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
            // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '针织衫')->where('GoodsNo', 'B52109237')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
        //     $res = $this->puhuo_wait_goods_model::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '针织衫')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
        //     // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '休闲长衬')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
        // // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '休闲长衬')->where('GoodsNo', 'B52106008')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
        // // $res = SpLypPuhuoWaitGoodsModel::where('WarehouseName', '贵阳云仓')->where('CategoryName1', '内搭')->where('CategoryName2', '针织衫')->where('GoodsNo', 'B52109039')->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
        //     'list_rows'=> $list_rows,//每页条数
        //     'page' => $this->page,//当前页
        // ]);
        // $res = $res ? $res->toArray() : [];
        // $res = $res ? $res['data'] : [];
        // return $res;

        //从指定铺货配置表里取数
        $res_data = [];
        $yuncangs = $this->puhuo_zhiding_goods_model::where([])->distinct(true)->column('Yuncang');
        if ($yuncangs) {
            foreach ($yuncangs as $v_yuncang) {
                $yuncang_goods = $this->puhuo_zhiding_goods_model::where([['Yuncang', '=', $v_yuncang]])->distinct(true)->column('GoodsNo');
                if ($yuncang_goods) {
                    $res = $this->puhuo_wait_goods_model::where('WarehouseName', $v_yuncang)->where([['GoodsNo', 'in', $yuncang_goods]])->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
                        'list_rows'=> $list_rows,//每页条数
                        'page' => $this->page,//当前页
                    ]);
                    $res = $res ? $res->toArray() : [];
                    $res = $res ? $res['data'] : [];
                    if ($res) {
                        foreach ($res as $v_res) {
                            $res_data[] = $v_res;
                        }
                    }
                }
            }
        }
        return $res_data;

    }

    ////满足率-单店skc数
    protected function get_dandian_skc($data=[], $not_in_goods = '', $field=' sum(skc_num) as store_skc_num, group_concat(goods_str) as goods_str ') {

        $season = $this->get_season_str($data['二级时间分类']);
        $season = $season ? $season.'季' : '';
        // $sql = "select count(货号) as store_skc_num from sp_sk where 年份='{$data['一级时间分类']}' and 季节 like '%{$season}%' and 一级分类='{$data['一级分类']}' and 二级分类='{$data['二级分类']}' 
        // and 风格='{$data['风格']}' and 店铺名称='{$data['店铺名称']}' and 货号 not in ($not_in_goods);";
        $sql = "select $field from sp_lyp_puhuo_spsk_stock where store_name='{$data['店铺名称']}' and category1='{$data['一级分类']}' and category2='{$data['二级分类']}' and year='{$data['一级时间分类']}' and season='{$season}' and style='{$data['风格']}';";
        // echo $sql;die;
        return $sql;

    }

    //满足率-（云仓可用+云仓下门店预计库存的总skc数）
    protected function get_yuncangkeyong_skc($data=[], $not_in_goods = '') {

        $season = $this->get_season_str($data['二级时间分类']);
        $season1 = $season ? $season.'季' : '';

        $sql1 = "select goods_str from sp_lyp_puhuo_spsk_stock where yuncang='{$data['云仓']}' and category1='{$data['一级分类']}' and category2='{$data['二级分类']}' and year='{$data['一级时间分类']}' and season='{$season1}' and style='{$data['风格']}'";
        $goods_str = $this->db_easy->Query($sql1);
        $goods_str = $goods_str ? implode(',', array_column($goods_str, 'goods_str')) : '';
        $goods_str = $goods_str ? explode(',', $goods_str) : [];

        // $sql = "select count(A.货号) as c from 
        // (
        // select 货号 from sp_sk where 年份='{$data['一级时间分类']}' and 季节 like '%{$season}%' and 一级分类='{$data['一级分类']}' and 二级分类='{$data['二级分类']}' 
        // and 风格='{$data['风格']}' and 云仓='{$data['云仓']}' and 预计库存数量>0  and 货号 not in ($not_in_goods) 
        // union  
        // select GoodsNo as 货号 from sp_lyp_puhuo_yuncangkeyong where TimeCategoryName1='{$data['一级时间分类']}' and TimeCategoryName2 like '%{$season}%' and CategoryName1='{$data['一级分类']}' and CategoryName2='{$data['二级分类']}' and StyleCategoryName='{$data['风格']}' and WarehouseName='{$data['云仓']}' and GoodsNo not in ($not_in_goods)
        // ) A;
        // ";

        $sql2 = "select GoodsNo from sp_lyp_puhuo_yuncangkeyong where WarehouseName='{$data['云仓']}' and TimeCategoryName1='{$data['一级时间分类']}' and TimeCategoryName2 like '%{$season}%' and CategoryName1='{$data['一级分类']}' and CategoryName2='{$data['二级分类']}' and StyleCategoryName='{$data['风格']}' and GoodsNo not in ($not_in_goods)";
        $yuncang_goods = $this->db_easy->Query($sql2);
        $yuncang_goods = $yuncang_goods ? array_column($yuncang_goods, 'GoodsNo') : [];
        //合并
        $all_goods_merge = array_merge($goods_str, $yuncang_goods);
        $all_goods_merge = $all_goods_merge ? count(array_unique($all_goods_merge)) : 0;

        // print_r(count($all_goods_merge));die;
        return $all_goods_merge;

    }

    //动销率-2周销skc数
    protected function get_store_sale_skc_num($data=[], $not_in_goods = '') {

        $season = $this->get_season_str($data['二级时间分类']);
        $season = $season ? $season.'季' : '';
        // $sql = "select count(distinct GoodsNo) as store_sale_skc_num from sp_lyp_puhuo_liangzhou where CustomerName='{$data['店铺名称']}' and CategoryName1='{$data['一级分类']}' and CategoryName2='{$data['二级分类']}' and TimeCategoryName1='{$data['一级时间分类']}' and Season='{$season}' and StyleCategoryName='{$data['风格']}' and amount>0 and GoodsNo not in ($not_in_goods);";
        $sql = "select skc_num from sp_lyp_puhuo_liangzhou_skc where CustomerName='{$data['店铺名称']}' and CategoryName1='{$data['一级分类']}' and CategoryName2='{$data['二级分类']}' and TimeCategoryName1='{$data['一级时间分类']}' and Season='{$season}' and StyleCategoryName='{$data['风格']}';";
        return $sql;

    }

    //动销率-店铺预计skc数
    protected function get_store_yuji_skc_num($data=[], $not_in_goods = '') {

        $season = $this->get_season_str($data['二级时间分类']);
        $season = $season ? $season.'季' : '';
        // $sql = "select count(货号) as store_yuji_skc_num from sp_sk where 年份='{$data['一级时间分类']}' and 季节 like '%{$season}%' and 一级分类='{$data['一级分类']}' and 二级分类='{$data['二级分类']}' and 风格='{$data['风格']}' and 店铺名称='{$data['店铺名称']}' and 预计库存数量>0 and 货号 not in ($not_in_goods);";
        $sql = "select sum(skc_num) as store_yuji_skc_num from sp_lyp_puhuo_spsk_stock where store_name='{$data['店铺名称']}' and category1='{$data['一级分类']}' and category2='{$data['二级分类']}' and year='{$data['一级时间分类']}' and season='{$season}' and style='{$data['风格']}';";
        // echo $sql;die;
        return $sql;

    }

    protected function get_goods_str($ti_goods=[]) {
        $ti_goods_str = '';
        if ($ti_goods) {
            foreach ($ti_goods as $v_goods) {
                $ti_goods_str .= "'".$v_goods."',";
            }
            $ti_goods_str = substr($ti_goods_str, 0, -1);
        }
        return $ti_goods_str;
    }

    protected function get_season_str($season) {
        $season_str = '';
        if ($season) {
            if (strstr($season, '春')) {
                $season_str = '春';
            } elseif (strstr($season, '夏')) {
                $season_str = '夏';
            } elseif (strstr($season, '秋')) {
                $season_str = '秋';
            } elseif (strstr($season, '冬')) {
                $season_str = '冬';
            }
        }
        return $season_str;
    }

    protected function get_fill_rate_score($fill_rate, $fill_rate_level) {

        $fill_rate_score = 0;
        if ($fill_rate_level) {
            if ($fill_rate < $fill_rate_level[3]['key']) {
                $fill_rate_score = $fill_rate_level[4]['score'];
            } elseif (($fill_rate >= $fill_rate_level[3]['key']) && ($fill_rate < $fill_rate_level[2]['key'])) {
                $fill_rate_score = $fill_rate_level[3]['score'];
            } elseif (($fill_rate >= $fill_rate_level[2]['key']) && ($fill_rate < $fill_rate_level[1]['key'])) {
                $fill_rate_score = $fill_rate_level[2]['score'];
            } elseif ($fill_rate >= $fill_rate_level[1]['key']) {
                $fill_rate_score = $fill_rate_level[1]['score'];
            }
        }
        return $fill_rate_score;

    }

    protected function get_dongxiao_rate_score($dongxiao_rate, $dongxiao_rate_level) {

        $dongxiao_rate_score = 0;
        if ($dongxiao_rate_level) {
            if ($dongxiao_rate < $dongxiao_rate_level[3]['key']) {
                $dongxiao_rate_score = $dongxiao_rate_level[4]['score'];
            } elseif (($dongxiao_rate >= $dongxiao_rate_level[3]['key']) && ($dongxiao_rate < $dongxiao_rate_level[2]['key'])) {
                $dongxiao_rate_score = $dongxiao_rate_level[3]['score'];
            } elseif (($dongxiao_rate >= $dongxiao_rate_level[2]['key']) && ($dongxiao_rate < $dongxiao_rate_level[1]['key'])) {
                $dongxiao_rate_score = $dongxiao_rate_level[2]['score'];
            } elseif ($dongxiao_rate >= $dongxiao_rate_level[1]['key']) {
                $dongxiao_rate_score = $dongxiao_rate_level[1]['score'];
            }
        }
        return $dongxiao_rate_score;

    }

    protected function check_can_puhuo($rule, $v_data, $puhuo_config, $goods_yuji_stock, $current_14days) {
        if (!$rule || !$v_data || ($goods_yuji_stock > 0) || ($goods_yuji_stock <= 0 && $current_14days)) return ['if_can_puhuo'=>false, 'data'=>[]];

        $can = true;
        $key_arr = [];
        foreach ($rule as $k_rule=>$v_rule) {
            if (strstr($k_rule, 'Stock') && $v_rule > 0) $key_arr[] = $k_rule;
        }
        // print_r($key_arr);die;
        $pu_arr = [];
        if ($key_arr) {
            foreach ($key_arr as $v_key) {
                //如果可铺的数 比 铺货标准的数 小 则不允许铺货
                if (isset($v_data[$v_key.'_puhuo']) && $v_data[$v_key.'_puhuo']>0) {
                    $pu_arr[$v_key.'_puhuo'] = ($v_data[$v_key.'_puhuo'] >= $rule[$v_key]) ? $rule[$v_key] : $v_data[$v_key.'_puhuo'];
                }
            }
        }
        // print_r($pu_arr);die;
        if (!$pu_arr) {
            $can = false;
        } else {

            // print_r($pu_arr);die;
            if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {
                $lianma_num = $puhuo_config['store_puhuo_lianma_nd'];
            } else {
                $lianma_num = $puhuo_config['store_puhuo_lianma_xz'];
            }

            if (count($pu_arr) < $lianma_num) {//小于中间码连码标准，肯定不能铺货
                $can = false;
            } else {

                $pu_arr_keys = [];
                foreach ($pu_arr as $k_pu=>$v_pu) {
                    $pu_arr_keys[] = str_replace(['Stock_', '_puhuo'], ['', ''], $k_pu);
                }

                //将 38，40，42 暂时转化为 正常的连续数字: 37,38,39
                $pu_arr_keys = str_replace(['00', '38', '40', '42'], ['28', '37', '38', '39'], $pu_arr_keys);
                sort($pu_arr_keys);
                // print_r($pu_arr_keys);die;

                $pu_arr_keys = getSeriesNum($pu_arr_keys);
                if ($pu_arr_keys) {
                    foreach ($pu_arr_keys as $k_keys=>$v_keys) {
                        if (count($v_keys) < $lianma_num) unset($pu_arr_keys[$k_keys]);
                    }
                }
                // print_r($pu_arr_keys);die;

                //最终得出 将要铺的 各个尺码 数
                if (!$pu_arr_keys) {
                    $can = false;
                } else {

                    $new_pu_arr = $new_pu_arr_keys = [];
                    array_walk_recursive($pu_arr_keys, function($value) use(&$new_pu_arr_keys){
                        $new_pu_arr_keys[] = $value;
                    });
                    
                    foreach ($new_pu_arr_keys as $vv) {
                        $sign_vv = '';
                        switch ($vv) {
                            case '28': $sign_vv='00';break;
                            case '37': $sign_vv='38';break;
                            case '38': $sign_vv='40';break;
                            case '39': $sign_vv='42';break;
                            default: $sign_vv=$vv;break;
                        }
                        $new_pu_arr['Stock_'.$sign_vv.'_puhuo'] = $pu_arr['Stock_'.$sign_vv.'_puhuo'] ?? 0;
                    }
                    $pu_arr = $new_pu_arr;

                    //最后判断主码连码情况，进一步确认是否可铺
                    $end_pu_arr = [];
                    if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {
                        $lianma_num = $puhuo_config['store_puhuo_lianma_nd'];
                        //主码
                        $end_pu_arr['30'] = $pu_arr['Stock_30_puhuo'] ?? 0;
                        $end_pu_arr['31'] = $pu_arr['Stock_31_puhuo'] ?? 0;
                        $end_pu_arr['32'] = $pu_arr['Stock_32_puhuo'] ?? 0;
                        $end_pu_arr['33'] = $pu_arr['Stock_33_puhuo'] ?? 0;

                        $pu_arr = $this->deal_end_pu_arr($end_pu_arr, $lianma_num);

                    } else {
                        $lianma_num = $puhuo_config['store_puhuo_lianma_xz'];

                        //主码
                        $end_pu_arr['29'] = $pu_arr['Stock_29_puhuo'] ?? 0;
                        $end_pu_arr['30'] = $pu_arr['Stock_30_puhuo'] ?? 0;
                        $end_pu_arr['31'] = $pu_arr['Stock_31_puhuo'] ?? 0;
                        $end_pu_arr['32'] = $pu_arr['Stock_32_puhuo'] ?? 0;
                        $end_pu_arr['33'] = $pu_arr['Stock_33_puhuo'] ?? 0;
                        $end_pu_arr['34'] = $pu_arr['Stock_34_puhuo'] ?? 0;

                        $pu_arr = $this->deal_end_pu_arr($end_pu_arr, $lianma_num);

                    }

                    if (!$pu_arr) {
                        $can = false;
                    }
                    // print_r($new_pu_arr);die;

                }

            }

        }
        return ['if_can_puhuo'=>$can, 'data'=>$pu_arr];

    }

    protected function deal_end_pu_arr($end_pu_arr, $lianma_num) {

        $main_keys = $new_pu_arr_keys = $end_data = [];
        foreach ($end_pu_arr as $k_pu_arr=>$v_pu_arr) {
            if ($v_pu_arr > 0) $main_keys[] = $k_pu_arr;
        }
        $res = getSeriesNum($main_keys);
        if ($res) {
            foreach ($res as $k_res=>$v_res) {
                if (count($v_res) < $lianma_num) unset($res[$k_res]);
            }
            array_walk_recursive($res, function($value) use(&$new_pu_arr_keys){
                $new_pu_arr_keys[] = $value;
            });
        }
        if ($new_pu_arr_keys) {
            foreach ($new_pu_arr_keys as $v_new_pu) {
                if (isset($end_pu_arr[$v_new_pu])) $end_data['Stock_'.$v_new_pu.'_puhuo'] = $end_pu_arr[$v_new_pu];
            }
        }
        // print_r($end_data);die;
        return $end_data;

    }

    //返回大小码店 各个尺码分母值
    protected function return_daxiaoma_stock_skcnum($daxiaoma_skcnum_info, $daxiaodian_info) {

        $Stock_00_skcnum = $Stock_29_skcnum = $Stock_34_skcnum = $Stock_35_skcnum = $Stock_36_skcnum = $Stock_38_skcnum = $Stock_40_skcnum = $Stock_42_skcnum = 0;
        if ($daxiaodian_info) {

            switch ($daxiaodian_info['big_small_store']) {
                case '大码店': 
                    $Stock_00_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_00_skcnum_big'] : 0;
                    $Stock_29_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_29_skcnum_big'] : 0;
                    $Stock_34_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_34_skcnum_big'] : 0;
                    $Stock_35_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_35_skcnum_big'] : 0;
                    $Stock_36_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_36_skcnum_big'] : 0;
                    $Stock_38_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_38_skcnum_big'] : 0;
                    $Stock_40_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_40_skcnum_big'] : 0;
                    $Stock_42_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_42_skcnum_big'] : 0;
                    break;
                case '正常店': 
                    $Stock_00_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_00_skcnum_normal'] : 0;
                    $Stock_29_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_29_skcnum_normal'] : 0;
                    $Stock_34_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_34_skcnum_normal'] : 0;
                    $Stock_35_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_35_skcnum_normal'] : 0;
                    $Stock_36_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_36_skcnum_normal'] : 0;
                    $Stock_38_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_38_skcnum_normal'] : 0;
                    $Stock_40_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_40_skcnum_normal'] : 0;
                    $Stock_42_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_42_skcnum_normal'] : 0;
                    break;
                case '小码店': 
                    $Stock_00_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_00_skcnum_small'] : 0;
                    $Stock_29_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_29_skcnum_small'] : 0;
                    $Stock_34_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_34_skcnum_small'] : 0;
                    $Stock_35_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_35_skcnum_small'] : 0;
                    $Stock_36_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_36_skcnum_small'] : 0;
                    $Stock_38_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_38_skcnum_small'] : 0;
                    $Stock_40_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_40_skcnum_small'] : 0;
                    $Stock_42_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_42_skcnum_small'] : 0;
                    break;    
            }

        }

        return ['Stock_00_skcnum'=>$Stock_00_skcnum, 'Stock_29_skcnum'=>$Stock_29_skcnum, 'Stock_34_skcnum'=>$Stock_34_skcnum, 'Stock_35_skcnum'=>$Stock_35_skcnum, 
        'Stock_36_skcnum'=>$Stock_36_skcnum, 'Stock_38_skcnum'=>$Stock_38_skcnum, 'Stock_40_skcnum'=>$Stock_40_skcnum, 'Stock_42_skcnum'=>$Stock_42_skcnum
        ];

    }

    protected function return_add_daxiaoma_customer_sort($dandian_skc, $daxiaoma_stock_skcnum, $v_data, $v_customer, $store_type) {

        $season = $this->get_season_str($v_data['TimeCategoryName2']);
        $season = $season ? $season.'季' : '';

        $stock_00_goods_str = $dandian_skc[0]['stock_00_goods_str'] ?? 0;
        $stock_00_goods_str = $stock_00_goods_str ? explode(',', $stock_00_goods_str) : [];
        $stock_00_goods_str_arr = $stock_00_goods_str;
        $stock_00_goods_str = $stock_00_goods_str ? count(array_unique($stock_00_goods_str)) : 0;

        $stock_29_goods_str = $dandian_skc[0]['stock_29_goods_str'] ?? 0;
        $stock_29_goods_str = $stock_29_goods_str ? explode(',', $stock_29_goods_str) : [];
        $stock_29_goods_str_arr = $stock_29_goods_str;
        $stock_29_goods_str = $stock_29_goods_str ? count(array_unique($stock_29_goods_str)) : 0;

        $stock_34_goods_str = $dandian_skc[0]['stock_34_goods_str'] ?? 0;
        $stock_34_goods_str = $stock_34_goods_str ? explode(',', $stock_34_goods_str) : [];
        $stock_34_goods_str_arr = $stock_34_goods_str;
        $stock_34_goods_str = $stock_34_goods_str ? count(array_unique($stock_34_goods_str)) : 0;

        $stock_35_goods_str = $dandian_skc[0]['stock_35_goods_str'] ?? 0;
        $stock_35_goods_str = $stock_35_goods_str ? explode(',', $stock_35_goods_str) : [];
        $stock_35_goods_str_arr = $stock_35_goods_str;
        $stock_35_goods_str = $stock_35_goods_str ? count(array_unique($stock_35_goods_str)) : 0;

        $stock_36_goods_str = $dandian_skc[0]['stock_36_goods_str'] ?? 0;
        $stock_36_goods_str = $stock_36_goods_str ? explode(',', $stock_36_goods_str) : [];
        $stock_36_goods_str_arr = $stock_36_goods_str;
        $stock_36_goods_str = $stock_36_goods_str ? count(array_unique($stock_36_goods_str)) : 0;

        $stock_38_goods_str = $dandian_skc[0]['stock_38_goods_str'] ?? 0;
        $stock_38_goods_str = $stock_38_goods_str ? explode(',', $stock_38_goods_str) : [];
        $stock_38_goods_str_arr = $stock_38_goods_str;
        $stock_38_goods_str = $stock_38_goods_str ? count(array_unique($stock_38_goods_str)) : 0;

        $stock_40_goods_str = $dandian_skc[0]['stock_40_goods_str'] ?? 0;
        $stock_40_goods_str = $stock_40_goods_str ? explode(',', $stock_40_goods_str) : [];
        $stock_40_goods_str_arr = $stock_40_goods_str;
        $stock_40_goods_str = $stock_40_goods_str ? count(array_unique($stock_40_goods_str)) : 0;


        $add_daxiaoma_customer_sort = [];
        $add_daxiaoma_customer_sort['WarehouseName'] = $v_data['WarehouseName'];
        $add_daxiaoma_customer_sort['CustomerName'] = $v_customer['CustomerName'];
        $add_daxiaoma_customer_sort['TimeCategoryName1'] = $v_data['TimeCategoryName1'];
        $add_daxiaoma_customer_sort['season'] = $season;
        $add_daxiaoma_customer_sort['CategoryName1'] = $v_data['CategoryName1'];
        $add_daxiaoma_customer_sort['CategoryName2'] = $v_data['CategoryName2'];
        $add_daxiaoma_customer_sort['StyleCategoryName'] = $v_data['StyleCategoryName'];
        $add_daxiaoma_customer_sort['store_type'] = $store_type;

        $add_daxiaoma_customer_sort['Stock_00_skcnum'] = $daxiaoma_stock_skcnum['Stock_00_skcnum'];
        $add_daxiaoma_customer_sort['Stock_00_skcnum_cur'] = $stock_00_goods_str;
        $add_daxiaoma_customer_sort['Stock_00_skcnum_score'] = $add_daxiaoma_customer_sort['Stock_00_skcnum'] ? round($add_daxiaoma_customer_sort['Stock_00_skcnum_cur']/$add_daxiaoma_customer_sort['Stock_00_skcnum'], 2) : 0;
        $add_daxiaoma_customer_sort['Stock_00_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_00_goods_str_arr'] = $stock_00_goods_str_arr;

        $add_daxiaoma_customer_sort['Stock_29_skcnum'] = $daxiaoma_stock_skcnum['Stock_29_skcnum'];
        $add_daxiaoma_customer_sort['Stock_29_skcnum_cur'] = $stock_29_goods_str;
        $add_daxiaoma_customer_sort['Stock_29_skcnum_score'] = $add_daxiaoma_customer_sort['Stock_29_skcnum'] ? round($add_daxiaoma_customer_sort['Stock_29_skcnum_cur']/$add_daxiaoma_customer_sort['Stock_29_skcnum'], 2) : 0;
        $add_daxiaoma_customer_sort['Stock_29_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_29_goods_str_arr'] = $stock_29_goods_str_arr;

        $add_daxiaoma_customer_sort['Stock_34_skcnum'] = $daxiaoma_stock_skcnum['Stock_34_skcnum'];
        $add_daxiaoma_customer_sort['Stock_34_skcnum_cur'] = $stock_34_goods_str;
        $add_daxiaoma_customer_sort['Stock_34_skcnum_score'] = $add_daxiaoma_customer_sort['Stock_34_skcnum'] ? round($add_daxiaoma_customer_sort['Stock_34_skcnum_cur']/$add_daxiaoma_customer_sort['Stock_34_skcnum'], 2) : 0;
        $add_daxiaoma_customer_sort['Stock_34_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_34_goods_str_arr'] = $stock_34_goods_str_arr;

        $add_daxiaoma_customer_sort['Stock_35_skcnum'] = $daxiaoma_stock_skcnum['Stock_35_skcnum'];
        $add_daxiaoma_customer_sort['Stock_35_skcnum_cur'] = $stock_35_goods_str;
        $add_daxiaoma_customer_sort['Stock_35_skcnum_score'] = $add_daxiaoma_customer_sort['Stock_35_skcnum'] ? round($add_daxiaoma_customer_sort['Stock_35_skcnum_cur']/$add_daxiaoma_customer_sort['Stock_35_skcnum'], 2) : 0;
        $add_daxiaoma_customer_sort['Stock_35_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_35_goods_str_arr'] = $stock_35_goods_str_arr;

        $add_daxiaoma_customer_sort['Stock_36_skcnum'] = $daxiaoma_stock_skcnum['Stock_36_skcnum'];
        $add_daxiaoma_customer_sort['Stock_36_skcnum_cur'] = $stock_36_goods_str;
        $add_daxiaoma_customer_sort['Stock_36_skcnum_score'] = $add_daxiaoma_customer_sort['Stock_36_skcnum'] ? round($add_daxiaoma_customer_sort['Stock_36_skcnum_cur']/$add_daxiaoma_customer_sort['Stock_36_skcnum'], 2) : 0;
        $add_daxiaoma_customer_sort['Stock_36_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_36_goods_str_arr'] = $stock_36_goods_str_arr;

        $add_daxiaoma_customer_sort['Stock_38_skcnum'] = $daxiaoma_stock_skcnum['Stock_38_skcnum'];
        $add_daxiaoma_customer_sort['Stock_38_skcnum_cur'] = $stock_38_goods_str;
        $add_daxiaoma_customer_sort['Stock_38_skcnum_score'] = $add_daxiaoma_customer_sort['Stock_38_skcnum'] ? round($add_daxiaoma_customer_sort['Stock_38_skcnum_cur']/$add_daxiaoma_customer_sort['Stock_38_skcnum'], 2) : 0;
        $add_daxiaoma_customer_sort['Stock_38_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_38_goods_str_arr'] = $stock_38_goods_str_arr;

        $add_daxiaoma_customer_sort['Stock_40_skcnum'] = $daxiaoma_stock_skcnum['Stock_40_skcnum'];
        $add_daxiaoma_customer_sort['Stock_40_skcnum_cur'] = $stock_40_goods_str;
        $add_daxiaoma_customer_sort['Stock_40_skcnum_score'] = $add_daxiaoma_customer_sort['Stock_40_skcnum'] ? round($add_daxiaoma_customer_sort['Stock_40_skcnum_cur']/$add_daxiaoma_customer_sort['Stock_40_skcnum'], 2) : 0;
        $add_daxiaoma_customer_sort['Stock_40_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_40_goods_str_arr'] = $stock_40_goods_str_arr;

        $add_daxiaoma_customer_sort['Stock_42_skcnum'] = 0;
        $add_daxiaoma_customer_sort['Stock_42_skcnum_cur'] = 0;
        $add_daxiaoma_customer_sort['Stock_42_skcnum_score'] = 0;
        $add_daxiaoma_customer_sort['Stock_42_skcnum_sort'] = 0;
        $add_daxiaoma_customer_sort['Stock_42_goods_str_arr'] = [];

        return $add_daxiaoma_customer_sort;

    }


    protected function return_daxiaoma_skcnum_score_sort($daxiaoma_customer_sort) {

        $Stock_00_skcnum_sort = sort_arr($daxiaoma_customer_sort, 'Stock_00_skcnum_score', SORT_ASC);
        foreach ($Stock_00_skcnum_sort as $k_Stock_00_skcnum_sort => &$v_Stock_00_skcnum_sort) {
            $v_Stock_00_skcnum_sort['Stock_00_skcnum_sort'] = ++$k_Stock_00_skcnum_sort;
        }
        $Stock_29_skcnum_sort = sort_arr($Stock_00_skcnum_sort, 'Stock_29_skcnum_score', SORT_ASC);
        foreach ($Stock_29_skcnum_sort as $k_Stock_29_skcnum_sort => &$v_Stock_29_skcnum_sort) {
            $v_Stock_29_skcnum_sort['Stock_29_skcnum_sort'] = ++$k_Stock_29_skcnum_sort;
        }
        $Stock_34_skcnum_sort = sort_arr($Stock_29_skcnum_sort, 'Stock_34_skcnum_score', SORT_ASC);
        foreach ($Stock_34_skcnum_sort as $k_Stock_34_skcnum_sort => &$v_Stock_34_skcnum_sort) {
            $v_Stock_34_skcnum_sort['Stock_34_skcnum_sort'] = ++$k_Stock_34_skcnum_sort;
        }
        $Stock_35_skcnum_sort = sort_arr($Stock_34_skcnum_sort, 'Stock_35_skcnum_score', SORT_ASC);
        foreach ($Stock_35_skcnum_sort as $k_Stock_35_skcnum_sort => &$v_Stock_35_skcnum_sort) {
            $v_Stock_35_skcnum_sort['Stock_35_skcnum_sort'] = ++$k_Stock_35_skcnum_sort;
        }
        $Stock_36_skcnum_sort = sort_arr($Stock_35_skcnum_sort, 'Stock_36_skcnum_score', SORT_ASC);
        foreach ($Stock_36_skcnum_sort as $k_Stock_36_skcnum_sort => &$v_Stock_36_skcnum_sort) {
            $v_Stock_36_skcnum_sort['Stock_36_skcnum_sort'] = ++$k_Stock_36_skcnum_sort;
        }
        $Stock_38_skcnum_sort = sort_arr($Stock_36_skcnum_sort, 'Stock_38_skcnum_score', SORT_ASC);
        foreach ($Stock_38_skcnum_sort as $k_Stock_38_skcnum_sort => &$v_Stock_38_skcnum_sort) {
            $v_Stock_38_skcnum_sort['Stock_38_skcnum_sort'] = ++$k_Stock_38_skcnum_sort;
        }
        $Stock_40_skcnum_sort = sort_arr($Stock_38_skcnum_sort, 'Stock_40_skcnum_score', SORT_ASC);
        foreach ($Stock_40_skcnum_sort as $k_Stock_40_skcnum_sort => &$v_Stock_40_skcnum_sort) {
            $v_Stock_40_skcnum_sort['Stock_40_skcnum_sort'] = ++$k_Stock_40_skcnum_sort;
        }
        return $Stock_40_skcnum_sort;

    }

    protected function check_daxiaoma($daxiaoma_puhuo_log, $daxiaoma_skcnum_score_sort, $add_puhuo_log, $v_data, $puhuo_config) {

        if ($daxiaoma_puhuo_log) {

            //指定款类型（剔除使用）
            $ti_type = $this->puhuo_ti_goods_model::where([])->distinct(true)->column('GoodsLevel');

            $add_puhuo_log_uuids = array_column($add_puhuo_log, 'uuid');
            $add_puhuo_log = array_combine($add_puhuo_log_uuids, $add_puhuo_log);

            //最终大小码连码标准
            $lianma_num = 0;
            if ($v_data['CategoryName1'] == '内搭') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_nd'];
            } elseif ($v_data['CategoryName1'] == '外套') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_wt'];
            } elseif ($v_data['CategoryName1'] == '鞋履') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_xl'];
            } elseif ($v_data['CategoryName2'] == '松紧短裤') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_sjdk'];
            } elseif ($v_data['CategoryName2'] == '松紧长裤') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_sjck'];
            } else {
                $lianma_num = $puhuo_config['end_puhuo_lianma_xz'];//其他下装
            }


            $can_puhuo_customer_names = array_column($daxiaoma_puhuo_log, 'CustomerName');
            $daxiaoma_puhuo_log = array_combine($can_puhuo_customer_names, $daxiaoma_puhuo_log);
            $new_daxiaoma_skcnum_score_sort = [];
            //取出当前可铺的店铺（已满足主码连码的）
            foreach ($daxiaoma_skcnum_score_sort as $v_daxiaoma_skcnum_score_sort) {

                if (in_array($v_daxiaoma_skcnum_score_sort['CustomerName'], $can_puhuo_customer_names)) {

                    $new_daxiaoma_skcnum_score_sort[] = $v_daxiaoma_skcnum_score_sort;

                }

            }

            $last_daxiaoma_puhuo_log = [];
            //铺28码
            if ($v_data['Stock_00'] > 0) {
                $new_daxiaoma_puhuo_log = [];
                $new_daxiaoma_puhuo_log_no = [];
                $Stock_00 = $v_data['Stock_00'];//原本总库存
                $each_sum = 0;//累计铺去的库存
                $Stock_00_sort_arr = sort_arr($new_daxiaoma_skcnum_score_sort, 'Stock_00_skcnum_sort', SORT_ASC);
                //对大小码可铺数组按排序顺序重新进行排序
                foreach ($Stock_00_sort_arr as $k_Stock_00_sort_arr=>$v_Stock_00_sort_arr) {
                    foreach ($daxiaoma_puhuo_log as $v_daxiaoma_puhuo_log) {
                        if ($v_Stock_00_sort_arr['CustomerName'] == $v_daxiaoma_puhuo_log['CustomerName']) {

                            if ($v_Stock_00_sort_arr['Stock_00_skcnum_cur'] >= $v_Stock_00_sort_arr['Stock_00_skcnum']) {//该店 当前尺码已满足无须再铺
                                $new_daxiaoma_puhuo_log_no[] = $v_daxiaoma_puhuo_log;
                            } else {
                                $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;
                            }

                        }
                    }
                }
                //开始逐个店铺货
                if ($new_daxiaoma_puhuo_log) {
                    foreach ($new_daxiaoma_puhuo_log as &$v_new_daxiaoma_puhuo_log) {
    
                        if ($each_sum >= $Stock_00 || $Stock_00==0) break;
                        if ($v_new_daxiaoma_puhuo_log['rule']['Stock_00'] > $Stock_00) {
    
                            $v_new_daxiaoma_puhuo_log['Stock_00_puhuo'] = $Stock_00;
                            $each_sum += $v_new_daxiaoma_puhuo_log['Stock_00_puhuo'];
                            $Stock_00 = 0;
    
                        } else {
    
                            $v_new_daxiaoma_puhuo_log['Stock_00_puhuo'] = $v_new_daxiaoma_puhuo_log['rule']['Stock_00'];
                            $each_sum += $v_new_daxiaoma_puhuo_log['Stock_00_puhuo'];
                            $Stock_00 -= $v_new_daxiaoma_puhuo_log['Stock_00_puhuo'];
    
                        }
    
                    }
                }
                //合适铺的 跟 不合适铺的 合并 
                $new_daxiaoma_puhuo_log = array_merge($new_daxiaoma_puhuo_log, $new_daxiaoma_puhuo_log_no);

                $last_daxiaoma_puhuo_log = $new_daxiaoma_puhuo_log;

            }



            if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {
                
                //铺29码
                if ($v_data['Stock_29'] > 0) {
                    $last_daxiaoma_puhuo_log = $this->return_each_daxiaoma_puhuo_log('29', $v_data['Stock_29'], $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log);
                }

                //铺34码
                if ($v_data['Stock_34'] > 0) {
                    $last_daxiaoma_puhuo_log = $this->return_each_daxiaoma_puhuo_log('34', $v_data['Stock_34'], $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log);
                }


            } else {

            }

            //铺35、36、38、40、42码
            $last_daxiaoma_puhuo_log = $this->return_each_daxiaoma_puhuo_log('35', $v_data['Stock_35'], $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log);
            $last_daxiaoma_puhuo_log = $this->return_each_daxiaoma_puhuo_log('36', $v_data['Stock_36'], $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log);
            $last_daxiaoma_puhuo_log = $this->return_each_daxiaoma_puhuo_log('38', $v_data['Stock_38'], $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log);
            $last_daxiaoma_puhuo_log = $this->return_each_daxiaoma_puhuo_log('40', $v_data['Stock_40'], $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log);
            $last_daxiaoma_puhuo_log = $this->return_each_daxiaoma_puhuo_log('42', $v_data['Stock_42'], $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log);

            //主码、大小码 联合 根据参数标准筛选出最终可铺的数据
            $last_daxiaoma_puhuo_log = $last_daxiaoma_puhuo_log ?: $daxiaoma_puhuo_log;
            $last_daxiaoma_puhuo_log = array_values($last_daxiaoma_puhuo_log);
            // print_r([$last_daxiaoma_puhuo_log,  count($daxiaoma_skcnum_score_sort)]);die;
            if ($last_daxiaoma_puhuo_log) {

                $init_puhuo_stock = [
                    'Stock_00_puhuo' => $v_data['Stock_00_puhuo'],
                    'Stock_29_puhuo' => $v_data['Stock_29_puhuo'],
                    'Stock_30_puhuo' => $v_data['Stock_30_puhuo'],
                    'Stock_31_puhuo' => $v_data['Stock_31_puhuo'],
                    'Stock_32_puhuo' => $v_data['Stock_32_puhuo'],
                    'Stock_33_puhuo' => $v_data['Stock_33_puhuo'],
                    'Stock_34_puhuo' => $v_data['Stock_34_puhuo'],
                    'Stock_35_puhuo' => $v_data['Stock_35_puhuo'],
                    'Stock_36_puhuo' => $v_data['Stock_36_puhuo'],
                    'Stock_38_puhuo' => $v_data['Stock_38_puhuo'],
                    'Stock_40_puhuo' => $v_data['Stock_40_puhuo'],
                    'Stock_42_puhuo' => $v_data['Stock_42_puhuo'],
                    'Stock_Quantity_puhuo' => $v_data['Stock_Quantity_puhuo'],
                ];

                //最终连码识别：
                foreach ($last_daxiaoma_puhuo_log as $k_last_daxiaoma_puhuo_log=>&$v_last_daxiaoma_puhuo_log) {

                    //test.....暂时测试测试
                    // if ($v_last_daxiaoma_puhuo_log['CustomerName'] != '忠县一店') continue;

                    $key_arr = [];    
                    foreach ($v_last_daxiaoma_puhuo_log as $kk_daxiaoma_puhuo_log=>$vv_daxiaoma_puhuo_log) {
                        if (strstr($kk_daxiaoma_puhuo_log, 'Stock') && $vv_daxiaoma_puhuo_log > 0) $key_arr[] = $this->return_which_stock_num($kk_daxiaoma_puhuo_log);
                    }


                    $pu_arr_keys = getSeriesNum($key_arr);
                    if ($pu_arr_keys) {
                        foreach ($pu_arr_keys as $k_keys=>$v_keys) {
                            if (count($v_keys) < $lianma_num) unset($pu_arr_keys[$k_keys]);
                        }
                    }
                    // print_r($pu_arr_keys);die;

                    if (!$pu_arr_keys) {//没有连码 ，不可铺

                        //不可铺，wait_goods（主码）各个尺码库存恢复
                        if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {
                
                            $init_puhuo_stock['Stock_30_puhuo'] = ($v_data['Stock_30_puhuo']>=0) ? ($init_puhuo_stock['Stock_30_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_30_puhuo']) : $v_data['Stock_30_puhuo'];
                            $init_puhuo_stock['Stock_31_puhuo'] = ($v_data['Stock_31_puhuo']>=0) ? ($init_puhuo_stock['Stock_31_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_31_puhuo']) : $v_data['Stock_31_puhuo'];
                            $init_puhuo_stock['Stock_32_puhuo'] = ($v_data['Stock_32_puhuo']>=0) ? ($init_puhuo_stock['Stock_32_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_32_puhuo']) : $v_data['Stock_32_puhuo'];
                            $init_puhuo_stock['Stock_33_puhuo'] = ($v_data['Stock_33_puhuo']>=0) ? ($init_puhuo_stock['Stock_33_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_33_puhuo']) : $v_data['Stock_33_puhuo'];
                            $init_puhuo_stock['Stock_Quantity_puhuo'] = ($v_data['Stock_Quantity_puhuo']>=0) ? ($init_puhuo_stock['Stock_Quantity_puhuo']+ $v_last_daxiaoma_puhuo_log['Stock_30_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_31_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_32_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_33_puhuo']) : $v_data['Stock_Quantity_puhuo'];

                        } else {//其他下装

                            $init_puhuo_stock['Stock_29_puhuo'] = ($v_data['Stock_29_puhuo']>=0) ? ($init_puhuo_stock['Stock_29_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_29_puhuo']) : $v_data['Stock_29_puhuo'];
                            $init_puhuo_stock['Stock_30_puhuo'] = ($v_data['Stock_30_puhuo']>=0) ? ($init_puhuo_stock['Stock_30_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_30_puhuo']) : $v_data['Stock_30_puhuo'];
                            $init_puhuo_stock['Stock_31_puhuo'] = ($v_data['Stock_31_puhuo']>=0) ? ($init_puhuo_stock['Stock_31_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_31_puhuo']) : $v_data['Stock_31_puhuo'];
                            $init_puhuo_stock['Stock_32_puhuo'] = ($v_data['Stock_32_puhuo']>=0) ? ($init_puhuo_stock['Stock_32_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_32_puhuo']) : $v_data['Stock_32_puhuo'];
                            $init_puhuo_stock['Stock_33_puhuo'] = ($v_data['Stock_33_puhuo']>=0) ? ($init_puhuo_stock['Stock_33_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_33_puhuo']) : $v_data['Stock_33_puhuo'];
                            $init_puhuo_stock['Stock_34_puhuo'] = ($v_data['Stock_34_puhuo']>=0) ? ($init_puhuo_stock['Stock_34_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_34_puhuo']) : $v_data['Stock_34_puhuo'];
                            $init_puhuo_stock['Stock_Quantity_puhuo'] = ($v_data['Stock_Quantity_puhuo']>=0) ? ($init_puhuo_stock['Stock_Quantity_puhuo']+ $v_last_daxiaoma_puhuo_log['Stock_29_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_30_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_31_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_32_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_33_puhuo']+$v_last_daxiaoma_puhuo_log['Stock_34_puhuo']) : $v_data['Stock_Quantity_puhuo'];

                        }

                        $v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_29_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_30_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_31_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_32_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_33_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_34_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['Stock_42_puhuo'] = 0;
                        $v_last_daxiaoma_puhuo_log['total'] = 0;

                        //将铺货日志的已铺主码记录 置0
                        if (isset($add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']])) {

                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_00_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_29_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_30_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_31_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_32_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_33_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_34_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_35_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_36_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_38_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_40_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_42_puhuo'] = 0;
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['total'] = 0;

                        }

                    } else {//有满足连码

                        //最终满足连码的情况， wait_goods 要把各个大小码的库存减去
                        if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {
                
                            $init_puhuo_stock['Stock_00_puhuo'] = ($v_data['Stock_00_puhuo']>=0) ? ($init_puhuo_stock['Stock_00_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_00_puhuo']) : $v_data['Stock_00_puhuo'];
                            $init_puhuo_stock['Stock_29_puhuo'] = ($v_data['Stock_29_puhuo']>=0) ? ($init_puhuo_stock['Stock_29_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_29_puhuo']) : $v_data['Stock_29_puhuo'];
                            $init_puhuo_stock['Stock_34_puhuo'] = ($v_data['Stock_34_puhuo']>=0) ? ($init_puhuo_stock['Stock_34_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_34_puhuo']) : $v_data['Stock_34_puhuo'];
                            $init_puhuo_stock['Stock_35_puhuo'] = ($v_data['Stock_35_puhuo']>=0) ? ($init_puhuo_stock['Stock_35_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_35_puhuo']) : $v_data['Stock_35_puhuo'];
                            $init_puhuo_stock['Stock_36_puhuo'] = ($v_data['Stock_36_puhuo']>=0) ? ($init_puhuo_stock['Stock_36_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_36_puhuo']) : $v_data['Stock_36_puhuo'];
                            $init_puhuo_stock['Stock_38_puhuo'] = ($v_data['Stock_38_puhuo']>=0) ? ($init_puhuo_stock['Stock_38_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_38_puhuo']) : $v_data['Stock_38_puhuo'];
                            $init_puhuo_stock['Stock_40_puhuo'] = ($v_data['Stock_40_puhuo']>=0) ? ($init_puhuo_stock['Stock_40_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_40_puhuo']) : $v_data['Stock_40_puhuo'];
                            $init_puhuo_stock['Stock_42_puhuo'] = ($v_data['Stock_42_puhuo']>=0) ? ($init_puhuo_stock['Stock_42_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_42_puhuo']) : $v_data['Stock_42_puhuo'];
                            $init_puhuo_stock['Stock_Quantity_puhuo'] = ($v_data['Stock_Quantity_puhuo']>=0) ? ($init_puhuo_stock['Stock_Quantity_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_29_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_34_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_42_puhuo']) : $v_data['Stock_Quantity_puhuo'];
            
                            //daxiaoma_customer_sort表 数据处理
                            foreach ($daxiaoma_skcnum_score_sort as &$vv_daxiaoma_skcnum_score_sort) {
                                if ($vv_daxiaoma_skcnum_score_sort['CustomerName'] == $v_last_daxiaoma_puhuo_log['CustomerName']) {

                                    $this->return_daxiaoma_customer_sort_nd($v_last_daxiaoma_puhuo_log, $v_data, $vv_daxiaoma_skcnum_score_sort, $ti_type);

                                }
                            }

                        } else {//其他下装

                            $init_puhuo_stock['Stock_00_puhuo'] = ($v_data['Stock_00_puhuo']>=0) ? ($init_puhuo_stock['Stock_00_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_00_puhuo']) : $v_data['Stock_00_puhuo'];
                            $init_puhuo_stock['Stock_35_puhuo'] = ($v_data['Stock_35_puhuo']>=0) ? ($init_puhuo_stock['Stock_35_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_35_puhuo']) : $v_data['Stock_35_puhuo'];
                            $init_puhuo_stock['Stock_36_puhuo'] = ($v_data['Stock_36_puhuo']>=0) ? ($init_puhuo_stock['Stock_36_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_36_puhuo']) : $v_data['Stock_36_puhuo'];
                            $init_puhuo_stock['Stock_38_puhuo'] = ($v_data['Stock_38_puhuo']>=0) ? ($init_puhuo_stock['Stock_38_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_38_puhuo']) : $v_data['Stock_38_puhuo'];
                            $init_puhuo_stock['Stock_40_puhuo'] = ($v_data['Stock_40_puhuo']>=0) ? ($init_puhuo_stock['Stock_40_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_40_puhuo']) : $v_data['Stock_40_puhuo'];
                            $init_puhuo_stock['Stock_42_puhuo'] = ($v_data['Stock_42_puhuo']>=0) ? ($init_puhuo_stock['Stock_42_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_42_puhuo']) : $v_data['Stock_42_puhuo'];
                            $init_puhuo_stock['Stock_Quantity_puhuo'] = ($v_data['Stock_Quantity_puhuo']>=0) ? ($init_puhuo_stock['Stock_Quantity_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] - $v_last_daxiaoma_puhuo_log['Stock_42_puhuo']) : $v_data['Stock_Quantity_puhuo'];

                            //daxiaoma_customer_sort表 数据处理
                            foreach ($daxiaoma_skcnum_score_sort as &$vv_daxiaoma_skcnum_score_sort) {
                                if ($vv_daxiaoma_skcnum_score_sort['CustomerName'] == $v_last_daxiaoma_puhuo_log['CustomerName']) {

                                    $this->return_daxiaoma_customer_sort_xz($v_last_daxiaoma_puhuo_log, $v_data, $vv_daxiaoma_skcnum_score_sort, $ti_type);

                                }
                            }

                        }

                        //将铺货日志的已铺大小码记录 填上对应值
                        if (isset($add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']])) {

                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_00_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_00_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_29_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_29_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_30_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_30_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_31_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_31_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_32_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_32_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_33_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_33_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_34_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_34_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_35_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_35_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_36_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_36_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_38_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_38_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_40_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_40_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['Stock_42_puhuo'] = $v_last_daxiaoma_puhuo_log['Stock_42_puhuo'];
                            $add_puhuo_log[$v_last_daxiaoma_puhuo_log['uuid']]['total'] = ($v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_29_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_30_puhuo'] + 
                            $v_last_daxiaoma_puhuo_log['Stock_31_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_32_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_33_puhuo'] + 
                            $v_last_daxiaoma_puhuo_log['Stock_34_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] + 
                            $v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_42_puhuo']);

                        }


                    }

                    unset($v_last_daxiaoma_puhuo_log['rule']);
                    $v_last_daxiaoma_puhuo_log['total'] = ($v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_29_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_30_puhuo'] + 
                    $v_last_daxiaoma_puhuo_log['Stock_31_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_32_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_33_puhuo'] + 
                    $v_last_daxiaoma_puhuo_log['Stock_34_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] + 
                    $v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_42_puhuo']) ;


                }

                //wait_goods 库存处理
                $this->puhuo_wait_goods_model::where([['WarehouseName', '=', $v_data['WarehouseName']], ['GoodsNo', '=', $v_data['GoodsNo']]])->update($init_puhuo_stock);

                //$daxiaoma_skcnum_score_sort 大小码铺货后 重新排序
                //大小码店 排序入库
                $daxiaoma_skcnum_score_sort = $this->return_daxiaoma_skcnum_score_sort($daxiaoma_skcnum_score_sort);

            }

            //入库 sp_lyp_puhuo_daxiaoma_customer_sort
            if ($daxiaoma_skcnum_score_sort) {
                //先去掉一些临时加的字段
                foreach ($daxiaoma_skcnum_score_sort as &$vvv_daxiaoma_skcnum_score_sort) {

                    $vvv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr']) : '';
                    $vvv_daxiaoma_skcnum_score_sort['Stock_29_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_29_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_29_goods_str_arr']) : '';
                    $vvv_daxiaoma_skcnum_score_sort['Stock_34_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_34_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_34_goods_str_arr']) : '';
                    $vvv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr']) : '';
                    $vvv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr']) : '';
                    $vvv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr']) : '';
                    $vvv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr']) : '';
                    $vvv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'] = $vvv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'] ? implode(',', $vvv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr']) : '';

                }
                // print_r($daxiaoma_skcnum_score_sort);die;

                //看看当前货号所属类别(细分到：一级分类/二级分类/风格)是否已存在
                $season = $this->get_season_str($v_data['TimeCategoryName2']);
                $season = $season ? $season.'季' : '';
                $if_exist = $this->puhuo_daxiaoma_customer_sort_model::where([
                    ['WarehouseName', '=', $v_data['WarehouseName']],
                    ['TimeCategoryName1', '=', $v_data['TimeCategoryName1']],
                    ['season', '=', $season],
                    ['CategoryName1', '=', $v_data['CategoryName1']],
                    ['CategoryName2', '=', $v_data['CategoryName2']],
                    ['StyleCategoryName', '=', $v_data['StyleCategoryName']],
                ])->find();
                if ($if_exist) {//存在则删除，重新插入
                    $this->puhuo_daxiaoma_customer_sort_model::where([
                        ['WarehouseName', '=', $v_data['WarehouseName']],
                        ['TimeCategoryName1', '=', $v_data['TimeCategoryName1']],
                        ['season', '=', $season],
                        ['CategoryName1', '=', $v_data['CategoryName1']],
                        ['CategoryName2', '=', $v_data['CategoryName2']],
                        ['StyleCategoryName', '=', $v_data['StyleCategoryName']],
                    ])->delete();
                }
                $chunk_list = array_chunk($daxiaoma_skcnum_score_sort, 500);
                foreach($chunk_list as $key => $val) {
                    $insert = $this->db_easy->table('sp_lyp_puhuo_daxiaoma_customer_sort')->strict(false)->insertAll($val);
                }
            }

        }

        return array_values($add_puhuo_log);


    }


    protected function return_each_daxiaoma_puhuo_log($each, $Stock_35, $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log) {

        $new_daxiaoma_puhuo_log = [];
        $new_daxiaoma_puhuo_log_no = [];
        $each_Stock = $Stock_35;//原本总库存
        $each_sum = 0;//累计铺去的库存
        $Stock_35_sort_arr = sort_arr($new_daxiaoma_skcnum_score_sort, 'Stock_'.$each.'_skcnum_sort', SORT_ASC);
        //对大小码可铺数组按排序顺序重新进行排序
        $last_daxiaoma_puhuo_log = $last_daxiaoma_puhuo_log ?: $daxiaoma_puhuo_log;
        foreach ($Stock_35_sort_arr as $k_Stock_35_sort_arr=>$v_Stock_35_sort_arr) {
            foreach ($last_daxiaoma_puhuo_log as $v_daxiaoma_puhuo_log) {
                if ($v_Stock_35_sort_arr['CustomerName'] == $v_daxiaoma_puhuo_log['CustomerName']) {
                    // $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;

                    if ($v_Stock_35_sort_arr['Stock_'.$each.'_skcnum_cur'] >= $v_Stock_35_sort_arr['Stock_'.$each.'_skcnum']) {//该店 当前尺码已满足无须再铺
                        $new_daxiaoma_puhuo_log_no[] = $v_daxiaoma_puhuo_log;
                    } else {
                        $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;
                    }

                }
            }
        }
        //开始逐个店铺货
        if ($new_daxiaoma_puhuo_log) {
            foreach ($new_daxiaoma_puhuo_log as &$v_new_daxiaoma_puhuo_log) {
    
                if ($each_sum >= $each_Stock || $each_Stock==0) break;
                if ($v_new_daxiaoma_puhuo_log['rule']['Stock_'.$each] > $each_Stock) {
    
                    $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'] = $each_Stock;
                    $each_sum += $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'];
                    $each_Stock = 0;
    
                } else {
    
                    $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'] = $v_new_daxiaoma_puhuo_log['rule']['Stock_'.$each];
                    $each_sum += $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'];
                    $each_Stock -= $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'];
    
                }
    
            }
        }
        //合适铺的 跟 不合适铺的 合并 
        $new_daxiaoma_puhuo_log = array_merge($new_daxiaoma_puhuo_log, $new_daxiaoma_puhuo_log_no);

        return $new_daxiaoma_puhuo_log;

        // $last_daxiaoma_puhuo_log = $new_daxiaoma_puhuo_log;


    }

    protected function return_each_daxiaoma_puhuo_log2($each, $Stock_35, $new_daxiaoma_skcnum_score_sort, $last_daxiaoma_puhuo_log, $daxiaoma_puhuo_log) {

        $new_daxiaoma_puhuo_log = [];
        $new_daxiaoma_puhuo_log_no = [];
        $each_Stock = $Stock_35;//原本总库存
        $each_sum = 0;//累计铺去的库存
        $Stock_35_sort_arr = sort_arr($new_daxiaoma_skcnum_score_sort, 'Stock_'.$each.'_skcnum_sort', SORT_ASC);
        // print_r($Stock_35_sort_arr);die;
        //对大小码可铺数组按排序顺序重新进行排序
        $last_daxiaoma_puhuo_log = $last_daxiaoma_puhuo_log ?: $daxiaoma_puhuo_log;
        foreach ($Stock_35_sort_arr as $k_Stock_35_sort_arr=>$v_Stock_35_sort_arr) {
            foreach ($last_daxiaoma_puhuo_log as $v_daxiaoma_puhuo_log) {
                if ($v_Stock_35_sort_arr['CustomerName'] == $v_daxiaoma_puhuo_log['CustomerName']) {
                    // $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;

                    if ($v_Stock_35_sort_arr['Stock_'.$each.'_skcnum_cur'] >= $v_Stock_35_sort_arr['Stock_'.$each.'_skcnum']) {//该店 当前尺码已满足无须再铺
                        $new_daxiaoma_puhuo_log_no[] = $v_daxiaoma_puhuo_log;
                    } else {
                        $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;
                    }

                }
            }
        }
        // print_r([$new_daxiaoma_puhuo_log, $new_daxiaoma_puhuo_log_no]);die;
        //开始逐个店铺货
        if ($new_daxiaoma_puhuo_log) {
            foreach ($new_daxiaoma_puhuo_log as &$v_new_daxiaoma_puhuo_log) {
    
                if ($each_sum >= $each_Stock || $each_Stock==0) break;
                if ($v_new_daxiaoma_puhuo_log['rule']['Stock_'.$each] > $each_Stock) {
    
                    $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'] = $each_Stock;
                    $each_sum += $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'];
                    $each_Stock = 0;
    
                } else {
    
                    $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'] = $v_new_daxiaoma_puhuo_log['rule']['Stock_'.$each];
                    $each_sum += $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'];
                    $each_Stock -= $v_new_daxiaoma_puhuo_log['Stock_'.$each.'_puhuo'];
    
                }
    
            }
        }
        // print_r([$new_daxiaoma_puhuo_log, $new_daxiaoma_puhuo_log_no]);die;
        //合适铺的 跟 不合适铺的 合并 
        $new_daxiaoma_puhuo_log = array_merge($new_daxiaoma_puhuo_log, $new_daxiaoma_puhuo_log_no);

        return $new_daxiaoma_puhuo_log;

        // $last_daxiaoma_puhuo_log = $new_daxiaoma_puhuo_log;


    }

    protected function return_which_stock_num($param) {

        $sign_vv = 0;
        switch ($param) {
            case 'Stock_00_puhuo': $sign_vv=28;break;
            case 'Stock_29_puhuo': $sign_vv=29;break;
            case 'Stock_30_puhuo': $sign_vv=30;break;
            case 'Stock_31_puhuo': $sign_vv=31;break;
            case 'Stock_32_puhuo': $sign_vv=32;break;
            case 'Stock_33_puhuo': $sign_vv=33;break;
            case 'Stock_34_puhuo': $sign_vv=34;break;
            case 'Stock_35_puhuo': $sign_vv=35;break;
            case 'Stock_36_puhuo': $sign_vv=36;break;
            case 'Stock_38_puhuo': $sign_vv=37;break;
            case 'Stock_40_puhuo': $sign_vv=38;break;
            case 'Stock_42_puhuo': $sign_vv=39;break;
        }
        return $sign_vv;

    }

    protected function return_which_stock_num_revert($param) {

        $sign_vv = '';
        switch ($param) {
            case 28: $sign_vv='Stock_00_puhuo';break;
            case 29: $sign_vv='Stock_29_puhuo';break;
            case 30: $sign_vv='Stock_30_puhuo';break;
            case 31: $sign_vv='Stock_31_puhuo';break;
            case 32: $sign_vv='Stock_32_puhuo';break;
            case 33: $sign_vv='Stock_33_puhuo';break;
            case 34: $sign_vv='Stock_34_puhuo';break;
            case 35: $sign_vv='Stock_35_puhuo';break;
            case 36: $sign_vv='Stock_36_puhuo';break;
            case 37: $sign_vv='Stock_38_puhuo';break;
            case 38: $sign_vv='Stock_40_puhuo';break;
            case 39: $sign_vv='Stock_42_puhuo';break;
        }
        return $sign_vv;

    }

    protected function return_daxiaoma_customer_sort_nd($v_last_daxiaoma_puhuo_log, $v_data, &$vv_daxiaoma_skcnum_score_sort, $ti_type) {

        $current_goods = in_array($v_data['StyleCategoryName2'], $ti_type) ? [] : [$v_data['GoodsNo']];//剔除指定款（如果是指定款，则不能进行满足率计算）

        if ($v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] > 0) {//28码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_29_puhuo'] > 0) {//29码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_29_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_29_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_34_puhuo'] > 0) {//34码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_34_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_34_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] > 0) {//35码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] > 0) {//36码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] > 0) {//38码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] > 0) {//40码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_42_puhuo'] > 0) {//42码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'] = $array_unique;
        }

    }

    protected function return_daxiaoma_customer_sort_xz($v_last_daxiaoma_puhuo_log, $v_data, &$vv_daxiaoma_skcnum_score_sort, $ti_type) {

        $current_goods = in_array($v_data['StyleCategoryName2'], $ti_type) ? [] : [$v_data['GoodsNo']];//剔除指定款（如果是指定款，则不能进行满足率计算）

        if ($v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] > 0) {//28码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] > 0) {//35码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] > 0) {//36码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] > 0) {//38码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] > 0) {//40码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_42_puhuo'] > 0) {//42码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_score'] = round($vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum'], 2);
            $vv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'] = $array_unique;
        }

    }



    ###################最终铺货数据使用的start######################

    protected function generate_end_data() {

        //先清空旧数据再跑
        $this->db_easy->Query("truncate table sp_lyp_puhuo_end_data;");
        $WarehouseName = $this->db_easy->Query("select distinct WarehouseName from sp_lyp_puhuo_cur_log;");
        if ($WarehouseName) {
            foreach ($WarehouseName as $v_ware) {
                $data = $this->get_data($v_ware['WarehouseName']);
                if ($data) {
                    $res_data = $this->get_end_data($data);
                    $chunk_list = array_chunk($res_data, 500);
                    foreach($chunk_list as $key => $val) {
                        $insert = $this->db_easy->table('sp_lyp_puhuo_end_data')->strict(false)->insertAll($val);
                    }
                }
            }
        }

    }

    protected function get_data($yuncang) {

        $sql = "select lpcs.Yuncang as WarehouseName
        , lpwg.TimeCategoryName1  
        , lpwg.TimeCategoryName2 
        , lpwg.CategoryName1 
        , lpwg.CategoryName2 
        , lpwg.CategoryName  
        , lpwg.GoodsName  
        , lpwg.StyleCategoryName 
        , lpwg.GoodsNo  
        , lpwg.StyleCategoryName1
        , lpwg.StyleCategoryName2 
        , lpwg.Lingxing 
        , lpwg.UnitPrice 
        , lpwg.ColorDesc 
        , lpwg.Stock_00_puhuo 
        , lpwg.Stock_29_puhuo 
        , lpwg.Stock_30_puhuo 
        , lpwg.Stock_31_puhuo 
        , lpwg.Stock_32_puhuo 
        , lpwg.Stock_33_puhuo 
        , lpwg.Stock_34_puhuo 
        , lpwg.Stock_35_puhuo 
        , lpwg.Stock_36_puhuo 
        , lpwg.Stock_38_puhuo 
        , lpwg.Stock_40_puhuo 
        , lpwg.Stock_42_puhuo 
        , 0 as Stock_44_puhuo 
        , lpwg.Stock_Quantity_puhuo 
				, lpwg.Stock_00 
        , lpwg.Stock_29 
        , lpwg.Stock_30 
        , lpwg.Stock_31 
        , lpwg.Stock_32 
        , lpwg.Stock_33 
        , lpwg.Stock_34 
        , lpwg.Stock_35 
        , lpwg.Stock_36 
        , lpwg.Stock_38 
        , lpwg.Stock_40 
        , lpwg.Stock_42 
        , 0 as Stock_44 
        , lpwg.Stock_Quantity
				, CONCAT(lpwg.WarehouseName, '', lpwg.GoodsNo ) as ware_goods
        from sp_lyp_puhuo_customer_sort lpcs 
        left join sp_lyp_puhuo_cur_log lpcl on lpcs.cur_log_uuid=lpcl.uuid 
        left join sp_lyp_puhuo_wait_goods lpwg on (lpcs.Yuncang=lpwg.WarehouseName and lpcs.GoodsNo=lpwg.GoodsNo)  
        where 1 and lpwg.WarehouseName='{$yuncang}' group by ware_goods;";
        return $this->db_easy->Query($sql);

    }

    protected function get_end_data($data) {

        $add_data = [];

        foreach ($data as $v_data) {

            $add_data_total = [

                [
                    'uuid' => '',
                    'WarehouseName' => $v_data['WarehouseName'],
                    'TimeCategoryName1' => $v_data['TimeCategoryName1'],
                    'TimeCategoryName2' => $v_data['TimeCategoryName2'],
                    'CategoryName1' => $v_data['CategoryName1'],
                    'CategoryName2' => $v_data['CategoryName2'],
                    'CategoryName' => $v_data['CategoryName'],
                    'GoodsName' => $v_data['GoodsName'],
                    'StyleCategoryName' => $v_data['StyleCategoryName'],
                    'GoodsNo' => $v_data['GoodsNo'],
                    
                    'StyleCategoryName1' => $v_data['StyleCategoryName1'],
                    'StyleCategoryName2' => $v_data['StyleCategoryName2'],
                    'Lingxing' => $v_data['Lingxing'],
                    'UnitPrice' => $v_data['UnitPrice'],
                    'ColorDesc' => $v_data['ColorDesc'],
                    'State' => '',
                    'CustomerName' => $v_data['WarehouseName'],
                    'CustomerId' => '',
                    'CustomerGrade' => '',
                    'Mathod' => $v_data['WarehouseName'],
                    
                    'StoreArea' => 0,
                    'xiuxian_num' => 0,
                    'score_sort' => 0,
                    'is_total' => 1,
                    'Stock_00_puhuo' => $v_data['Stock_00'],
                    'Stock_29_puhuo' => $v_data['Stock_29'],
                    'Stock_30_puhuo' => $v_data['Stock_30'],
                    'Stock_31_puhuo' => $v_data['Stock_31'],
                    'Stock_32_puhuo' => $v_data['Stock_32'],
                    'Stock_33_puhuo' => $v_data['Stock_33'],
                    'Stock_34_puhuo' => $v_data['Stock_34'],
                    'Stock_35_puhuo' => $v_data['Stock_35'],
                    'Stock_36_puhuo' => $v_data['Stock_36'],
                    'Stock_38_puhuo' => $v_data['Stock_38'],
                    'Stock_40_puhuo' => $v_data['Stock_40'],
                    'Stock_42_puhuo' => $v_data['Stock_42'],
                    'Stock_44_puhuo' => $v_data['Stock_44'],
                    'Stock_Quantity_puhuo' => $v_data['Stock_Quantity'],
                ],
    
                [
                    'uuid' => '',
                    'WarehouseName' => $v_data['WarehouseName'],
                    'TimeCategoryName1' => $v_data['TimeCategoryName1'],
                    'TimeCategoryName2' => $v_data['TimeCategoryName2'],
                    'CategoryName1' => $v_data['CategoryName1'],
                    'CategoryName2' => $v_data['CategoryName2'],
                    'CategoryName' => $v_data['CategoryName'],
                    'GoodsName' => $v_data['GoodsName'],
                    'StyleCategoryName' => $v_data['StyleCategoryName'],
                    'GoodsNo' => $v_data['GoodsNo'],
                    
                    'StyleCategoryName1' => $v_data['StyleCategoryName1'],
                    'StyleCategoryName2' => $v_data['StyleCategoryName2'],
                    'Lingxing' => $v_data['Lingxing'],
                    'UnitPrice' => $v_data['UnitPrice'],
                    'ColorDesc' => $v_data['ColorDesc'],
                    'State' => '',
                    'CustomerName' => '余量',
                    'CustomerId' => '',
                    'CustomerGrade' => '',
                    'Mathod' => '余量',
                    
                    'StoreArea' => 0,
                    'xiuxian_num' => 0,
                    'score_sort' => 0,
                    'is_total' => 1,
                    'Stock_00_puhuo' => $v_data['Stock_00_puhuo'],
                    'Stock_29_puhuo' => $v_data['Stock_29_puhuo'],
                    'Stock_30_puhuo' => $v_data['Stock_30_puhuo'],
                    'Stock_31_puhuo' => $v_data['Stock_31_puhuo'],
                    'Stock_32_puhuo' => $v_data['Stock_32_puhuo'],
                    'Stock_33_puhuo' => $v_data['Stock_33_puhuo'],
                    'Stock_34_puhuo' => $v_data['Stock_34_puhuo'],
                    'Stock_35_puhuo' => $v_data['Stock_35_puhuo'],
                    'Stock_36_puhuo' => $v_data['Stock_36_puhuo'],
                    'Stock_38_puhuo' => $v_data['Stock_38_puhuo'],
                    'Stock_40_puhuo' => $v_data['Stock_40_puhuo'],
                    'Stock_42_puhuo' => $v_data['Stock_42_puhuo'],
                    'Stock_44_puhuo' => $v_data['Stock_44_puhuo'],
                    'Stock_Quantity_puhuo' => $v_data['Stock_Quantity_puhuo'],
                ],
    
            ];

            $add_data = array_merge($add_data, $add_data_total);

            //该云仓下 各个货品铺货情况获取
            $ware_goods = $this->db_easy->Query($this->get_ware_goods_sql($v_data['WarehouseName'], $v_data['GoodsNo']));
            if ($ware_goods) {
                $add_data = array_merge($add_data, $ware_goods);
            }

        }
        return $add_data;

    }

    protected function get_ware_goods_sql($WarehouseName, $GoodsNo) {

        return "select lpcl.uuid
        , lpcs.Yuncang as WarehouseName
        , lpwg.TimeCategoryName1  
        , lpwg.TimeCategoryName2 
        , lpwg.CategoryName1 
        , lpwg.CategoryName2 
        , lpwg.CategoryName  
        , lpwg.GoodsName  
        , lpwg.StyleCategoryName 
        , lpwg.GoodsNo  
        , lpwg.StyleCategoryName1
        , lpwg.StyleCategoryName2 
        , lpwg.Lingxing 
        , lpwg.UnitPrice 
        , lpwg.ColorDesc 
        , left(lpcs.State, 2) as State
        , lpcs.CustomerName
        , lpcs.CustomerId 
        , lpcs.CustomerGrade
        , lpcs.Mathod
        , lpcs.StoreArea
        , lpcs.xiuxian_num 
        , lpcs.score_sort
        , 0 as is_total 
        , lpcl.Stock_00_puhuo 
        , lpcl.Stock_29_puhuo 
        , lpcl.Stock_30_puhuo 
        , lpcl.Stock_31_puhuo 
        , lpcl.Stock_32_puhuo 
        , lpcl.Stock_33_puhuo 
        , lpcl.Stock_34_puhuo 
        , lpcl.Stock_35_puhuo 
        , lpcl.Stock_36_puhuo 
        , lpcl.Stock_38_puhuo 
        , lpcl.Stock_40_puhuo 
        , lpcl.Stock_42_puhuo 
        , 0 as Stock_44_puhuo 
        , lpcl.total as Stock_Quantity_puhuo  
        from sp_lyp_puhuo_customer_sort lpcs 
        left join sp_lyp_puhuo_cur_log lpcl on lpcs.cur_log_uuid=lpcl.uuid 
        left join sp_lyp_puhuo_wait_goods lpwg on (lpcs.Yuncang=lpwg.WarehouseName and lpcs.GoodsNo=lpwg.GoodsNo)  
        where 1 and lpwg.WarehouseName='{$WarehouseName}' and lpwg.GoodsNo='{$GoodsNo}'";

    }

    ###################最终铺货数据使用的end######################

}
