<?php
namespace app\admin\controller\system\dingding;

use think\facade\Db;
use think\db\Raw;
use EasyAdmin\annotation\ControllerAnnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\admin\controller\system\dingding\DingTalk;
use app\api\service\dingding\Sample;

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

            $new_name = md5($file->getOriginalName()) . '_' . rand(100, 999) . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'public/upload/dd_img/' . date('Ymd',time()).'/';   //文件保存路径
            // print_r($_SERVER);
            $url = $_SERVER['HTTP_ORIGIN'] . '/upload/dd_img/' . date('Ymd',time()).'/' . $new_name;
            $info = $file->move($save_path, $new_name);
            
            return json(['code' => 0, 'msg' => '上传成功', 'path' => $url]);
        }
    }

    // 发送测试
    public function testSend() {
        // upload/dd_img/20230817/28cefa547f573a951bcdbbeb1396b06f.jpg_614.jpg
        if (request()->isAjax()) {
            $model = new DingTalk;
            // echo $path = $this->request->domain() ;
            
            // echo $path = $_SERVER['HTTP_ORIGIN']. "/upload/dd_img/" . date('Ymd').'/3ce3c522cbdcb4f9d4af5fecfc4ed532_337.jpg';
            $path = "http://im.babiboy.com/upload/dd_img/" . date('Ymd').'/62a21e4c7e989dce29832dd3ebb2e381_959.png';

            echo '<br>';
            // 上传图 
            echo $media_id = $model->uploadDingFile($path, "测试_". time());

            // $res = $model->sendImageMsg('350364576037719254', $media_id );
            // $res = $model->sendImageMsg('350364576037719254', '@lAjPDgCwcKCcChTOPviv5c4jcn31' );
            // $res = $model->sendFileMsg();
            $res = $model->sendMarkdown('350364576037719254', '');
            // $res = $model->sendOaMsg('350364576037719254' );
            // $res = $model->sendActionCardMsg([]);
            print_r($res);
            
            // return json(['code' => 0, 'msg' => '上传成功', 'path' => $url]);
        }
    }

}
