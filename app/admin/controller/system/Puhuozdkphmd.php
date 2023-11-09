<?php


namespace app\admin\controller\system;

use app\admin\model\bi\SpLypPuhuoZdkphmdModel;
// use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
// use voku\helper\HtmlDomParser;
// use think\cache\driver\Redis;
// use app\admin\model\weather\Region;

/**
 * Class Puhuozdkphmd
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="指定款铺货门店管理")
 */
class Puhuozdkphmd extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];
    const ProductMemberAuth = 7;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SpLypPuhuoZdkphmdModel;
    }

    /**
     * @NodeAnotation(title="指定款铺货门店管理列表")
     */
    public function index() {

        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $params) = $this->buildTableParames();
            $where = $this->getParms();
            $list = $this->model->where(function ($query) use ($where) {
                                if (!empty($where['Yuncang'])) $query->where('Yuncang', $where['Yuncang']);
                                if (!empty($where['Mathod'])) $query->where('Mathod', $where['Mathod']);
                                // if (!empty($where['CategoryName2'])) $query->where('CategoryName2', 'in', $where['CategoryName2']);
                                $query->where(1);
                            })
                            ->paginate([
                                'list_rows'=> $limit,
                                'page' => $page,
                            ]);
            $list = $list ? $list->toArray() : [];
            // print_r($list);die;

            $data = [
                'code'  => 0,
                'msg'   => '',
                'today_date'   => date('m-d'),
                'count' => $list ? $list['total'] : 0,
                'data'  => $list ? $list['data'] : 0,
            ];
            return json($data);
        }
        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="获取下拉字段列")
     */
    public function getWeatherField() {

        $info_list = $this->model->column('Yuncang,Mathod');
        $Yuncang_list = $Mathod_list = [];
        if(!empty($info_list)){
            $Yuncang_list = array_unique(array_column($info_list,'Yuncang'));
            $Yuncang_list = array_combine($Yuncang_list,$Yuncang_list);

            $Mathod_list = array_unique(array_column($info_list,'Mathod'));
            $Mathod_list = array_combine($Mathod_list,$Mathod_list);
        }
        $data = [
            'code'  => 1,
            'msg'   => '',
            'Yuncang_list'  => $Yuncang_list,
            'Mathod_list'  => $Mathod_list,
        ];
        return json($data);

    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function update() {
        // 获取店铺ID
        $id = $this->request->get('id');
        
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $this->model->where([['id', '=', $id]])->update($post);
            $this->success('更新成功');

        }

        $info_list = $this->model->column('Yuncang,Mathod');
        $Yuncang_list = $Mathod_list = [];
        if(!empty($info_list)){
            $Yuncang_list = array_unique(array_column($info_list,'Yuncang'));
            $Yuncang_list = array_combine($Yuncang_list,$Yuncang_list);

            $Mathod_list = array_unique(array_column($info_list,'Mathod'));
            $Mathod_list = array_combine($Mathod_list,$Mathod_list);
        }

        $rulea_info = $this->model->where([['id', '=', $id]])->find();
        $rulea_info = $rulea_info ? $rulea_info->toArray() : [];

        $this->assign([
            'Yuncang_list' => $Yuncang_list,
            'Mathod_list' => $Mathod_list,
            'rulea_info' => $rulea_info,
        ]);

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add() {

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                // 'Stock_00' => 'require',
            ];
            $message = [
                // 'Stock_00.require' => '28尺码铺货数不能为空',
            ];
            $this->validate($post, $rule, $message);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        $info_list = $this->model->column('Yuncang,Mathod');
        $Yuncang_list = $Mathod_list = [];
        if(!empty($info_list)){
            $Yuncang_list = array_unique(array_column($info_list,'Yuncang'));
            $Yuncang_list = array_combine($Yuncang_list,$Yuncang_list);

            $Mathod_list = array_unique(array_column($info_list,'Mathod'));
            $Mathod_list = array_combine($Mathod_list,$Mathod_list);
        }
        
        $this->assign([
            'Yuncang_list' => $Yuncang_list,
            'Mathod_list' => $Mathod_list,
        ]);

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="删除")
     */
    public function delete() {

        $this->checkPostRequest();
        $post = $this->request->post();
        $row = $this->model->whereIn('id', $post ? $post['id'] : [])->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }


}