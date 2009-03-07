/**
 * Inline email checker.
 *
 * @require-package core
 */

Snap.EmailCheck = function (input, info) {
  this._input     = input;
  this._info      = info;
  this._last      = null;
  this._timeout   = null;

  this._s = {
    DEFAULT     : 'in case of forgetfulness',
    INVALID     : 'invalid email',
    VALID_EMAIL : 'looks good'
  };

  this.init();
};

Snap.EmailCheck.prototype = {

  init          : function() {
    var obj = this;

    this._input.keyup(function() {
      obj._checkInput.bind(obj)();
    });

    if( this._input.val() != '' ) {
      this._checkInput({now: true});
      show(this._info.parent());
    } else {
      this._info.text(this._s.DEFAULT);
    }
  },

  _checkInput       : function(options) {
    var email = $(this._input).val();
    if( this._last == email ) {
      return;
    }
    this._last = email;

    if( this._timeout ) {
      clearTimeout(this._timeout);
      this._timeout = null;
    }

    if( email == '' ) {
      this._info
      .text(this._s.DEFAULT)
      .parent()
        .animate({
          backgroundColor: Snap.ColourPalette.White
        }, 500);
    } else {
      if( options && options.now ) {
        this.checkValidity();
      } else {
        this._timeout = setTimeout(this.checkValidity.bind(this), 500);
      }
    }
  },

  checkValidity : function() {
    var email = this._input.val();
    if( email.length == 0 ) {
      return;
    }

    var error = null;
    if( email.length < 5 ) {
      error = this._s.INVALID;
    } else if( !(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(email)) ) {
      error = this._s.INVALID;
    }

    if( error ) {
      this._info
      .text(error)
      .parent()
        .animate({
          backgroundColor: Snap.ColourPalette.Error
        }, 500);
    } else {
      this._info
      .text(this._s.VALID_EMAIL)
      .parent()
        .animate({
          backgroundColor: Snap.ColourPalette.Success
        }, 500);
    }
  }

};