/**

 @Name：testTablePlug tablePlug测试页面引用的组件
 @Author：岁月小偷
 @License：MIT

 */
layui.config({base: 'layui/plug/'})
// layui.config({base: 'download/tablePlug/'})
//   .extend({tablePlug: 'tablePlug.min'})
  .extend({tablePlug: 'tablePlug/tablePlug'})
  .extend({formSelects: '{/}test/js/formSelects/formSelects-v4', renderFormSelectsIn: '{/}test/js/renderFormSelectsIn'})
  .define(['tablePlug', 'laydate', 'renderFormSelectsIn'], function (exports) {
    "use strict";
    var $ = layui.$,
      form = layui.form,
      layer = layui.layer,
      table = layui.table,
      tablePlug = layui.tablePlug;
    // 当前表格要不要智能reload
    form.on('switch(tableSmartSwitch)', function (data) {
      var elem = $(data.elem);
      var formElem = elem.closest('.layui-form');
      var tableElem = formElem.next('table');
      var tableId = tableElem.next() ? (tableElem.next().attr('lay-id') || tableElem.attr('id')) : tableElem.attr('id');
      tablePlug.tableCheck.reset(tableId);
      table.reload(tableId, {
        smartReloadModel: data.elem.checked
      });
    });


    exports('testTablePlug', {});
  });
