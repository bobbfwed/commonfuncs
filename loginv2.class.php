<?PHP
/***** LOGIN V2 *****************************************************************
 *                                                                              *
 * Version: 2.1.0                                                               *
 * Date: October 4, 2016                                                        *
 *                                                                              *
 * Requires PHP version >= 5.4 with either OpenSSL extenion or Mcrypt extenion, *
 * or PHP >= 7.0.                                                               *
 *                                                                              *
 * Written by: Brandon Nimon                                                    *
 * Copyright: Brandon Nimon                                                     *
 * Email: brandon@nimonpro.com                                                  *
 *                                                                              *
 * To the best of my knowledge, as of October 2016, this is a very secure       *
 * method for storing and verifying users in a database along with              *
 * cryptographically secure hashes for password verification (using blowfish).  *
 *                                                                              *
 * All public methods in this class return an array of values instead of just   *
 * true or false. The array is as follows:                                      *
 *   array {                                                                    *
 *     ["status"]   => bool         // possitive/negative result of method      *
 *     ["loggedin"] => bool         // true if user or logged in, false if not  *
 *     ["userid"]   => int/bool     // user's id, or false if not logged in     *
 *     ["username"] => string/bool  // user's name, or false if not logged in   *
 *     ["seclevel"] => int/bool     // user's security value, or false...       *
 *     ["errors"]   => array        // array of errors from last method call    *
 *   }                                                                          *
 *                                                                              *
 * If token_only_allowed is enabled: as long as a valid token is supplied, the  *
 * username and password can also be supplied but they will not be checked. If  *
 * an invalid token (wrong or expired) is supplied, then their username is      *
 * checked for login, and a new token is generated.                             *
 *                                                                              *
 * PHP 5.3.7+ could probably also run this script if the array shorthand ("[]") *
 * were to be replaced with "array()". This is untested though.                 *
 *                                                                              *
 *                                                                              *
 * Public methods:                                                              *
 *  settings - get and set framework settings                                   *
 *  test_login - test user's credentials                                        *
 *  get_token - check login, get token if logged in or create one               *
 *  get_session_user_status - get errors for unexpected logout of session user  *
 *  client_allowed_to_login - check if user is allowed to log in                *
 *  userinfo - get current user's info (logged in, ID, etc.)                    *
 *  logout - log current user out/destroy session (server) and token data (db)  *
 *  create_user - create a user                                                 *
 *  user_exists - test if a user already exists in the database                 *
 *  get_clean_username - sanitize username (only: A-Z, a-z, 0-9, _)             *
 *  password_strength - get strength of a password, and if its strong enough    *
 *  check_user_password - check user's current password against a value         *
 *  change_password - change a user's password                                  *
 *  approve_user - set "approved" flag for a user                               *
 *  delete_user - delete a user (set "deleted" flag)                            *
 *  undelete_user - undelete a user (unset "deleted" flag)                      *
 *  ip_allowed - check if an IP is allowed to login                             *
 *  ip_allow_only - whitelist an IP or range of IPs                             *
 *  ip_block - blacklist an IP or range of IPs                                  *
 *  ip_remove - remove an IP from white or blacklist                            *
 *  get_managed_ips - get list of IPs on whitelist and blacklist                *
 *  session_keys - get and set class's session values                           *
 *  update_input_globals - change user/pw and token globals (GET/POST/etc.)     *
 *                                                                              *
 ********************************************************************************/

/***** FEATURES TO ADD / BUGS TO FIX ********************************************
 *                                                                              *
 * Feature: Allow non-strict login verification (ignore changing IPs and don't  *
 * require unique tokens).                                                      *
 *                                                                              *
 ********************************************************************************/

/***** EXPECTED TABLE LAYOUT **********************************************************************************
 * More columns can be added without conflict. Use the additional_fields parameter in create_user method.     *
 **************************************************************************************************************

  CREATE TABLE IF NOT EXISTS `sec_users` (
    `id` int(9) NOT NULL,
    `user` varchar(20) NOT NULL,
    `pwhash` binary(60) NOT NULL,
    `pwchangedate` int(10) NOT NULL,
    `seclevel` tinyint(2) NOT NULL,
    `token` varbinary(60) NOT NULL,
    `tokendate` int(10) NOT NULL,
    `lastlogin` int(10) NOT NULL DEFAULT '0',
    `lastip` varchar(15) DEFAULT NULL,
    `lastview` int(10) NOT NULL DEFAULT '0',
    `added` int(10) NOT NULL,
    `approved` int(10) NOT NULL,
    `deleted` int(10) NOT NULL DEFAULT '0'
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Store user info (for LoginV2)';
  ALTER TABLE `sec_users`
    ADD PRIMARY KEY (`id`),
    ADD KEY `user` (`user`);
  ALTER TABLE `sec_users`
    MODIFY `id` int(9) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

  CREATE TABLE IF NOT EXISTS `sec_badlogin` (
    `id` int(9) NOT NULL,
    `type` tinyint(1) NOT NULL,
    `ip` varchar(15) NOT NULL,
    `user` varchar(20) NOT NULL,
    `date` int(10) NOT NULL
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Track bad logins (for LoginV2)';
  ALTER TABLE `sec_badlogin`
    ADD PRIMARY KEY (`id`),
    ADD KEY `date` (`date`);
  ALTER TABLE `sec_badlogin`
    MODIFY `id` int(9) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

  CREATE TABLE IF NOT EXISTS `sec_iplist` (
    `id` int(9) NOT NULL,
    `ip` varchar(15) NOT NULL,
    `type` tinyint(1) NOT NULL COMMENT '1 = black, 2 = white',
    `added` int(10) NOT NULL
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='track black and whitelisted IPs';
  ALTER TABLE `sec_iplist`
    ADD PRIMARY KEY (`id`),
    ADD KEY `type` (`type`);
  ALTER TABLE `sec_iplist`
    MODIFY `id` int(9) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

 **************************************************************************************************************/
declare(strict_types=1);

class loginv2 {

  protected $userinfovals = [
      'loggedin' => false,                  // current user's login status - true if logged in false if not
      'userid' => false,                    // current user's id... false if not logged in
      'username' => false,                  // current user's username... false if not logged in
      'seclevel' => false,                  // current user's security level... false if not logged in
      'token' => false,                     // current user's login token - used for session-based or token-only logins - a HASH_LENGTH-size case-sensetive string if logged in, false if not
      'tokenexpires' => false               // time the current user's login token should expire -- it could change (in theory) if the $config settings are changed after the class is initialized
    ];

  protected $db;                            // store reference to database
  protected $error_ar = [];                 // track errors as they occur, so they can be returned
  protected $token_global;                  // holds reference to the array for which the token value is searched through
  protected $userpw_global;                 // holds reference to the array for which the username and password values are searched through

