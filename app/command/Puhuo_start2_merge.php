<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
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
use app\admin\model\bi\SpLypPuhuoZdySetModel;
use app\admin\model\bi\SpLypPuhuoZdySet2Model;
use app\admin\model\bi\SpLypPuhuoZdyYuncangGoodsModel;
use app\admin\model\bi\SpLypPuhuoZdyYuncangGoods2Model;
use app\admin\model\bi\SpLypPuhuoOnegoodsRuleModel;
use app\admin\model\bi\SpLypPuhuoRunModel;
use app\admin\model\bi\SpLypPuhuoZdkphmdModel;
use app\admin\model\bi\SpLypPuhuoDdUserModel;
use app\admin\model\bi\SpLypPuhuoWarehouseReserveConfigModel;
use app\admin\model\bi\SpLypPuhuoWarehouseReserveGoodsModel;
use app\admin\model\bi\SpLypPuhuoYujiStockModel;
use app\admin\model\bi\CwlDaxiaoHandleModel;
use app\admin\model\bi\SpWwChunxiaStockModel;
use app\api\service\dingding\Sample;
// use app\admin\model\CustomerModel;
//自动铺货2.0版
//合并 puhuo_yuncangkeyong、puhuo_start2_pro 逻辑
//每天凌晨04:00跑，预计10分钟跑完20个货号
//1.sp_lyp_puhuo_customer_sort(主码排序)  2.sp_lyp_puhuo_cur_log/sp_lyp_puhuo_log   3.sp_lyp_puhuo_daxiaoma_customer_sort（大小码排序） 4.sp_lyp_puhuo_end_data（最终铺货结果）
class Puhuo_start2_merge extends Command
{
    protected $db_easy;
    protected $list_rows=160;//每页条数
    protected $page=1;//当前页
    // protected $customer_model;
    protected $sp_ww_chunxia_stock_model;
    protected $puhuo_rule_model;
    protected $puhuo_rule_b_model;
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
    protected $puhuo_zdy_set_model;
    protected $puhuo_zdy_set2_model;
    protected $puhuo_zdy_yuncang_goods_model;
    protected $puhuo_zdy_yuncang_goods2_model;
    protected $puhuo_onegoods_rule_model;
    protected $puhuo_yuji_stock_model;
    protected $cwl_daxiao_handle_model;

    protected function configure()
    {
        // 指令配置
        $this->setName('Puhuo_start2_merge')
            ->addArgument('if_deal_yuncangkeyong', Argument::OPTIONAL)//是否处理云仓可用
            ->addArgument('list_rows', Argument::OPTIONAL)//每页条数
            ->setDescription('the Puhuo_start2_merge command');
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
        $this->puhuo_zdy_set_model = new SpLypPuhuoZdySetModel();
        $this->puhuo_zdy_set2_model = new SpLypPuhuoZdySet2Model();
        $this->puhuo_zdy_yuncang_goods_model = new SpLypPuhuoZdyYuncangGoodsModel();
        $this->puhuo_zdy_yuncang_goods2_model = new SpLypPuhuoZdyYuncangGoods2Model();
        $this->puhuo_onegoods_rule_model = new SpLypPuhuoOnegoodsRuleModel();
        $this->cwl_daxiao_handle_model = new CwlDaxiaoHandleModel();
        $this->puhuo_rule_b_model = new SpLypPuhuoRuleBModel();
        $this->puhuo_yuji_stock_model = new SpLypPuhuoYujiStockModel();
    }

    protected function execute(Input $input, Output $output) {
        
        ini_set('memory_limit','1024M');
        $db = Db::connect("mysql");

        $if_deal_yuncangkeyong    = $input->getArgument('if_deal_yuncangkeyong') ?? 1;//是否处理云仓可用
        $list_rows    = $input->getArgument('list_rows') ?: 1000;//每页条数

        //铺货开始 更新铺货状态
        $puhuo_run_res = SpLypPuhuoRunModel::create(['puhuo_status' => SpLypPuhuoRunModel::PUHUO_STATUS['running']]);

        //先处理 puhuo_yuncangkeyong 
        if ($if_deal_yuncangkeyong) {
            $this->puhuo_yuncangkeyong2();
        }

        //店铺预计库存实时更新
        $this->puhuo_customer_yuji_stock();

        //铺货规则获取
        $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();
        $puhuo_rule = $puhuo_config['puhuo_rule'];
        if ($puhuo_rule == 1) {//A方案
            $this->puhuo_rule_model = new SpLypPuhuoRuleAModel();
        } else {
            $this->puhuo_rule_model = new SpLypPuhuoRuleBModel();
        }
        
        $data = $this->get_wait_goods_data($list_rows);
        $data_taozhuang = $this->get_wait_goods_data_taozhuang($list_rows);//套装套西
        // print_r([$data, $data_taozhuang]);die;

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
        //云仓归类、按省份归类店铺
        $all_customer_arr = $all_customer_arr_state = [];
        if ($all_customers) {
            foreach ($all_customers as $v_customer) {
                $all_customer_arr[$v_customer['CustomItem15']][] = $v_customer;
                $all_customer_arr_state[$v_customer['State']][] = $v_customer;
            }
        }

        $customer_level = $this->puhuo_score_model::where([['config_str', '=', 'customer_level']])->column('*', 'key');
        $fill_rate_level = $this->puhuo_score_model::where([['config_str', '=', 'fill_rate']])->column('*', 'key_level');
        $dongxiao_rate_level = $this->puhuo_score_model::where([['config_str', '=', 'dongxiao_rate']])->column('*', 'key_level');
        // print_r($fill_rate_level);die;
        //剔除的货品
        $ti_goods = $this->puhuo_ti_goods_model::where([])->column('GoodsNo');
        $ti_goods = $this->get_goods_str($ti_goods);

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

        //大小码店信息
        $daxiao_res = $this->get_daxiao_handle();

        //指定门店信息
        $puhuo_zdkphmd = SpLypPuhuoZdkphmdModel::where([])->field('B,A1,A2,A3,N,H3,H6,K1,K2,X1,X2,X3')->select();
        $puhuo_zdkphmd = $puhuo_zdkphmd ? $puhuo_zdkphmd->toArray() : [];
        // print_r($puhuo_zdkphmd);die;

        if ($data) {

            //开始尝试自动铺货模式
            foreach ($data as $v_data_each) {
                // print_r($v_data_each);die;
                $v_data = $v_data_each['goods'] ?? [];
                $Selecttype = $v_data_each['Selecttype'] ?? 0;//0-该云仓所有店 1-多店 2-多省 3-商品专员 4-经营模式
                $Commonfield = $v_data_each['Commonfield'] ?? '';
                $rule_type = $v_data_each['rule_type'] ?? 1;//1-A方案 2-B方案
                $remain_store = $v_data_each['remain_store'] ?? 2;//剩余门店 1-铺 2-不铺
                $remain_rule_type = $v_data_each['remain_rule_type'] ?? 0;//铺货方案2 0-不选 1-A  2-B
                $zuhe_customer = $v_data_each['zuhe_customer'] ?? '';//指定类型1-组合 的店铺
                $if_zdmd = $v_data_each['if_zdmd'] ?? 1;//是否指定门店 1-是 2-否

                $GoodsNo = $v_data['GoodsNo'] ?: '';//货号
                $WarehouseName = $v_data['WarehouseName'] ?: '';//云仓
                $TimeCategoryName1 = $v_data['TimeCategoryName1'] ?: '';//一级时间分类
                $TimeCategoryName2 = $v_data['TimeCategoryName2'] ?: '';//二级时间分类(季节)
                $CategoryName1 = $v_data['CategoryName1'] ?: '';//一级分类
                $CategoryName2 = $v_data['CategoryName2'] ?: '';//二级分类
                $StyleCategoryName = $v_data['StyleCategoryName'] ?: '';//(风格)基本款
                $StyleCategoryName1 = $v_data['StyleCategoryName1'] ?: '';//一级风格
                $StyleCategoryName2 = $v_data['StyleCategoryName2'] ?: '';//二级风格（货品等级）
                $StyleCategoryName2_sign = $StyleCategoryName2 ? mb_substr($StyleCategoryName2, 0, -1) : '';
                $StyleCategoryName2_customer = $StyleCategoryName2_sign ? array_column($puhuo_zdkphmd, $StyleCategoryName2_sign) : [];
                $StyleCategoryName2_customer = $StyleCategoryName2_customer ? $StyleCategoryName2_customer : array_column($puhuo_zdkphmd, 'B');
                $StyleCategoryName2_customer = $StyleCategoryName2_customer ? array_unique(array_filter($StyleCategoryName2_customer)) : [];

                $all_customers = $ex_store = [];

                switch ($Selecttype) {
                    case 0: 
                        $all_customers = $all_customer_arr[$WarehouseName] ?? [];
                        break;
                    case 1: //组合（多省、商品专员、经营模式）

                        $ex_store = $zuhe_customer ? explode(',', $zuhe_customer) : [];

                        break;
                    case 2: //单店

                        $ex_store = $Commonfield ? explode(',', $Commonfield) : [];

                        break;
                }

                if ($ex_store) {
                    foreach ($ex_store as $v_Commonfield) {
                        foreach ($all_customer_arr[$WarehouseName] as $v_cus) {
                            if ($v_Commonfield == $v_cus['CustomerName']) {
                                $all_customers[] = $v_cus;
                            }
                        }
                    }
                }
                if ($remain_store == $this->puhuo_zdy_set2_model::REMAIN_STORE['puhuo']) {//如果剩余门店：铺 则店铺应该要获取该云仓的全部店铺
                    $all_customers = $all_customer_arr[$WarehouseName] ?? [];
                }

                //如果是指定门店铺货
                if ($if_zdmd == $this->puhuo_zdy_set2_model::IF_ZDMD['is_zhiding']) {
                    $all_customers_intersect = array_intersect($StyleCategoryName2_customer, $all_customers ? array_column($all_customers, 'CustomerName') : []);
                    $all_customers_arr = [];
                    foreach ($all_customers as $v_cus) {
                        if (in_array($v_cus['CustomerName'], $all_customers_intersect)) $all_customers_arr[] = $v_cus;
                    }
                    $all_customers = $all_customers_arr;
                }

                //大小码-满足率-分母
                $season = $this->get_season_str($TimeCategoryName2);
                $season = $season ? $season.'季' : '';
                $daxiaoma_skcnum_info = $this->puhuo_daxiaoma_skcnum_model::where([['WarehouseName', '=', $WarehouseName], ['TimeCategoryName1', '=', $TimeCategoryName1], ['season', '=', $season], 
                ['CategoryName1', '=', $CategoryName1], ['CategoryName2', '=', $CategoryName2], ['StyleCategoryName', '=', $StyleCategoryName]])->find();

                //是否已存在 daxiaoma_customer_sort
                $if_exist_daxiaoma_customer_sort = $this->puhuo_daxiaoma_customer_sort_model::where([['WarehouseName', '=', $WarehouseName], ['TimeCategoryName1', '=', $TimeCategoryName1], ['season', '=', $season], 
                ['CategoryName1', '=', $CategoryName1], ['CategoryName2', '=', $CategoryName2], ['StyleCategoryName', '=', $StyleCategoryName]])->column('*', 'CustomerName');

                //先查看单款是否已经指定了特定的铺货规则,优先选择已指定的特定铺货规则
                // $if_exist_onegoods_rule = $this->puhuo_onegoods_rule_model::where([['por.Yuncang', '=', $WarehouseName], ['por.GoodsNo', '=', $GoodsNo]])->alias('por')
                // ->join(['sp_lyp_puhuo_rule_b' => 'prb'], 'por.rule_id=prb.id', 'inner')
                // ->field('prb.*')->find();
                // $if_exist_onegoods_rule = $if_exist_onegoods_rule ? $if_exist_onegoods_rule->toArray() : [];

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
                        // $daxiaodian_info = $this->puhuo_daxiaoma_customer_model::where([['customer_name', '=', $v_customer['CustomerName']]])->field('big_small_store')->find();
                        // $daxiaodian_info = $daxiaodian_info ? $daxiaodian_info->toArray() : [];
                        // $store_type = ($daxiaodian_info&&$daxiaodian_info['big_small_store']) ? ($this->puhuo_daxiaoma_customer_sort_model::store_type[$daxiaodian_info['big_small_store']] ?? 0) : 0;

                        // $daxiaodian_info = $this->cwl_daxiao_handle_model::where([['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', $CategoryName1], ['二级分类', '=', $CategoryName2], 
                        // ['风格', '=', $StyleCategoryName], ['一级风格', '=', $StyleCategoryName1], ['季节归集', '=', $season]])->field('店铺名称,大小码提醒 as big_small_store')->find();
                        // $daxiaodian_info = $daxiaodian_info ? $daxiaodian_info->toArray() : [];
                        // $store_type = ($daxiaodian_info&&$daxiaodian_info['big_small_store']) ? ($this->cwl_daxiao_handle_model::store_type_text[$daxiaodian_info['big_small_store']] ?? 0) : 2;//1-大码店 2-正常店 3-小码店
                        $daxiaodian_info = $daxiao_res[$v_customer['CustomerName'].$CategoryName1.$CategoryName2.$StyleCategoryName.$StyleCategoryName1.$season] ?? [];
                        $store_type = ($daxiaodian_info&&$daxiaodian_info['big_small_store']) ? ($this->cwl_daxiao_handle_model::store_type_text[$daxiaodian_info['big_small_store']] ?? 0) : 2;//1-大码店 2-正常店 3-小码店

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

                                $rule = [];
                                $where = [
                                    ['Yuncang', '=', $WarehouseName], 
                                    ['State', '=', $v_customer['State']], 
                                    ['StyleCategoryName', '=', $v_data['StyleCategoryName']], 
                                    // ['StyleCategoryName1', '=', $v_data['StyleCategoryName1']], 
                                    ['CategoryName1', '=', $v_data['CategoryName1']], 
                                    ['CategoryName2', '=', $v_data['CategoryName2']], 
                                    ['CategoryName', '=', $v_data['CategoryName']], 
                                    ['CustomerGrade', '=', $v_customer['CustomerGrade']]
                                ];
                                $where_qita = [
                                    ['Yuncang', '=', $WarehouseName], 
                                    ['State', '=', $v_customer['State']], 
                                    ['StyleCategoryName', '=', $v_data['StyleCategoryName']], 
                                    // ['StyleCategoryName1', '=', $v_data['StyleCategoryName1']], 
                                    ['CategoryName1', '=', $v_data['CategoryName1']], 
                                    ['CategoryName2', '=', $v_data['CategoryName2']], 
                                    ['CategoryName', '=', '其它'], 
                                    ['CustomerGrade', '=', $v_customer['CustomerGrade']]
                                ];

                                $insert_rule_type = 0;
                                if (in_array($v_customer['CustomerName'], $ex_store)) {
                                    //查询对应的铺货标准
                                    if ($rule_type == $this->puhuo_zdy_set2_model::RULE_TYPE['type_b']) {//如单款指定了铺货规则B
                                        $rule = $this->puhuo_rule_b_model::where($where)->find();
                                        if (!$rule) $rule = $this->puhuo_rule_b_model::where($where_qita)->find();
                                    } else {
                                        $rule = $this->puhuo_rule_model::where($where)->find();
                                        if (!$rule) $rule = $this->puhuo_rule_model::where($where_qita)->find();
                                    }
                                    $rule = $rule ? $rule->toArray() : [];
                                    $insert_rule_type = $rule_type;
                                } else {
                                    if ($remain_store == $this->puhuo_zdy_set2_model::REMAIN_STORE['puhuo']) {
                                        //查询对应的铺货标准
                                        if ($remain_rule_type == $this->puhuo_zdy_set2_model::RULE_TYPE['type_b']) {//如单款指定了铺货规则B
                                            $rule = $this->puhuo_rule_b_model::where($where)->find();
                                            if (!$rule) $rule = $this->puhuo_rule_b_model::where($where_qita)->find();
                                            $rule = $rule ? $rule->toArray() : [];
                                        } elseif ($remain_rule_type == $this->puhuo_zdy_set2_model::RULE_TYPE['type_a']) {
                                            $rule = $this->puhuo_rule_model::where($where)->find();
                                            if (!$rule) $rule = $this->puhuo_rule_model::where($where_qita)->find();
                                            $rule = $rule ? $rule->toArray() : [];
                                        } else {
                                            $rule = [];
                                        }
                                        $insert_rule_type = $remain_rule_type;
                                    }
                                }

                                // print_r($where);die;

                                //该店该款 库存数
                                // print_r([['省份', '=', $v_customer['State']], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', $v_data['CategoryName1']], ['二级分类', '=', $v_data['CategoryName2']], ['分类', '=', $v_data['CategoryName']], ['货号', '=', $GoodsNo]]);die;
                                $goods_yuji_stock = $this->puhuo_yuji_stock_model::where([['省份', '=', $v_customer['State']], ['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', $v_data['CategoryName1']], ['二级分类', '=', $v_data['CategoryName2']], ['分类', '=', $v_data['CategoryName']], ['货号', '=', $GoodsNo]])->field('预计库存')->find();
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
                                        'rule_type' => $insert_rule_type,
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
                                        'rule_type' => $insert_rule_type,
                                        'rule_id' => $rule ? $rule['id'] : 0,
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

                                //记录铺货日志：
                                Log::channel('puhuo')->write('##############普通-云仓-货号-店铺:'.$WarehouseName.$GoodsNo.$v_customer['CustomerName'].'##############'.json_encode(['rule'=>$rule, 'v_data'=>$v_data, 'goods_yuji_stock'=>$goods_yuji_stock, 'current_14days'=>$current_14days, 'can_puhuo***result***：'=>$can_puhuo]) );

                                $this->puhuo_customer_sort_model::where([['GoodsNo', '=', $GoodsNo], ['CustomerName', '=', $v_customer['CustomerName']]])->update(['cur_log_uuid' => $uuid]);

                            }
                            // print_r([$add_puhuo_log, $v_data]);die;

                            ######################大小码铺货逻辑start(只针对 主码 可铺的店进行大小码 铺货)############################
                            
                            //echo json_encode(['daxiaoma_puhuo_log'=>$daxiaoma_puhuo_log, 'daxiaoma_skcnum_score_sort'=>$daxiaoma_skcnum_score_sort,  'add_puhuo_log'=>$add_puhuo_log,  'v_data'=>$v_data,  'puhuo_config'=>$puhuo_config]);die;
                            $add_puhuo_log = $this->check_daxiaoma($daxiaoma_puhuo_log, $daxiaoma_skcnum_score_sort, $add_puhuo_log, $v_data, $puhuo_config);
                            
                            ######################大小码铺货逻辑end################################################################

                            //记录铺货日志：
                            Log::channel('puhuo')->write('##############普通-大小码铺货逻辑处理完毕后：add_puhuo_log##############'.json_encode(['add_puhuo_log'=>$add_puhuo_log]) );

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


        }


        //记录铺货日志：
        Log::channel('puhuo')->write('##############记录日志，接下来进行套装套西铺货##############');

        ######################套装套西处理start##########################
        //铺 套装套西
        $this->puhuo_taozhuang($data_taozhuang, $all_customer_arr, $all_customer_arr_state, $customer_level, $qiwen_config_arr, $ti_goods, $fill_rate_level,
    $dongxiao_rate_level, $daxiao_res, $puhuo_config, $puhuo_zdkphmd);
        ######################套装套西处理end##########################

        //记录铺货日志：
        Log::channel('puhuo')->write('##############记录日志，最终铺货数据清洗##############');

        //最终铺货数据清洗
        $this->generate_end_data();

        //铺货完成 修改puhuo_run状态
        SpLypPuhuoRunModel::where([['id', '=', $puhuo_run_res->id]])->update(['puhuo_status' => SpLypPuhuoRunModel::PUHUO_STATUS['finish']]);

        //铺货完成钉钉通知
        $sample = new Sample();
        $users = SpLypPuhuoDdUserModel::where([])->select();
        $users = $users ? $users->toArray() : [];
        if ($users) {
            foreach ($users as $val) {
                $sample->sendMarkdownImg($val['userid'], '铺货完成通知------'.date('Y-m-d H:i:s'), '');
            }
        }

        echo 'okk';die;
        
    }


