<?php

//puhuo 服务层
namespace app\admin\service;

use app\admin\model\bi\SpLypPuhuoCustomerSortModel;
use app\admin\model\bi\SpLypPuhuoCurLogModel;
use app\admin\model\bi\SpLypPuhuoWaitGoodsModel;
use app\admin\model\bi\SpLypPuhuoEndDataModel;
use app\admin\model\bi\SpLypPuhuoConfigModel;
use app\admin\model\bi\SpLypPuhuoScoreModel;
use app\admin\model\bi\SpLypPuhuoColdtohotModel;
use app\admin\model\bi\SpLypPuhuoHottocoldModel;
use app\admin\model\bi\SpLypPuhuoTiGoodsTypeModel;
use app\admin\model\bi\SpLypPuhuoZhidingGoodsModel;
use app\admin\model\bi\SpLypPuhuoZdySetModel;
use app\admin\model\bi\SpLypPuhuoZdySet2Model;
use app\admin\model\bi\SpLypPuhuoZdyYuncangGoodsModel;
use app\admin\model\bi\SpLypPuhuoZdyYuncangGoods2Model;
use app\admin\model\bi\SpLypPuhuoOnegoodsRuleModel;
use app\admin\model\bi\SpLypPuhuoRunModel;
use app\admin\model\bi\SpLypPuhuoDdUserModel;
use app\admin\model\bi\SpLypPuhuoWarehouseReserveConfigModel;
use app\admin\model\bi\SpLypPuhuoWarehouseReserveGoodsModel;
use app\admin\model\bi\SpLypPuhuoCaogaoModel;
use app\admin\model\bi\DdUserModel;
use app\admin\model\CustomerModel;
use app\common\traits\Singleton;
use think\facade\Db;

class PuhuoService
{

    use Singleton;

    protected $easy_db;
    protected $mysql;
    protected $erp;

    public function __construct()
    {
        $this->easy_db = Db::connect("mysql");
        $this->mysql = Db::connect("mysql");
        $this->erp = Db::connect('sqlsrv');
    }

