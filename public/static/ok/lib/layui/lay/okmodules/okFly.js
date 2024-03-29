/*flyio官方文档 https://wendux.github.io/dist/#/doc/flyio/readme*/
!function(e){function t(r){if(n[r])return n[r].exports;var o=n[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,t),o.l=!0,o.exports}var n={};t.m=e,t.c=n,t.i=function(e){return e},t.d=function(e,n,r){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:r})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=5)}({1:function(e,t,n){"use strict";var r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e};e.exports={type:function(e){return Object.prototype.toString.call(e).slice(8,-1).toLowerCase()},isObject:function(e,t){return t?"object"===this.type(e):e&&"object"===(void 0===e?"undefined":r(e))},isFormData:function(e){return"undefined"!=typeof FormData&&e instanceof FormData},trim:function(e){return e.replace(/(^\s*)|(\s*$)/g,"")},encode:function(e){return encodeURIComponent(e).replace(/%40/gi,"@").replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")},formatParams:function(e){function t(e,i){var s=o.encode,a=o.type(e);if("array"==a)e.forEach(function(e,n){o.isObject(e)||(n=""),t(e,i+"%5B"+n+"%5D")});else if("object"==a)for(var c in e)i?t(e[c],i+"%5B"+s(c)+"%5D"):t(e[c],s(c));else r||(n+="&"),r=!1,n+=i+"="+s(e)}var n="",r=!0,o=this;return this.isObject(e)?(t(e,""),n):e},merge:function(e,t){for(var n in t)e.hasOwnProperty(n)?this.isObject(t[n],1)&&this.isObject(e[n],1)&&this.merge(e[n],t[n]):e[n]=t[n];return e}}},5:function(e,t,n){function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}var o=function(){function e(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,n,r){return n&&e(t.prototype,n),r&&e(t,r),t}}(),i=n(1),s="undefined"!=typeof document,a=function(){function e(t){function n(e){function t(){e.p=n=r=null}var n=void 0,r=void 0;i.merge(e,{lock:function(){n||(e.p=new Promise(function(e,t){n=e,r=t}))},unlock:function(){n&&(n(),t())},clear:function(){r&&(r("cancel"),t())}})}r(this,e),this.engine=t||XMLHttpRequest,this.default=this;var o=this.interceptors={response:{use:function(e,t){this.handler=e,this.onerror=t}},request:{use:function(e){this.handler=e}}},s=o.request;n(o.response),n(s),this.config={method:"GET",baseURL:"",headers:{},timeout:0,params:{},parseJson:!0,withCredentials:!1}}return o(e,[{key:"request",value:function(e,t,n){var r=this,o=new this.engine,a="Content-Type",c=a.toLowerCase(),u=this.interceptors,f=u.request,l=u.response,p=f.handler,h=new Promise(function(u,h){function d(e){return e&&e.then&&e.catch}function m(e,t){e?e.then(function(){t()}):t()}function y(n){function r(e,t,r){m(l.p,function(){if(e){r&&(t.request=n);var o=e.call(l,t,Promise);t=void 0===o?t:o}d(t)||(t=Promise[0===r?"resolve":"reject"](t)),t.then(function(e){u(e)}).catch(function(e){h(e)})})}function f(e){e.engine=o,r(l.onerror,e,-1)}function p(e,t){this.message=e,this.status=t}t=n.body,e=i.trim(n.url);var y=i.trim(n.baseURL||"");if(e||!s||y||(e=location.href),0!==e.indexOf("http")){var v="/"===e[0];if(!y&&s){var g=location.pathname.split("/");g.pop(),y=location.protocol+"//"+location.host+(v?"":g.join("/"))}if("/"!==y[y.length-1]&&(y+="/"),e=y+(v?e.substr(1):e),s){var b=document.createElement("a");b.href=e,e=b.href}}var w=i.trim(n.responseType||""),O=-1!==["GET","HEAD","DELETE","OPTION"].indexOf(n.method),j=i.type(t),x=n.params||{};O&&"object"===j&&(x=i.merge(t,x)),x=i.formatParams(x);var P=[];x&&P.push(x),O&&t&&"string"===j&&P.push(t),P.length>0&&(e+=(-1===e.indexOf("?")?"?":"&")+P.join("&")),o.open(n.method,e);try{o.withCredentials=!!n.withCredentials,o.timeout=n.timeout||0,"stream"!==w&&(o.responseType=w)}catch(e){}var E=n.headers[a]||n.headers[c],T="application/x-www-form-urlencoded";i.trim((E||"").toLowerCase())===T?t=i.formatParams(t):i.isFormData(t)||-1===["object","array"].indexOf(i.type(t))||(T="application/json;charset=utf-8",t=JSON.stringify(t)),E||O||(n.headers[a]=T);for(var C in n.headers)if(C===a&&i.isFormData(t))delete n.headers[C];else try{o.setRequestHeader(C,n.headers[C])}catch(e){}o.onload=function(){try{var e=o.response||o.responseText;e&&n.parseJson&&-1!==(o.getResponseHeader(a)||"").indexOf("json")&&!i.isObject(e)&&(e=JSON.parse(e));var t=o.responseHeaders;if(!t){t={};var s=(o.getAllResponseHeaders()||"").split("\r\n");s.pop(),s.forEach(function(e){if(e){var n=e.split(":")[0];t[n]=o.getResponseHeader(n)}})}var c=o.status,u=o.statusText,h={data:e,headers:t,status:c,statusText:u};if(i.merge(h,o._response),c>=200&&c<300||304===c)h.engine=o,h.request=n,r(l.handler,h,0);else{var d=new p(u,c);d.response=h,f(d)}}catch(d){f(new p(d.msg,o.status))}},o.onerror=function(e){f(new p(e.msg||"Network Error",0))},o.ontimeout=function(){f(new p("timeout [ "+o.timeout+"ms ]",1))},o._options=n,setTimeout(function(){o.send(O?null:t)},0)}i.isObject(e)&&(n=e,e=n.url),n=n||{},n.headers=n.headers||{},m(f.p,function(){i.merge(n,JSON.parse(JSON.stringify(r.config)));var o=n.headers;o[a]=o[a]||o[c]||"",delete o[c],n.body=t||n.body,e=i.trim(e||""),n.method=n.method.toUpperCase(),n.url=e;var s=n;p&&(s=p.call(f,n,Promise)||n),d(s)||(s=Promise.resolve(s)),s.then(function(e){e===n?y(e):u(e)},function(e){h(e)})})});return h.engine=o,h}},{key:"all",value:function(e){return Promise.all(e)}},{key:"spread",value:function(e){return function(t){return e.apply(null,t)}}}]),e}();a.default=a,["get","post","put","patch","head","delete"].forEach(function(e){a.prototype[e]=function(t,n,r){return this.request(t,n,i.merge({method:e},r))}}),["lock","unlock","clear"].forEach(function(e){a.prototype[e]=function(){this.interceptors.request[e]()}}),function(e,t){t()}(0,function(){window.fly=new a,window.Fly=a}),e.exports=a}});

/**配置fly*/
function flyConfiguration(fly){
	fly.config.baseURL = ""; /**配置请求域名*/
	fly.config.headers = {
		'Content-Type': 'application/x-www-form-urlencoded',
		'uid':'xinchen'
	};

	//添加请求拦截器
	fly.interceptors.request.use(function (request) {
		var cache = layui.data('cache');
		 request.headers["token"]=cache.token;
		return request;
	});

	//添加响应拦截器，响应拦截器会在then/catch处理之前执行
	fly.interceptors.response.use(
		function (response) {
			//只将请求结果的data字段返回
			return response.data
		},
		function (err) {
			//发生网络错误后会走到这里
			return Promise.resolve(err);
		}
	);
	return fly;
}


layui.define(["jquery"], function (exprots) {
	var $ = layui.jquery;
	var okFly = flyConfiguration(fly);

	exprots("okFly", okFly);
});