    //铺 套装套西
    protected function puhuo_taozhuang($data_taozhuang, $all_customer_arr, $all_customer_arr_state, $customer_level, $qiwen_config_arr, $ti_goods, $fill_rate_level,
    $dongxiao_rate_level, $daxiao_res, $puhuo_config, $puhuo_zdkphmd) {

        if ($data_taozhuang) {

            //开始尝试自动铺货模式
            foreach ($data_taozhuang as $v_data_each) {
                // print_r($v_data_each);die;
                $goods = $v_data_each['goods'] ?? [];
                $Selecttype = $v_data_each['Selecttype'] ?? 0;//0-该云仓所有店 1-多店 2-多省 3-商品专员 4-经营模式
                $Commonfield = $v_data_each['Commonfield'] ?? '';
                $rule_type = $v_data_each['rule_type'] ?? 1;//1-A方案 2-B方案
                $remain_store = $v_data_each['remain_store'] ?? 2;//剩余门店 1-铺 2-不铺
                $remain_rule_type = $v_data_each['remain_rule_type'] ?? 0;//铺货方案2 0-不选 1-A  2-B
                $zuhe_customer = $v_data_each['zuhe_customer'] ?? '';//指定类型1-组合 的店铺
                $if_zdmd = $v_data_each['if_zdmd'] ?? 1;//是否指定门店 1-是 2-否

                if ($goods) {

                    foreach ($goods as $v_goods) {//每两个货号为一对(上衣+裤子，必须同时满足连码要求才能铺)

                        $res_taozhuang = [];
                        $wait_goods_stock = [];
                        foreach ($v_goods as $v_data) {

                            $GoodsNo = $v_data['GoodsNo'] ?: '';//货号
                            $WarehouseName = $v_data['WarehouseName'] ?: '';//云仓
                            $TimeCategoryName1 = $v_data['TimeCategoryName1'] ?: '';//一级时间分类
                            $TimeCategoryName2 = $v_data['TimeCategoryName2'] ?: '';//二级时间分类(季节)
                            $CategoryName1 = $v_data['CategoryName1'] ?: '';//一级分类
                            $CategoryName2 = $v_data['CategoryName2'] ?: '';//二级分类
                            $StyleCategoryName = $v_data['StyleCategoryName'] ?: '';//(风格)基本款
                            $StyleCategoryName1 = $v_data['StyleCategoryName1'] ?: '';//一级风格
                            $StyleCategoryName2 = $v_data['StyleCategoryName2'] ?: '';//二级风格（货品等级）
                            $StyleCategoryName2_sign = $StyleCategoryName2 ? mb_substr($StyleCategoryName2, 0, -1) : '';
                            $StyleCategoryName2_customer = $StyleCategoryName2_sign ? array_column($puhuo_zdkphmd, $StyleCategoryName2_sign) : [];
                            $StyleCategoryName2_customer = $StyleCategoryName2_customer ? $StyleCategoryName2_customer : array_column($puhuo_zdkphmd, 'B');
                            $StyleCategoryName2_customer = $StyleCategoryName2_customer ? array_unique(array_filter($StyleCategoryName2_customer)) : [];
    
                            $all_customers = $ex_store = [];
    
                            switch ($Selecttype) {
                                case 0: 
                                    $all_customers = $all_customer_arr[$WarehouseName] ?? [];
                                    break;
                                case 1: //组合（多省、商品专员、经营模式）

                                    $ex_store = $zuhe_customer ? explode(',', $zuhe_customer) : [];

                                    break;
                                case 2: //单店

                                    $ex_store = $Commonfield ? explode(',', $Commonfield) : [];
                            
                                    break;
                            }

                            if ($ex_store) {
                                foreach ($ex_store as $v_Commonfield) {
                                    foreach ($all_customer_arr[$WarehouseName] as $v_cus) {
                                        if ($v_Commonfield == $v_cus['CustomerName']) {
                                            $all_customers[] = $v_cus;
                                        }
                                    }
                                }
                            }
                            if ($remain_store == $this->puhuo_zdy_set2_model::REMAIN_STORE['puhuo']) {//如果剩余门店：铺 则店铺应该要获取该云仓的全部店铺
                                $all_customers = $all_customer_arr[$WarehouseName] ?? [];
                            }

                            //如果是指定门店铺货
                            if ($if_zdmd == $this->puhuo_zdy_set2_model::IF_ZDMD['is_zhiding']) {
                                $all_customers_intersect = array_intersect($StyleCategoryName2_customer, $all_customers ? array_column($all_customers, 'CustomerName') : []);
                                $all_customers_arr = [];
                                foreach ($all_customers as $v_cus) {
                                    if (in_array($v_cus['CustomerName'], $all_customers_intersect)) $all_customers_arr[] = $v_cus;
                                }
                                $all_customers = $all_customers_arr;
                            }
                            // print_r($all_customers);die;
    
                            //大小码-满足率-分母
                            $season = $this->get_season_str($TimeCategoryName2);
                            $season = $season ? $season.'季' : '';
                            $daxiaoma_skcnum_info = $this->puhuo_daxiaoma_skcnum_model::where([['WarehouseName', '=', $WarehouseName], ['TimeCategoryName1', '=', $TimeCategoryName1], ['season', '=', $season], 
                            ['CategoryName1', '=', $CategoryName1], ['CategoryName2', '=', $CategoryName2], ['StyleCategoryName', '=', $StyleCategoryName]])->find();
    
                            //是否已存在 daxiaoma_customer_sort
                            $if_exist_daxiaoma_customer_sort = $this->puhuo_daxiaoma_customer_sort_model::where([['WarehouseName', '=', $WarehouseName], ['TimeCategoryName1', '=', $TimeCategoryName1], ['season', '=', $season], 
                            ['CategoryName1', '=', $CategoryName1], ['CategoryName2', '=', $CategoryName2], ['StyleCategoryName', '=', $StyleCategoryName]])->column('*', 'CustomerName');
    
                            //先查看单款是否已经指定了特定的铺货规则,优先选择已指定的特定铺货规则
                            // $if_exist_onegoods_rule = $this->puhuo_onegoods_rule_model::where([['por.Yuncang', '=', $WarehouseName], ['por.GoodsNo', '=', $GoodsNo]])->alias('por')
                            // ->join(['sp_lyp_puhuo_rule_b' => 'prb'], 'por.rule_id=prb.id', 'inner')
                            // ->field('prb.*')->find();
                            // $if_exist_onegoods_rule = $if_exist_onegoods_rule ? $if_exist_onegoods_rule->toArray() : [];
    
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
                                    // $daxiaodian_info = $this->puhuo_daxiaoma_customer_model::where([['customer_name', '=', $v_customer['CustomerName']]])->field('big_small_store')->find();
                                    // $daxiaodian_info = $daxiaodian_info ? $daxiaodian_info->toArray() : [];
                                    // $store_type = ($daxiaodian_info&&$daxiaodian_info['big_small_store']) ? ($this->puhuo_daxiaoma_customer_sort_model::store_type[$daxiaodian_info['big_small_store']] ?? 0) : 0;
    
                                    // $daxiaodian_info = $this->cwl_daxiao_handle_model::where([['店铺名称', '=', $v_customer['CustomerName']], ['一级分类', '=', $CategoryName1], ['二级分类', '=', $CategoryName2], 
                                    // ['风格', '=', $StyleCategoryName], ['一级风格', '=', $StyleCategoryName1], ['季节归集', '=', $season]])->field('店铺名称,大小码提醒 as big_small_store')->find();
                                    // $daxiaodian_info = $daxiaodian_info ? $daxiaodian_info->toArray() : [];
                                    // $store_type = ($daxiaodian_info&&$daxiaodian_info['big_small_store']) ? ($this->cwl_daxiao_handle_model::store_type_text[$daxiaodian_info['big_small_store']] ?? 0) : 2;//1-大码店 2-正常店 3-小码店
                                    $daxiaodian_info = $daxiao_res[$v_customer['CustomerName'].$CategoryName1.$CategoryName2.$StyleCategoryName.$StyleCategoryName1.$season] ?? [];
                                    $store_type = ($daxiaodian_info&&$daxiaodian_info['big_small_store']) ? ($this->cwl_daxiao_handle_model::store_type_text[$daxiaodian_info['big_small_store']] ?? 0) : 2;//1-大码店 2-正常店 3-小码店
    
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
    
                                            $rule = [];
                                            $where = [
                                                ['Yuncang', '=', $WarehouseName], 
                                                ['State', '=', $v_customer['State']], 
                                                ['StyleCategoryName', '=', $v_data['StyleCategoryName']], 
                                                // ['StyleCategoryName1', '=', $v_data['StyleCategoryName1']], 
                                                ['CategoryName1', '=', $v_data['CategoryName1']], 
                                                ['CategoryName2', '=', $v_data['CategoryName2']], 
                                                ['CategoryName', '=', $v_data['CategoryName']], 
                                                ['CustomerGrade', '=', $v_customer['CustomerGrade']]
                                            ];
                                            $where_qita = [
                                                ['Yuncang', '=', $WarehouseName], 
                                                ['State', '=', $v_customer['State']], 
                                                ['StyleCategoryName', '=', $v_data['StyleCategoryName']], 
                                                // ['StyleCategoryName1', '=', $v_data['StyleCategoryName1']], 
                                                ['CategoryName1', '=', $v_data['CategoryName1']], 
                                                ['CategoryName2', '=', $v_data['CategoryName2']], 
                                                ['CategoryName', '=', '其它'], 
                                                ['CustomerGrade', '=', $v_customer['CustomerGrade']]
                                            ];

                                            $insert_rule_type = 0;
                                            if (in_array($v_customer['CustomerName'], $ex_store)) {
                                                //查询对应的铺货标准
                                                if ($rule_type == $this->puhuo_zdy_set2_model::RULE_TYPE['type_b']) {//如单款指定了铺货规则B
                                                    $rule = $this->puhuo_rule_b_model::where($where)->find();
                                                    if (!$rule) $rule = $this->puhuo_rule_b_model::where($where_qita)->find();
                                                } else {
                                                    $rule = $this->puhuo_rule_model::where($where)->find();
                                                    if (!$rule) $rule = $this->puhuo_rule_model::where($where_qita)->find();
                                                }
                                                $rule = $rule ? $rule->toArray() : [];
                                                $insert_rule_type = $rule_type;
                                            } else {
                                                if ($remain_store == $this->puhuo_zdy_set2_model::REMAIN_STORE['puhuo']) {
                                                    //查询对应的铺货标准
                                                    if ($remain_rule_type == $this->puhuo_zdy_set2_model::RULE_TYPE['type_b']) {//如单款指定了铺货规则B
                                                        $rule = $this->puhuo_rule_b_model::where($where)->find();
                                                        if (!$rule) $rule = $this->puhuo_rule_b_model::where($where_qita)->find();
                                                        $rule = $rule ? $rule->toArray() : [];
                                                    } elseif ($remain_rule_type == $this->puhuo_zdy_set2_model::RULE_TYPE['type_a']) {
                                                        $rule = $this->puhuo_rule_model::where($where)->find();
                                                        if (!$rule) $rule = $this->puhuo_rule_model::where($where_qita)->find();
                                                        $rule = $rule ? $rule->toArray() : [];
                                                    } else {
                                                        $rule = [];
                                                    }
                                                    $insert_rule_type = $remain_rule_type;
                                                }
                                            }

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
                                                    'rule_type' => $insert_rule_type,
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
                                                    'rule_type' => $insert_rule_type,
                                                    'rule_id' => $rule ? $rule['id'] : 0,
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

                                            //记录铺货日志：
                                            Log::channel('puhuo')->write('##############套装套西-云仓-货号-店铺:'.$WarehouseName.$GoodsNo.$v_customer['CustomerName'].'##############'.json_encode(['rule'=>$rule, 'v_data'=>$v_data, 'goods_yuji_stock'=>$goods_yuji_stock, 'current_14days'=>$current_14days, 'can_puhuo***result***：'=>$can_puhuo]) );

                                            $this->puhuo_customer_sort_model::where([['GoodsNo', '=', $GoodsNo], ['CustomerName', '=', $v_customer['CustomerName']]])->update(['cur_log_uuid' => $uuid]);
    
                                        }
    
                                        ######################大小码铺货逻辑start(只针对 主码 可铺的店进行大小码 铺货)############################
                                        
                                        $add_puhuo_log = $this->check_daxiaoma($daxiaoma_puhuo_log, $daxiaoma_skcnum_score_sort, $add_puhuo_log, $v_data, $puhuo_config);

//                                        dd($add_puhuo_log);
                                        $res_taozhuang[] = $add_puhuo_log;
                                        //要查询最新的库存数据出来
                                        $cur_goods = $this->puhuo_wait_goods_model::where([['id', '=', $v_data['id']]])->find();
                                        $wait_goods_stock[] = $cur_goods ? $cur_goods->toArray() : [];

                                        ######################大小码铺货逻辑end################################################################

                                        //记录铺货日志：
                                        Log::channel('puhuo')->write('##############套装套西-大小码铺货逻辑处理完毕后：add_puhuo_log##############'.json_encode(['add_puhuo_log'=>$add_puhuo_log]) );
    
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
                        // print_r(json_encode(['res_taozhuang'=>$res_taozhuang, 'wait_goods_stock'=>$wait_goods_stock]));die;


                        //对套装套西铺货结果 处理，同个店铺必须满足上衣+裤子 都齐码 才能铺货
                        $can_puhuo_taozhuang = [];
                        if ($res_taozhuang && count($res_taozhuang)==2) {

                            $shangyi = $res_taozhuang[0] ?? [];
                            $kuzi = $res_taozhuang[1] ?? [];
                            $shangyi_stock = $wait_goods_stock[0] ?? [];
                            $kuzi_stock = $wait_goods_stock[1] ?? [];

                            // print_r($shangyi);die;
                            foreach ($shangyi as &$v_shangyi) {

                                $if_puhuo_shangyi = $v_shangyi['total'] ? 1 : 0;
                                $customer_if_puhuo_shangyi = $v_shangyi['CustomerName'].$if_puhuo_shangyi;
                                $match_shangyi_sign = 0;
                                foreach ($kuzi as $v_kuzi) {
                                    $if_puhuo_kuzi = $v_kuzi['total'] ? 1 : 0;
                                    $customer_if_puhuo_kuzi = $v_kuzi['CustomerName'].$if_puhuo_kuzi;

                                    if ($if_puhuo_shangyi && $if_puhuo_kuzi && ($customer_if_puhuo_shangyi == $customer_if_puhuo_kuzi)) {
                                        $can_puhuo_taozhuang[] = [
                                            'CustomerName' => $v_shangyi['CustomerName'],
                                            'shangyi_uuid' => $v_shangyi['uuid'],
                                            'kuzi_uuid' => $v_kuzi['uuid'], 
                                        ];
                                        $match_shangyi_sign = 1;
                                    }
                                }

                                if ($match_shangyi_sign == 0) {//(上衣) 若单店 上衣或裤子其中一个不满足连码，都不铺

                                    //wait_goods表库存处理
                                    $shangyi_stock['Stock_00_puhuo'] += $v_shangyi['Stock_00_puhuo'];
                                    $shangyi_stock['Stock_29_puhuo'] += $v_shangyi['Stock_29_puhuo'];
                                    $shangyi_stock['Stock_30_puhuo'] += $v_shangyi['Stock_30_puhuo'];
                                    $shangyi_stock['Stock_31_puhuo'] += $v_shangyi['Stock_31_puhuo'];
                                    $shangyi_stock['Stock_32_puhuo'] += $v_shangyi['Stock_32_puhuo'];
                                    $shangyi_stock['Stock_33_puhuo'] += $v_shangyi['Stock_33_puhuo'];
                                    $shangyi_stock['Stock_34_puhuo'] += $v_shangyi['Stock_34_puhuo'];
                                    $shangyi_stock['Stock_35_puhuo'] += $v_shangyi['Stock_35_puhuo'];
                                    $shangyi_stock['Stock_36_puhuo'] += $v_shangyi['Stock_36_puhuo'];
                                    $shangyi_stock['Stock_38_puhuo'] += $v_shangyi['Stock_38_puhuo'];
                                    $shangyi_stock['Stock_40_puhuo'] += $v_shangyi['Stock_40_puhuo'];
                                    $shangyi_stock['Stock_42_puhuo'] += $v_shangyi['Stock_42_puhuo'];
                                    $shangyi_stock['Stock_Quantity_puhuo'] += $v_shangyi['total'];

                                    $v_shangyi['Stock_00_puhuo'] = 0;
                                    $v_shangyi['Stock_29_puhuo'] = 0;
                                    $v_shangyi['Stock_30_puhuo'] = 0;
                                    $v_shangyi['Stock_31_puhuo'] = 0;
                                    $v_shangyi['Stock_32_puhuo'] = 0;
                                    $v_shangyi['Stock_33_puhuo'] = 0;
                                    $v_shangyi['Stock_34_puhuo'] = 0;
                                    $v_shangyi['Stock_35_puhuo'] = 0;
                                    $v_shangyi['Stock_36_puhuo'] = 0;
                                    $v_shangyi['Stock_38_puhuo'] = 0;
                                    $v_shangyi['Stock_40_puhuo'] = 0;
                                    $v_shangyi['Stock_42_puhuo'] = 0;
                                    $v_shangyi['total'] = 0;

                                }

                            }

                            //(裤子) 若单店 上衣或裤子其中一个不满足连码，都不铺 处理
                            $kuzi_uuid = [];
                            if ($can_puhuo_taozhuang) {
                                $kuzi_uuid = $can_puhuo_taozhuang ? array_column($can_puhuo_taozhuang, 'kuzi_uuid') : [];
                            }
                            foreach ($kuzi as &$v_kuzi) {
                                if ( !in_array($v_kuzi['uuid'], $kuzi_uuid) ) {
                                        //wait_goods表库存处理
                                        $kuzi_stock['Stock_00_puhuo'] += $v_kuzi['Stock_00_puhuo'];
                                        $kuzi_stock['Stock_29_puhuo'] += $v_kuzi['Stock_29_puhuo'];
                                        $kuzi_stock['Stock_30_puhuo'] += $v_kuzi['Stock_30_puhuo'];
                                        $kuzi_stock['Stock_31_puhuo'] += $v_kuzi['Stock_31_puhuo'];
                                        $kuzi_stock['Stock_32_puhuo'] += $v_kuzi['Stock_32_puhuo'];
                                        $kuzi_stock['Stock_33_puhuo'] += $v_kuzi['Stock_33_puhuo'];
                                        $kuzi_stock['Stock_34_puhuo'] += $v_kuzi['Stock_34_puhuo'];
                                        $kuzi_stock['Stock_35_puhuo'] += $v_kuzi['Stock_35_puhuo'];
                                        $kuzi_stock['Stock_36_puhuo'] += $v_kuzi['Stock_36_puhuo'];
                                        $kuzi_stock['Stock_38_puhuo'] += $v_kuzi['Stock_38_puhuo'];
                                        $kuzi_stock['Stock_40_puhuo'] += $v_kuzi['Stock_40_puhuo'];
                                        $kuzi_stock['Stock_42_puhuo'] += $v_kuzi['Stock_42_puhuo'];
                                        $kuzi_stock['Stock_Quantity_puhuo'] += $v_kuzi['total'];

                                        $v_kuzi['Stock_00_puhuo'] = 0;
                                        $v_kuzi['Stock_29_puhuo'] = 0;
                                        $v_kuzi['Stock_30_puhuo'] = 0;
                                        $v_kuzi['Stock_31_puhuo'] = 0;
                                        $v_kuzi['Stock_32_puhuo'] = 0;
                                        $v_kuzi['Stock_33_puhuo'] = 0;
                                        $v_kuzi['Stock_34_puhuo'] = 0;
                                        $v_kuzi['Stock_35_puhuo'] = 0;
                                        $v_kuzi['Stock_36_puhuo'] = 0;
                                        $v_kuzi['Stock_38_puhuo'] = 0;
                                        $v_kuzi['Stock_40_puhuo'] = 0;
                                        $v_kuzi['Stock_42_puhuo'] = 0;
                                        $v_kuzi['total'] = 0;
                                }
                            }

                            Db::startTrans();
                            try {
                                //删除旧数据 重新入库
                                $this->puhuo_log_model::where([['WarehouseName', '=', $shangyi[0]['WarehouseName']], ['GoodsNo', '=', $shangyi[0]['GoodsNo']]])->delete();
                                $this->puhuo_cur_log_model::where([['WarehouseName', '=', $shangyi[0]['WarehouseName']], ['GoodsNo', '=', $shangyi[0]['GoodsNo']]])->delete();
                                $this->puhuo_log_model::where([['WarehouseName', '=', $kuzi[0]['WarehouseName']], ['GoodsNo', '=', $kuzi[0]['GoodsNo']]])->delete();
                                $this->puhuo_cur_log_model::where([['WarehouseName', '=', $kuzi[0]['WarehouseName']], ['GoodsNo', '=', $kuzi[0]['GoodsNo']]])->delete();
                                
                                //铺货日志重新批量入库
                                $chunk_list = $shangyi ? array_chunk($shangyi, 500) : [];
                                if ($chunk_list) {
                                    foreach($chunk_list as $key => $val) {
                                        $this->db_easy->table('sp_lyp_puhuo_cur_log')->strict(false)->insertAll($val);
                                        $this->db_easy->table('sp_lyp_puhuo_log')->strict(false)->insertAll($val);
                                    }
                                }

                                $chunk_list = $kuzi ? array_chunk($kuzi, 500) : [];
                                if ($chunk_list) {
                                    foreach($chunk_list as $key => $val) {
                                        $this->db_easy->table('sp_lyp_puhuo_cur_log')->strict(false)->insertAll($val);
                                        $this->db_easy->table('sp_lyp_puhuo_log')->strict(false)->insertAll($val);
                                    }
                                }

                                //wait_goods表库存处理
                                unset($shangyi_stock['create_time']);
		                        unset($kuzi_stock['create_time']);
                                $this->puhuo_wait_goods_model::where([['WarehouseName', '=', $shangyi[0]['WarehouseName']], ['GoodsNo', '=', $shangyi[0]['GoodsNo']]])->update($shangyi_stock);
                                $this->puhuo_wait_goods_model::where([['WarehouseName', '=', $kuzi[0]['WarehouseName']], ['GoodsNo', '=', $kuzi[0]['GoodsNo']]])->update($kuzi_stock);

                                Db::commit();
                            } catch (\Exception $e) {
                                Db::rollback();
                            }

                        }


                    }

                    
                }
                

            }


        }

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
        // $zhiding_goods = $this->puhuo_zhiding_goods_model::where([])->field("concat(Yuncang, GoodsNo) as yuncang_goods, State")->group('yuncang_goods')->select();
        // $zhiding_goods = $zhiding_goods ? $zhiding_goods->toArray() : [];
        // $yuncang_goods = $zhiding_goods ? array_column($zhiding_goods, 'yuncang_goods') : [];
        // $zhiding_goods = array_combine($yuncang_goods, $zhiding_goods);

        //改为从新的 自定义铺货设置表 获取
        $zdy_yuncang_goods = $this->puhuo_zdy_set2_model::where([['pzs.if_taozhuang', '=', $this->puhuo_zdy_set2_model::IF_TAOZHUANG['not_taozhuang']]])->alias('pzs')
        ->join(['sp_lyp_puhuo_zdy_yuncang_goods2' => 'pzyg'], 'pzs.id=pzyg.set_id', 'left')
        ->field('pzs.Yuncang, pzs.Selecttype, pzs.Commonfield, pzs.rule_type, pzs.remain_store, pzs.remain_rule_type, pzs.zuhe_customer, pzs.if_zdmd, pzyg.GoodsNo, pzyg.set_id, concat(pzs.Yuncang, pzyg.GoodsNo) as yuncang_goods')->select();
        $zdy_yuncang_goods = $zdy_yuncang_goods ? $zdy_yuncang_goods->toArray() : [];
        $yuncang_goods = $zdy_yuncang_goods ? array_column($zdy_yuncang_goods, 'yuncang_goods') : [];
        $zdy_yuncang_goods = array_combine($yuncang_goods, $zdy_yuncang_goods);
        // print_r($zdy_yuncang_goods);die;

        //按云仓分组
        $res_yuncang = $res_data = [];
        if ($zdy_yuncang_goods) {
            foreach ($zdy_yuncang_goods as $v_yuncang_goods) {
                $res_yuncang[$v_yuncang_goods['Yuncang']][] = $v_yuncang_goods;
            }
            // print_r($res_yuncang);die;

            $where=[
                [['TimeCategoryName2','like','%秋%']],
                [['TimeCategoryName2','like','%冬%']],
                [
                    ['TimeCategoryName2','like','%春%'],
                    ['TimeCategoryName1','=','2024'],
                ],
                [
                    ['TimeCategoryName2','like','%夏%'],
                    ['TimeCategoryName1','=','2024'],
                ],
                [
                    ['TimeCategoryName2','=','通季'],
                    ['GoodsName','=','正统长衬'],
                ],
                [
                    ['TimeCategoryName2','=','通季'],
                    ['CategoryName2','=','正统长衬'],
                ],
                [
                    ['TimeCategoryName2','=','通季'],
                    ['CategoryName1','=','正统长衬'],
                ],
            ];

            foreach ($res_yuncang as $k_yuncang=>$v_yuncang) {
                $arr_goods = array_column($v_yuncang, 'GoodsNo');
                $res = $this->puhuo_wait_goods_model::where('WarehouseName', $k_yuncang)->where([['GoodsNo', 'in', $arr_goods]])
                    ->where(function ($query) use ($where) {
                       $query ->whereOr($where);
                    })
                    ->paginate([
                        'list_rows' => $list_rows,//每页条数
                        'page' => $this->page,//当前页
                    ]);
                $res = $res ? $res->toArray() : [];
                $res = $res ? $res['data'] : [];
                if ($res) {
                    foreach ($res as $v_res) {
                        // $res_data[] = $v_res;
                        $res_data[] = [
                            'goods' => $v_res,
                            'Selecttype' => isset($zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]) ? $zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]['Selecttype'] : 0,
                            'Commonfield' => isset($zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]) ? $zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]['Commonfield'] : '',
                            'rule_type' => isset($zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]) ? $zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]['rule_type'] : 1,
                            'remain_store' => isset($zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]) ? $zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]['remain_store'] : 2,
                            'remain_rule_type' => isset($zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]) ? $zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]['remain_rule_type'] : 0,
                            'zuhe_customer' => isset($zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]) ? $zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]['zuhe_customer'] : '',
                            'if_zdmd' => isset($zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]) ? $zdy_yuncang_goods[$v_res['WarehouseName'].$v_res['GoodsNo']]['if_zdmd'] : 1,
                        ];
                    }
                }
            }
        }
        return $res_data;

    }

    //获取套装套西铺货配置
    protected function get_wait_goods_data_taozhuang($list_rows) {

        $zdy_yuncang_goods = $this->puhuo_zdy_set2_model::where([['if_taozhuang', '=', $this->puhuo_zdy_set2_model::IF_TAOZHUANG['is_taozhuang']]])
        ->field('Yuncang, GoodsNo, Selecttype, Commonfield, rule_type, remain_store, remain_rule_type, if_taozhuang, zuhe_customer, if_zdmd')->select();
        $zdy_yuncang_goods = $zdy_yuncang_goods ? $zdy_yuncang_goods->toArray() : [];
        if ($zdy_yuncang_goods) {
            foreach ($zdy_yuncang_goods as &$v_yuncang_goods) {
                $v_yuncang_goods['goods'] = [];
                $arr_goods = $v_yuncang_goods['GoodsNo'] ? explode(' ', $v_yuncang_goods['GoodsNo']) : [];

                $res = $this->puhuo_wait_goods_model::where('WarehouseName', $v_yuncang_goods['Yuncang'])->where([['GoodsNo', 'in', $arr_goods]])->where('TimeCategoryName2', 'like', ['1'=>'%秋%', '2'=>'%冬%'], 'OR')->paginate([
                    'list_rows'=> $list_rows,//每页条数
                    'page' => $this->page,//当前页
                ]);
                $res = $res ? $res->toArray() : [];
                $res = $res ? $res['data'] : [];
                if (count($res)%2 > 0) continue;
                $v_yuncang_goods['goods'] = array_chunk($res, 2);
            }
        }
        // print_r($zdy_yuncang_goods);die;
        return $zdy_yuncang_goods;

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

    //获取大小码店数据
    protected function get_daxiao_handle() {

        $sql = 'select CONCAT(店铺名称, "", 一级分类, "", 二级分类, "", 风格, "", 一级风格, "", 季节归集  ) as merge_str, 大小码提醒 as big_small_store from cwl_daxiao_handle group by merge_str;';
        $res = $this->db_easy->Query($sql);
        $merge_str_arr = $res ? array_column($res, 'merge_str') : [];
        $res = array_combine($merge_str_arr, $res);
        return $res;

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
                case $this->cwl_daxiao_handle_model::store_type['big']: //大码店
                    $Stock_00_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_00_skcnum_big'] : 0;
                    $Stock_29_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_29_skcnum_big'] : 0;
                    $Stock_34_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_34_skcnum_big'] : 0;
                    $Stock_35_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_35_skcnum_big'] : 0;
                    $Stock_36_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_36_skcnum_big'] : 0;
                    $Stock_38_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_38_skcnum_big'] : 0;
                    $Stock_40_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_40_skcnum_big'] : 0;
                    $Stock_42_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_42_skcnum_big'] : 0;
                    break;
                case $this->cwl_daxiao_handle_model::store_type['small']: //小码店
                    $Stock_00_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_00_skcnum_small'] : 0;
                    $Stock_29_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_29_skcnum_small'] : 0;
                    $Stock_34_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_34_skcnum_small'] : 0;
                    $Stock_35_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_35_skcnum_small'] : 0;
                    $Stock_36_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_36_skcnum_small'] : 0;
                    $Stock_38_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_38_skcnum_small'] : 0;
                    $Stock_40_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_40_skcnum_small'] : 0;
                    $Stock_42_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_42_skcnum_small'] : 0;
                    break;      
                default: //正常店
                    $Stock_00_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_00_skcnum_normal'] : 0;
                    $Stock_29_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_29_skcnum_normal'] : 0;
                    $Stock_34_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_34_skcnum_normal'] : 0;
                    $Stock_35_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_35_skcnum_normal'] : 0;
                    $Stock_36_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_36_skcnum_normal'] : 0;
                    $Stock_38_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_38_skcnum_normal'] : 0;
                    $Stock_40_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_40_skcnum_normal'] : 0;
                    $Stock_42_skcnum = $daxiaoma_skcnum_info ? $daxiaoma_skcnum_info['Stock_42_skcnum_normal'] : 0;
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
            } elseif ( strpos($v_data['CategoryName2'],'套西')) {
                $lianma_num = $puhuo_config['end_puhuo_lianma_tx'];
            } elseif ($v_data['CategoryName1'] == '外套') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_wt'];
            } elseif ($v_data['CategoryName1'] == '鞋履') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_xl'];
            } elseif ($v_data['CategoryName2'] == '松紧短裤') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_sjdk'];
            } elseif ($v_data['CategoryName2'] == '松紧长裤') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_sjck'];
            } elseif ($v_data['CategoryName2'] == '羊毛裤') {
                $lianma_num = $puhuo_config['end_puhuo_lianma_ymk'];
            }  else {
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

                            // if ($v_Stock_00_sort_arr['Stock_00_skcnum_cur'] >= $v_Stock_00_sort_arr['Stock_00_skcnum']) {//该店 当前尺码已满足无须再铺
                            //     $new_daxiaoma_puhuo_log_no[] = $v_daxiaoma_puhuo_log;
                            // } else {
                            //     $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;
                            // }

                            //去掉 当前尺码 大于 已有尺码库存逻辑 20230905
                            $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;

                        }
                    }
                }
                //开始逐个店铺货
                if ($new_daxiaoma_puhuo_log) {
                    foreach ($new_daxiaoma_puhuo_log as &$v_new_daxiaoma_puhuo_log) {
    
                        if ($Stock_00 <= 0) break;
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



            if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && (strstr($v_data['CategoryName2'], '松紧') || strstr($v_data['CategoryName2'], '羊毛裤') ))) {
                
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
                // print_r([$init_puhuo_stock, count($last_daxiaoma_puhuo_log), $last_daxiaoma_puhuo_log]);die;

                //记录铺货日志：
                Log::channel('puhuo')->write('##############普通(大小码)-init_puhuo_stock:'.'##############'.json_encode(['init_puhuo_stock'=>$init_puhuo_stock]) );

                //先铺能满足连码的
                $merge_lianma_arr = [];
                $merge_not_lianma_arr = [];
                foreach ($last_daxiaoma_puhuo_log as $k_last_daxiaoma_lianma=>$v_last_daxiaoma_lianma) {
                    $key_arr = [];    
                    foreach ($v_last_daxiaoma_lianma as $kk_daxiaoma_puhuo_log=>$vv_daxiaoma_puhuo_log) {
                        if (strstr($kk_daxiaoma_puhuo_log, 'Stock') && $vv_daxiaoma_puhuo_log > 0) $key_arr[] = $this->return_which_stock_num($kk_daxiaoma_puhuo_log);
                    }

                    $pu_arr_keys = getSeriesNum($key_arr);
                    if ($pu_arr_keys) {
                        foreach ($pu_arr_keys as $k_keys=>$v_keys) {
                            if (count($v_keys) < $lianma_num) unset($pu_arr_keys[$k_keys]);
                        }
                    }
                    if (!$pu_arr_keys) {//没有连码 ，不可铺
                        $v_last_daxiaoma_lianma['is_lianma'] = 0;
                        $merge_not_lianma_arr[] = $v_last_daxiaoma_lianma;
                    } else {
                        $v_last_daxiaoma_lianma['is_lianma'] = 1;
                        $merge_lianma_arr[] = $v_last_daxiaoma_lianma;
                    }
                }
                $merge_lianma_arr = array_merge($merge_lianma_arr, $merge_not_lianma_arr);

                // //test...
                // foreach ($last_daxiaoma_puhuo_log as $v_add_puhuo_log) {
                //     if ($v_add_puhuo_log['Stock_38_puhuo'] > 0) $arr[] = $v_add_puhuo_log['Stock_38_puhuo'];
                // }
                // print_r([$arr, 7878]);die;

                //最终连码识别：
                foreach ($merge_lianma_arr as $k_last_daxiaoma_puhuo_log=>&$v_last_daxiaoma_puhuo_log) {

                    //test.....暂时测试测试
                    // if ($v_last_daxiaoma_puhuo_log['CustomerName'] != '忠县一店') continue;

                    $is_lianma = $v_last_daxiaoma_puhuo_log['is_lianma'];

                    if (!$is_lianma) {//没有连码 ，不可铺

                        //不可铺，wait_goods（主码）各个尺码库存恢复
                        if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && (strstr($v_data['CategoryName2'], '松紧') || strstr($v_data['CategoryName2'], '羊毛裤') ))) {
                
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
                        if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && (strstr($v_data['CategoryName2'], '松紧') || strstr($v_data['CategoryName2'], '羊毛裤') ))) {
                
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
					unset($v_last_daxiaoma_puhuo_log['is_lianma']);
                    $v_last_daxiaoma_puhuo_log['total'] = ($v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_29_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_30_puhuo'] + 
                    $v_last_daxiaoma_puhuo_log['Stock_31_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_32_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_33_puhuo'] + 
                    $v_last_daxiaoma_puhuo_log['Stock_34_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] + 
                    $v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] + $v_last_daxiaoma_puhuo_log['Stock_42_puhuo']) ;

                }

                //记录铺货日志：
                Log::channel('puhuo')->write('##############普通(大小码)-云仓-货号:'.$v_data['WarehouseName'].$v_data['GoodsNo'].'##############'.json_encode(['init_puhuo_stock'=>$init_puhuo_stock]) );

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

                    unset($vvv_daxiaoma_skcnum_score_sort['create_time']);
                    unset($vvv_daxiaoma_skcnum_score_sort['id']);

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

                    // if ($v_Stock_35_sort_arr['Stock_'.$each.'_skcnum_cur'] >= $v_Stock_35_sort_arr['Stock_'.$each.'_skcnum']) {//该店 当前尺码已满足无须再铺
                    //     $new_daxiaoma_puhuo_log_no[] = $v_daxiaoma_puhuo_log;
                    // } else {
                    //     $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;
                    // }

                    //去掉 当前尺码 大于 已有尺码库存逻辑 20230905
                    $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;

                }
            }
        }
        //开始逐个店铺货
        if ($new_daxiaoma_puhuo_log) {
            foreach ($new_daxiaoma_puhuo_log as &$v_new_daxiaoma_puhuo_log) {
    
                if ($each_Stock <= 0) break;
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

                    // if ($v_Stock_35_sort_arr['Stock_'.$each.'_skcnum_cur'] >= $v_Stock_35_sort_arr['Stock_'.$each.'_skcnum']) {//该店 当前尺码已满足无须再铺
                    //     $new_daxiaoma_puhuo_log_no[] = $v_daxiaoma_puhuo_log;
                    // } else {
                    //     $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;
                    // }

                    //去掉 当前尺码 大于 已有尺码库存逻辑 20230905
                    $new_daxiaoma_puhuo_log[] = $v_daxiaoma_puhuo_log;

                }
            }
        }
        // print_r([$new_daxiaoma_puhuo_log, $new_daxiaoma_puhuo_log_no]);die;
        //开始逐个店铺货
        if ($new_daxiaoma_puhuo_log) {
            foreach ($new_daxiaoma_puhuo_log as &$v_new_daxiaoma_puhuo_log) {
    
                if ($each_Stock <= 0) break;//$each_sum >= $each_Stock || 
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
            $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_29_puhuo'] > 0) {//29码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_29_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_29_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_29_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_34_puhuo'] > 0) {//34码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_34_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_34_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_34_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] > 0) {//35码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] > 0) {//36码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] > 0) {//38码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] > 0) {//40码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_42_puhuo'] > 0) {//42码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'] = $array_unique;
        }

    }

    protected function return_daxiaoma_customer_sort_xz($v_last_daxiaoma_puhuo_log, $v_data, &$vv_daxiaoma_skcnum_score_sort, $ti_type) {

        $current_goods = in_array($v_data['StyleCategoryName2'], $ti_type) ? [] : [$v_data['GoodsNo']];//剔除指定款（如果是指定款，则不能进行满足率计算）

        if ($v_last_daxiaoma_puhuo_log['Stock_00_puhuo'] > 0) {//28码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_00_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_00_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_35_puhuo'] > 0) {//35码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_35_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_35_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_36_puhuo'] > 0) {//36码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_36_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_36_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_38_puhuo'] > 0) {//38码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_38_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_38_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_40_puhuo'] > 0) {//40码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_40_skcnum'], 2) : 0;
            $vv_daxiaoma_skcnum_score_sort['Stock_40_goods_str_arr'] = $array_unique;
        }
        if ($v_last_daxiaoma_puhuo_log['Stock_42_puhuo'] > 0) {//42码
            $array_unique = array_unique(array_merge($vv_daxiaoma_skcnum_score_sort['Stock_42_goods_str_arr'], $current_goods));
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur'] = count($array_unique);
            $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_score'] = $vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum'] ? round($vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum_cur']/$vv_daxiaoma_skcnum_score_sort['Stock_42_skcnum'], 2) : 0;
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
                    'WarehouseCode' => '',
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
                    'ColorCode' => '',
                    'CustomItem17' => '',
                    'State' => '',
                    'CustomerName' => $v_data['WarehouseName'],
                    'CustomerId' => '',
                    'CustomerGrade' => '',
                    'CustomerCode' => '',
                    'Mathod' => $v_data['WarehouseName'],
                    
                    'StoreArea' => 0,
                    'xiuxian_num' => 0,
                    'score_sort' => 0,
                    'is_total' => 1,
                    'kepu_sort' => -2,
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
                    'WarehouseCode' => '',
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
                    'ColorCode' => '',
                    'CustomItem17' => '',
                    'State' => '',
                    'CustomerName' => '余量',
                    'CustomerId' => '',
                    'CustomerGrade' => '',
                    'CustomerCode' => '',
                    'Mathod' => '余量',
                    
                    'StoreArea' => 0,
                    'xiuxian_num' => 0,
                    'score_sort' => 0,
                    'is_total' => 1,
                    'kepu_sort' => -1,
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

                $tmp_arr = [];
                $cur_sort = 0;
                foreach ($ware_goods as $v_ware_goods) {
                    if ($v_ware_goods['Stock_Quantity_puhuo'] > 0) {
                        $cur_sort++;
                        $tmp_arr[$v_ware_goods['uuid']] = $cur_sort;
                    }
                }   

                foreach ($ware_goods as &$vv_ware_goods) {
                    if (isset($tmp_arr[$vv_ware_goods['uuid']])) {
                        $vv_ware_goods['kepu_sort'] = $tmp_arr[$vv_ware_goods['uuid']];
                    }
                }

                $add_data = array_merge($add_data, $ware_goods);
            }

        }
        return $add_data;

    }

    protected function get_ware_goods_sql($WarehouseName, $GoodsNo) {

        return "select lpcl.uuid
        , lpcs.Yuncang as WarehouseName
        ,CASE WHEN lpcs.Yuncang='长沙云仓' THEN 'CK006' 
			WHEN lpcs.Yuncang='武汉云仓' THEN 'CK003' 
			WHEN lpcs.Yuncang='南昌云仓' THEN 'CK002' 
			WHEN lpcs.Yuncang='广州云仓' THEN 'CK004' 
			WHEN lpcs.Yuncang='贵阳云仓' THEN 'CK005' ELSE '' END AS WarehouseCode
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
        , lpwg.ColorCode 
        , c.CustomItem17 
        , left(lpcs.State, 2) as State
        , lpcs.CustomerName
        , lpcs.CustomerId 
        , lpcs.CustomerGrade
        , c.CustomerCode
        , lpcs.Mathod
        , lpcs.StoreArea
        , lpcs.xiuxian_num 
        , lpcs.score_sort
        , 0 as is_total 
        , 0 as kepu_sort 
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
        left join customer c on lpcs.CustomerId=c.CustomerId  
        where 1 and lpwg.WarehouseName='{$WarehouseName}' and lpwg.GoodsNo='{$GoodsNo}'";

    }

    ###################最终铺货数据使用的end######################



