"use strict";
layui.define([], function (exprots) {
    let okMock = {
        api: {
            jxclist: "/yangjian/jxclist",
            listtask: "/Task/list",
            listUser: "/member/Userlist",
            listAuth: "/Auth/AuthRoleList",
            listRole: "/Auth/listRole",
            Toutlist: "/tour/list",

            menu: {
                list: "https://easy-mock.com/mock/5d0ce725424f15399a6c2068/okadmin/menu/list"
            },
            user: {
                list: "http://rap2api.taobao.org/app/mock/233041/user/list",
            },
            role: {
                list: "http://rap2api.taobao.org/app/mock/233041/role/list"
            },
            permission: {
                list: "http://rap2api.taobao.org/app/mock/233041/permission/list",
            },
            article: {
                list: "http://rap2api.taobao.org/app/mock/233041/article/list"
            },
            task: {
                list: "http://rap2api.taobao.org/app/mock/233041/task/list"
            },
            link: {
                list: "http://rap2api.taobao.org/app/mock/233041/link/list"
            },
            product: {
                list: "http://rap2api.taobao.org/app/mock/233041/product/list"
            },
            log: {
                list: "https://easy-mock.com/mock/5d0ce725424f15399a6c2068/okadmin/log/list"
            },
            message: {
                list: "http://rap2api.taobao.org/app/mock/233041/message/list"
            },
            download: {
                list: "http://rap2api.taobao.org/app/mock/233041/download/list"
            },
            bbs: {
                list: "http://rap2api.taobao.org/app/mock/233041/bbs/list"
            }
        }
    };
    exprots("okMock", okMock);
});
