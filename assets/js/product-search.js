(() => {
  'use strict';
  document.querySelectorAll('[data-product-search]').forEach(form => {
    const input=form.querySelector('.home-search-input'),category=form.querySelector('.home-search-category'),list=form.querySelector('.home-search-suggestions');
    if(!input||!category||!list)return;
    let timer=null,controller=null,items=[];
    const close=()=>{list.hidden=true;list.replaceChildren();input.setAttribute('aria-expanded','false');items=[];};
    const addText=(parent,tag,text,className)=>{const element=document.createElement(tag);element.textContent=text;if(className)element.className=className;parent.append(element);return element;};
    const normalise=value=>String(value||'').normalize('NFKD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,' ').trim();
    const distance=(a,b)=>{
      if(a===b)return 0;if(!a.length)return b.length;if(!b.length)return a.length;
      let previous=Array.from({length:b.length+1},(_,i)=>i);
      for(let i=1;i<=a.length;i++){const current=[i];for(let j=1;j<=b.length;j++)current[j]=Math.min(current[j-1]+1,previous[j]+1,previous[j-1]+(a[i-1]===b[j-1]?0:1));previous=current;}
      return previous[b.length];
    };
    const matchScore=(product,query)=>{
      const needle=normalise(query),text=normalise(`${product.name||''} ${product.category||''}`);if(!needle||!text)return 0;
      if(text.includes(needle))return 100;
      const queryWords=needle.split(' '),words=text.split(' '),compact=queryWords.join('');
      const candidates=[...words];for(let i=0;i<words.length;i++){if(words[i+1])candidates.push(words[i]+words[i+1]);if(words[i+2])candidates.push(words[i]+words[i+1]+words[i+2]);}
      let compactBest=0;for(const candidate of candidates){const length=Math.max(compact.length,candidate.length);if(!length||Math.abs(compact.length-candidate.length)>Math.max(3,Math.ceil(length*.35)))continue;compactBest=Math.max(compactBest,Math.round((1-distance(compact,candidate)/length)*100));}
      const tokenScores=queryWords.map(word=>{let best=0;for(const candidate of words){if(candidate===word)return 100;if(word.length>=3&&candidate.includes(word)){best=Math.max(best,92);continue;}const length=Math.max(word.length,candidate.length);if(length&&Math.abs(word.length-candidate.length)<=Math.max(3,Math.ceil(length*.4)))best=Math.max(best,Math.round((1-distance(word,candidate)/length)*100));}return best;});
      return Math.max(compactBest,tokenScores.reduce((sum,score)=>sum+score,0)/tokenScores.length);
    };
    const render=(products,query)=>{
      list.replaceChildren();
      products.forEach(product=>{product.clientScore=matchScore(product,query);});
      products.sort((a,b)=>b.clientScore-a.clientScore||(Number(b.score)||0)-(Number(a.score)||0));
      products.slice(0,6).forEach(product=>{
        const link=document.createElement('a');link.href=product.url;link.className='home-search-suggestion';link.setAttribute('role','option');link.tabIndex=-1;
        const image=document.createElement('img');image.src=product.image;image.alt='';image.loading='lazy';link.append(image);
        const copy=document.createElement('span');addText(copy,'strong',product.name);if(product.category)addText(copy,'small',product.category);link.append(copy);addText(link,'b',product.price);list.append(link);
      });
      const all=document.createElement('a');const target=new URL(form.action,location.href);target.searchParams.set('q',query);all.href=target.toString();all.className='home-search-view-all';all.setAttribute('role','option');all.tabIndex=-1;all.textContent=`View all results for “${query}”`;list.append(all);
      items=[...list.querySelectorAll('a')];list.hidden=false;input.setAttribute('aria-expanded','true');
    };
    const search=async()=>{
      const query=input.value.trim();if(query.length<2){close();return;}
      controller?.abort();controller=new AbortController();
      const endpoint=new URL(form.dataset.searchEndpoint,location.href);endpoint.searchParams.set('q',query);if(category.value)endpoint.searchParams.set('category',category.value);
      try{const response=await fetch(endpoint,{credentials:'same-origin',cache:'no-store',signal:controller.signal});if(!response.ok)throw new Error();const data=await response.json();if(input.value.trim()===query)render(Array.isArray(data.products)?data.products:[],query);}catch(error){if(error.name!=='AbortError')close();}
    };
    input.addEventListener('input',()=>{clearTimeout(timer);if(input.value.trim().length<2){controller?.abort();close();return;}timer=setTimeout(search,300);});
    category.addEventListener('change',()=>{if(input.value.trim().length>=2)search();});
    input.addEventListener('keydown',event=>{if(event.key==='ArrowDown'&&!list.hidden&&items.length){event.preventDefault();items[0].focus();}else if(event.key==='Escape')close();});
    list.addEventListener('keydown',event=>{const index=items.indexOf(document.activeElement);if(event.key==='ArrowDown'){event.preventDefault();items[Math.min(items.length-1,index+1)]?.focus();}else if(event.key==='ArrowUp'){event.preventDefault();if(index<=0)input.focus();else items[index-1]?.focus();}else if(event.key==='Escape'){close();input.focus();}});
    form.addEventListener('submit',()=>{if(!category.value)category.disabled=true;});
    document.addEventListener('click',event=>{if(!form.contains(event.target))close();});
  });
})();
