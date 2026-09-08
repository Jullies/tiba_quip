(() => {
  const variants = document.getElementById('variants');
  function field(value, type='text') { const input=document.createElement('input'); input.type=type; input.value=value??''; if(type==='number'){input.min='0';input.step='0.01';} return input; }
  function button(label, fn) { const b=document.createElement('button');b.type='button';b.className='tf-button style-3';b.textContent=label;b.addEventListener('click',fn);return b; }
  function parse(value, fallback) { try { return JSON.parse(value); } catch { return fallback; } }
  function makeTable(headers) { const wrap=document.createElement('div');wrap.className='table-scroll mb-4';const table=document.createElement('table');table.className='admin-table';const head=table.createTHead().insertRow();headers.forEach(h=>{const th=document.createElement('th');th.textContent=h;head.append(th)});const body=table.createTBody();wrap.append(table);return {wrap,body}; }
  function cell(row, node) { const c=row.insertCell();c.append(node);return node; }
  if(variants) {
    let data=parse(variants.value,[]);if(!Array.isArray(data))data=[];
    const grid=makeTable(['SKU','Options (Size: Large; Color: Blue)','Regular price','Selling price','Stock','Track stock','Availability','']);
    const entries=[];
    function add(v={}) {
      const row=grid.body.insertRow();let attrs=typeof v.attributes==='string'?parse(v.attributes,{}):(v.attributes||{});
      const entry={original:v,row,sku:cell(row,field(v.sku)),options:cell(row,field(Object.entries(attrs).map(([k,v])=>`${k}: ${v}`).join('; '))),regular:cell(row,field(v.regular_price??v.price??0,'number')),price:cell(row,field(v.price??0,'number')),stock:cell(row,field(v.stock??0,'number'))};
      entry.manage=cell(row,field('1','checkbox'));entry.manage.checked=Boolean(Number(v.manage_stock||0));
      const select=document.createElement('select');['instock','outofstock','onbackorder'].forEach(s=>{const o=document.createElement('option');o.value=s;o.textContent=s;select.append(o)});select.value=v.stock_status||'instock';entry.status=cell(row,select);
      cell(row,button('Remove',()=>{entry.removed=true;row.remove()}));entries.push(entry);
    }
    data.forEach(add);variants.hidden=true;
    if(variants.previousElementSibling?.classList.contains('help'))variants.previousElementSibling.textContent='Each option has its own SKU, price and stock. Removing an option deactivates it while retaining order history.';
    variants.before(grid.wrap);variants.before(button('Add variant',()=>add()));
    variants.form.addEventListener('submit',()=>{variants.value=JSON.stringify(entries.filter(e=>!e.removed).map(e=>({...e.original,sku:e.sku.value,attributes:Object.fromEntries(e.options.value.split(';').filter(s=>s.trim()).map(s=>{const i=s.indexOf(':');return i<0?['Option',s.trim()]:[s.slice(0,i).trim(),s.slice(i+1).trim()]})),regular_price:e.regular.value,price:e.price.value,stock:e.stock.value,manage_stock:e.manage.checked?1:0,stock_status:e.status.value,active:1})));});
  }
  const attributes=document.getElementById('attributes');
  if(attributes) {
    const data=parse(attributes.value,{});const grid=makeTable(['Attribute','Values (separate with |)','']);const entries=[];
    function add(key='',v={}) {const row=grid.body.insertRow();const e={key,original:v,row,name:cell(row,field(v.name||key)),value:cell(row,field(Array.isArray(v.values)?v.values.join(' | '):(v.value||'')))};cell(row,button('Remove',()=>{e.removed=true;row.remove()}));entries.push(e);}
    Object.entries(data).forEach(([k,v])=>add(k,v));attributes.hidden=true;if(attributes.nextElementSibling?.classList.contains('help'))attributes.nextElementSibling.hidden=true;
    attributes.before(grid.wrap);attributes.before(button('Add attribute',()=>add()));attributes.form.addEventListener('submit',()=>{attributes.value=JSON.stringify(Object.fromEntries(entries.filter(e=>!e.removed&&e.name.value.trim()).map(e=>[e.name.value.trim(),{...e.original,name:e.name.value.trim(),value:e.value.value,values:e.value.value.split('|').map(v=>v.trim()).filter(Boolean)}])));});
  }
  const dimensions=document.getElementById('dimensions');
  if(dimensions) {const data=parse(dimensions.value,{});const wrapper=document.createElement('div');wrapper.className='row';const fields={};['length','width','height'].forEach(k=>{const col=document.createElement('div');col.className='col-md-4';const label=document.createElement('label');label.textContent=k[0].toUpperCase()+k.slice(1);fields[k]=field(data[k]??'','number');label.append(fields[k]);col.append(label);wrapper.append(col)});dimensions.hidden=true;dimensions.before(wrapper);if(dimensions.nextElementSibling?.classList.contains('help'))dimensions.nextElementSibling.hidden=true;dimensions.form.addEventListener('submit',()=>{dimensions.value=JSON.stringify(Object.fromEntries(Object.entries(fields).map(([k,v])=>[k,v.value])))});}
})();
