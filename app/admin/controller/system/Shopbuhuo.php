<?php
namespace app\admin\controller\system;

use think\facade\Db;
use think\cache\driver\Redis;
use app\admin\model\budongxiao\SpWwBudongxiaoDetail;
use app\admin\model\budongxiao\SpXwBudongxiaoYuncangkeyong;
use app\admin\model\budongxiao\CwlBudongxiaoStatistics;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class Budongxiao
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="店铺补货")
 */
class Shopbuhuo extends AdminController
{
    // 接收筛选参数
    public $params = [];
    // 数据库
    protected $db_easyA = '';
    protected $db_sqlsrv = '';
    protected $db_bi = '';
    // 用户信息
    protected $authInfo = '';
    
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_bi = Db::connect('mysql2');

        $this->authInfo = session('admin');
        // $this->rand_code = $this->rand_code(10);
        $this->create_time = date('Y-m-d H:i:s', time());
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
                if ($column == "B" || $column == "C") {
                    if (is_numeric($data_origin[$column])) {
                        $t1 = intval(($data_origin[$column]- 25569) * 3600 * 24); //转换成1970年以来的秒数
                        $data_origin[$column] = gmdate('Y/m/d',$t1);
                    } else {
                        $data_origin[$column] = $data_origin[$column];
                    }
                }
            }
            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = $data_origin[$key];
            }
        }
        return $data;
        // echo '<pre>';
        // print_r($data);
    }

    /**
     * @NodeAnotation(title="出货指令单")
     * 仓库给店铺补货 7天内调空
     */
    public function chuhuozhiling() {
        
        if (request()->isAjax()) {
            // 筛选条件

        } else {
            //获取表单上传文件 例如上传了a.xslx
            // $file = Request::file('file');
            //生成保存文件名（generate_password 方法生成随机数，getOriginalExtension 方法获取上传文件的扩展名）
            // $new_name = generate_password(18) . '.' . $file->getOriginalExtension();
            // $save_path = '../runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            // $info = $file->move($save_path,$new_name);   
        }
    }  

    /**
     * @NodeAnotation(title="渠道调拨")
     * 店铺给店铺调拨
     */
    public function qudaodiaobo() {
        
        if (request()->isAjax()) {
            // 筛选条件
            $data = $this->qudaodiaobo_group();
            return json(["code" => "0", "msg" => "", "data" => $data, 'create_time' => $this->create_time]);

        } else {
            $find_qudaodiaobo = $this->db_easyA->table('cwl_qudaodiaobo')->where([
                ['aid', '=', $this->authInfo['id']]
            ])->field('create_time')
            ->order('create_time DESC')
            ->find();
            return View('qudaodiaobo',[
                'create_time' => $find_qudaodiaobo ? $find_qudaodiaobo['create_time'] : '无记录'
            ]);
        }
    }  

    // 康雷在途
    public function qudaodiaobo_zaitu() {
        $sql = "
        SELECT
            T.CustomItem17 商品专员,
            T.CustomerName 店铺名称,
            T.GoodsNo 货号,
            SUM ( T.intransit_quantity ) 在途数量 
        FROM
            (
            SELECT
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName ,
                EG.GoodsNo,
                SUM ( EDG.Quantity ) AS intransit_quantity 
            FROM
                ErpDelivery ED
                LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID= EDG.DeliveryID
                LEFT JOIN ErpCustomer EC ON ED.CustomerId= EC.CustomerId
                LEFT JOIN ErpGoods EG ON EDG.GoodsId= EG.GoodsId 
            WHERE
                ED.CodingCode= 'EndNode2' 
                AND ED.IsCompleted= 0 --AND ED.IsReceipt IS NULL
                
                AND ED.DeliveryID NOT IN (
                SELECT
                    ERG.DeliveryId 
                FROM
                    ErpCustReceipt ER
                    LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID= ERG.ReceiptID 
                WHERE
                    ER.CodingCodeText= '已审结' 
                    AND ERG.DeliveryId IS NOT NULL 
                    AND ERG.DeliveryId!= '' 
                GROUP BY
                    ERG.DeliveryId 
                ) 
                AND EC.CustomItem17 = '{$this->authInfo["name"]}' 
            GROUP BY
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName,
                EG.GoodsNo UNION ALL--店店调拨在途
            SELECT
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName AS dept_name,
                EG.GoodsNo,
                SUM ( EIG.Quantity ) AS intransit_quantity 
            FROM
                ErpCustOutbound EI
                LEFT JOIN ErpCustOutboundGoods EIG ON EI.CustOutboundId= EIG.CustOutboundId
                LEFT JOIN ErpCustomer EC ON EI.InCustomerId= EC.CustomerId
                LEFT JOIN ErpGoods EG ON EIG.GoodsId= EG.GoodsId 
            WHERE
                EI.CodingCodeText= '已审结' 
                AND EI.IsCompleted= 0 
                AND EI.CustOutboundId NOT IN (
                SELECT
                    ERG.CustOutboundId 
                FROM
                    ErpCustReceipt ER
                    LEFT JOIN ErpCustReceiptGoods ERG ON ER.ReceiptID= ERG.ReceiptID 
                WHERE
                    ER.CodingCodeText= '已审结' 
                    AND ERG.CustOutboundId IS NOT NULL 
                    AND ERG.CustOutboundId!= '' 
                GROUP BY
                    ERG.CustOutboundId 
                ) 
                AND EC.CustomItem17 = '{$this->authInfo["name"]}' 
                AND EC.ShutOut= 0 
            GROUP BY
                EC.CustomItem17,
                EC.CustomerId,
                EC.CustomerName,
                EG.GoodsNo 
            ) T 
        GROUP BY
            T.CustomItem17,
            T.CustomerName,
            T.GoodsNo;
        ";
        // 在途 调出店铺是不能有在途的，这样没意义
        $zaitu = $this->db_sqlsrv->query($sql);
        return $zaitu;
    }

    // 康雷库存
    public function qudaodiaobo_kucun() {
        // 店铺库存
        $sql = "
            SELECT
                EC.CustomItem17,
                EC.CustomerName ,
                EG.GoodsNo ,
                SUM(ECS.Quantity) AS actual_quantity
            FROM ErpCustomerStock ECS 
            LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            WHERE  EC.ShutOut=0
                AND EC.CustomItem17 = '{$this->authInfo["name"]}' 
                AND EG.TimeCategoryName1=2023
            GROUP BY 
                EC.CustomItem17,
                EC.CustomerName,
                EG.GoodsNo 
            HAVING SUM(ECS.Quantity)!=0
        ";
        $kucun = $this->db_sqlsrv->query($sql);
        return $kucun;
    }

    // 康雷7天
    public function day7() {
        $sql = "
            SELECT
                T.CustomItem17 商品专员,
                T.CustomerName 店铺名称,
                T.GoodsNo 货号,
                T.[单据类型],
                T.BillId 调出单号,
                T.Quantity 调出数量,
                T.[库存数量],
                T.[清空时间]
            FROM
            (
            SELECT
                EC.CustomerName,
                EC.CustomItem17,
                ECS.StockId,
                ECS.BillId,
                CASE WHEN ECS.BillType='ErpCustOutbound' THEN '店铺调出单' WHEN ECS.BillType='ErpCustReceipt' THEN '店铺收货单' WHEN ECS.BillType='ErpRetail' THEN '零售核销单' ELSE '其他' END AS 单据类型,
                ECS.GoodsId,
                EG.GoodsNo,
                ECS.Quantity,
                SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime) AS 库存数量,
                ECS.CreateTime,
                CASE WHEN SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime)<=0 AND ECS.BillType= 'ErpCustOutbound' AND ECS.Quantity<=-2 THEN '调出清空' END AS 清空操作,
                CASE WHEN SUM(ECS.Quantity) OVER (PARTITION BY EC.CustomerId,ECS.GoodsId ORDER BY ECS.CreateTime)<=0 AND ECS.BillType= 'ErpCustOutbound' AND ECS.Quantity<=-2
                                                THEN ECS.CreateTime END AS 清空时间
            FROM ErpCustomer EC
            LEFT JOIN ErpCustomerStock ECS ON EC.CustomerId=ECS.CustomerId
            LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId
            WHERE EC.CustomItem17='张洋涛'-- EC.CustomerName='大石二店'
            AND EG.TimeCategoryName1=2023
            ) T
            WHERE T.[清空时间] > GETDATE()-7
        ";
        $day7 = $this->db_sqlsrv->query($sql);
        return $day7;
    }

    // qudaodiaobo康雷数据合并 相同货号 统计数量累加
    public function qudaodiaobo_group() {
        $select_qudaodiaobo = $this->db_easyA->query("
                select aa.*,b.CustomerName as 调出店铺名称, c.CustomerName as 调入店铺名称, '' as 备注 from (
                SELECT a.*,sum(a.数量) as `调出店铺该货号数据合计` FROM `cwl_qudaodiaobo` as a
                WHERE a.aid='{$this->authInfo["id"]}'

                GROUP BY 调出店铺编号,货号
                ) as aa left join customer as b on aa.调出店铺编号=b.CustomerCode 
                left join customer as c on aa.调入店铺编号=c.CustomerCode
            ");
        
            
        //  调出不能有在途！！！
        $zaitu = $this->qudaodiaobo_zaitu();
        // 调空不能有在途！！！
        $kucun = $this->qudaodiaobo_kucun();

        $wrongData = [];
        foreach ($select_qudaodiaobo as $key => $val) {

            //  调出不能有在途
            // 1 调出在途
            foreach ($zaitu as $key2 => $val2) {
                if ($val['调出店铺名称'] == $val2['店铺名称'] && $val['货号'] == $val2['货号']) {
                    $select_qudaodiaobo[$key]['信息反馈'] = "【调出在途】 在途数量：" . $val2['在途数量'] . ' 商品专员：' . $val2['商品专员']; 
                    $wrongData[] = $select_qudaodiaobo[$key];
                    break;
                }
            }
            //2 调空在途
            foreach ($kucun as $key3 => $val3) {
                if ($val['调出店铺名称'] == $val3['CustomerName'] && $val['货号'] == $val3['GoodsNo']) {
                    // 库存 - 调出 <= 0
                    if ($val3['actual_quantity'] - $val['调出店铺该货号数据合计'] <= 0) {
                        // $select_qudaodiaobo[$key]['剩余库存'] = $val3['actual_quantity'];
                        $select_qudaodiaobo[$key]['信息反馈'] = "【调空在途】 剩余库存：{$val3['actual_quantity']} 在途数量：" . $val2['在途数量'] . ' 商品专员：' . $val2['商品专员']; 
                        $wrongData[] = $select_qudaodiaobo[$key];
                    }
                    break;
                }
            }
        }

        // return $select_qudaodiaobo;
        return $wrongData;

        // foreach ($select_qudaodiaobo as $key => $val) {
        //     if ($val['信息反馈'] != '') {
        //         dump($select_qudaodiaobo[$key]);
        //     }
        // }
    }

    // 上传excel
    public function uploadExcel_diaobo() {
        if (request()->isAjax()) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $new_name = rand(100, 999) . time() . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            if($info) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => '原单编号',
                    'B' => '单据日期',
                    'C' => '审结日期',
                    'D' => '调出店铺编号',
                    'E' => '调入店铺编号',
                    'F' => '调出价格类型',
                    'G' => '调入价格类型',
                    'H' => '货号',
                    'I' => '颜色编号',
                    'J' => '规格',
                    'K' => '尺码',
                    'L' => '数量',
                    'M' => '规格',
                    'N' => '备注',
                ];
                //读取数据
                $data = $this->readExcel($info, $read_column);
                // $data['aname'] = $this->authInfo['name'];
                // $data['aid'] = $this->authInfo['id'];
                // $data['create_time'] = date('Y-m-d H:i:S');
                foreach ($data as $key => $val) {
                    $data[$key]['aname'] = $this->authInfo['name'];
                    $data[$key]['aid'] = $this->authInfo['id'];
                    $data[$key]['create_time'] = $this->create_time;
                }
                // dump($data); die;
        
                $this->db_easyA->startTrans();
                $this->db_easyA->table('cwl_qudaodiaobo')->where([
                    ['aid', '=', $this->authInfo['id']]
                 ])->delete();
                $insertAll_qudaodiaobo = $this->db_easyA->table('cwl_qudaodiaobo')->insertAll($data);
                if ($insertAll_qudaodiaobo) {
                    
                    $this->db_easyA->commit();
                    return json(['code' => 0, 'msg' => '上传成功']);
                } else {
                    $this->db_easyA->rollback();
                    return json(['code' => 0, 'msg' => '上传失败']);
                }
            } 
        }
    }

    // 测试渠道调拨
    public function test3() {
        // $auth = checkAdmin();
        // dump($auth);
        // $data = cache('testdata');
        // dump($data);

        // $this->db_easyA->table('cwl_qudaodiaobo')->where([
        //     ['aid', '=', $this->authInfo['id']]
        //  ])->delete();

        // AND a.调出店铺编号='Y0878'    
        $select_qudaodiaobo = $this->db_easyA->query("
            select aa.*,b.CustomerName as 调出店铺名称, c.CustomerName as 调入店铺名称, '' as 备注 from (
            SELECT a.*,sum(a.数量) as `调出店铺该货号数据合计` FROM `cwl_qudaodiaobo` as a
            GROUP BY 调出店铺编号,货号
            ) as aa left join customer as b on aa.调出店铺编号=b.CustomerCode 
            left join customer as c on aa.调入店铺编号=c.CustomerCode
        ");

        //  
        $zaitu = $this->qudaodiaobo_zaitu();
        $kucun = $this->qudaodiaobo_kucun();

        // dump($kucun);

        $wrongData = [];
        foreach ($select_qudaodiaobo as $key => $val) {
            // 1 调出在途
            foreach ($zaitu as $key2 => $val2) {
                if ($val['调出店铺名称'] == $val2['店铺名称'] && $val['货号'] == $val2['货号']) {
                    $select_qudaodiaobo[$key]['信息反馈'] = "【调出在途】 在途数量：" . $val2['在途数量'] . ' 商品专员：' . $val2['商品专员']; 
                    $wrongData[] = $select_qudaodiaobo[$key];
                    break;
                }
            }
            //2 调空在途
            foreach ($kucun as $key3 => $val3) {
                if ($val['调出店铺名称'] == $val3['CustomerName'] && $val['货号'] == $val3['GoodsNo']) {
                    // 库存 - 调出 <= 0
                    if ($val3['actual_quantity'] - $val['调出店铺该货号数据合计'] <= 0) {
                        // $select_qudaodiaobo[$key]['剩余库存'] = $val3['actual_quantity'];
                        $select_qudaodiaobo[$key]['信息反馈'] = "【调空在途】 剩余库存：{$val3['actual_quantity']} 在途数量：" . $val2['在途数量'] . ' 商品专员：' . $val2['商品专员']; 
                        $wrongData[] = $select_qudaodiaobo[$key];
                    }
                    break;
                }
            }
        } 

        dump($wrongData);
    }

    public function test4() {
        $select_zhilingdan = $this->db_easyA->query("
            SELECT
                a.*,
                DATE_FORMAT(NOW(), '%Y-%m-%d %h:%i:%s') as create_time,
                b.CustomerName as 店铺名称
            FROM
                cwl_chuhuozhilingdan AS a
                LEFT JOIN customer AS b ON a.店铺编号 = b.CustomerCode
            WHERE a.aid='{$this->authInfo["id"]}'
        ");
        // dump($select_zhilingdan);
        // die;

        $this->db_easyA->startTrans();
        // 删除当前用户所有记录
        $delete_data = $this->db_easyA->table('cwl_chuhuozhilingdan')->where([
            ['aid', '=', $this->authInfo["id"]]
        ])->delete();

        $insert_date = $this->db_easyA->table('cwl_chuhuozhilingdan')->insertAll($select_zhilingdan);
        if ($delete_data && $insert_date) {
            $this->db_easyA->commit();
        } else {
            $this->db_easyA->rollback();
        }
    }

    public function test5() {
        $day7 = $this->day7();

        echo '<pre>';
        print_r($day7);die;

        foreach ($day7 as $key => $val) {
            $this->db_easyA->table('cwl_chuhuozhilingdan')->where([
                ['aid', '=', $this->authInfo["id"]],
                ['店铺名称', '=', $val['店铺名称']],
                ['货号', '=', $val['货号']],
            ])->update([
                '清空时间' => $val['清空时间'],
            ]);
        }

        // dump($select_zhilingdan);
        // print_r($wrongData);
    }

}
