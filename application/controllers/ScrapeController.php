<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

class ScrapeController extends SnaapiController {

  private $_pages_scraped;
  const MAX_PAGES_TO_SCRAPE = 1;

  public function init() {
    SnaapiController::init();

    if( 'development' == $this->getInvokeArg('env') ) {
      $this->_helper->viewRenderer->setRender('index');
    }
  }

  public function phpAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      /*$model = $this->getFunctionsModel();
      $db = $model->getTable()->getAdapter();
      $sql = "SELECT *  FROM `functions` WHERE `data` LIKE '% ,%'";
      foreach( $db->query($sql)->fetchAll() as $result ) {
        $result['data'] = str_replace(" ,", ',', $result['data']);

        $this->getFunctionsModel()->setData(array(
          'category' => $result['category'],
          'id' => $result['id'],
          'data' => $result['data']
        ));
      }*/

      $this->scrapePHPHierarchies();
      $this->scrapePHPFunctions();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function pythonAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapePythonConstants();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function cssAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeCSSFunctions();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function zendAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeZend();
    } else {
      $this->_forward('error', 'error');
    }
  }

  public function fbAction() {
    if( 'development' == $this->getInvokeArg('env') ) {
      $this->view->results = '';
      $this->_pages_scraped = 0;

      $this->scrapeFacebook();
    } else {
      $this->_forward('error', 'error');
    }
  }

  private function scrapeFacebook() {
    $category = 'Facebook API';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $functions = array(
      array("http://wiki.developers.facebook.com/index.php/Admin.getRestrictionInfo", "admin.getRestrictionInfo",
        "Returns the demographic restrictions for the application."),
      array("http://wiki.developers.facebook.com/index.php/Admin.setAppProperties", "admin.setAppProperties",
        "Sets values for properties for your applications in the Facebook Developer application."),
      array("http://wiki.developers.facebook.com/index.php/Admin.setRestrictionInfo", "admin.setRestrictionInfo",
        "Sets the demographic restrictions for the application."),
      array("http://wiki.developers.facebook.com/index.php/Application.getPublicInfo", "application.getPublicInfo",
        "Returns public information about a given application (not necessarily your own)."),
      array("http://wiki.developers.facebook.com/index.php/Auth.createToken", "auth.createToken", 
        "Creates an auth_token to be passed in as a parameter to login.php and then to auth.getSession after the user has logged in."),
      array("http://wiki.developers.facebook.com/index.php/Auth.expireSession", "auth.expireSession", 
        "Expires the session indicated in the API call, for your application."),
      array("http://wiki.developers.facebook.com/index.php/Auth.getSession", "auth.getSession", 
        "Returns the session key bound to an auth_token, as returned by auth.createToken or in the callback URL."),
      array("http://wiki.developers.facebook.com/index.php/Auth.promoteSession", "auth.promoteSession", 
        "Returns a temporary session secret associated to the current existing session, for use in a client-side component to an application."),
      array("http://wiki.developers.facebook.com/index.php/Auth.revokeAuthorization", "auth.revokeAuthorization",
        "If this method is called for the logged in user, then no further API calls can be made on that user's behalf until the user decides to authorize the application again."),
      array("http://wiki.developers.facebook.com/index.php/Auth.revokeExtendedPermission", "auth.revokeExtendedPermission",
        "Removes a specific extended permission that a user explicitly granted to your application."),
      array("http://wiki.developers.facebook.com/index.php/Batch.run", "batch.run",
        "Execute a list of individual API calls in a single batch."),
      array("http://wiki.developers.facebook.com/index.php/Comments.get", "comments.get",
      	"Returns all comments for a given xid posted through fb:comments. This method is a wrapper for the FQL query on the comment FQL table."),
      array("http://wiki.developers.facebook.com/index.php/Data.getCookies", "data.getCookies",
        "Returns all cookies for a given user and application."),
      array("http://wiki.developers.facebook.com/index.php/Data.setCookie", "data.setCookie",
        "Sets a cookie for a given user and application."),
      array("http://wiki.developers.facebook.com/index.php/Events.cancel", "events.cancel", 
        "Cancels an event. The application must be an admin of the event."),
      array("http://wiki.developers.facebook.com/index.php/Events.create", "events.create", 
        "Creates an event on behalf of the user if the application has an active session; otherwise it creates an event on behalf of the application."),
      array("http://wiki.developers.facebook.com/index.php/Events.edit", "events.edit", 
        "Edits an existing event. The application must be an admin of the event."),
      array("http://wiki.developers.facebook.com/index.php/Events.get", "events.get", 
        "Returns all visible events according to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Events.getMembers", "events.getMembers", 
        "Returns membership list data associated with an event."),
      array("http://wiki.developers.facebook.com/index.php/Events.rsvp", "events.rsvp", 
        "Sets the attendance option for the current user."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.deleteCustomTags", "fbml.deleteCustomTags",
        "Deletes one or more custom tags you previously registered for the calling application with fbml.registerCustomTags"),
      array("http://wiki.developers.facebook.com/index.php/Fbml.getCustomTags", "fbml.getCustomTags",
        "Returns the custom tag definitions for tags that were previously defined using fbml.registerCustomTags"),
      array("http://wiki.developers.facebook.com/index.php/Fbml.refreshImgSrc", "fbml.refreshImgSrc", 
        "Fetches and re-caches the image stored at the given URL."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.refreshRefUrl", "fbml.refreshRefUrl", 
        "Fetches and re-caches the content stored at the given URL."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.registerCustomTags", "fbml.registerCustomTags",
        "Registers custom tags you can include in your that applications' FBML markup. Custom tags consist of FBML snippets that are rendered during parse time on the containing page that references the custom tag."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.setRefHandle", "fbml.setRefHandle", 
        "Associates a given \"handle\" with FBML markup so that the handle can be used within the fb:ref FBML tag."),
      array("http://wiki.developers.facebook.com/index.php/Fbml.uploadNativeStrings", "fbml.uploadNativeStrings",
        "Lets you insert text strings into the Facebook Translations database so they can be translated."),
      array("http://wiki.developers.facebook.com/index.php/Feed.deactivateTemplateBundleByID", "feed.deactivateTemplateBundleByID",
        "Deactivates a previously registered template bundle."),
      array("http://wiki.developers.facebook.com/index.php/Feed.getRegisteredTemplateBundleByID", "feed.getRegisteredTemplateBundleByID",
        "Retrieves information about a specified template bundle previously registered by the requesting application."),
      array("http://wiki.developers.facebook.com/index.php/Feed.getRegisteredTemplateBundles", "feed.getRegisteredTemplateBundles",
        "Retrieves the full list of all the template bundles registered by the requesting application."),
      array("", "feed.publishActionOfUser",
        "This method is deprecated. Please use feed.publishUserAction instead."),
      array("", "feed.publishStoryToUser",
        "This method is deprecated. Please use feed.publishUserAction instead."),
      array("http://wiki.developers.facebook.com/index.php/Feed.publishTemplatizedAction", "feed.publishTemplatizedAction", 
        "Publishes a Mini-Feed story to the Facebook Page corresponding to the page_actor_id parameter. Note: This method is deprecated for actions taken by users only; it still works for actions taken by Facebook Pages."),
      array("http://wiki.developers.facebook.com/index.php/Feed.publishUserAction", "feed.publishUserAction",
        "Publishes a story on behalf of the user owning the session, using the specified template bundle."),
      array("http://wiki.developers.facebook.com/index.php/Feed.registerTemplateBundle", "feed.registerTemplateBundle",
        "Builds a template bundle around the specified templates, registers them on Facebook, and responds with a template bundle ID that can be used to identify your template bundle to other Feed-related API calls."),
      array("http://wiki.developers.facebook.com/index.php/Fql.query", "fql.query", 
        "Evaluates an FQL (Facebook Query Language) query."),
      array("http://wiki.developers.facebook.com/index.php/Friends.areFriends", "friends.areFriends", 
        "Returns whether or not each pair of specified users is friends with each other."),
      array("http://wiki.developers.facebook.com/index.php/Friends.get", "friends.get", 
        "Returns the identifiers for the current user's Facebook friends."),
      array("http://wiki.developers.facebook.com/index.php/Friends.getAppUsers", "friends.getAppUsers", 
        "Returns the identifiers for the current user's Facebook friends who have authorized the specific calling application."),
      array("http://wiki.developers.facebook.com/index.php/Friends.getLists", "friends.getLists", 
        "Returns the identifiers for the current user's Facebook friend lists."),
      array("http://wiki.developers.facebook.com/index.php/Groups.get", "groups.get", 
        "Returns all visible groups according to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Groups.getMembers", "groups.getMembers", 
        "Returns membership list data associated with a group."),
      array("http://wiki.developers.facebook.com/index.php/Links.get", "links.get",
        "Returns all links the user has posted on their profile through your application."),
      array("http://wiki.developers.facebook.com/index.php/Links.post", "links.post",
        "Lets a user post a link on their Wall through your application."),
      array("http://wiki.developers.facebook.com/index.php/LiveMessage.send", "liveMessage.send",
        "Sends a \"message\" directly to a user's browser, which can be handled in FBJS."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.createListing", "marketplace.createListing", 
        "Create or modify a listing in Marketplace."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.getCategories", "marketplace.getCategories", 
        "Returns all the Marketplace categories."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.getListings", "marketplace.getListings", 
        "Return all Marketplace listings either by listing ID or by user."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.getSubCategories", "marketplace.getSubCategories", 
        "Returns the Marketplace subcategories for a particular category."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.removeListing", "marketplace.removeListing", 
        "Remove a listing from Marketplace."),
      array("http://wiki.developers.facebook.com/index.php/Marketplace.search", "marketplace.search", 
        "Search Marketplace for listings filtering by category, subcategory and a query string."),
      array("http://wiki.developers.facebook.com/index.php/Notes.create", "notes.create",
        "Lets a user write a Facebook note through your application."),
      array("http://wiki.developers.facebook.com/index.php/Notes.delete", "notes.delete",
        "Lets a user delete a Facebook note that was written through your application."),
      array("http://wiki.developers.facebook.com/index.php/Notes.edit", "notes.edit",
        "Lets a user edit a Facebook note through your application."),
      array("http://wiki.developers.facebook.com/index.php/Notes.get", "notes.get",
        "Returns a list of all of the visible notes written by the specified user."),
      array("http://wiki.developers.facebook.com/index.php/Notifications.get", "notifications.get", 
        "Returns information on outstanding Facebook notifications for current session user."),
      array("http://wiki.developers.facebook.com/index.php/Notifications.send", "notifications.send", 
        "Sends a notification to a set of users."),
      array("http://wiki.developers.facebook.com/index.php/Notifications.sendEmail", "notifications.sendEmail", 
        "Sends an email to the specified users who have the application."),
      array("http://wiki.developers.facebook.com/index.php/Pages.getInfo", "pages.getInfo", 
        "Returns all visible pages to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Pages.isAdmin", "pages.isAdmin", 
        "Checks whether the logged-in user is the admin for a given Page."),
      array("http://wiki.developers.facebook.com/index.php/Pages.isAppAdded", "pages.isAppAdded", 
        "Checks whether the Page has added the application."),
      array("http://wiki.developers.facebook.com/index.php/Pages.isFan", "pages.isFan", 
        "Checks whether a user is a fan of a given Page."),
      array("http://wiki.developers.facebook.com/index.php/Photos.addTag", "photos.addTag", 
        "Adds a tag with the given information to a photo."),
      array("http://wiki.developers.facebook.com/index.php/Photos.createAlbum", "photos.createAlbum", 
        "Creates and returns a new album owned by the current session user."),
      array("http://wiki.developers.facebook.com/index.php/Photos.get", "photos.get", 
        "Returns all visible photos according to the filters specified."),
      array("http://wiki.developers.facebook.com/index.php/Photos.getAlbums", "photos.getAlbums", 
        "Returns metadata about all of the photo albums uploaded by the specified user."),
      array("http://wiki.developers.facebook.com/index.php/Photos.getTags", "photos.getTags", 
        "Returns the set of user tags on all photos specified."),
      array("http://wiki.developers.facebook.com/index.php/Photos.upload", "photos.upload", 
        "Uploads a photo owned by the current session user and returns the new photo."),
      array("http://wiki.developers.facebook.com/index.php/Profile.getFBML", "profile.getFBML", 
        "Gets the FBML that is currently set for a user's profile."),
      array("http://wiki.developers.facebook.com/index.php/Profile.getInfo", "profile.getInfo",
        "Returns the specified user's application info section for the calling application."),
      array("http://wiki.developers.facebook.com/index.php/Profile.getInfoOptions", "profile.getInfoOptions",
        "Returns the options associated with the specified field for an application info section."),
      array("http://wiki.developers.facebook.com/index.php/Profile.setFBML", "profile.setFBML", 
        "Sets the FBML for a user's profile, including the content for both the profile box and the profile actions."),
      array("http://wiki.developers.facebook.com/index.php/Profile.setInfo", "profile.setInfo",
        "Configures an application info section that the specified user can install on the Info tab of her profile."),
      array("http://wiki.developers.facebook.com/index.php/Profile.setInfoOptions", "profile.setInfoOptions",
        "Specifies the objects for a field for an application info section."),
      array("http://wiki.developers.facebook.com/index.php/Status.get", "status.get",
        "Returns the user's current and most recent statuses. This is a streamlined version of users.setStatus."),
      array("http://wiki.developers.facebook.com/index.php/Status.set", "status.set",
        "Updates a user's Facebook status through your application."),
      array("http://wiki.developers.facebook.com/index.php/Users.getInfo", "users.getInfo", 
        "Returns a wide array of user-specific information for each user identifier passed, limited by the view of the current user."),
      array("http://wiki.developers.facebook.com/index.php/Users.getLoggedInUser", "users.getLoggedInUser", 
        "Gets the user ID (uid) associated with the current session."),
      array("", "users.getStandardInfo",
        "Returns an array of user-specific information for use by the application itself."),
      array("http://wiki.developers.facebook.com/index.php/Users.hasAppPermission", "users.hasAppPermission", 
        "Checks whether the user has opted in to an extended application permission."),
      array("", "users.isAppAdded",
        "This method is deprecated. Please use users.isAppUser instead."),
      array("http://wiki.developers.facebook.com/index.php/Users.isAppUser", "users.isAppUser",
        "Returns whether the user (either the session user or user specified by UID) has authorized the calling application."),
      array("http://wiki.developers.facebook.com/index.php/Users.isVerified", "users.isVerified",
        "Returns whether the user is a verified Facebook user."),
      array("http://wiki.developers.facebook.com/index.php/Users.setStatus", "users.setStatus", 
        "Updates a user's Facebook status."),
      array("http://wiki.developers.facebook.com/index.php/Video.getUploadLimits", "video.getUploadLimits",
        "Returns the file size and length limits for a video that the current user can upload through your application."),
      array("http://wiki.developers.facebook.com/index.php/Video.upload", "video.upload",
        "Uploads a video owned by the current session user and returns the video.")
    );

    for( $index = 0; $index < count($functions); ++$index ) {
      $functions[$index][1][0] = strtoupper($functions[$index][1][0]);
      $this->getFunctionsModel()->insertOrUpdateFunction(array(
        'category' => $category_id,
        'hierarchy' => 2,
        'name' => $functions[$index][1],
        'url' => $functions[$index][0],
        'short_description' => $functions[$index][2]
      ));
    }
  }

  private function scrapeZend() {
    $category = 'Zend';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $categories = array(
      "Zend_Acl",
      "Zend_Amf",
      "Zend_Auth",
      "Zend_Cache",
      "Zend_Captcha",
      "Zend_Config",
      "Zend_Config_Writer",
      "Zend_Console_Getopt",
      "Zend_Controller",
      "Zend_Currency",
      "Zend_Date",
      "Zend_Db",
      "Zend_Debug",
      "Zend_Dojo",
      "Zend_Dom",
      "Zend_Exception",
      "Zend_Feed",
      "Zend_File",
      "Zend_Filter",
      "Zend_Filter_Input",
      "Zend_Form",
      "Zend_Gdata",
      "Zend_Http",
      "Zend_Infocard",
      "Zend_Json",
      "Zend_Layout",
      "Zend_Ldap",
      "Zend_Loader",
      "Zend_Locale",
      "Zend_Log",
      "Zend_Mail",
      "Zend_Measure",
      "Zend_Memory",
      "Zend_Mime",
      "Zend_OpenId",
      "Zend_Paginator",
      "Zend_Pdf",
      "Zend_ProgressBar",
      "Zend_Registry",
      "Zend_Rest",
      "Zend_Search_Lucene",
      "Zend_Server_Reflection",
      "Zend_Service_Akismet",
      "Zend_Service_Amazon",
      "Zend_Service_Audioscrobbler",
      "Zend_Service_Delicious",
      "Zend_Service_Flickr",
      "Zend_Service_Nirvanix",
      "Zend_Service_ReCaptcha",
      "Zend_Service_Simpy",
      "Zend_Service_SlideShare",
      "Zend_Service_StrikeIron",
      "Zend_Service_Technorati",
      "Zend_Service_Twitter",
      "Zend_Service_Yahoo",
      "Zend_Session",
      "Zend_Soap",
      "Zend_Test",
      "Zend_Text",
      "Zend_Timesync",
      "Zend_Translate",
      "Zend_Uri",
      "Zend_Validate",
      "Zend_Version",
      "Zend_View",
      "Zend_Wildfire",
      "Zend_XmlRpc",
      "ZendX_Console_Process_Unix",
      "ZendX_JQuery"
    );

    $urls = array(
      "http://framework.zend.com/manual/en/zend.acl.html",
      "http://framework.zend.com/manual/en/zend.amf.html",
      "http://framework.zend.com/manual/en/zend.auth.html",
      "http://framework.zend.com/manual/en/zend.cache.html",
      "http://framework.zend.com/manual/en/zend.captcha.html",
      "http://framework.zend.com/manual/en/zend.config.html",
      "http://framework.zend.com/manual/en/zend.config.writer.html",
      "http://framework.zend.com/manual/en/zend.console.getopt.html",
      "http://framework.zend.com/manual/en/zend.controller.html",
      "http://framework.zend.com/manual/en/zend.currency.html",
      "http://framework.zend.com/manual/en/zend.date.html",
      "http://framework.zend.com/manual/en/zend.db.html",
      "http://framework.zend.com/manual/en/zend.debug.html",
      "http://framework.zend.com/manual/en/zend.dojo.html",
      "http://framework.zend.com/manual/en/zend.dom.html",
      "http://framework.zend.com/manual/en/zend.exception.html",
      "http://framework.zend.com/manual/en/zend.feed.html",
      "http://framework.zend.com/manual/en/zend.file.html",
      "http://framework.zend.com/manual/en/zend.filter.html",
      "http://framework.zend.com/manual/en/zend.filter.input.html",
      "http://framework.zend.com/manual/en/zend.form.html",
      "http://framework.zend.com/manual/en/zend.gdata.html",
      "http://framework.zend.com/manual/en/zend.http.html",
      "http://framework.zend.com/manual/en/zend.infocard.html",
      "http://framework.zend.com/manual/en/zend.json.html",
      "http://framework.zend.com/manual/en/zend.layout.html",
      "http://framework.zend.com/manual/en/zend.ldap.html",
      "http://framework.zend.com/manual/en/zend.loader.html",
      "http://framework.zend.com/manual/en/zend.locale.html",
      "http://framework.zend.com/manual/en/zend.log.html",
      "http://framework.zend.com/manual/en/zend.mail.html",
      "http://framework.zend.com/manual/en/zend.measure.html",
      "http://framework.zend.com/manual/en/zend.memory.html",
      "http://framework.zend.com/manual/en/zend.mime.html",
      "http://framework.zend.com/manual/en/zend.openid.html",
      "http://framework.zend.com/manual/en/zend.paginator.html",
      "http://framework.zend.com/manual/en/zend.pdf.html",
      "http://framework.zend.com/manual/en/zend.progressbar.html",
      "http://framework.zend.com/manual/en/zend.registry.html",
      "http://framework.zend.com/manual/en/zend.rest.html",
      "http://framework.zend.com/manual/en/zend.search.lucene.html",
      "http://framework.zend.com/manual/en/zend.server.reflection.html",
      "http://framework.zend.com/manual/en/zend.service.akismet.html",
      "http://framework.zend.com/manual/en/zend.service.amazon.html",
      "http://framework.zend.com/manual/en/zend.service.audioscrobbler.html",
      "http://framework.zend.com/manual/en/zend.service.delicious.html",
      "http://framework.zend.com/manual/en/zend.service.flickr.html",
      "http://framework.zend.com/manual/en/zend.service.nirvanix.html",
      "http://framework.zend.com/manual/en/zend.service.recaptcha.html",
      "http://framework.zend.com/manual/en/zend.service.simpy.html",
      "http://framework.zend.com/manual/en/zend.service.slideshare.html",
      "http://framework.zend.com/manual/en/zend.service.strikeiron.html",
      "http://framework.zend.com/manual/en/zend.service.technorati.html",
      "http://framework.zend.com/manual/en/zend.service.twitter.html",
      "http://framework.zend.com/manual/en/zend.service.yahoo.html",
      "http://framework.zend.com/manual/en/zend.session.html",
      "http://framework.zend.com/manual/en/zend.soap.html",
      "http://framework.zend.com/manual/en/zend.test.html",
      "http://framework.zend.com/manual/en/zend.text.html",
      "http://framework.zend.com/manual/en/zend.timesync.html",
      "http://framework.zend.com/manual/en/zend.translate.html",
      "http://framework.zend.com/manual/en/zend.uri.html",
      "http://framework.zend.com/manual/en/zend.validate.html",
      "http://framework.zend.com/manual/en/zend.version.html",
      "http://framework.zend.com/manual/en/zend.view.html",
      "http://framework.zend.com/manual/en/zend.wildfire.html",
      "http://framework.zend.com/manual/en/zend.xmlrpc.html",
      "http://framework.zend.com/manual/en/zendx.console.process.unix.html",
      "http://framework.zend.com/manual/en/zendx.jquery.html"
    );

    for( $index = 0; $index < count($categories); ++$index ) {
      $this->view->results .= $this->getHierarchiesModel()->insert($category_id, 1, $categories[$index], $urls[$index])."\n";
    }
  }

  private function scrapeCSSFunctions() {
    $category = 'CSS';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $scrapeable = $this->getFunctionsModel()->fetchAllScrapeable($category_id);

    if( empty($scrapeable) ) {
      $this->nothing_to_scrape($category);
      return;
    }

    foreach( $scrapeable as $function ) {
      $this->_pages_scraped++;

      if( $this->_pages_scraped % 100 == 0 ) {
        sleep(1);
      }

      if( $this->_pages_scraped > ScrapeController::MAX_PAGES_TO_SCRAPE ) {
        $this->view->results .= 'Hit max page count for scraping.' . "\n";
        return;
      }

      $this->view->results .= $function['name'] . "\n";
      if( !$function['url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $function['url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      $start_index = strpos($contents, '<h2>Possible Values</h2>');
      if( $start_index === FALSE ) {
        $this->view->results .= 'We didn\'t find possible values, skipping...' . "\n";
        continue;
      }
      $start_index += strlen('<h2>Possible Values</h2>');
      $end_index = strpos($contents, '</table>', $start_index);
      if( $end_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the end of the possible values, skipping...' . "\n";
        continue;
      }

      $data = substr($contents, $start, $end-$start);
      $this->view->results .= $data."\n";
/*
      $this->getFunctionsModel()->setData(array(
        'category' => $category_id,
        'id' => $function['id'],
        'data' => $line
      ));*/
    }
  }

  private function scrapePythonConstants() {
    $category = 'Python';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $scrapeable = $this->getHierarchiesModel()->fetchAllScrapeable($category_id);

    if( empty($scrapeable) ) {
      $this->nothing_to_scrape($category);
      return;
    }

    foreach( $scrapeable as $hierarchy ) {
      $this->_pages_scraped++;

      if( $this->_pages_scraped > ScrapeController::MAX_PAGES_TO_SCRAPE ) {
        $this->view->results .= 'Hit max page count for scraping.' . "\n";
        return;
      }

      $this->view->results .= $hierarchy['name'] . "\n";
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      if( strpos($source_url, '#') !== FALSE ) {
        // Cool, let's grab this section's info.
        $parts = explode('#', $source_url);
        $base_url = $parts[0];
        $block = $parts[1];
        $contents = file_get_contents($source_url);

        $start_block = strpos($contents, 'id="'.$block.'"');
        if( $start_block === FALSE ) {
          $this->view->results .= 'We couldn\'t find the block, skipping...' . "\n";
          continue;
        }

        $done = false;

        $start = strpos($contents, '<dl class="data"', $start_block);
        if( $start !== FALSE ) {
          $end = strpos($contents, '</div>', $start);
          if( $end === FALSE ) {
            $this->view->results .= 'We couldn\'t find the end of the block, skipping...' . "\n";
            continue;
          }

          $push_back = $start;
          while( ($push_back = strpos($contents, '<div', $push_back)) !== FALSE ) {
            if( $push_back > $end ) {
              break;
            }
            $push_back++;
            $end = strpos($contents, '</div>', $end + 1);
          }

          $fail = false;
          $block_data = explode('<dl class="data">', substr($contents, $start, $end-$start));
          foreach( $block_data as $entry ) {
            $entry_data = explode("\n", $entry);
            if( count($entry_data) > 1 ) {
              $name = null;
              $href = null;
              $description = null;
              if( preg_match('/<dt id="(.+?)">/', $entry_data[1], $id) ) {
                $name = $id[1];
              }
              if( preg_match('/href="(#.+?)"/', $entry_data[2], $id) ) {
                $href = $id[1];
              }
              if( preg_match('/<dd>(.+?)<\/dd>/', str_replace("\n", ' ', $entry), $id) ) {
                $description = substr($id[1], 0, strpos($id[1], '</p>'));
                $description = preg_replace('/<.+?>/', '', $description);
              }

              if( !$name || !$href ) {
                $fail = true;
                break;
              }
              $this->getFunctionsModel()->insertOrUpdateFunction(array(
                'category' => $category_id,
                'hierarchy' => $hierarchy['id'],
                'name' => $name,
                'url' => $base_url.$href,
                'short_description' => $description
              ));
              $done = true;
            }
          }
          if( $fail ) {
            continue;
          } else {  
            $done = true;
          }
        }

        if( !$done ) {
          $start = strpos($contents, '<dl class="method"', $start_block);
          if( $start !== FALSE ) {
            $end = strpos($contents, '</div>', $start);
            if( $end === FALSE ) {
              $this->view->results .= 'We couldn\'t find the end of the block, skipping...' . "\n";
              continue;
            }

            $push_back = $start;
            while( ($push_back = strpos($contents, '<div', $push_back)) !== FALSE ) {
              if( $push_back > $end ) {
                break;
              }
              $push_back++;
              $end = strpos($contents, '</div>', $end + 1);
            }

            $fail = false;
            $block_data = explode('<dl class="method">', substr($contents, $start, $end-$start));
            foreach( $block_data as $entry ) {
              $entry_data = explode("\n", $entry);
              if( count($entry_data) > 1 ) {
                $name = null;
                $href = null;
                $description = null;
                if( preg_match('/<dt id="(.+?)">/', $entry_data[1], $id) ) {
                  $name = $id[1];
                }
                if( preg_match('/href="(#.+?)"/', $entry_data[2], $id) ) {
                  $href = $id[1];
                }
                if( preg_match('/<dd>(.+?)<\/dd>/', str_replace("\n", ' ', $entry), $id) ) {
                  $description = substr($id[1], 0, strpos($id[1], '</p>'));
                  $description = preg_replace('/<.+?>/', '', $description);
                }

                $this->view->results .= $name."\n";
                $this->view->results .= $href."\n";
                $this->view->results .= $description."\n\n";

                if( !$name || !$href ) {
                  $fail = true;
                  break;
                }
                $this->getFunctionsModel()->insertOrUpdateFunction(array(
                  'category' => $category_id,
                  'hierarchy' => $hierarchy['id'],
                  'name' => $name,
                  'url' => $base_url.$href,
                  'short_description' => $description
                ));
              }
            }
            if( $fail ) {
              continue;
            } else {
              $done = true;
            }
          }
        }

        if( !$done ) {
          $start = strpos($contents, '<dl class="function"', $start_block);
          if( $start === FALSE ) {
            $this->view->results .= 'We couldn\'t find the starting entry, skipping...' . "\n";
            continue;
          }

          $end = strpos($contents, '</div>', $start);
          if( $end === FALSE ) {
            $this->view->results .= 'We couldn\'t find the end of the block, skipping...' . "\n";
            continue;
          }

          $push_back = $start;
          while( ($push_back = strpos($contents, '<div', $push_back)) !== FALSE ) {
            if( $push_back > $end ) {
              break;
            }
            $push_back++;
            $end = strpos($contents, '</div>', $end + 1);
          }

          $fail = false;
          $block_data = explode('<dl class="function">', substr($contents, $start, $end-$start));
          foreach( $block_data as $entry ) {
            $entry_data = explode("\n", $entry);
            if( count($entry_data) > 1 ) {
              $name = null;
              $href = null;
              $description = null;
              if( preg_match('/<dt id="(.+?)">/', $entry_data[1], $id) ) {
                $name = $id[1];
              }
              if( preg_match('/href="(#.+?)"/', $entry_data[2], $id) ) {
                $href = $id[1];
              }
              if( preg_match('/<dd>(.+?)<\/dd>/', str_replace("\n", ' ', $entry), $id) ) {
                $description = substr($id[1], 0, strpos($id[1], '</p>'));
                $description = preg_replace('/<.+?>/', '', $description);
              }

              if( !$name || !$href ) {
                $fail = true;
                break;
              }
              $this->getFunctionsModel()->insertOrUpdateFunction(array(
                'category' => $category_id,
                'hierarchy' => $hierarchy['id'],
                'name' => $name,
                'url' => $base_url.$href,
                'short_description' => $description
              ));
            }
          }
          if( $fail ) {
            $this->view->results .= 'We failed, skipping...' . "\n";
            continue;
          } else {
            $done = true;
          }
        }

        if( !$done ) {
          $this->view->results .= 'We couldn\'t find the start of the block, skipping...' . "\n";
          continue;
        }

      }

      $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
    }
  }

  private function scrapePHPHierarchies() {
    $category = 'PHP';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $scrapeable = $this->getHierarchiesModel()->fetchAllScrapeable($category_id);

    if( empty($scrapeable) ) {
      $this->nothing_to_scrape($category);
      return;
    }

    foreach( $scrapeable as $hierarchy ) {
      $this->_pages_scraped++;

      if( $this->_pages_scraped > ScrapeController::MAX_PAGES_TO_SCRAPE ) {
        $this->view->results .= 'Hit max page count for scraping.' . "\n";
        return;
      }

      $this->view->results .= $hierarchy['name'] . "\n";
      if( !$hierarchy['source_url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $hierarchy['source_url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);
      
      $start_index = strpos($contents, '<h2>Table of Contents</h2>');
      if( $start_index === FALSE ) {
        $this->view->results .= 'We didn\'t find a Table of Contents, skipping...' . "\n";
        continue;
      }
      $end_index = strpos($contents, '</ul>', $start_index);
      if( $end_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the end of the list, skipping...' . "\n";
        continue;
      }

      $line = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));
      if( !preg_match_all('/<li><a href="([a-zA-Z0-9_\-.]+)">([a-zA-Z0-9_:.\->]+)<\/a> — ([a-zA-Z0-9 \-_,.+;\[:\]<>=\/\'\(\)"#\\\\]+)<\/li>/', $line, $matches) ) {
        $this->view->results .= 'We coulnd\'t find any functions in this list, skipping...' . "\n";
        $this->view->results .= $line . "\n";
        $this->view->results .= $start_index.'-'.$end_index . "\n";
        continue;
      }

      $list_item_count = preg_match_all('/<li>/', $line, $nothing);
      if( $list_item_count != count($matches[1]) ) {
        $this->view->results .= 'We missed some items ('.($list_item_count - count($matches[1])).') in the list, skipping...' . "\n";
        $this->view->results .= print_r($matches[2], TRUE);
        continue;
      }

      $dirname = dirname($source_url).'/';
      for( $index = 0; $index < count($matches[1]); ++$index ) {
        $name = $matches[2][$index];
        $url = $dirname . $matches[1][$index];
        $description = $matches[3][$index];
        //$this->view->results .= $name.' - '.$description."\n";
        //$this->view->results .= '  <a href="'.$url.'">'.$url."</a>\n";

        $this->getFunctionsModel()->insertOrUpdateFunction(array(
          'category' => $category_id,
          'hierarchy' => $hierarchy['id'],
          'name' => $name,
          'url' => $url,
          'short_description' => $description
        ));
      }
      $this->getHierarchiesModel()->touch($category_id, $hierarchy['id']);
    }
  }

  private function scrapePHPFunctions() {
    $category = 'PHP';

    $category_id = $this->getCategoriesModel()->fetchCategoryByName($category);

    if( !$category_id ) {
      $this->invalid_category($category);
      return;
    }

    $scrapeable = $this->getFunctionsModel()->fetchAllScrapeable($category_id);

    if( empty($scrapeable) ) {
      $this->nothing_to_scrape($category);
      return;
    }

    foreach( $scrapeable as $function ) {
      $this->_pages_scraped++;

      if( $this->_pages_scraped % 100 == 0 ) {
        sleep(1);
      }

      if( $this->_pages_scraped > ScrapeController::MAX_PAGES_TO_SCRAPE ) {
        $this->view->results .= 'Hit max page count for scraping.' . "\n";
        return;
      }

      $this->view->results .= $function['name'] . "\n";
      if( !$function['url'] ) {
        $this->view->results .= 'No source URL specified, skipping...' . "\n";
        continue;
      }
      $source_url = $function['url'];
      $this->view->results .= '<a href="'.$source_url.'">'.$source_url."</a>\n";

      $contents = file_get_contents($source_url);

      if( strpos($contents, 'classsynopsis') !== FALSE ) {
        $this->view->results .= 'This is a class definition, skipping...' . "\n";
        continue;
      }

      if( strpos($contents, '<span class="simpara">') !== FALSE ) {
        $this->view->results .= 'This is not a function, skipping...' . "\n";
        continue;
      }

      $start_index = strpos($contents, '<h3 class="title">Description</h3>');
      if( $start_index === FALSE ) {
        $this->view->results .= 'We didn\'t find a Description, skipping...' . "\n";
        continue;
      }
      $start_index += strlen('<h3 class="title">Description</h3>');
      $end_index = strpos($contents, '</div>', $start_index);
      if( $end_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find the end of the description, skipping...' . "\n";
        continue;
      }

      if( strpos($contents, 'This function is an alias of:') !== FALSE ) {
        $this->view->results .= 'This function appears to be an alias, skipping...' . "\n";
        $this->getFunctionsModel()->touch($category_id, $function['id']);
        continue;
      }

      $start_index = strpos($contents, '<span', $start_index);
      if( $start_index === FALSE ) {
        $this->view->results .= 'We couldn\'t find any description tags, skipping...' . "\n";
        continue;
      }

      $line = str_replace("\n", '', substr($contents, $start_index, $end_index - $start_index));
      $line = str_replace("<span class=\"type.+?\">", '<st>', $line);
      $line = str_replace("<b>", '', $line);
      $line = str_replace("</b>", '', $line);
      $line = str_replace("<span class=\"modifier\">", '<st>', $line);
      $line = str_replace("<span class=\"methodname\">", '<sm>', $line);
      $line = str_replace("<span class=\"methodparam\">", '<smp>', $line);
      $line = str_replace("<span class=\"initializer\">", '<si>', $line);
      $line = preg_replace("/<tt.+?>/", '<sp>', $line);
      $line = str_replace("</tt>", '</s>', $line);
      $line = str_replace("</span>", '</s>', $line);
      $line = str_replace("</a>", '', $line);
      $line = preg_replace("/<a.+?>/", '', $line);
      $line = preg_replace('/( ){2,}/', ' ', $line);
      //$this->view->results .= $line."\n";
      if( strlen($line) < 5 ) {
        $this->view->results .= 'Line is unreasonably small, skipping...' . "\n";
        continue;
      }

      $this->getFunctionsModel()->setData(array(
        'category' => $category_id,
        'id' => $function['id'],
        'data' => $line
      ));
    }
  }

  private function invalid_category($name) {
    $this->view->results .= 'We can\'t find the category you requested: '.$name."\n";
  }

  private function nothing_to_scrape($name) {
    $this->view->results .= 'We can\'t find anything to scrape in the '.$name.' category.' . "\n";
  }

}
