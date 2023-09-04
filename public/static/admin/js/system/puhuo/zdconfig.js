define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        saveZhidingGoodsConfig_url:ea.url('system.puhuo.Zdconfig/saveZhidingGoodsConfig'),
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
            this.guiyang_goods_config();
            this.wuhan_goods_config();
            this.guangzhou_goods_config();
            this.nanchang_goods_config();
            this.changsha_goods_config();

        },
        // 保存仓库预留参数配置
        saveZhidingGoodsConfig:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log(res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="sign_id"]').val(res.data.sign_id)
                ea.msg.success(res.msg);
             });
        },
        // 删除配置
        // delConfig:function (_this,_url,_id) {
        //     ea.request.get({
        //         url:_url,
        //         data:{id:_id}
        //     },function (res) {
        //         if(res.code == 1){
        //             _this.parents('tr').remove();
        //             layer.closeAll()
        //         }
        //         ea.msg.success(res.msg);
        //     })
        // },

        //贵阳云仓配置
        guiyang_goods_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.guiyang_goods_config-select'), function (key, element) {

                $(element).find('.get_guiyang_goods_config').on('click', function(){
                    var _url = url.saveZhidingGoodsConfig_url;
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        GoodsNo:GoodsNo,
                        Yuncang:sign_id,
                    }
                    // 保存配置
                    that.saveZhidingGoodsConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });
                
            ea.listen();
        },

        //武汉云仓配置
        wuhan_goods_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.wuhan_goods_config-select'), function (key, element) {

                $(element).find('.get_wuhan_goods_config').on('click', function(){
                    var _url = url.saveZhidingGoodsConfig_url;
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        GoodsNo:GoodsNo,
                        Yuncang:sign_id,
                    }
                    // 保存配置
                    that.saveZhidingGoodsConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });
                
            ea.listen();
        },

        //广州云仓配置
        guangzhou_goods_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.guangzhou_goods_config-select'), function (key, element) {

                $(element).find('.get_guangzhou_goods_config').on('click', function(){
                    var _url = url.saveZhidingGoodsConfig_url;
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        GoodsNo:GoodsNo,
                        Yuncang:sign_id,
                    }
                    // 保存配置
                    that.saveZhidingGoodsConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });
                
            ea.listen();
        },

        //南昌云仓配置
        nanchang_goods_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.nanchang_goods_config-select'), function (key, element) {

                $(element).find('.get_nanchang_goods_config').on('click', function(){
                    var _url = url.saveZhidingGoodsConfig_url;
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        GoodsNo:GoodsNo,
                        Yuncang:sign_id,
                    }
                    // 保存配置
                    that.saveZhidingGoodsConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });

            ea.listen();
        },

        //长沙云仓配置
        changsha_goods_config:function (){
            var that = this;

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.changsha_goods_config-select'), function (key, element) {

                $(element).find('.get_changsha_goods_config').on('click', function(){
                    var _url = url.saveZhidingGoodsConfig_url;
                    var GoodsNo = $(element).find('input[name="GoodsNo"]').val();
                    var sign_id = $(element).find('input[name="sign_id"]').val();
    
                    var _data = {
                        GoodsNo:GoodsNo,
                        Yuncang:sign_id,
                    }
                    // 保存配置
                    that.saveZhidingGoodsConfig($(element), _url, _data);
                })// .bind(genderSelect)

            });
                
            ea.listen();
        }


    };
    return Controller;
});