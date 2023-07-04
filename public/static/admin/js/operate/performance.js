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

        list: function () {

            // 选择风格
            xmSelect.render({
                el: '#xm-fengge',
                filterable: true,
                toolbar: {show: true},
                name: '风格',
                // showCount: 1,
                theme: {
                    color: '#1cbbb4',
                },
                data: [
                    {name: "基本款", value: "基本款", selected: ""},
                    {name: "引流款", value: "引流款", selected: ""},
                ]
            })

        }
    };
    return Controller;
});