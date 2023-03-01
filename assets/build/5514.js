"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[5514],{551:function(e,t,r){r.d(t,{p:function(){return i}});var n=(0,r(5115).c)((function(e,t){function r(e){return r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},r(e)}Object.defineProperty(t,"__esModule",{value:!0});var n,i="https://js.stripe.com/v3",o=/^https:\/\/js\.stripe\.com\/v3\/?(\?.*)?$/,s="loadStripe.setLoadParameters was called but an existing Stripe.js script already exists in the document; existing script parameters will be used",a=null,l=function(e){return null!==a||(a=new Promise((function(t,r){if("undefined"!=typeof window)if(window.Stripe&&e&&console.warn(s),window.Stripe)t(window.Stripe);else try{var n=function(){for(var e=document.querySelectorAll('script[src^="'.concat(i,'"]')),t=0;t<e.length;t++){var r=e[t];if(o.test(r.src))return r}return null}();n&&e?console.warn(s):n||(n=function(e){var t=e&&!e.advancedFraudSignals?"?advancedFraudSignals=false":"",r=document.createElement("script");r.src="".concat(i).concat(t);var n=document.head||document.body;if(!n)throw new Error("Expected document.body not to be null. Stripe.js requires a <body> element.");return n.appendChild(r),r}(e)),n.addEventListener("load",(function(){window.Stripe?t(window.Stripe):r(new Error("Stripe.js not available"))})),n.addEventListener("error",(function(){r(new Error("Failed to load Stripe.js"))}))}catch(e){return void r(e)}else t(null)}))),a},c=function(e,t,r){if(null===e)return null;var n=e.apply(void 0,t);return function(e,t){e&&e._registerWrapper&&e._registerWrapper({name:"stripe-js",version:"1.28.0",startTime:t})}(n,r),n},d=!1,u=function(){for(var e=arguments.length,t=new Array(e),r=0;r<e;r++)t[r]=arguments[r];d=!0;var i=Date.now();return l(n).then((function(e){return c(e,t,i)}))};u.setLoadParameters=function(e){if(d)throw new Error("You cannot change load parameters after calling loadStripe");n=function(e){var t="invalid load parameters; expected object of shape\n\n    {advancedFraudSignals: boolean}\n\nbut received\n\n    ".concat(JSON.stringify(e),"\n");if(null===e||"object"!==r(e))throw new Error(t);if(1===Object.keys(e).length&&"boolean"==typeof e.advancedFraudSignals)return e;throw new Error(t)}(e)},t.loadStripe=u})),i=n},5514:function(e,t,r){r.r(t),r.d(t,{sc_stripe_payment_element:function(){return o}});var n=r(6046),i=r(551);r(5115);const o=class{constructor(e){(0,n.r)(this,e),this.scPaid=(0,n.c)(this,"scPaid",7),this.scPayError=(0,n.c)(this,"scPayError",7),this.loaded=!1,this.confirming=!1}async componentDidLoad(){this.publishableKey&&this.accountId&&(this.stripe=await i.p.loadStripe(this.publishableKey,{stripeAccount:this.accountId}),this.loadElement())}handleClientSecretChange(e,t){e!==t&&(this.loaded=!1,this.loadElement())}async maybeConfirmOrder(e){var t,r,n,i,o,s,a;if("finalized"===(null==e?void 0:e.status)&&"stripe"===(null===(t=null==e?void 0:e.payment_intent)||void 0===t?void 0:t.processor_type)&&(null===(i=null===(n=null===(r=null==e?void 0:e.payment_intent)||void 0===r?void 0:r.processor_data)||void 0===n?void 0:n.stripe)||void 0===i?void 0:i.type))return await this.confirm(null===(a=null===(s=null===(o=null==e?void 0:e.payment_intent)||void 0===o?void 0:o.processor_data)||void 0===s?void 0:s.stripe)||void 0===a?void 0:a.type)}async confirm(e,t={}){const r={elements:this.elements,confirmParams:{return_url:this.successUrl},redirect:"if_required",...t};if(!this.confirming)try{const t="setup"===e?await this.stripe.confirmSetup(r):await this.stripe.confirmPayment(r);if(null==t?void 0:t.error)throw this.error=t.error.message,t.error;this.scPaid.emit()}catch(e){console.error(e),this.scPayError.emit(e),e.message&&(this.error=e.message)}finally{this.confirming=!1}}loadElement(){if(!this.stripe||!this.clientSecret||!this.container)return;const e=getComputedStyle(document.body);this.elements=this.stripe.elements({clientSecret:this.clientSecret,appearance:{variables:{colorPrimary:e.getPropertyValue("--sc-color-primary-500"),colorText:e.getPropertyValue("--sc-input-label-color"),borderRadius:e.getPropertyValue("--sc-input-border-radius-medium"),colorBackground:e.getPropertyValue("--sc-input-background-color"),fontSizeBase:e.getPropertyValue("--sc-input-font-size-medium")},rules:{".Input":{border:e.getPropertyValue("--sc-input-border")},".Input::placeholder":{color:e.getPropertyValue("--sc-input-placeholder-color")}}}}),this.elements.create("payment",{wallets:{applePay:"never",googlePay:"never"}}).mount(".sc-payment-element-container"),this.element=this.elements.getElement("payment"),this.element.on("ready",(()=>this.loaded=!0))}render(){return(0,n.h)("div",{class:"sc-stripe-payment-element","data-testid":"stripe-payment-element"},!!this.error&&(0,n.h)("sc-text",{style:{color:"var(--sc-color-danger-500)","--font-size":"var(--sc-font-size-small)",marginBottom:"0.5em"}},this.error),(0,n.h)("div",{class:"loader",hidden:this.loaded},(0,n.h)("div",{class:"loader__row"},(0,n.h)("div",{style:{width:"50%"}},(0,n.h)("sc-skeleton",{style:{width:"50%",marginBottom:"0.5em"}}),(0,n.h)("sc-skeleton",null)),(0,n.h)("div",{style:{flex:"1"}},(0,n.h)("sc-skeleton",{style:{width:"50%",marginBottom:"0.5em"}}),(0,n.h)("sc-skeleton",null)),(0,n.h)("div",{style:{flex:"1"}},(0,n.h)("sc-skeleton",{style:{width:"50%",marginBottom:"0.5em"}}),(0,n.h)("sc-skeleton",null))),(0,n.h)("div",{class:"loader__details"},(0,n.h)("sc-skeleton",{style:{height:"1rem"}}),(0,n.h)("sc-skeleton",{style:{height:"1rem",width:"30%"}}))),(0,n.h)("div",{hidden:!this.loaded,class:"sc-payment-element-container",ref:e=>this.container=e}))}get el(){return(0,n.g)(this)}static get watchers(){return{clientSecret:["handleClientSecretChange"],order:["maybeConfirmOrder"]}}};o.style="sc-stripe-payment-element{display:block}.loader{display:grid;height:128px;gap:2em}.loader__row{display:flex;align-items:flex-start;justify-content:space-between;gap:1em}.loader__details{display:grid;gap:0.5em}"}}]);
//# sourceMappingURL=5514.js.map