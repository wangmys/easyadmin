define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.Customorstocksale7/index',
        city_url: 'system.weather/city',
        list_index: '/admin/system.Customorstocksale7/index'
    };

    var table = layui.table,
        treetable = layui.treetable,
        form = layui.form
    var Controller = {

        index: function () {
            url = ea.url('/system.Customorstocksale7/getField');
            ea.request.get({
                url: url,
                data: {}
            }, function (res) {
                console.log(res);
                // 省列表
                var province_list = res.province_list;
                // 气温区域
                var air_temperature_list = res.air_temperature_list;
                // 经营模式
                var management_model_list = res.management_model_list;
                // 店铺等级
                var grade_list = res.grade_list;
                // 一级分类
                var level1_list = res.level1_list;
                // 二级分类
                var level2_list = res.level2_list;
                // 店铺
                var store_list = res.store_list;
                // 风格
                var style_list = res.style_list;
                // 季节
                var season_list = res.season_list;
                var cols = [
                    { field: '省份', width: 100, title: '省份', fixed: 'left', search: 'xmSelect', selectList: province_list, laySearch: true },
                    { field: '风格', width: 100, title: '风格', fixed: 'left', hide:true, search: 'xmSelect', selectList: style_list, laySearch: true },
                    { field: '季节', width: 100, title: '季节', fixed: 'left', hide:true, search: 'xmSelect', selectList: season_list, laySearch: true },
                    { field: '气温区域', width: 100, title: '气温区域', fixed: 'left', search: 'xmSelect', selectList: air_temperature_list, laySearch: true },
                    { field: '经营模式', width: 100, title: '经营模式', fixed: 'left', search: 'xmSelect', selectList: management_model_list, laySearch: true },
                    { field: '店铺名称', width: 100, title: '店铺名称', fixed: 'left', search: 'xmSelect', selectList: store_list, laySearch: true },
                    { field: '店铺等级', width: 100, title: '店铺等级', fixed: 'left', search: 'xmSelect', selectList: grade_list, laySearch: true },
                    { field: '一级分类', width: 100, title: '一级分类', fixed: 'left', search: 'xmSelect', selectList: level1_list, laySearch: true },
                    { field: '二级分类', width: 100, title: '二级分类', fixed: 'left', search: 'xmSelect', selectList: level2_list, laySearch: true },
                    //   {field: 'City', width: 100, title: '地级市',fixed:'left',search: 'select',selectList:city_list,laySearch:true},
                    //   {field: 'BdCity', width: 100, title: '绑定的城市',fixed:'left',search: false},
                    //   {field: 'SendGoodsGroup', width: 160, title: '温度带',fixed:'left'},
                ];
                var data = res.data;
                data.forEach(function (val, index) {
                    if (index == 0) {
                        cols.push({
                            width: 140, search: false, field: val, title: val
                        })
                    } else {
                        cols.push({
                            width: 100, search: false, field: val, title: val
                        })
                    }

                })

                console.log('---------');
                console.log(cols);
                console.log('---------');


                ea.table.render({
                    url: init.list_index,
                    search: true,
                    height: 680,
                    limit: 200,
                    toolbar: [],
                    limits: [100, 200, 500, 1000, 5000, 6000],
                    cols: [cols]
                });

                ea.listen();

            }, function (res) {
                alert('失败')
            })
        },
        city: function () {
            ea.listen();
        }
    };
    return Controller;
});