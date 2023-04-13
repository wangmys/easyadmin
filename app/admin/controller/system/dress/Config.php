<?php


namespace app\admin\controller\system\dress;

use app\admin\model\dress\Accessories;
use app\api\constants\ApiConstant;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use app\common\logic\inventory\DressLogic;


/**
 * Class Config
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="引流配置")
 */
class Config extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Accessories;
        // 实例化逻辑类
        $this->logic = new DressLogic;
    }

    /**
     * 数据筛选配置
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 查询所有的省份
            $provinceList = $this->logic->getProvince();
            // 字段
            $field = AdminConstant::YINLIU_COLUMN;
            $data = [
                'provinceList' => $provinceList,
                'field' => $field
            ];
            return json($data);
        }

        // 查询表头
        $head = $this->logic->getHead();
        $this->assign([
            'field' => $head
        ]);
        return $this->fetch();
    }

    /**
     * 保存配置
     */
    public function saveConfig()
    {
        // 获取提交数据
        $post = $this->request->post();
        $check_list = ['id','field'];
        foreach ($post as $k=>$v){
            if(empty($v) && in_array($k,$check_list)){
                return $this->error('参数不能为空');
            }
            if($k == 'stock' && $v==''){
                return $this->error('请填写库存值');
            }
            if($k == 'name' && $v==''){
                $post[$k] = str_replace(',','_',$post['field']);
            }
        }
        try {
            if(!empty($post['id'])){
                $model = $this->logic->dressHead->find($post['id']);
                $model->save($post);
                $res_id = $model->id;
            }else{
                $res_id = $this->logic->saveHead($post);
            }
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('成功',['id' => $res_id]);
    }

    /**
     * 删除配置
     */
    public function delConfig()
    {
        // ID
        $id = $this->request->get('id');
        if(empty($id)){
            return $this->error('ID为空');
        }
         try {
            $this->logic->dressHead->where('id',$id)->delete();
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');
    }
}
