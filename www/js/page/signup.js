/**
 * Signup actions.
 *
 * @requires fur-username-check fur-password-check fur-email-check
 */

$(function() {

  var signup = '#signup';

  var usernameCheck = new Snap.UsernameCheck(
    $(signup + ' .form .username input'),
    $(signup + ' .form .username .info-text')
  );

  new Snap.PasswordCheck(
    $(signup + ' .form .password input'),
    $(signup + ' .form .password .info-text'),
    usernameCheck
  );

  new Snap.EmailCheck(
    $(signup + ' .form .email input'),
    $(signup + ' .form .email .info-text')
  );

  $(signup + ' .form .text').each(function() {
    var text = $(this);
    var info = $(this).parent().parent().children('.col-info').children('.info');
    $(this)
      .focus(function() {
        info.addClass('focus').show();
      })
      .blur(function() {
        info.removeClass('focus');
        if( text.val() == '' ) {
          info.hide();
        }
      });
  });

});
