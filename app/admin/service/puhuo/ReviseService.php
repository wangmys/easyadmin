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

        $db = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->select()->toArray();
        $WarehouseName = $this->xm($db, 'WarehouseName');
        $CategoryName1 = $this->xm($db, 'CategoryName1');
        $GoodsNo = $this->xm($db, 'GoodsNo');
        $CustomerName = $this->xm($db, 'CustomerName');
        $CustomItem17 = $this->xm($db, 'CustomItem17');

        return compact('WarehouseName', 'CategoryName1', 'GoodsNo', 'CustomerName', 'CustomItem17');

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


}