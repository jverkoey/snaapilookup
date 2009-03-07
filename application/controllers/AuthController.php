<?php

include_once APPLICATION_PATH . '/controllers/SnaapiController.php';

#
# RFC822 Email Parser
#
# By Cal Henderson <cal@iamcal.com>
# This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
# http://creativecommons.org/licenses/by-sa/2.5/
#
# $Revision: 1.1 $
#
function is_valid_email_address($email){

	$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

	$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

	$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
		'\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

	$quoted_pair = '\\x5c[\\x00-\\x7f]';

	$domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";

	$quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";

	$domain_ref = $atom;

	$sub_domain = "($domain_ref|$domain_literal)";

	$word = "($atom|$quoted_string)";

	$domain = "$sub_domain(\\x2e$sub_domain)*";

	$local_part = "$word(\\x2e$word)*";

	$addr_spec = "$local_part\\x40$domain";

	return preg_match("!^$addr_spec$!", $email) ? 1 : 0;
}

class AuthController extends SnaapiController {

  /**
   * /auth/
   */
  public function indexAction() {
    $this->_helper->getHelper('Redirector')
                    ->setGotoSimple('index', 'login');
    $this->_helper->viewRenderer->setNoRender();
  }

  /**
   * /auth/login
   */
  public function loginAction() {
    if( $this->getRequest()->isPost() && isset($_POST['openid_action']) ||
        $this->getRequest()->isGet() && isset($_GET['openid_mode']) ) {
      $this->loginOpenID();
    } else if( $this->getRequest()->isPost() && isset($_POST['login_action']) ) {
      $this->loginUserID();
    } else {
      $this->_helper->getHelper('Redirector')
                      ->setGotoSimple('index', 'login');
    }
    $this->_helper->viewRenderer->setNoRender();
  }

  /**
   * /auth/signup
   */
  public function signupAction() {
    if( $this->getRequest()->isPost() && isset($_POST['signup_action']) ) {
      $this->createAccount();
    } else {
      $this->_helper->getHelper('Redirector')
                      ->setGotoSimple('index', 'signup');
    }
    $this->_helper->viewRenderer->setNoRender();
  }

  /**
   * /auth/logout
   */
  public function logoutAction() {
    Zend_Auth::getInstance()->clearIdentity();
    $this->_helper->getHelper('Redirector')
                    ->setGotoSimple('index', 'login');
  }

  protected function failToCreateAccount($error) {
    $this->_forward('index', 'signup', null, array(
      'signup_error' => $error,
      'username' => $_POST['username'],
      'email' => $_POST['email']));
    return false;
  }

  /**
   * Use the data passed in by the user to create a new account.
   */
  protected function createAccount() {

    if( !isset($_POST['signup_action']) ) {
      $this->_forward('index', 'signup', null, array(
        'signup_error' => 'Improper post data.'));
      return false;
    }

    if( empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email']) ) {
      return $this->failToCreateAccount('Missing fields.');
    }

    $username = $_POST['username'];
    $exists = $this->_getUsersModel()->nicknameExists($username);

    if( $exists ) {
      return $this->failToCreateAccount('The username you chose already exists.');
    }

    $password = $_POST['password'];

    if( strlen($password) < 5 || $password == $username ) {
      return $this->failToCreateAccount('Please provide a better password.');
    }

    $email = trim($_POST['email']);

    if( !is_valid_email_address($email) ) {
      return $this->failToCreateAccount('Invalid email provided.');
    }

    // Create the user now!
    $profile = array('nickname' => $username, 'email' => $email);
    $user_id = $this->_getUsersModel()
                      ->createNewUserFromProfile($profile);
    $this->_getUserIDModel()->attachUserID($username, $password, $user_id);

    $authAdapter = new Zend_Auth_Adapter_DbTable(
        Zend_Registry::get('dbAdapter'),
        'userid',
        'username',
        'password',
        "MD5(CONCAT('".Zend_Registry::get('staticSalt')."', ?, salt))"
    );

    $authAdapter->setIdentity($username)
                ->setCredential($password);
    $auth = Zend_Auth::getInstance();
    $result = $auth->authenticate($authAdapter);
    if( $result->isValid() ) {
      $this->_storeUserProfile($auth, $user_id, $profile);
      $this->_helper->getHelper('Redirector')
                      ->setGotoSimple('edit', 'profile');
    } else {
      return $this->failToCreateAccount('Failed to authenticate you.');
    }

    return true;
  }

  protected function failToLoginUserID($error) {
    $params = array('login_error' => $error);
    $this->_forward('index', 'login', null, $params);
    return false;
  }

