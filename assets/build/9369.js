"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[9369],{9369:function(e,i,s){s.r(i),s.d(i,{sc_choices:function(){return r}});var t=s(7492);let h=0;const r=class{constructor(e){(0,t.r)(this,e),this.inputId="choices-"+ ++h,this.helpId=`choices-help-text-${h}`,this.labelId=`choices-label-${h}`,this.label="",this.size="medium",this.required=!1,this.showLabel=!0,this.help="",this.hideLabel=!1,this.columns=1,this.errorMessage=""}componentDidLoad(){this.handleRequiredChange(),this.handleResize()}handleRequiredChange(){const e=this.el.querySelectorAll("sc-choice");e.length&&e.forEach((e=>{e.required=this.required}))}handleResize(){"ResizeObserver"in window&&new window.ResizeObserver((e=>{e.forEach((e=>{this.width=e.contentRect.width}))})).observe(this.el)}render(){return(0,t.h)("fieldset",{part:"base",class:{choices:!0,"choices--hide-label":this.hideLabel,"choices--auto-width":this.autoWidth,"breakpoint-sm":this.width<384,"breakpoint-md":this.width>=384&&this.width<576,"breakpoint-lg":this.width>=576&&this.width<768,"breakpoint-xl":this.width>=768},role:"radiogroup"},(0,t.h)("sc-form-control",{size:this.size,required:this.required,label:this.label,showLabel:this.showLabel,help:this.help,inputId:this.inputId,helpId:this.helpId,labelId:this.labelId},(0,t.h)("div",{part:"choices",class:"choices__items"},(0,t.h)("slot",null))))}get el(){return(0,t.g)(this)}static get watchers(){return{required:["handleRequiredChange"]}}};r.style=":host{display:block}.choices ::slotted(div){margin:0;display:flex;flex-wrap:wrap;gap:10px}.choices:not(.choices--auto-width) ::slotted(div){margin:0;display:grid;gap:10px}.breakpoint-lg ::slotted(div),.breakpoint-xl ::slotted(div){grid-template-columns:repeat(var(--columns, 1), 1fr)}.choices .choices__items{margin:0;display:flex;flex-wrap:wrap;gap:10px}.choices:not(.choices--auto-width) .choices__items{display:grid;gap:10px}.choices{border:none;padding:0;margin:0;min-width:0;margin:0}.choices .choices__label{font-family:var(--sc-input-font-family);font-size:var(--sc-input-font-size-medium);font-weight:var(--sc-input-font-weight);color:var(--sc-input-color);padding:0 var(--sc-spacing-xx-small)}.choices__items{display:grid;gap:10px}"}}]);
//# sourceMappingURL=9369.js.map