  protected $config = [
      'login_expires' => 36000,             // how long each login is valid for - default: 10 hours

      'inactive_logout' => false,           // run inactive logout - if a user hasn't visited a page in inactive_timeout seconds, their login is no longer valid
      'inactive_timeout' => 3600,           // how long after being inactive before the user is logged out automatically - default: 1 hour

      'use_session' => true,                // intialize and utilize session data -- this probably should only be disabled if this is used in an API setup
      'session_key' => 'loginv2',           // key in $_SESSION that has the session values used in the class
      'session_destroy' => false,           // during logout, should the entire session be destroyed? false will only destroy the session information set in session_key (set above), true will invoke PHP's session_destroy() actually removing all server-side session data (and cookie) for the current user

      'token_global' => 'REQUEST',          // can be set as 'REQUEST', 'GET', 'POST', 'SESSION', or 'COOKIE' -- limits the scope of valid input, to increase security a bit, or allow the use of externally controlled sessions or cookies (using cookies for this is probably a bad idea) -- if set to SESSION, a session is started even if use_session is disabled, but none of the other session features will work if use_session is disabled
      'token_only_allowed' => false,        // allow users to login with their token only (useful for APIs) - slightly less secure if enabled, but for most purposes, acceptibly so
      'token_key' => 'loginv2token',        // this key is searched for in the $config['token_global'] defined scope, it holds the user's token -- basically, it's the variable's name that will be submitted containing the verification token
      'token_cleanup_keep' => 600,          // keep tokens around for a little while after they expire, this is purely for displaying errors if a user get logged out unexpectedly - default: 10 minutes

      'username_max_length' => 20,          // maximum length a username can be (after invalid characters have been removed -- valid: A-Z, a-z, 0-9, _ ) -- length limit should be included in input form
      'username_min_length' => 3,           // minimum length a username can be (after invalid characters have been removed -- valid: A-Z, a-z, 0-9, _ )

      'password_max_length' => 72,          // maximum length an inputted password can be, any longer than this, and the input is truncated, shortening this only reduces your overall security -- this limit is determined by BCRYPT / Blowfish, the function truncates passwords to 72 characters
      'password_min_length' => 8,           // minimum length an inputted password can be -- length is only a part of password securtiy and a short length can be used if password_strength_test is enabled and password_min_strength is a sufficiently high value (will force a minimum length to an extent anyway)
      'password_strength_test' => true,     // do a basic check on password for strength
      'password_min_strength' => 18000,     // minimum allowable strength for a password to be valid -- this value is the approximate number of seconds to brute force match the password - 18000 = 300 minutes - 5 hours -- higher is better in a logarithmic way 20000 is only slightly better than 2000 and so on -- real life hacking time should be *much* longer due to Blowfish's high cost of hashing

      'userpw_global' => 'REQUEST',         // can be set as 'REQUEST', 'GET', 'POST', 'SESSION', or 'COOKIE' -- limits the scope of valid input, to increase security a bit, or allow the use of externally controlled sessions or cookies (using cookies for this is a bad idea) -- if set to SESSION, a session is started even if use_session is disabled, but none of the other session features will work if use_session is disabled
      'username_key' => 'user',             // key in $userpw_global that has the username
      'password_key' => 'pw',               // key in $userpw_global that has the password

      'def_seclevel' => 8,                  // default security level (used during user creation) -- an abatrary number usually used for indicating security distinctions between normal users, moderators, admins, etc. (a recommended system: 1 = super admin, 2 = admin, 4 = moderator, 6 = special user, 8 = standard user)

      'badlogin_timeout' => 300,            // amount of seconds unsuccessful logings are relevent (default 5 minutes)
      'badlogin_limit' => 5,                // the number of unsuccessful logins before lockout

      'user_table' => 'sec_users',          // table to store user information
      'badlogin_table' => 'sec_badlogin',   // table to store unsuccessful logins
      'ip_table' => 'sec_iplist',           // table for white and blacklisted IPs

      'dateformat' => 'F j, Y',             // date() format -- not yet used
      'datetimeformat' => 'F j, Y g:i',     // date() format -- only used (so far) in an error message in check_user_password() method

      'cost_for_pw_hash' => 9,              // the higher the number, the more time it takes to create a password hash and verify it (only used during user login and user creation) -- once a password is created at this cost, it cannot be [easily] changed unless the user changes their password
      'cost_for_ses_hash' => 6,             // the higher the number, the more time it takes to create a session hash (during initial login) (session hashes are never verified against a user input, rather they are created as short term keys at login to verify the user has already logged in, i.e. the value is only ever compared against a stored value in the database, thus the cost is not a significant factor with regards to security)
      // Cost timing from my test server (faster servers should have higher costs)
      // Cost: seconds to hash (same amount of time to check hash)
      //  < 4: FAILS
      //    4: 0.0021
      //    6: 0.0074
      //    8: 0.0288
      //    9: 0.0574 (0.05s is the recommended de-hashing time for a high-traffic production server)
      //   10: 0.1142
      //   11: 0.2280
      //   12: 0.4558
      //   13: 0.9114
      //   14: 1.8225
      //   15: 3.6451
      //   16: 7.2901
      // Each increase in cost approximately doubles the time

      // deveolopment settings
      'destroy_session_if_failed' => true,  // if a login fails, should the session be destroyed? -- should never be disabled on a production server, but good for testing if you are having problems with session data going missing during login attempts
      'add_bad_login_if_failed' => true     // if a login fails, should it logged? -- should never be disabled on a production server
    ];

  const HASH_LENGTH = 60;                   // the length of the hash generated (used for password length and token length) - 60 characters is the output length of BCRYPT / Blowfish hashing
  const IP_LENGTH = 15;                     // the length limit of IPs in the database (will need to be 45 for IPv6)

  // Values stored to database for when an unsuccessful login is logged. !! DO NOT CHANGE !!
  const BAD_USER = 1;
  const BAD_PW = 2;
  const BAD_SESSION = 3;
  const BAD_TOKEN = 4;

  // Values used for ip list.  !! DO NOT CHANGE !!
  const BLACKLISTED_IP = 1;
  const WHITELISTED_IP = 2;



  // initialize class, $db is PDO databse connection, $config is settings to change (if you need)
  // configurations should be setup during initialization for best results, changing some settings after initialization are undocumented (but sometimes useful)
  public function __construct (pdo &$db, array $config = []) {

    if (!function_exists('random_bytes') && !function_exists('password_hash') && !function_exists('openssl_random_pseudo_bytes') && !function_exists('mcrypt_create_iv'))
      throw new Exception('Login V2 Requires one of the following: PHP version >= 7.0.0 / OpenSSL extension / Mcrypt extension'); // there isn't enough here to generate a cryptographically secure hash or random bytes

    $this->db = &$db;                                                           // assign active database

    if (count($config) > 0)                                                     // if there are settings to be set (this isn't a full check, but it's not necessary here)
      $this->settings($config);                                                 // doesn't check any returns, so be sure $config array is formatted correctly

    if ($this->config['cost_for_pw_hash'] < 4)                                  // costs less than 4 don't produce an output, and could cause serious security risks
      $this->config['cost_for_pw_hash'] = 4;

    if ($this->config['cost_for_ses_hash'] < 4)                                 // costs less than 4 don't produce an output, and could cause serious security risks
      $this->config['cost_for_ses_hash'] = 4;

    // !!! A session is started regardless of use_session config setting if either global is set to SESSION -- but none of the other session features will work if use_session is disabled !!!
    if (session_id() === '' && ($this->config['use_session'] || strtoupper($this->config['token_global']) == 'SESSION' || strtoupper($this->config['userpw_global']) == 'SESSION')) {

      ini_set('session.gc_maxlifetime', strval($this->config['login_expires'])); // attempt to change session life (doesn't always work)
      session_set_cookie_params($this->config['login_expires']);                // attempt to change session life (doesn't always work)
      session_start();

    }

    $this->cleanup_login_table();
    $this->update_input_globals();

  }


  // get or store settings in this class
  // SYNTAX:
  //   settings() -> returns config array - use: $loginv2->settings()['setting']
  //   settings('setting') -> returns value of single config setting (as array with index with setting's name) - use: $loginv2->settings('setting')['setting']
  //   settings('setting', value) -> stores single setting (returns true)
  //   settings(array['setting' => value]) -> stores multiple settings (returns true)
  //   settings(array['setting1', 'setting2']) -> returns multiple settings (returns true)
  // NOTE: With arrays, the returned status could be true, even though nothing was changed, lookout for errors when using an array
  public function settings ($config = false, $value = NULL): array {

    if ($config === false)                                                      // get all values of config
      return $this->return_values(true, $this->config);

    elseif (is_array($config)) {                                                // set/get multiple values of config using array

      if (count($config) > 0) {

        if ($this->is_assoc($config)) {                                         // set multiple values of config using array ('setting_name' => new_value)

          $ret_ar = [];

          foreach ($config as $setting => $setvalue) {

            if (isset($this->config[$setting])) {                               // make sure the setting exists

              $this->config[$setting] = $setvalue;                              // set setting
              $ret_ar[$setting] = $setvalue;                                    // return the new value if changed

            } else
              $this->error_ar[] = 'Invalid configuration name "'.$config[$i].'".';

          }

          return $this->return_values(true, $ret_ar);

        } else {                                                                // get multiple values of config using array ('setting_name1', 'setting_name2')

          $ret_ar = [];

          for ($i = 0; $i < count($config); $i++) {

            if (isset($this->config[$config[$i]]))                              // make sure the setting exists
              $ret_ar[$config[$i]] = $this->config[$config[$i]];                // return the value

            else
              $this->error_ar[] = 'Invalid configuration name "'.$config[$i].'".';

          }

          return $this->return_values(true, $ret_ar);

        }

      } else
        return $this->return_values(false);                                     // empty array doesn't do much....

    } elseif ($value === NULL) {                                                // get single value of a setting in config

      if (isset($this->config[$config]))                                        // make sure the setting exists
        return $this->return_values(true, [$config => $this->config[$config]]);

      else {

        $this->error_ar[] = 'Invalid configuration name "'.$config.'".';
        return $this->return_values(false);

      }

    } else {                                                                    // set single value in config

      if (isset($this->config[$config])) {

        $this->config[$config] = $value;                                        // set setting
        return $this->return_values(true, [$config => $value]);

      } else {

        $this->error_ar[] = 'Invalid configuration name "'.$config.'".';
        return $this->return_values(false);

      }

    }

  }


