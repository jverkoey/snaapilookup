/**
 * The snaapi home page.
 */

(function(){
  new Snap.GhostInput('#search .text', 'Languages, frameworks, or API function names');
  new Snap.TypeAhead({
    input    : '#search .text',
    filters  : '#search .filters',
    dropdown : '#search .dropdown',
    result   : '#result',
    external : '#external_page',
    catch_phrase : '#catch-phrase',
    logo     : '#logo',
    small_logo : '#small-logo'
  });
  $('#search .text').focus();
})();
