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
            if (!empty($where['季节'])) $query->where('季节', $where['季节']);
            if (!empty($where['一级分类'])) $query->where('一级分类', $where['一级分类']);
            if (!empty($where['二级分类'])) $query->where('二级分类', $where['二级分类']);
            if (!empty($where['分类'])) $query->where('分类', $where['分类']);
            if (!empty($where['风格'])) $query->where('风格', $where['风格']);
            $query->where(1);
        })->count();

        $list = $this->model->where(function ($query) use ($where) {
            if (!empty($where['店铺名称'])) $query->where('店铺名称', 'in', $where['店铺名称']);
            if (!empty($where['货号'])) $query->where('货号', $where['货号']);
            if (!empty($where['季节'])) $query->where('季节', $where['季节']);
            if (!empty($where['一级分类'])) $query->where('一级分类', $where['一级分类']);
            if (!empty($where['二级分类'])) $query->where('二级分类', $where['二级分类']);
            if (!empty($where['分类'])) $query->where('分类', $where['分类']);
            if (!empty($where['风格'])) $query->where('风格', $where['风格']);
            $query->where(1);
        })->page($page, $limit)->select();

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
