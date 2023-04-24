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
    // 随机数
    protected $rand_code = '';
    // 创建时间
    protected $create_time = '';

    public function __construct()
    {
        $this->db_easyA = Db::connect('mysql');
        $this->db_sqlsrv = Db::connect('sqlsrv');
        $this->db_bi = Db::connect('mysql2');
        // $this->rand_code = $this->rand_code(10);
        // $this->create_time = date('Y-m-d H:i:s', time());
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
            }
            //取出指定的数据
            foreach ($read_column as $key => $val) {
                $data[$row - 2][$val] = $data_origin[$key];
            }
        }
        // return $data;
        echo '<pre>';
        print_r($data);
    }

    /**
     * @NodeAnotation(title="出货指令单")
     */
    public function chuhuozhiling() {
        
        if (request()->isAjax()) {
            // 筛选条件

        } else {
            echo '出货指令单';
            //获取表单上传文件 例如上传了a.xslx
            // $file = Request::file('file');
            //生成保存文件名（generate_password 方法生成随机数，getOriginalExtension 方法获取上传文件的扩展名）
            // $new_name = generate_password(18) . '.' . $file->getOriginalExtension();
            // $save_path = '../runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            // $info = $file->move($save_path,$new_name);

            $file = app()->getRootPath() . 'runtime\upload\shopbuhuo\20230424\出货指令单.xlsx';
            
            // die;
            if($file) {
                //成功上传后 获取上传的数据
                //要获取的数据字段
                $read_column = [
                    'A' => 'real_name',
                    'B' => 'sex',
                    'C' => 'grade',
                    'D' => 'class',
                    'E' => 'roll_number',
                    'F' => 'mobile',
                    'G' => 'id_card',
                    'H' => 'user_name',
                    'I' => 'passwd',
                ];
                //读取数据
                $data = $this->readExcel($file, $read_column);
                dump($data);
            }
        }
    }  

    /**
     * @NodeAnotation(title="渠道调拨")
     */
    public function qudaodiaobo() {
        
        if (request()->isAjax()) {
            // 筛选条件


        } else {
            return View('qudaodiaobo',[
            
            ]);
            
        }
    }  

    // 康雷在途
    public function qudaodiaobo_zaitu() {
        $sql1 = "
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
                AND EC.CustomItem17 = '张洋涛' 
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
                AND EC.CustomItem17 = '张洋涛' 
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
        $zaitukucun = $this->db_sqlsrv->query($sql1);
        return $zaitukucun;
    }


    // qudaodiaobo康雷数据合并 相同货号 统计数量累加
    public function qudaodiaobo_group() {
        $select_qudaodiaobo = $this->db_easyA->query("
                select aa.*,b.CustomerName as 调出店铺名称, c.CustomerName as 调入店铺名称, '' as 备注 from (
                SELECT a.*,sum(a.数量) as `汇总数量` FROM `cwl_qudaodiaobo` as a
                GROUP BY 调出店铺编号,货号
                ) as aa left join customer as b on aa.调出店铺编号=b.CustomerCode 
                left join customer as c on aa.调入店铺编号=c.CustomerCode
            ");
        
        //  
        $zaitu = $this->qudaodiaobo_zaitu($select_qudaodiaobo);

        foreach ($select_qudaodiaobo as $key => $val) {
            foreach ($zaitu as $key2 => $val2) {
                if ($val['调入店铺名称'] == $val2['店铺名称'] && $val['货号'] == $val2['货号']) {
                    $select_qudaodiaobo[$key]['备注'] = "在途数量：" . $val2['在途数量']; 
                    break;
                }
            }
        }

        foreach ($select_qudaodiaobo as $key => $val) {
            if ($val['备注'] != '') {
                dump($select_qudaodiaobo[$key]);
            }
        }

        // dump($select_qudaodiaobo);
        // dump($zaitu);
    }

    public function uploadExcel() {
        // header('Content-Type: application/json; charset=utf-8');
        // error_reporting(E_ALL);
        $file = request()->file('file');  //这里‘file’是你提交时的name
        echo $new_name = rand(100, 999) . time() . '.' . $file->getOriginalExtension();
        echo "<br>";
        echo $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
        echo "<br>";
        $info = $file->move($save_path, $new_name);
        // dump($info);die;

        if($info) {
            //成功上传后 获取上传的数据
            //要获取的数据字段
            $read_column = [
                'A' => 'real_name',
                'B' => 'sex',
                'C' => 'grade',
                'D' => 'class',
                'E' => 'roll_number',
                'F' => 'mobile',
                'G' => 'id_card',
                'H' => 'user_name',
                'I' => 'passwd',
            ];
            //读取数据
            $data = $this->readExcel($info, $read_column);
            dump($data);
        }

        // return json($file);
        // $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
        // dump($file);
    }
}
