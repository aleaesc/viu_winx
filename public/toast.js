(function(){
  function ensureStack(){
    var stack = document.getElementById('toast-stack');
    if(!stack){
      stack = document.createElement('div');
      stack.id='toast-stack';
      stack.className='toast-stack';
      document.body.appendChild(stack);
    } else if (!stack.classList.contains('toast-stack')) {
      stack.classList.add('toast-stack');
    }
    return stack;
  }
  var defaults = {
    success:{title:'Success', msg:'Your changes are saved successfully.'},
    error:{title:'Error', msg:'Error has occured while saving changes.'},
    info:{title:'Info', msg:'New settings available on your account.'},
    warning:{title:'Warning', msg:'Username you have entered is invalid.'}
  };
  window.showToast = function(type, message){
    var cfg = defaults[type] || defaults.info;
    var stack = ensureStack();
    var el = document.createElement('div');
    el.className = 'toast toast--'+(type||'info');
    var icons = {
      success:'<svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20"/><path d="M26 15 L18 23 L14 19" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
      error:'<svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20"/><path d="M15 15 L25 25 M25 15 L15 25" stroke="white" stroke-width="3" stroke-linecap="round"/></svg>',
      info:'<svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20"/><text x="20" y="26" text-anchor="middle" font-size="18" font-weight="800" fill="white">i</text></svg>',
      warning:'<svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="20"/><text x="20" y="26" text-anchor="middle" font-size="18" font-weight="800" fill="white">!</text></svg>'
    };
      el.innerHTML = ''+
        '<div class="toast__bar"></div>'+
        '<button type="button" aria-label="Close" class="toast__close">×</button>'+
        '<div class="toast__wrap">'+
          '<div class="toast__icon">'+icons[type||'info']+'</div>'+
          '<div class="toast__text">'+
            '<div class="toast__title">'+cfg.title+'</div>'+
            '<div class="toast__msg">'+(message||cfg.msg)+'</div>'+
          '</div>'+
        '</div>'+
        '<div class="toast__timer"><div class="toast__timer-fill"></div></div>';

      // show immediately, dismiss after 2 seconds
      stack.appendChild(el);
      var fill = el.querySelector('.toast__timer-fill');
      fill.style.transition = 'width 2s linear';
      requestAnimationFrame(function(){ fill.style.width = '100%'; });
      var closer = el.querySelector('.toast__close');
      var t = setTimeout(function(){ el.remove(); }, 2000);
      closer.addEventListener('click', function(){ clearTimeout(t); el.remove(); });
  };
})();
