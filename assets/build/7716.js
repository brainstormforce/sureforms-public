"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[7716],{8578:function(r,n,e){e.d(n,{o:function(){return t}});var i=e(6046),t=function(r,n,e){void 0===e&&(e=!0);var t="Function"===r.constructor.name?r.prototype:r,o=t.componentWillLoad;t.componentWillLoad=function(){var r,t=this,u=(0,i.g)(this),l={promise:new Promise((function(n){r=n})),resolve:r},a=new CustomEvent("openWormhole",{bubbles:!0,composed:!0,detail:{consumer:this,fields:n,updater:function(r,n){(r in u?u:t)[r]=n},onOpen:l}});u.dispatchEvent(a);var c=function(){if(o)return o.call(t)};return e?l.promise.then((function(){return c()})):c()}}},4447:function(r,n,e){e.d(n,{a:function(){return u},c:function(){return i},h:function(){return o},i:function(){return t}});const i=r=>((null==r?void 0:r.data)||[]).map((r=>({price_id:r.price.id,quantity:r.quantity}))),t=(r,n)=>{var e;return!!((null==(e=null==n?void 0:n.line_items)?void 0:e.data)||[]).map((r=>r.price.id)).find((n=>(null==r?void 0:r.id)===n))},o=r=>{var n,e,i;return!!(null===(e=null===(n=null==r?void 0:r.line_items)||void 0===n?void 0:n.data)||void 0===e?void 0:e.length)&&(null===(i=null==r?void 0:r.line_items.data)||void 0===i?void 0:i.some((r=>{var n;return null===(n=null==r?void 0:r.price)||void 0===n?void 0:n.recurring_interval_count})))},u=r=>{var n;return o(r)&&(null===(n=null==r?void 0:r.line_items.data)||void 0===n?void 0:n.some((r=>{var n;return null===(n=null==r?void 0:r.price)||void 0===n?void 0:n.recurring_period_count})))}},3297:function(r,n,e){e.d(n,{a:function(){return u},g:function(){return t},i:function(){return a},t:function(){return l}});var i=e(290);const t=r=>r.amount_off&&r.currency?o({amount:r.amount_off,currency:r.currency}):r.percent_off?(0|r.percent_off)+"% off":"",o=({amount:r,currency:n})=>{const e=((r,n)=>["bif","clp","djf","gnf","jpy","kmf","krw"].includes(n)?r:r/100)(r,n);return`${new Intl.NumberFormat(void 0,{style:"currency",currency:n}).format(parseFloat(e.toFixed(2)))}`},u=(r="usd")=>{const[n]=new Intl.NumberFormat(void 0,{style:"currency",currency:r}).formatToParts();return null==n?void 0:n.value},l=(r,n,e=(0,i._)("every","surecart"),t=(0,i._)("once","surecart"),o=!1)=>{switch(n){case"day":return`${e} ${(0,i.s)(o?(0,i.a)("%d day","%d days",r,"surecart"):(0,i.a)("day","%d days",r,"surecart"),r)}`;case"week":return`${e} ${(0,i.s)(o?(0,i.a)("%d week","%d weeks",r,"surecart"):(0,i.a)("week","%d weeks",r,"surecart"),r)}`;case"month":return`${e} ${(0,i.s)(o?(0,i.a)("%d month","%d months",r,"surecart"):(0,i.a)("month","%d months",r,"surecart"),r)}`;case"year":return`${e} ${(0,i.s)(o?(0,i.a)("%d year","%d years",r,"surecart"):(0,i.a)("year","%d years",r,"surecart"),r)}`;default:return t}},a=(r,n={})=>{if(!r)return"";const{showOnce:e,labels:t}=n,{interval:o=(0,i._)("every","surecart"),period:u=(0,i._)("for","surecart")}=t||{};return`${c(r,o,e?(0,i._)("once","surecart"):"")} ${s(r,u)}`},c=(r,n,e=(0,i._)("once","surecart"))=>r.recurring_interval_count&&r.recurring_interval?l(r.recurring_interval_count,r.recurring_interval,` ${n}`,e):"",s=(r,n,e="")=>(null==r?void 0:r.recurring_period_count)&&(null==r?void 0:r.recurring_interval)?l(((null==r?void 0:r.recurring_period_count)||0)*(null==r?void 0:r.recurring_interval_count),null==r?void 0:r.recurring_interval,` ${n}`,e,!0):""},7716:function(r,n,e){e.r(n),e.d(n,{sc_order_confirmation_line_items:function(){return l}});var i=e(6046),t=e(8578),o=e(4447),u=e(3297);e(290),e(5115);const l=class{constructor(r){(0,i.r)(this,r)}render(){var r,n;return this.loading?(0,i.h)("sc-line-item",null,(0,i.h)("sc-skeleton",{style:{width:"50px",height:"50px","--border-radius":"0"},slot:"image"}),(0,i.h)("sc-skeleton",{slot:"title",style:{width:"120px",display:"inline-block"}}),(0,i.h)("sc-skeleton",{slot:"description",style:{width:"60px",display:"inline-block"}}),(0,i.h)("sc-skeleton",{style:{width:"120px",display:"inline-block"},slot:"price"}),(0,i.h)("sc-skeleton",{style:{width:"60px",display:"inline-block"},slot:"price-description"})):(0,i.h)("div",{class:{"confirmation-summary":!0}},(0,i.h)("div",{class:"line-items",part:"line-items"},null===(n=null===(r=this.order)||void 0===r?void 0:r.line_items)||void 0===n?void 0:n.data.map((r=>{var n,e,t,l,a,c;return(0,i.h)("sc-product-line-item",{key:r.id,imageUrl:null===(e=null===(n=null==r?void 0:r.price)||void 0===n?void 0:n.product)||void 0===e?void 0:e.image_url,name:`${null===(l=null===(t=null==r?void 0:r.price)||void 0===t?void 0:t.product)||void 0===l?void 0:l.name}`,editable:!1,removable:!1,quantity:r.quantity,amount:null!==r.ad_hoc_amount?r.ad_hoc_amount:r.price.amount,currency:null===(a=this.order)||void 0===a?void 0:a.currency,trialDurationDays:null===(c=null==r?void 0:r.price)||void 0===c?void 0:c.trial_duration_days,interval:(0,u.i)(null==r?void 0:r.price,{showOnce:(0,o.h)(this.order)})})}))))}};(0,t.o)(l,["order","busy","loading","empty"],!1),l.style=":host{display:block}.line-items{display:grid;gap:var(--sc-spacing-small)}::part(line-items){display:grid;gap:var(--sc-spacing-small)}"}}]);
//# sourceMappingURL=7716.js.map