"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[712],{8517:function(e,t,n){function r(e){let t="";const n=Object.entries(e);let r;for(;r=n.shift();){let[e,o]=r;if(Array.isArray(o)||o&&o.constructor===Object){const t=Object.entries(o).reverse();for(const[r,o]of t)n.unshift([`${e}[${r}]`,o])}else void 0!==o&&(null===o&&(o=""),t+="&"+[e,o].map(encodeURIComponent).join("="))}return t.substr(1)}function o(e){return(function(e){let t;try{t=new URL(e,"http://example.com").search.substring(1)}catch(e){}if(t)return t}(e)||"").replace(/\+/g,"%20").split("&").reduce(((e,t)=>{const[n,r=""]=t.split("=").filter(Boolean).map(decodeURIComponent);return n&&function(e,t,n){const r=t.length,o=r-1;for(let i=0;i<r;i++){let r=t[i];!r&&Array.isArray(e)&&(r=e.length.toString());const a=!isNaN(Number(t[i+1]));e[r]=i===o?n:e[r]||(a?[]:{}),Array.isArray(e[r])&&!a&&(e[r]={...e[r]}),e=e[r]}}(e,n.replace(/\]/g,"").split("["),r),e}),{})}function i(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"",t=arguments.length>1?arguments[1]:void 0;if(!t||!Object.keys(t).length)return e;let n=e;const i=e.indexOf("?");return-1!==i&&(t=Object.assign(o(e),t),n=n.substr(0,i)),n+"?"+r(t)}n.d(t,{a:function(){return i},b:function(){return r},g:function(){return o}})},712:function(e,t,n){n.d(t,{a:function(){return P},g:function(){return i}});var r=n(290),o=n(8517);function i(e,t){return(0,o.g)(e)[t]}function a(e,t){return void 0!==i(e,t)}function s(e){const t=e.split("?"),n=t[1],r=t[0];return n?r+"?"+n.split("&").map((e=>e.split("="))).sort(((e,t)=>e[0].localeCompare(t[0]))).map((e=>e.join("="))).join("&"):r}const c=(e,t)=>{let n,r,o=e.path;return"string"==typeof e.namespace&&"string"==typeof e.endpoint&&(n=e.namespace.replace(/^\/|\/$/g,""),r=e.endpoint.replace(/^\//,""),o=r?n+"/"+r:n),delete e.namespace,delete e.endpoint,t({...e,path:o})},d=(e,t)=>{let{path:n,url:r,...i}=e;return{...i,url:r&&(0,o.a)(r,t),path:n&&(0,o.a)(n,t)}},u=e=>e.json?e.json():Promise.reject(e),l=e=>{const{next:t}=(e=>{if(!e)return{};const t=e.match(/<([^>]+)>; rel="next"/);return t?{next:t[1]}:{}})(e.headers.get("link"));return t},p=async(e,t)=>{if(!1===e.parse)return t(e);if(!(e=>{const t=!!e.path&&-1!==e.path.indexOf("per_page=-1"),n=!!e.url&&-1!==e.url.indexOf("per_page=-1");return t||n})(e))return t(e);const n=await P({...d(e,{per_page:100}),parse:!1}),r=await u(n);if(!Array.isArray(r))return r;let o=l(n);if(!o)return r;let i=[].concat(r);for(;o;){const t=await P({...e,path:void 0,url:o,parse:!1}),n=await u(t);i=i.concat(n),o=l(t)}return i},h=new Set(["PATCH","PUT","DELETE"]),f="GET",w=function(e){return arguments.length>1&&void 0!==arguments[1]&&!arguments[1]?e:204===e.status?null:e.json?e.json():Promise.reject(e)},m=e=>{const t={code:"invalid_json",message:(0,r._)("The response is not a valid JSON response.")};if(!e||!e.json)throw t;return e.json().catch((()=>{throw t}))},g=function(e){let t=!(arguments.length>1&&void 0!==arguments[1])||arguments[1];return Promise.resolve(w(e,t)).catch((e=>v(e,t)))};function v(e){if(arguments.length>1&&void 0!==arguments[1]&&!arguments[1])throw e;return m(e).then((e=>{const t={code:"unknown_error",message:(0,r._)("An unknown error occurred.")};throw e||t}))}const y={Accept:"application/json, */*;q=0.1"},j={credentials:"include"},O=[(e,t)=>("string"!=typeof e.url||a(e.url,"_locale")||(e.url=(0,o.a)(e.url,{_locale:"user"})),"string"!=typeof e.path||a(e.path,"_locale")||(e.path=(0,o.a)(e.path,{_locale:"user"})),t(e)),c,(e,t)=>{const{method:n=f}=e;return h.has(n.toUpperCase())&&(e={...e,headers:{...e.headers,"X-HTTP-Method-Override":n,"Content-Type":"application/json"},method:"POST"}),t(e)},p],b=e=>{if(e.status>=200&&e.status<300)return e;throw e};let _=e=>{const{url:t,path:n,data:o,parse:i=!0,...a}=e;let{body:s,headers:c}=e;return c={...y,...c},o&&(s=JSON.stringify(o),c["Content-Type"]="application/json"),window.fetch(t||n||window.location.href,{...j,...a,body:s,headers:c}).then((e=>Promise.resolve(e).then(b).catch((e=>v(e,i))).then((e=>g(e,i)))),(e=>{if(e&&"AbortError"===e.name)throw e;throw{code:"fetch_error",message:(0,r._)("You are probably offline.")}}))};function P(e){return O.reduceRight(((e,t)=>n=>t(n,e)),_)(e).catch((t=>"rest_cookie_invalid_nonce"!==t.code?Promise.reject(t):window.fetch(P.nonceEndpoint).then(b).then((e=>e.text())).then((t=>(P.nonceMiddleware.nonce=t,P(e))))))}var T,x,A;P.use=function(e){O.unshift(e)},P.setFetchHandler=function(e){_=e},P.createNonceMiddleware=function(e){const t=(e,n)=>{const{headers:r={}}=e;for(const o in r)if("x-wp-nonce"===o.toLowerCase()&&r[o]===t.nonce)return n(e);return n({...e,headers:{...r,"X-WP-Nonce":t.nonce}})};return t.nonce=e,t},P.createPreloadingMiddleware=function(e){const t=Object.keys(e).reduce(((t,n)=>(t[s(n)]=e[n],t)),{});return(e,n)=>{const{parse:r=!0}=e;let o=e.path;if(!o&&e.url){const t=i(e.url,"rest_route");"string"==typeof t&&(o=t)}if("string"==typeof o){const n=e.method||"GET",i=s(o);if("GET"===n&&t[i]){const e=t[i];return delete t[i],Promise.resolve(r?e.body:new window.Response(JSON.stringify(e.body),{status:200,statusText:"OK",headers:e.headers}))}if("OPTIONS"===n&&t[n]&&t[n][i]){const e=t[n][i];return delete t[n][i],Promise.resolve(r?e.body:e)}}return n(e)}},P.createRootURLMiddleware=e=>(t,n)=>c(t,(t=>{let r,o=t.url,i=t.path;return"string"==typeof i&&(r=e,-1!==e.indexOf("?")&&(i=i.replace("?","&")),i=i.replace(/^\//,""),"string"==typeof r&&-1!==r.indexOf("?")&&(i=i.replace("?","&")),o=r+i),n({...t,url:o})})),P.fetchAllMiddleware=p,P.mediaUploadMiddleware=(e,t)=>{if(!function(e){const t=!!e.method&&"POST"===e.method;return(!!e.path&&-1!==e.path.indexOf("/wp/v2/media")||!!e.url&&-1!==e.url.indexOf("/wp/v2/media"))&&t}(e))return t(e);let n=0;const o=e=>(n++,t({path:`/wp/v2/media/${e}/post-process`,method:"POST",data:{action:"create-image-subsizes"},parse:!1}).catch((()=>n<5?o(e):(t({path:`/wp/v2/media/${e}?force=true`,method:"DELETE"}),Promise.reject()))));return t({...e,parse:!1}).catch((t=>{const n=t.headers.get("x-wp-upload-attachment-id");return t.status>=500&&t.status<600&&n?o(n).catch((()=>!1!==e.parse?Promise.reject({code:"post_process",message:(0,r._)("Media upload failed. If this is a photo or a large image, please scale it down and try again.")}):Promise.reject(t))):v(t,e.parse)})).then((t=>g(t,e.parse)))},(null===window||void 0===window?void 0:window.scData)&&(P.use(P.createRootURLMiddleware(null===(T=null===window||void 0===window?void 0:window.scData)||void 0===T?void 0:T.root_url)),P.nonceMiddleware=P.createNonceMiddleware(null===(x=null===window||void 0===window?void 0:window.scData)||void 0===x?void 0:x.nonce),P.use(P.nonceMiddleware),P.nonceEndpoint=null===(A=null===window||void 0===window?void 0:window.scData)||void 0===A?void 0:A.nonce_endpoint)}}]);
//# sourceMappingURL=712.js.map