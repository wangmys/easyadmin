<?php


namespace app\admin\controller\system;

use app\admin\model\Spsk as SpskM;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\facade\Db;
use voku\helper\HtmlDomParser;
use think\cache\driver\Redis;
use app\admin\model\weather\Region;

/**
 * Class Spsk
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="spsk管理")
 */
class Spsk extends AdminController
{
    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SpskM;
    }

    /**
     * @NodeAnotation(title="spsk列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $params) = $this->buildTableParames();
            $where = $this->getParms();

        $count = $this->model->where(function ($query) use ($where) {
            if (!empty($where['店铺名称'])) $query->where('店铺名称', 'in', $where['店铺名称']);
            if (!empty($where['货号'])) $query->where('货号', $where['货号']);
            if (!empty($where['季节'])) $query->whereIn('季节', $where['季节']);
            if (!empty($where['一级分类'])) $query->where('一级分类', $where['一级分类']);
            if (!empty($where['二级分类'])) $query->whereIn('二级分类', $where['二级分类']);
            if (!empty($where['分类'])) $query->whereIn('分类', $where['分类']);
            if (!empty($where['风格'])) $query->where('风格', $where['风格']);
            if (!empty($where['商品负责人'])) $query->whereIn('商品负责人', $where['商品负责人']);
            if (!empty($where['省份'])) $query->whereIn('省份', $where['省份']);
            if (!empty($where['经营模式'])) $query->whereIn('经营模式', $where['经营模式']);
            $query->where(1);
        })->count();

        $list = $this->model->where(function ($query) use ($where) {
            if (!empty($where['店铺名称'])) $query->where('店铺名称', 'in', $where['店铺名称']);
            if (!empty($where['货号'])) $query->where('货号', $where['货号']);
            if (!empty($where['季节'])) $query->whereIn('季节', $where['季节']);
            if (!empty($where['一级分类'])) $query->where('一级分类', $where['一级分类']);
            if (!empty($where['二级分类'])) $query->whereIn('二级分类', $where['二级分类']);
            if (!empty($where['分类'])) $query->whereIn('分类', $where['分类']);
            if (!empty($where['风格'])) $query->where('风格', $where['风格']);
            if (!empty($where['商品负责人'])) $query->whereIn('商品负责人', $where['商品负责人']);
            if (!empty($where['省份'])) $query->whereIn('省份', $where['省份']);
            if (!empty($where['经营模式'])) $query->whereIn('经营模式', $where['经营模式']);
            $query->where(1);
        })->page($page, $limit)->select();
        if ($list) {
            foreach ($list as &$v_list) {
                $v_list['预计00/28/37/44/100/160/S'] = $v_list['预计00/28/37/44/100/160/S'] ?: '';
                $v_list['预计29/38/46/105/165/M'] = $v_list['预计29/38/46/105/165/M'] ?: '';
                $v_list['预计30/39/48/110/170/L'] = $v_list['预计30/39/48/110/170/L'] ?: '';
                $v_list['预计31/40/50/115/175/XL'] = $v_list['预计31/40/50/115/175/XL'] ?: '';
                $v_list['预计32/41/52/120/180/2XL'] = $v_list['预计32/41/52/120/180/2XL'] ?: '';
                $v_list['预计33/42/54/125/185/3XL'] = $v_list['预计33/42/54/125/185/3XL'] ?: '';
                $v_list['预计34/43/56/190/4XL'] = $v_list['预计34/43/56/190/4XL'] ?: '';
                $v_list['预计35/44/58/195/5XL'] = $v_list['预计35/44/58/195/5XL'] ?: '';
                $v_list['预计36/6XL'] = $v_list['预计36/6XL'] ?: '';
                $v_list['预计38/7XL'] = $v_list['预计38/7XL'] ?: '';
                $v_list['预计_40'] = $v_list['预计_40'] ?: '';
                $v_list['预计库存数量'] = $v_list['预计库存数量'] ?: '';
                $v_list['省份'] = $v_list['省份'] ? mb_substr($v_list['省份'], 0, 2) : '';
            }
        }

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
     * 获取spsk下拉字段列
     */
    public function getSpskSelect()
    {
        $info_list = $this->model->get_spsk_select();
        $data = [
            'code'  => 1,
            'msg'   => '',
            'data'  => $info_list
        ];
        return json($data);
    }

}
