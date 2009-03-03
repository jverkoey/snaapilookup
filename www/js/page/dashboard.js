/**
 * The snaapi home page.
 */

(function(){
  new Snap.GhostInput('#search .text', 'Languages, frameworks, or API function names');
  new Snap.TypeAhead({
    input    : '#search .text',
    filters  : '#search .filters',
    dropdown : '#search .dropdown'
  });
  $('#search .text').focus();
})();
