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

namespace app\common\logic\inventory;

use AlibabaCloud\SDK\Dingtalk\Vlink_1_0\Models\GetFollowerAuthInfoResponseBody\result\authInfo\mobile;
use app\common\constants\AdminConstant;
use EasyAdmin\tool\CommonTool;
use think\facade\Db;
use app\admin\model\dress\Yinliu;
use app\admin\model\dress\YinliuQuestion;
use app\admin\model\dress\YinliuStore;
use app\admin\model\dress\Accessories;
use app\admin\model\dress\DressHead;
use app\admin\model\dress\DressWarStock;

/**
 * 逻辑层
 * Class AuthService
 * @package app\common\logic
 */
class DressLogic
{


    /***
     * 构造方法
     * DressLogic constructor.
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {
        $this->yinliu = new Yinliu;
        $this->question = new YinliuQuestion;
        $this->yinliuStore = new YinliuStore;
        $this->dressHead = new DressHead;
        $this->warStock = new DressWarStock;
    }

    /**
     * 对比配饰库存完成率
     * @param string $start_date
     * @param string $end_date
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function contrastYinliuFinishRate($start_date = '' , $end_date = '')
    {
        if(empty($start_date) || empty($end_date)) return [];
        if(strtotime($end_date) > time()){
            return [];
        }
        // 查询所有商品负责人
        $charge_list = $this->yinliuStore->where([
            'Date' => $start_date
        ])->select();
        // 获取配置
        $config = sysconfig('stock_warn');
        // 配饰列表
        $goods_list = AdminConstant::ACCESSORIES_LIST;
        // 负责人店铺完成信息
        $charge_finish_info = [];
        foreach ($charge_list as $key => $value){
            $info = [
                '商品负责人' => $value['商品负责人']
            ];
            // 查询结束时间节点时开始时间配饰库存不达标的店铺数
            foreach ($goods_list as $kk => $vv){
                if($value[$vv]){
                    // 获取标准判断
                    $standard = $config[$vv];
                    // 开始时间配饰不合格店铺
                    $not_list = explode(',',$value[$vv]);
                    // 查询结束时间时对应商品库存低于标准的店铺数量
                    $store_list = $this->yinliu->where([
                        '商品负责人' => $value['商品负责人'],
                        'Date' => $end_date,
                        '店铺名称' => $not_list
                    ])->where(function ($q)use($vv,$standard){
                        if($standard > 0){
                            $q->whereNull($vv);
                        }
                        $q->whereOr($vv,'<',$standard);
                    })->column('店铺名称');
                    // 计算当前配饰店铺未完成率 = 结束店铺数量 / 开始店铺数量
                    $store_count = count($store_list);
                    $f_count = count($not_list) - $store_count;
                    $info[$vv] = count($not_list);
                    $info["{$vv}_1"] = $f_count;
                    $info["{$vv}_2"] = $store_count;
                }else{
                    $info[$vv] = '';
                    $info["{$vv}_1"] = '';
                    $info["{$vv}_2"] = '';
                }
            }
             $charge_finish_info[] = $info;
        }
        return $charge_finish_info;
    }


    /**
     * 获取对比结果
     * @param $get
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getComparisonResult($get)
    {
        // 指定商品负责人
        $charge = $get['商品负责人']??'曹太阳';
        // 获取开始至结束日期
        $date_all = getThisDayToStartDate();
        // 开始日期
        $start_date = $date_all[0];
        // 结束日期
        $end_date = $date_all[1];
        // 查询负责人待完成店铺
        $model = new YinliuStore;
        // 查询开始数据
        $info = $model->where([
            '商品负责人' => $charge,
            'Date' => $start_date
        ])->find();
        $info_1 = $model->where([
            '商品负责人' => $charge,
            'Date' => $end_date
        ])->find();
        $merge_data = [];
        $list = AdminConstant::ACCESSORIES_LIST;
         // 获取配置
        $config = sysconfig('stock_warn');
        $arr1 = [];
        if($info && $info_1){
            $info = $info->toArray();
            foreach ($info as $k => $v){
                if(in_array($k,$list)){
                    // 获取标准判断
                    $standard = $config[$k];
                    $arr = ['商品负责人' => $info['商品负责人']];
                    $arr['配饰'] = $k;
                    if(empty($v)){
                        $store_arr = [];
                    }else{
                        $store_arr = explode(',',$v);
                    }
                    $arr['问题店铺'] = count($store_arr);
                    // 剩余店铺
                    $store_list = Yinliu::where([
                        '商品负责人' => $info['商品负责人'],
                        'Date' => $end_date,
                        '店铺名称' => $store_arr
                    ])->where(function ($q)use($k,$standard){
                        if($standard > 0){
                            $q->whereNull($k);
                        }
                        $q->whereOr($k,'<',$standard);
                    })->column('店铺名称');
                    $arr['剩余店铺'] = count($store_list);
                    $arr['已处理'] = $arr['问题店铺'] - $arr['剩余店铺'];
                    $arr1[] = $arr;
                }
            }
            return $arr1;
        }
    }

    /**
     * 获取表头字段
     * @return array
     */
    public function getHead()
    {
        $head = $model = $this->dressHead::where(['state' => 1])->column('name,field,stock','id');
        // 固定字段
        $column = AdminConstant::YINLIU_COLUMN;
        $head_list = [];
        foreach ($head as $k => $v){
            $v['field'] = array_map(function($val)use($v){
                $item = explode(',',$v['field']);
                if(in_array($val['name'],$item)) $val['selected'] = true;
                return $val;
            }, $column);
            $head_list[] = $v;
        }
        return $head_list;
    }

