"use strict";(self.webpackChunksureforms=self.webpackChunksureforms||[]).push([[8769],{8769:function(t,a,s){s.r(a),s.d(a,{sc_pagination:function(){return h}});var e=s(7492),i=s(9988);s(7094);const h=class{constructor(t){(0,e.r)(this,t),this.scPrevPage=(0,e.c)(this,"scPrevPage",7),this.scNextPage=(0,e.c)(this,"scNextPage",7),this.page=1,this.perPage=0,this.total=0,this.totalShowing=0,this.totalPages=0}componentWillLoad(){this.handlePaginationChange()}handlePaginationChange(){this.hasNextPage=this.total>1&&this.page<this.totalPages,this.hasPreviousPage=this.totalPages>1&&this.page>1,this.from=this.perPage*(this.page-1)+1,this.to=Math.min(this.from+this.totalShowing-1,this.total)}render(){return this.hasNextPage||this.hasPreviousPage?(0,e.h)("sc-flex",null,(0,e.h)("div",null,(0,i.s)((0,i._)("Displaying %1d to %2d of %3d items","surecart"),this.from,this.to,this.total)),(0,e.h)("sc-flex",null,(0,e.h)("sc-button",{onClick:()=>this.scPrevPage.emit(),disabled:!this.hasPreviousPage,size:"small"},(0,i._)("Previous","surecart")),(0,e.h)("sc-button",{onClick:()=>this.scNextPage.emit(),disabled:!this.hasNextPage,size:"small"},(0,i._)("Next","surecart")))):null}static get watchers(){return{total:["handlePaginationChange"],totalPages:["handlePaginationChange"],page:["handlePaginationChange"],perPage:["handlePaginationChange"],totalShowing:["handlePaginationChange"]}}};h.style=":host{display:block}"}}]);
//# sourceMappingURL=8769.js.map