"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[1618],{8578:function(o,r,n){n.d(r,{o:function(){return t}});var i=n(6046),t=function(o,r,n){void 0===n&&(n=!0);var t="Function"===o.constructor.name?o.prototype:o,e=t.componentWillLoad;t.componentWillLoad=function(){var o,t=this,s=(0,i.g)(this),l={promise:new Promise((function(r){o=r})),resolve:o},u=new CustomEvent("openWormhole",{bubbles:!0,composed:!0,detail:{consumer:this,fields:r,updater:function(o,r){(o in s?s:t)[o]=r},onOpen:l}});s.dispatchEvent(u);var c=function(){if(e)return e.call(t)};return n?l.promise.then((function(){return c()})):c()}}},1618:function(o,r,n){n.r(r),n.d(r,{sc_order_coupon_form:function(){return s}});var i=n(6046),t=n(8578),e=n(290);n(5115);const s=class{constructor(o){(0,i.r)(this,o),this.scApplyCoupon=(0,i.c)(this,"scApplyCoupon",7)}handleErrorsChange(){var o;const r=((null===(o=null==this?void 0:this.error)||void 0===o?void 0:o.additional_errors)||[]).find((o=>{var r;return"discount.promotion_code"===(null===(r=null==o?void 0:o.data)||void 0===r?void 0:r.attribute)}));this.errorMessage=(null==r?void 0:r.message)?null==r?void 0:r.message:""}render(){var o,r,n;return(0,i.h)("sc-coupon-form",{label:this.label,loading:this.loading,busy:this.busy,error:this.errorMessage,discount:null===(o=null==this?void 0:this.order)||void 0===o?void 0:o.discount,currency:null===(r=null==this?void 0:this.order)||void 0===r?void 0:r.currency,"discount-amount":null===(n=null==this?void 0:this.order)||void 0===n?void 0:n.discount_amount},(0,i.h)("slot",null,(0,e._)("Apply Coupon","surecart")))}static get watchers(){return{error:["handleErrorsChange"]}}};(0,t.o)(s,["loading","busy","order","error"],!1),s.style=":host{display:block}.coupon-form{position:relative}.form{opacity:0;visibility:hidden;height:0;transition:opacity var(--sc-transition-fast) ease-in-out}.coupon-form--is-open .form{opacity:1;visibility:visible;height:auto;margin-top:var(--sc-spacing-small);display:grid;gap:var(--sc-spacing-small)}.coupon-form--is-open .trigger{color:var(--sc-input-label-color)}.coupon-form--is-open .trigger:hover{text-decoration:none}.trigger{cursor:pointer;font-size:var(--sc-font-size-small);color:var(--sc-color-gray-500);user-select:none}.trigger:hover{text-decoration:underline}"}}]);