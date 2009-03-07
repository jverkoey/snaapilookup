/**
 * Asynchronous username checker.
 *
 * @require-package core
 */

Snap.UsernameCheck = function (input, info) {
  this._input   = input;
  this._info    = info;
  this._last    = null;
  this._timeout = null;
  this._isvalid = false;

  this._s = {
    DEFAULT         : 'pick your nickname',
    UNIQUE_NICKNAME : 'nice, it\'s unique',
    NICKNAME_DUPE   : 'that name\'s taken',
    CHECKING        : 'Checking availability...'
  };

  this.init();
};

Snap.UsernameCheck.prototype = {

  isValid           : function() {
    return this._isvalid;
  },

  getName           : function() {
    return this._input.val();
  },

  init              : function() {
    var obj = this;

    this._input.keyup(function() {
      obj._checkInput.bind(obj)();
    });
    
    this._info.text(this._s.DEFAULT);
    if( this._input.val() != '' ) {
      this._checkInput({now: true});
      show(this._info.parent());
    }
  },

  _checkInput       : function(options) {
    var name = $.trim($(this._input).val());
    if( this._last == name ) {
      return;
    }
    this._last = name;

    if( this._timeout ) {
      clearTimeout(this._timeout);
      this._timeout = null;
    }

    if( name == '' ) {
      this._info
        .text(this._s.DEFAULT)
        .parent()
          .css({
            backgroundColor: Snap.ColourPalette.White
          });

      this._isvalid = false;
    } else {
      if( options && options.now ) {
        this.checkAvailability();
      } else {
        this._timeout = setTimeout(this.checkAvailability.bind(this), 500);
      }
    }
  },

  checkAvailability : function() {
    var obj = this;
    var delayText = function() {
      this._info.text(this._s.CHECKING);
    }

    var timeout = setTimeout(delayText.bind(this), 300);

    var success = function(result, textStatus) {
      clearTimeout(timeout);
      if( result.nickname == this.nickname ) {
        if( !result.exists ) {
          obj._info
          .text(obj._s.UNIQUE_NICKNAME)
          .parent()
            .animate({
              backgroundColor: Snap.ColourPalette.Success
            }, 500);
          obj._isvalid = true;
        } else {
          obj._info
          .text(obj._s.NICKNAME_DUPE)
          .parent()
            .animate({
              backgroundColor: Snap.ColourPalette.Error
            }, 500);
          obj._isvalid = false;
        }
      }
    }

    var nickname = this._input.val();
    $.ajax({
      type    : 'GET',
      url     : '/user/exists',
      dataType: 'json',
      nickname: nickname,
      data    : {nickname:nickname},
      success : success
    });
  }

};