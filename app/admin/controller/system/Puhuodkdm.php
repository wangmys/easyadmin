<?php


namespace app\admin\controller\system;

use app\admin\model\bi\SpLypPuhuoWarehouseReserveGoodsModel;
use app\admin\model\CustomerModel;
use app\admin\model\SjpGoodsModel;
use app\admin\model\bi\SpLypPuhuoWaitGoodsModel;
use app\admin\model\bi\SpLypPuhuoWarehouseReserveConfigModel;
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
 * Class Puhuodkdm
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="仓库预留单款单码配置")
 */
class Puhuodkdm extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];
    const ProductMemberAuth = 7;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SpLypPuhuoWarehouseReserveGoodsModel;
    }

    /**
     * @NodeAnotation(title="仓库预留单款单码配置列表")
     */
    public function index() {

        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $params) = $this->buildTableParames();
            $where = $this->getParms();
            $list = $this->model->where(function ($query) use ($where) {
                                if (!empty($where['config_str'])) $query->where('config_str', $where['config_str']);
                                if (!empty($where['GoodsNo'])) $query->whereIn('GoodsNo', $where['GoodsNo']);
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

        $info_list = SpLypPuhuoWarehouseReserveGoodsModel::column('config_str,GoodsNo');
        $config_str_list = $GoodsNo_list = [];
        if(!empty($info_list)){
            $config_str_list = array_unique(array_column($info_list,'config_str'));
            $config_str_list = array_combine($config_str_list,$config_str_list);

            $GoodsNo_list = array_unique(array_column($info_list,'GoodsNo'));
            $GoodsNo_list = array_combine($GoodsNo_list,$GoodsNo_list);
        }
        $data = [
            'code'  => 1,
            'msg'   => '',
            'config_str_list'  => $config_str_list,
            'GoodsNo_list'  => $GoodsNo_list,
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
            $rule = [
                'config_str' => 'require',
                'GoodsNo' => 'require',
            ];
            $message = [
                'config_str.require' => '云仓不能为空',
                'GoodsNo.require' => '货号不能为空',
            ];
            $this->validate($post, $rule, $message);

            $GoodsNo = $post['GoodsNo'] ? explode(' ', $post['GoodsNo']) : [];
            if ($GoodsNo) {

                //查询该云仓已存在的货号
                $exist_goods = $this->model->where([['id', '<>', $id],   ['config_str', '=', $post['config_str']], ['GoodsNo', 'in', $GoodsNo]])->column('GoodsNo');
                if ($exist_goods) {
                    $this->error('该云仓下，以下货号已存在，请剔除:'.implode(',', $exist_goods));
                }

                Db::startTrans();
                try {

                    //直接删掉该条记录，重新插入
                    $this->model->where([['id', '=', $id]])->delete();

                    $add_data = [];
                    $post_new = $post;
                    foreach ($GoodsNo as $v_goods) {
                        $post_new['GoodsNo'] = $v_goods;
                        $add_data[] = $post_new;
                    }
                    $save = $this->model->saveAll($add_data);
                    
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }

                $save ? $this->success('更新成功') : $this->error('更新失败');

            }

        }

        $info_list = CustomerModel::where([['ShutOut', '=', 0]])->column('CustomItem15 as config_str,State,CustomerGrade');
        $config_str_list = [];
        
        if(!empty($info_list)){
            $config_str_list = array_unique(array_column($info_list,'config_str'));
            $config_str_list = array_combine($config_str_list,$config_str_list);
        }
        $info = $this->model->where([['id', '=', $id]])->find();
        $info = $info ? $info->toArray() : [];
        // print_r($info);die;

        $this->assign([
            'config_str_list' => $config_str_list,
            'info' => $info,
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
                'config_str' => 'require',
                'GoodsNo' => 'require',
            ];
            $message = [
                'config_str.require' => '云仓不能为空',
                'GoodsNo.require' => '货号不能为空',
            ];
            $this->validate($post, $rule, $message);
            $GoodsNo = $post['GoodsNo'] ? explode(' ', $post['GoodsNo']) : [];
            if ($GoodsNo) {

                //查询该云仓已存在的货号
                $exist_goods = $this->model->where([['config_str', '=', $post['config_str']], ['GoodsNo', 'in', $GoodsNo]])->column('GoodsNo');
                if ($exist_goods) {
                    $this->error('该云仓下，以下货号已存在，请剔除:'.implode(',', $exist_goods));
                }

                $add_data = [];
                $post_new = $post;
                foreach ($GoodsNo as $v_goods) {
                    $post_new['GoodsNo'] = $v_goods;
                    $add_data[] = $post_new;
                }
                $save = $this->model->saveAll($add_data);
                $save ? $this->success('保存成功') : $this->error('保存失败');

            }

        }

        $info_list = CustomerModel::where([['ShutOut', '=', 0]])->column('CustomItem15 as config_str,State,CustomerGrade');
        // $goods_info_list = SpLypPuhuoWaitGoodsModel::where([])->column('StyleCategoryName,StyleCategoryName1,CategoryName1,CategoryName2,CategoryName');
        $config_str_list = [];
        
        if(!empty($info_list)){
            $config_str_list = array_unique(array_column($info_list,'config_str'));
            $config_str_list = array_combine($config_str_list, $config_str_list);
        }
        // print_r($config_str_list);die;
        
        $this->assign([
            'config_str_list' => $config_str_list,
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

    /**
     * 获取云仓对应默认配置
     */
    public function get_province_by_config_str() {

        $post = $this->request->post();
        $info = SpLypPuhuoWarehouseReserveConfigModel::where([['config_str', '=', $post['config_str']]])->find();
        $info = $info ? $info->toArray() : [];
        return json(['data' => $info]);

    }

    /**
     * @NodeAnotation(title="导入铺货规则")
     */
    public function import_dkdm() {

        if (request()->isAjax()) {
            $file = request()->file('file');
            $new_name = "铺货规则" . '_' . uuid('puhuo') . '.' . $file->getOriginalExtension();
            $save_path = app()->getRootPath() . 'runtime/uploads/'.date('Ymd',time()).'/';   //文件保存路径
            $info = $file->move($save_path, $new_name);

            if ($info) {

                $read_column = [

                    'A' => '云仓',
                    'B' => '货号',
                    'I' => '44/28/',
                    'J' => '46/29/165/38/M/105',
                    'K' => '48/30/170/39/L/110',
                    'L' => '50/31/175/40/XL/115',
                    'M' => '52/32/180/41/2XL/120',
                    'N' => '54/33/185/42/3XL/125',
                    'O' => '56/34/190/43/4XL/130',
                    'P' => '58/35/195/44/5XL/',
                    'Q' => '60/36/6XL/',
                    'R' => '38/7XL',
                    'S' => '40',
                    'T' => '42',
                ];

                $data = importExcel($info, $read_column);
                if (!$data) {
                    return json(['code' => 500, 'msg'=>'error,读取不到数据']);
                }
                //入库
                $add_data = [];
                foreach ($data as $v_data) {
                    $add_data[] = [

                        'config_str' => $v_data['云仓'],
                        'GoodsNo' => $v_data['货号'],

                        '_28' => $v_data['44/28/'],
                        '_29' => $v_data['46/29/165/38/M/105'],
                        '_30' => $v_data['48/30/170/39/L/110'],
                        '_31' => $v_data['50/31/175/40/XL/115'],
                        '_32' => $v_data['52/32/180/41/2XL/120'],
                        '_33' => $v_data['54/33/185/42/3XL/125'],
                        '_34' => $v_data['56/34/190/43/4XL/130'],
                        '_35' => $v_data['58/35/195/44/5XL/'],
                        '_36' => $v_data['60/36/6XL/'],
                        '_38' => $v_data['38/7XL'],
                        '_40' => $v_data['40'],
                        '_42' => $v_data['42'],
                        'total' => $v_data['合计'],

                    ];

                }

                //清空表
                $db = Db::connect("mysql");
                $db->Query("truncate table sp_lyp_puhuo_rule_a;");

                $chunk_list = array_chunk($add_data, 1000);
                foreach($chunk_list as $key => $val) {
                    $db->table('sp_lyp_puhuo_rule_a')->insertAll($val);
                }

                return json(['code' => 0, 'msg'=>'导入成功', 'data'=>$add_data]);

            } else {

                return json(['code' => 500, 'msg'=>'error,请联系系统管理员']);

            }

        }

    }


}
