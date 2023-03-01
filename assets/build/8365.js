"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[8365],{7231:function(e,t,n){n.d(t,{o:function(){return r}});const r=(e,t)=>{new window.IntersectionObserver(((e,n)=>{e[0].intersectionRatio>0&&(t(),n.unobserve(e[0].target))})).observe(e)}},8365:function(e,t,n){n.r(t),n.d(t,{sc_subscription:function(){return c}});var r=n(7492),i=n(9988),s=n(1479),o=n(7231),a=n(2538);n(7094);const c=class{constructor(e){(0,r.r)(this,e)}componentWillLoad(){(0,o.o)(this.el,(()=>{this.getSubscription()}))}async cancelPendingUpdate(){var e;if(confirm((0,i._)("Are you sure you want to cancel the pending update to your plan?","surecart")))try{this.busy=!0,this.subscription=await(0,s.a)({path:(0,a.a)(`surecart/v1/subscriptions/${null===(e=this.subscription)||void 0===e?void 0:e.id}/`,{expand:["price","price.product","latest_invoice","product"]}),method:"PATCH",data:{purge_pending_update:!0}})}catch(e){(null==e?void 0:e.message)?this.error=e.message:this.error=(0,i._)("Something went wrong","surecart"),console.error(this.error)}finally{this.busy=!1}}async getSubscription(){if(!this.subscription)try{this.loading=!0,this.subscription=await await(0,s.a)({path:(0,a.a)(`surecart/v1/subscriptions/${this.subscriptionId}`,{expand:["price","price.product","latest_invoice"],...this.query||{}})})}catch(e){(null==e?void 0:e.message)?this.error=e.message:this.error=(0,i._)("Something went wrong","surecart"),console.error(this.error)}finally{this.loading=!1}}renderName(e){var t,n,r;return"string"!=typeof(null===(t=null==e?void 0:e.price)||void 0===t?void 0:t.product)?null===(r=null===(n=null==e?void 0:e.price)||void 0===n?void 0:n.product)||void 0===r?void 0:r.name:(0,i._)("Subscription","surecart")}renderRenewalText(e){const t=(0,r.h)("sc-subscription-status-badge",{subscription:e});return(null==e?void 0:e.cancel_at_period_end)&&e.current_period_end_at?(0,r.h)("span",null,t," ",(0,i.s)((0,i._)("Your plan will be canceled on","surecart"))," ",(0,r.h)("sc-format-date",{date:1e3*e.current_period_end_at,month:"long",day:"numeric",year:"numeric"})):"trialing"===e.status&&e.trial_end_at?(0,r.h)("span",null,t," ",(0,i.s)((0,i._)("Your plan begins on","surecart"))," ",(0,r.h)("sc-format-date",{date:1e3*e.trial_end_at,month:"long",day:"numeric",year:"numeric"})):"active"===e.status&&e.current_period_end_at?(0,r.h)("span",null,t," ",(0,i.s)((0,i._)("Your plan renews on","surecart"))," ",(0,r.h)("sc-format-date",{date:1e3*e.current_period_end_at,month:"long",day:"numeric",year:"numeric"})):t}renderEmpty(){return(0,r.h)("slot",{name:"empty"},(0,i._)("This subscription does not exist.","surecart"))}renderLoading(){return(0,r.h)("sc-stacked-list-row",{style:{"--columns":"2"},"mobile-size":0},(0,r.h)("div",{style:{padding:"0.5em"}},(0,r.h)("sc-skeleton",{style:{width:"30%",marginBottom:"0.75em"}}),(0,r.h)("sc-skeleton",{style:{width:"20%",marginBottom:"0.75em"}}),(0,r.h)("sc-skeleton",{style:{width:"40%"}})))}renderContent(){return this.loading?this.renderLoading():this.subscription?(0,r.h)("sc-stacked-list-row",{"mobile-size":0},(0,r.h)("sc-subscription-details",{subscription:this.subscription})):this.renderEmpty()}render(){var e,t,n,s;return(0,r.h)("sc-dashboard-module",{heading:this.heading||(0,i._)("Current Plan","surecart"),class:"subscription",error:this.error},!!this.subscription&&(0,r.h)("sc-flex",{slot:"end"},!!Object.keys(null===(e=this.subscription)||void 0===e?void 0:e.pending_update).length&&(0,r.h)("sc-button",{type:"link",onClick:()=>this.cancelPendingUpdate()},(0,r.h)("sc-icon",{name:"x-octagon",slot:"prefix"}),(0,i._)("Cancel Scheduled Update","surecart")),(null===(t=null==this?void 0:this.subscription)||void 0===t?void 0:t.cancel_at_period_end)?(0,r.h)("sc-button",{type:"link",href:(0,a.a)(window.location.href,{action:"renew"})},(0,r.h)("sc-icon",{name:"repeat",slot:"prefix"}),(0,i._)("Renew Plan","surecart")):"canceled"!==(null===(n=this.subscription)||void 0===n?void 0:n.status)&&(null===(s=this.subscription)||void 0===s?void 0:s.current_period_end_at)&&this.showCancel&&(0,r.h)("sc-button",{type:"link",href:(0,a.a)(window.location.href,{action:"cancel"})},(0,r.h)("sc-icon",{name:"x",slot:"prefix"}),(0,i._)("Cancel Plan","surecart"))),(0,r.h)("sc-card",{"no-padding":!0,style:{"--overflow":"hidden"}},(0,r.h)("sc-stacked-list",null,this.renderContent())),this.busy&&(0,r.h)("sc-block-ui",{spinner:!0}))}get el(){return(0,r.g)(this)}};c.style=":host{display:block}.subscription{display:grid;gap:0.5em}.subscription a{text-decoration:none;font-weight:var(--sc-font-weight-semibold);display:inline-flex;align-items:center;gap:0.25em;color:var(--sc-color-primary-500)}.subscription a.cancel{color:var(--sc-color-danger-500)}"}}]);
//# sourceMappingURL=8365.js.map