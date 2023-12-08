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

use think\facade\Db;

class ExcelhandleService
{

    protected $mysql;

    public function __construct()
    {
        $this->mysql = Db::connect('mysql');

    }


    public function order_no($where = [])
    {

        $config = Db::connect('mysql')->table('sp_lyp_puhuo_excel_config')->where(1)->find();
        $config['特殊店铺'] = array_column(json_decode($config['特殊店铺'], true), null, 'CustomerName');
        $config['商品负责人'] = json_decode($config['商品负责人'], true);

        $data = [];

//        $Customers = $this->mysql->table('sp_lyp_puhuo_excel')->where($where)->column('CustomerName');
        $Customers = ['安康一店', '阿拉尔一店'];

        $date = date('Y-m-d');
        $order_no_num = 1;
        foreach ($Customers as $key => $item) {

            $cus_num = 1; //店铺包数
            $yk_con = isset($config['特殊店铺'][$item]['YK']) ? $config['特殊店铺'][$item]['YK'] : $config['衣裤'];
            $xl_con = isset($config['特殊店铺'][$item]['XZ']) ? $config['特殊店铺'][$item]['XZ'] : $config['鞋子'];
            //衣裤
            $clothesPants = $this->mysql->table('sp_lyp_puhuo_excel')->where('CustomerName', $item)->whereIn('CategoryName1', ['外套', '内搭', '下装'])
                ->order('CategoryName1 ASC')->select()->toArray();
            $shoes = $this->mysql->table('sp_lyp_puhuo_excel')->where('CustomerName', $item)->where('CategoryName1', '鞋履')->select()->toArray();
            $total = 0; //总件数
            //处理衣裤

            $gg = $clothesPants ? $clothesPants : $shoes;
            if ($gg) {
                $sortWhere = [
                    'CustomItem17' => $config['商品负责人'][$gg[0]['CustomItem17']],
                    'date' => $date
                ];
                $sortDb = $this->mysql->table('sp_lyp_puhuo_excel_data')->where($sortWhere)->order('sort desc')->value('sort');
                if ($sortDb) {
                    $order_no_num = (int)$sortDb + 1;
                }
            }


            foreach ($clothesPants as $cp_v) {
                $clothesPantsArr = $cp_v;
                if ($cp_v['Stock_Quantity_puhuo'] < $yk_con) { //单货号小于配置
                    $total = $total + $cp_v['Stock_Quantity_puhuo'];
                    if ($total <= $yk_con * $cus_num) {
                        $clothesPantsArr['sort'] = $order_no_num;
                        $clothesPantsArr['uuid'] = $config['商品负责人'][$cp_v['CustomItem17']] . $config[$cp_v['xingzhi']] . date('Ymd') . str_pad($order_no_num, 3, '0', STR_PAD_LEFT);
                        $data[$item][] = $clothesPantsArr;
                    } else {
                        $cus_num++;
                        $order_no_num++;
                        $clothesPantsArr['sort'] = $order_no_num;
                        $clothesPantsArr['uuid'] = $config['商品负责人'][$cp_v['CustomItem17']] . $config[$cp_v['xingzhi']] . date('Ymd') . str_pad($order_no_num, 3, '0', STR_PAD_LEFT);
                        $data[$item][] = $clothesPantsArr;
                    }

                }
            }
            //鞋子
            foreach ($shoes as $s_k => $s_v) {
                if ($s_v['Stock_Quantity_puhuo'] <= $xl_con && isset($data[$item][$s_k])) { //加到原来的
                    $shoesArr = $s_v;
                    $shoesArr['sort'] = $data[$item][$s_k]['sort'];
                    $shoesArr['uuid'] = $data[$item][$s_k]['uuid'];
                    $data[$item][$s_k][] = $shoesArr;
                } else {
                    $order_no_num++;
                    $shoesArr = $s_v;
                    $shoesArr['sort'] = $order_no_num;
                    $shoesArr['uuid'] = $config['商品负责人'][$s_v['CustomItem17']] . $config[$s_v['xingzhi']] . date('Ymd') . str_pad($order_no_num, 3, '0', STR_PAD_LEFT);
                    $data[$item][$s_k][] = $shoesArr;

                }

            }

            //更换店铺 包数更换
            $order_no_num++;

        }
        $return = [];
        foreach ($data as $item) {
            foreach ($item as $son) {
                $return[] = $son;
            }
        }

        $sort = array_column($return, 'sort');
        array_multisort($sort, SORT_ASC, $return);

        return $return;

//        return $this->array_sort($return, 'uuid', 'asc');

    }

    public function array_sort($arr, $keys, $orderby = 'asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($orderby == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[] = $arr[$k];
        }
        return $new_array;
    }

    public function xm_select($data)
    {
        $res = [];
        foreach ($data as $item) {
            $res[] = ['name' => $item, 'value' => $item];
        }
        return $res;
    }


    public function get_yunchang_goods_data()
    {

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