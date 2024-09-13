(()=>{"use strict";const e=window.wp.i18n,t=window.wp.htmlEntities,s=window.React,n=window.ReactJSXRuntime,{registerPaymentMethod:a}=window.wc.wcBlocksRegistry,{getSetting:i}=window.wc.wcSettings,r=i("mono_part_pay_data",{}),p=(0,t.decodeEntities)(r.title),o=a=>{const i=r.available_parts||[],{eventRegistration:p,emitResponse:o}=a,{onPaymentSetup:d}=p;return(0,s.useEffect)((()=>{const e=d((async()=>{const e=document.getElementById("desired_parts").value;return e?{type:o.responseTypes.SUCCESS,meta:{paymentMethodData:{desired_parts:e}}}:{type:o.responseTypes.ERROR,message:"Please select the number of desired parts."}}));return()=>{e()}}),[o.responseTypes.ERROR,o.responseTypes.SUCCESS,d]),(0,n.jsxs)("div",{children:[(0,n.jsx)("p",{children:(0,t.decodeEntities)(r.description||"")}),(0,n.jsx)("label",{htmlFor:"desired_parts",children:(0,e._nx)("Desired payments number","mono-pay-part")}),(0,n.jsx)("select",{name:"desired_parts",id:"desired_parts",children:i.map((t=>(0,n.jsx)("option",{value:t,children:sprintf((0,e._nx)("%d payment","%d payments",t,"Number of payments","mono-pay-part"),t)},t)))})]})},d=()=>r.icon?(0,n.jsx)("img",{src:r.icon,style:{float:"right",marginRight:"20px"}}):"",c=()=>(0,n.jsxs)("span",{style:{width:"100%"},children:[p,(0,n.jsx)(d,{})]});a({name:"mono_part_pay",label:(0,n.jsx)(c,{}),content:(0,n.jsx)(o,{}),edit:(0,n.jsx)(o,{}),canMakePayment:()=>!0,ariaLabel:p,supports:{features:r.supports},canMakePayment:()=>!0})})();