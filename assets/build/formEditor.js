!function(){"use strict";var e=window.wp.element,t=window.wp.plugins,n=window.wp.editPost,a=window.wp.components,l=window.wp.i18n;const i=e=>{console.log("Selecting tab",e)};(0,t.registerPlugin)("plugin-document-setting-panel-demo",{render:()=>(0,e.createElement)(n.PluginDocumentSettingPanel,{initialOpen:!0,title:(0,l.__)("Form Options Panel","sureforms"),name:"sureforms-forms-panel",className:"sureforms--panel"},(0,e.createElement)(a.TabPanel,{activeClass:"active-tab",onSelect:i,tabs:[{name:"tab1",title:"Tab 1",className:"tab-one"},{name:"tab2",title:"Tab 2",className:"tab-two"}]},(t=>(0,e.createElement)("p",null,t.title)))),icon:"palmtree"})}();
//# sourceMappingURL=formEditor.js.map