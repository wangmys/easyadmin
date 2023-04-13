define(["jquery", "easy-admin"], function ($, ea) {
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.dress.dress/index',
        export_url: 'system.dress.dress/index_export'
    };
    var table = layui.table

    var Controller = {
        index: function () {
            var $get = $("#cols").val();
            var cols = JSON.parse($get);

            cols.forEach(function (i,value) {
                i.templet = function(d){
                    return that.templet(d,this)
                }
            })
            var that = this;
            ea.table.render({
                init:init,
                search:true,
                height: 760,
                limit:1000,
                toolbar:[
                    'custom_export'
                ],
                cols: [cols],
                limits:[1000,2000]
            });

            // var $_field = $("#_field").val();
            // var _field = JSON.parse($_field);
            // console.log(_field)
            // var list_table = table.init('list', {
            //     url:'/admin/system.dress.dress/stock',
            //     cols: [_field],
            // });

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