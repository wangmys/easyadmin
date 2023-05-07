define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.index/index',
        export_url: 'system.dress.index/index_export',
    };
    var Controller = {

        index: function () {
            var $get = $("#where").val();
            var $cols= $("#cols").val();
            var cols = JSON.parse($cols);
            ea.table.render({
                init:init,
                search:false,
                where:JSON.parse($get),
                height: 760,
                limit: 1000,
                toolbar:[
                    'custom_export'
                ],
                limits:[100,200,500,1000],
                cols: cols
            });

            var table = layui.table;
            //转换静态表格
            var list_table = table.init('list', {
                done: function (res, curr, count) {
                    // var that = this.elem.next();
                    // for(res.data in item){
                    //     var tr = that.find("[data-index=" + index + "]").children();
                    //         tr.each(function (i,value) {
                    //             $(this).css("background-color", "rgba(255,0,0,.2)");//单元格背景颜色
                    //         })
                    // }
                    // $('#list_table').find('tr').css({'background':'#5FB878','color':'white'}).siblings().removeAttr('style')
                }
            });
          ea.listen();
        },
        list: function () {
          var $get = $("#where").val();
            var $cols= $("#cols").val();
            var cols = JSON.parse($cols);
            init.index_url = 'system.dress.index/list';
            init.export_url = 'system.dress.index/list_export';
            ea.table.render({
                init:init,
                search:false,
                where:JSON.parse($get),
                height: 760,
                limit: 1000,
                toolbar:[
                    'custom_export'
                ],
                limits:[100,200,500,1000],
                cols: cols
            });

            var table = layui.table;
            //转换静态表格
            var list_table = table.init('list', {
                done: function (res, curr, count) {

                }
            });
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