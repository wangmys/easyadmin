define(["jquery", "easy-admin", "treetable", "iconPickerFa", "autocomplete"], function ($, ea) {
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
                var cols = [
                    {type: "checkbox"},
                    {field: 'State', width: 100, title: '省份',search: true,fixed:'left'},
                    {field: 'Region', width: 100, title: '区域',fixed:'left'},
                    {field: 'CustomerName', width: 100, title: '店铺',search: true,fixed:'left'},
                    {field: 'City', width: 100, title: '地级市',search: true,fixed:'left'},
                    {field: 'BdCity', width: 100, title: '绑定的城市',fixed:'left'},
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
            form.val("app-form", {
              phone:17775611493
            });
            ea.listen();
        }
    };
    return Controller;
});