  protected function loginUserID() {
    $auth = Zend_Auth::getInstance();

    if( empty($_POST['username']) || empty($_POST['password'])) {
      return $this->failToLoginUserID('We weren\'t given much to work with. Make sure you fill in your username and password.');
    }

    $authAdapter = new Zend_Auth_Adapter_DbTable(
        Zend_Registry::get('dbAdapter'),
        'userid',
        'username',
        'password',
        "MD5(CONCAT('".Zend_Registry::get('staticSalt')."', ?, salt))"
    );

    $authAdapter->setIdentity($_POST['username'])
                ->setCredential($_POST['password']);
    $auth = Zend_Auth::getInstance();
    $result = $auth->authenticate($authAdapter);

    if( !$result->isValid() ) {
      return $this->failToLoginUserID(implode($result->getMessages(), '<br/>'));
    }

    $user_id = $this->_getUsersModel()->fetchUserIdByNickname($auth->getIdentity());
    $this->_storeUserProfile($auth, $user_id, $this->_getUsersModel()->fetchUserProfile($user_id));

    $this->_getUsersModel()->updateLoginTime($user_id);

    // A great success!
    $this->_helper->getHelper('Redirector')
                    ->setGotoSimple('index', 'index');

    return true;
  }

  /**
   * Map a specific OpenID url to its equivalent, valid OpenID url.
   * Example: flickr.com doesn't actually provide OpenID support, so we redirect to me.yahoo.com.
   *
   * @param  string  $url
   * @return null|string
   */
  protected function mapOpenIDUrl($url) {
    $urlmap = array(
      'http://flickr.com/' => 'http://me.yahoo.com/'
    );

    $normalizedUrl = $url;
    if( Zend_OpenId::normalizeUrl($normalizedUrl) ) {
      foreach( $urlmap as $key => $val ) {
        Zend_OpenId::normalizeUrl($key);

        if( $normalizedUrl == $key ) {
          $normalizedUrl = $key;
          Zend_OpenId::normalizeUrl($normalizedUrl);
          break;
        }
      }
      return $normalizedUrl;
    }

    return null;
  }

  protected function isOpenIDCallback() {
    return isset($_GET['openid_mode']) || isset($_POST['openid_mode']);
  }

  protected function failToLoginOpenID($error, $url = null) {
    $params = array('error' => $error);
    if( $url ) {
      $params['openid_url'] = $url;
    }
    $this->_forward('index', 'login', null, $params);
    return false;
  }

  protected function loginOpenID() {
    $auth = Zend_Auth::getInstance();

    if( isset($_POST['openid_action']) || $this->isOpenIDCallback() ) {

      if( !$this->isOpenIDCallback() && empty($_POST['openid_url']) ) {
        return $this->failToLoginOpenID('We weren\'t given much to work with. Make sure you fill in your OpenID url.');
      }

      $normalizedUrl = !$this->isOpenIDCallback() ? $_POST['openid_url'] : null;

      if( $this->isOpenIDCallback() || ($normalizedUrl = $this->mapOpenIDUrl($normalizedUrl)) ) {

        $sreg = new Zend_OpenId_Extension_Sreg(array(
            'nickname'  => false,
            'email'     => false,
            'fullname'  => false,
            'dob'       => false,
            'gender'    => false,
            'postcode'  => false,
            'country'   => false,
            'language'  => false,
            'timezone'  => false
          ), null, 1.1);

        $result = $auth->authenticate(
          new Zend_Auth_Adapter_OpenId($normalizedUrl, null, null, null, $sreg));

        if( !$result->isValid() ) {
          return $this->failToLoginOpenID(implode($result->getMessages(), '<br/>'),
            !$this->isOpenIDCallback() ? $_POST['openid_url'] : null);
        }

        $user_id = $this->_getOpenIDModel()->fetchUserId($auth->getIdentity());
        if( !$user_id ) {
          // This is a new user.
          $profile = $sreg->getProperties();

          // Avoid dupes.
          if( isset($profile['nickname']) && !empty($profile['nickname']) ) {
            $exists = $this->_getUsersModel()->nicknameExists($profile['nickname']);
            unset($profile['nickname']);
          }

          $user_id = $this->_getUsersModel()
                            ->createNewUserFromProfile($profile);
          $this->_getOpenIDModel()->attachOpenID($auth->getIdentity(), $user_id);
          $this->_storeUserProfile($auth, $user_id, $profile);
          $this->_helper->getHelper('Redirector')
                          ->setGotoSimple('confirm', 'profile');
        } else {
          // A great success!
          $this->_getUsersModel()->updateLoginTime($user_id);
          $this->_storeUserProfile($auth, $user_id, $this->_getUsersModel()->fetchUserProfile($user_id));
          $this->_helper->getHelper('Redirector')
                          ->setGotoSimple('index', 'index');
        }

      } else if( !$this->isOpenIDCallback() ){
        $this->_forward('index', 'login', null, array(
          'error' => 'The OpenID url you provided isn\'t valid.',
          'openid_url' => $_POST['openid_url']));

      }

    } else {
      return $this->failToLoginOpenID('No data sent over the wire.');
    }

    return true;
  }

}
