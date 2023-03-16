define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.weather/index',
        city_url: 'system.weather/city',
        list_index: '/admin/system.weather/index'
    };

    var table = layui.table,
        treetable = layui.treetable,
        form = layui.form
    var Controller = {

        index: function () {
            url = ea.url('/system.weather/getWeatherField');
            ea.request.get({
                url:url,
                data:{}
            },function (res) {
                // 省列表
                var province_list = res.province_list;
                // 区域列表
                var area_list = res.area_list;
                // 地级市
                var city_list = res.city_list;
                // 店铺
                var store_list = res.store_list;
                var cols = [
                    {type: "checkbox"},
                    {field: 'State', width: 100, title: '省份',fixed:'left',search: 'select',selectList:province_list,laySearch:true},
                    {field: 'Region', width: 100, title: '区域',fixed:'left',search: 'select',selectList:area_list,laySearch:true},
                    {field: 'CustomerName', width: 100, title: '店铺',fixed:'left',search: 'xmSelect',selectList:store_list,laySearch:true},
                    {field: 'City', width: 100, title: '地级市',fixed:'left',search: 'select',selectList:city_list,laySearch:true},
                    {field: 'BdCity', width: 100, title: '绑定的城市',fixed:'left',search: false},
                    {field: 'SendGoodsGroup', width: 160, title: '温度带',fixed:'left'},
                ];
                var data = res.data;
                data.forEach(function (val,index){
                    if(index == 0){
                        cols.push({
                            width: 140 , search:false , field: val, title: val
                        })
                    }else{
                        cols.push({
                            width: 100 , search:false , field: val, title: val
                        })
                    }

                })

                cols.push({
                    width: 120,
                    title: '操作',
                    templet: ea.table.tool,
                    operat: [
                        [{
                            text: '绑定城市',
                            url: init.city_url,
                            method: 'open',
                            auth: '',
                            class: 'layui-btn layui-btn-normal layui-btn-xs',
                            field:'CustomerId'
                        }],
                    ],
                    fixed: 'right'
                })

                ea.table.render({
                    url: init.list_index,
                    search:true,
                    height: 680,
                    limit: 20,
                    toolbar:[],
                    limits:[20,100,200,500,1000],
                    cols: [cols]
                });

                ea.listen();

            },function (res) {
                alert('失败')
            })
        },
        city: function () {
            ea.listen();
        }
    };
    return Controller;
});