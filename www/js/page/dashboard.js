/**
 * The snaapi home page.
 */

new Snap.Database();

$(function(){
  new Snap.GhostInput('#search .text', 'Languages, frameworks, or API function names');
  new Snap.TypeAhead({
    search        : '#search',
    input         : '#search .text',
    dropdown      : '#search .dropdown',
    result        : '#result',
    external      : '#external-page',
    content_table : '#content-table',
    parent_table  : '#page-table',
    messages      : '#messages',
    goback        : '#goback'
  }, new Snap.FilterBar({
    filters  : '#filters'
  }));
  if( !window.user_id ) {
    new Snap.OpenIdLogin('#auth .login');
  }
  if( !window.sel ) {
    $('#search .text').focus();
  }

  Snap.Database.singleton.load();
});
