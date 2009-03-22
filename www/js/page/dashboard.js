/**
 * The snaapi home page.
 */

new Snap.Database();

$(function(){
  new Snap.GhostInput('#search .text', 'API lookups or #languages and #frameworks');
  var filters = new Snap.FilterBar({
    filters  : '#filters'
  });
  new Snap.TypeAhead({
    search        : '#search',
    input         : '#search .text',
    dropdown      : '#search .dropdown',
    result        : '#result',
    external      : '#external-page',
    content_table : '#content-table',
    parent_table  : '#page-table',
    messages      : '#messages',
    goback        : '#goback',
    whyjoin       : '#whyjoin'
  }, filters);

  new Snap.TreeView({
    view          : '#tree-view .list'
  }, filters);

  if( !window.user_id ) {
    new Snap.OpenIdLogin('#auth .login');
  }

  $('#whyjoin .imagish').click(function() {
    $(this).fadeOut('fast', function() {
      $('#whyjoin .textish').fadeIn('fast');
    });
  });

  //$('#search .text').focus();

  Snap.Database.singleton.load();
});
