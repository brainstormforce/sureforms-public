"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[1073],{682:function(o,t,n){n.d(t,{o:function(){return e}});var r=n(7492),e=function(o,t,n){void 0===n&&(n=!0);var e="Function"===o.constructor.name?o.prototype:o,u=e.componentWillLoad;e.componentWillLoad=function(){var o,e=this,i=(0,r.g)(this),s={promise:new Promise((function(t){o=t})),resolve:o},c=new CustomEvent("openWormhole",{bubbles:!0,composed:!0,detail:{consumer:this,fields:t,updater:function(o,t){(o in i?i:e)[o]=t},onOpen:s}});i.dispatchEvent(c);var l=function(){if(u)return u.call(e)};return n?s.promise.then((function(){return l()})):l()}}},1073:function(o,t,n){n.r(t),n.d(t,{sc_total:function(){return u}});var r=n(7492),e=n(682);const u=class{constructor(o){(0,r.r)(this,o),this.total="amount_due",this.order_key={total:"total_amount",subtotal:"subtotal_amount",amount_due:"amount_due"}}render(){var o,t,n,e,u;if((null===(o=this.order)||void 0===o?void 0:o.currency)&&(null===(e=null===(n=null===(t=this.order)||void 0===t?void 0:t.line_items)||void 0===n?void 0:n.data)||void 0===e?void 0:e.length))return(0,r.h)("sc-format-number",{type:"currency",currency:this.order.currency,value:null===(u=this.order)||void 0===u?void 0:u[this.order_key[this.total]]})}};(0,e.o)(u,["order"],!1),u.style=":host{display:block}"}}]);
//# sourceMappingURL=1073.js.map