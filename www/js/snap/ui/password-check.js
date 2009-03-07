/**
 * Inline password checker.
 *
 * @require-package core
 */

Snap.PasswordCheck = function (input, info, opt_usernamecheck) {
  this._input     = input;
  this._info      = info;
  this._username  = opt_usernamecheck;
  this._last      = null;
  this._timeout   = null;

  this._s = {
    MIN_LENGTH : '5 or more characters',
    VALID_PASS : 'looks good',
    PASS_SAME  : 'username = password'
  };

  this.init();
};

Snap.PasswordCheck.prototype = {

  init          : function() {
    var obj = this;

    this._input.keyup(function() {
      obj._checkInput.bind(obj)();
    });

    if( this._input.val() != '' ) {
      this._checkInput({now: true});
      show(this._info.parent());
    } else {
      this._info.text(this._s.MIN_LENGTH);
    }
  },

  _checkInput       : function(options) {
    var pass = $(this._input).val();
    if( this._last == pass ) {
      return;
    }
    this._last = pass;

    if( this._timeout ) {
      clearTimeout(this._timeout);
      this._timeout = null;
    }

    if( pass == '' ) {
      this._info
      .text(this._s.MIN_LENGTH)
      .parent()
        .attr('style', 'display:block')
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
    var pass = this._input.val();
    var error = null;
    if( pass.length < 5 ) {
      error = this._s.MIN_LENGTH;
    } else if( this._username && this._username.isValid() && this._username.getName() == pass ) {
      error = this._s.PASS_SAME;
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
      .text(this._s.VALID_PASS)
      .parent()
        .animate({
          backgroundColor: Snap.ColourPalette.Success
        }, 500);
    }
  }

};