<?php


namespace app\admin\controller\system\command;

use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use jianyan\excel\Excel;
use app\admin\model\command\CommandErrorTotal;
use app\admin\model\command\CommandLog;
use app\admin\model\command\CommandErrorLog;


/**
 * Class Accessories
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰库存")
 */
class Index extends AdminController
{

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->totalModel = new CommandErrorTotal;
        $this->logModel = new CommandLog;
        $this->errorLogModel = new CommandErrorLog;
    }

    /**
     * @NodeAnotation(title="调拨指令记录")
     */
    public function index()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
         // 商品负责人列表
        $manager = $this->logModel->group('商品负责人')->order('id','asc')->column('商品负责人','商品负责人');
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询指令记录
            $list = $this->logModel->where(function ($q)use($filters,$manager){
                if(!empty($filters['商品负责人'])){
                     $q->whereIn('商品负责人',$filters['商品负责人']);
                }else{
                     $q->whereIn('商品负责人',reset($manager));
                }
            })->select();
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($list),
                    'data'  => $list
                ];
            return json($data);
        }

        return $this->fetch('',[
            'manager' => $manager,
            'searchValue' => reset($manager)
        ]);
    }

    /**
     * @NodeAnotation(title="错误调拨统计")
     */
    public function total()
    {
        // 筛选
        $filters = json_decode($this->request->get('filter', '{}',null), true);
         // 商品负责人列表
        $manager = $this->totalModel->group('商品负责人')->order('id','asc')->column('商品负责人','商品负责人');
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询指令记录
            $list = $this->totalModel->where(function ($q)use($filters,$manager){
                if(!empty($filters['商品负责人'])){
                     $q->whereIn('商品负责人',$filters['商品负责人']);
                }
            })->order('商品负责人,year desc,month asc')->select();
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($list),
                    'data'  => $list
                ];
            return json($data);
        }

        return $this->fetch('',[
            'manager' => $manager
        ]);
    }

}
