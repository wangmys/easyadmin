<?php

namespace app\admin\service\puhuo;

use app\admin\model\bi\SpLypPuhuoEndDataModel;
use app\admin\model\bi\SpLypPuhuoEndDataReviseModel;
use think\facade\Db;

class ReviseService
{


    protected $mysql;

    public function __construct()
    {
        $this->mysql = Db::connect("mysql");
    }

    public function list($params)
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

        $list = SpLypPuhuoEndDataReviseModel::where($where)->field('*')
            ->paginate([
                'list_rows' => $pageLimit,
                'page' => $page,
            ]);
        $list = $list ? $list->toArray() : [];

        $data = [
            'count' => $list ? $list['total'] : 0,
            'data' => $list ? $list['data'] : 0,
        ];
        return $data;

    }

    public function getXmMapSelect()
    {

        $db = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where(['admin_id' => session('admin.id')])->select()->toArray();
        $WarehouseName = $this->xm($db, 'WarehouseName');
        $CategoryName1 = $this->xm($db, 'CategoryName1');
        $GoodsNo = $this->xm($db, 'GoodsNo');
        $CustomerName = $this->xm($db, 'CustomerName');
        $CustomItem17 = $this->xm($db, 'CustomItem17');

        $Size = [
            ['name' => '28/44/37/S', 'value' => 'Stock_00_puhuo'],
            ['name' => '29/46/38/M', 'value' => 'Stock_29_puhuo'],
            ['name' => '30/48/39/L', 'value' => 'Stock_30_puhuo'],
            ['name' => '31/50/40/XL', 'value' => 'Stock_31_puhuo'],
            ['name' => '32/52/41/2XL', 'value' => 'Stock_32_puhuo'],
            ['name' => '33/54/42/3XL', 'value' => 'Stock_33_puhuo'],
            ['name' => '34/56/43/4XL', 'value' => 'Stock_34_puhuo'],
            ['name' => '35/58/44/5XL', 'value' => 'Stock_35_puhuo'],
            ['name' => '36/6XL', 'value' => 'Stock_36_puhuo'],
            ['name' => '38/7XL', 'value' => 'Stock_38_puhuo'],
            ['name' => '40/8XL', 'value' => 'Stock_40_puhuo'],
            ['name' => '42', 'value' => 'Stock_42_puhuo'],
            ['name' => '44', 'value' => 'Stock_44_puhuo'],
        ];
        return compact('WarehouseName', 'CategoryName1', 'GoodsNo', 'CustomerName', 'CustomItem17', 'Size');

    }


    public function xm($list, $field)
    {

        $res = array_unique(array_column($list, $field));

        $return = [];
        foreach ($res as $item) {
            $return[] = ['name' => $item, 'value' => $item];
        }
        return $return;

    }


    public function set_revise($param)
    {

        $where = [
            ['GoodsNo', '=', $param['GoodsNo']],
            ['WarehouseName', '=', $param['WarehouseName']],
            ['admin_id', '=', session('admin.id')],
        ];
        $minMax = range($param['min'], $param['max'], 1);
        $list = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where('is_total', '0')->where($where)->select()->toArray();
        $total = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where(['is_total' => 1, 'CustomerName' => '余量'])->where($where)->find();
        foreach ($list as &$item) {
            foreach ($item as $son => &$son_v) {
                if (in_array($son, explode(',', $param['Size'])) && in_array($son_v, $minMax)) {
                    $total[$son] -= $param['number'];
                    $total['Stock_Quantity_puhuo'] -= $param['number'];

                    $item[$son] += $param['number'];
                    $item['Stock_Quantity_puhuo'] += $param['number'];
                }
            }
            $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where($where)->where(['CustomerName' => $item['CustomerName']])->update($item);
        }
        //修改余量
        $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where(['is_total' => 1, 'CustomerName' => '余量'])->where($where)->update($total);


        return true;

    }


}