"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[5136],{8578:function(n,o,t){t.d(o,{o:function(){return e}});var r=t(6046),e=function(n,o,t){void 0===t&&(t=!0);var e="Function"===n.constructor.name?n.prototype:n,s=e.componentWillLoad;e.componentWillLoad=function(){var n,e=this,i=(0,r.g)(this),c={promise:new Promise((function(o){n=o})),resolve:n},u=new CustomEvent("openWormhole",{bubbles:!0,composed:!0,detail:{consumer:this,fields:o,updater:function(n,o){(n in i?i:e)[n]=o},onOpen:c}});i.dispatchEvent(u);var a=function(){if(s)return s.call(e)};return t?c.promise.then((function(){return a()})):a()}}},5136:function(n,o,t){t.r(o),t.d(o,{sc_order_confirmation_customer:function(){return i}});var r=t(6046),e=t(290),s=t(8578);t(5115);const i=class{constructor(n){(0,r.r)(this,n)}render(){return this.customer?(0,r.h)("sc-customer-details",{customer:this.customer,loading:this.loading,error:this.error},(0,r.h)("span",{slot:"heading"},(0,r.h)("slot",{name:"heading"},this.heading||(0,e._)("Billing Details","surecart")))):null}};(0,s.o)(i,["order","customer","loading"],!1),i.style=":host{display:block}"}}]);
//# sourceMappingURL=5136.js.map