#############################合并 puhuo_yuncangkeyong 代码进来 start###############################

    protected function puhuo_yuncangkeyong() {

        $db = Db::connect("mysql");
        $db->Query("truncate table sp_lyp_puhuo_yuncangkeyong;");
        
        $data = $this->get_kl_data();
        if ($data) {

            $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->field('warehouse_reserve_smallsize,warehouse_reserve_mainsize,warehouse_reserve_bigsize,yuliu_num')->find();
            foreach ($data as $v_data) {
                
                $v_data['Lingxing'] = $v_data['CategoryName'] ? mb_substr($v_data['CategoryName'], 0, 2) : '';
                if ($v_data['Stock_Quantity'] >= $puhuo_config['yuliu_num']) {//大于200件的才作预留

                    $warehouse_reserve_smallsize = $puhuo_config['warehouse_reserve_smallsize']/100;
                    $warehouse_reserve_mainsize = $puhuo_config['warehouse_reserve_mainsize']/100;
                    $warehouse_reserve_bigsize = $puhuo_config['warehouse_reserve_bigsize']/100;
                    if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {

                        //小码
                        $v_data['Stock_00_yuliu'] = round($warehouse_reserve_smallsize*$v_data['Stock_00'], 0);
                        $v_data['Stock_00_puhuo'] = $v_data['Stock_00']-$v_data['Stock_00_yuliu'];
                        $v_data['Stock_29_yuliu'] = round($warehouse_reserve_smallsize*$v_data['Stock_29'], 0);
                        $v_data['Stock_29_puhuo'] = $v_data['Stock_29']-$v_data['Stock_29_yuliu'];

                        //主码
                        $v_data['Stock_30_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_30'], 0);
                        $v_data['Stock_30_puhuo'] = $v_data['Stock_30']-$v_data['Stock_30_yuliu'];
                        $v_data['Stock_31_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_31'], 0);
                        $v_data['Stock_31_puhuo'] = $v_data['Stock_31']-$v_data['Stock_31_yuliu'];
                        $v_data['Stock_32_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_32'], 0);
                        $v_data['Stock_32_puhuo'] = $v_data['Stock_32']-$v_data['Stock_32_yuliu'];
                        $v_data['Stock_33_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_33'], 0);
                        $v_data['Stock_33_puhuo'] = $v_data['Stock_33']-$v_data['Stock_33_yuliu'];

                        //大码
                        $v_data['Stock_34_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_34'], 0);
                        $v_data['Stock_34_puhuo'] = $v_data['Stock_34']-$v_data['Stock_34_yuliu'];
                        $v_data['Stock_35_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_35'], 0);
                        $v_data['Stock_35_puhuo'] = $v_data['Stock_35']-$v_data['Stock_35_yuliu'];

                        $v_data['Stock_Quantity_yuliu'] = $v_data['Stock_00_yuliu'] + $v_data['Stock_29_yuliu'] + $v_data['Stock_30_yuliu'] + $v_data['Stock_31_yuliu'] + $v_data['Stock_32_yuliu'] + $v_data['Stock_33_yuliu'] + $v_data['Stock_34_yuliu'] + $v_data['Stock_35_yuliu'];

                    } else {

                        //小码
                        $v_data['Stock_00_yuliu'] = round($warehouse_reserve_smallsize*$v_data['Stock_00'], 0);
                        $v_data['Stock_00_puhuo'] = $v_data['Stock_00']-$v_data['Stock_00_yuliu'];
                        
                        //主码
                        $v_data['Stock_29_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_29'], 0);
                        $v_data['Stock_29_puhuo'] = $v_data['Stock_29']-$v_data['Stock_29_yuliu'];
                        $v_data['Stock_30_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_30'], 0);
                        $v_data['Stock_30_puhuo'] = $v_data['Stock_30']-$v_data['Stock_30_yuliu'];
                        $v_data['Stock_31_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_31'], 0);
                        $v_data['Stock_31_puhuo'] = $v_data['Stock_31']-$v_data['Stock_31_yuliu'];
                        $v_data['Stock_32_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_32'], 0);
                        $v_data['Stock_32_puhuo'] = $v_data['Stock_32']-$v_data['Stock_32_yuliu'];
                        $v_data['Stock_33_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_33'], 0);
                        $v_data['Stock_33_puhuo'] = $v_data['Stock_33']-$v_data['Stock_33_yuliu'];
                        $v_data['Stock_34_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_34'], 0);
                        $v_data['Stock_34_puhuo'] = $v_data['Stock_34']-$v_data['Stock_34_yuliu'];

                        //大码
                        $v_data['Stock_35_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_35'], 0);
                        $v_data['Stock_35_puhuo'] = $v_data['Stock_35']-$v_data['Stock_35_yuliu'];
                        $v_data['Stock_36_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_36'], 0);
                        $v_data['Stock_36_puhuo'] = $v_data['Stock_36']-$v_data['Stock_36_yuliu'];
                        $v_data['Stock_38_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_38'], 0);
                        $v_data['Stock_38_puhuo'] = $v_data['Stock_38']-$v_data['Stock_38_yuliu'];
                        $v_data['Stock_40_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_40'], 0);
                        $v_data['Stock_40_puhuo'] = $v_data['Stock_40']-$v_data['Stock_40_yuliu'];
                        $v_data['Stock_42_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_42'], 0);
                        $v_data['Stock_42_puhuo'] = $v_data['Stock_42']-$v_data['Stock_42_yuliu'];
                        
                        $v_data['Stock_Quantity_yuliu'] = $v_data['Stock_00_yuliu'] + $v_data['Stock_29_yuliu'] + $v_data['Stock_30_yuliu'] + $v_data['Stock_31_yuliu'] + $v_data['Stock_32_yuliu'] + $v_data['Stock_33_yuliu'] + $v_data['Stock_34_yuliu'] + $v_data['Stock_35_yuliu'] + $v_data['Stock_36_yuliu'] + $v_data['Stock_38_yuliu'] + $v_data['Stock_40_yuliu'] + $v_data['Stock_42_yuliu'];
                    }

                    $v_data['Stock_Quantity_puhuo'] = $v_data['Stock_Quantity']-$v_data['Stock_Quantity_yuliu'];

                } else {//小于200件的，全铺
                    $v_data['Stock_00_puhuo'] = $v_data['Stock_00'];
                    $v_data['Stock_29_puhuo'] = $v_data['Stock_29'];
                    $v_data['Stock_30_puhuo'] = $v_data['Stock_30'];
                    $v_data['Stock_31_puhuo'] = $v_data['Stock_31'];
                    $v_data['Stock_32_puhuo'] = $v_data['Stock_32'];
                    $v_data['Stock_33_puhuo'] = $v_data['Stock_33'];
                    $v_data['Stock_34_puhuo'] = $v_data['Stock_34'];
                    $v_data['Stock_35_puhuo'] = $v_data['Stock_35'];
                    $v_data['Stock_36_puhuo'] = $v_data['Stock_36'];
                    $v_data['Stock_38_puhuo'] = $v_data['Stock_38'];
                    $v_data['Stock_40_puhuo'] = $v_data['Stock_40'];
                    $v_data['Stock_42_puhuo'] = $v_data['Stock_42'];
                    $v_data['Stock_Quantity_puhuo'] = $v_data['Stock_Quantity'];
                }
                // print_r($v_data);die;
                SpLypPuhuoYuncangkeyongModel::create($v_data);

            }
            
            //生成铺货 货品数据
            $data = $this->get_wait_goods_data_ycky();

            $chunk_list = $data ? array_chunk($data, 500) : [];
            if ($chunk_list) {
                Db::startTrans();
                try {
                    //先清空旧数据再跑
                    $db->Query("truncate table sp_lyp_puhuo_wait_goods;");
                    foreach($chunk_list as $key => $val) {
                        $insert = $db->table('sp_lyp_puhuo_wait_goods')->strict(false)->insertAll($val);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }


        }

    }

    protected function puhuo_yuncangkeyong2() {

        $db = Db::connect("mysql");
        $db->Query("truncate table sp_lyp_puhuo_yuncangkeyong;");
        
        $data = $this->get_kl_data();
        if ($data) {

            $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->field('yuliu_num')->find();
            $warehouse_reserve_config = SpLypPuhuoWarehouseReserveConfigModel::where([])->column('*', 'config_str');
            $warehouse_reserve_goods = $db->Query("select concat(g.config_str, g.GoodsNo) as ware_goods, g.* from sp_lyp_puhuo_warehouse_reserve_goods g group by ware_goods;");
            $ware_goods = array_column($warehouse_reserve_goods, 'ware_goods');
            $warehouse_reserve_goods = array_combine($ware_goods, $warehouse_reserve_goods);
            // print_r($warehouse_reserve_config);die;

            $res_data = [];

            //对新数组真实尺码进行处理
            $tmp_res_data = $new_res_data = [];
            foreach ($data as $v_data) {
                $tmp_res_data[$v_data['WarehouseName'].$v_data['GoodsNo']][] = $v_data;
            }

            foreach ($tmp_res_data as $v_tmp_res_data) {

                if (!$v_tmp_res_data) {
                    continue;
                }
                $Stock_00 = $Stock_29 = $Stock_30 = $Stock_31 = $Stock_32 = $Stock_33 = $Stock_34 = $Stock_35 = $Stock_36 = $Stock_38 = $Stock_40 = $Stock_42 = $Stock_Quantity = 0;

                $ViewOrder_arr = [];
                foreach ($v_tmp_res_data as $vv_tmp_res_data) {
                    $Stock_00 += $vv_tmp_res_data['Stock_00'];
                    $Stock_29 += $vv_tmp_res_data['Stock_29'];
                    $Stock_30 += $vv_tmp_res_data['Stock_30'];
                    $Stock_31 += $vv_tmp_res_data['Stock_31'];
                    $Stock_32 += $vv_tmp_res_data['Stock_32'];
                    $Stock_33 += $vv_tmp_res_data['Stock_33'];
                    $Stock_34 += $vv_tmp_res_data['Stock_34'];
                    $Stock_35 += $vv_tmp_res_data['Stock_35'];
                    $Stock_36 += $vv_tmp_res_data['Stock_36'];
                    $Stock_38 += $vv_tmp_res_data['Stock_38'];
                    $Stock_40 += $vv_tmp_res_data['Stock_40'];
                    $Stock_42 += $vv_tmp_res_data['Stock_42'];
                    $Stock_Quantity += $vv_tmp_res_data['Stock_Quantity'];
                    $ViewOrder_arr[$vv_tmp_res_data['ViewOrder']] = $vv_tmp_res_data['Size'];
                }
                // print_r([$v_tmp_res_data,  $ViewOrder_arr]);die;

                $arr = [1=>$Stock_00, 2=>$Stock_29, 3=>$Stock_30, 4=>$Stock_31, 5=>$Stock_32, 6=>$Stock_33, 7=>$Stock_34, 8=>$Stock_35, 9=>$Stock_36, 10=>$Stock_38
                , 11=>$Stock_29, 12=>$Stock_42
                ];
                $arr2 = []; 
                foreach ($arr as $k_arr=>$v_arr) {
                    if ($v_arr > 0) $arr2[] = $k_arr;
                }
                //连码计算
                $seri_arr = getSeriesNum($arr2);
                $lianma_arr = [];
                if ($seri_arr) {
                    foreach ($seri_arr as $v_seri) {
                        $lianma_arr[] = count($v_seri);
                    }
                }

                $v_data = $v_tmp_res_data[0];

                $tmp_data = [
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
                    'Lingxing' => $v_data['CategoryName'] ? mb_substr($v_data['CategoryName'], 0, 2) : '',
                    'UnitPrice' => $v_data['UnitPrice'],
                    'ColorDesc' => $v_data['ColorDesc'],
                    'ColorCode' => $v_data['ColorCode'],
                    
                    'Stock_00' => $Stock_00,
                    'Stock_00_yuliu' => 0,
                    'Stock_00_puhuo' => 0,
                    'Stock_00_size' => $ViewOrder_arr[1] ?? '',//($v_data['ViewOrder']==1) ? $v_data['Size'] : '',

                    'Stock_29' => $Stock_29,
                    'Stock_29_yuliu' => 0,
                    'Stock_29_puhuo' => 0,
                    'Stock_29_size' => $ViewOrder_arr[2] ?? '',

                    'Stock_30' => $Stock_30,
                    'Stock_30_yuliu' => 0,
                    'Stock_30_puhuo' => 0,
                    'Stock_30_size' => $ViewOrder_arr[3] ?? '',

                    'Stock_31' => $Stock_31,
                    'Stock_31_yuliu' => 0,
                    'Stock_31_puhuo' => 0,
                    'Stock_31_size' => $ViewOrder_arr[4] ?? '',

                    'Stock_32' => $Stock_32,
                    'Stock_32_yuliu' => 0,
                    'Stock_32_puhuo' => 0,
                    'Stock_32_size' => $ViewOrder_arr[5] ?? '',

                    'Stock_33' => $Stock_33,
                    'Stock_33_yuliu' => 0,
                    'Stock_33_puhuo' => 0,
                    'Stock_33_size' => $ViewOrder_arr[6] ?? '',

                    'Stock_34' => $Stock_34,
                    'Stock_34_yuliu' => 0,
                    'Stock_34_puhuo' => 0,
                    'Stock_34_size' => $ViewOrder_arr[7] ?? '',

                    'Stock_35' => $Stock_35,
                    'Stock_35_yuliu' => 0,
                    'Stock_35_puhuo' => 0,
                    'Stock_35_size' => $ViewOrder_arr[8] ?? '',

                    'Stock_36' => $Stock_36,
                    'Stock_36_yuliu' => 0,
                    'Stock_36_puhuo' => 0,
                    'Stock_36_size' => $ViewOrder_arr[9] ?? '',

                    'Stock_38' => $Stock_38,
                    'Stock_38_yuliu' => 0,
                    'Stock_38_puhuo' => 0,
                    'Stock_38_size' => $ViewOrder_arr[10] ?? '',

                    'Stock_40' => $Stock_40,
                    'Stock_40_yuliu' => 0,
                    'Stock_40_puhuo' => 0,
                    'Stock_40_size' => $ViewOrder_arr[11] ?? '',

                    'Stock_42' => $Stock_42,
                    'Stock_42_yuliu' => 0,
                    'Stock_42_puhuo' => 0,
                    'Stock_42_size' => $ViewOrder_arr[12] ?? '',

                    'Stock_Quantity' => $Stock_Quantity,
                    'Stock_Quantity_yuliu' => 0,
                    'Stock_Quantity_puhuo' => 0,
                    
                    'qima' => $lianma_arr ? max($lianma_arr) : 1,
                ];
                
                // $v_data['Lingxing'] = $v_data['CategoryName'] ? mb_substr($v_data['CategoryName'], 0, 2) : '';
                if ($tmp_data['Stock_Quantity'] >= $puhuo_config['yuliu_num']) {//大于200件的才作预留

                    $each_goods_config = isset($warehouse_reserve_goods[$v_data['WarehouseName'].$v_data['GoodsNo']]) ? $warehouse_reserve_goods[$v_data['WarehouseName'].$v_data['GoodsNo']] : (isset($warehouse_reserve_config[$v_data['WarehouseName']]) ? $warehouse_reserve_config[$v_data['WarehouseName']] : []);

                    if ($each_goods_config) {
                        $each_goods_config['_28'] = $each_goods_config['_28']/100;
                        $each_goods_config['_29'] = $each_goods_config['_29']/100;
                        $each_goods_config['_30'] = $each_goods_config['_30']/100;
                        $each_goods_config['_31'] = $each_goods_config['_31']/100;
                        $each_goods_config['_32'] = $each_goods_config['_32']/100;
                        $each_goods_config['_33'] = $each_goods_config['_33']/100;
                        $each_goods_config['_34'] = $each_goods_config['_34']/100;
                        $each_goods_config['_35'] = $each_goods_config['_35']/100;
                        $each_goods_config['_36'] = $each_goods_config['_36']/100;
                        $each_goods_config['_38'] = $each_goods_config['_38']/100;
                        $each_goods_config['_40'] = $each_goods_config['_40']/100;
                        $each_goods_config['_42'] = $each_goods_config['_42']/100;
                    }

                    if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {

                        //小码
                        $tmp_data['Stock_00_yuliu'] = $each_goods_config ? round($each_goods_config['_28']*$tmp_data['Stock_00'], 0) : 0;
                        $tmp_data['Stock_00_puhuo'] = $tmp_data['Stock_00']-$tmp_data['Stock_00_yuliu'];
                        $tmp_data['Stock_29_yuliu'] = $each_goods_config ? round($each_goods_config['_29']*$tmp_data['Stock_29'], 0) : 0;
                        $tmp_data['Stock_29_puhuo'] = $tmp_data['Stock_29']-$tmp_data['Stock_29_yuliu'];

                        //主码
                        $tmp_data['Stock_30_yuliu'] = $each_goods_config ? round($each_goods_config['_30']*$tmp_data['Stock_30'], 0) : 0;
                        $tmp_data['Stock_30_puhuo'] = $tmp_data['Stock_30']-$tmp_data['Stock_30_yuliu'];
                        $tmp_data['Stock_31_yuliu'] = $each_goods_config ? round($each_goods_config['_31']*$tmp_data['Stock_31'], 0) : 0;
                        $tmp_data['Stock_31_puhuo'] = $tmp_data['Stock_31']-$tmp_data['Stock_31_yuliu'];
                        $tmp_data['Stock_32_yuliu'] = $each_goods_config ? round($each_goods_config['_32']*$tmp_data['Stock_32'], 0) : 0;
                        $tmp_data['Stock_32_puhuo'] = $tmp_data['Stock_32']-$tmp_data['Stock_32_yuliu'];
                        $tmp_data['Stock_33_yuliu'] = $each_goods_config ? round($each_goods_config['_33']*$tmp_data['Stock_33'], 0) : 0;
                        $tmp_data['Stock_33_puhuo'] = $tmp_data['Stock_33']-$tmp_data['Stock_33_yuliu'];

                        //大码
                        $tmp_data['Stock_34_yuliu'] = $each_goods_config ? round($each_goods_config['_34']*$tmp_data['Stock_34'], 0) : 0;
                        $tmp_data['Stock_34_puhuo'] = $tmp_data['Stock_34']-$tmp_data['Stock_34_yuliu'];
                        $tmp_data['Stock_35_yuliu'] = $each_goods_config ? round($each_goods_config['_35']*$tmp_data['Stock_35'], 0) : 0;
                        $tmp_data['Stock_35_puhuo'] = $tmp_data['Stock_35']-$tmp_data['Stock_35_yuliu'];

                        $tmp_data['Stock_Quantity_yuliu'] = $tmp_data['Stock_00_yuliu'] + $tmp_data['Stock_29_yuliu'] + $tmp_data['Stock_30_yuliu'] + $tmp_data['Stock_31_yuliu'] + $tmp_data['Stock_32_yuliu'] + $tmp_data['Stock_33_yuliu'] + $tmp_data['Stock_34_yuliu'] + $tmp_data['Stock_35_yuliu'];

                    } else {

                        //小码
                        $tmp_data['Stock_00_yuliu'] = $each_goods_config ? round($each_goods_config['_28']*$tmp_data['Stock_00'], 0) : 0;
                        $tmp_data['Stock_00_puhuo'] = $tmp_data['Stock_00']-$tmp_data['Stock_00_yuliu'];
                        
                        //主码
                        $tmp_data['Stock_29_yuliu'] = $each_goods_config ? round($each_goods_config['_29']*$tmp_data['Stock_29'], 0) : 0;
                        $tmp_data['Stock_29_puhuo'] = $tmp_data['Stock_29']-$tmp_data['Stock_29_yuliu'];
                        $tmp_data['Stock_30_yuliu'] = $each_goods_config ? round($each_goods_config['_30']*$tmp_data['Stock_30'], 0) : 0;
                        $tmp_data['Stock_30_puhuo'] = $tmp_data['Stock_30']-$tmp_data['Stock_30_yuliu'];
                        $tmp_data['Stock_31_yuliu'] = $each_goods_config ? round($each_goods_config['_31']*$tmp_data['Stock_31'], 0) : 0;
                        $tmp_data['Stock_31_puhuo'] = $tmp_data['Stock_31']-$tmp_data['Stock_31_yuliu'];
                        $tmp_data['Stock_32_yuliu'] = $each_goods_config ? round($each_goods_config['_32']*$tmp_data['Stock_32'], 0) : 0;
                        $tmp_data['Stock_32_puhuo'] = $tmp_data['Stock_32']-$tmp_data['Stock_32_yuliu'];
                        $tmp_data['Stock_33_yuliu'] = $each_goods_config ? round($each_goods_config['_33']*$tmp_data['Stock_33'], 0) : 0;
                        $tmp_data['Stock_33_puhuo'] = $tmp_data['Stock_33']-$tmp_data['Stock_33_yuliu'];
                        $tmp_data['Stock_34_yuliu'] = $each_goods_config ? round($each_goods_config['_34']*$tmp_data['Stock_34'], 0) : 0;
                        $tmp_data['Stock_34_puhuo'] = $tmp_data['Stock_34']-$tmp_data['Stock_34_yuliu'];

                        //大码
                        $tmp_data['Stock_35_yuliu'] = $each_goods_config ? round($each_goods_config['_35']*$tmp_data['Stock_35'], 0) : 0;
                        $tmp_data['Stock_35_puhuo'] = $tmp_data['Stock_35']-$tmp_data['Stock_35_yuliu'];
                        $tmp_data['Stock_36_yuliu'] = $each_goods_config ? round($each_goods_config['_36']*$tmp_data['Stock_36'], 0) : 0;
                        $tmp_data['Stock_36_puhuo'] = $tmp_data['Stock_36']-$tmp_data['Stock_36_yuliu'];
                        $tmp_data['Stock_38_yuliu'] = $each_goods_config ? round($each_goods_config['_38']*$tmp_data['Stock_38'], 0) : 0;
                        $tmp_data['Stock_38_puhuo'] = $tmp_data['Stock_38']-$tmp_data['Stock_38_yuliu'];
                        $tmp_data['Stock_40_yuliu'] = $each_goods_config ? round($each_goods_config['_40']*$tmp_data['Stock_40'], 0) : 0;
                        $tmp_data['Stock_40_puhuo'] = $tmp_data['Stock_40']-$tmp_data['Stock_40_yuliu'];
                        $tmp_data['Stock_42_yuliu'] = $each_goods_config ? round($each_goods_config['_42']*$tmp_data['Stock_42'], 0) : 0;
                        $tmp_data['Stock_42_puhuo'] = $tmp_data['Stock_42']-$tmp_data['Stock_42_yuliu'];
                        
                        $tmp_data['Stock_Quantity_yuliu'] = $tmp_data['Stock_00_yuliu'] + $tmp_data['Stock_29_yuliu'] + $tmp_data['Stock_30_yuliu'] + $tmp_data['Stock_31_yuliu'] + $tmp_data['Stock_32_yuliu'] + $tmp_data['Stock_33_yuliu'] + $tmp_data['Stock_34_yuliu'] + $tmp_data['Stock_35_yuliu'] + $tmp_data['Stock_36_yuliu'] + $tmp_data['Stock_38_yuliu'] + $tmp_data['Stock_40_yuliu'] + $tmp_data['Stock_42_yuliu'];
                    }

                    $tmp_data['Stock_Quantity_puhuo'] = $tmp_data['Stock_Quantity']-$tmp_data['Stock_Quantity_yuliu'];

                } else {//小于200件的，全铺
                    $tmp_data['Stock_00_puhuo'] = $tmp_data['Stock_00'];
                    $tmp_data['Stock_29_puhuo'] = $tmp_data['Stock_29'];
                    $tmp_data['Stock_30_puhuo'] = $tmp_data['Stock_30'];
                    $tmp_data['Stock_31_puhuo'] = $tmp_data['Stock_31'];
                    $tmp_data['Stock_32_puhuo'] = $tmp_data['Stock_32'];
                    $tmp_data['Stock_33_puhuo'] = $tmp_data['Stock_33'];
                    $tmp_data['Stock_34_puhuo'] = $tmp_data['Stock_34'];
                    $tmp_data['Stock_35_puhuo'] = $tmp_data['Stock_35'];
                    $tmp_data['Stock_36_puhuo'] = $tmp_data['Stock_36'];
                    $tmp_data['Stock_38_puhuo'] = $tmp_data['Stock_38'];
                    $tmp_data['Stock_40_puhuo'] = $tmp_data['Stock_40'];
                    $tmp_data['Stock_42_puhuo'] = $tmp_data['Stock_42'];
                    $tmp_data['Stock_Quantity_puhuo'] = $tmp_data['Stock_Quantity'];
                }
                // print_r($tmp_data);die;
                // SpLypPuhuoYuncangkeyongModel::create($tmp_data);

                $res_data[] = $tmp_data;

            }
            // print_r($res_data);die;

            //批量入库
            $chunk_list = $data ? array_chunk($res_data, 500) : [];
            if ($chunk_list) {
                foreach($chunk_list as $key => $val) {
                    $insert = $db->table('sp_lyp_puhuo_yuncangkeyong')->strict(false)->insertAll($val);
                }
            }
            // print_r($res_data);die;
            
            //生成铺货 货品数据
            $data = $this->get_wait_goods_data_ycky();

            $chunk_list = $data ? array_chunk($data, 500) : [];
            if ($chunk_list) {
                Db::startTrans();
                try {
                    //先清空旧数据再跑
                    $db->Query("truncate table sp_lyp_puhuo_wait_goods;");
                    foreach($chunk_list as $key => $val) {
                        $insert = $db->table('sp_lyp_puhuo_wait_goods')->strict(false)->insertAll($val);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    dd($e->getMessage());
                }
            }


        }

    }

    protected function get_kl_data() {

            $sql = "SELECT 

            T.WarehouseName,
        
            -- EG.GoodsNo,
        
            EG.TimeCategoryName1,
        
            EG.TimeCategoryName2,
        
            EG.CategoryName1,
        
            EG.CategoryName2,
        
            EG.CategoryName,
        
            EG.GoodsName,
        
            EG.StyleCategoryName,
        
            EG.GoodsNo,
        
            EG.StyleCategoryName1,
        
            EG.StyleCategoryName2,
            
						EGPT.UnitPrice,
						
            EGC.ColorDesc,
            
            EGC.ColorCode,
						
						EBGS.ViewOrder,
						
						EBGS.Size,
        
            SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END) AS [Stock_00],
        
            SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0 END) AS [Stock_29],
        
            SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0 END) AS [Stock_30],
        
            SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0 END) AS [Stock_31],
        
            SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0 END) AS [Stock_32],
        
            SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0 END) AS [Stock_33],
        
            SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0 END) AS [Stock_34],
        
            SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0 END) AS [Stock_35],
        
            SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0 END) AS [Stock_36],
        
            SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END) AS [Stock_38],
        
            SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END) AS [Stock_40],
            
            SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END) AS [Stock_42],
        
            SUM(T.Quantity) AS Stock_Quantity,
        
            CASE WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%111111111111%' THEN 12 
            
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%11111111111%' THEN 11 
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%1111111111%' THEN 10 
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%111111111%' THEN 9
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%11111111%' THEN 8
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%1111111%' THEN 7
        
                WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%111111%' THEN 6
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%11111%' THEN 5
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%1111%' THEN 4
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%111%' THEN 3
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%11%' THEN 2
        
                    WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
        
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                        CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                        
                        ) LIKE '%1%' THEN 1
        
                    ELSE 0
        
                END AS qima 
        
        FROM 
        
        (
        
        SELECT 
        
            EW.WarehouseName,
        
            EWS.GoodsId,
        
            EWSD.SizeId,
        
            SUM(EWSD.Quantity) AS Quantity
        
        FROM ErpWarehouseStock EWS
        
        LEFT JOIN ErpWarehouseStockDetail EWSD ON EWS.StockId=EWSD.StockId
        
        LEFT JOIN ErpWarehouse EW ON EWS.WarehouseId=EW.WarehouseId
        
        LEFT JOIN ErpGoods EG ON EWS.GoodsId=EG.GoodsId
        
        WHERE EG.TimeCategoryName1>2022
        
            AND EG.CategoryName1 NOT IN ('物料','人事物料')
        
            AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
        
        GROUP BY  
        
            EW.WarehouseName,
        
            EWS.GoodsId,
        
            EWSD.SizeId
        
        
        
        UNION ALL 
        
        --出货指令单占用库存
        
        SELECT
        
            EW.WarehouseName,
        
            ESG.GoodsId,
        
            ESGD.SizeId,
        
            -SUM ( ESGD.Quantity ) AS SumQuantity
        
        FROM ErpSorting ES
        
        LEFT JOIN ErpSortingGoods ESG ON ES.SortingID= ESG.SortingID
        
        LEFT JOIN ErpSortingGoodsDetail ESGD ON ESG.SortingGoodsID=ESGD.SortingGoodsID
        
        LEFT JOIN ErpWarehouse EW ON ES.WarehouseId=EW.WarehouseId
        
        LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
        
        WHERE	 ES.IsCompleted= 0   
        
            AND EG.TimeCategoryName1>2022
        
            AND EG.CategoryName1 NOT IN ('物料','人事物料')
        
            AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
        
        GROUP BY
        
            EW.WarehouseName,
        
            ESG.GoodsId,
        
            ESGD.SizeId
        
        
        
        UNION ALL
        
            --仓库出货单占用库存
        
        SELECT
        
            EW.WarehouseName,
        
            EDG.GoodsId,
        
            EDGD.SizeId,
        
            -SUM ( EDGD.Quantity ) AS SumQuantity
        
        FROM ErpDelivery ED
        
        LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID= EDG.DeliveryID
        
        LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
        
        LEFT JOIN ErpWarehouse EW ON ED.WarehouseId=EW.WarehouseId
        
        LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
        
        WHERE ED.CodingCode= 'StartNode1' 
        
            AND EDG.SortingID IS NULL
        
            AND EG.TimeCategoryName1>2022
        
            AND EG.CategoryName1 NOT IN ('物料','人事物料')
        
            AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
        
        GROUP BY
        
            EW.WarehouseName,
        
            EDG.GoodsId,
        
            EDGD.SizeId
        
        
        
        UNION ALL
        
            --采购退货指令单占用库存
        
        SELECT
        
            EW.WarehouseName,
        
            EPRNG.GoodsId,
        
            EPRNGD.SizeId,
        
            -SUM ( EPRNGD.Quantity ) AS SumQuantity
        
        FROM ErpPuReturnNotice EPRN
        
        LEFT JOIN ErpPuReturnNoticeGoods EPRNG ON EPRN.PuReturnNoticeId= EPRNG.PuReturnNoticeId
        
        LEFT JOIN ErpPuReturnNoticeGoodsDetail EPRNGD ON EPRNG.PuReturnNoticeGoodsId=EPRNGD.PuReturnNoticeGoodsId
        
        LEFT JOIN ErpWarehouse EW ON EPRN.WarehouseId=EW.WarehouseId
        
        LEFT JOIN ErpGoods EG ON EPRNG.GoodsId=EG.GoodsId
        
        WHERE (EPRN.IsCompleted = 0 OR EPRN.IsCompleted IS NULL) 
        
            AND EG.TimeCategoryName1>2022
        
            AND EG.CategoryName1 NOT IN ('物料','人事物料')
        
            AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
        
        GROUP BY
        
            EW.WarehouseName,
        
            EPRNG.GoodsId,
        
            EPRNGD.SizeId
        
        
        
        UNION ALL
        
            --采购退货单占用库存
        
        SELECT
        
            EW.WarehouseName,
        
            EPCRG.GoodsId,
        
            EPCRGD.SizeId,
        
            -SUM ( EPCRGD.Quantity ) AS SumQuantity
        
        FROM ErpPurchaseReturn EPCR
        
        LEFT JOIN ErpPurchaseReturnGoods EPCRG ON EPCR.PurchaseReturnId= EPCRG.PurchaseReturnId
        
        LEFT JOIN ErpPurchaseReturnGoodsDetail EPCRGD ON EPCRG.PurchaseReturnGoodsId=EPCRGD.PurchaseReturnGoodsId
        
        LEFT JOIN ErpWarehouse EW ON EPCR.WarehouseId=EW.WarehouseId
        
        LEFT JOIN ErpGoods EG ON EPCRG.GoodsId=EG.GoodsId
        
        WHERE EPCR.CodingCode= 'StartNode1'
        
            AND EG.TimeCategoryName1>2022
        
            AND EG.CategoryName1 NOT IN ('物料','人事物料')
        
            AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
        
        GROUP BY
        
            EW.WarehouseName,
        
            EPCRG.GoodsId,
        
            EPCRGD.SizeId
        
        
        
        UNION ALL
        
            --仓库调拨占用库存
        
        SELECT
        
            EW.WarehouseName,
        
            EIG.GoodsId,
        
            EIGD.SizeId,
        
            -SUM ( EIGD.Quantity ) AS SumQuantity
        
        FROM ErpInstruction EI
        
        LEFT JOIN ErpInstructionGoods EIG ON EI.InstructionId= EIG.InstructionId
        
        LEFT JOIN ErpInstructionGoodsDetail EIGD ON EIG.InstructionGoodsId=EIGD.InstructionGoodsId
        
        LEFT JOIN ErpWarehouse EW ON EI.OutItemId=EW.WarehouseId
        
        LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
        
        WHERE EI.Type= 1
        
            AND (EI.CodingCode= 'StartNode1' OR (EI.CodingCode= 'EndNode2' AND EI.IsCompleted=0 ))
        
            AND EG.TimeCategoryName1>2022
        
            AND EG.CategoryName1 NOT IN ('物料','人事物料')
        
            AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
        
        GROUP BY
        
            EW.WarehouseName,
        
            EIG.GoodsId,
        
            EIGD.SizeId
        
        
        
        ) T
        
        LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId 
        
        LEFT JOIN ErpGoods EG ON T.GoodsId=EG.GoodsId 
        
        LEFT JOIN ErpGoodsColor EGC ON EG.GoodsId=EGC.GoodsId   
        
        LEFT JOIN (SELECT 
                                        EGPT.GoodsId, 
                                        SUM(CASE WHEN EGPT.PriceName='零售价' THEN EGPT.UnitPrice ELSE NULL END) AS UnitPrice,
                                        SUM(CASE WHEN EGPT.PriceName='成本价' THEN EGPT.UnitPrice ELSE NULL END) AS CostPrice
                                    FROM ErpGoodsPriceType EGPT
                                    GROUP BY EGPT.GoodsId ) EGPT ON EG.GoodsId=EGPT.GoodsId 
        
        GROUP BY 
        
            T.WarehouseName,
        
            EG.GoodsNo,
        
            EG.TimeCategoryName1,
        
            EG.TimeCategoryName2,
        
            EG.CategoryName1,
        
            EG.CategoryName2,
        
            EG.CategoryName,
        
            EG.GoodsName,
        
            EG.StyleCategoryName,
        
            EG.GoodsNo,
        
            EG.StyleCategoryName1,
        
            EG.StyleCategoryName2,
            
            EGC.ColorDesc,
						
						EBGS.ViewOrder,
						
						EBGS.SizeId,
						
						EBGS.Size,
						
						EGC.ColorCode,
            
            EGPT.UnitPrice 
        HAVING  SUM(T.Quantity) >0
        
        ;";

            return Db::connect("sqlsrv")->Query($sql);

    }

    //可以铺货的货品数据
    protected function get_wait_goods_data_ycky() {

        $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();
        $warehouse_qima_nd = $puhuo_config ? $puhuo_config['warehouse_qima_nd'] : 0;
        $warehouse_qima_xz = $puhuo_config ? $puhuo_config['warehouse_qima_xz'] : 0;
        $store_puhuo_lianma_nd = $puhuo_config ? $puhuo_config['store_puhuo_lianma_nd'] : 0;//中间码连码个数 （有可能为：2，3，4）
        $store_puhuo_lianma_xz = $puhuo_config ? $puhuo_config['store_puhuo_lianma_xz'] : 0;//中间码连码个数（有可能为：2，3，4，5，6）

        $sql_store_puhuo_lianma_nd = $sql_store_puhuo_lianma_xz = '';
        //内搭、外套、鞋履、松紧裤
        if ($store_puhuo_lianma_nd == 2) {//中间码，2连码

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0) or  (Stock_32_puhuo >0 and Stock_33_puhuo >0) )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0) or  (Stock_32_puhuo >0 and Stock_33_puhuo >0) )) ";

        } elseif ($store_puhuo_lianma_nd == 3) {

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) ";

        } elseif ($store_puhuo_lianma_nd == 4) {

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0)  )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) ) ";

        } else {//其他默认使用3 

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) ";

        }

        //下装
        if ($store_puhuo_lianma_xz == 2) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( 
                (Stock_29_puhuo >0 and Stock_30_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0) or 
                (Stock_31_puhuo >0 and Stock_32_puhuo >0) or 
                (Stock_32_puhuo >0 and Stock_33_puhuo >0) or   
                (Stock_33_puhuo >0 and Stock_34_puhuo >0)   
                )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 3) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( 
                (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0) or 
                (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0)  
                )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 4) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( 
                (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0)  
                )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 5) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0) )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 6) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0) )
                )  ";

        } else {//其他默认使用5

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0) )
                )  ";

        }

        $sql = "select WarehouseName,TimeCategoryName1,TimeCategoryName2,CategoryName1,CategoryName2, CategoryName, GoodsName, StyleCategoryName, GoodsNo, StyleCategoryName1, StyleCategoryName2, Lingxing, UnitPrice, ColorDesc, ColorCode, Stock_00_puhuo, Stock_00_puhuo as Stock_00, Stock_00_size, Stock_29_puhuo, Stock_29_puhuo as Stock_29, Stock_29_size, Stock_30_puhuo, Stock_30_puhuo as Stock_30, Stock_30_size, Stock_31_puhuo, Stock_31_puhuo as Stock_31, Stock_31_size, Stock_32_puhuo, Stock_32_puhuo as Stock_32, Stock_32_size, Stock_33_puhuo, Stock_33_puhuo as Stock_33, Stock_33_size, Stock_34_puhuo, Stock_34_puhuo as Stock_34, Stock_34_size, Stock_35_puhuo, Stock_35_puhuo as Stock_35, Stock_35_size, Stock_36_puhuo, Stock_36_puhuo as Stock_36, Stock_36_size, Stock_38_puhuo, Stock_38_puhuo as Stock_38, Stock_38_size, Stock_40_puhuo, Stock_40_puhuo as Stock_40, Stock_40_size, Stock_42_puhuo, Stock_42_puhuo as Stock_42, Stock_42_size, Stock_Quantity_puhuo, Stock_Quantity_puhuo as Stock_Quantity, qima, (case when 
        (CategoryName1 in ('内搭', '外套', '鞋履') and qima>=$warehouse_qima_nd) 
        or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and qima>=$warehouse_qima_nd) 
        or (CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and qima>=$warehouse_qima_xz)  

        $sql_store_puhuo_lianma_nd 

        $sql_store_puhuo_lianma_xz 

        then 1 else 0 end) as can_puhuo from sp_lyp_puhuo_yuncangkeyong where 1 having  can_puhuo=1;";
        // echo $sql;die;

        return Db::connect("mysql")->Query($sql);

    }

    ###########################合并 puhuo_yuncangkeyong 代码进来 end###############################


    #########################店铺预计库存实时更新start#############################

    public function puhuo_customer_yuji_stock() {

        $goodsno = $this->puhuo_zdy_yuncang_goods2_model::where([])->distinct(true)->column('GoodsNo');
        
        $goodsno = get_goods_str($goodsno);
        // print_r($goodsno);die;
        
        $sql = "SELECT 
        T.CustomItem15 云仓,
        T.State 省份,
        T.CustomItem17 商品负责人,
        T.CustomerName 店铺名称,
        CASE WHEN T.MathodId=7 THEN '加盟' WHEN T.MathodId=4 THEN '直营' END AS 经营模式,
        EG.TimeCategoryName1 一级时间分类,
        EG.TimeCategoryName2 二级时间分类,
        EG.StyleCategoryName1 一级风格,
        EG.StyleCategoryName2 二级风格,
        EG.CategoryName1 一级分类,
        EG.CategoryName2 二级分类,
        EG.CategoryName 分类,
        EG.StyleCategoryName 风格,
        EG.GoodsNo 货号,
        -- EGPT.UnitPrice 零售价,
        -- ISNULL(TT.Price,EGPT.UnitPrice) 当前零售价,
        SUM(店铺库存) 店铺库存,
    -- 	SUM(在途库存) 在途库存,
    -- 	SUM(已配未发数量) 已配未发数量,
    -- 	SUM(在途量合计) 在途量合计,
        SUM(预计库存) 预计库存,
    -- 	CASE WHEN SUM(预计库存)<2 THEN '无效' END AS 无效识别,
    -- 	CASE WHEN ISNULL(SUM(店铺库存),0)=0 AND SUM(在途量合计)>2 THEN 1 END AS 新增SKC识别,
    -- 	CASE WHEN SUM(店库存加在途)>2 THEN 1 END AS 库存SKC数,
        CASE WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季' 
                 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
                 WHEN EG.TimeCategoryName2 LIKE '%秋%' THEN '秋季'
                 WHEN EG.TimeCategoryName2 LIKE '%冬%' THEN '冬季'
                    END AS 季节归集,
        T.CustomerGrade AS 店铺等级,
        CASE WHEN EG.CategoryName2='短T' AND EG.CategoryName LIKE '%翻领%' THEN '翻领' 
                 WHEN EG.CategoryName2='短T' AND EG.CategoryName LIKE '%圆领%' THEN '圆领' 
                 WHEN EG.CategoryName2='短T' THEN '其他'
                 ELSE EG.CategoryName2 END AS 领型
    -- 			 ,
    -- 	GETDATE() 更新时间
    FROM 
    (
    -- 店铺库存
    SELECT 
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo,
        SUM(ECS.Quantity) 店铺库存,
    -- 	NULL 在途库存,
    -- 	NULL 已配未发数量,
    -- 	NULL 在途量合计,
        SUM(ECS.Quantity) 预计库存,
        SUM(ECS.Quantity) 店库存加在途
    FROM ErpCustomer EC 
    LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId = ECS.CustomerId
    LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
    WHERE EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
    -- 	AND EG.TimeCategoryName1=2023
    -- 	AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%')
        AND EC.ShutOut=0 
        AND EC.MathodId IN (4,7)
        AND EC.RegionId!=55
        --AND EC.CustomItem15='南昌云仓'
        AND EG.GoodsNo in 
        ($goodsno)
    GROUP BY
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo
    HAVING SUM(ECS.Quantity)!=0
    UNION ALL 
    -- 仓库发货在途
    SELECT 
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo,
        NULL AS 店铺库存,
    -- 	SUM(EDG.Quantity) AS 在途库存,
    -- 	NULL 已配未发数量,
    -- 	SUM(EDG.Quantity) 在途量合计,
        SUM(EDG.Quantity) 预计库存,
        SUM(EDG.Quantity) 店库存加在途
    FROM ErpDelivery ED 
    LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID=EDG.DeliveryID
    LEFT JOIN ErpCustomer EC ON ED.CustomerId=EC.CustomerId
    LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
    WHERE ED.CodingCode='EndNode2'
        AND ED.IsCompleted=0
        --AND ED.IsReceipt IS NULL
        AND ED.DeliveryID NOT IN (SELECT ERG.DeliveryId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.DeliveryId IS NOT NULL AND ERG.DeliveryId!='' GROUP BY ERG.DeliveryId)
        AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
    -- 	AND EG.TimeCategoryName1=2023
    -- 	AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%')
        AND EC.ShutOut=0 
        AND EC.MathodId IN (4,7)
        AND EC.RegionId!=55
        -- AND EC.CustomItem15='南昌云仓'
        AND EG.GoodsNo in 
        ($goodsno)
    GROUP BY
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo
    UNION ALL
    --店店调拨在途
    SELECT
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo,
        NULL AS 店铺库存,
    -- 	SUM(EIG.Quantity) AS 在途库存,
    -- 	NULL 已配未发数量,
    -- 	SUM(EIG.Quantity) 在途量合计,
        SUM(EIG.Quantity) 预计库存,
        SUM(EIG.Quantity) 店库存加在途
    FROM ErpCustOutbound EI 
    LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId=EIG.CustOutboundId
    LEFT JOIN ErpCustomer EC ON EI.InCustomerId=EC.CustomerId
    LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
    WHERE EI.CodingCodeText='已审结'
        AND EI.IsCompleted=0
        AND EI.CustOutboundId NOT IN (SELECT ERG.CustOutboundId FROM ErpCustReceipt ER LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID=ERG.ReceiptID  WHERE ER.CodingCodeText='已审结' AND ERG.CustOutboundId IS NOT NULL AND ERG.CustOutboundId!='' GROUP BY ERG.CustOutboundId )
        AND EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
    -- 	AND EG.TimeCategoryName1=2023
    -- 	AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%')
        AND EC.ShutOut=0 
        AND EC.MathodId IN (4,7)
        AND EC.RegionId!=55
    -- 	AND EC.CustomItem15='南昌云仓'
        AND EG.GoodsNo in 
        ($goodsno)
    GROUP BY 
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo
    UNION ALL 
    --店铺已配未发
    SELECT 
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo,
        NULL AS 店铺库存,
    -- 	NULL AS 在途库存,
    -- 	SUM(ESG.Quantity) 已配未发数量,
    -- 	SUM(ESG.Quantity) 在途量合计,
        SUM(ESG.Quantity) 预计库存,
        NULL 店库存加在途
    FROM ErpCustomer EC
    LEFT JOIN ErpSorting ES ON EC.CustomerId=ES.CustomerId
    LEFT JOIN ErpSortingGoods ESG ON ES.SortingID=ESG.SortingID
    LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
    WHERE EG.CategoryName1 IN ('内搭','外套','下装','鞋履')
    -- 	AND EG.TimeCategoryName1=2023
    -- 	AND (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%')
        AND EC.ShutOut=0 
        AND EC.MathodId IN (4,7)
        AND ES.IsCompleted=0
        AND EC.RegionId!=55
    -- 	AND EC.CustomItem15='南昌云仓'
        AND EG.GoodsNo in 
        ($goodsno)
    GROUP BY 
        EC.CustomItem15,
        EC.State,
        EC.CustomItem17,
        EC.CustomerId,
        EC.CustomerName,
        EC.MathodId,
        EC.CustomerGrade,
        EG.GoodsNo
    ) T 
    LEFT JOIN ErpGoods EG ON T.GoodsNo=EG.GoodsNo
    -- LEFT JOIN ErpGoodsPriceType EGPT ON EG.GoodsId=EGPT.GoodsId
    -- WHERE EGPT.PriceId=1
    GROUP BY
        T.CustomItem15,
        T.CustomItem15,
        T.State,
        T.CustomItem17,
        T.CustomerId,
        T.CustomerName,
        T.MathodId,
        EG.TimeCategoryName1,
        EG.TimeCategoryName2,
        EG.StyleCategoryName1,
        EG.StyleCategoryName2,
        EG.CategoryName1,
        EG.CategoryName2,
        EG.CategoryName,
        EG.StyleCategoryName,
        EG.GoodsNo,
        -- EGPT.UnitPrice,
        -- TT.Price,
        T.CustomerGrade;
        ";

        $res = Db::connect("sqlsrv")->Query($sql);

        $chunk_list = $res ? array_chunk($res, 500) : [];
        if ($chunk_list) {
            foreach($chunk_list as $key => $val) {
                $insert = $this->db_easy->table('sp_lyp_puhuo_yuji_stock')->strict(false)->insertAll($val);
            }
        }

    }


    #########################店铺预计库存实时更新end#############################











}
