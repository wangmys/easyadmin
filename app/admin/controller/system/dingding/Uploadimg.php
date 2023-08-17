<?php
namespace app\admin\controller\system\dingding;

use think\facade\Db;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\admin\controller\system\dingding\DingTalk;

/**
 * Class Uploadimg
 * @package app\admin\controller\system\dingding
 * @ControllerAnnotation(title="钉钉上传图")
 */
class Uploadimg extends AdminController
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

    public function index() {
        return View('index',[
            
        ]);
    }

    // 上传excel 店铺补货
    public function upload_img() {
        if (request()->isAjax()) {
            $file = request()->file('file');  //这里‘file’是你提交时的name
            $file->getOriginalName();

            $new_name = $file->getOriginalName() . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'public/upload/dd_img/' . date('Ymd',time()).'/';   //文件保存路径
            // print_r($_SERVER);
            $url = $_SERVER['HTTP_ORIGIN'] . '/upload/dd_img/' . date('Ymd',time()).'/' . $new_name;
            $info = $file->move($save_path, $new_name);
            
            return json(['code' => 0, 'msg' => '上传成功', 'path' => $url]);
        }
    }

    // 发送测试
    public function testSend() {
        if (request()->isAjax()) {
            $model = new DingTalk;
            $path = $this->request->domain() . "/upload/dd_img/" . date('Ymd').'/S012.jpg';

            // 上传图 
            $media_id = $model->uploadDingFile($path, "测试");
            
            // return json(['code' => 0, 'msg' => '上传成功', 'path' => $url]);
        }
    }

}
