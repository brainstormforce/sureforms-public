"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[2607],{3563:function(e,o,t){function i(e,o,t){return new Promise((i=>{if((null==t?void 0:t.duration)===1/0)throw new Error("Promise-based animations must be finite.");const a=e.animate(o,{...t,duration:window.matchMedia("(prefers-reduced-motion: reduce)").matches?0:t.duration});a.addEventListener("cancel",i,{once:!0}),a.addEventListener("finish",i,{once:!0})}))}function a(e){var o;return Promise.all(((null===(o=null==e?void 0:e.getAnimations)||void 0===o?void 0:o.call(e))||[]).map((e=>new Promise((o=>{const t=requestAnimationFrame(o);e.addEventListener("cancel",(()=>t),{once:!0}),e.addEventListener("finish",(()=>t),{once:!0}),e.cancel()})))))}function s(e,o){return e.map((e=>({...e,height:"auto"===e.height?`${o}px`:e.height})))}t.d(o,{a:function(){return i},b:function(){return l},c:function(){return s},g:function(){return c},s:function(){return a}});const n=new Map,r=new WeakMap;function l(e,o){n.set(e,function(e){return null!=e?e:{keyframes:[],options:{duration:0}}}(o))}function c(e,o){const t=r.get(e);if(null==t?void 0:t[o])return t[o];return n.get(o)||{keyframes:[],options:{duration:0}}}},2607:function(e,o,t){t.r(o),t.d(o,{sc_dialog:function(){return c}});var i=t(7492),a=t(9988),s=t(3563);t(7094);const n=new Set;function r(e){n.add(e),document.body.classList.add("sc-scroll-lock")}function l(e){n.delete(e),0===n.size&&document.body.classList.remove("sc-scroll-lock")}const c=class{constructor(e){(0,i.r)(this,e),this.scRequestClose=(0,i.c)(this,"scRequestClose",7),this.scShow=(0,i.c)(this,"scShow",7),this.scAfterShow=(0,i.c)(this,"scAfterShow",7),this.scHide=(0,i.c)(this,"scHide",7),this.scAfterHide=(0,i.c)(this,"scAfterHide",7),this.scInitialFocus=(0,i.c)(this,"scInitialFocus",7),this.open=!1,this.label="",this.noHeader=!1,this.hasFooter=!1}async show(){this.open||(this.open=!0)}async hide(){this.open&&(this.open=!1)}requestClose(e){if(this.scRequestClose.emit(e).defaultPrevented){const e=(0,s.g)(this.el,"dialog.denyClose");(0,s.a)(this.panel,e.keyframes,e.options)}else this.hide()}handleKeyDown(e){"Escape"===e.key&&(e.stopPropagation(),this.requestClose("keyboard"))}async handleOpenChange(){if(this.open){this.scShow.emit(),r(this.el);const e=this.el.querySelector("[autofocus]");e&&e.removeAttribute("autofocus"),await Promise.all([(0,s.s)(this.dialog),(0,s.s)(this.overlay)]),this.dialog.hidden=!1,requestAnimationFrame((()=>{this.scInitialFocus.emit().defaultPrevented||(e?e.focus({preventScroll:!0}):this.panel.focus({preventScroll:!0})),e&&e.setAttribute("autofocus","")}));const o=(0,s.g)(this.el,"dialog.show"),t=(0,s.g)(this.el,"dialog.overlay.show");await Promise.all([(0,s.a)(this.panel,o.keyframes,o.options),(0,s.a)(this.overlay,t.keyframes,t.options)]),this.scAfterShow.emit()}else{this.scHide.emit(),await Promise.all([(0,s.s)(this.dialog),(0,s.s)(this.overlay)]);const e=(0,s.g)(this.el,"dialog.hide"),o=(0,s.g)(this.el,"dialog.overlay.hide");await Promise.all([(0,s.a)(this.panel,e.keyframes,e.options),(0,s.a)(this.overlay,o.keyframes,o.options)]),this.dialog.hidden=!0,l(this.el);const t=this.originalTrigger;"function"==typeof(null==t?void 0:t.focus)&&setTimeout((()=>t.focus())),this.scAfterHide.emit()}}componentDidLoad(){this.hasFooter=!!this.el.querySelector('[slot="footer"]'),this.dialog.hidden=!this.open,this.open&&r(this.el)}disconnectedCallback(){l(this.el)}render(){return(0,i.h)("div",{part:"base",ref:e=>this.dialog=e,class:{dialog:!0,"dialog--open":this.open,"dialog--has-footer":this.hasFooter},onKeyDown:e=>this.handleKeyDown(e)},(0,i.h)("div",{part:"overlay",class:"dialog__overlay",onClick:()=>this.requestClose("overlay"),ref:e=>this.overlay=e,tabindex:"-1"}),(0,i.h)("div",{part:"panel",class:"dialog__panel",role:"dialog","aria-modal":"true","aria-hidden":this.open?"false":"true","aria-label":this.noHeader||this.label,"aria-labelledby":!this.noHeader||"title",ref:e=>this.panel=e,tabindex:"0"},!this.noHeader&&(0,i.h)("header",{part:"header",class:"dialog__header"},(0,i.h)("h2",{part:"title",class:"dialog__title",id:"title"},(0,i.h)("slot",{name:"label"}," ",this.label.length>0?this.label:String.fromCharCode(65279)," ")),(0,i.h)("sc-button",{class:"dialog__close",type:"text",circle:!0,part:"close-button",exportparts:"base:close-button__base",onClick:()=>this.requestClose("close-button")},(0,i.h)("sc-icon",{name:"x",label:(0,a._)("Close","surecart")}))),(0,i.h)("div",{part:"body",class:"dialog__body"},(0,i.h)("slot",null)),(0,i.h)("footer",{part:"footer",class:"dialog__footer"},(0,i.h)("slot",{name:"footer"}))))}get el(){return(0,i.g)(this)}static get watchers(){return{open:["handleOpenChange"]}}};(0,s.b)("dialog.show",{keyframes:[{opacity:0,transform:"scale(0.8)"},{opacity:1,transform:"scale(1)"}],options:{duration:150,easing:"ease"}}),(0,s.b)("dialog.hide",{keyframes:[{opacity:1,transform:"scale(1)"},{opacity:0,transform:"scale(0.8)"}],options:{duration:150,easing:"ease"}}),(0,s.b)("dialog.denyClose",{keyframes:[{transform:"scale(1)"},{transform:"scale(1.02)"},{transform:"scale(1)"}],options:{duration:150}}),(0,s.b)("dialog.overlay.show",{keyframes:[{opacity:0},{opacity:1}],options:{duration:150}}),(0,s.b)("dialog.overlay.hide",{keyframes:[{opacity:1},{opacity:0}],options:{duration:150}}),c.style=":host{--width:31rem;--header-spacing:var(--sc-spacing-large);--body-spacing:var(--sc-spacing-large);--footer-spacing:var(--sc-spacing-large);display:contents}[hidden]{display:none !important}.dialog{display:flex;align-items:center;justify-content:center;position:fixed;top:0;right:0;bottom:0;left:0;z-index:var(--sc-z-index-dialog);box-sizing:border-box}.dialog__panel{display:flex;flex-direction:column;z-index:2;width:var(--width);max-width:calc(100% - var(--sc-spacing-2x-large));max-height:calc(100% - var(--sc-spacing-2x-large));background-color:var(--sc-panel-background-color);border-radius:var(--sc-border-radius-medium);box-shadow:var(--sc-shadow-x-large);position:relative}.dialog__panel:focus{outline:none}@media screen and (max-width: 420px){.dialog__panel{max-height:80vh}}.dialog--open .dialog__panel{display:flex;opacity:1;transform:none}.dialog__header{flex:0 0 auto;display:flex;border-bottom:1px solid var(--sc-color-gray-300)}.dialog__title{flex:1 1 auto;font:inherit;font-size:var(--sc-font-size-large);line-height:var(--sc-line-height-dense);padding:var(--header-spacing);margin:0}.dialog__close{flex:0 0 auto;display:flex;align-items:center;font-size:var(--sc-font-size-x-large);padding:0 calc(var(--header-spacing) / 2);z-index:2}.dialog__body{flex:1 1 auto;padding:var(--body-spacing);overflow:auto;-webkit-overflow-scrolling:touch}.dialog__footer{flex:0 0 auto;text-align:right;padding:var(--footer-spacing)}.dialog__footer ::slotted(sl-button:not(:first-of-type)){margin-left:var(--sc-spacing-x-small)}.dialog:not(.dialog--has-footer) .dialog__footer{display:none}.dialog__overlay{position:fixed;top:0;right:0;bottom:0;left:0;background-color:var(--sc-overlay-background-color)}"}}]);
//# sourceMappingURL=2607.js.map