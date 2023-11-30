<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

use app\api\controller\lufei\Jianheskc as JianheskcApi;

/**
 * Class Jianheskc
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="检核SKC_预计库存")
 */
class Jianheskcpro extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_bi = '';
    // 创建时间
    protected $create_time = '';

    // 用户信息
    protected $authInfo = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_bi = Db::connect('mysql2');
        $this->create_time = date('Y-m-d H:i:s', time());

        $this->authInfo = session('admin');

        if (cache('jianheskc_data_create')) {
            echo '数据正在更新中，请稍后再试！';
            die;
        } 
    }

    /**
     *  获取表格头部字段
     */
    // public function getHeader() {
    //     // if (request()->isAjax()) {
    //     if (1) {
    //         // 筛选条件
    //         $input = input();
    //         @$pageParams1 = ($input['page'] - 1) * $input['limit'];
    //         @$pageParams2 = input('limit');


    //         if (!empty($input['店铺名称'])) {
    //             // echo $input['商品负责人'];
    //             $map1Str = xmSelectInput($input['店铺名称']);
    //             $map1 = " AND m.店铺名称 IN ({$map1Str})";
    //         } else {
    //             $map1 = "";
    //         }

    //         if (!empty($input['商品负责人'])) {
    //             // echo $input['商品负责人'];
    //             $map4Str = xmSelectInput($input['商品负责人']);
    //             $map4 = " AND m.商品负责人 IN ({$map4Str})";
    //         } else {
    //             $map4 = "";
    //         }

    //         if (!empty($input['温区'])) {
    //             // echo $input['商品负责人'];
    //             $map5Str = xmSelectInput($input['温区']);
    //             $map5 = " AND m.温区 IN ({$map5Str})";
    //         } else {
    //             $map5 = "";
    //         }

    //         if (!empty($input['省份'])) {
    //             // echo $input['商品负责人'];
    //             $map6Str = xmSelectInput($input['省份']);
    //             $map6 = " AND m.省份 IN ({$map6Str})";
    //         } else {
    //             $map6 = "";
    //         }

    //         $sql0 = "
    //             SELECT
    //                 店铺名称
    //             FROM
    //                 cwl_jianhe_stock_skc as m
    //             WHERE 
    //                 1
    //                 {$map1}
    //                 {$map4}
    //                 {$map5}
    //                 {$map6}

    //             GROUP BY
    //                 店铺名称
    //             -- LIMIT 100
    //         ";  
      
    //         $select_店铺名称 = $this->db_easyA->query($sql0);

            
    //         if ($select_店铺名称) {
    //             // print_r($select_店铺名称);die;
    //             $field = "";
    //             $customerName = "";
    //             $len = count($select_店铺名称);
    //             foreach ($select_店铺名称 as $key => $val) {
    //                 $field .= 
    //                 ",sum(
    //                     case 
    //                         when m.店铺名称 = '{$val['店铺名称']}' then m.店铺库存 else null
    //                     end
    //                 ) AS {$val['店铺名称']}";
                    
    //                 if ($key < $len -1 ) {
    //                     $customerName .= "'{$val['店铺名称']}'" . ",";
    //                 } else {
    //                     $customerName .= "'{$val['店铺名称']}'";
    //                 }
    //             }
    
    //             // echo $customerName;die;
    //             // -- 	m.店铺名称 in ('南宁一店','南宁二店')
    //             $sql = "
    //                 SELECT
    //                     m.一级分类,m.二级分类,m.修订分类
    //                     {$field}
    //                 FROM
    //                     cwl_jianhe_stock_skc as m
    //                 WHERE 1
    //                     AND m.店铺名称 in ({$customerName})

    //                 GROUP BY
    //                     m.一级分类,m.二级分类,m.修订分类
    //                 -- limit 1
    //             ";  
    
    //             // die;
    //             $select = $this->db_easyA->query($sql);

    //             if ($select) {
    //                 // 查温区
    //                 $sql_customer = "
    //                     SELECT
    //                         c.`CustomerName`,
    //                         c.`CustomItem10`,
    //                         c.`CustomItem11`,
    //                         c.`CustomItem36`,
    //                         d.five_item_num 
    //                     FROM
    //                         customer AS c
    //                         LEFT JOIN sp_skc_sz_detail as d on c.CustomerName = d.store_name
    //                 ";
                    
                    
    //                 $select_customer = $this->db_easyA->query($sql_customer);
    //                 $res = @$select[0];
                    
    //                 // dump($select_customer);
    //                 // die;

    //                 foreach ($res as $key => $val) {
    //                     foreach ($select_customer as $key2 => $val2) {
    //                         if ($key == $val2['CustomerName']) {
    //                             $res[$key] = [
    //                                 '温区' => $val2['CustomItem36'],
    //                                 '裤台' => $val2['CustomItem10'] + $val2['CustomItem11'],
    //                                 '窗数' => $val2['five_item_num'],
    //                             ];
    //                             break;
    //                         }
    //                     }
    //                 }

    //                 // dump($res);
    //                 // die;
    //                 return json(["code" => "0", "msg" => "", "data" => $res]);
    //             } else {
    //                 return json(["code" => "0", "msg" => "", "data" => []]);
    //             }
    
   
    //         } else {

    //             return json(["code" => "0", "msg" => "", "data" => []]);
    //         }

            
    //     } 
    // } 

    public function getHeader() {
        // if (request()->isAjax()) {
        if (1) {
            // 筛选条件
            $input = input();
            @$pageParams1 = ($input['page'] - 1) * $input['limit'];
            @$pageParams2 = input('limit');


            // if (!empty($input['店铺名称'])) {
            //     // echo $input['商品负责人'];
            //     $map1Str = xmSelectInput($input['店铺名称']);
            //     $map1 = " AND m.店铺名称 IN ({$map1Str})";
            // } else {
            //     $map1 = "";
            // }

            // if (!empty($input['商品负责人'])) {
            //     // echo $input['商品负责人'];
            //     $map4Str = xmSelectInput($input['商品负责人']);
            //     $map4 = " AND m.商品负责人 IN ({$map4Str})";
            // } else {
            //     $map4 = "";
            // }

            // if (!empty($input['温区'])) {
            //     // echo $input['商品负责人'];
            //     $map5Str = xmSelectInput($input['温区']);
            //     $map5 = " AND m.温区 IN ({$map5Str})";
            // } else {
            //     $map5 = "";
            // }

            // if (!empty($input['省份'])) {
            //     // echo $input['商品负责人'];
            //     $map6Str = xmSelectInput($input['省份']);
            //     $map6 = " AND m.省份 IN ({$map6Str})";
            // } else {
            //     $map6 = "";
            // }

            $select_店铺名称 = $this->db_easyA->table('cwl_jianhe_stock_skc_upload')->field('店铺名称')->where([
                'aid' => $this->authInfo['id']
            ])->group('店铺名称')->select();
            
            $map_店铺名称 = "";
            foreach ($select_店铺名称 as $k1 => $v1) {
                if ($k1 + 1 < count($select_店铺名称)) {
                    $map_店铺名称 .= "'{$v1['店铺名称']}',";
                } else {
                    $map_店铺名称 .= "'{$v1['店铺名称']}'";
                }
            }
            $map1 = " AND m.店铺名称 IN ({$map_店铺名称})";
            // echo $map_店铺名称;die;

            $sql0 = "
                SELECT
                    店铺名称
                FROM
                    cwl_jianhe_stock_skc as m
                WHERE  1
                    {$map1}

                GROUP BY
                    店铺名称
                -- LIMIT 10
            ";  
      
            $select_店铺名称 = $this->db_easyA->query($sql0);

            
            if ($select_店铺名称) {
                // print_r($select_店铺名称);die;
                $field = "";
                $customerName = "";
                $len = count($select_店铺名称);
                foreach ($select_店铺名称 as $key => $val) {
                    $field .= 
                    ",sum(
                        case 
                            when m.店铺名称 = '{$val['店铺名称']}' then m.店铺库存 else null
                        end
                    ) AS {$val['店铺名称']}";
                    
                    if ($key < $len -1 ) {
                        $customerName .= "'{$val['店铺名称']}'" . ",";
                    } else {
                        $customerName .= "'{$val['店铺名称']}'";
                    }
                }
    
                // echo $customerName;die;
                // -- 	m.店铺名称 in ('南宁一店','南宁二店')
                $sql = "
                    SELECT
                        m.一级分类,m.二级分类,m.修订分类
                        {$field}
                    FROM
                        cwl_jianhe_stock_skc as m
                    WHERE 1
                        AND m.店铺名称 in ({$customerName})

                    GROUP BY
                        m.一级分类,m.二级分类,m.修订分类
                    -- limit 1
                ";  
    
                // die;
                $select = $this->db_easyA->query($sql);

                if ($select) {
                    // 查温区
                    $sql_customer = "
                        SELECT
                            c.`CustomerName`,
                            c.`CustomItem10`,
                            c.`CustomItem11`,
                            c.`CustomItem36`,
                            d.five_item_num 
                        FROM
                            customer AS c
                            LEFT JOIN sp_skc_sz_detail as d on c.CustomerName = d.store_name
                    ";
                    
                    
                    $select_customer = $this->db_easyA->query($sql_customer);
                    $res = @$select[0];
                    
                    // dump($select_customer);
                    // die;

                    foreach ($res as $key => $val) {
                        foreach ($select_customer as $key2 => $val2) {
                            if ($key == $val2['CustomerName']) {
                                $res[$key] = [
                                    '温区' => $val2['CustomItem36'],
                                    '裤台' => $val2['CustomItem10'] + $val2['CustomItem11'],
                                    '窗数' => $val2['five_item_num'],
                                ];
                                break;
                            }
                        }
                    }

                    // dump($res);
                    // die;
                    return json(["code" => "0", "msg" => "", "data" => $res]);
                } else {
                    return json(["code" => "0", "msg" => "", "data" => []]);
                }
            } else {
                return json(["code" => "0", "msg" => "", "data" => []]);
            }
        } 
    } 
    
    /**
     * @NodeAnotation(title="检核SKC") 
     */
    public function handle() {
        if (request()->isAjax()) {
        // if (1) {
            // 筛选条件
            $input = input();
            @$pageParams1 = ($input['page'] - 1) * $input['limit'];
            @$pageParams2 = input('limit');

            if (!empty($input['检核类型'])) {
                // echo $input['商品负责人'];
                // $map0Str = xmSelectInput($input['检核类型']);
                $map0 = "m.{$input['检核类型']}";
            } else {
                $map0 = "m.预计库存skc";
            }
            // if (!empty($input['店铺名称'])) {
            //     // echo $input['商品负责人'];
            //     $map1Str = xmSelectInput($input['店铺名称']);
            //     $map1 = " AND m.店铺名称 IN ({$map1Str})";
            // } else {
            //     $map1 = "";
            // }

            // if (!empty($input['调整风格'])) {
            //     // echo $input['商品负责人'];
            //     $map2Str = xmSelectInput($input['调整风格']);
            //     $map2 = " AND m.调整风格 IN ({$map2Str})";
            // } else {
            //     $map2 = "";
            // }

            // if (!empty($input['修订季节'])) {
            //     // echo $input['商品负责人'];
            //     $map3Str = xmSelectInput($input['修订季节']);
            //     $map3 = " AND m.修订季节 IN ({$map3Str})";
            // } else {
            //     $map3 = "";
            // }
            // if (!empty($input['商品负责人'])) {
            //     // echo $input['商品负责人'];
            //     $map4Str = xmSelectInput($input['商品负责人']);
            //     $map4 = " AND m.商品负责人 IN ({$map4Str})";
            // } else {
            //     $map4 = "";
            // }

            // if (!empty($input['温区'])) {
            //     // echo $input['商品负责人'];
            //     $map5Str = xmSelectInput($input['温区']);
            //     $map5 = " AND m.温区 IN ({$map5Str})";
            // } else {
            //     $map5 = "";
            // }

            // if (!empty($input['省份'])) {
            //     // echo $input['商品负责人'];
            //     $map6Str = xmSelectInput($input['省份']);
            //     $map6 = " AND m.省份 IN ({$map6Str})";
            // } else {
            //     $map6 = "";
            // }

            $select_店铺名称 = $this->db_easyA->table('cwl_jianhe_stock_skc_upload')->field('店铺名称')->where([
                'aid' => $this->authInfo['id']
            ])->group('店铺名称')->select();

            $map_店铺名称 = "";
            foreach ($select_店铺名称 as $k1 => $v1) {
                if ($k1 + 1 < count($select_店铺名称)) {
                    $map_店铺名称 .= "'{$v1['店铺名称']}',";
                } else {
                    $map_店铺名称 .= "'{$v1['店铺名称']}'";
                }
            }
            $map1 = " AND m.店铺名称 IN ({$map_店铺名称})";

            $sql0 = "
                SELECT
                    店铺名称
                FROM
                    cwl_jianhe_stock_skc as m
                WHERE 
                    1
                    {$map1}
                GROUP BY
                    店铺名称
                -- LIMIT 100
            ";  
            $select_店铺名称 = $this->db_easyA->query($sql0);

            if ($select_店铺名称) {
                $field = "";
                $customerName = "";
                $len = count($select_店铺名称);
                foreach ($select_店铺名称 as $key => $val) {
                    $field .= 
                    ",sum(
                        case 
                            -- when m.店铺名称 = '{$val['店铺名称']}' then m.店铺库存 else null
                            when m.店铺名称 = '{$val['店铺名称']}' then {$map0} else null
                        end
                    ) AS {$val['店铺名称']}";
                    
                    if ($key < $len -1 ) {
                        $customerName .= "'{$val['店铺名称']}'" . ",";
                    } else {
                        $customerName .= "'{$val['店铺名称']}'";
                    }
                }
    
                // echo $customerName;die;
                // -- 	m.店铺名称 in ('南宁一店','南宁二店')
                $sql = "
                    SELECT
                        IFNULL(m.一级分类,'合计') AS 一级分类,
                        IFNULL(m.二级分类,'合计') AS 二级分类, 
                        IFNULL(m.修订分类,'合计') AS 修订分类 
                        {$field}
                    FROM
                        cwl_jianhe_stock_skc as m
                    WHERE 1
                        AND m.店铺名称 in ({$customerName})
                    GROUP BY
                        m.一级分类,m.二级分类,m.修订分类
                        WITH ROLLUP
                    
                    -- LIMIT {$pageParams1}, {$pageParams2}  
                ";  
    
                $select = $this->db_easyA->query($sql);
                $count = count($select);
                // echo '<pre>';
                // print_r($select);
                // die;

                $sql_上传skc = "
                    select * from cwl_jianhe_stock_skc_upload
                    WHERE 1
                        AND 店铺名称 in ({$customerName})
                        AND 预计skc需合计数 = 1
                        AND aid = '{$this->authInfo['id']}'
                ";
                $select_上传skc = $this->db_easyA->query($sql_上传skc);
                
                // 重要
                foreach ($select as $key1 => $val1) {
                    foreach ($select_上传skc as $key2 => $val2) {
                        if ( $val1['一级分类'] ==  $val2['一级分类'] && $val1['二级分类'] ==  $val2['二级分类']  && $val1['修订分类'] ==  $val2['修订分类']) {
                            $select[$key1][$val2['店铺名称']] += 1;
                            // break;
                        }

                        if ($val1['一级分类'] ==  $val2['一级分类'] && $val1['二级分类'] ==  $val2['二级分类'] && $val1['修订分类'] ==  '合计') {
                            $select[$key1][$val2['店铺名称']] += 1;
                        }
                    }
                }


                return json(["code" => "0", "msg" => "", "count" => $count, "data" => $select]);
            } else {
                return json(["code" => "0", "msg" => "", "count" => 0, "data" => []]);
            }


        } else {
            $customer17 = $this->db_easyA->query("
                SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_jianhe_stock_skc WHERE 商品负责人 IS NOT NULL AND 商品负责人 !='0' GROUP BY 商品负责人
            ");

            // dump($customer17);die;


            foreach ($customer17 as $key => $val) {
                if (checkAdmin()) {
                    if ($key == 0) {
                        $customer17 = $val['name'];
                    }
                } elseif (session('admin.name') == $val['name']) {
                    $customer17 = $val['name'];
                } else {
                    $customer17 = '曹太阳';
                }
            } 
            return View('handle', [
                'customer17' => $customer17
            ]);
        }
    } 


    // 获取筛选栏多选参数
    public function getXmMapSelect() {
        // 商品负责人
        $customer17 = $this->db_easyA->query("
            SELECT 商品负责人 as name, 商品负责人 as value FROM cwl_jianhe_stock_skc WHERE 商品负责人 IS NOT NULL AND 商品负责人 !='0' GROUP BY 商品负责人
        ");
        $province = $this->db_easyA->query("
            SELECT 省份 as name, 省份 as value FROM cwl_jianhe_stock_skc WHERE 省份 IS NOT NULL GROUP BY 省份
        ");
        $customer36 = $this->db_easyA->query("
            SELECT 温区 as name, 温区 as value FROM cwl_jianhe_stock_skc WHERE 温区 IS NOT NULL GROUP BY 温区
        ");
        $customer = $this->db_easyA->query("
            SELECT 店铺名称 as name, 店铺名称 as value FROM cwl_jianhe_stock_skc GROUP BY 店铺名称
        ");

        $find_name = false;
        foreach ($customer17 as $key => $val) {
            if (checkAdmin()) {
                if ($key == 0) {
                    $customer17[$key]['selected'] = true;
                    $find_name = true;
                    break;
                }
            } elseif (session('admin.name') == $val['name']) {
                $customer17[$key]['selected'] = true;
                $find_name = true;
                break;
            }
        } 

        // 如果找不到名字
        if ($find_name == false) {
            foreach ($customer17 as $key2 => $val2) {
                if ( '曹太阳' == $val2['name']) {
                    $customer17[$key2]['selected'] = true;
                    $find_name = true;
                    break;
                }
            } 
        }
        
        // 门店
        // $storeAll = SpWwBudongxiaoDetail::getMapStore();

        return json(["code" => "0", "msg" => "", "data" => ['customer' => $customer, 'customer17' => $customer17, 'customer36' => $customer36, 'province' => $province]]);
    }

    // 实时数据更新
    public function updateDdata() {
        if (! cache('jianheskc_data_create')) {
            cache('jianheskc_data_create', true, 1800);
            $jianheskcapi = new JianheskcApi;
            $jianheskcapi->skc_data();
            return json(['status' => 1, 'msg' => '更新成功']);
        } else {
            return json(['status' => 0, 'msg' => '当前数据正在更新中，请稍后再试']);
        }

    }

    public function testRedis()
    {
        // $redis = new Redis;
        // echo '<pre>';
        // print_r($redis);
        // die;
        cache('jianheskc_data_create', null);
        // cache('jianheskc_data_create', true, 1800);
    }

    /** 
     * 读取excel里面的内容保存为数组
     * @param string $file_path  导入文件的路径
     * @param array $read_column  要返回的字段
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function readExcel($file_path = '/', $read_column = array())
    {
        $reader = IOFactory::createReader('Xlsx');
    
        $reader->setReadDataOnly(TRUE);
    
        //载入excel表格
        $spreadsheet = $reader->load($file_path);
    
        // 读取第一個工作表
        $sheet = $spreadsheet->getSheet(0);
    
        // 取得总行数
        $highest_row = $sheet->getHighestRow();
    
        // 取得总列数
        $highest_column = $sheet->getHighestColumn();
    
        //读取内容
        $data_origin = array();
        $data = array();
        for ($row = 2; $row <= $highest_row; $row++) { //行号从2开始
            for ($column = 'A'; $column <= $highest_column; $column++) { //列数是以A列开始
                $str = $sheet->getCell($column . $row)->getValue();
                //保存该行的所有列
                $data_origin[$column] = $str;
                if ($column == "C" || $column == "D") {
                    if (is_numeric($data_origin[$column])) {
                        $t1 = intval(($data_origin[$column]- 25569) * 3600 * 24); //转换成1970年以来的秒数
                        $data_origin[$column] = gmdate('Y/m/d',$t1);
                    } else {
                        $data_origin[$column] = $data_origin[$column];
                    }
                }
            }

            // 删除空行，好用的很
            if(!implode('', $data_origin)){
                //删除空行
                continue;
            }

            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = @$data_origin[$key] ? $data_origin[$key] : '';
            }
        }
        return $data;
    }

    // 上次excel测试
    public function readExcel_test() {
        // $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/补货申请_黎亿炎_ccccccccccccc.xlsx';   //文件保存路径
        $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/计算预计库存导入单.xlsx';   //文件保存路径
        $read_column = [
            // 'A' => '原单编号',
            'B' => '仓库编号',
            'C' => '店铺编号',
            'F' => '货号',
            // 'G' => '尺码',
            'I' => '铺货数',
            // 'K' => '备注',
        ];

        // if (! cache('test_date')) {
        //     $data = $this->readExcel1($save_path, $read_column);
        //     cache('test_date', $data, 3600);
        // } else {
        //     $data = cache('test_date'); 
        // }
        $data = $this->readExcel($save_path, $read_column);


        if ($data) {
            // $this->authInfo
            foreach ($data as $kk => $vv) {
                $data[$kk]['aid'] = $this->authInfo['id'];
                $data[$kk]['aname'] = $this->authInfo['name'];
                $data[$kk]['更新时间'] = date('Y-m-d', time());
            }

            // 删除历史
            $this->db_easyA->table('cwl_jianhe_stock_skc_upload')->where([
                ['aid', '=', $this->authInfo['id']]
            ])->delete();

            // 插入
            $chunk_list = array_chunk($data, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $this->db_easyA->table('cwl_jianhe_stock_skc_upload')->strict(false)->insertAll($val);
            }

            // 分组处理
            $this->groupHandle();

            // die;

            $sql_店铺 = "
                update `cwl_jianhe_stock_skc_upload` as u
                left join customer as c on u.店铺编号 = c.CustomerCode
                set
                    u.店铺名称 = c.CustomerName
                where
                    u.aid = '{$this->authInfo['id']}'
            ";
            $this->db_easyA->execute($sql_店铺);

            $sql_分类 = "
                update `cwl_jianhe_stock_skc_upload` as u
                left join sp_ww_hpzl as h on u.货号 = h.货号
                set
                    u.一级分类 = h.一级分类,
                    u.二级分类 = h.二级分类,
                    u.分类 = h.分类,
                    u.季节 = h.季节
                where
                u.aid = '{$this->authInfo['id']}'
            ";
            $this->db_easyA->execute($sql_分类);

            $sql_修订分类 = "
                update cwl_jianhe_stock_skc_upload as sk
                LEFT JOIN (
                    SELECT
                        分类,修订分类
                    FROM	cwl_jianhe_skc_biaozhun_1 where 分类 is not null group by 分类
                ) as b ON sk.分类 = b.分类
                set 
                    sk.修订分类 = b.修订分类
                where 
                    sk.修订分类 is null
                    AND sk.aid = '{$this->authInfo['id']}'
            ";
            $this->db_easyA->execute($sql_修订分类);

            $sql_预计库存数量 = "
                update `cwl_jianhe_stock_skc_upload` as u
                left join sp_sk as sk on u.店铺名称 = sk.店铺名称 and u.一级分类 = sk.一级分类 and u.二级分类 = sk.二级分类 and u.货号 = sk.货号
                set
                    u.店铺预计库存数量 = sk.预计库存数量
                where
                aid = '{$this->authInfo['id']}'
            ";
            $this->db_easyA->execute($sql_预计库存数量);

            $sql_预计skc需合计数 = "
                update `cwl_jianhe_stock_skc_upload` 
                set
                    预计skc需合计数 = case
                        when 店铺预计库存数量 is null OR 店铺预计库存数量 <= 0 then 1 else 0
                    end
                where
                aid = '{$this->authInfo['id']}'
            ";
            $this->db_easyA->execute($sql_预计skc需合计数);
        }
    }

    // 分组合并
    private function groupHandle() {
        $sql = "
            SELECT aid,aname,仓库编号,店铺编号,货号, sum(铺货数) as 铺货数, 更新时间 FROM `cwl_jianhe_stock_skc_upload` where aid = '{$this->authInfo['id']}' group by 店铺编号,货号
        ";
        $select = $this->db_easyA->query($sql);
        // 删除历史
        $this->db_easyA->table('cwl_jianhe_stock_skc_upload')->where([
            ['aid', '=', $this->authInfo['id']]
        ])->delete();

        // 插入
        $chunk_list = array_chunk($select, 500);
        foreach($chunk_list as $key => $val) {
            // 基础结果 
            $this->db_easyA->table('cwl_jianhe_stock_skc_upload')->strict(false)->insertAll($val);
        }


    }
}
