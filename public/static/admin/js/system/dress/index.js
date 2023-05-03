define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.inventory/index',
        finish_rate: '/admin/system.dress.inventory/finish_rate',
        export_url: 'system.dress.inventory/index_export',
    };
    var Controller = {

        index: function () {
          // 自定义模块
          layui.config({
            base: '/static/plugs/lay-module/soul-table/',   // 模块目录
            version: 'v1.6.4'
          }).extend({             // 模块别名
            soulTable: 'soulTable'
          });
          var $get = $("#where").val();
          layui.use(['form', 'table','soulTable'], function () {
                var table = layui.table,
                soulTable = layui.soulTable;
                table.render({
                    elem: '#currentTable'
                    ,url: init.finish_rate
                    ,where:JSON.parse($get)
                    ,height: 700
                    ,page: false
                    ,cols: [
                        [
                            {field: '商品负责人', width: 200, title: '商品负责人', rowspan: 2,fixed:'left'},
                            {title: '背包', colspan: 3},
                            {title: '挎包', colspan: 3},
                            {title: '领带', colspan: 3},
                            {title: '帽子', colspan: 3},
                            {title: '内裤', colspan: 3},
                            {title: '皮带', colspan: 3},
                            {title: '袜子', colspan: 3},
                            {title: '手包', colspan: 3},
                            {title: '胸包', colspan: 3}
                        ],
                        [
                            {field: '背包', width:100, title: '问题店铺',event:'pp',border: {style: 'solid',color: '1E9FFF'}},
                            {field: '背包_1', width:100, title: '已完成'},
                            {field: '背包_2', width:150, title: '未完成店铺数'},
                            {field: '挎包', width:100, title: '问题店铺'},
                            {field: '挎包_1', width:100, title: '已完成'},
                            {field: '挎包_2', width:150, title: '未完成店铺数'},
                            {field: '领带', width:100, title: '问题店铺'},
                            {field: '领带_1', width:100, title: '已完成'},
                            {field: '领带_2', width:150, title: '未完成店铺数'},
                            {field: '帽子', width:100, title: '问题店铺'},
                            {field: '帽子_1', width:100, title: '已完成'},
                            {field: '帽子_2', width:150, title: '未完成店铺数'},
                            {field: '内裤', width:100, title: '问题店铺'},
                            {field: '内裤_1', width:100, title: '已完成'},
                            {field: '内裤_2', width:150, title: '未完成店铺数'},
                            {field: '皮带', width:100, title: '问题店铺'},
                            {field: '皮带_1', width:100, title: '已完成'},
                            {field: '皮带_2', width:150, title: '未完成店铺数'},
                            {field: '袜子', title: '问题店铺', width:100},
                            {field: '袜子_1', title: '已完成', width:100},
                            {field: '袜子_2', title: '未完成店铺数', width:150},
                            {field: '手包', title: '问题店铺', width:100},
                            {field: '手包_1', title: '已完成', width:100},
                            {field: '手包_2', title: '未完成店铺数', width:150},
                            {field: '胸包', title: '问题店铺', width:100},
                            {field: '胸包_1', title: '已完成', width:100},
                            {field: '胸包_2', title: '未完成店铺数', width:150}
                        ]
                    ]
                    ,done: function () {
                        soulTable.render(this)
                    }
                    ,rowEvent: function (obj) {

                    }
                    ,toolEvent: function (obj) {

                    }
                });
            })
          ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        password: function () {
            ea.listen();
        }
    };
    return Controller;
});