    /**
     * 获取所有省份
     */
    public function getProvince()
    {
        $provinceName = Accessories::whereNotIn('省份', ['合计'])->group('省份')->column('省份');
        $provinceList = [];
        foreach ($provinceName as $k => $v){
            $provinceList[] = [
                'name' => $v,
                'value' => $v
            ];
        }
        return $provinceList;
    }

    /**
     * 设置默认省份选中
     */
    public function setProvince($province_str,$province_list)
    {
        $list = explode(',',$province_str);
        foreach ($province_list as $key => &$v){
            if(in_array($v['name'],$list)){
                $v['selected'] = true;
            }
        }
        return $province_list;
    }

     // 拼接查询字段 SQL 语句
    public function getFieldSQL()
    {
        $head = $this->dressHead->column('name,field,stock', 'id');
        $_field_default = ['省份','店铺名称','商品负责人'];
        $_field = array_merge($_field_default, array_column($head, 'name'));
        $field = implode(',', $_field_default);
        foreach ($head as $k => $v) {
            $field_str = str_replace(',', ' + ', $v['field']);
            $field .= ",( $field_str ) as {$v['name']}";
        }
        $field = trim($field, ',');
        return $field;
    }

    /**
     * 保存表头数
     * @param $data
     * @return bool
     */
    public function saveHead($data)
    {
        $this->dressHead->save($data);
        return $this->dressHead->id;
    }

    /**
     * 获取条件列表
     */
    public function getSelectList()
    {
        $default_select = [];
        $fields = [
             // 设置省份列表
            'province_list' => '省份',
            // 设置省份列表
            'shop_list' => '店铺名称',
            // 设置省份列表
            'charge_list' => '商品负责人'
        ];
        $model = (new \app\admin\model\dress\Accessories);
        foreach ($fields as $k => $v){
            $list = $model->group($v)->whereNotIn($v,'合计')->column($v);
            $default_select[$v] =  array_combine($list,$list);
        }
        return $default_select;
    }

    /**
     * 获取所有门店
     */
    public function getStore()
    {
        $storeName = Accessories::whereNotIn('店铺名称', ['合计'])->group('店铺名称')->column('店铺名称');
        $storeList = [];
        foreach ($storeName as $k => $v){
            $storeList[] = [
                'name' => $v,
                'value' => $v
            ];
        }
        return $storeList;
    }

    /**
     * 获取预警库存配置
     */
    public function warStockItem()
    {
        // 配置列表
        $config_list = $this->warStock->column('province,content');
        $province_config_list = [];
        foreach ($config_list as $k => $v){
            $province_list = explode(',',$v['province']);
            $config_item = json_decode($v['content'],true);
            if(isset($config_item['省份'])) unset($config_item['省份']);
            $province_config_list[] = [
                '省份' => $province_list,
                '_data' => $config_item
            ];
        }
        return $province_config_list;
    }

    /**
     * 保存预警库存配置
     */
    public function saveWarStock($data)
    {
        // 启动事务
        Db::startTrans();
        // 保存数据前,删除所有数据
        try {
            // 清空所有数据
            $this->warStock->where('id','>',0)->delete(true);
            $this->warStock->saveAll($data);
            // 提交事务
            Db::commit();
            return true;
        }catch (\Exception $e){
            // 回滚事务
            Db::rollback();
            return false;
        }
        return false;
    }
}