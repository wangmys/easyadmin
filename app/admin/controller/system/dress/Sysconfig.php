<?php


namespace app\admin\controller\system\dress;

use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use app\admin\model\dress\Accessories as AccessoriesM;
use app\common\logic\accessories\AccessoriesLogic;


/**
 * Class Sysconfig
 * @package app\admin\controller\system\dress
 * @ControllerAnnotation(title="配饰2.0配置")
 */
class Sysconfig extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new AccessoriesM();
        // 实例化逻辑类
        $this->logic = new AccessoriesLogic;
    }

    /**
     * @NodeAnotation(title="配饰2.0配置")
     */
    public function index()
    {
        // 查询所有的店铺等级
        $levelList = $this->logic->getLevel();
        // echo $levelList = $this->logic->fetchSql()->getLevel();
        // echo $this->logic->getLastSql();
        if ($this->request->isAjax()) {
            // 字段
            $field = $this->logic->getTableRow();
            $data = [
                'provinceList' => $levelList,
                'field' => $field
            ];
            return json($data);
        }
        // 查询配置项表头
        $head = $this->logic->getSysHead();
        // 查询已保存数据
        $data = $this->logic->warStock->column('id,level,content','level');
        // echo $data = $this->logic->warStock->fetchSql()->column('id,level,content','level');

        $d_field = [];
        foreach ($levelList as $kk => $vv){
            $_kk = $vv['name'];
            $d_field[$_kk]['店铺等级'] = $_kk;
            // 已保存数值
            $item = !empty($data[$_kk])?json_decode($data[$_kk]['content'],true):[];
            // 获取省份
            foreach ($head as $k=>$v){
                $v_key = $v['name'];
                if(isset($item[$v_key])){
                    $d_field[$_kk][$v['name']] = $item[$v_key];
                }else{
                    $d_field[$_kk][$v['name']] = $v['stock']??0;
                }
            }
        }

        $this->assign([
            'field' => $head,
            '_field' => array_column($head,'name'),
            'd_field' => $d_field,
        ]);
        return $this->fetch();
    }

    /**
     * 检测有没有重复省
     * @param $all
     * @param $check_arr
     * @return bool
     */
    public function checkDress($all,$check_arr)
    {
        foreach ($check_arr as $k => $v){
            if(in_array($v,$all)){
                return true;
            }
        }
        return false;
    }

    /**
     * 保存配饰表头配置
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
            if($k == 'name' && $v==''){
                $post[$k] = str_replace(',','_',$post['field']);
            }
        }
        try {
            if(!empty($post['id'])){
                $model = $this->logic->accessoriesHead->find($post['id']);
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
            $this->logic->accessoriesHead->where('id',$id)->delete();
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success('删除成功');
    }

    /**
     * 保存配置
     */
    public function save()
    {
        $provinceAll = [];
        $post = $this->request->post();
        $levelList = $post['店铺等级'];
        $item_all = [];
        foreach ($levelList as $k => $v){
            $province_item = explode(',',$v);
            if($k>0){
                if($this->checkDress($provinceAll,$province_item)){
                    return $this->error('店铺等级重复,请重新选择');
                }
            }
            $provinceAll = array_merge($provinceAll,$province_item);

            foreach ($post as $kk => $vv){
                $new[$kk] = $vv[$k];
            }
            $_level = $new['店铺等级'];
            $item_all[] = [
                'level' => $_level,
                'content' => json_encode($new)
            ];
        }
        $rs = $this->logic->saveWarStock($item_all);
        if($rs){
            return $this->success('成功');
        }
        return $this->error('失败');
    }
}
