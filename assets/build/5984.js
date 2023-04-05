"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[5984],{5358:function(e,t,o){o.d(t,{F:function(){return i},p:function(){return c},r:function(){return r}});class i{constructor(e,t){this.form=null,this.input=e,this.options={form:e=>{var t,o;return(null===(o=null===(t=this.closestElement("sc-form",e))||void 0===t?void 0:t.shadowRoot)||void 0===o?void 0:o.querySelector("form"))||this.closestElement("form",e)},name:e=>e.name,value:e=>e.value,disabled:e=>e.disabled,...t},this.form=this.options.form(this.input),this.handleFormData=this.handleFormData.bind(this)}closestElement(e,t){return t?t&&t!=document&&t!=window&&t.closest(e)||this.closestElement(e,t.getRootNode().host):null}addFormData(){this.form&&this.form.addEventListener("formdata",this.handleFormData)}removeFormData(){this.form&&this.form.removeEventListener("formdata",this.handleFormData)}handleFormData(e){const t=this.options.disabled(this.input),o=this.options.name(this.input),i=this.options.value(this.input);t||"string"!=typeof o||void 0===i||(Array.isArray(i)?i.forEach((t=>{t&&e.formData.append(o,t.toString())})):i&&e.formData.append(o,i.toString()))}}const c=e=>{var t;const{email:o,name:i,password:c,shipping_city:r,shipping_country:s,shipping_line_1:n,shipping_line_2:a,shipping_postal_code:h,shipping_state:l,billing_city:d,billing_country:u,billing_line_1:b,billing_line_2:k,billing_postal_code:p,billing_state:m,"tax_identifier.number_type":f,"tax_identifier.number":x,...v}=e;console.log({data:e});const g={...r?{city:r}:{},...s?{country:s}:{},...n?{line_1:n}:{},...a?{line_2:a}:{},...h?{postal_code:h}:{},...l?{state:l}:{}},_={...d?{city:d}:{},...u?{country:u}:{},...b?{line_1:b}:{},...k?{line_2:k}:{},...p?{postal_code:p}:{},...m?{state:m}:{}};return{...i?{name:i}:{},...o?{email:o}:{},...c?{password:c}:{},...Object.keys(g||{}).length?{shipping_address:g}:{},...Object.keys(_||{}).length?{billing_address:_}:{},...f&&x?{tax_identifier:{number:x,number_type:f}}:{},...(null===(t=Object.keys(v))||void 0===t?void 0:t.length)?{metadata:v}:{}}},r=async e=>{const t=[...e.shadowRoot.querySelectorAll("*")].filter((e=>"function"==typeof e.reportValidity));for(const e of t)if(!await e.reportValidity())return!1;return!0}},5984:function(e,t,o){o.r(t),o.d(t,{sc_checkbox:function(){return s}});var i=o(6046),c=o(5358);let r=0;const s=class{constructor(e){(0,i.r)(this,e),this.scBlur=(0,i.c)(this,"scBlur",7),this.scChange=(0,i.c)(this,"scChange",7),this.scFocus=(0,i.c)(this,"scFocus",7),this.inputId="checkbox-"+ ++r,this.labelId=`checkbox-label-${r}`,this.hasFocus=!1,this.disabled=!1,this.static=!1,this.required=!1,this.checked=!1,this.indeterminate=!1,this.invalid=!1}firstUpdated(){this.input.indeterminate=this.indeterminate}async triggerClick(){return this.input.click()}async triggerFocus(e){return this.input.focus(e)}async triggerBlur(){return this.input.blur()}async reportValidity(){return this.invalid=!this.input.checkValidity(),this.input.reportValidity()}setCustomValidity(e){this.input.setCustomValidity(e),this.invalid=!this.input.checkValidity()}handleClick(){this.checked=!this.checked,this.indeterminate=!1}handleBlur(){this.hasFocus=!1,this.scBlur.emit()}handleFocus(){this.hasFocus=!0,this.scFocus.emit()}handleLabelMouseDown(){this.input.focus()}handleStateChange(){this.input.checked=this.checked,this.input.indeterminate=this.indeterminate,this.scChange.emit()}componentDidLoad(){this.formController=new c.F(this.el,{value:e=>e.checked?e.value:void 0}).addFormData()}disconnectedCallback(){var e;null===(e=this.formController)||void 0===e||e.removeFormData()}render(){const e=this.static?"div":"label";return(0,i.h)(e,{part:"base",class:{checkbox:!0,"checkbox--is-required":this.required,"checkbox--checked":this.checked,"checkbox--disabled":this.disabled,"checkbox--focused":this.hasFocus,"checkbox--indeterminate":this.indeterminate},htmlFor:this.inputId,onMouseDown:()=>this.handleLabelMouseDown()},(0,i.h)("span",{part:"control",class:"checkbox__control"},this.checked?(0,i.h)("span",{part:"checked-icon",class:"checkbox__icon"},(0,i.h)("svg",{viewBox:"0 0 16 16"},(0,i.h)("g",{stroke:"none","stroke-width":"1",fill:"none","fill-rule":"evenodd","stroke-linecap":"round"},(0,i.h)("g",{stroke:"currentColor","stroke-width":"2"},(0,i.h)("g",{transform:"translate(3.428571, 3.428571)"},(0,i.h)("path",{d:"M0,5.71428571 L3.42857143,9.14285714"}),(0,i.h)("path",{d:"M9.14285714,0 L3.42857143,9.14285714"})))))):"",!this.checked&&this.indeterminate?(0,i.h)("span",{part:"indeterminate-icon",class:"checkbox__icon"},(0,i.h)("svg",{viewBox:"0 0 16 16"},(0,i.h)("g",{stroke:"none","stroke-width":"1",fill:"none","fill-rule":"evenodd","stroke-linecap":"round"},(0,i.h)("g",{stroke:"currentColor","stroke-width":"2"},(0,i.h)("g",{transform:"translate(2.285714, 6.857143)"},(0,i.h)("path",{d:"M10.2857143,1.14285714 L1.14285714,1.14285714"})))))):"",(0,i.h)("input",{id:this.inputId,ref:e=>this.input=e,type:"checkbox",name:this.name,value:this.value,checked:this.checked,disabled:this.disabled,required:this.required,role:"checkbox","aria-checked":this.checked?"true":"false","aria-labelledby":this.labelId,onClick:()=>this.handleClick(),onBlur:()=>this.handleBlur(),onFocus:()=>this.handleFocus()})),(0,i.h)("span",{part:"label",id:this.labelId,class:"checkbox__label"},(0,i.h)("slot",null)))}get el(){return(0,i.g)(this)}static get watchers(){return{checked:["handleStateChange"],indeterminate:["handleStateChange"]}}};s.style=':host{display:block}.checkbox{display:flex;align-items:center;font-family:var(--sc-input-font-family);font-size:var(--sc-input-font-size-medium);font-weight:var(--sc-input-font-weight);color:var(--sc-input-color);vertical-align:middle;cursor:pointer}.checkbox__control{flex:0 0 auto;position:relative;display:inline-flex;align-items:center;justify-content:center;width:var(--sc-checkbox-size);height:var(--sc-checkbox-size);border:solid var(--sc-input-border-width) var(--sc-input-border-color);border-radius:2px;background-color:var(--sc-input-background-color);color:var(--sc-color-white);transition:var(--sc-transition-fast) border-color, var(--sc-transition-fast) opacity, var(--sc-transition-fast) background-color, var(--sc-transition-fast) color, var(--sc-transition-fast) box-shadow}.checkbox__control input[type=checkbox]{position:absolute;opacity:0;padding:0;margin:0;pointer-events:none}.checkbox__control .checkbox__icon{display:inline-flex;width:var(--sc-checkbox-size);height:var(--sc-checkbox-size)}.checkbox__control .checkbox__icon svg{width:100%;height:100%}.checkbox:not(.checkbox--checked):not(.checkbox--disabled) .checkbox__control:hover{border-color:var(--sc-input-border-color-hover);background-color:var(--sc-input-background-color-hover)}.checkbox.checkbox--focused:not(.checkbox--checked):not(.checkbox--disabled) .checkbox__control{border-color:var(--sc-input-border-color-focus);background-color:var(--sc-input-background-color-focus);box-shadow:0 0 0 var(--sc-focus-ring-width) var(--sc-focus-ring-color-primary)}.checkbox--checked .checkbox__control,.checkbox--indeterminate .checkbox__control{border-color:var(--sc-color-primary-500);background-color:var(--sc-color-primary-500)}.checkbox.checkbox--checked:not(.checkbox--disabled) .checkbox__control:hover,.checkbox.checkbox--indeterminate:not(.checkbox--disabled) .checkbox__control:hover{opacity:0.8}.checkbox.checkbox--checked:not(.checkbox--disabled).checkbox--focused .checkbox__control,.checkbox.checkbox--indeterminate:not(.checkbox--disabled).checkbox--focused .checkbox__control{border-color:var(--sc-color-white);background-color:var(--sc-color-primary-500);box-shadow:0 0 0 var(--sc-focus-ring-width) var(--sc-focus-ring-color-primary)}.checkbox--disabled{opacity:0.5;cursor:not-allowed}.checkbox__label{line-height:var(--sc-checkbox-size);margin-left:0.5em;flex:1}.checkbox--is-required .checkbox__label:after{content:" *";color:var(--sc-color-danger-500)}::slotted(*){display:inline-block}'}}]);