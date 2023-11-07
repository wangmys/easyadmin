define(["jquery", "easy-admin2"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        update_url: 'system.puhuozdkphmd/update',
        add_url: 'system.puhuozdkphmd/add',
        delete_url: 'system.puhuozdkphmd/delete',
        list_index: '/admin/system.puhuozdkphmd/index'
    };

    var table = layui.table,
        treetable = layui.treetable,
        form = layui.form
    var Controller = {

        index: function () {
            url = ea.url('/system.puhuozdkphmd/getWeatherField');
            ea.request.get({
                url:url,
                data:{}
            },function (res) {
                var Yuncang_list = res.Yuncang_list;
                var Mathod_list = res.Mathod_list;

                var cols = [
                    {type: "checkbox",fixed:'left'},
                    {field: 'Yuncang', width: 70, title: '云仓',fixed:'left',search: 'select',selectList:Yuncang_list,laySearch:true},
                    {field: 'Mathod', width: 70, title: '经营模式',fixed:'left',search: 'select',selectList:Mathod_list,laySearch:true},
                    {field: 'B', width: 80, title: 'B级',search: false},
                    {field: 'A1', width: 80, title: 'A1级',search: false},
                    {field: 'A2', width: 80, title: 'A2级',search: false},
                    {field: 'A3', width: 80, title: 'A3级',search: false},
                    {field: 'N', width: 80, title: 'N级',search: false},
                    {field: 'H3', width: 80, title: 'H3级',search: false},
                    {field: 'H6', width: 80, title: 'H6级',search: false},
                    {field: 'K1', width: 80, title: 'K1级',search: false},
                    {field: 'K2', width: 80, title: 'K2级',search: false},
                    {field: 'X1', width: 80, title: 'X1级',search: false},
                    {field: 'X2', width: 80, title: 'X2级',search: false},
                    {field: 'X3', width: 80, title: 'X3级',search: false},
                ];

                cols.push({
                    width: 120,
                    title: '操作',
                    templet: ea.table.tool,
                    operat: [
                        [{
                            text: '编辑',
                            url: init.update_url,
                            method: 'open',
                            auth: '',
                            class: 'layui-btn layui-btn-normal layui-btn-xs',
                            field:'id'
                        }],
                        // 'delete'
                    ],
                    fixed: 'right'
                })

                ea.table.render({
                    init: init,//如果使用toolbar['add','delete']等这些默认配置，这句必须要加上！！
                    url: init.list_index,
                    search:true,
                    height: 800,
                    limit: 1000,
                    toolbar:[
                        'add','delete'
                    ],
                    // defaultToolbar: false, //这里在右边显示
                    limits:[1000,2000,3000],
                    cols: [cols],
                    done:function (res, curr, count) {


                    }
                });

                ea.listen();


            },function (res) {
                alert('失败')
            })
        },
        update: function () {
            ea.listen();
        },
        add: function () {
            ea.listen();
        }
    };
    return Controller;
});