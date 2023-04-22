define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.dress/index',
        list_url: 'system.dress.dress/list',
        export_url: 'system.dress.dress/index_export'
    };
    var table = layui.table

    var Controller = {
        index: function () {
            var $cols = $("#cols").val();
            var cols = JSON.parse($("#cols").val());
            var where = JSON.parse($("#where").val())
            cols.forEach(function (i,value) {
                i.templet = function(d){
                    return that.templet(d,this)
                }
            })
            var that = this;
            ea.table.render({
                init:init,
                where:where,
                search:true,
                height: 760,
                limit:1000,
                toolbar:[
                    'custom_export'
                ],
                cols: [cols],
                limits:[1000,2000]
            });

            var list_table = table.init('standard_table', {

            });

            ea.listen();
        },
        list: function () {
            var $cols = $("#cols").val();
            var cols = JSON.parse($("#cols").val());
            var where = JSON.parse($("#where").val())
            cols.forEach(function (i,value) {
                i.templet = function(d){
                    return that.templet(d,this)
                }
            })
            var that = this;
            init.index_url = init.list_url;
            init.export_url = 'system.dress.dress/list_export';
            ea.table.render({
                init:init,
                where:where,
                search:true,
                height: 760,
                limit:1000,
                toolbar:[
                    'custom_export'
                ],
                cols: [cols],
                limits:[1000,2000]
            });

            var list_table = table.init('standard_table', {

            });

            ea.listen();
        },
        templet:function (_data,_this) {
            var key = "_"+_this.field;
            if(_data[_this.field] === null){
                _data[_this.field] = 0;
            }
            if(_data[key] === true){
                //得到当前行数据，并拼接成自定义模板
                return '<div style="width: 100%;height:100%;display: inline-block;background: rgba(255,0,0,.2)">'+ _data[_this.field] +'</div>'
            }
            return _data[_this.field];
        }
    };
    return Controller;
});