define(["jquery", "easy-admin2"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        update_url: 'system.puhuoruleb/update',
        add_url: 'system.puhuoruleb/add',
        delete_url: 'system.puhuoruleb/delete',
        list_index: '/admin/system.puhuoruleb/index'
    };

    var table = layui.table,
        treetable = layui.treetable,
        form = layui.form
    var Controller = {

        index: function () {
            url = ea.url('/system.puhuoruleb/getWeatherField');
            ea.request.get({
                url:url,
                data:{}
            },function (res) {
                var Yuncang_list = res.Yuncang_list;
                var State_list = res.State_list;
                var StyleCategoryName_list = res.StyleCategoryName_list;
                var CategoryName1_list = res.CategoryName1_list;
                var CategoryName2_list = res.CategoryName2_list;
                var CategoryName_list = res.CategoryName_list;

                var cols = [
                    {type: "checkbox",fixed:'left'},
                    {field: 'Yuncang', width: 70, title: '云仓',fixed:'left',search: 'select',selectList:Yuncang_list,laySearch:true},
                    {field: 'State', width: 70, title: '省份',fixed:'left',search: 'select',selectList:State_list,laySearch:true},
                    {field: 'StyleCategoryName', width: 70, title: '风格',search: false,search: 'select',selectList:StyleCategoryName_list},
                    // {field: 'StyleCategoryName1', width: 70, title: '一级风格',search: false},
                    {field: 'CategoryName1', width: 90, title: '一级分类',search: 'xmSelect',selectList:CategoryName1_list,laySearch:true},
                    {field: 'CategoryName2', width: 100, title: '二级分类',search: 'xmSelect',selectList:CategoryName2_list,laySearch:true},
                    {field: 'CategoryName', width: 100, title: '分类',search: 'xmSelect',selectList:CategoryName_list,laySearch:true},
                    {field: 'CustomerGrade', width: 50, title: '店铺等级',search: false},
                    {field: 'Stock_00', width: 50, title: '28',search: false},
                    {field: 'Stock_29', width: 50, title: '29',search: false},
                    {field: 'Stock_30', width: 50, title: '30',search: false},
                    {field: 'Stock_31', width: 50, title: '31',search: false},
                    {field: 'Stock_32', width: 50, title: '32',search: false},
                    {field: 'Stock_33', width: 50, title: '33',search: false},
                    {field: 'Stock_34', width: 50, title: '34',search: false},
                    {field: 'Stock_35', width: 50, title: '35',search: false},
                    {field: 'Stock_36', width: 50, title: '36',search: false},
                    {field: 'Stock_38', width: 50, title: '38',search: false},
                    {field: 'Stock_40', width: 50, title: '40',search: false},
                    {field: 'Stock_42', width: 50, title: '42',search: false},
                    {field: 'total', width: 50, title: '合计',search: false},
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
                        'add','delete','custom_import'
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