  // just find out if user is allowed to log in at all -- useful for displaying lockout errors before the user attempts to log in
  public function client_allowed_to_login (): array {

    return $this->return_values($this->check_client_allowed_to_login());        // return if user is allowed to login

  }


  // go through the steps of verifying if the current user has the corrent credentials to be logged in
  // if token_only_allowed is enabled, and a token value exists, test token for a valid login, if any of that fails, try the following:
  // if the user has inputted a correct username and password, validate
  // if no username or password is given and use_session is enabled, then it checks existing $_SESSION against database values (it tests userid, username, seclevel, token, and current ip)
  public function test_login (): array {

    if ($this->check_client_allowed_to_login()) {                               // check if user is even allowed to login

      // check token first -- this is done first, so if someone keeps sending username and passwords (like through an API), it doesn't keep generating new tokens
      if ($this->config['token_only_allowed'] && isset($this->token_global[$this->config['token_key']])) { // if a token key has been provided

        if ($id = $this->check_token_login()) {                                 // if token is valid, get back the user's ID

          $this->log_user_in_from_db($id);
          return $this->return_values(true);

        }

      }

      // check username and password (from form)
      if (isset($this->userpw_global[$this->config['username_key']], $this->userpw_global[$this->config['password_key']])) { // if form information has been provided

        $user = $this->clean_username($this->userpw_global[$this->config['username_key']]); // clean up username

        if ($user && $this->userpw_global[$this->config['password_key']]) {     // check values have been entered into the form
          
          if ($id = $this->check_user_and_password($user, $this->userpw_global[$this->config['password_key']])) { // if username and password is valid, get back user's ID

            $this->register_new_login($id);
            return $this->return_values(true);

          }

        } else
          $this->error_ar[] = 'Enter a valid username and password.';

      // check session information
      } elseif ($this->config['use_session'] && session_id() !== '' && isset($_SESSION[$this->config['session_key']])) { // if a session exists

        if ($this->check_session_login()) {                                     // if session data is valid (checks user and token and other information), get back user's ID (but we're not using it)

          $this->log_user_in_from_session();
          return $this->return_values(true);

        }

      } else
        $this->error_ar[] = 'No valid login values.';

    }

    if ($this->config['destroy_session_if_failed'])                             // developer option
      $this->session_destroy();

    return $this->return_values(false);

  }


  // check login credentials, get token if user already has one assigned
  // if user is not yet logged in, $log_user_in_if_not_already determines if a new token should be generated or not
  public function get_token (bool $log_user_in_if_not_already = false): array {

    if ($this->check_client_allowed_to_login()) {                               // check if user is even allowed to login

      if (isset($this->userpw_global[$this->config['username_key']], $this->userpw_global[$this->config['password_key']])) { // if form information has been provided

        $user = $this->clean_username($this->userpw_global[$this->config['username_key']]); // clean up username

        if ($user && $this->userpw_global[$this->config['password_key']]) {     // check values have been entered into the form
          
          if ($id = $this->check_user_and_password($user, $this->userpw_global[$this->config['password_key']])) { // if username and password is valid, get back user's ID

            $stmt = $this->db->prepare('SELECT `token`, `tokendate` FROM `'.$this->config['user_table'].'` WHERE `id` = :id LIMIT 1'); // get any existing token values
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            $stmt->execute();

            list($token, $tokendate) = $stmt->fetch(PDO::FETCH_NUM);            // get any existing token values

            if (trim($token) && $tokendate + $this->config['login_expires'] > time()) { // test if token is valid

              $this->log_user_in_from_db($id);                                  // log user in
              return $this->return_values(true, [$this->config['token_key'] => $token]); // return token for future log ins

            } elseif ($log_user_in_if_not_already) {

              $this->register_new_login($id);                                   // generate new token for user and log user in
              return $this->return_values(true, [$this->config['token_key'] => $this->userinfovals['token']]); // return token for future log ins

            } else
              $this->error_ar[] = 'No valid token available.';

          }

        } else
          $this->error_ar[] = 'Invalid username or password.';

      } else
        $this->error_ar[] = 'No valid login values.';

    }

    return $this->return_values(false);

  }


  // if a user was logged out (usually unexpectedly), this method can tell the user why
  // only works if sessions are being used
  // token expiring is only displayed if caught before it is cleaned up, the timing of which is controlled by the token_cleanup_keep variable
  public function get_session_user_status () {

    if (session_id() !== '' &&                                                  // if session is enabled
        (
          isset($_SESSION[$this->config['session_key']]['userid']) ||           // if a userid is available or...
          isset($_SESSION[$this->config['token_key']])                          // if a token is available
        )
      ) {

      if (isset($_SESSION[$this->config['session_key']]['userid'])) {           // if a userid is available

        $stmt = $this->db->prepare('SELECT `token`, `tokendate`, `lastview`, `approved` FROM `'.$this->config['user_table'].'` WHERE `id` = :userid');
        $stmt->bindParam(':userid', $_SESSION[$this->config['session_key']]['userid'], PDO::PARAM_INT);

      } elseif (isset($_SESSION[$this->config['token_key']])) {                 // if a token is available

        $stmt = $this->db->prepare('SELECT `token`, `tokendate`, `lastview`, `approved` FROM `'.$this->config['user_table'].'` WHERE `token` = :token');
        $stmt->bindParam(':token', $_SESSION[$this->config['token_key']], PDO::PARAM_STR);

      }

      $stmt->execute();

      if (list($token, $tokendate, $lastview, $approved) = $stmt->fetch(PDO::FETCH_NUM)) { // if user information is found
          
        $ret = true;                                                            // return true if the user seems to be logged in (other errors could still keep the user logged out)

        if (isset($_SESSION[$this->config['token_key']]) && $token !== $_SESSION[$this->config['token_key']]) {

          $this->error_ar[] = 'Invalid token. Log in again to acquire new token.';
          $ret = false;

        }

        if (isset($_SESSION[$this->config['session_key']]['token']) && $token !== $_SESSION[$this->config['session_key']]['token']) {

          $this->error_ar[] = 'Invalid token. Log in again to acquire new token.';
          $ret = false;

        }

        if ($tokendate <= time() - $this->config['login_expires']) {

          $this->error_ar[] = 'Token expired. Log in again to acquire new token.';
          $ret = false;

        }

        if ($this->config['inactive_logout'] && $lastview <= time() - $this->config['inactive_timeout']) {

          $this->error_ar[] = 'Inactive for more than '.$this->relative_time($this->config['inactive_timeout']).'. Log in again.';
          $ret = false;

        }

        if ($approved >= time()) {

          $this->error_ar[] = 'User has not been approved, contact administator if you think this is in error.';
          $ret = false;

        }

        return $this->return_values($ret);

      } else
        $this->error_ar[] = 'User or token information not found.';

    }

    return $this->return_values(false);

  }


  // provide read-only public access to user's info
  // can be used like this: if ($loginv2->userinfo()['loggedin']) { ... }
  // or like this: if ($loginv2->userinfo('loggedin')) { ... }
  // this is the only public method that breaks the rule about returned values, only because it is probably the most used method, so simplicity is healthy for cycles and memory
  public function userinfo (string $info = '') {

    if ($info && isset($this->userinfovals[$info]))                             // if a specific bit of information is requested
      return $this->userinfovals[$info];                                        // only return the value (doesn't return anything else)

    else
      return $this->return_values(true);                                        // return all the user's values

  }


  // returns public version of session_destroy
  // maybe add option to only destroy relevent session data, instead of all of it
  // if $destroy_entire_session is true it will destroy session and destroy its cookie
  // if $destroy_entire_session is false it will only unset the array used for this login class
  public function logout (): array {

    return $this->return_values($this->session_destroy());

  }


