"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[1012],{1946:function(e,t,r){r.d(t,{o:function(){return s}});const s=(e,t)=>{new window.IntersectionObserver(((e,r)=>{e[0].intersectionRatio>0&&(t(),r.unobserve(e[0].target))})).observe(e)}},1012:function(e,t,r){r.r(t),r.d(t,{sc_orders_list:function(){return l}});var s=r(6046),i=r(290),a=r(712),n=r(1946),o=r(8517);r(5115);const l=class{constructor(e){(0,s.r)(this,e),this.query={page:1,per_page:10},this.orders=[],this.pagination={total:0,total_pages:0}}componentWillLoad(){(0,n.o)(this.el,(()=>{this.initialFetch()}))}async initialFetch(){try{this.loading=!0,await this.getOrders()}catch(e){console.error(this.error),this.error=(null==e?void 0:e.message)||(0,i._)("Something went wrong","surecart")}finally{this.loading=!1}}async fetchOrders(){try{this.busy=!0,await this.getOrders()}catch(e){console.error(this.error),this.error=(null==e?void 0:e.message)||(0,i._)("Something went wrong","surecart")}finally{this.busy=!1}}async getOrders(){const e=await await(0,a.a)({path:(0,o.a)("surecart/v1/orders/",{expand:["line_items","charge"],...this.query}),parse:!1});return this.pagination={total:parseInt(e.headers.get("X-WP-Total")),total_pages:parseInt(e.headers.get("X-WP-TotalPages"))},this.orders=await e.json(),this.orders}nextPage(){this.query.page=this.query.page+1,this.fetchOrders()}prevPage(){this.query.page=this.query.page-1,this.fetchOrders()}renderStatusBadge(e){const{status:t,charge:r}=e;if(r&&"object"==typeof r){if(null==r?void 0:r.fully_refunded)return(0,s.h)("sc-tag",{type:"danger"},(0,i._)("Refunded","surecart"));if(null==r?void 0:r.refunded_amount)return(0,s.h)("sc-tag",{type:"info"},(0,i._)("Partially Refunded","surecart"))}return(0,s.h)("sc-order-status-badge",{status:t})}renderLoading(){return(0,s.h)("sc-card",{noPadding:!0},(0,s.h)("sc-stacked-list",null,(0,s.h)("sc-stacked-list-row",{style:{"--columns":"4"},"mobile-size":500},[...Array(4)].map((()=>(0,s.h)("sc-skeleton",{style:{width:"100px",display:"inline-block"}}))))))}renderEmpty(){return(0,s.h)("div",null,(0,s.h)("sc-divider",{style:{"--spacing":"0"}}),(0,s.h)("slot",{name:"empty"},(0,s.h)("sc-empty",{icon:"shopping-bag"},(0,i._)("You don't have any orders.","surecart"))))}renderList(){return this.orders.map((e=>{var t,r;const{line_items:a,total_amount:n,currency:o,charge:l,created_at:d,url:c}=e;return(0,s.h)("sc-stacked-list-row",{href:c,style:{"--columns":"4"},"mobile-size":500},(0,s.h)("div",null,"string"!=typeof l&&(0,s.h)("sc-format-date",{class:"order__date",date:1e3*((null==l?void 0:l.created_at)||d),month:"short",day:"numeric",year:"numeric"})),(0,s.h)("div",null,(0,s.h)("sc-text",{truncate:!0,style:{"--color":"var(--sc-color-gray-500)"}},(0,i.s)((0,i.a)("%s item","%s items",(null===(t=null==a?void 0:a.pagination)||void 0===t?void 0:t.count)||0,"surecart"),(null===(r=null==a?void 0:a.pagination)||void 0===r?void 0:r.count)||0))),(0,s.h)("div",null,this.renderStatusBadge(e)),(0,s.h)("div",null,(0,s.h)("sc-format-number",{type:"currency",currency:o,value:n})))}))}renderContent(){var e;return this.loading?this.renderLoading():0===(null===(e=this.orders)||void 0===e?void 0:e.length)?this.renderEmpty():(0,s.h)("sc-card",{"no-padding":!0},(0,s.h)("sc-stacked-list",null,this.renderList()))}render(){var e,t;return(0,s.h)("sc-dashboard-module",{class:"orders-list",error:this.error},(0,s.h)("span",{slot:"heading"},(0,s.h)("slot",{name:"heading"},this.heading||(0,i._)("Order History","surecart"))),!!this.allLink&&!!(null===(e=this.orders)||void 0===e?void 0:e.length)&&(0,s.h)("sc-button",{type:"link",href:this.allLink,slot:"end"},(0,i._)("View all","surecart"),(0,s.h)("sc-icon",{name:"chevron-right",slot:"suffix"})),this.renderContent(),!this.allLink&&(0,s.h)("sc-pagination",{page:this.query.page,perPage:this.query.per_page,total:this.pagination.total,totalPages:this.pagination.total_pages,totalShowing:null===(t=null==this?void 0:this.orders)||void 0===t?void 0:t.length,onScNextPage:()=>this.nextPage(),onScPrevPage:()=>this.prevPage()}),this.busy&&(0,s.h)("sc-block-ui",null))}get el(){return(0,s.g)(this)}};l.style=":host{display:block}.orders-list{display:grid;gap:0.75em}.orders-list__heading{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between}.orders-list__title{font-size:var(--sc-font-size-x-large);font-weight:var(--sc-font-weight-bold);line-height:var(--sc-line-height-dense)}.orders-list a{text-decoration:none;font-weight:var(--sc-font-weight-semibold);display:inline-flex;align-items:center;gap:0.25em;color:var(--sc-color-primary-500)}.order__row{color:var(--sc-color-gray-800);text-decoration:none;display:grid;align-items:center;justify-content:space-between;gap:0;grid-template-columns:1fr 1fr 1fr auto;margin:0;padding:var(--sc-spacing-small) var(--sc-spacing-large)}.order__row:not(:last-child){border-bottom:1px solid var(--sc-color-gray-200)}.order__row:hover{background:var(--sc-color-gray-50)}.order__date{font-weight:var(--sc-font-weight-semibold)}"}}]);