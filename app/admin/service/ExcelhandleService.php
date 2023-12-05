<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\bi\CwlDaxiaoHandleModel;
use app\admin\model\bi\SpLypPuhuoCurLogModel;
use app\admin\model\bi\SpLypPuhuoCustomerSortModel;
use app\admin\model\bi\SpLypPuhuoDaxiaomaCustomerModel;
use app\admin\model\bi\SpLypPuhuoDaxiaomaCustomerSortModel;
use app\admin\model\bi\SpLypPuhuoDaxiaomaSkcnumModel;
use app\admin\model\bi\SpLypPuhuoLogModel;
use app\admin\model\bi\SpLypPuhuoOnegoodsRuleModel;
use app\admin\model\bi\SpLypPuhuoRuleBModel;
use app\admin\model\bi\SpLypPuhuoScoreModel;
use app\admin\model\bi\SpLypPuhuoShangshidayModel;
use app\admin\model\bi\SpLypPuhuoTiGoodsModel;
use app\admin\model\bi\SpLypPuhuoWaitGoodsModel;
use app\admin\model\bi\SpLypPuhuoZdySet2Model;
use app\admin\model\bi\SpLypPuhuoZdySetModel;
use app\admin\model\bi\SpLypPuhuoZdyYuncangGoods2Model;
use app\admin\model\bi\SpLypPuhuoZdyYuncangGoodsModel;
use app\admin\model\bi\SpLypPuhuoZhidingGoodsModel;
use app\admin\model\bi\SpWwChunxiaStockModel;
use think\facade\Cache;
use think\facade\Db;

class ExcelhandleService
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
    protected $cwl_daxiao_handle_model;

    public function __construct()
    {
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
    }


    public function customer_sort(){
        $db=Db::connect('mysql');
        $customList=$db->table('sp_lyp_puhuo_excel')->group('CustomerName')->select()->toArray();


        $customer_level = $this->puhuo_score_model::where([['config_str', '=', 'customer_level']])->column('*', 'key');
        $fill_rate_level = $this->puhuo_score_model::where([['config_str', '=', 'fill_rate']])->column('*', 'key_level');
        $dongxiao_rate_level = $this->puhuo_score_model::where([['config_str', '=', 'dongxiao_rate']])->column('*', 'key_level');

        $ti_goods = $this->puhuo_ti_goods_model::where([])->column('GoodsNo');
        $ti_goods = $this->get_goods_str($ti_goods);

        foreach ($customList as $item){

            //店铺等级评分
            $CustomerGradeScore = isset($customer_level[$item['CustomerGrade']]) ? $customer_level[$item['CustomerGrade']]['score'] : 0;

            //满足率计算
            //满足率-单店skc
            $dandian_skc = $this->db_easy->Query($this->get_dandian_skc(['一级时间分类' => $item['TimeCategoryName1'], '二级时间分类' => $item['TimeCategoryName2'], '一级分类' => $item['CategoryName1'], '二级分类' =>  $item['CategoryName2'], '风格' =>  $item['StyleCategoryName'], '店铺名称' => $item['CustomerName']], $ti_goods));
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

            dd($CustomerGradeScore);
        }


        dd([$customer_level,$fill_rate_level,$dongxiao_rate_level]);



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




    public function get_yunchang_goods_data() {

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
        
        EGC.ColorDesc,
        
        EGPT.UnitPrice,
    
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
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓','广州过季仓')
    
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
    
    WHERE	 (ES.CodingCode= 'StartNode1'
    
                        OR (ES.CodingCode= 'EndNode2' AND ES.IsCompleted= 0 )
    
                    ) 
    
        AND EG.TimeCategoryName1>2022
    
        AND EG.CategoryName1 NOT IN ('物料','人事物料')
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓','广州过季仓')
    
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
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓','广州过季仓')
    
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
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓','广州过季仓')
    
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
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓','广州过季仓')
    
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
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓','广州过季仓')
    
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
        
        EGPT.UnitPrice 
    HAVING  SUM(T.Quantity) >0
    
    ;";

        return Db::connect("sqlsrv")->Query($sql);

    }


}