layui.define(["jquery","laydate","laytpl"],function(exports) {
    "use strict";
    var $ = layui.jquery,laydate = layui.laydate,
        laytpl = layui.laytpl,ddTpl = [
            '{{# if(d.data){ }}'
               ,'{{# layui.each(d.data, function(index,item){ }}'
                   ,'<dd lay-value="{{ item.name }}" class="layui-define-tcs-dd">{{ item.value }}</dd>',
               ,'{{# }); }}'
            ,'{{# } else { }}'
                ,'<dd lay-value="" class="layui-define-tcs-dd">无数据</dd>'
            ,'{{# } }}'
        ].join(''),selectTpl = [ //单选下拉框模板
            '<div class="layui-define-tcs-div layui-define-tcs-div-one" style="{{d.style.type}}px; width: {{d.style.width}}px; left: {{d.style.left}}px;">'
              , '<dl>'
                  ,ddTpl
              , '</dl>'
            , '</div>'
        ].join(''),selectMoreTpl = [ //多选下拉框模板
            '<div class="layui-define-tcs-div" style="{{d.style.type}}px; width: {{d.style.width}}px; left: {{d.style.left}}px;">'
              ,'<div>'
                 ,'<span style="text-align: left">'
                    ,'<button type="button" id="selectAll" class="layui-btn layui-btn-sm layui-btn-primary">全选</button>'
                 ,'</span>'
                 ,'<span style="float: right">'
                    ,'<button id="confirmBtn" type="button" class="layui-btn layui-btn-sm layui-btn-primary">确定</button>'
                 ,'</span>'
              ,'</div>'
              ,'<div class="layui-define-tcs-div-more">'
                 ,'<ul class="layui-define-tcs-ul" >'
                    ,'{{# if(d.data){ }}'
                        ,'{{# d.data.forEach(function(item){ }}'
                            ,'<li class="layui-define-tcs-checkbox" data-name="{{ item.name }}" data-value="{{ item.value }}">'
                               ,'<div class="layui-define-tcs-checkbox" lay-skin="primary">'
                                  ,'<span>{{ item.value }}</span>'
                                  ,'<i class="layui-icon layui-icon-ok"></i>'
                               ,'</div>'
                            ,'</li>'
                        ,'{{# }); }}'
                    ,'{{# } else { }}'
                        ,'<li>无数据</li>'
                    ,'{{# } }}'
                 ,'</ul>'
              ,'</div>'
            ,'</div>'
        ].join(''),ddSearchTpl = [
            '{{# if(d.data){ }}'
               ,'{{# d.data.forEach(function(item){ }}'
                   ,'{{# if((item.value+\'\').indexOf(d.search)>-1){ }}'
                       ,'<dd lay-value="{{ item.name }}" class="layui-define-tcs-dd">{{ item.value }}</dd>',
                   ,'{{# } }}'
               ,'{{# }); }}'
            ,'{{# } else { }}'
                ,'<dd lay-value="" class="layui-define-tcs-dd">无数据</dd>'
            ,'{{# } }}'
        ].join(''),liTpl = [
            '<li class="{{ d.classText }}" data-name="{{ d.name }}" data-value="{{ d.value }}">'
               ,'<div class="layui-define-tcs-checkbox" lay-skin="primary">'
                   ,'<span>{{ d.value }}</span>'
                   ,'<i style="{{ d.bkgColor }}" class="layui-icon layui-icon-ok"></i>'
               ,'</div>'
            ,'</li>'
        ].join('');
    var Class = function () {}; //构造器
    var singleInstance = new Class(); //单列
    var inFunc = function () {singleInstance.leaveStat = false;},outFunc = function () {singleInstance.leaveStat = true;};
    document.onclick = function () {if(singleInstance.leaveStat)singleInstance.deleteAll();};

    //日期选择框
    Class.prototype.date = function(options){
        var othis = this;
        othis.callback = options.callback,othis.element = options.element,othis.dateType = options.dateType;
        othis.dateType = othis.isEmpty(othis.dateType) ? "datetime":othis.dateType;
        var that = options.element;
        if ($(that).find('input').length>0)return;
        othis.deleteAll(),othis.leaveStat = false;
        var input = $('<input class="layui-input layui-define-tcs-input" type="text" id="thisDate">');
        $(that).append(input),input.focus();
        //日期时间选择器 (show: true 表示直接显示)
        laydate.render({elem: '#thisDate',type: othis.dateType,show: true,done:function (value, date) {
            othis.deleteAll();
            if(othis.callback)othis.callback({value:value,td:that});
        }});
        $('div.layui-laydate').hover(inFunc,outFunc),$(that).hover(inFunc,outFunc);
        layui.stope();
    };

    //判断是否为空函数
    Class.prototype.isEmpty = function(dataStr){return typeof dataStr === 'undefined' || dataStr === null || dataStr.length <= 0;};

    //生成下拉框函数入口
    Class.prototype.register = function(options){
        var othis = this;
        othis.enabled = options.enabled,othis.callback = options.callback,othis.data = options.data,othis.element = options.element;
        var that = othis.element;
        if ($(that).find('input').length>0)return;
        othis.deleteAll(that),othis.leaveStat = false; //鼠标离开单元格或下拉框div区域状态，默认不离开（false）
        var input = $('<input class="layui-input layui-define-tcs-input" placeholder="关键字搜索">');
        var icon = $('<i class="layui-icon layui-define-tcs-edge" data-td-text="'+$(that).find("div.layui-table-cell").eq(0).text()+'" >&#xe625;</i>');
        othis.input = input,othis.icon = icon;
        $(that).append(input),$(that).append(icon),input.focus();
        var thisY = that.getBoundingClientRect().top; //单元格y坐标
        var thisX = that.getBoundingClientRect().left; //单元格x坐标
        var thisHeight = that.offsetHeight,thisWidth = that.offsetWidth //单元格宽度和高度
            ,clientHeight = document.documentElement['clientHeight'] //窗口高度
            ,scrollTop = document.body['scrollTop'] | document.documentElement['scrollTop'];//滚动条滚动高度
        var bottom = clientHeight-scrollTop-thisY+3; //div底部距离窗口底部长度
        var top = thisY+thisHeight+scrollTop+3; //div元素y坐标
        //当前y坐标大于窗口0.55倍的高度则往上延伸，否则往下延伸。
        var type = thisY+thisHeight > 0.55*clientHeight ?  'top:auto;bottom: '+bottom : 'bottom:auto;top:'+top;
        //下三角图标旋转180度成上三角图标
        thisY+thisHeight > 0.55*clientHeight ? $(icon).addClass("layui-define-tcs-edgeTransform") : '';
        //获取下拉框div模板
        var html = othis.enabled ? selectMoreTpl : selectTpl;
        //生成下拉框
        $('body').append(laytpl(html).render({data: othis.data,style: {type: type,width: thisWidth,left: thisX}}));
        //事件注册
        othis.events();
    };

    //删除所有删除下拉框和时间选择框
    Class.prototype.deleteAll = function(){
        var othis = this;
        //删除下拉框
        $('div.layui-table-body').find('td').each(function () {
            var icon = $(this).find('i.layui-define-tcs-edge'),text = icon.attr('data-td-text');
            if(icon.length === 0)return;
            $(this).find('input.layui-define-tcs-input').remove(),icon = icon.eq(0);
            $(this).find("div.layui-table-cell").eq(0).text(text),icon.remove();
        });
        //删除时间选择框
        $("#thisDate").next().remove(),$("#thisDate").remove();
        $("div.layui-laydate").remove(),$('div.layui-define-tcs-div').remove();
        //清除leaveStat（离开状态属性）
        delete othis.leaveStat;
    };

    //注册事件
    Class.prototype.events = function(){
        var othis = this;
        //给输入框注册值改变事件
        othis.input.bind('input propertychange', function(){othis.enabled ? lsFunc(this.value) : dsFunc(this.value);});
        var lsFunc = function(val){
            if(othis.isEmpty(val)) return;
            var ul = $('div.layui-define-tcs-div').find('ul.layui-define-tcs-ul').eq(0),liArr = [];
            $(ul).find('li').each(function () {
                var thisValue = $(this).data('value');
                thisValue = othis.isEmpty(thisValue) ? "" : thisValue;
                if(thisValue.indexOf(val) > -1){
                    var classText = $(this).attr("class");
                    var backgroundColor = classText.indexOf("li-checked") > -1 ? "background-color: #60b979" : '';
                    var html = laytpl(liTpl).render({classText:classText,name:$(this).data('name'),value:thisValue,bkgColor:backgroundColor});
                    liArr.push(html),$(this).remove();
                }
            });
            ul.prepend(liArr.join("")),liFunc();
        };
        var dsFunc = function(val){
            var dl = $('div.layui-define-tcs-div').find('dl').eq(0);
            var html = othis.isEmpty(val) ? ddTpl : ddSearchTpl;
            dl.html(""),dl.prepend(laytpl(html).render({data: othis.data,search: val})),ddFunc();
        };
        //注册点击事件
        othis.icon.bind('click',function () {layui.stope(),othis.deleteAll();});

        //给dd元素注册点击事件(单选)
        var ddFunc = function () {
            var ddArr = $('div.layui-define-tcs-div').find('dd');
            ddArr.unbind('click'),ddArr.bind('click',function (e) {
                layui.stope(e),othis.deleteAll();
                if(othis.callback)othis.callback({select:{name:$(this).attr('lay-value'),value:$(this).text()},td:othis.element});
            });
        };
        //给li元素注册点击事件（多选）
        var liFunc = function(){
            var liArr = $('div.layui-define-tcs-div').find('li');
            liArr.unbind('click'),liArr.bind('click',function (e) {
                layui.stope(e);
                var icon = $(this).find("i"),liClass = $(this).attr("class");
                (liClass && liClass.indexOf("li-checked")) > -1 ? (icon.css("background-color","#fff"),$(this).removeClass("li-checked"))
                    : (icon.css("background-color","#60b979"),$(this).addClass("li-checked"));
            });
        };
        ddFunc(),liFunc();

        //给下拉框和当前单元格（td）注册鼠标悬停事件
        $('div.layui-define-tcs-div').hover(inFunc,outFunc),$(othis.element).hover(inFunc,outFunc);

        //“确定”按钮
        $('#confirmBtn').bind('click',function (e) {
            var dataList = new Array();
            $("div.layui-define-tcs-div").find("div li").each(function (e) {
                var liClass = $(this).attr("class");
                if(!liClass || liClass.indexOf("li-checked") <= -1)return;
                dataList.push({name:$(this).data("name"),value:$(this).data("value")});
            });
            othis.deleteAll();
            if(othis.callback)othis.callback({select:dataList,td:othis.element});
        });

        //“全选”按钮
        $('#selectAll').bind('click',function () {
            var btn = this,status = $(btn).attr('data-status');
            $('ul.layui-define-tcs-ul').find('li').each(function (e) {
                var icon = $(this).find("i");
                othis.isEmpty(status) || status === 'false'
                    ? (icon.css("background-color","#60b979"),$(this).addClass("li-checked"),$(btn).attr("data-status","true"))
                    : (icon.css("background-color","#fff"),$(this).removeClass("li-checked"),$(btn).attr("data-status","false"));
            });
        });
    };

    var active = {
        createSelect:function (options) {singleInstance.register(options);},
        update:function (options) {$(options.element).find("div.layui-table-cell").eq(0).text(options.value);},
        createDate:function (options) {singleInstance.date(options);}
    };
    layui.link(layui.cache.base + 'css/layuiTableColumnEdit.css');
    exports('layuiTableColumnEdit', active);
});