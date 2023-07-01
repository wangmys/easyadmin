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

    console.log(66)

    var Controller = {

        list: function () {
          console.log('66666')
          ea.listen();
        }
    };
    return Controller;
});