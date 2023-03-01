"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[9161],{682:function(r,n,e){e.d(n,{o:function(){return t}});var o=e(7492),t=function(r,n,e){void 0===e&&(e=!0);var t="Function"===r.constructor.name?r.prototype:r,i=t.componentWillLoad;t.componentWillLoad=function(){var r,t=this,u=(0,o.g)(this),c={promise:new Promise((function(n){r=n})),resolve:r},s=new CustomEvent("openWormhole",{bubbles:!0,composed:!0,detail:{consumer:this,fields:n,updater:function(r,n){(r in u?u:t)[r]=n},onOpen:c}});u.dispatchEvent(s);var l=function(){if(i)return i.call(t)};return e?c.promise.then((function(){return l()})):l()}}},5241:function(r,n,e){e.d(n,{a:function(){return u},g:function(){return t},i:function(){return s},t:function(){return c}});var o=e(9988);const t=r=>r.amount_off&&r.currency?i({amount:r.amount_off,currency:r.currency}):r.percent_off?(0|r.percent_off)+"% off":"",i=({amount:r,currency:n})=>{const e=((r,n)=>["bif","clp","djf","gnf","jpy","kmf","krw"].includes(n)?r:r/100)(r,n);return`${new Intl.NumberFormat(void 0,{style:"currency",currency:n}).format(parseFloat(e.toFixed(2)))}`},u=(r="usd")=>{const[n]=new Intl.NumberFormat(void 0,{style:"currency",currency:r}).formatToParts();return null==n?void 0:n.value},c=(r,n,e=(0,o._)("every","surecart"),t=(0,o._)("once","surecart"),i=!1)=>{switch(n){case"day":return`${e} ${(0,o.s)(i?(0,o.a)("%d day","%d days",r,"surecart"):(0,o.a)("day","%d days",r,"surecart"),r)}`;case"week":return`${e} ${(0,o.s)(i?(0,o.a)("%d week","%d weeks",r,"surecart"):(0,o.a)("week","%d weeks",r,"surecart"),r)}`;case"month":return`${e} ${(0,o.s)(i?(0,o.a)("%d month","%d months",r,"surecart"):(0,o.a)("month","%d months",r,"surecart"),r)}`;case"year":return`${e} ${(0,o.s)(i?(0,o.a)("%d year","%d years",r,"surecart"):(0,o.a)("year","%d years",r,"surecart"),r)}`;default:return t}},s=(r,n={})=>{if(!r)return"";const{showOnce:e,labels:t}=n,{interval:i=(0,o._)("every","surecart"),period:u=(0,o._)("for","surecart")}=t||{};return`${l(r,i,e?(0,o._)("once","surecart"):"")} ${a(r,u)}`},l=(r,n,e=(0,o._)("once","surecart"))=>r.recurring_interval_count&&r.recurring_interval?c(r.recurring_interval_count,r.recurring_interval,` ${n}`,e):"",a=(r,n,e="")=>(null==r?void 0:r.recurring_period_count)&&(null==r?void 0:r.recurring_interval)?c(((null==r?void 0:r.recurring_period_count)||0)*(null==r?void 0:r.recurring_interval_count),null==r?void 0:r.recurring_interval,` ${n}`,e,!0):""},9161:function(r,n,e){e.r(n),e.d(n,{sc_order_confirmation_totals:function(){return c}});var o=e(7492),t=e(5241),i=e(9988),u=e(682);e(7094);const c=class{constructor(r){(0,o.r)(this,r)}renderDiscountLine(){var r,n,e,u,c,s,l,a,d,v,p,m,f,h,y;if(!(null===(e=null===(n=null===(r=this.order)||void 0===r?void 0:r.discount)||void 0===n?void 0:n.promotion)||void 0===e?void 0:e.code))return null;let _="";return(null===(c=null===(u=this.order)||void 0===u?void 0:u.discount)||void 0===c?void 0:c.coupon)&&(_=(0,t.g)(null===(l=null===(s=this.order)||void 0===s?void 0:s.discount)||void 0===l?void 0:l.coupon)),(0,o.h)("sc-line-item",{style:{marginTop:"var(--sc-spacing-small)"}},(0,o.h)("span",{slot:"description"},(0,i._)("Discount","surecart"),(0,o.h)("br",null),(null===(v=null===(d=null===(a=this.order)||void 0===a?void 0:a.discount)||void 0===d?void 0:d.promotion)||void 0===v?void 0:v.code)&&(0,o.h)("sc-tag",{type:"success",size:"small"},null===(f=null===(m=null===(p=this.order)||void 0===p?void 0:p.discount)||void 0===m?void 0:m.promotion)||void 0===f?void 0:f.code)),_&&(0,o.h)("span",{class:"coupon-human-discount",slot:"price-description"},"(",_,")"),(0,o.h)("sc-format-number",{slot:"price",type:"currency",currency:null===(h=this.order)||void 0===h?void 0:h.currency,value:-(null===(y=this.order)||void 0===y?void 0:y.discount_amount)}))}render(){return(0,o.h)("div",{class:{"line-item-totals":!0}},(0,o.h)("sc-line-item-total",{order:this.order,total:"subtotal"},(0,o.h)("span",{slot:"description"},(0,i._)("Subtotal","surecart"))),this.renderDiscountLine(),(0,o.h)("sc-divider",{style:{"--spacing":"var(--sc-spacing-small)"}}),(0,o.h)("sc-line-item-total",{order:this.order,size:"large","show-currency":!0},(0,o.h)("span",{slot:"description"},(0,i._)("Total","surecart"))))}};(0,u.o)(c,["order","busy","loading","empty"],!1),c.style=":host{display:block}"}}]);
//# sourceMappingURL=9161.js.map