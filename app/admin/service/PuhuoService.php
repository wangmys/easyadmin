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
use app\admin\model\bi\SpLypPuhuoZdyYuncangGoodsModel;
use app\admin\model\bi\SpLypPuhuoOnegoodsRuleModel;
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
     * 获取各云仓 自定义铺货货品 配置列表
     */
    public function get_zdy_goods($Yuncang) {

        $res = SpLypPuhuoZdySetModel::where([['Yuncang', '=', $Yuncang]])->field('id,Yuncang,GoodsNo,Selecttype,Commonfield')->select();
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

    //获取多店、多省、商品专员、经营模式 下拉数据
    public function get_select_data($yuncang) {

        $customer_regionid_notin_text = config('skc.customer_regionid_notin_text');
        $customer_list = $this->easy_db->Query("select CustomerName as name, CustomerId as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by CustomerName;");
        $province_list = $this->easy_db->Query("select State as name, State as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by State;");
        $goods_manager_list = $this->easy_db->Query("select CustomItem17 as name, CustomItem17 as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by CustomItem17;");
        $mathod_list = $this->easy_db->Query("select Mathod as name, Mathod as value from customer where CustomItem15='{$yuncang}' and Mathod in ('直营', '加盟') and Region not in ($customer_regionid_notin_text) and ShutOut=0 group by Mathod;");//[['name' => '加盟', 'value' => '加盟'], ['name' => '直营', 'value' => '直营']];
        return ['customer_list' => $customer_list, 'province_list' => $province_list, 'goods_manager_list' => $goods_manager_list, 'mathod_list' => $mathod_list];

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

    /*
    检测货号是否已存在
    */
    public function checkPuhuoZdySetGoods($post) {

        $ZdyYuncangGoodsModel = new SpLypPuhuoZdyYuncangGoodsModel();

        $return = ['error'=>'0', 'goodsno_str'=>''];
        if ($post['id']) {

            $exist_goods = $ZdyYuncangGoodsModel::where([['Yuncang', '=', $post['Yuncang']], ['set_id', '<>', $post['id']]])->column('GoodsNo');
            
        } else {
            
            $exist_goods = $ZdyYuncangGoodsModel::where([['Yuncang', '=', $post['Yuncang']]])->column('GoodsNo');

        }

        $goods = $post['GoodsNo'] ? explode(' ', $post['GoodsNo']) : [];
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
    public function savePuhuoZdySet($data) {

        $id = $data['id'];
        $Yuncang = $data['Yuncang'];
        $Selecttype = $data['Selecttype'] ? $data['Selecttype'] : 0;
        $Commonfield = $data['Commonfield'] ?? '';
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
     * 删除铺货配置(多店/多省/商品专员/经营模式)
     */
    public function delPuhuoZdySet($id) {

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