import{a as y}from"./index--_ApOnHu.js";import{q as d}from"./index-CU7IZpZl.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const h=y("CircleAlertIcon",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["line",{x1:"12",x2:"12",y1:"8",y2:"12",key:"1pkeuh"}],["line",{x1:"12",x2:"12.01",y1:"16",y2:"16",key:"4dfq90"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const i=y("ImageIcon",[["rect",{width:"18",height:"18",x:"3",y:"3",rx:"2",ry:"2",key:"1m3agn"}],["circle",{cx:"9",cy:"9",r:"2",key:"af1f0g"}],["path",{d:"m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21",key:"1xmnt7"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const n=y("InfoIcon",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M12 16v-4",key:"1dtifu"}],["path",{d:"M12 8h.01",key:"e9boi3"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const g=y("Trash2Icon",[["path",{d:"M3 6h18",key:"d0wm0j"}],["path",{d:"M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6",key:"4alrt4"}],["path",{d:"M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2",key:"v07s0e"}],["line",{x1:"10",x2:"10",y1:"11",y2:"17",key:"1uufr5"}],["line",{x1:"14",x2:"14",y1:"11",y2:"17",key:"xtxkd"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const s=y("UploadIcon",[["path",{d:"M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4",key:"ih7n3h"}],["polyline",{points:"17 8 12 3 7 8",key:"t8dd8p"}],["line",{x1:"12",x2:"12",y1:"3",y2:"15",key:"widbto"}]]),r=e=>({url:r.url(e),method:"get"});r.definition={methods:["get","head"],url:"/settings/profile"};r.url=e=>r.definition.url+d(e);r.get=e=>({url:r.url(e),method:"get"});r.head=e=>({url:r.url(e),method:"head"});const o=e=>({action:r.url(e),method:"get"});o.get=e=>({action:r.url(e),method:"get"});o.head=e=>({action:r.url({[e?.mergeQuery?"mergeQuery":"query"]:{_method:"HEAD",...e?.query??e?.mergeQuery??{}}}),method:"get"});r.form=o;const t=e=>({url:t.url(e),method:"patch"});t.definition={methods:["patch"],url:"/settings/profile"};t.url=e=>t.definition.url+d(e);t.patch=e=>({url:t.url(e),method:"patch"});const l=e=>({action:t.url({[e?.mergeQuery?"mergeQuery":"query"]:{_method:"PATCH",...e?.query??e?.mergeQuery??{}}}),method:"post"});l.patch=e=>({action:t.url({[e?.mergeQuery?"mergeQuery":"query"]:{_method:"PATCH",...e?.query??e?.mergeQuery??{}}}),method:"post"});t.form=l;const u=e=>({url:u.url(e),method:"delete"});u.definition={methods:["delete"],url:"/settings/profile"};u.url=e=>u.definition.url+d(e);u.delete=e=>({url:u.url(e),method:"delete"});const m=e=>({action:u.url({[e?.mergeQuery?"mergeQuery":"query"]:{_method:"DELETE",...e?.query??e?.mergeQuery??{}}}),method:"post"});m.delete=e=>({action:u.url({[e?.mergeQuery?"mergeQuery":"query"]:{_method:"DELETE",...e?.query??e?.mergeQuery??{}}}),method:"post"});u.form=m;export{h as C,n as I,g as T,s as U,i as a,r as e};
