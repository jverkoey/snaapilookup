/**
 * The snaapi home page.
 */

$(function(){
  new Snap.GhostInput('#search .text', 'Languages, frameworks, or API function names');
  var db = new Snap.Database();
  new Snap.TypeAhead({
    search   : '#search',
    input    : '#search .text',
    dropdown : '#search .dropdown',
    result   : '#result',
    external_table : '#external_table',
    external : '#external_page',
    catch_phrase : '#catch-phrase',
    logo     : '#logo',
    small_logo : '#small-logo',
    whyjoin  : '#whyjoin'
  }, new Snap.FilterBar({
    filters  : '#filters'
  }));
  if( !window.user_id ) {
    new Snap.OpenIdLogin('#auth .login');
  }
  if( !window.sel ) {
    $('#search .text').focus();
  }

  db.load();
});
