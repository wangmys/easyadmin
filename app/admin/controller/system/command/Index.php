<?php


namespace app\admin\controller\system\command;

use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use think\App;
use think\facade\Db;
use jianyan\excel\Excel;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\admin\model\command\CommandErrorTotal;
use app\admin\model\command\CommandLog;
use app\admin\model\command\CommandErrorLog;


/**
 * Class Index
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="调拨指令记录")
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
        $manager = $this->errorLogModel->group('商品负责人')->order('id','asc')->column('商品负责人','商品负责人');
        $month = $this->errorLogModel->group('month')->order('month','desc')->column('month','month');
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询指令记录
            $list = $this->errorLogModel->where(function ($q)use($filters,$manager){
                if(!empty($filters['商品负责人'])){
                     $q->whereIn('商品负责人',$filters['商品负责人']);
                }else{
//                     $user = session('admin');
//                     if($user['id'] != AdminConstant::SUPER_ADMIN_ID) $q->whereIn('商品负责人',$user['name']);
                }
                if(!empty($filters['month'])){
                     $q->whereIn('month',$filters['month']);
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
            'searchValue' => reset($manager),
            'month' => $month,
            'searchValue2' => date('n'),
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
        $manager = $this->totalModel->group('商品负责人')->where([
            'year' => date('Y')
        ])->order('id','asc')->column('商品负责人','商品负责人');
        // 获取参数
        $where = $this->request->get();
        if ($this->request->isAjax()) {
            // 查询指令记录
            $list = $this->totalModel->where(function ($q)use($filters,$manager){
                if(!empty($filters['商品负责人'])){
                     $q->whereIn('商品负责人',$filters['商品负责人']);
                }
            })->order('商品负责人,year desc,month asc')->select();

            // 数据集重组
            $new = [];
            foreach ($list as $k => $v){
                $new[$v['商品负责人']]['商品负责人'] = $v['商品负责人'];
                $new[$v['商品负责人']][$v['date_str']] = $v['num'];
            }
            // 返回数据
            $data = [
                    'code'  => 0,
                    'msg'   => '',
                    'count' => count($new),
                    'data'  => $new
                ];
            return json($data);
        }
        $field = $this->totalModel->group('date_str')->column('date_str');
        return $this->fetch('',[
            'manager' => $manager,
            'field' => $field
        ]);
    }

}
