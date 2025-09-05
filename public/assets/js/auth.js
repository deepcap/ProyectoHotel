// /public/assets/js/auth.js
(function(){
  // Redirige a login si no hay sesión (demo)
  if (sessionStorage.getItem('auth') !== '1') {
    window.location = '/public/pages/login.html';
  }
})();

function logout(){
  sessionStorage.removeItem('auth');
  window.location = '/public/pages/login.html';
}