"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[9673],{9673:function(s,r,t){t.r(r),t.d(r,{sc_wordpress_password_edit:function(){return i}});var e=t(6046),a=t(290),o=t(712);t(5115);const i=class{constructor(s){(0,e.r)(this,s)}renderEmpty(){return(0,e.h)("slot",{name:"empty"},(0,a._)("User not found.","surecart"))}async handleSubmit(s){this.loading=!0,this.error="";try{const{password:r,password_confirm:t}=await s.target.getFormJson();if(r!==t)throw{message:(0,a._)("Passwords do not match.","surecart")};await(0,o.a)({path:"wp/v2/users/me",method:"PATCH",data:{password:r}}),this.successUrl?window.location.assign(this.successUrl):this.loading=!1}catch(s){this.error=(null==s?void 0:s.message)||(0,a._)("Something went wrong","surecart"),this.loading=!1}}render(){return(0,e.h)("sc-dashboard-module",{class:"customer-details",error:this.error},(0,e.h)("span",{slot:"heading"},this.heading||(0,a._)("Update Password","surecart")," "),(0,e.h)("sc-card",null,(0,e.h)("sc-form",{onScFormSubmit:s=>this.handleSubmit(s)},(0,e.h)("sc-input",{label:(0,a._)("New Password","surecart"),name:"password",type:"password",required:!0}),(0,e.h)("sc-input",{label:(0,a._)("Confirm New Password","surecart"),name:"password_confirm",type:"password",required:!0}),(0,e.h)("div",null,(0,e.h)("sc-button",{type:"primary",full:!0,submit:!0},(0,a._)("Update Password","surecart"))))),this.loading&&(0,e.h)("sc-block-ui",{spinner:!0}))}};i.style=":host{display:block;position:relative}"}}]);