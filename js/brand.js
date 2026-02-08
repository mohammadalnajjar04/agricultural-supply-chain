(function(){
  // Simple reveal on load (no libraries)
  function revealNow(){
    document.querySelectorAll('.reveal').forEach(function(el){
      el.classList.add('is-visible');
    });
  }

  // Password toggle (Framework-like UX)
  function initPasswordToggles(){
    document.querySelectorAll('[data-toggle-password]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var targetId = btn.getAttribute('data-target');
        if (!targetId) return;
        var input = document.getElementById(targetId);
        if (!input) return;
        var isPass = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPass ? 'text' : 'password');
        btn.classList.toggle('is-on', isPass);
        var icon = btn.querySelector('i');
        if (icon) {
          icon.classList.toggle('fa-eye', !isPass);
          icon.classList.toggle('fa-eye-slash', isPass);
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){
      revealNow();
      initPasswordToggles();
    });
  } else {
    revealNow();
    initPasswordToggles();
  }
})();
