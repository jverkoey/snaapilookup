/**
 * Suggestion text displayed in an input box that disappears on focus.
 *
 * @require-package core
 */

Snap.GhostInput = function(id, suggestionText) {

  var actions = {
    focus: function() {
      if( $(this).val() == suggestionText ) {
        $(this).val('');
      }
      $(this).removeClass('inactive');
    },

    blur: function() {
      if( $(this).val() == '' || $(this).val() == suggestionText ) {
        $(this)
          .val(suggestionText)
          .addClass('inactive');
      }
    },

    init: function() {
      if( $(this).val() == '' || $(this).val() == suggestionText ) {
        $(this)
          .val(suggestionText)
          .addClass('inactive');
      }
    }
  };

  $(id)
    .focus(actions.focus)
    .blur(actions.blur)
    .each(actions.init);
}
