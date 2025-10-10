// ui.js: light UI helpers, autocomplete implementation, and SVG loader

function $(sel, root=document){return root.querySelector(sel)}
function $all(sel, root=document){return Array.from(root.querySelectorAll(sel))}

const UI = (function(){
  const suggestionsEl = $('#suggestions');
  let items = [];
  let activeIndex = -1;

  function setItems(list){
    items = list.slice().sort();
    render();
  }

  function render(filter=''){
    suggestionsEl.innerHTML = '';
    const f = filter.trim().toLowerCase();
    const matches = items.filter(it=>it.toLowerCase().includes(f));
    if(matches.length===0){suggestionsEl.style.display='none';return}
    matches.forEach((m, idx)=>{
      const div = document.createElement('div');
      div.className='item';
      div.textContent = m;
      div.dataset.index = idx;
      div.addEventListener('click', ()=>{
        selectItem(m);
      });
      suggestionsEl.appendChild(div);
    });
    suggestionsEl.style.display='block';
    activeIndex=-1;
  }

  function selectItem(text){
    $('#guess-input').value = text;
    hide();
    $('#guess-input').focus();
  }

  function hide(){ suggestionsEl.style.display='none' }

  function onKey(e){
    const visible = suggestionsEl.style.display!=='none';
    const itemsEls = Array.from(suggestionsEl.children);
    if(e.key==='ArrowDown' && visible){
      activeIndex = Math.min(activeIndex+1, itemsEls.length-1);
      updateActive(itemsEls);
      e.preventDefault();
    } else if(e.key==='ArrowUp' && visible){
      activeIndex = Math.max(activeIndex-1, 0);
      updateActive(itemsEls);
      e.preventDefault();
    } else if(e.key==='Enter' && visible && activeIndex>=0){
      selectItem(itemsEls[activeIndex].textContent);
      e.preventDefault();
    }
  }

  function updateActive(itemsEls){
    itemsEls.forEach(el=>el.classList.remove('active'));
    if(activeIndex>=0 && itemsEls[activeIndex]) itemsEls[activeIndex].classList.add('active');
  }

  // SVG loader: load an external SVG file and inject into #map-wrapper
  //v2
  async function loadSVG(path){
  const wrapper = $('#map-wrapper');
  wrapper.innerHTML = '<div id="map-placeholder">Loading map…</div>';
  try{
    const res = await fetch(path);
    console.log('Fetch response:', res); // <-- add this
    if(!res.ok) throw new Error('Failed to load SVG');
    const svgText = await res.text();
    console.log('SVG length:', svgText.length); // <-- add this
    wrapper.innerHTML = svgText;
    $all('svg path').forEach(p=>p.classList.add('state'));
    return wrapper.querySelector('svg');
  }catch(err){
    wrapper.innerHTML = '<div class="error">Could not load map.</div>';
    console.error(err);
    return null;
  }
}
//v1
  // async function loadSVG(path){
  //   const wrapper = $('#map-wrapper');
  //   wrapper.innerHTML = '<div id="map-placeholder">Loading map…</div>';
  //   try{
  //     const res = await fetch(path);
  //     if(!res.ok) throw new Error('Failed to load SVG');
  //     const svgText = await res.text();
  //     wrapper.innerHTML = svgText;
  //     // mark paths as .state for styling convenience
  //     $all('svg path').forEach(p=>p.classList.add('state'));
  //     return wrapper.querySelector('svg');
  //   }catch(err){
  //     wrapper.innerHTML = '<div class="error">Could not load map.</div>';
  //     console.error(err);
  //     return null;
  //   }
  // }

  return { setItems, onKey, hide, loadSVG };
})();
