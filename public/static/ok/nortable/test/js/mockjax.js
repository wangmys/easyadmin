/**

 @Name：mockjax 引入jquery.mockjax.js
 @Author：岁月小偷
 @License：MIT

 */
layui.define(['jquery'], function (exports) {
  "use strict";
  var modelName = 'mockjax';
  window.$ = window.$ || layui.$;

  var scriptElement = document.createElement('script');
  var scriptElementFirst = document.getElementsByTagName('script')[0];
  scriptElement.async = true;
  scriptElement.charset = 'utf-8';
  scriptElement.src = 'test/js/jquery-mockjax/jquery.mockjax.js';
  scriptElementFirst.parentNode.insertBefore(scriptElement, scriptElementFirst);


  exports(modelName, {});
});
