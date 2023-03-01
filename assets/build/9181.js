"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[9181],{9181:function(s,t,i){i.r(t),i.d(t,{sc_subscription_renew:function(){return c}});var e=i(7492),r=i(9988),n=i(1479),o=i(2538);i(7094);const c=class{constructor(s){(0,e.r)(this,s)}componentDidLoad(){this.fetchItems()}async fetchItems(){try{this.loading=!0,await this.fetchSubscription()}catch(s){console.error(this.error),this.error=(null==s?void 0:s.message)||(0,r._)("Something went wrong","surecart")}finally{this.loading=!1}}async fetchSubscription(){this.subscriptionId&&(this.subscription=await(0,n.a)({path:(0,o.a)(`/surecart/v1/subscriptions/${this.subscriptionId}`,{expand:["price","price.product","latest_invoice","product"]})}))}async renewSubscription(){try{this.error="",this.busy=!0,await(0,n.a)({path:`/surecart/v1/subscriptions/${this.subscriptionId}/renew/`,method:"PATCH"}),this.successUrl?window.location.assign(this.successUrl):this.busy=!1}catch(s){this.error=(null==s?void 0:s.message)||(0,r._)("Something went wrong","surecart"),this.busy=!1}}renderContent(){var s;return this.loading?this.renderLoading():(0,e.h)(e.F,null,(0,e.h)("sc-subscription-details",{subscription:null==this?void 0:this.subscription,hideRenewalText:!0}),(0,e.h)("sc-alert",{type:"info",open:!0},(0,r._)("This plan will no longer be canceled. It will renew on","surecart")," ",(0,e.h)("sc-format-date",{type:"timestamp",date:null===(s=null==this?void 0:this.subscription)||void 0===s?void 0:s.current_period_end_at,month:"long",day:"numeric",year:"numeric"}),"."," "))}renderLoading(){return(0,e.h)("div",{style:{padding:"0.5em"}},(0,e.h)("sc-skeleton",{style:{width:"30%",marginBottom:"0.75em"}}),(0,e.h)("sc-skeleton",{style:{width:"20%",marginBottom:"0.75em"}}),(0,e.h)("sc-skeleton",{style:{width:"40%"}}))}render(){return(0,e.h)("sc-dashboard-module",{heading:this.heading||(0,r._)("Renew your plan","surecart"),class:"subscription-cancel",error:this.error},(0,e.h)("sc-card",null,this.renderContent(),(0,e.h)("sc-button",{type:"primary",full:!0,loading:this.loading||this.busy,disabled:this.loading||this.busy,onClick:()=>this.renewSubscription()},(0,r._)("Renew Plan","surecart")),!!this.backUrl&&(0,e.h)("sc-button",{href:this.backUrl,full:!0,loading:this.loading||this.busy,disabled:this.loading||this.busy},(0,r._)("Go Back","surecart"))),this.busy&&(0,e.h)("sc-block-ui",null))}};c.style=":host{display:block;position:relative}.subscription-renew{display:grid;gap:0.5em}"}}]);
//# sourceMappingURL=9181.js.map