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
use app\common\traits\Singleton;
use think\facade\Db;

class PuhuoService
{

    use Singleton;
    protected $easy_db;

    public function __construct() {
        $this->easy_db = Db::connect("mysql");
    }

    public function puhuo_index($params) {

        $pageLimit = $params['limit'] ?? 15;//每页条数
        $page = $params['page'] ?? 1;//当前页
        $WarehouseName = $params['WarehouseName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';
        $GoodsNo = $params['GoodsNo'] ?? '';//货号
        $CustomerName = $params['CustomerName'] ?? '';//店铺名称
        $is_puhuo = $params['is_puhuo'] ?? '';

        $where = $list = [];
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
        if ($is_puhuo) {
            if ($is_puhuo == '可铺') {//可铺

                $where[] = ['Stock_Quantity_puhuo', '>', 0];
                $list = SpLypPuhuoEndDataModel::where($where)->field('*')
                ->paginate([
                    'list_rows'=> $pageLimit,
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
                    'list_rows'=> $pageLimit,
                    'page' => $page,
                ]);
                $list = $list ? $list->toArray() : [];

            }
        } else {

            $list = SpLypPuhuoEndDataModel::where($where)->field('*')
                ->paginate([
                    'list_rows'=> $pageLimit,
                    'page' => $page,
                ]);
                $list = $list ? $list->toArray() : [];

        }
        // print_r([$pageLimit, $page]);die;
        
        $data = [
            'count' => $list ? $list['total'] : 0,
            'data'  => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    public function getXmMapSelect() {

        $WarehouseName = $this->easy_db->query("select WarehouseName as name, WarehouseName as value from sp_lyp_puhuo_cur_log  group by WarehouseName;");
        $CategoryName1 = $this->easy_db->query("select CategoryName1 as name, CategoryName1 as value from sp_lyp_puhuo_wait_goods where CategoryName1!='' and (TimeCategoryName2 like '%秋%' or TimeCategoryName2 like '%冬%') group by CategoryName1;");
        $GoodsNo = $this->easy_db->query("select GoodsNo as name, GoodsNo as value from sp_lyp_puhuo_end_data  group by GoodsNo;");
        $CustomerName = $this->easy_db->query("select CustomerName as name, CustomerName as value from sp_lyp_puhuo_end_data  group by CustomerName;");

        return ['WarehouseName' => $WarehouseName, 'CategoryName1' => $CategoryName1, 'GoodsNo'=>$GoodsNo, 'CustomerName'=>$CustomerName, 'is_puhuo' => [['name'=>'可铺', 'value'=>'可铺'], ['name'=>'不可铺', 'value'=>'可铺']]];

    }

    //铺货-统计
    public function puhuo_statistic($params) {

        $WarehouseName = $params['WarehouseName'] ?? '';
        $CategoryName1 = $params['CategoryName1'] ?? '';

        $where = $where_store = [];
        if ($WarehouseName) {
            $where[] = ['lpwg.WarehouseName', 'in', $WarehouseName];
        }
        else {//暂时使用，目前只有贵阳云仓数据 上线后去掉 20230823

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

        return ['nd'=>$nd, 'wt'=>$wt, 'xz'=>$xz, 'xl'=>$xl, 'store_num'=>$store_num];

    }

    /**
     * 获取铺货配置
     */
    public function get_puhuo_config() {

        return SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();

    }

    /**
     * 保存仓库预留参数配置/保存门店上铺货连码标准配置/保存仓库齐码参数配置
     */
    public function save_warehouse_config($data) {

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
     * 获取评分标准
     */
    public function get_puhuo_score($config_str) {

        $res = SpLypPuhuoScoreModel::where([['config_str', '=', $config_str]])->select();
        $res = $res ? $res->toArray() : [];
        return $res;

    }

    /**
     * 检测是否已存在
     */
    public function check_customer_level($post) {

        return SpLypPuhuoScoreModel::where([['config_str', '=', $post['config_str']], ['key', '=', $post['key']]])->field('id')->find();

    }

    /**
     * 保存店铺评分标准配置
     */
    public function save_customer_level($data) {

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
    public function del_customer_level($id) {

        return SpLypPuhuoScoreModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 获取气温评分标准
     */
    public function get_qiwen_score($str='coldtohot') {

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
    public function check_coldtohot($post) {

        return SpLypPuhuoColdtohotModel::where([['yuncang', '=', $post['yuncang']], ['province', '=', $post['province']], ['wenqu', '=', $post['wenqu']]])->field('id')->find();

    }

    /**
     * 保存气温评分标准配置（冷到热）
     */
    public function save_coldtohot($data) {

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
        return ['id'=>$id, 'msg'=>$msg];

    }

    /**
     * 检测是否已存在（热到冷）
     */
    public function check_hottocold($post) {

        return SpLypPuhuoHottocoldModel::where([['yuncang', '=', $post['yuncang']], ['province', '=', $post['province']], ['wenqu', '=', $post['wenqu']]])->field('id')->find();

    }

    /**
     * 保存气温评分标准配置（热到冷）
     */
    public function save_hottocold($data) {

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
        return ['id'=>$id, 'msg'=>$msg];

    }

    /**
     * 删除气温评分标准配置（冷到热）
     */
    public function del_coldtohot($id) {

        return SpLypPuhuoColdtohotModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 删除气温评分标准配置(热到冷）
     */
    public function del_hottocold($id) {

        return SpLypPuhuoHottocoldModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 获取指定款分类
     */
    public function get_ti_goods_type() {

        $res = SpLypPuhuoTiGoodsTypeModel::where([])->column('id,GoodsLevel', 'GoodsLevel');
        return $res;

    }

    /**
     * 获取指定款分类
     */
    public function get_zhiding_goods($Yuncang) {

        $res = SpLypPuhuoZhidingGoodsModel::where([['Yuncang', '=', $Yuncang]])->column('GoodsNo');
        $res = $res ? implode(' ', $res) : '';
        return $res;

    }

    /**
     * 保存各个云仓指定铺货货品配置
     */
    public function saveZhidingGoodsConfig($data) {

        $Yuncang = $data['Yuncang'];
        $GoodsNo = $data['GoodsNo'] ? explode(' ', $data['GoodsNo']) : [];

        //先删除旧的配置
        SpLypPuhuoZhidingGoodsModel::where([['Yuncang', '=', $Yuncang]])->delete();
        //重新入库
        $res_data = [];
        if ($GoodsNo) {
            foreach ($GoodsNo as $v_goods) {
                $v_goods = $v_goods ? trim($v_goods): '';
                if ($v_goods) {
                    $res_data[] = ['Yuncang'=>$Yuncang, 'GoodsNo'=>$v_goods];
                }
            }

            if ($res_data) {
                $chunk_list = array_chunk($res_data, 500);
                foreach($chunk_list as $key => $val) {
                    $insert = Db::connect("mysql")->table('sp_lyp_puhuo_zhiding_goods')->strict(false)->insertAll($val);
                }
            }
        }

        return $Yuncang;

    }

    /**
     * 删除剔除指定款货品等级配置
     */
    public function del_ti_goods_type($id) {

        return SpLypPuhuoTiGoodsTypeModel::where([['id', '=', $id]])->delete();

    }

    /**
     * 检测是否已存在
     */
    public function check_ti_goods_type($post) {

        return SpLypPuhuoTiGoodsTypeModel::where([['GoodsLevel', '=', $post['GoodsLevel']]])->field('id')->find();

    }

    /**
     * 保存剔除指定款货品等级配置
     */
    public function save_ti_goods_type($data) {

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
        return ['id'=>$id, 'msg'=>$msg];

    }


}