  // checks to see if username is unique then creates row with username and hashed password
  // it is up to the developer using this to insert any other relavent information about the user into the row
  // $seclevel is optional, it can contain the user's security level (can be used for permission levels), false uses the default value set in config
  // $wait_for_approval sets if an admin has to approve the user before they can log in
  // $additional_fields is an optional associative array of fields and values to insert if user creation was successful -- structure as follows:
  //   array(
  //     'your_field' => array (
  //       'value' => value,
  //       'PDOtype' => PDO::PARAM_XXX  // use PDO constants
  //     )
  //   )
  // returns new user's id number if successful, false if it failed (will also populate error array)
  public function create_user (string $user, string $pw, int $seclevel = -1, bool $wait_for_approval = false, array $additional_fields = []): array {

    if ($seclevel === -1)
      $seclevel = $this->config['def_seclevel'];

    $user = $this->clean_username($user);                                       // clean username
    $pw = substr($pw, 0, $this->config['password_max_length']);                 // clean password (kinda)

    if (strlen($user) < $this->config['username_min_length']) {                 // check user min length

      $this->error_ar[] = 'The username is too short. It must be at least ' . $this->config['username_min_length'] . ' valid characters long.<br>' .
                          'Valid characters include upper and lowercase letters, numbers, and underscores ( "_" ).';
      return $this->return_values(false);

    }

    if (strlen($user) > $this->config['username_max_length']) {                 // check user max length

      $this->error_ar[] = 'The username is too long. It must be at most ' . $this->config['username_max_length'] . ' characters long.';
      return $this->return_values(false);

    }

    if ($this->check_user_exists($user)) {                                      // check to see if username is unique

      $this->error_ar[] = 'A user with that name already exists.';
      return $this->return_values(false);

    }

    if (strlen($pw) < $this->config['password_min_length']) {                   // check password length

      $this->error_ar[] = 'Password is too short. It must be at least '.$this->config['password_min_length'].' characters long.';
      return $this->return_values(false);
      
    }

    if ($this->config['password_strength_test']) {                              // check password strength

      if (!$this->check_password_strength($pw))
        return $this->return_values(false);
      
    }

    $pwhash = $this->generate_hash($pw, $this->config['cost_for_pw_hash']);     // generate unique password hash

    if (count($additional_fields) > 0 && $this->is_assoc($additional_fields)) { // fields to be inserted along with new user

      $field_str = ', `'.implode('`,`', array_keys($additional_fields)).'`';    // create comma delimited string of additional fields
      $param_str = ', :addfield'.implode(',:addfield', range(1, count($additional_fields))); // create string that looks like ", :addfield1, :addfield2, :addfield3..."

    } else
      $field_str = $param_str = false;

    // create new user
    $stmt = $this->db->prepare('INSERT INTO `'.$this->config['user_table'].'`
      (`user`, `pwhash`, `seclevel`, `added`, `token`, `tokendate`, `pwchangedate`, `approved`'.$field_str.')
      VALUES
      (:user, :pwhash, :seclevel, :time1, 0, 0, :time2, :approved'.$param_str.')');

    $time = time();
    $stmt->bindParam(':user', $user, PDO::PARAM_STR, $this->config['username_max_length']);
    $stmt->bindParam(':pwhash', $pwhash, PDO::PARAM_STR, self::HASH_LENGTH);
    $stmt->bindParam(':seclevel', $seclevel, PDO::PARAM_INT);
    $stmt->bindParam(':time1', $time, PDO::PARAM_INT);
    $stmt->bindParam(':time2', $time, PDO::PARAM_INT);
    $stmt->bindValue(':approved', ($wait_for_approval ? 0 : $time), PDO::PARAM_INT);

    if ($field_str) {

      $i = 1;

      foreach ($additional_fields as $insert_info)
        $stmt->bindValue(':addfield'.$i++, $insert_info['value'], $insert_info['PDOtype']);

    }

    $stmt->execute();

    return $this->return_values(true, ['newuserid' => $this->db->lastInsertId()]);

  }


  // find out if a user exists, useful for creating users
  public function user_exists (string $user): array {

    return $this->return_values(check_user_exists($user));
    
  }


  // make sure username only has acceptable characters
  // could also be used to prevent certain usernames being added
  // use: $loginv2->get_clean_username('A user_name w/Stuff #01')['cleaned_username']; // will return 'Auser_namewStuff01'
  public function get_clean_username (string $user): array {

    return $this->return_values(true, ['cleaned_username' => $this->clean_username($user)]);

  }


  // calculate password strength in seconds it would take to bruteforce hack
  // returns true if password is strong enough, false if not, returns strength value in 'password_strength' index
  public function password_strength (string $pw): array {

    $pw = preg_replace('/(.)\\1+/', '$1', $pw);                                 // remove repeate characters (a likely error for making it easy to match)

    $strength = $this->test_password_strength($pw);

    return $this->return_values($strength >= $this->config['password_min_strength'], ['password_strength' => $strength]);

  }


  // test a user's password against an entered value (usually used before a password change, for security reasons)
  public function check_user_password (int $userid, string $pw): array {

    $stmt = $this->db->prepare('SELECT `pwhash` FROM `'.$this->config['user_table'].'` WHERE `id` = :userid LIMIT 1');
    $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);

    $stmt->execute();

    if (list($dbhash) = $stmt->fetch(PDO::FETCH_NUM)) {                         // user found

      if (!$this->check_password($pw, $dbhash)) {                               // password does not matched

        $this->error_ar[] = 'Value does not match current password.';
        return $this->return_values(false);

      } else
        return $this->return_values(true);

    } else {

      $this->error_ar[] = 'User not found, user\'s ID expected.';
      return $this->return_values(false);

    }

  }


  // the parent program is the one responsible for making sure the password is strong enough and user and password is properly verified
  public function change_password (string $userid, string $newpw): array {

    if (strlen($newpw) < $this->config['password_min_length']) {                // check password length

      $this->error_ar[] = 'Password is too short. It must be at least '.$this->config['password_min_length'].' characters long.';
      return $this->return_values(false);
      
    }

    if ($this->config['password_strength_test']) {                              // check password strength

      if (!$this->check_password_strength($newpw))
        return $this->return_values(false);
      
    }

    $pwhash = $this->generate_hash($newpw, $this->config['cost_for_pw_hash']);  // generate unique password hash

    // change password hash
    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET `pwhash` = :pwhash, `pwchangedate` = :time WHERE `id` = :userid LIMIT 1');
    $stmt->bindParam(':pwhash', $pwhash, PDO::PARAM_STR, self::HASH_LENGTH);
    $stmt->bindValue(':time', time(), PDO::PARAM_INT);
    $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);

    $stmt->execute();

