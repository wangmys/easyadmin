<?php

namespace app\admin\controller\system\puhuo;

use app\admin\model\bi\SpLypPuhuoYuncangkeyongModel;
use app\admin\model\CustomerModel;
use app\admin\service\ExcelconfigService;
use app\admin\service\ExcelhandleService;
use app\common\logic\execl\PHPExecl;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use jianyan\excel\Excel;
use think\App;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class Excel
 * @package app\admin\controller\system\puhuo
 * @ControllerAnnotation(title="铺货excel配置",auth=true)
 */
class Excelconfig extends AdminController
{
    protected $service;
    protected $request;
    protected $erp;
    protected $mysql;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new ExcelconfigService();
        $this->erp = Db::connect('sqlsrv');
        $this->mysql = Db::connect('mysql');
    }

    /**
     * @return mixed
     * @NodeAnotation(title="铺货excel配置",auth=true)
     */
    public function index()
    {
        $config = $this->mysql->table('sp_lyp_puhuo_excel_config')->select()->toArray();
        $config = $config[0];
        $configCustomItem17 = json_decode($config['商品负责人'], true);
        $CustomItem17Arr = CustomerModel::where('CustomItem17', '<>', '')
            ->where('ShutOut','<>',1)->distinct(true)->column('CustomItem17');

        $CustomItem17 = [];
        foreach ($CustomItem17Arr as $item) {
            $jname = $configCustomItem17[$item] ?? '';
            $CustomItem17[] = ['name' => $item, 'value' => $jname];
        }

        $this->assign(['config' => $config, 'CustomItem17' => $CustomItem17]);
        return $this->fetch();
    }

    /**
     * @param $op
     * @return void
     * @NodeAnotation(title="铺货excel设置保存",auth=true)
     */
    public function save($op = 1)
    {
        $param = $this->request->param();

        $data = [];
        if ($op == 1) {
            $data = [
                '上新' => $param['SX'],
                '补货' => $param['SH'],
                '新补' => $param['XH'],
                '衣裤' => $param['YK'],
                '鞋子' => $param['XZ'],
            ];
        } elseif ($op == 2) {
            $data = [
                '商品负责人' => json_encode($param['json2'])
            ];
        } elseif ($op == 3) {
            $CustomerName = $param['CustomerName'] ?? [];
            $YK = $param['YK'] ?? [];
            $XZ = $param['XZ'] ?? [];
            $newData = [];
            foreach ($CustomerName as $key => $item) {
                if(empty($item) ){
                    continue;
                }
                $newData[] = [
                    'CustomerName' => trim($CustomerName[$key]),
                    'YK' => (int)($YK[$key] ?? 0),
                    'XZ' => (int)($XZ[$key] ?? 0),
                ];

            }
            $data = [
                '特殊店铺' => json_encode($newData)
            ];

        }

        $this->mysql->table('sp_lyp_puhuo_excel_config')->where(1)->save($data);

        $this->success('保存成功');
    }
}