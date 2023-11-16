define(["jquery", "easy-admin2"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        update_url: 'system.puhuodkdm/update',
        add_url: 'system.puhuodkdm/add',
        delete_url: 'system.puhuodkdm/delete',
        list_index: '/admin/system.puhuodkdm/index'
    };

    var table = layui.table,
        treetable = layui.treetable,
        form = layui.form
    var Controller = {

        index: function () {
            url = ea.url('/system.puhuodkdm/getWeatherField');
            ea.request.get({
                url:url,
                data:{}
            },function (res) {
                var config_str_list = res.config_str_list;
                var GoodsNo_list = res.GoodsNo_list;

                var cols = [
                    {type: "checkbox",fixed:'left'},
                    {field: 'config_str', width: 70, title: '云仓',fixed:'left',search: 'select',selectList:config_str_list,laySearch:true},
                    {field: 'GoodsNo', width: 70, title: '货号',fixed:'left',search: 'xmSelect',selectList:GoodsNo_list,laySearch:true},
                    {field: '_28', width: 50, title: '28/37/44/S(%)',search: false},
                    {field: '_29', width: 50, title: '29/38/46/M(%)',search: false},
                    {field: '_30', width: 50, title: '30/39/48/L(%)',search: false},
                    {field: '_31', width: 60, title: '31/40/50/XL(%)',search: false},
                    {field: '_32', width: 60, title: '32/41/52/2XL(%)',search: false},
                    {field: '_33', width: 60, title: '33/42/54/3XL(%)',search: false},
                    {field: '_34', width: 60, title: '34/43/56/4XL(%)',search: false},
                    {field: '_35', width: 60, title: '35/44/58/5XL(%)',search: false},
                    {field: '_36', width: 50, title: '36/6XL(%)',search: false},
                    {field: '_38', width: 50, title: '38/7XL(%)',search: false},
                    {field: '_40', width: 50, title: '40/8XL(%)',search: false},
                    {field: '_42', width: 50, title: '42(%)',search: false},
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
                        'add','delete'//,'custom_import'
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
        add: function (res) {
            console.log(res);
            ea.listen();
        }
    };
    return Controller;
});