    public function puhuo_index($params)
    {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $WarehouseName = $params['WarehouseName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $GoodsNo = $params['GoodsNo'] ?? '';//货号
        $CustomerName = $params['CustomerName'] ?? '';//店铺名称
        $is_puhuo = $params['is_puhuo'] ?? '';
        $CustomItem17 = $params['CustomItem17'] ?? '';//商品专员
        // $score_sort = $params['score_sort'] ?? '';//店铺排名
        $kepu_sort = $params['kepu_sort'] ?? 0;//可铺店铺排名

        $where = $list = [];
        $where[] = ['is_delete', '=', 2];
        $where[] = ['admin_id', '=', session('admin.id')];
        if ($WarehouseName) {
            $where[] = ['WarehouseName', 'in', $WarehouseName];
        }
        if ($CategoryName1) {
            $where[] = ['CategoryName1', 'in', $CategoryName1];
        }
        if ($GoodsNo) {
            $where[] = ['GoodsNo', 'in', $GoodsNo];
        }
        if ($CustomerName) {
            $where[] = ['CustomerName', 'in', $CustomerName];
        }
        if ($CustomItem17) {
            $where[] = ['CustomItem17', 'in', $CustomItem17];
        }
        // if ($score_sort) {
        //     $where[] = ['score_sort', '<=', $score_sort];
        // }
        if ($is_puhuo) {
            if ($is_puhuo == '可铺') {//可铺

                if ($kepu_sort) {
                    $where[] = ['kepu_sort', '<>', 0];
                    $where[] = ['kepu_sort', '<=', $kepu_sort];
                } else {
                    $where[] = ['Stock_Quantity_puhuo', '>', 0];
                }
                $list = SpLypPuhuoEndDataModel::where($where)->field('*')
                    ->paginate([
                        'list_rows' => $pageLimit,
                        'page' => $page,
                    ]);
                $list = $list ? $list->toArray() : [];

            } else {//不可铺

                $where[] = ['is_total', '>', 0];
                $list = SpLypPuhuoEndDataModel::where($where)->whereOr(function ($q) use ($WarehouseName, $CategoryName1, $GoodsNo, $CustomerName) {
                    $where = [['is_total', '=', 0], ['Stock_Quantity_puhuo', '=', 0]];
                    if ($WarehouseName) {
                        $where[] = ['WarehouseName', 'in', $WarehouseName];
                    }
                    if ($CategoryName1) {
                        $where[] = ['CategoryName1', 'in', $CategoryName1];
                    }
                    if ($GoodsNo) {
                        $where[] = ['GoodsNo', 'in', $GoodsNo];
                    }
                    if ($CustomerName) {
                        $where[] = ['CustomerName', 'in', $CustomerName];
                    }
                    $q->where($where);
                })->field('*')
                    ->paginate([
                        'list_rows' => $pageLimit,
                        'page' => $page,
                    ]);
                $list = $list ? $list->toArray() : [];

            }
        } else {

            $list = SpLypPuhuoEndDataModel::where($where)->field('*')
                ->paginate([
                    'list_rows' => $pageLimit,
                    'page' => $page,
                ]);
            $list = $list ? $list->toArray() : [];

        }
        // print_r([$pageLimit, $page]);die;

        $data = [
            'count' => $list ? $list['total'] : 0,
            'data' => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    public function caogao_index($params)
    {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $WarehouseName = $params['WarehouseName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $GoodsNo = $params['GoodsNo'] ?? '';//货号
        $CustomerName = $params['CustomerName'] ?? '';//店铺名称
        $is_puhuo = $params['is_puhuo'] ?? '';
        $CustomItem17 = $params['CustomItem17'] ?? '';//商品专员
        // $score_sort = $params['score_sort'] ?? '';//店铺排名
        $kepu_sort = $params['kepu_sort'] ?? 0;//可铺店铺排名
        $setTime1 = $params['setTime1'] ?? '';//开始日期
        $setTime2 = $params['setTime2'] ?? '';//结束日期

        $where = $list = [];
        $where[] = ['admin_id', '=', session('admin.id')];
        if ($WarehouseName) {
            $where[] = ['WarehouseName', 'in', $WarehouseName];
        }
        if ($CategoryName1) {
            $where[] = ['CategoryName1', 'in', $CategoryName1];
        }
        if ($GoodsNo) {
            $where[] = ['GoodsNo', 'in', $GoodsNo];
        }
        if ($CustomerName) {
            $where[] = ['CustomerName', 'in', $CustomerName];
        }
        if ($CustomItem17) {
            $where[] = ['CustomItem17', 'in', $CustomItem17];
        }
        if (isset($params['State']) && !empty($params['State'])) {
            $where[] = ['State', 'in', $params['State']];
        }
        if (isset($params['wenqu']) && !empty($params['wenqu'])) {
            $where[] = ['wenqu', 'in', $params['wenqu']];
        }
        if (isset($params['TimeCategoryName2']) && !empty($params['TimeCategoryName2'])) {
            $where[] = ['TimeCategoryName2', 'in', $params['TimeCategoryName2']];
        }
        if (isset($params['is_delete']) && $params['is_delete'] == 1) {
            $where[] = ['is_delete', '=', 1];
        } else {
            $where[] = ['is_delete', '=', 2];
        }
        if ($setTime1 && $setTime2) {
            $setTime2 = $setTime2 . ' 23:59:59';
            $where[] = ['create_time', 'between', [$setTime1, $setTime2]];
        }

        if ($kepu_sort) {
            $where[] = ['kepu_sort', '<>', 0];
            $where[] = ['kepu_sort', '<=', $kepu_sort];
        } else {
            $where[] = ['Stock_Quantity_puhuo', '>', 0];
        }
        $list = SpLypPuhuoCaogaoModel::where($where)->field('*')
            ->paginate([
                'list_rows' => $pageLimit,
                'page' => $page,
            ]);
        $list = $list ? $list->toArray() : [];

        // print_r([$pageLimit, $page]);die;

        $data = [
            'count' => $list ? $list['total'] : 0,
            'data' => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    public function puhuo_daodan($params)
    {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $WarehouseName = $params['WarehouseName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $GoodsNo = $params['GoodsNo'] ?? '';//货号
        $CustomerName = $params['CustomerName'] ?? '';//店铺名称
        $is_puhuo = $params['is_puhuo'] ?? '';
        $CustomItem17 = $params['CustomItem17'] ?? '';//商品专员
        // $score_sort = $params['score_sort'] ?? '';//店铺排名
        $kepu_sort = $params['kepu_sort'] ?? 0;//可铺店铺排名

        $where = $list = [];
        $where[] = ['lped.admin_id', '=', session('admin.id')];

        if ($WarehouseName) {
            $where[] = ['lped.WarehouseName', 'in', $WarehouseName];
        }
        if ($CategoryName1) {
            $where[] = ['lped.CategoryName1', 'in', $CategoryName1];
        }
        if ($GoodsNo) {
            $where[] = ['lped.GoodsNo', 'in', $GoodsNo];
        }
        if ($CustomerName) {
            $where[] = ['lped.CustomerName', 'in', $CustomerName];
        }
        if ($CustomItem17) {
            $where[] = ['lped.CustomItem17', 'in', $CustomItem17];
        }
        // if ($score_sort) {
        //     $where[] = ['score_sort', '<=', $score_sort];
        // }
        if ($is_puhuo) {
            if ($is_puhuo == '可铺') {//可铺

                if ($kepu_sort) {
                    $where[] = ['lped.kepu_sort', '<>', 0];
                    $where[] = ['lped.kepu_sort', '<=', $kepu_sort];
                } else {
                    $where[] = ['lped.Stock_Quantity_puhuo', '>', 0];
                }
                $list = SpLypPuhuoEndDataModel::where($where)
                    ->alias('lped')
                    ->join(['sp_lyp_puhuo_wait_goods' => 'lpwg'], 'lped.GoodsNo=lpwg.GoodsNo and lped.WarehouseName=lpwg.WarehouseName', 'left')
                    ->field('lped.*,lpwg.Stock_00_size,lpwg.Stock_29_size,lpwg.Stock_30_size,lpwg.Stock_31_size,lpwg.Stock_32_size,lpwg.Stock_33_size,
                lpwg.Stock_34_size,lpwg.Stock_35_size,lpwg.Stock_36_size,lpwg.Stock_38_size,lpwg.Stock_40_size,lpwg.Stock_42_size')
                    ->paginate([
                        'list_rows' => $pageLimit,
                        'page' => $page,
                    ]);
                $list = $list ? $list->toArray() : [];

            } else {//不可铺

                // $where[] = ['is_total', '>', 0];
                // $list = SpLypPuhuoEndDataModel::where($where)->whereOr(function ($q) use ($WarehouseName, $CategoryName1, $GoodsNo, $CustomerName) {
                //     $where = [['is_total', '=', 0], ['Stock_Quantity_puhuo', '=', 0]];
                //     if ($WarehouseName) {
                //         $where[] = ['WarehouseName', 'in', $WarehouseName];
                //     }
                //     if ($CategoryName1) {
                //         $where[] = ['CategoryName1', 'in', $CategoryName1];
                //     }
                //     if ($GoodsNo) {
                //         $where[] = ['GoodsNo', 'in', $GoodsNo];
                //     }
                //     if ($CustomerName) {
                //         $where[] = ['CustomerName', 'in', $CustomerName];
                //     }
                //     $q->where($where);
                // })->field('*')
                // ->paginate([
                //     'list_rows'=> $pageLimit,
                //     'page' => $page,
                // ]);
                // $list = $list ? $list->toArray() : [];

            }
        } else {

            $list = SpLypPuhuoEndDataModel::where($where)
                ->alias('lped')
                ->join(['sp_lyp_puhuo_wait_goods' => 'lpwg'], 'lped.GoodsNo=lpwg.GoodsNo and lped.WarehouseName=lpwg.WarehouseName', 'left')
                ->field('lped.*,lpwg.Stock_00_size,lpwg.Stock_29_size,lpwg.Stock_30_size,lpwg.Stock_31_size,lpwg.Stock_32_size,lpwg.Stock_33_size,
                lpwg.Stock_34_size,lpwg.Stock_35_size,lpwg.Stock_36_size,lpwg.Stock_38_size,lpwg.Stock_40_size,lpwg.Stock_42_size')
                // ->field('*')
                ->paginate([
                    'list_rows' => $pageLimit,
                    'page' => $page,
                ]);
            $list = $list ? $list->toArray() : [];

        }

        $data = [
            'count' => $list ? $list['total'] : 0,
            'data' => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    public function puhuo_daodan_caogao($params)
    {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $WarehouseName = $params['WarehouseName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $GoodsNo = $params['GoodsNo'] ?? '';//货号
        $CustomerName = $params['CustomerName'] ?? '';//店铺名称
        $is_puhuo = $params['is_puhuo'] ?? '';
        $CustomItem17 = $params['CustomItem17'] ?? '';//商品专员
        // $score_sort = $params['score_sort'] ?? '';//店铺排名
        $kepu_sort = $params['kepu_sort'] ?? 0;//可铺店铺排名
        $is_delete = $params['is_delete'] ?? 0;//是否导出
        $setTime1 = $params['setTime1'] ?? '';//开始日期
        $setTime2 = $params['setTime2'] ?? '';//结束日期
        $caogao_uuid = $params['caogao_uuid'] ?? '';//草稿uuid

        $where = $list = [];
        $where[] = ['lpc.admin_id', '=', session('admin.id')];

        if ($WarehouseName) {
            $where[] = ['lpc.WarehouseName', 'in', $WarehouseName];
        }
        if ($CategoryName1) {
            $where[] = ['lpc.CategoryName1', 'in', $CategoryName1];
        }
        if ($GoodsNo) {
            $where[] = ['lpc.GoodsNo', 'in', $GoodsNo];
        }
        if ($CustomerName) {
            $where[] = ['lpc.CustomerName', 'in', $CustomerName];
        }
        if ($CustomItem17) {
            $where[] = ['lpc.CustomItem17', 'in', $CustomItem17];
        }
        // if ($score_sort) {
        //     $where[] = ['score_sort', '<=', $score_sort];
        // }
        if ($is_delete) {
            $where[] = ['lpc.is_delete', '=', $is_delete];
        }
        if ($setTime1 && $setTime2) {
            $setTime2 = $setTime2 . ' 23:59:59';
            $where[] = ['lpc.create_time', 'between', [$setTime1, $setTime2]];
        }

        if ($caogao_uuid) {
            $where[] = ['lpc.uuid', 'in', $caogao_uuid];
        }

        if ($is_puhuo) {
            if ($is_puhuo == '可铺') {//可铺

                if ($kepu_sort) {
                    $where[] = ['lpc.kepu_sort', '<>', 0];
                    $where[] = ['lpc.kepu_sort', '<=', $kepu_sort];
                } else {
                    $where[] = ['lpc.Stock_Quantity_puhuo', '>', 0];
                }
                $list = SpLypPuhuoCaogaoModel::where($where)
                    ->alias('lpc')
                    ->join(['sp_lyp_puhuo_wait_goods' => 'lpwg'], 'lpc.GoodsNo=lpwg.GoodsNo and lpc.WarehouseName=lpwg.WarehouseName', 'left')
                    ->field('lpc.*,lpwg.Stock_00_size,lpwg.Stock_29_size,lpwg.Stock_30_size,lpwg.Stock_31_size,lpwg.Stock_32_size,lpwg.Stock_33_size,
                lpwg.Stock_34_size,lpwg.Stock_35_size,lpwg.Stock_36_size,lpwg.Stock_38_size,lpwg.Stock_40_size,lpwg.Stock_42_size')
                    ->paginate([
                        'list_rows' => $pageLimit,
                        'page' => $page,
                    ]);
                $list = $list ? $list->toArray() : [];

            } else {//不可铺

                // $where[] = ['is_total', '>', 0];
                // $list = SpLypPuhuoEndDataModel::where($where)->whereOr(function ($q) use ($WarehouseName, $CategoryName1, $GoodsNo, $CustomerName) {
                //     $where = [['is_total', '=', 0], ['Stock_Quantity_puhuo', '=', 0]];
                //     if ($WarehouseName) {
                //         $where[] = ['WarehouseName', 'in', $WarehouseName];
                //     }
                //     if ($CategoryName1) {
                //         $where[] = ['CategoryName1', 'in', $CategoryName1];
                //     }
                //     if ($GoodsNo) {
                //         $where[] = ['GoodsNo', 'in', $GoodsNo];
                //     }
                //     if ($CustomerName) {
                //         $where[] = ['CustomerName', 'in', $CustomerName];
                //     }
                //     $q->where($where);
                // })->field('*')
                // ->paginate([
                //     'list_rows'=> $pageLimit,
                //     'page' => $page,
                // ]);
                // $list = $list ? $list->toArray() : [];

            }
        } else {

            $list = SpLypPuhuoCaogaoModel::where($where)
                ->alias('lpc')
                ->join(['sp_lyp_puhuo_wait_goods' => 'lpwg'], 'lpc.GoodsNo=lpwg.GoodsNo and lpc.WarehouseName=lpwg.WarehouseName', 'left')
                ->field('lpc.*,lpwg.Stock_00_size,lpwg.Stock_29_size,lpwg.Stock_30_size,lpwg.Stock_31_size,lpwg.Stock_32_size,lpwg.Stock_33_size,
                lpwg.Stock_34_size,lpwg.Stock_35_size,lpwg.Stock_36_size,lpwg.Stock_38_size,lpwg.Stock_40_size,lpwg.Stock_42_size')
                // ->field('*')
                ->paginate([
                    'list_rows' => $pageLimit,
                    'page' => $page,
                ]);
            $list = $list ? $list->toArray() : [];

        }

        $data = [
            'count' => $list ? $list['total'] : 0,
            'data' => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    public function change_caogao_status($res_data)
    {

        if ($res_data) {
            $chunk_list = array_chunk($res_data, 500);
            foreach ($chunk_list as $key => $val) {
                $uuid_arr = $val ? array_column($val, 'uuid') : [];
                if ($uuid_arr) {
                    SpLypPuhuoCaogaoModel::where([['uuid', 'in', $uuid_arr]])->update(['is_delete' => 1]);
                }
            }
        }

    }

    public function add_puhuo_daodan($res_data)
    {

        if ($res_data) {
            // $this->easy_db->query("truncate table sp_lyp_puhuo_daodan;");
            $chunk_list = array_chunk($res_data, 1000);
            foreach ($chunk_list as $key => $val) {
                $insert = $this->easy_db->table('sp_lyp_puhuo_daodan')->strict(false)->insertAll($val);
            }
        }

    }

    public function getXmMapSelect($sign = 1)
    {

        if ($sign == 1) {
            $WarehouseName = $this->easy_db->query("select WarehouseName as name, WarehouseName as value from sp_lyp_puhuo_cur_log  group by WarehouseName;");
            $CategoryName1 = $this->easy_db->query("select CategoryName1 as name, CategoryName1 as value from sp_lyp_puhuo_wait_goods where CategoryName1!='' and (TimeCategoryName2 like '%秋%' or TimeCategoryName2 like '%冬%') group by CategoryName1;");
            $GoodsNo = $this->easy_db->query("select GoodsNo as name, GoodsNo as value from sp_lyp_puhuo_end_data  group by GoodsNo;");
            $CustomerName = $this->easy_db->query("select CustomerName as name, CustomerName as value from sp_lyp_puhuo_end_data where CustomerName!='余量' and CustomerName not like '%云仓%' group by CustomerName;");
            $CustomItem17 = $this->easy_db->query("select CustomItem17 as name, CustomItem17 as value from sp_lyp_puhuo_end_data where CustomItem17!='' group by CustomItem17;");
        } else {
            $WarehouseName = $this->easy_db->query("select WarehouseName as name, WarehouseName as value from sp_lyp_puhuo_caogao  group by WarehouseName;");
            $CategoryName1 = $this->easy_db->query("select CategoryName1 as name, CategoryName1 as value from sp_lyp_puhuo_caogao where CategoryName1!='' and (TimeCategoryName2 like '%秋%' or TimeCategoryName2 like '%冬%') group by CategoryName1;");
            $GoodsNo = $this->easy_db->query("select GoodsNo as name, GoodsNo as value from sp_lyp_puhuo_caogao  group by GoodsNo;");
            $CustomerName = $this->easy_db->query("select CustomerName as name, CustomerName as value from sp_lyp_puhuo_caogao where CustomerName!='余量' and CustomerName not like '%云仓%' group by CustomerName;");
            $CustomItem17 = $this->easy_db->query("select CustomItem17 as name, CustomItem17 as value from sp_lyp_puhuo_caogao where CustomItem17!='' group by CustomItem17;");
        }

        return ['WarehouseName' => $WarehouseName, 'CategoryName1' => $CategoryName1, 'GoodsNo' => $GoodsNo, 'CustomerName' => $CustomerName, 'CustomItem17' => $CustomItem17, 'is_puhuo' => [['name' => '可铺', 'value' => '可铺'], ['name' => '不可铺', 'value' => '可铺']]];

    }

    public function getXmMapSelectCaogao(){

        $db = $this->mysql->table('sp_lyp_puhuo_caogao')->where([['is_delete', '<>',1 ],['admin_id','=',session('admin.id')]])->select()->toArray();
        $WarehouseName = xm($db, 'WarehouseName');
        $CategoryName1 = xm($db, 'CategoryName1');
        $GoodsNo = xm($db, 'GoodsNo');
        $CustomerName = xm($db, 'CustomerName');
        $CustomItem17 = xm($db, 'CustomItem17');
        $State = xm($db, 'State');
        $TimeCategoryName2 = xm($db, 'TimeCategoryName2');
        $wenqu = xm($db, 'wenqu');

        return compact('WarehouseName', 'State', 'CategoryName1', 'GoodsNo', 'CustomerName', 'CustomItem17','TimeCategoryName2','wenqu');

    }

    //铺货-统计
    public function puhuo_statistic($params)
    {

        $WarehouseName = $params['WarehouseName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';

        $where = $where_store = [];
        if ($WarehouseName) {
            $where[] = ['lpwg.WarehouseName', 'in', $WarehouseName];
        } else {//暂时使用，目前只有贵阳云仓数据 上线后去掉 20230823

            $WarehouseName = $this->easy_db->query("select distinct WarehouseName from sp_lyp_puhuo_cur_log;");
            $WarehouseName = $WarehouseName ? array_column($WarehouseName, 'WarehouseName') : [];
            $where[] = ['lpwg.WarehouseName', 'in', $WarehouseName];

        }
        if ($CategoryName1) {
            $where_store[] = ['lpwg.CategoryName1', 'in', $CategoryName1];
        }
        $wait_goods_model = new SpLypPuhuoWaitGoodsModel;
        $customer_sort_model = new SpLypPuhuoCustomerSortModel;
        $where1 = array_merge($where, [['lpwg.CategoryName1', '=', '内搭']]);
        $where2 = array_merge($where, [['lpwg.CategoryName1', '=', '外套']]);
        $where3 = array_merge($where, [['lpwg.CategoryName1', '=', '下装']]);
        $where4 = array_merge($where, [['lpwg.CategoryName1', '=', '鞋履']]);
        $nd = $wait_goods_model::where($where1)->alias('lpwg')->count();
        $wt = $wait_goods_model::where($where2)->alias('lpwg')->count();
        $xz = $wait_goods_model::where($where3)->alias('lpwg')->count();
        $xl = $wait_goods_model::where($where4)->alias('lpwg')->count();

        $where_store_merge = array_merge($where, $where_store);
        $store_num = $customer_sort_model::where($where_store_merge)->alias('lpcs')
            ->join(['sp_lyp_puhuo_cur_log' => 'lpcl'], 'lpcs.cur_log_uuid=lpcl.uuid', 'left')
            ->join(['sp_lyp_puhuo_wait_goods' => 'lpwg'], 'lpcs.GoodsNo=lpwg.GoodsNo and lpcs.Yuncang=lpwg.WarehouseName', 'left')
            ->count('distinct lpcs.CustomerId');

        return ['nd' => $nd, 'wt' => $wt, 'xz' => $xz, 'xl' => $xl, 'store_num' => $store_num];

    }

    /**
     * 获取铺货配置
     */
    public function get_puhuo_config()
    {

        return SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();

    }

    /**
     * 获取铺货配置2
     */
    public function get_puhuo_config2()
    {

        $res = SpLypPuhuoWarehouseReserveConfigModel::select();
        $res = $res ? $res->toArray() : [];
        return $res;

    }

    /**
     * 保存仓库预留参数配置/保存门店上铺货连码标准配置/保存仓库齐码参数配置
     */
    public function save_warehouse_config($data)
    {

        $id = null;
        if ($data) {
            $sign_id = $data['sign_id'];
            unset($data['sign_id']);
            $id = SpLypPuhuoConfigModel::where([['config_str', '=', $sign_id]])->update($data);
            $id = $sign_id;
        }
        return $id;

    }

    /**
     * 保存仓库预留参数配置/保存门店上铺货连码标准配置/保存仓库齐码参数配置2
     */
    public function save_warehouse_config2($data)
    {

        $id = $data['id'] ?? 0;
        if ($id) {
            SpLypPuhuoWarehouseReserveConfigModel::where([['id', '=', $id]])->update($data);
        }
        return $id;

    }

    /**
     * 获取评分标准
     */
    public function get_puhuo_score($config_str)
    {

        $res = SpLypPuhuoScoreModel::where([['config_str', '=', $config_str]])->select();
        $res = $res ? $res->toArray() : [];
        return $res;

    }

    /**
     * 检测是否已存在
     */
    public function check_customer_level($post)
    {

        return SpLypPuhuoScoreModel::where([['config_str', '=', $post['config_str']], ['key', '=', $post['key']]])->field('id')->find();

    }

    /**
     * 保存店铺评分标准配置
     */
    public function save_customer_level($data)
    {

        $id = null;
        if ($data) {
            $id = $data['id'];
            if ($id != '') {//更新

                SpLypPuhuoScoreModel::where([['id', '=', $id]])->update($data);

            } else {//插入

                $max_key_level = SpLypPuhuoScoreModel::where([['config_str', '=', $data['config_str']]])->max('key_level');
                $data['key_level'] = ++$max_key_level;
                $id = SpLypPuhuoScoreModel::create($data);
                $id = $id->id;

            }
        }
        return $id;

    }

    /**
     * 删除店铺评分标准配置
     */
    public function del_customer_level($id)
    {

        return SpLypPuhuoScoreModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 获取气温评分标准
     */
    public function get_qiwen_score($str = 'coldtohot')
    {

        if ($str == 'coldtohot') {
            $res = SpLypPuhuoColdtohotModel::where([])->select();
        } else {
            $res = SpLypPuhuoHottocoldModel::where([])->select();
        }
        $res = $res ? $res->toArray() : [];
        return $res;

    }

    /**
     * 检测是否已存在（冷到热）
     */
    public function check_coldtohot($post)
    {

        return SpLypPuhuoColdtohotModel::where([['yuncang', '=', $post['yuncang']], ['province', '=', $post['province']], ['wenqu', '=', $post['wenqu']]])->field('id')->find();

    }

    /**
     * 保存气温评分标准配置（冷到热）
     */
    public function save_coldtohot($data)
    {

        $id = null;
        $msg = '';
        if ($data) {
            $id = $data['id'];
            if ($id != '') {//更新

                $if_exist = SpLypPuhuoColdtohotModel::where([['yuncang', '=', $data['yuncang']], ['province', '=', $data['province']], ['wenqu', '=', $data['wenqu']], ['id', '<>', $id]])->field('id')->find();
                if ($if_exist) {
                    $msg = '配置已存在，请检查';
                } else {
                    SpLypPuhuoColdtohotModel::where([['id', '=', $id]])->update($data);
                }

            } else {//插入

                $max_qiwen_sort = SpLypPuhuoColdtohotModel::where([])->max('qiwen_sort');
                $data['qiwen_sort'] = ++$max_qiwen_sort;
                $id = SpLypPuhuoColdtohotModel::create($data);
                $id = $id->id;

            }
        }
        return ['id' => $id, 'msg' => $msg];

    }

    /**
     * 检测是否已存在（热到冷）
     */
    public function check_hottocold($post)
    {

        return SpLypPuhuoHottocoldModel::where([['yuncang', '=', $post['yuncang']], ['province', '=', $post['province']], ['wenqu', '=', $post['wenqu']]])->field('id')->find();

    }

    /**
     * 保存气温评分标准配置（热到冷）
     */
    public function save_hottocold($data)
    {

        $id = null;
        $msg = '';
        if ($data) {
            $id = $data['id'];
            if ($id != '') {//更新

                $if_exist = SpLypPuhuoHottocoldModel::where([['yuncang', '=', $data['yuncang']], ['province', '=', $data['province']], ['wenqu', '=', $data['wenqu']], ['id', '<>', $id]])->field('id')->find();
                if ($if_exist) {
                    $msg = '配置已存在，请检查';
                } else {
                    SpLypPuhuoHottocoldModel::where([['id', '=', $id]])->update($data);
                }

            } else {//插入

                $max_qiwen_sort = SpLypPuhuoHottocoldModel::where([])->max('qiwen_sort');
                $data['qiwen_sort'] = ++$max_qiwen_sort;
                $id = SpLypPuhuoHottocoldModel::create($data);
                $id = $id->id;

            }
        }
        return ['id' => $id, 'msg' => $msg];

    }

    /**
     * 删除气温评分标准配置（冷到热）
     */
    public function del_coldtohot($id)
    {

        return SpLypPuhuoColdtohotModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 删除气温评分标准配置(热到冷）
     */
    public function del_hottocold($id)
    {

        return SpLypPuhuoHottocoldModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 获取指定款分类
     */
    public function get_ti_goods_type()
    {

        $res = SpLypPuhuoTiGoodsTypeModel::where([])->column('id,GoodsLevel', 'GoodsLevel');
        return $res;

    }

    /**
     * 获取指定款分类
     */
    public function get_zhiding_goods($Yuncang)
    {

        $res = SpLypPuhuoZhidingGoodsModel::where([['Yuncang', '=', $Yuncang]])->column('GoodsNo');
        $res = $res ? implode(' ', $res) : '';
        return $res;

    }

    /**
     * 获取各云仓 自定义铺货货品 配置列表
     */
    public function get_zdy_goods($Yuncang)
    {

        $res = SpLypPuhuoZdySetModel::where([['Yuncang', '=', $Yuncang]])->field('id,Yuncang,GoodsNo,Selecttype,Commonfield,rule_type,if_taozhuang')->select();
        $res = $res ? $res->toArray() : [];
        $select_list = $this->get_select_data($Yuncang);
        if ($res) {
            foreach ($res as &$v_res) {//0全部店  、 1多店、2多省、3商品专员、4经营模式
                $Commonfield_arr = $v_res['Commonfield'] ? explode(',', $v_res['Commonfield']) : [];
                $Commonfield_select = [];
                switch ($v_res['Selecttype']) {
                    case 1: //多店
                        if ($select_list['customer_list']) {
                            foreach ($select_list['customer_list'] as $v_customer_list) {
                                if (in_array($v_customer_list['value'], $Commonfield_arr)) {
                                    $v_customer_list['selected'] = true;
                                }
                                $Commonfield_select[] = $v_customer_list;
                            }
                        }
                        $v_res['Selecttype_str'] = '多店';
                        break;

                    case 2: //多省
                        if ($select_list['province_list']) {
                            foreach ($select_list['province_list'] as $v_province_list) {
                                if (in_array($v_province_list['value'], $Commonfield_arr)) {
                                    $v_province_list['selected'] = true;
                                }
                                $Commonfield_select[] = $v_province_list;
                            }
                        }
                        $v_res['Selecttype_str'] = '多省';
                        break;

                    case 3: //商品专员
                        if ($select_list['goods_manager_list']) {
                            foreach ($select_list['goods_manager_list'] as $v_goods_manager_list) {
                                if (in_array($v_goods_manager_list['value'], $Commonfield_arr)) {
                                    $v_goods_manager_list['selected'] = true;
                                }
                                $Commonfield_select[] = $v_goods_manager_list;
                            }
                        }
                        $v_res['Selecttype_str'] = '商品专员';
                        break;

                    case 4: //经营模式
                        if ($select_list['mathod_list']) {
                            foreach ($select_list['mathod_list'] as $v_mathod_list) {
                                if (in_array($v_mathod_list['value'], $Commonfield_arr)) {
                                    $v_mathod_list['selected'] = true;
                                }
                                $Commonfield_select[] = $v_mathod_list;
                            }
                        }
                        $v_res['Selecttype_str'] = '经营模式';
                        break;
                    default:
                        $v_res['Selecttype_str'] = '';
                        break;

                }

                $v_res['Commonfield_select'] = $Commonfield_select;

            }
        }
        $return = [];
        if ($Yuncang == '武汉云仓') {
            $return = ['wuhan_goods_config' => $res, 'wuhan_select_list' => $select_list];
        } elseif ($Yuncang == '贵阳云仓') {
            $return = ['guiyang_goods_config' => $res, 'guiyang_select_list' => $select_list];
        } elseif ($Yuncang == '广州云仓') {
            $return = ['guangzhou_goods_config' => $res, 'guangzhou_select_list' => $select_list];
        } elseif ($Yuncang == '南昌云仓') {
            $return = ['nanchang_goods_config' => $res, 'nanchang_select_list' => $select_list];
        } elseif ($Yuncang == '长沙云仓') {
            $return = ['changsha_goods_config' => $res, 'changsha_select_list' => $select_list];
        }

        return $return;

    }

    /**
     * 获取各云仓 自定义铺货货品 配置列表2
     */
    public function get_zdy_goods2($Yuncang)
    {

        $res = SpLypPuhuoZdySet2Model::where([['admin_id', '=', session('admin.id')], ['Yuncang', '=', $Yuncang], ['Selecttype', '=', SpLypPuhuoZdySet2Model::SELECT_TYPE['much_store']]])->field('id,Yuncang,GoodsNo,Selecttype,Commonfield,rule_type,remain_store,remain_rule_type,if_taozhuang,if_zdmd')->select();
        $res = $res ? $res->toArray() : [];
        $select_list = $this->get_select_data($Yuncang);
        if ($res) {
            foreach ($res as &$v_res) {//1组合(多省、商品专员、经营模式) 2多店
                $Commonfield_arr = $v_res['Commonfield'] ? explode(',', $v_res['Commonfield']) : [];
                $Commonfield_select = [];
                switch ($v_res['Selecttype']) {
                    case 1: //组合
                        // if ($select_list['customer_list']) {
                        //     foreach ($select_list['customer_list'] as $v_customer_list) {
                        //         if (in_array($v_customer_list['value'], $Commonfield_arr)) {
                        //             $v_customer_list['selected'] = true;
                        //         }
                        //         $Commonfield_select[] = $v_customer_list;
                        //     }
                        // }
                        $v_res['Selecttype_str'] = '组合';
                        break;

                    case 2: //单店
                        if ($select_list['customer_list']) {
                            foreach ($select_list['customer_list'] as $v_province_list) {
                                if (in_array($v_province_list['value'], $Commonfield_arr)) {
                                    $v_province_list['selected'] = true;
                                }
                                $Commonfield_select[] = $v_province_list;
                            }
                        }
                        $v_res['Selecttype_str'] = '单店';
                        $v_res['Commonfield'] = $v_res['Commonfield'] ? implode(' ', explode(',', $v_res['Commonfield'])) : '';
                        break;

                    default:
                        $v_res['Selecttype_str'] = '';
                        break;

                }

                $v_res['Commonfield_select'] = $Commonfield_select;

            }
        }
        $return = [];
        if ($Yuncang == '武汉云仓') {
            $return = ['wuhan_goods_config' => $res, 'wuhan_select_list' => $select_list];
        } elseif ($Yuncang == '贵阳云仓') {
            $return = ['guiyang_goods_config' => $res, 'guiyang_select_list' => $select_list];
        } elseif ($Yuncang == '广州云仓') {
            $return = ['guangzhou_goods_config' => $res, 'guangzhou_select_list' => $select_list];
        } elseif ($Yuncang == '南昌云仓') {
            $return = ['nanchang_goods_config' => $res, 'nanchang_select_list' => $select_list];
        } elseif ($Yuncang == '长沙云仓') {
            $return = ['changsha_goods_config' => $res, 'changsha_select_list' => $select_list];
        }

        return $return;

    }

    /**
     * 获取各云仓 自定义铺货货品 配置列表2zh
     */
    public function get_zdy_goods2zh($Yuncang)
    {

        $res = SpLypPuhuoZdySet2Model::where([['admin_id', '=', session('admin.id')], ['Yuncang', '=', $Yuncang], ['Selecttype', '=', SpLypPuhuoZdySet2Model::SELECT_TYPE['much_merge']]])->field('id,Yuncang,GoodsNo,Selecttype,Commonfield,rule_type,remain_store,remain_rule_type,if_taozhuang,if_zdmd')->select();
        $res = $res ? $res->toArray() : [];
        $select_list = $this->get_select_data2($Yuncang);
        if ($res) {
            foreach ($res as &$v_res) {//1组合(多省、商品专员、经营模式) 2多店
                $Commonfield_arr = $v_res['Commonfield'] ? explode(',', $v_res['Commonfield']) : [];
                $Commonfield_select = [];
                switch ($v_res['Selecttype']) {
                    case 1: //组合
                        if ($select_list['merge_list']) {
                            foreach ($select_list['merge_list'] as $v_customer_list) {
                                if (in_array($v_customer_list['value'], $Commonfield_arr)) {
                                    $v_customer_list['selected'] = true;
                                }
                                $Commonfield_select[] = $v_customer_list;
                            }
                        }
                        $v_res['Selecttype_str'] = '组合';
                        break;

                    default:
                        $v_res['Selecttype_str'] = '';
                        break;

                }
                $rr=[];
                foreach ($Commonfield_select as $item){
                    if(isset($rr[$item['pid']])){
                        $rr[$item['pid']]['children'] []=$item;
                    }else{
                        $rr[$item['pid']]=['name'=>$item['pid'],'value'=>$item['pid'],'children'=>[$item]];
                    }
                }
                $rr=array_values($rr);
                $v_res['Commonfield_select'] = $rr;

            }
        }
        $cc=[];
        foreach ($select_list['merge_list'] as $item){

            if(isset($cc[$item['pid']])){
                $cc[$item['pid']]['children'] []=$item;
            }else{
                $cc[$item['pid']]=['name'=>$item['pid'],'value'=>$item['pid'],'children'=>[$item]];
            }
        }
        $cc=array_values($cc);
        $select_list['merge_list'] = $cc;

        $return = [];
        if ($Yuncang == '武汉云仓') {
            $return = ['wuhan_goods_config' => $res, 'wuhan_select_list' => $select_list];
        } elseif ($Yuncang == '贵阳云仓') {
            $return = ['guiyang_goods_config' => $res, 'guiyang_select_list' => $select_list];
        } elseif ($Yuncang == '广州云仓') {
            $return = ['guangzhou_goods_config' => $res, 'guangzhou_select_list' => $select_list];
        } elseif ($Yuncang == '南昌云仓') {
            $return = ['nanchang_goods_config' => $res, 'nanchang_select_list' => $select_list];
        } elseif ($Yuncang == '长沙云仓') {
            $return = ['changsha_goods_config' => $res, 'changsha_select_list' => $select_list];
        }

        return $return;

    }

    //获取多店、多省、商品专员、经营模式 下拉数据
    public function get_select_data($yuncang)
    {

        $customer_regionid_notin_text = config('skc.customer_regionid_notin_text');
        $customer_list = $this->easy_db->Query("select CustomerName as name, CustomerId as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by CustomerName;");
        $province_list = $this->easy_db->Query("select State as name, State as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by State;");
        $goods_manager_list = $this->easy_db->Query("select CustomItem17 as name, CustomItem17 as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by CustomItem17;");
        $mathod_list = $this->easy_db->Query("select Mathod as name, Mathod as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by Mathod;");//[['name' => '加盟', 'value' => '加盟'], ['name' => '直营', 'value' => '直营']];
        return ['customer_list' => $customer_list, 'province_list' => $province_list, 'goods_manager_list' => $goods_manager_list, 'mathod_list' => $mathod_list];

    }

    //获取组合（多省、商品专员、经营模式）下拉数据
    public function get_select_data2($yuncang)
    {

        $customer_regionid_notin_text = config('skc.customer_regionid_notin_text');
        $province_list = $this->easy_db->Query("select '省份' as pid, State as name, concat('省份-', State) as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by State;");
        $goods_manager_list = $this->easy_db->Query("select '商品专员' as pid, CustomItem17 as name, concat('商品专员-', CustomItem17) as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by CustomItem17;");
        $mathod_list = $this->easy_db->Query("select '经营模式' as pid,Mathod as name, concat('经营模式-', Mathod) as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by Mathod;");//[['name' => '加盟', 'value' => '加盟'], ['name' => '直营', 'value' => '直营']];
        $wenqu_list = $this->easy_db->Query("select '温区' as pid, CustomItem36 as name, concat('温区-', CustomItem36) as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by CustomItem36;");//温区
        $merge = array_merge($mathod_list, $province_list, $goods_manager_list, $wenqu_list);

        return ['merge_list' => $merge];

    }

    /**
     * 保存各个云仓指定铺货货品配置
     */
    public function saveZhidingGoodsConfig($data)
    {

        $Yuncang = $data['Yuncang'];
        $GoodsNo = $data['GoodsNo'] ? explode(' ', $data['GoodsNo']) : [];

        //先删除旧的配置
        SpLypPuhuoZhidingGoodsModel::where([['Yuncang', '=', $Yuncang]])->delete();
        //重新入库
        $res_data = [];
        if ($GoodsNo) {
            foreach ($GoodsNo as $v_goods) {
                $v_goods = $v_goods ? trim($v_goods) : '';
                if ($v_goods) {
                    $res_data[] = ['Yuncang' => $Yuncang, 'GoodsNo' => $v_goods];
                }
            }

            if ($res_data) {
                $chunk_list = array_chunk($res_data, 500);
                foreach ($chunk_list as $key => $val) {
                    $insert = Db::connect("mysql")->table('sp_lyp_puhuo_zhiding_goods')->strict(false)->insertAll($val);
                }
            }
        }

        return $Yuncang;

    }

    /*
    检测货号是否已存在
    */
    public function checkPuhuoZdySetGoods($post)
    {

        $ZdyYuncangGoodsModel = new SpLypPuhuoZdyYuncangGoodsModel();

        $return = ['error' => '0', 'goodsno_str' => ''];

        $goods = $post['GoodsNo'] ? explode(' ', $post['GoodsNo']) : [];

        //如果是套装套西，则货品个数必须是双数
        if ($post['if_taozhuang'] == SpLypPuhuoZdySetModel::IF_TAOZHUANG['is_taozhuang'] && (count($goods) % 2)) {
            $return['error'] = 2;
            return $return;
        }

        if ($post['id']) {

            $exist_goods = $ZdyYuncangGoodsModel::where([['Yuncang', '=', $post['Yuncang']], ['set_id', '<>', $post['id']]])->column('GoodsNo');

        } else {

            $exist_goods = $ZdyYuncangGoodsModel::where([['Yuncang', '=', $post['Yuncang']]])->column('GoodsNo');

        }

        $intersect_goods = array_intersect($goods, $exist_goods);
        if ($intersect_goods) {
            $return['error'] = 1;
            $return['goodsno_str'] = implode(',', $intersect_goods);
        }

        return $return;

    }

    /*
    检测货号是否已存在2
    */
    public function checkPuhuoZdySetGoods2($post)
    {

        $ZdyYuncangGoodsModel = new SpLypPuhuoZdyYuncangGoods2Model();

        $return = ['error' => '0', 'goodsno_str' => ''];

        $goods = $post['GoodsNo'] ? explode(' ', $post['GoodsNo']) : [];

        //如果是套装套西，则货品个数必须是双数
        if ($post['if_taozhuang'] == SpLypPuhuoZdySet2Model::IF_TAOZHUANG['is_taozhuang'] && (count($goods) % 2)) {
            $return['error'] = 2;
            return $return;
        }

        if ($post['id']) {

            $exist_goods = $ZdyYuncangGoodsModel::where([['admin_id', '=', session('admin.id')], ['Yuncang', '=', $post['Yuncang']], ['set_id', '<>', $post['id']]])->column('GoodsNo');

        } else {

            $exist_goods = $ZdyYuncangGoodsModel::where([['admin_id', '=', session('admin.id')], ['Yuncang', '=', $post['Yuncang']]])->column('GoodsNo');

        }

        $intersect_goods = array_intersect($goods, $exist_goods);
        if ($intersect_goods) {
            $return['error'] = 1;
            $return['goodsno_str'] = implode(',', $intersect_goods);
        }

        return $return;

    }

    /**
     * 保存各个云仓铺货配置(多店/多省/商品专员/经营模式)
     */
    public function savePuhuoZdySet($data)
    {

        $id = $data['id'];
        $Yuncang = $data['Yuncang'];
        $Selecttype = $data['Selecttype'] ? $data['Selecttype'] : 0;
        $Commonfield = $data['Commonfield'] ?? '';
        $rule_type = $data['rule_type'] ?? 1;
        $if_taozhuang = $data['if_taozhuang'] ?? 2;
        $GoodsNo = $data['GoodsNo'] ? trim($data['GoodsNo']) : '';
        $GoodsNo_arr = [];
        if ($GoodsNo) {
            $GoodsNo_arr = explode(' ', $GoodsNo);
        }

        $add_data = [
            'Yuncang' => $Yuncang,
            'GoodsNo' => $GoodsNo,
            'Selecttype' => $Selecttype,
            'Commonfield' => $Commonfield,
            'rule_type' => $rule_type,
            'if_taozhuang' => $if_taozhuang,
        ];

        $ZdyYuncangGoodsModel = new SpLypPuhuoZdyYuncangGoodsModel();

        Db::startTrans();
        try {
            if ($id) {//修改

                SpLypPuhuoZdySetModel::where([['id', '=', $id]])->update($add_data);
                $ZdyYuncangGoodsModel::where([['set_id', '=', $id]])->delete();

            } else {//新增

                $res = SpLypPuhuoZdySetModel::create($add_data);
                $id = $res->id;

            }

            $insert_data = [];
            if ($GoodsNo_arr) {
                foreach ($GoodsNo_arr as $v_goodsno) {
                    $insert_data[] = [
                        'Yuncang' => $Yuncang,
                        'GoodsNo' => $v_goodsno,
                        'set_id' => $id,
                    ];
                }
            }
            $ZdyYuncangGoodsModel->saveAll($insert_data);

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
        }

        return $id;

    }

    /**
     * 保存各个云仓铺货配置2(多店/多省/商品专员/经营模式)
     */
    public function savePuhuoZdySet2($data)
    {

        $id = $data['id'];
        $Yuncang = $data['Yuncang'];
        $Selecttype = $data['Selecttype'] ? $data['Selecttype'] : 0;
        $Commonfield = $data['Commonfield'] ?? '';
        $rule_type = $data['rule_type'] ?? 1;
        $remain_store = $data['remain_store'] ?? 2;
        $remain_rule_type = $data['remain_rule_type'] ?? 0;
        $if_taozhuang = $data['if_taozhuang'] ?? 2;
        $if_zdmd = $data['if_zdmd'] ?? 1;
        $GoodsNo = $data['GoodsNo'] ? trim($data['GoodsNo']) : '';
        $GoodsNo_arr = [];
        if ($GoodsNo) {
            $GoodsNo_arr = explode(' ', $GoodsNo);
        }
        if ($Selecttype == SpLypPuhuoZdySet2Model::SELECT_TYPE['much_store']) {//单店的情况
            $Commonfield = $Commonfield ? implode(',', explode(' ', $Commonfield)) : '';
        }

        $CustomerNames = null;
        if ($Selecttype == SpLypPuhuoZdySet2Model::SELECT_TYPE['much_merge']) {//组合的情况  处理组合店铺入库
            $Commonfield_arr = $Commonfield ? explode(',', $Commonfield) : [];
            $province_arr = $goods_manager_arr = $mathod_arr = $wenqu_arr = [];
            if ($Commonfield_arr) {
                foreach ($Commonfield_arr as $v_common) {
                    if (strstr($v_common, '省份')) {
                        $province_arr[] = str_replace(['省份-'], [''], $v_common);
                    } elseif (strstr($v_common, '商品专员')) {
                        $goods_manager_arr[] = str_replace(['商品专员-'], [''], $v_common);
                    } elseif (strstr($v_common, '温区')) {
                        $wenqu_arr[] = str_replace(['温区-'], [''], $v_common);
                    } else {
                        $mathod_arr[] = str_replace(['经营模式-'], [''], $v_common);
                    }
                }

                $customer_regionid_notin_text = config('skc.customer_regionid_notin_text');
                $new_customers = Db::connect("mysql")->Query("select CustomerName from customer where Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 
                and CustomerName not in (select 店铺名称 from customer_first);");//剔除新店
                $new_customers = $new_customers ? array_column($new_customers, 'CustomerName') : [];
                $where = [['Region', 'not in', explode(',', $customer_regionid_notin_text)], ['ShutOut', '=', 0], ['CustomerName', 'not in', $new_customers]];
                if ($province_arr) {
                    $where[] = ['State', 'in', $province_arr];
                }
                if ($goods_manager_arr) {
                    $where[] = ['CustomItem17', 'in', $goods_manager_arr];
                }
                if ($mathod_arr) {
                    $where[] = ['Mathod', 'in', $mathod_arr];
                }
                if ($wenqu_arr) {
                    $where[] = ['CustomItem36', 'in', $wenqu_arr];
                }
                $CustomerNames = CustomerModel::where($where)->column('CustomerName');
                $CustomerNames = $CustomerNames ? implode(',', $CustomerNames) : null;
                // print_r([$where, $CustomerNames]);die;
            }
        }

        $add_data = [
            'Yuncang' => $Yuncang,
            'GoodsNo' => $GoodsNo,
            'Selecttype' => $Selecttype,
            'Commonfield' => $Commonfield,
            'rule_type' => $rule_type,
            'remain_store' => $remain_store,
            'remain_rule_type' => $remain_rule_type,
            'if_taozhuang' => $if_taozhuang,
            'if_zdmd' => $if_zdmd,
            'zuhe_customer' => $CustomerNames,
            'admin_id' => session('admin.id')
        ];

        $ZdyYuncangGoodsModel = new SpLypPuhuoZdyYuncangGoods2Model();

        Db::startTrans();
        try {
            if ($id) {//修改

                SpLypPuhuoZdySet2Model::where([['id', '=', $id]])->update($add_data);
                $ZdyYuncangGoodsModel::where([['set_id', '=', $id]])->delete();

            } else {//新增

                $res = SpLypPuhuoZdySet2Model::create($add_data);
                $id = $res->id;

            }

            $insert_data = [];
            if ($GoodsNo_arr) {
                foreach ($GoodsNo_arr as $v_goodsno) {
                    $insert_data[] = [
                        'Yuncang' => $Yuncang,
                        'GoodsNo' => $v_goodsno,
                        'set_id' => $id,
                        'admin_id' => session('admin.id')
                    ];
                }
            }
            $ZdyYuncangGoodsModel->saveAll($insert_data);

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
        }

        return $id;

    }

    /**
     * 删除铺货配置(多店/多省/商品专员/经营模式)
     */
    public function delPuhuoZdySet($id)
    {

        Db::startTrans();
        try {

            $res = SpLypPuhuoZdySetModel::where([['id', '=', $id]])->delete();
            SpLypPuhuoZdyYuncangGoodsModel::where([['set_id', '=', $id]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
        }

    }

    /**
     * 删除铺货配置2(多店/多省/商品专员/经营模式)
     */
    public function delPuhuoZdySet2($id)
    {

        Db::startTrans();
        try {

            $res = SpLypPuhuoZdySet2Model::where([['id', '=', $id]])->delete();
            SpLypPuhuoZdyYuncangGoods2Model::where([['set_id', '=', $id]])->delete();

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();
        }

    }

    /**
     * 删除剔除指定款货品等级配置
     */
    public function del_ti_goods_type($id)
    {

        return SpLypPuhuoTiGoodsTypeModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 检测是否已存在
     */
    public function check_ti_goods_type($post)
    {

        return SpLypPuhuoTiGoodsTypeModel::where([['GoodsLevel', '=', $post['GoodsLevel']]])->field('id')->find();

    }

    /**
     * 保存剔除指定款货品等级配置
     */
    public function save_ti_goods_type($data)
    {

        $id = null;
        $msg = '';
        if ($data) {
            $id = $data['id'];
            if ($id != '') {//更新

                $if_exist = SpLypPuhuoTiGoodsTypeModel::where([['id', '<>', $id], ['GoodsLevel', '=', $data['GoodsLevel']]])->field('id')->find();
                if ($if_exist) {
                    $msg = '货品等级已存在，请检查';
                } else {
                    SpLypPuhuoTiGoodsTypeModel::where([['id', '=', $id]])->update($data);
                }

            } else {//插入

                $id = SpLypPuhuoTiGoodsTypeModel::create($data);
                $id = $id->id;

            }
        }
        return ['id' => $id, 'msg' => $msg];

    }

    /**
     * 获取手动铺货执行记录
     */
    public function get_puhuo_run()
    {

        $res = SpLypPuhuoRunModel::where([['admin_id', '=', session('admin.id')]])->order('id desc')->find();
        $res = $res ? $res->toArray() : [];
        return $res;

    }

    /**
     * 获取铺货货品个数
     */
    public function get_puhuo_goods_count()
    {

        return SpLypPuhuoZdyYuncangGoods2Model::where(['admin_id' => session('admin.id')])->count();

    }

    /**
     * 获取钉钉推送用户
     */
    public function get_dingding_user()
    {

        $dd_user = $this->easy_db->query("select name as name, userid as value from dd_user  group by userid;");
        $sel_dd_user = SpLypPuhuoDdUserModel::where(['admin_id' => session('admin.id')])->column('userid');
        foreach ($dd_user as &$v_user) {
            if (in_array($v_user['value'], $sel_dd_user)) {
                $v_user['selected'] = true;
            }
        }
        return $dd_user;

    }

    /**
     * 保存钉钉推送用户
     */
    public function save_dingding_user($dingding)
    {

//        $this->easy_db->query("truncate table sp_lyp_puhuo_dd_user;");
        $this->easy_db->table('sp_lyp_puhuo_dd_user')->where(['admin_id' => session('admin.id')])->delete();
        $select_dd_user = DdUserModel::where([['userid', 'in', $dingding ? explode(',', $dingding) : []]])->field('userid,name')->select();
        $select_dd_user = $select_dd_user ? $select_dd_user->toArray() : [];
        if ($select_dd_user) {
            foreach ($select_dd_user as &$v_sel) {
                $v_sel['admin_id'] = session('admin.id');
                SpLypPuhuoDdUserModel::create($v_sel);
            }
        }

    }

    /**
     * 处理草稿数据
     */
    public function deal_caogao($params)
    {

        $caogao_arr = $params['caogao_arr'] ?? [];

        $count = SpLypPuhuoEndDataModel::where([])->count();
        if (count($caogao_arr) != $count) {
            $res_end_data = SpLypPuhuoEndDataModel::where([['uuid', 'in', $caogao_arr], ['Stock_Quantity_puhuo', '>', 0]])->select();
        } else {
            $res_end_data = SpLypPuhuoEndDataModel::where([['is_total', '<>', 1], ['Stock_Quantity_puhuo', '>', 0]])->select();
        }
        //草稿入库
        $res_end_data = $res_end_data ? $res_end_data->toArray() : [];
        if (!$res_end_data) {
            return json(["code" => "400", "msg" => "请选择可铺数据保存", "data" => []]);
        }
        if ($res_end_data) {
            foreach ($res_end_data as &$v_end_data) {
                unset($v_end_data['create_time']);
            }
        }

        $chunk_list = array_chunk($res_end_data, 500);
        foreach ($chunk_list as $key => $val) {
            $uuid_arr = array_column($val, 'uuid');
            SpLypPuhuoEndDataModel::where([['uuid', 'in', $uuid_arr]])->update(['is_delete' => 1]);
            $insert = $this->easy_db->table('sp_lyp_puhuo_caogao')->strict(false)->insertAll($val);
        }

        return json(["code" => "200", "msg" => "保存成功", "data" => []]);

    }

    /**
     *保存修订
     * @return void
     */
    public function revise()
    {

        try {
            $where = [
                ['is_total', '=', 0],
                ['Stock_Quantity_puhuo', '>', 0],
                ['admin_id', '=', session('admin.id')],
            ];
            $list = SpLypPuhuoEndDataModel::where(function ($q) use ($where) {
                $q->where($where);
            })->whereOr(function ($q) use ($where) {
                $q->where([['admin_id', '=', session('admin.id')], ['is_total', '=', '1'], ['CustomerName', '=', '余量']]);
            })->field('*')
                ->select()->toArray();
            $stockDb = $this->easy_db->table('sp_lyp_puhuo_end_data')->where([
                ['is_total', '=', 1],
                ['CustomerName', '<>', '余量'],
                ['Stock_Quantity_puhuo', '>', 0],
                ['admin_id', '=', session('admin.id')],
            ])->select()->toArray();


            //库存
            $stock = [];
            foreach ($stockDb as $key => $item) {
                $stock[$item['WarehouseName'] . $item['GoodsNo']] = $item;
            }
            $yuiliu = [];
            $Dbyuliu = $this->easy_db->table('sp_lyp_puhuo_warehouse_reserve_config')->select()->toArray();
            foreach ($Dbyuliu as $key => &$item) {
                $yuiliu[$item['config_str']] = [
//                    'WarehouseName' => $item['config_str'],
                    'Stock_00_puhuo' => $item['_28'],
                    'Stock_29_puhuo' => $item['_29'],
                    'Stock_30_puhuo' => $item['_30'],
                    'Stock_31_puhuo' => $item['_31'],
                    'Stock_32_puhuo' => $item['_32'],
                    'Stock_33_puhuo' => $item['_33'],
                    'Stock_34_puhuo' => $item['_34'],
                    'Stock_35_puhuo' => $item['_35'],
                    'Stock_36_puhuo' => $item['_36'],
                    'Stock_38_puhuo' => $item['_38'],
                    'Stock_40_puhuo' => $item['_40'],
                    'Stock_42_puhuo' => $item['_42'],
                ];

            }
            foreach ($list as &$item) {
                $item['wenqu'] = '';
                $item['CustomItem14'] = '';
                $item['CustomItem25'] = '';
                $item['CustomItem45'] = '';
                if ($item['CustomerName'] == '余量') {

                    // 1. 方案
//                    $yunc = $this->easy_db->table('sp_lyp_puhuo_yuncangkeyong')
//                        ->where(['GoodsNo' => $item['GoodsNo'], 'WarehouseName' => $item['WarehouseName'], 'admin_id' => $item['admin_id']])->find();
//                    $item['Stock_00_puhuo'] = $yunc['Stock_00'];
//                    $item['Stock_29_puhuo'] = $yunc['Stock_29'];
//                    $item['Stock_30_puhuo'] = $yunc['Stock_30'];
//                    $item['Stock_31_puhuo'] = $yunc['Stock_31'];
//                    $item['Stock_32_puhuo'] = $yunc['Stock_32'];
//                    $item['Stock_33_puhuo'] = $yunc['Stock_33'];
//                    $item['Stock_34_puhuo'] = $yunc['Stock_34'];
//                    $item['Stock_35_puhuo'] = $yunc['Stock_35'];
//                    $item['Stock_36_puhuo'] = $yunc['Stock_36'];
//                    $item['Stock_38_puhuo'] = $yunc['Stock_38'];
//                    $item['Stock_40_puhuo'] = $yunc['Stock_40'];
//                    $item['Stock_42_puhuo'] = $yunc['Stock_42'];
//                    $item['Stock_Quantity_puhuo'] = $yunc['Stock_Quantity'] - ($yunc['Stock_Quantity_puhuo']- $item['Stock_Quantity_puhuo']);

                    //2. 方案
                    $Stock_Quantity_puhuo = 0;
                    $configYl = $yuiliu[$item['WarehouseName']];
                    foreach ($item as $son => $son_v) {
                        if (in_array($son, array_keys($configYl))) {
                            $x = -($stock[$item['WarehouseName'] . $item['GoodsNo']][$son] / ($configYl[$son] / 100 - 1));
                            $x = (int)round($x, 0);
                            $x = $x - ($stock[$item['WarehouseName'] . $item['GoodsNo']][$son] - $item[$son]);
                            $Stock_Quantity_puhuo += $x - $item[$son];
                            $item[$son] = $x;
                        }
                    }
                    $item['Stock_Quantity_puhuo'] += $Stock_Quantity_puhuo;
                } else {
                    $cus = $this->erp->table('ErpCustomer')->where(['CustomerId' => $item['CustomerId']])->field('CustomItem14,CustomItem25,CustomItem45')->find();
                    $puhuoConfig = $this->easy_db->table('sp_lyp_puhuo_config')->where(1)->find();

                    if ($puhuoConfig['if_hottocold'] == 2) {
                        $wenqu = $this->easy_db->table('sp_lyp_puhuo_coldtohot')->where([['province', 'like', '%' . $item['State'] . '%']])->find();
                    } else {
                        $wenqu = $this->easy_db->table('sp_lyp_puhuo_hottocold')->where([['province', 'like', '%' . $item['State'] . '%']])->find();
                    }
                    $item['wenqu'] = $wenqu['wenqu'] ?? '';
                    $item['CustomItem14'] = $cus['CustomItem14'] ?? '';
                    $item['CustomItem25'] = $cus['CustomItem25'] ?? '';
                    $item['CustomItem45'] = $cus['CustomItem45'] ?? '';

                }

            }

            $this->easy_db->table('sp_lyp_puhuo_end_data_revise')->where('admin_id', session('admin.id'))->delete();
            $chunk_list = array_chunk($list, 500);
            foreach ($chunk_list as $key => $val) {
                $insert = $this->easy_db->table('sp_lyp_puhuo_end_data_revise')->strict(false)->insertAll($val);
            }
        } catch (\Exception $e) {
            dd($e);
        }


    }


    public function caogao_order_no($where = [])
    {

        $config = $this->mysql->table('sp_lyp_puhuo_excel_config')->where(1)->find();
        $config['特殊店铺'] = array_column(json_decode($config['特殊店铺'], true), null, 'CustomerName');
        $config['商品负责人'] = json_decode($config['商品负责人'], true);

        $data = [];
        $exDb = $this->mysql->table('sp_lyp_puhuo_caogao')->where($where)->where('admin_id', session('admin.id'))->select()->toArray();

        $CustomersKV = [];
        foreach ($exDb as $item) {
            $aa = [
                'WarehouseName' => $item['WarehouseName'],
                'WarehouseCode' => $item['WarehouseCode'],
                'CustomerName' => $item['CustomerName'],
                'CustomItem17' => $item['CustomItem17'],
            ];
            $CustomersKV[$item['WarehouseName'] . '_' . $item['CustomerName']] = $aa;
        }
//        $CustomersKV = array_column($exDb, null, 'CustomerName');
//        $Customers = array_values(array_unique(array_column($exDb, 'CustomerName')));
//        $Customers = ['安康一店', '阿拉尔一店'];
        $CustomItem17Arr = array_values(array_unique(array_column($exDb, 'CustomItem17')));

        $numArr = [];
        foreach ($CustomItem17Arr as $item) {
            $numArr[$item] = 1;

        }

        foreach ($CustomItem17Arr as $item) {
            $sortWhere = [
                'CustomItem17' => $item,
                'date' => date('Y-m-d')
            ];
            $sortDb = $this->mysql->table('sp_lyp_puhuo_excel_data')->where($sortWhere)->where('admin_id', session('admin.id'))->order('sort desc')->value('sort');
            if ($sortDb) {
                $numArr[$item] = (int)$sortDb + 1;
            }
        }

        foreach ($CustomersKV as $cus => $item) {

            $cus_num = 1; //店铺包数
            $yk_con = isset($config['特殊店铺'][$item['CustomerName']]['YK']) ? $config['特殊店铺'][$item['CustomerName']]['YK'] : $config['衣裤'];
            $xl_con = isset($config['特殊店铺'][$item['CustomerName']]['XZ']) ? $config['特殊店铺'][$item['CustomerName']]['XZ'] : $config['鞋子'];
            //衣裤
            $clothesPants = $this->mysql->table('sp_lyp_puhuo_caogao')->where($where)
                ->where(['admin_id' => session('admin.id'), 'CustomerName' => $item['CustomerName'], 'WarehouseName' => $item['WarehouseName']])
                ->whereIn('CategoryName1', ['外套', '内搭', '下装'])
                ->order('CategoryName1 ASC')->select()->toArray();
            $shoes = $this->mysql->table('sp_lyp_puhuo_caogao')->where($where)
                ->where(['admin_id' => session('admin.id'), 'CustomerName' => $item['CustomerName'], 'WarehouseName' => $item['WarehouseName']])
                ->where('CategoryName1', '鞋履')
                ->select()->toArray();
            $total = 0; //总件数
            //处理衣裤

            foreach ($clothesPants as $cp_v) {

                $clothesPantsArr = $cp_v;
                if ($cp_v['Stock_Quantity_puhuo'] < $yk_con) { //单货号小于配置
                    $total = $total + $cp_v['Stock_Quantity_puhuo'];
                    $shoper = $config['商品负责人'][$item['CustomItem17']] ?? 'SG';
                    if ($total <= $yk_con * $cus_num) {

                        $clothesPantsArr['sort'] = $numArr[$item['CustomItem17']];
                        $clothesPantsArr['uuid'] = $shoper . $config[$cp_v['xingzhi']] . date('Ymd') . str_pad($numArr[$item['CustomItem17']], 3, '0', STR_PAD_LEFT);
                        $data[$item['CustomerName']][] = $clothesPantsArr;
                    } else {
                        $cus_num++;
                        $numArr[$item['CustomItem17']]++;
                        $clothesPantsArr['sort'] = $numArr[$item['CustomItem17']];
                        $clothesPantsArr['uuid'] = $shoper . $config[$cp_v['xingzhi']] . date('Ymd') . str_pad($numArr[$item['CustomItem17']], 3, '0', STR_PAD_LEFT);
                        $data[$item['CustomerName']][] = $clothesPantsArr;
                    }

                }
            }

            $no = 0;
            //鞋子
            foreach ($shoes as $s_k => $s_v) {
                if ($s_v['Stock_Quantity_puhuo'] <= $xl_con && isset($data[$item['CustomerName']][$s_k])) { //加到原来的
                    $shoesArr = $s_v;
                    $shoesArr['sort'] = $data[$item['CustomerName']][$s_k]['sort'];
                    $shoesArr['uuid'] = $data[$item['CustomerName']][$s_k]['uuid'];
                    $data[$item['CustomerName']][] = $shoesArr;
                } else {
                    $shoper = $config['商品负责人'][$item['CustomItem17']] ?? 'SG';

                    if ($no != 0) {
                        $shoesArr = $s_v;
                        $shoesArr['sort'] = $no;
                        $shoesArr['uuid'] = $shoper . $config[$s_v['xingzhi']] . date('Ymd') . str_pad($no, 3, '0', STR_PAD_LEFT);
                        $data[$item['CustomerName']][] = $shoesArr;
                    } else {
                        $numArr[$item['CustomItem17']]++;
                        $no = $numArr[$item['CustomItem17']];
                        $shoesArr = $s_v;
                        $shoesArr['sort'] = $numArr[$item['CustomItem17']];
                        $shoesArr['uuid'] = $shoper . $config[$s_v['xingzhi']] . date('Ymd') . str_pad($numArr[$item['CustomItem17']], 3, '0', STR_PAD_LEFT);
                        $data[$item['CustomerName']][] = $shoesArr;

                    }


                }

            }
            //更换云仓店铺 包数更换
            $numArr[$item['CustomItem17']]++;

        }
        $return = [];
        foreach ($data as $item) {
            foreach ($item as $son) {
                $return[] = $son;
            }
        }

        $CustomItem17Sort = array_column($return, 'CustomItem17');
        $sortArr = array_column($return, 'sort');
        array_multisort($CustomItem17Sort, SORT_ASC, $sortArr, SORT_ASC, $return);

        return $return;

    }
    public function Size($GoodsNo)
    {

        $sql = "SELECT
    bgs.Size
FROM
    ErpGoods a
    LEFT JOIN ErpGoodsSize gs ON gs.GoodsId= a.GoodsId
    LEFT JOIN ErpBaseGoodsSize bgs ON bgs.SizeId= gs.SizeId 
WHERE
    gs.IsEnable=1 and
    a.GoodsNo= '{$GoodsNo}'
ORDER BY
    bgs.ViewOrder ASC";

        $erpSize = Db::connect('sqlsrv')->query($sql);
        $erpSize = array_column($erpSize, 'Size');

        return $erpSize;


    }

    public function Color($GoodsNo)
    {

        $erp = Db::connect('sqlsrv')->table('ErpGoods')->alias('a')->where('a.GoodsNo', $GoodsNo)
            ->leftjoin('ErpGoodsColor c', 'c.GoodsId=a.GoodsId')
            ->field('a.*,c.ColorDesc, c.ColorCode')
            ->find();

        return $erp;

    }


}