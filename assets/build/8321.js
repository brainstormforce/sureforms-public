"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[8321],{2538:function(t,e,s){function n(t){let e="";const s=Object.entries(t);let n;for(;n=s.shift();){let[t,l]=n;if(Array.isArray(l)||l&&l.constructor===Object){const e=Object.entries(l).reverse();for(const[n,l]of e)s.unshift([`${t}[${n}]`,l])}else void 0!==l&&(null===l&&(l=""),e+="&"+[t,l].map(encodeURIComponent).join("="))}return e.substr(1)}function l(t){return(function(t){let e;try{e=new URL(t,"http://example.com").search.substring(1)}catch(t){}if(e)return e}(t)||"").replace(/\+/g,"%20").split("&").reduce(((t,e)=>{const[s,n=""]=e.split("=").filter(Boolean).map(decodeURIComponent);return s&&function(t,e,s){const n=e.length,l=n-1;for(let r=0;r<n;r++){let n=e[r];!n&&Array.isArray(t)&&(n=t.length.toString());const i=!isNaN(Number(e[r+1]));t[n]=r===l?s:t[n]||(i?[]:{}),Array.isArray(t[n])&&!i&&(t[n]={...t[n]}),t=t[n]}}(t,s.replace(/\]/g,"").split("["),n),t}),{})}function r(){let t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"",e=arguments.length>1?arguments[1]:void 0;if(!e||!Object.keys(e).length)return t;let s=t;const r=t.indexOf("?");return-1!==r&&(e=Object.assign(l(t),e),s=s.substr(0,r)),s+"?"+n(e)}s.d(e,{a:function(){return r},b:function(){return n},g:function(){return l}})},8321:function(t,e,s){s.r(e),s.d(e,{sc_wordpress_user:function(){return i}});var n=s(7492),l=s(9988),r=s(2538);s(7094);const i=class{constructor(t){(0,n.r)(this,t)}renderContent(){var t,e,s,r,i,o,u,c;return this.user?(0,n.h)(n.F,null,!!(null===(t=null==this?void 0:this.user)||void 0===t?void 0:t.display_name)&&(0,n.h)("sc-stacked-list-row",{style:{"--columns":"3"},mobileSize:480},(0,n.h)("div",null,(0,n.h)("strong",null,(0,l._)("Display Name","surecart"))),(0,n.h)("div",null,null===(e=this.user)||void 0===e?void 0:e.display_name),(0,n.h)("div",null)),!!(null===(s=null==this?void 0:this.user)||void 0===s?void 0:s.email)&&(0,n.h)("sc-stacked-list-row",{style:{"--columns":"3"},mobileSize:480},(0,n.h)("div",null,(0,n.h)("strong",null,(0,l._)("Account Email","surecart"))),(0,n.h)("div",null,null===(r=this.user)||void 0===r?void 0:r.email),(0,n.h)("div",null)),!!(null===(i=null==this?void 0:this.user)||void 0===i?void 0:i.first_name)&&(0,n.h)("sc-stacked-list-row",{style:{"--columns":"3"},mobileSize:480},(0,n.h)("div",null,(0,n.h)("strong",null,(0,l._)("First Name","surecart"))),(0,n.h)("div",null,null===(o=this.user)||void 0===o?void 0:o.first_name),(0,n.h)("div",null)),!!(null===(u=null==this?void 0:this.user)||void 0===u?void 0:u.last_name)&&(0,n.h)("sc-stacked-list-row",{style:{"--columns":"3"},mobileSize:480},(0,n.h)("div",null,(0,n.h)("strong",null,(0,l._)("Last Name","surecart"))),(0,n.h)("div",null,null===(c=this.user)||void 0===c?void 0:c.last_name),(0,n.h)("div",null))):this.renderEmpty()}renderEmpty(){return(0,n.h)("slot",{name:"empty"},(0,l._)("User not found.","surecart"))}render(){return(0,n.h)("sc-dashboard-module",{class:"customer-details"},(0,n.h)("span",{slot:"heading"},this.heading||(0,l._)("Account Details","surecart")," "),(0,n.h)("sc-button",{type:"link",href:(0,r.a)(window.location.href,{action:"edit",model:"user"}),slot:"end"},(0,n.h)("sc-icon",{name:"edit-3",slot:"prefix"}),(0,l._)("Update","surecart")),(0,n.h)("sc-card",{"no-padding":!0},(0,n.h)("sc-stacked-list",null,this.renderContent())))}};i.style=":host{display:block;position:relative}.customer-details{display:grid;gap:0.75em}"}}]);
//# sourceMappingURL=8321.js.map