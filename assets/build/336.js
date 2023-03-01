"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[336],{7231:function(e,t,s){s.d(t,{o:function(){return n}});const n=(e,t)=>{new window.IntersectionObserver(((e,s)=>{e[0].intersectionRatio>0&&(t(),s.unobserve(e[0].target))})).observe(e)}},336:function(e,t,s){s.r(t),s.d(t,{sc_payment_methods_list:function(){return l}});var n=s(7492),a=s(9988),i=s(1479),r=s(7231),o=s(2538);s(7094);const l=class{constructor(e){(0,n.r)(this,e),this.paymentMethods=[]}componentWillLoad(){(0,r.o)(this.el,(()=>{this.getPaymentMethods()})),this.handleSlotChange()}handleSlotChange(){this.hasTitleSlot=!!this.el.querySelector('[slot="title"]')}async deleteMethod(e){if(confirm((0,a._)("Are you sure you want to remove this payment method?","surecart")))try{this.busy=!0,await(0,i.a)({path:`surecart/v1/payment_methods/${null==e?void 0:e.id}/detach`,method:"PATCH"}),this.paymentMethods=this.paymentMethods.filter((t=>t.id!==e.id))}catch(e){alert((null==e?void 0:e.messsage)||(0,a._)("Something went wrong","surecart"))}finally{this.busy=!1}}async setDefault(e){var t;try{this.busy=!0,await(0,i.a)({path:`surecart/v1/customers/${null===(t=null==e?void 0:e.customer)||void 0===t?void 0:t.id}`,method:"PATCH",data:{default_payment_method:e.id}}),this.paymentMethods=await await(0,i.a)({path:(0,o.a)("surecart/v1/payment_methods/",{expand:["card","customer"],...this.query})})}catch(e){alert((null==e?void 0:e.messsage)||(0,a._)("Something went wrong","surecart"))}finally{this.busy=!1}}async getPaymentMethods(){try{this.loading=!0,this.paymentMethods=await await(0,i.a)({path:(0,o.a)("surecart/v1/payment_methods/",{expand:["card","customer"],...this.query})})}catch(e){(null==e?void 0:e.message)?this.error=e.message:this.error=(0,a._)("Something went wrong","surecart"),console.error(this.error)}finally{this.loading=!1}}renderLoading(){return(0,n.h)("sc-card",{noPadding:!0},(0,n.h)("sc-stacked-list",null,(0,n.h)("sc-stacked-list-row",{style:{"--columns":"4"},"mobile-size":500},[...Array(4)].map((()=>(0,n.h)("sc-skeleton",{style:{width:"100px",display:"inline-block"}}))))))}renderEmpty(){return(0,n.h)("div",null,(0,n.h)("sc-divider",{style:{"--spacing":"0"}}),(0,n.h)("slot",{name:"empty"},(0,n.h)("sc-empty",{icon:"credit-card"},(0,a._)("You don't have any saved payment methods.","surecart"))))}renderList(){return this.paymentMethods.map((e=>{const{id:t,card:s,customer:i,live_mode:r}=e;return(0,n.h)("sc-stacked-list-row",{style:{"--columns":"4"},"mobile-size":0},(0,n.h)("sc-flex",{"justify-content":"flex-start","align-items":"center",style:{"--spacing":"0.5em"}},(0,n.h)("sc-cc-logo",{style:{fontSize:"36px"},brand:null==s?void 0:s.brand}),(0,n.h)("span",{style:{fontSize:"7px",whiteSpace:"nowrap"}},"⬤"," ","⬤"," ","⬤"," ","⬤"),(0,n.h)("span",null,null==s?void 0:s.last4)),(0,n.h)("div",null,(0,a._)("Exp.","surecart")," ",null==s?void 0:s.exp_month,"/",null==s?void 0:s.exp_year),(0,n.h)("div",null,"string"!=typeof i&&(null==i?void 0:i.default_payment_method)===t&&(0,n.h)("sc-tag",{type:"info"},(0,a._)("Default","surecart")),!r&&(0,n.h)("sc-tag",{type:"warning"},(0,a._)("Test","surecart"))),(0,n.h)("div",null,(0,n.h)("sc-dropdown",{position:"bottom-right"},(0,n.h)("sc-icon",{name:"more-horizontal",slot:"trigger"}),(0,n.h)("sc-menu",null,"string"!=typeof i&&(null==i?void 0:i.default_payment_method)!==t&&(0,n.h)("sc-menu-item",{onClick:()=>this.setDefault(e)},(0,a._)("Make Default","surecart")),(0,n.h)("sc-menu-item",{onClick:()=>this.deleteMethod(e)},(0,a._)("Delete","surecart"))))))}))}renderContent(){var e;return this.loading?this.renderLoading():0===(null===(e=this.paymentMethods)||void 0===e?void 0:e.length)?this.renderEmpty():(0,n.h)("sc-card",{"no-padding":!0},(0,n.h)("sc-stacked-list",null,this.renderList()))}render(){return(0,n.h)("sc-dashboard-module",{class:"payment-methods-list",error:this.error},(0,n.h)("span",{slot:"heading"},(0,n.h)("slot",{name:"heading"},this.heading||(0,a._)("Payment Methods","surecart"))),(0,n.h)("sc-flex",{slot:"end"},(0,n.h)("sc-button",{type:"link",href:(0,o.a)(window.location.href,{action:"index",model:"charge"})},(0,n.h)("sc-icon",{name:"clock",slot:"prefix"}),(0,a._)("Payment History","surecart")),(0,n.h)("sc-button",{type:"link",href:(0,o.a)(window.location.href,{action:"create",model:"payment_method"})},(0,n.h)("sc-icon",{name:"plus",slot:"prefix"}),(0,a._)("Add","surecart"))),this.renderContent(),this.busy&&(0,n.h)("sc-block-ui",{spinner:!0}))}get el(){return(0,n.g)(this)}};l.style=":host{display:block;position:relative}.payment-methods-list{display:grid;gap:0.5em}.payment-methods-list sc-heading a{text-decoration:none;font-weight:var(--sc-font-weight-semibold);display:inline-flex;align-items:center;gap:0.25em;color:var(--sc-color-primary-500)}"}}]);
//# sourceMappingURL=336.js.map