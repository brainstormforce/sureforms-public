"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[5938],{1946:function(t,e,s){s.d(e,{o:function(){return a}});const a=(t,e)=>{new window.IntersectionObserver(((t,s)=>{t[0].intersectionRatio>0&&(e(),s.unobserve(t[0].target))})).observe(t)}},5938:function(t,e,s){s.r(e),s.d(e,{sc_dashboard_downloads_list:function(){return h}});var a=s(6046),i=s(290),r=s(712),n=s(1946),o=s(8517);s(5115);const h=class{constructor(t){(0,a.r)(this,t),this.query={page:1,per_page:10},this.purchases=[],this.pagination={total:0,total_pages:0}}componentWillLoad(){(0,n.o)(this.el,(()=>{this.initialFetch()}))}async initialFetch(){try{this.loading=!0,await this.getItems()}catch(t){console.error(this.error),this.error=(null==t?void 0:t.message)||(0,i._)("Something went wrong","surecart")}finally{this.loading=!1}}async fetchItems(){try{this.busy=!0,await this.getItems()}catch(t){console.error(this.error),this.error=(null==t?void 0:t.message)||(0,i._)("Something went wrong","surecart")}finally{this.busy=!1}}async getItems(){const t=await await(0,r.a)({path:(0,o.a)("surecart/v1/purchases/",{expand:["product","product.files"],downloadable:!0,revoked:!1,...this.query}),parse:!1});return this.pagination={total:parseInt(t.headers.get("X-WP-Total")),total_pages:parseInt(t.headers.get("X-WP-TotalPages"))},this.purchases=await t.json(),this.purchases}nextPage(){this.query.page=this.query.page+1,this.fetchItems()}prevPage(){this.query.page=this.query.page-1,this.fetchItems()}render(){var t;return(0,a.h)("sc-downloads-list",{heading:this.heading,allLink:this.allLink&&this.pagination.total_pages>1?this.allLink:"",loading:this.loading,busy:this.busy,requestNonce:this.requestNonce,error:this.error,purchases:this.purchases},(0,a.h)("span",{slot:"heading"},(0,a.h)("slot",{name:"heading"},this.heading||(0,i._)("Downloads","surecart"))),(0,a.h)("sc-pagination",{slot:"after",page:this.query.page,perPage:this.query.per_page,total:this.pagination.total,totalPages:this.pagination.total_pages,totalShowing:null===(t=null==this?void 0:this.purchases)||void 0===t?void 0:t.length,onScNextPage:()=>this.nextPage(),onScPrevPage:()=>this.prevPage()}))}get el(){return(0,a.g)(this)}};h.style=":host{display:block}.download__details{opacity:0.75}"}}]);
//# sourceMappingURL=5938.js.map