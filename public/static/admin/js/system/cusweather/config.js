define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        save_url:ea.url('system.cusweather.config/saveConfig'),
        index_url:ea.url('/system.cusweather.config/index')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
             this.pinyin();
        },
        // 保存配置
        saveConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="id[]"]').val(res.data.id)
                ea.msg.success(res.msg);
                location.reload();
             });
        },

        //窗数陈列标准
        pinyin:function (){
            var html = '';
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.pinyin-select'), function (key, element) {

                $(element).find('.get').on('click', function(){
                    var _url = url.save_url;
                    var weather_prefix = $(element).find('input[name="weather_prefix[]"]').val();
                    var id = $(element).find('input[name="id[]"]').val();
                    var _data = {
                        weather_prefix:weather_prefix,
                        id:id
                    }
                    // 保存配置
                    that.saveConfig($(element), _url, _data);
                })

           });
            
            ea.listen();
       }


    };
    return Controller;
});