"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[6557],{6557:function(s,t,i){i.r(t),i.d(t,{sc_stacked_list:function(){return e},sc_stacked_list_row:function(){return a}});var r=i(7492);const e=class{constructor(s){(0,r.r)(this,s)}render(){return(0,r.h)("slot",null)}};e.style=":host{display:block;font-family:var(--sc-font-sans)}:slotted(*){margin:0}";const a=class{constructor(s){(0,r.r)(this,s),this.target="_self",this.mobileSize=600,this.hasPrefix=!1,this.hasSuffix=!1}componentDidLoad(){"ResizeObserver"in window&&new window.ResizeObserver((s=>{s.forEach((s=>{this.width=s.contentRect.width}))})).observe(this.el)}handleSlotChange(){this.hasPrefix=!!this.el.querySelector('[slot="prefix"]'),this.hasSuffix=!!this.el.querySelector('[slot="suffix"]')}render(){const s=this.href?"a":"div";return(0,r.h)(s,{href:this.href,target:this.target,class:{"list-row":!0,"list-row--has-prefix":this.hasPrefix,"list-row--has-suffix":this.hasSuffix,"breakpoint-lg":this.width>=this.mobileSize}},(0,r.h)("span",{class:"list-row__prefix"},(0,r.h)("slot",{name:"prefix",onSlotchange:()=>this.handleSlotChange()})),(0,r.h)("slot",{onSlotchange:()=>this.handleSlotChange()}),(0,r.h)("span",{class:"list-row__suffix"},(0,r.h)("slot",{name:"suffix",onSlotchange:()=>this.handleSlotChange()})))}get el(){return(0,r.g)(this)}};a.style=":host{display:block;--column-width-min:125px;position:relative}:host(:not(:last-child)){border-bottom:1px solid var(--sc-color-gray-200)}.list-row{color:var(--sc-color-gray-800);text-decoration:none;display:grid;justify-content:space-between;grid-template-columns:repeat(auto-fit, minmax(100%, 1fr));gap:var(--sc-spacing-xx-small);padding:var(--sc-spacing-medium) var(--sc-spacing-large);transition:background-color var(--sc-transition-fast) ease;min-width:0px;min-height:0px}.list-row[href]:hover{background:var(--sc-stacked-list-row-hover-color, var(--sc-color-gray-50))}.list-row__prefix,.list-row__suffix{position:absolute;top:50%;transform:translateY(-50%);z-index:1}.list-row__prefix{left:var(--sc-spacing-large)}.list-row__suffix{right:var(--sc-spacing-large)}.list-row--has-prefix{padding-left:var(--sc-spacing-xxxx-large)}.list-row--has-suffix{padding-right:var(--sc-spacing-xxxx-large)}.list-row.breakpoint-lg{grid-template-columns:repeat(calc(var(--columns) - 1), 1fr) 0.5fr;gap:var(--sc-spacing-large)}.list-row.breakpoint-lg ::slotted(:last-child:not(:first-child)){display:flex;justify-content:flex-end}"}}]);
//# sourceMappingURL=6557.js.map