(function(){
'use strict';
document.querySelectorAll('[data-confirm]').forEach(function(form){form.addEventListener('submit',function(event){if(!window.confirm(form.dataset.confirm))event.preventDefault()})});
var states={active:'success',published:'success',publish:'success',paid:'success',delivered:'success',confirmed:'info',processing:'info',shipped:'info',pending:'warning',unpaid:'warning',draft:'warning',hidden:'danger',inactive:'danger',archived:'danger',cancelled:'danger',refunded:'danger',outofstock:'danger'};
function decorateStatuses(scope){scope.querySelectorAll('.admin-table td').forEach(function(cell){var key=cell.textContent.trim().toLowerCase().replace(/\s+/g,'');if(states[key]&&!cell.querySelector('a,button,input,form,.tq-status'))cell.innerHTML='<span class="tq-status tq-status-'+states[key]+'">'+cell.textContent.trim()+'</span>'})}
function csvValue(value){return '"'+String(value).replace(/"/g,'""')+'"'}
function enhanceTable(table,index){
 if(table.dataset.tqGrid)return;table.dataset.tqGrid='1';
 var body=table.tBodies[0],heads=Array.from(table.querySelectorAll('thead th')),scroll=table.closest('.table-scroll');if(!body||!heads.length||!scroll)return;
 var tools=document.createElement('div');tools.className='tq-table-tools';
 var search=document.createElement('input');search.type='search';search.placeholder='Search this table…';search.setAttribute('aria-label','Search table');
 var column=document.createElement('select');column.setAttribute('aria-label','Choose search column');column.innerHTML='<option value="-1">All columns</option>'+heads.map(function(head,i){return '<option value="'+i+'">'+head.textContent.trim()+'</option>'}).join('');
 var limit=document.createElement('select');limit.setAttribute('aria-label','Rows to show');limit.innerHTML='<option value="10">10 rows</option><option value="25" selected>25 rows</option><option value="50">50 rows</option><option value="0">All rows</option>';
 var count=document.createElement('span');count.className='tq-table-count';
 var reset=document.createElement('button');reset.type='button';reset.className='tq-table-reset';reset.textContent='Reset';
 var exportButton=document.createElement('button');exportButton.type='button';exportButton.className='tq-table-export';exportButton.innerHTML='<i class="icon-download"></i> CSV';
 tools.append(search,column,limit,count,reset,exportButton);scroll.parentNode.insertBefore(tools,scroll);
 var query='',columnIndex=-1,rowLimit=25,sortIndex=-1,sortDirection=1;
 function rows(){return Array.from(body.rows).filter(function(row){return !row.classList.contains('tq-grid-empty')})}
 function apply(){
  var found=0,shown=0;rows().forEach(function(row){var cells=Array.from(row.cells),value=columnIndex<0?cells.map(function(cell){return cell.textContent}).join(' '):(cells[columnIndex]?cells[columnIndex].textContent:'');var match=!query||value.toLocaleLowerCase().indexOf(query)>-1;if(match)found++;var visible=match&&(!rowLimit||shown<rowLimit);row.hidden=!visible;if(visible)shown++});
  var empty=body.querySelector('.tq-grid-empty');if(empty)empty.remove();if(!shown){var row=body.insertRow(),cell=row.insertCell();row.className='tq-grid-empty';cell.colSpan=heads.length;cell.textContent='No matching records. Try another search or filter.'}
  count.textContent='Showing '+shown+' of '+found;
 }
 function compare(a,b){var av=a.cells[sortIndex]?a.cells[sortIndex].textContent.trim():'',bv=b.cells[sortIndex]?b.cells[sortIndex].textContent.trim():'';var an=Number(av.replace(/[^0-9.-]/g,'')),bn=Number(bv.replace(/[^0-9.-]/g,''));return Number.isFinite(an)&&Number.isFinite(bn)&&av!==''&&bv!==''?(an-bn)*sortDirection:av.localeCompare(bv,undefined,{numeric:true,sensitivity:'base'})*sortDirection}
 heads.forEach(function(head,i){head.tabIndex=0;head.setAttribute('role','button');head.title='Sort by '+head.textContent.trim();var sort=function(){heads.forEach(function(item){item.classList.remove('sort-asc','sort-desc')});sortDirection=sortIndex===i?-sortDirection:1;sortIndex=i;head.classList.add(sortDirection===1?'sort-asc':'sort-desc');rows().sort(compare).forEach(function(row){body.appendChild(row)});apply()};head.addEventListener('click',sort);head.addEventListener('keydown',function(event){if(event.key==='Enter'||event.key===' '){event.preventDefault();sort()}})});
 search.addEventListener('input',function(){query=search.value.trim().toLocaleLowerCase();apply()});column.addEventListener('change',function(){columnIndex=Number(column.value);apply()});limit.addEventListener('change',function(){rowLimit=Number(limit.value);apply()});
 reset.addEventListener('click',function(){query='';columnIndex=-1;rowLimit=25;search.value='';column.value='-1';limit.value='25';sortIndex=-1;heads.forEach(function(head){head.classList.remove('sort-asc','sort-desc')});apply();search.focus()});
 exportButton.addEventListener('click',function(){var lines=[heads.map(function(head){return csvValue(head.textContent.trim())}).join(',')];rows().filter(function(row){return !row.hidden}).forEach(function(row){lines.push(Array.from(row.cells).map(function(cell){return csvValue(cell.textContent.trim().replace(/\s+/g,' '))}).join(','))});var blob=new Blob(['\ufeff'+lines.join('\r\n')],{type:'text/csv;charset=utf-8'}),link=document.createElement('a');link.href=URL.createObjectURL(blob);link.download='tiba-quip-table-'+(index+1)+'.csv';link.click();setTimeout(function(){URL.revokeObjectURL(link.href)},500)});
 apply();
}
decorateStatuses(document);
if(!document.body.classList.contains('admin-section-dashboard'))document.querySelectorAll('.admin-table').forEach(enhanceTable);
})();
