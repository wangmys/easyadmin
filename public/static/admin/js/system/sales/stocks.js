define(["jquery", "easy-admin", "treetable", "iconPickerFa", "autocomplete"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.sales.stocks/index',
        list_url: 'system.menu/index',
        list_url2: 'system.sales.stocks/list',
        export_url: 'system.sales.stocks/export',
    };

    var table = layui.table,
        treetable = layui.treetable,
        iconPickerFa = layui.iconPickerFa,
        autocomplete = layui.autocomplete;

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                cols: [
                    [
                        {type: "checkbox", rowspan: 2},
                        {field: 'WenQu', minWidth: 80, title: '气温区域', rowspan: 2},
                        {field: 'CategoryName1', minWidth: 80, title: '一级分类', rowspan: 2},
                        {field: 'CategoryName2', minWidth: 80, title: '二级分类', rowspan: 2},
                        {field: 'CategoryName', minWidth: 80, title: '分类', rowspan: 2},
                        {minWidth: 80, title: '2022年秋款(2022.7.1 - 2022.11.30)',colspan:5},
                    ],
                    [

                        {field: '销售占比', minWidth: 80, title: '销售占比'},
                        {field: '库存占比', minWidth: 80, title: '库存占比'},
                        {field: '效率', minWidth: 80, title: '效率'},
                        {field: '折扣', title: '折扣', width: 85},
                        {field: '毛利', minWidth: 80, title: '毛利'}
                    ]
                ],
            });
            ea.listen();
        },
        list: function () {
          // 自定义模块
          layui.config({
            base: '/static/plugs/lay-module/soul-table/',   // 模块目录
            version: 'v1.6.4'
          }).extend({             // 模块别名
            soulTable: 'soulTable'
          });
          layui.use(['form', 'table','soulTable'], function () {
                var table = layui.table,
                soulTable = layui.soulTable;
                table.render({
                    elem: '#currentTable'
                    ,url: '/admin/system.sales.stocks/list'
                    ,height: 700
                    ,page: false
                    ,cols: [
                        [
                            {field: 'WenQu', minWidth: 80, title: '气温区域', rowspan: 2},
                            {field: 'CategoryName1', minWidth: 80, title: '一级分类', rowspan: 2},
                            {field: 'CategoryName2', minWidth: 80, title: '二级分类', rowspan: 2},
                            {field: 'CategoryName', minWidth: 80, title: '分类', rowspan: 2},
                            {minWidth: 80, title: '2022年秋款(2022.7.1 - 2022.11.30)',colspan:5},
                        ],
                        [
                            {field: 'sale_rate', minWidth: 80, title: '销售占比'},
                            {field: 'stock_rate', minWidth: 80, title: '库存占比'},
                            {field: 'xl_rate', minWidth: 80, title: '效率'},
                            {field: 'discount', minWidth: 85, title: '折扣'},
                            {field: 'profit_rate', minWidth: 80, title: '毛利'}
                        ]

                    ]
                    ,done: function () {
                        soulTable.render(this)
                    }
                });
            })
          ea.listen();
        }
    };
    return Controller;
});