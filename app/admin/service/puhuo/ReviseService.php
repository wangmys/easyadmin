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

        $db = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where([['CustomerName', '<>', '余量'], ['admin_id', '=', session('admin.id')]])->select()->toArray();
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
        $GoodsNoList = explode(',', $param['GoodsNo']);

        foreach ($GoodsNoList as $GoodsNo) {
            $where = [
                ['GoodsNo', '=', $GoodsNo],
                ['WarehouseName', '=', $param['WarehouseName']],
                ['admin_id', '=', session('admin.id')],
            ];

            $where2 = [];
            if (isset($param['CustomerName']) && !empty($param['CustomerName'])) {
                $where2[] = ['CustomerName', 'IN', explode(',', $param['CustomerName'])];
            }
            $minMax = range($param['min'], $param['max'], 1);
            $list = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where('is_total', '0')->where($where2)->where($where)->select()->toArray();

            $total = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where(['is_total' => 1, 'CustomerName' => '余量'])->where($where)->find();
            foreach ($list as &$item) {
                foreach ($item as $son => &$son_v) {
                    if (in_array($son, explode(',', $param['Size'])) && in_array($son_v, $minMax)) {
                        if (($item[$son] + $param['number']) < 0) {
                            continue;
                        }
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

        }

        return true;

    }


    public function order_no($where = [])
    {

        $config = $this->mysql->table('sp_lyp_puhuo_excel_config')->where(1)->find();
        $config['特殊店铺'] = array_column(json_decode($config['特殊店铺'], true), null, 'CustomerName');
        $config['商品负责人'] = json_decode($config['商品负责人'], true);

        $data = [];
        $exDb = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where($where)->where('admin_id', session('admin.id'))->select()->toArray();

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
            $clothesPants = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where($where)
                ->where(['admin_id' => session('admin.id'), 'CustomerName' => $item['CustomerName'], 'WarehouseName' => $item['WarehouseName']])
                ->whereIn('CategoryName1', ['外套', '内搭', '下装'])
                ->order('CategoryName1 ASC')->select()->toArray();
            $shoes = $this->mysql->table('sp_lyp_puhuo_end_data_revise')->where($where)
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