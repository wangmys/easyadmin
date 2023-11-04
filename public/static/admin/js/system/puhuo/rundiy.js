define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form,
    table = layui.table,
    url = {
        savePuhuoRun_url:ea.url('system.puhuo.Rundiy/savePuhuoRun'),
        getPuhuoRun_url:ea.url('system.puhuo.Rundiy/getPuhuoRun')
    }

    var Controller = {
        index: function () {
             var that = this;
             // 调用
            this.rundiy();

        },

        //获取手动铺货耗时
        getPuhuoRun:function (element,_url,_data) {
            var that = this;

            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log('getpuhuorun...', res);

                layer.confirm(res.data.msg,{},function () {

                    var _url_save = url.savePuhuoRun_url;
                    that.savePuhuoRun(element, _url_save, _data);

                    layer.closeAll()
                })

             });

        },
        //保存手动铺货记录
        savePuhuoRun:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                // console.log('savePuhuoRun。。。。。..', res);
                alert(res.data.msg);
             });
        },



        // 保存仓库预留参数配置
        savePuhuoZdySet:function (element,_url,_data) {
            ea.request.post({
                url:_url,
                data:_data
            },function (res) {
                //console.log(122, res);
                element.attr('lay-id',res.data.id);
                element.find('input[name="Yuncang"]').val(res.data.Yuncang)
                element.find('input[name="id"]').val(res.data.id)
                ea.msg.success(res.msg);
             });
        },
        // 删除配置
        delConfig:function (_this,_url,_id) {
            ea.request.get({
                url:_url,
                data:{id:_id}
            },function (res) {
                //console.log(res);
                if(res.code == 1){
                    _this.parents('tr').remove();
                    layer.closeAll()
                }
                ea.msg.success(res.msg);
            })
        },

        bind:function (element) {
            var gender = element.find('.Commonfield')[0];
            var that = this;
            var data = JSON.parse(element.attr('lay-data'));
            var genderSelect = xmSelect.render({
                el: gender,
                filterable: true,
                name: 'Commonfield',
                data: function(){
                    return data
                }
            })
        },

        //贵阳云仓配置
        rundiy:function (){
            var that = this;
            var html = '';

            // 获取渲染后端渲染的数据,重新绑定事件
            $.each($('.rundiy-select'), function (key, element) {

                //编辑 保存
                $(element).find('.get_rundiy').on('click', function(){
                    var _url_get = url.getPuhuoRun_url;
                    var _data = {}
                    var return_msg = that.getPuhuoRun($(element), _url_get, _data);

                })// .bind(genderSelect)

            });
                
            ea.listen();
        }


    };
    return Controller;
});