    return $this->return_values($stmt->rowCount() == 1);

  }


  // approve a user if $wait_for_approval was set as true in the create_user() method
  // the parent program is the one responsible for making sure this method is properly secured
  // $time can set in the future, and the user won't be able to log in until that time has passed, or can be set as 0 to "unapprove" a user
  public function approve_user (int $userid, int $time = -1): array {

    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET `approved` = :time WHERE `id` = :userid LIMIT 1');
    $stmt->bindValue(':time', ($time < 0 ? time() : $time), PDO::PARAM_INT);
    $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);

    $stmt->execute();

    return $this->return_values($stmt->rowCount() == 1);

  }


  // the parent program is the one responsible for making sure this method is properly secured
  // $time is the time to delete the user, if left off, or set before time(), the user will be prevented from logging in imediately, but if $time is in the future, the user can stay logged in until $time has passed
  public function delete_user (int $userid, int $time = -1): array {

    if ($time < time())                                                         // if the time suplied has already past, then set the deletion time to now
      $time = time();

    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET `deleted` = :time WHERE `id` = :userid LIMIT 1');
    $stmt->bindParam(':time', $time, PDO::PARAM_INT);
    $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);

    $stmt->execute();

    return $this->return_values($stmt->rowCount() == 1);

  }


  // the parent program is the one responsible for making sure this method is properly secured
  public function undelete_user (int $userid): array {

    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET `deleted` = 0 WHERE `id` = :userid LIMIT 1');
    $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);

    $stmt->execute();

    return $this->return_values($stmt->rowCount() == 1);

  }

  
  // if given IP is allowed by whitelist and not on blacklist, then return true, else return false
  public function ip_allowed (string $ip): array {

    return $this->return_values($this->check_ip_allowed($ip));

  }


  // whitelist an IP or block of IPs
  // if no whitelist exists, all IPs are "OKAY", otherwise, only IPs in whitelist are allowed
  // if the current IP would be blocked by an executed whitelist add, it won't add it
  // SYNTAX:
  //  ip_allow_only('192.168.1.105') -> adds IP 192.168.1.105 to whitelist
  //  ip_allow_only('192.168.*.*') -> allows all local IPs (use * as a wildcard)
  public function ip_allow_only (string $ip): array {

    return $this->return_values($this->add_ip($ip, $this::WHITELISTED_IP));
    
  }


  // blacklist an IP or block of IPs, use 
  // IPs on blacklist are blocked from access to the system
  // if the current IP would be blocked by an executed blacklist add, it won't add it
  // SYNTAX:
  //  ip_block('192.168.1.105') -> adds IP 192.168.1.105 to blacklist
  //  ip_block('192.168.*.*') -> blocks all local IPs (use * as a wildcard)
  public function ip_block (string $ip): array {

    return $this->return_values($this->add_ip($ip, $this::BLACKLISTED_IP));

  }


  // remove IP from white or blacklist
  // if IP is in whitelist and the removal will block current IP, it won't be removed
  public function ip_remove (string $ip): array {

    $stmt = $this->db->prepare('SELECT `id`, `type`, `added` FROM `'.$this->config['ip_table'].'` WHERE `ip` = :ip LIMIT 1');
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);

    $stmt->execute();

    if (list($removed_id, $removed_type, $removed_added) = $stmt->fetch(PDO::FETCH_NUM)) {

      $stmt = $this->db->prepare('DELETE FROM `'.$this->config['ip_table'].'` WHERE `ip` = :ip');
      $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);

      $stmt->execute();

      if ($removed_type == $this::WHITELISTED_IP && !$this->check_ip_allowed($_SERVER['REMOTE_ADDR'])) { // removing an IP from the whitelist could mess with current user

        // revert entry back to what it was
        $stmt = $this->db->prepare('INSERT INTO `'.$this->config['ip_table'].'` (`id`, `ip`, `type`, `added`) VALUES (:id, :ip, :type, :time)');
        $stmt->bindParam(':id', $removed_id, PDO::PARAM_INT);
        $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);
        $stmt->bindParam(':type', $removed_type, PDO::PARAM_INT);
        $stmt->bindParam(':time', $removed_added, PDO::PARAM_INT);

        $stmt->execute();

        $this->error_ar[] = 'Removing this IP/range from whitelist will block your IP, so it was not removed.';

        return $this->return_values(false);

      }

      return $this->return_values(true);

    } else
      $this->error_ar[] = 'IP doesn\'t existed in list, nothing has been changed.';

    return $this->return_values(false);

  }


  // send to parent program the current blacklist and whitelist values
  public function get_managed_ips (): array {

    $whiteblacklist_ips = ['whitelist' => [], 'blacklist' => []];

    $stmt = $this->db->query('SELECT `ip` FROM `'.$this->config['ip_table'].'` WHERE `type` = '.$this::WHITELISTED_IP);
    while (list($ip) = $stmt->fetch(PDO::FETCH_NUM))
      $whiteblacklist_ips['whitelist'][] = $ip;

    $stmt = $this->db->query('SELECT `ip` FROM `'.$this->config['ip_table'].'` WHERE `type` = '.$this::BLACKLISTED_IP);
    while (list($ip) = $stmt->fetch(PDO::FETCH_NUM))
      $whiteblacklist_ips['blacklist'][] = $ip;


    return $this->return_values(true, $whiteblacklist_ips);

  }


  // get or store values in the class's session
  // SYNTAX:
  //   session_keys() -> returns entire contents of class's session - use: $loginv2->session_keys()['session_key']
  //   session_keys('session_key') -> returns value of single session value (as array with index with session value's name) - use: $loginv2->settings('session_key')['session_key']
  //   session_keys('session_key', value) -> stores single session value (returns true)
  //   session_keys(array['session_key' => value]) -> stores multiple session values (returns true)
  //   session_keys(array['session_key1', 'session_key2']) -> returns multiple session values (returns true)
  //   session_keys('session_key', NULL, true) -> deletes session value (returns true)
  //   session_keys(array['session_key1', 'session_key2'], NULL, true) -> deletes multiple session values (returns true)
  // You can ignore $clear_errors, it is used for recursion
  public function session_keys ($key = false, $value = NULL, bool $unset = false, bool $clear_errors = true): array {

    if (session_id() === '') {                                                  // if a session is not started, don't do anything (test it this way incase use_session is disabled, but sessions are still started)

      $this->error_ar[] = 'Session has not been started.';
      return $this->return_values(false);

    }

    if (!isset($_SESSION[$this->config['session_key']])) {                      // if a session is not started, don't do anything (test it this way incase use_session is disabled, but sessions are still started)

      $this->error_ar[] = 'No session data has been stored.';
      return $this->return_values(false);

    }

    $protected_keys = ['userid', 'username', 'seclevel', 'token', 'tokenexpires']; // values that are read-only -- I know this adds very little security/protection, but it at least makes it harder for developers to accidentally change them

    if ($key === false)                                                         // get all values of loginv2's session key
      return $this->return_values(true, $_SESSION[$this->config['session_key']]);

    else {

      if (is_array($key)) {                                                     // set/get multiple values in session

        if (count($key) > 0) {

          if ($this->is_assoc($key)) {                                          // set multiple values in session

            $ret_ar = [];

            foreach ($key as $sess_key => $keyvalue)
              $ret_ar[$sess_key] = $this->session_keys($sess_key, $keyvalue, $unset, false)[$sess_key];

            return $this->return_values(true, $ret_ar);

          } else {                                                              // get multiple values in session

            $ret_ar = [];

            for ($i = 0; $i < count($key); $i++) {

              $val = $this->session_keys($key[$i], NULL, $unset, false);

              if (!$unset && $val['status'])
                $ret_ar[$key[$i]] = $val[$key[$i]];

            }

            return $this->return_values(true, $ret_ar);

          }

        } else
          return $this->return_values(false);

      } elseif ($value === NULL) {                                              // get single session value

        $key = strtolower($key);

        if (isset($_SESSION[$this->config['session_key']][$key])) {

          if ($unset === false)
            return $this->return_values(true, [$key => $_SESSION[$this->config['session_key']][$key]], $clear_errors);

          else {                                                                // delete/unset session value

            if (!in_array($key, $protected_keys)) { 

              unset($_SESSION[$this->config['session_key']][$key]);
              return $this->return_values(true, [], $clear_errors);

            } else {

              $this->error_ar[] = 'Session key "'.$key.'" is protected/read-only.';
              return $this->return_values(false, [], $clear_errors);

            }

          }

        } else {

          $this->error_ar[] = 'Session key "'.$key.'" does not exist.';
          return $this->return_values(false, [], $clear_errors);

        }

      } else {                                                                  // set single session value

        if (!in_array($key, $protected_keys)) {

          $_SESSION[$this->config['session_key']][$key] = $value;
          return $this->return_values(true, [$key => $_SESSION[$this->config['session_key']][$key]], $clear_errors);

        } else {

          $this->error_ar[] = 'Session key "'.$key.'" is protected/read-only.';
          return $this->return_values(false, [], $clear_errors);

        }

      }

    }

  }


  // change/update the user/password global, and the token global
  // set userpw_global and token_global using $loginv2->settings() before running this method
  // if either global is set to SESSION and a session has not been started, it will set the scope to $_REQUEST
  public function update_input_globals ($userpw_global = false, $token_global = false): array {

    $return_type = true;                                                        // return true on most cases -- false if 'SESSION' is selected for either global, but a session hasn't been started yet

    // set username and password scope
    switch ($this->config['userpw_global']) {

      case 'GET':     $this->userpw_global = &$_GET;     break;
      case 'POST':    $this->userpw_global = &$_POST;    break;
      case 'COOKIE':  $this->userpw_global = &$_COOKIE;  break;
      case 'SESSION':

        if (session_id() !== '') {                                              // if a session has been started, use the $_SESSION variable

          $this->userpw_global = &$_SESSION;
          break;

        }

        $this->error_ar[] = 'A session has not been started. "userpw_global" could not be set to $_SESSION, using $_REQUEST instead.';
        $return_type = false;

      default:        $this->userpw_global = &$_REQUEST;

    }

    // set token scope
    switch ($this->config['token_global']) {

      case 'GET':     $this->token_global = &$_GET;     break;
      case 'POST':    $this->token_global = &$_POST;    break;
      case 'COOKIE':  $this->token_global = &$_COOKIE;  break;
      case 'SESSION':

        if (session_id() !== '') {                                              // if a session has been started, use the $_SESSION variable

          $this->token_global = &$_SESSION;
          break;

        }

        $this->error_ar[] = 'A session has not been started. "token_global" could not be set to $_SESSION, using $_REQUEST instead.';
        $return_type = false;

      default:        $this->token_global = &$_REQUEST;

    }

    return $this->return_values($return_type);

  }

  // END OF PUBLIC METHODS





  // START OF PRIVATE METHODS

  // clear expired tokens
  private function cleanup_login_table () {

    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET token = "", `tokendate` = 0 WHERE `tokendate` != 0 AND `tokendate` < :date');
    $stmt->bindValue(':date', time() - $this->config['login_expires'] - $this->config['token_cleanup_keep'], PDO::PARAM_INT); // save tokens for a little while after they expire to allow error to be displayed

    $stmt->execute();

  }


  // find a user that matches all the given token, if it exists, return their id, if not, return false
  private function check_token_login () {

    $stmt = $this->db->prepare('SELECT `id`, `tokendate`, `lastview`, `approved` FROM `'.$this->config['user_table'].'`
      WHERE `token` = :token AND
            (
              `deleted` = 0 OR
              `deleted` > :deltime
            )');
    $stmt->bindParam(':token', $this->token_global[$this->config['token_key']], PDO::PARAM_STR, self::HASH_LENGTH);
    $stmt->bindValue(':deltime', time(), PDO::PARAM_INT);

    $stmt->execute();

    if (list($id, $tokendate, $lastview, $approved) = $stmt->fetch(PDO::FETCH_NUM)) {

      if ($tokendate <= time() - $this->config['login_expires']) {

        $this->error_ar[] = 'Token expired. Log in again to acquire new token.';
        return false;

      }

      if ($this->config['inactive_logout'] && $lastview <= time() - $this->config['inactive_timeout']) {

        $this->error_ar[] = 'Inactive for more than '.$this->config['inactive_timeout'].' seconds. Log in again.';
        return false;

      }

      if ($approved >= time()) {

        $this->error_ar[] = 'User has not been approved, contact administator if you think this is in error.';
        return false;

      }

      return $id;

    }

    $this->log_bad_login_attempt(self::BAD_TOKEN);
    $this->error_ar[] = 'Invalid token. Log in again to acquire new token.';
    return false;

  }


  // find a user that matches all the information stored in the $_SESSION variable, if it exists, return user's id
  private function check_session_login () {

    $stmt = $this->db->prepare('SELECT EXISTS(
        SELECT * FROM `'.$this->config['user_table'].'`
        WHERE `id` = :userid AND
              `user` = :user AND
              `seclevel` = :seclevel AND
              `token` = :token AND
              `tokendate` > :tokenexpire AND
              `lastip` = :ip AND
              '.($this->config['inactive_timeout'] ? '`lastview` > :inacttime AND' : '').'
              `approved` < :time AND
              (
                `deleted` = 0 OR
                `deleted` > :deltime
              )
      )');
    $stmt->bindParam(':userid', $_SESSION[$this->config['session_key']]['userid'], PDO::PARAM_INT);
    $stmt->bindParam(':user', $_SESSION[$this->config['session_key']]['username'], PDO::PARAM_STR, $this->config['username_max_length']);
    $stmt->bindParam(':seclevel', $_SESSION[$this->config['session_key']]['seclevel'], PDO::PARAM_INT);
    $stmt->bindParam(':token', $_SESSION[$this->config['session_key']]['token'], PDO::PARAM_STR, self::HASH_LENGTH);
    $stmt->bindValue(':tokenexpire', time() - $this->config['login_expires'], PDO::PARAM_INT);
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);
    if ($this->config['inactive_timeout'])
      $stmt->bindValue(':inacttime', time() - $this->config['inactive_timeout'], PDO::PARAM_INT);
    $stmt->bindValue(':time', time(), PDO::PARAM_INT);
    $stmt->bindValue(':deltime', time(), PDO::PARAM_INT);

    $stmt->execute();

    list($exists) = $stmt->fetch(PDO::FETCH_NUM);

    if (!$exists) {

      // check inactive_timeout specificially if the session check fails (slightly faster in most cases)
      if ($this->config['inactive_timeout']) {

        $stmt = $db->prepare('SELECT `lastview` FROM `'.$this->config['user_table'].'` WHERE `id` = :userid');
        $stmt->bindParam(':userid', $_SESSION[$this->config['session_key']]['userid'], PDO::PARAM_INT);

        $stmt->execute();

        list($lastview) = $stmt->fetch(PDO::FETCH_NUM);

        if ($lastview <= time() - $this->config['inactive_timeout']) {

          $this->error_ar[] = 'Inactive for more than '.$this->config['inactive_timeout'].' seconds. Log in again.';
          return false;

        }

      }

      $this->log_bad_login_attempt(self::BAD_SESSION, $_SESSION[$this->config['session_key']]['username']);
      $this->error_ar[] = 'Session expired or invalid. Log in again.';
      return false;

    }

    return ($exists == 1 ? $_SESSION[$this->config['session_key']]['userid'] : false);

  }


  // register the user logging in (using a form) -- generate a token and store it
  // should only be called after user with this $id is properly verified
  private function register_new_login (int $id) {

    $randomstr = substr(base64_encode($this->rand_bytes(17)), 0, 22);
    $randomstr = str_replace('+', '.', $randomstr);
    $token = $this->generate_hash($randomstr, $this->config['cost_for_ses_hash']);

    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'`
      SET `lastlogin` = :time1,
          `token` = :token,
          `tokendate` = :time2,
          `lastip` = :ip,
          `lastview` = :time3
      WHERE `id` = :userid LIMIT 1');
    $time = time();
    $stmt->bindParam(':time1', $time, PDO::PARAM_INT);
    $stmt->bindParam(':time2', $time, PDO::PARAM_INT);
    $stmt->bindParam(':time3', $time, PDO::PARAM_INT);
    $stmt->bindParam(':userid', $id, PDO::PARAM_INT);
    $stmt->bindParam(':token', $token, PDO::PARAM_STR, self::HASH_LENGTH);
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);

    $stmt->execute();

    $this->log_user_in_from_db($id);

    if ($this->config['use_session']) {

      $_SESSION[$this->config['session_key']]['userid'] = $this->userinfovals['userid'];
      $_SESSION[$this->config['session_key']]['username'] = $this->userinfovals['username'];
      $_SESSION[$this->config['session_key']]['seclevel'] = $this->userinfovals['seclevel'];
      $_SESSION[$this->config['session_key']]['token'] = $token;
      $_SESSION[$this->config['session_key']]['tokenexpires'] = $time + $this->config['login_expires'];

    }

  }


  // retrieve data pertaining to the user with this id, then store that to the relevent variables, and set the "loggedin" element to true
  // should only be called after user with this $id is properly verified
  private function log_user_in_from_db (int $id) {

    $stmt = $this->db->prepare('SELECT `user`, `seclevel`, `token`, `tokendate` FROM `'.$this->config['user_table'].'` WHERE `id` = :userid LIMIT 1');
    $stmt->bindParam(':userid', $id, PDO::PARAM_INT);

    $stmt->execute();

    list($user, $seclevel, $token, $tokendate) = $stmt->fetch(PDO::FETCH_NUM);

    $this->userinfovals['loggedin'] = true;
    $this->userinfovals['userid'] = $id;
    $this->userinfovals['username'] = $user;
    $this->userinfovals['seclevel'] = $seclevel;
    $this->userinfovals['token'] = $token;
    $this->userinfovals['tokenexpires'] = $tokendate + $this->config['login_expires'];

    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET `lastip` = :ip, `lastview` = :time WHERE `id` = :userid LIMIT 1');
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);
    $stmt->bindValue(':time', time(), PDO::PARAM_INT);
    $stmt->bindParam(':userid', $id, PDO::PARAM_INT);

    $stmt->execute();

  }


  // set the "loggedin" element to true and store current session data into current class's values
  // should only be called after user with the session data is properly verified
  private function log_user_in_from_session () {

    $this->userinfovals['loggedin'] = true;
    $this->userinfovals['userid'] = $_SESSION[$this->config['session_key']]['userid'];
    $this->userinfovals['username'] = $_SESSION[$this->config['session_key']]['username'];
    $this->userinfovals['seclevel'] = $_SESSION[$this->config['session_key']]['seclevel'];
    $this->userinfovals['token'] = $_SESSION[$this->config['session_key']]['token'];
    $this->userinfovals['tokenexpires'] = $_SESSION[$this->config['session_key']]['tokenexpires'];

    $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET `lastip` = :ip, `lastview` = :time WHERE `id` = :userid LIMIT 1');
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);
    $stmt->bindValue(':time', time(), PDO::PARAM_INT);
    $stmt->bindParam(':userid', $this->userinfovals['userid'], PDO::PARAM_INT);

    $stmt->execute();

  }


  // make sure username only has acceptable characters
  // could also be used to prevent certain usernames being added
  private function clean_username (string $user): string {

    return preg_replace('/[^A-Za-z0-9_]/', '', $user);                          // allow alpha-numerics and underscore ("_")

  }


  private function check_user_exists (string $user): bool {

    $user = $this->clean_username($user);

    $stmt = $this->db->prepare('SELECT EXISTS(SELECT * FROM `'.$this->config['user_table'].'` WHERE `user` = :user)');
    $stmt->bindParam(':user', $user, PDO::PARAM_STR);

    $stmt->execute();

    list($exists) = $stmt->fetch(PDO::FETCH_NUM);

    return ($exists == 1);

  }


  private function check_password_strength (string $pw): bool {

    $pw_strength = $this->test_password_strength($pw);

    if ($pw_strength < $this->config['password_min_strength'] / 1000) {

      $this->error_ar[] = 'Password is very weak. Try making it longer and add different types of characters.';
      return false;

    } elseif ($pw_strength < $this->config['password_min_strength']) {

      $this->error_ar[] = 'Password is too weak. Try making it longer or add different types of characters.';
      return false;

    }

    return true;

  }


  // try to determine password strength by calculating how long it would take to bruteforce hack
  // returns the number of seconds it would take at 20B tries per second
  private function test_password_strength (string $pw): float {

    $pw = preg_replace('/(.)\\1+/', '$1', $pw);                                 // remove repeate characters (a likely error for making it easy to match)

    $score = 0;

    if (preg_match("#[0-9]+#", $pw))                                            // numbers
      $score += 10;
    if (preg_match("#[A-Z]+#", $pw))                                            // uppercase
      $score += 26;
    if (preg_match("#[a-z]+#", $pw))                                            // lowercase
      $score += 26;
    if (preg_match("#[\!\@\#\$\%\^\&\*\(\)\_\+\-\=]+#", $pw))                   // common characters
      $score += 14;
    if (preg_match("#[\`\~\[\{\]\}\\\|\;\:\'\"\,\<\.\>\/\?\s]+#", $pw))         // less common characters
      $score += 19;
    if (preg_match("#[^a-zA-Z0-9\!\@\#\$\%\^\&\*\(\)\_\+\-\=\`\~\[\{\]\}\\\|\;\:\'\"\,\<\.\>\/\?\s]+#", $pw)) // any other character -- if I could find a good "non-UTF-8" regex, then I would probably replace this with that
      $score += 94;                                                             // it's actually double this, but strlen counts non-UTF-8 characters as twice their actual length, and that's mostly what will be caught by the above regex, so the math works out in the end

    return pow($score, strlen($pw)) / 10000000000;                              // result is seconds to crack at 20 Billion tries per second (divide by half of 20B because on average, it will be found somewhere in the middle of the search)

  }


  // generate cryptographically secure random bytes
  private function rand_bytes (int $size): string {

    if (function_exists('random_bytes'))                                        // PHP >= 7.0.0
      return random_bytes($size);

    if (function_exists('openssl_random_pseudo_bytes'))                         // if OpenSSL extension is installed and PHP >= 5.3.0
      return openssl_random_pseudo_bytes($size);

    if (function_exists('mcrypt_create_iv'))                                    // if Mcrypt extension is installed and PHP >= 5.3.0
      return mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

    throw new Exception('Login V2 Requires one of the following: PHP version >= 7.0.0 / OpenSSL extension / Mcrypt extension'); // there isn't enough here to generate a cryptographically secure hash or random bytes

  }


  // generate a new random hash based on a given string
  // if $str is a known value (such as a password), the resulting hash can be verified against the same $str in the future using $this->check_password()
  private function generate_hash (string $str, int $cost): string {

    if ($cost < 4)                                                              // cost values less than 4 do not produce reliable results
      $cost = 4;

    if (function_exists('password_hash'))                                       // PHP >= 5.5.0
      return password_hash($str, PASSWORD_BCRYPT, ['cost' => $cost]);           // generate hash - forcing BCRYPT/Blowfish because the cost is manually set

    $salt = substr(base64_encode($this->rand_bytes(17)), 0, 22);                // generate cryptographically secure random salt
    $salt = str_replace('+', '.', $salt);

    $param = '$'.implode('$', [
      "2y",                                                                     // select the most secure version of blowfish (>=PHP 5.3.7)
      str_pad(substr(strval($cost), 0, 2), 2, '0', STR_PAD_LEFT),               // add the cost in two digits
      $salt                                                                     // add the salt
    ]);

    return crypt($str, $param);                                                 // generate hash

  }


  // check password against correctly formatted crypt() hash
  private function check_password (string $str, string $hash): bool {

    if (function_exists('password_verify'))                                     // PHP >= 5.5.0
      return password_verify($str, $hash);                                      // check to see if the hashes match

    return crypt($str, $hash) == $hash;                                         // check to see if the hashes match

  }


  // log the current user out -- remove stored session data
  // if $config['session_destroy'] is true, it also invokes PHP's session_destroy() removing all server-side session data and deletes the session cookie
  // returns true if the PHP session was destroyed, false if unable to or settings said not to
  private function session_destroy (): bool {

    if ($this->userinfovals['userid'] !== false) {

      $stmt = $this->db->prepare('UPDATE `'.$this->config['user_table'].'` SET `token` = "", `tokendate` = 0 WHERE `id` = :userid LIMIT 1');
      $stmt->bindParam(':userid', $this->userinfovals['userid'], PDO::PARAM_INT);

      $stmt->execute();

    }

    $this->userinfovals['loggedin'] = false;
    $this->userinfovals['userid'] = false;
    $this->userinfovals['username'] = false;
    $this->userinfovals['seclevel'] = false;
    $this->userinfovals['token'] = false;
    $this->userinfovals['tokenexpires'] = false;

    if (isset($_SESSION[$this->config['session_key']])) {

      if ($this->config['session_destroy']) {

        $_SESSION = [];

        if (isset($_COOKIE[session_name()]))
          setcookie(session_name(), '', time() - 86400, '/');

        if (@session_destroy())
          return true;

        else {

          $this->error_ar[] = 'PHP session could not be destroyed.';
          return false;

        }

      } else {

        unset($_SESSION[$this->config['session_key']]);
        return true;

      }

    }

    return false;

  }


  // check to see if a user by the given name exists
  // then check to see if the given password matches that user's stored password
  // return the user's id if both are valid, false if not
  // store bad login attempt except when user is not been approved yet
  private function check_user_and_password (string $user, string $pw) {

    $user = $this->clean_username($user);
    $pw = substr($pw, 0, $this->config['password_max_length']);

    $stmt = $this->db->prepare('SELECT `id`, `pwhash`, `approved`, `deleted` FROM `'.$this->config['user_table'].'` WHERE `user` = :user LIMIT 1');
    $stmt->bindParam(':user', $user, PDO::PARAM_STR);

    $stmt->execute();

    if (list($id, $dbhash, $approved, $deleted) = $stmt->fetch(PDO::FETCH_NUM)) { // username found

      if (!$this->check_password($pw, $dbhash)) {                               // password does not matched

        $this->log_bad_login_attempt(self::BAD_PW, $user);
        $this->error_ar[] = 'Wrong username or password.';
        return false;

      }

      if ($deleted !== 0 && $deleted <= time()) {                               // user is deleted

        $this->log_bad_login_attempt(self::BAD_USER, $user);
        $this->error_ar[] = 'User has been deleted, contact administator if you think this is in error.';
        return false;

      }

      if ($approved == 0) {                                                     // user is not approved

        $this->error_ar[] = 'User has not yet been approved, contact administator if you think this is in error.';
        return false;

      }

      if ($approved > time()) {                                                 // user is not approved

        $this->error_ar[] = 'User has been approved to log in after ' . date($this->config['datetimeformat'], $approved) . '.';
        return false;

      }

      return $id;                                                               // everything checks out, return the user's ID

    } else {                                                                    // no user with this nanme

      $this->log_bad_login_attempt(self::BAD_USER, $user);
      $this->error_ar[] = 'Wrong username or password.';
      return false;

    }

  }


  // log a bad login to the server
  // type = 1 when the username and password are wrong
  // type = 2 when the session values are wrong (also occurs if the session has expired -- should probably fix this)
  private function log_bad_login_attempt (int $type, $user = false) {

    if ($this->config['add_bad_login_if_failed']) {                             // developer option

      $stmt = $this->db->prepare('INSERT INTO `'.$this->config['badlogin_table'].'`
        (`type`, `ip`, `user`, `date`)
        VALUES
        (:type, :ip, :user, :date)');
      $stmt->bindParam(':type', $type, PDO::PARAM_INT);
      $ip = $_SERVER['REMOTE_ADDR'];
      $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);
      $stmt->bindValue(':user', trim(strval($user)), PDO::PARAM_STR);
      $stmt->bindValue(':date', time(), PDO::PARAM_INT);

      $stmt->execute();

    }

  }


  // check database for logged bad login attempts, if there is enough in a recent enough amount of time, return false
  private function check_bad_login_attempts (string $ip, $user = false): bool {

    $stmt = $this->db->prepare('SELECT COUNT(*) FROM `'.$this->config['badlogin_table'].'` WHERE (`ip` = :ip' . ($user !== false ? ' OR `user` = :user' : '') . ') AND `date` > :time');
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);
    if ($user !== false)
      $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindValue(':time', time() - $this->config['badlogin_timeout'], PDO::PARAM_INT);

    $stmt->execute();

    list($badlogincount) = $stmt->fetch(PDO::FETCH_NUM);

    return ($badlogincount < $this->config['badlogin_limit']);

  }


  // check to see if the client's ip has been blocked, or if they have failed to log in too many times
  private function check_client_allowed_to_login (): bool {

    $user = (isset($this->userpw_global[$this->config['username_key']]) ? $this->clean_username($this->userpw_global[$this->config['username_key']]) : false);
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!$this->check_ip_allowed($ip)) {

      $this->error_ar[] = 'Your IP has been blocked.';
      return false;

    }


    if (!$this->check_bad_login_attempts($ip, $user)) {

      $this->error_ar[] = 'You have attempted to log in too many times.';
      return false;

    }

    return true;

  }


  // Add IP to whitelist or blacklist
  // if no whitelist exists, all IPs are "OKAY", otherwise, only IPs in whitelist are allowed
  // if the current IP would be blocked by an executed IP add, it won't add it
  private function add_ip (string $ip, int $type): bool {

    $stmt = $this->db->prepare('SELECT `id`, `type`, `added` FROM `'.$this->config['ip_table'].'` WHERE `ip` = :ip');
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);

    $stmt->execute();

    if (list($duplicate_id, $duplicate_type, $duplicate_added) = $stmt->fetch(PDO::FETCH_NUM)) {

      if ($type != $duplicate_type) {

        $this->error_ar[] = 'IP already existed in list, the type has been updated.';

        $stmt = $this->db->prepare('UPDATE `'.$this->config['ip_table'].'` SET `type` = :type, `added` = :time WHERE `id` = :id');
        $stmt->bindParam(':type', $type, PDO::PARAM_INT);
        $stmt->bindValue(':time', time(), PDO::PARAM_INT);
        $stmt->bindParam(':id', $duplicate_id, PDO::PARAM_INT);

        $stmt->execute();

        if (!$this->check_ip_allowed($_SERVER['REMOTE_ADDR'])) {

          // revert entry back to what it was
          $stmt = $this->db->prepare('UPDATE `'.$this->config['ip_table'].'` SET `type` = :type, `added` = :time WHERE `id` = :id');
          $stmt->bindParam(':type', $duplicate_type, PDO::PARAM_INT);
          $stmt->bindParam(':time', $duplicate_added, PDO::PARAM_INT);
          $stmt->bindParam(':id', $duplicate_id, PDO::PARAM_INT);

          $stmt->execute();

          if ($type == $this::WHITELISTED_IP)
            $this->error_ar[] = 'Adding this IP/range to the whitelist will block your IP, so it was not added. Be sure to add an IP/range that includes your IP first.';
          
          else
            $this->error_ar[] = 'Adding this IP/range to the blacklist will block your IP, so it was not added.';

          return false;

        }

        return true;

      } else {

        $this->error_ar[] = 'IP already existed in '.($type == $this::WHITELISTED_IP ? 'whitelist' : 'blacklist').', no updates made.';
        return false;

      }

    } else {

      $stmt = $this->db->prepare('INSERT INTO `'.$this->config['ip_table'].'` (`ip`, `type`, `added`) VALUES (:ip, :type, :time)');
      $stmt->bindParam(':ip', $ip, PDO::PARAM_STR, self::IP_LENGTH);
      $stmt->bindParam(':type', $type, PDO::PARAM_INT);
      $stmt->bindValue(':time', time(), PDO::PARAM_INT);

      $stmt->execute();
        
      $list_id = $this->db->lastInsertId();

      if (!$this->check_ip_allowed($_SERVER['REMOTE_ADDR'])) {

        // delete added entry to allow user to access the page
        $stmt = $this->db->prepare('DELETE FROM `'.$this->config['ip_table'].'` WHERE `id` = :id');
        $stmt->bindParam(':id', $list_id, PDO::PARAM_INT);

        $stmt->execute();

        if ($type == $this::WHITELISTED_IP)
          $this->error_ar[] = 'Adding this IP/range to the whitelist will block your IP, so it was not added. Be sure to add an IP/range that includes your IP first.';
        
        else
          $this->error_ar[] = 'Adding this IP/range to the blacklist will block your IP, so it was not added.';

        return false;

      }

      return true;

    }

  }

  // if given IP is allowed by whitelist, and is not on blacklist, then return true, else return false
  private function check_ip_allowed (string $ip): bool {

    // check whilelist first
    $stmt = $this->db->query('SELECT EXISTS(SELECT * FROM `'.$this->config['ip_table'].'` WHERE `type` = '.$this::WHITELISTED_IP.')');
    list($whitelist_exists) = $stmt->fetch(PDO::FETCH_NUM);

    if ($whitelist_exists == 1) {                                               // if there are whitelisted IPs, then the given IP must be in that list
      $stmt = $this->db->query('SELECT `ip` FROM `'.$this->config['ip_table'].'` WHERE `type` = '.$this::WHITELISTED_IP);

      $block = true;                                                            // if there are whitelisted IPs, then the given IP must be in that list
      while ((list($check_ip) = $stmt->fetch(PDO::FETCH_NUM)) && $block) {

        if (fnmatch($check_ip, $ip))                                            // found in list
          $block = false;

      }

      if ($block)
        return false;

    }

    // check blacklist
    $stmt = $this->db->query('SELECT `ip` FROM `'.$this->config['ip_table'].'` WHERE `type` = '.$this::BLACKLISTED_IP);
    while (list($check_ip) = $stmt->fetch(PDO::FETCH_NUM)) {

      if (fnmatch($check_ip, $ip))                                              // found in list
        return false;

    }

    return true;

  }


  // used to return all necessary information to main program
  private function return_values ($status = false, array $add_params = [], bool $clear_errors = true): array {

    $ret_ar = $this->userinfovals + [
        'status' => $status,
        'errors' => $this->error_ar
      ] + $add_params;

    if ($clear_errors)
      $this->error_ar = [];

    return $ret_ar;

  }


  // test if a given array is associative or not
  // note that it returns true if the array is empty -- so test for that beforehand if it's important
  private function is_assoc (array $arr): bool {

    return array_keys($arr) !== range(0, count($arr) - 1);

  }


  // display time in "4 minutes 10 seconds" format, $precision_depth is precision depth
  // leaving public incase someone wants to use it somewhere on their site...
  public function relative_time (int $seconds, int $precision_depth = 0, array $denomname = array('second','minute','hour','day','week','month','year','decade'), array $denomval = array(1,60,3600,86400,604800,2630880,31570560,315705600)): string {

    for ($v = count($denomval) - 1; ($v >= 0) && (($no = $seconds / $denomval[$v]) <= 1); $v--);
      if ($v < 0)
        $v = 0;

    $seconds %= $denomval[$v];
  
    $no = ($precision_depth ? floor($no) : round($no)); // if last denomination, round
  
    if ($no != 1)
      $denomname[$v] .= 's';

    $x = $no . ' ' . $denomname[$v];
  
    if (($precision_depth > 0) && ($v >= 1))
      $x .= ' ' . $this->relative_time($seconds, $precision_depth - 1, $denomname, $denomval);
  
    return $x;

  }

}