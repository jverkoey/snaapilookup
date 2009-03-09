/**
 * Login actions.
 */

$(function(){

  var undefined;

  // Setup the OpenID services.
  // Clicking an OpenID service should set the URL field with its domain.
  var login = '#auth .login';
  var openid_services = {
    google:       { url: 'http://www.google.com/accounts/o8/id' },
    myopenid:     { url: 'http://username.myopenid.com/' },
    flickr:       { url: 'http://flickr.com/' },
    yahoo:        { url: 'http://me.yahoo.com' },
    aol:          { url: 'http://openid.aol.com/username' },
    blogger:      { url: 'http://username.blogspot.com/' },
    livejournal:  { url: 'http://username.livejournal.com/' },
    verisign:     { url: 'http://username.pip.verisignlabs.com/' },
    myvidoop:     { url: 'http://username.myvidoop.com/' },
    claimid:      { url: 'http://claimid.com/username' },
    technorati:   { url: 'http://technorati.com/people/technorati/username' },
    vox:          { url: 'http://username.vox.com/' }
  };

  var activeService;

  $(login + ' .form form')
    .submit(function() {
      return $(login + ' .loginform .text').val() != '';
    });

  $(login + ' .loginform .text')
    .focus(function() {
      $(login + ' .services .list').show();
    });

  $(login + ' .loginform .button')
    .focus(function() {
      $(login + ' .services .list').hide();
    });

  $(login + ' .userloginform .button')
    .focus(function() {
      $(login + ' .services .list').hide();
    });

  $(login + ' .userloginform .text')
    .focus(function() {
      $(login + ' .services .list').hide();
    });

  $(login + ' .services img:first')
    .click(function() {
      $(login + ' .services .list').toggle();
    })
    .hover(function() {
      $(login + ' .services .list').show();
    });

  // Allow the user to select each service if it's a valid service.
  $(login + ' .services .list .service').each(function(index) {
    var service = openid_services[$(this).text().toLowerCase().replace('!', '')];
    if( service === undefined ) {
      // Bail out!
      return;
    }
    $(this).click(function() {
      if( service.url.indexOf('username') >= 0 ) {
        // Set the service name.
        $(login + ' .services .list .bottombar .servicename').text($(this).text());
        // Slide down the bottom bar and focus it.
        var input = $(login + ' .services .list .bottombar .text')
        input.val('');
        $(login + ' .services .list .bottombar').slideDown('fast', function() {
          input.focus();
        });
      } else {
        $(login + ' .services .list .bottombar').slideUp('fast');
        $(login + ' .loginform .text').focus();
      }
      $(login + ' .services .list .service').each(function() {
        $(this).removeClass('selected');
      });
      $(this).addClass('selected');
      $(login + ' .loginform .text').val(service.url);
      activeService = service;
    });

    $(login + ' .services .list .bottombar .text').keyup(function() {
      var newUrl = $(this).val() != '' ? activeService.url.replace('username', $(this).val()) :
                                         activeService.url;
      $(login + ' .loginform .text').val(newUrl);
    });

  });

});
