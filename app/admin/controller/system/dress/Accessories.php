<?php


namespace app\admin\controller\system\dress;

use app\admin\model\dress\Accessories as AccessoriesM;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use think\App;


/**
 * Class Admin
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="配饰库存")
 */
class Accessories extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new AccessoriesM();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            // 设置默认筛选结果
            $this->setWhere($where);

            $count = $this->model
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }

                })
                ->count();

            if(empty($where)){
                $stock_warn = [];
            }else{
                $stock_warn = [];
                foreach ($where as $k => $v){
                    $stock_warn[$v[0]] = $v[2]??0;
                }
            }

            $list = $this->model
//                ->where($where)
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                })
                ->page($page, $limit)
                ->select()->append(['config'])->withAttr('config',function ($data,$value) use($stock_warn){
                    return $stock_warn;
                });
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
                'config' => $stock_warn
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="库存结果")
     */
    public function list()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();

            // 计算条数
            $count = $this->model
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                })
                ->count();

            if(empty($where)){
                $stock_warn = sysconfig('stock_warn');
            }else{
                $stock_warn = [];
                foreach ($where as $k => $v){
                    $stock_warn[$v[0]] = $v[2]??0;
                }
            }

            // 获取列表
            $list = $this->model
                ->where(function ($q)use($where){
                    foreach ($where as $k => $v){
                        $q->whereOr($v[0], $v[1], $v[2]);
                    }
                })
                ->page($page, $limit)
                ->select()->append(['config'])->withAttr('config',function ($data,$value) use($stock_warn){
                    return $stock_warn;
                });
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list
            ];
            return json($data);
        }
        return $this->fetch();
    }


    /**
     * 设置默认筛选结果
     */
    public function setWhere($where)
    {
        $stock_warn = sysconfig('stock_warn');
        if(empty($where)){
            foreach ($stock_warn as $k => $v){
                $where[] = [];
            }
        }
        return $where;
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $authIds = $this->request->post('auth_ids', []);
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $authIds = $this->request->post('auth_ids', []);
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $rule = [];
            $this->validate($post, $rule);
            if (isset($row['password'])) {
                unset($row['password']);
            }
            try {
                $save = $row->save($post);
                TriggerService::updateMenu($id);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->auth_ids = explode(',', $row->auth_ids);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $this->checkPostRequest();
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        $id == AdminConstant::SUPER_ADMIN_ID && $this->error('超级管理员不允许修改');
        if (is_array($id)){
            if (in_array(AdminConstant::SUPER_ADMIN_ID, $id)){
                $this->error('超级管理员不允许修改');
            }
        }
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $this->checkPostRequest();
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        if ($post['id'] == AdminConstant::SUPER_ADMIN_ID && $post['field'] == 'status') {
            $this->error('超级管理员状态不允许修改');
        }
        $row = $this->model->find($post['id']);
        empty($row) && $this->error('数据不存在');
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }


}
