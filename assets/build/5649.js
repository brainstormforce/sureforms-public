"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[5649],{682:function(t,e,i){i.d(e,{o:function(){return n}});var s=i(7492),n=function(t,e,i){void 0===i&&(i=!0);var n="Function"===t.constructor.name?t.prototype:t,a=n.componentWillLoad;n.componentWillLoad=function(){var t,n=this,r=(0,s.g)(this),o={promise:new Promise((function(e){t=e})),resolve:t},c=new CustomEvent("openWormhole",{bubbles:!0,composed:!0,detail:{consumer:this,fields:e,updater:function(t,e){(t in r?r:n)[t]=e},onOpen:o}});r.dispatchEvent(c);var u=function(){if(a)return a.call(n)};return i?o.promise.then((function(){return u()})):u()}}},7617:function(t,e,i){i.d(e,{c:function(){return c},e:function(){return r},f:function(){return u}});var s=i(1479),n=i(2538);const a="surecart/v1/orders/",r=["line_items","line_item.price","price.product","customer","customer.shipping_address","payment_intent","discount","discount.promotion","discount.coupon","shipping_address","tax_identifier"],o=(t,e="")=>{let i=t?`${a}${t}`:a;return i=`${i}${e}`,(0,n.a)(i,{expand:r})},c=async({id:t,data:e,query:i={}})=>await(0,s.a)({method:t?"PATCH":"POST",path:(0,n.a)(o(t),i),data:e}),u=async({id:t,data:e={},query:i={},processor:a})=>await(0,s.a)({method:"POST",path:(0,n.a)(o(t,"/finalize/"),{processor_type:a,...i}),data:e})},5649:function(t,e,i){i.r(e),i.d(e,{sc_customer_email:function(){return r}});var s=i(7492),n=i(682),a=i(7617);i(1479),i(9988),i(7094);const r=class{constructor(t){(0,s.r)(this,t),this.scChange=(0,s.c)(this,"scChange",7),this.scClear=(0,s.c)(this,"scClear",7),this.scInput=(0,s.c)(this,"scInput",7),this.scFocus=(0,s.c)(this,"scFocus",7),this.scBlur=(0,s.c)(this,"scBlur",7),this.scUpdateOrderState=(0,s.c)(this,"scUpdateOrderState",7),this.size="medium",this.value="",this.pill=!1,this.showLabel=!0,this.help="",this.disabled=!1,this.readonly=!1,this.required=!1,this.invalid=!1}async handleChange(){var t;this.value=this.input.value,this.scChange.emit();try{const e=await(0,a.c)({id:null===(t=this.order)||void 0===t?void 0:t.id,data:{email:this.input.value}});this.scUpdateOrderState.emit(e)}catch(t){console.error(t)}}handleSessionChange(t){t.email&&t.email!==this.value&&(this.value=t.email)}async reportValidity(){return this.input.reportValidity()}render(){var t;return(0,s.h)("sc-input",{type:"email",name:"email",ref:t=>this.input=t,value:(null===(t=this.customer)||void 0===t?void 0:t.email)||this.value,label:this.label,help:this.help,autocomplete:"email",placeholder:this.placeholder,disabled:!!this.loggedIn,readonly:this.readonly,required:!0,invalid:this.invalid,autofocus:this.autofocus,hasFocus:this.hasFocus,onScChange:()=>this.handleChange(),onScInput:()=>this.scInput.emit(),onScFocus:()=>this.scFocus.emit(),onScBlur:()=>this.scBlur.emit()})}static get watchers(){return{order:["handleSessionChange"]}}};(0,n.o)(r,["order","customer","loggedIn"],!1),r.style="sc-customer-email{display:block}"}}]);
//# sourceMappingURL=5649.js.map