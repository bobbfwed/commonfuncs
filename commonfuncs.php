<?PHP

/*******************************************************************************
 * Created By Brandon Nimon
 * bnimon@gmail.com
 * www.nimonpro.com
 *   
 * (C) 2016 Brandon Nimon
 ******************************************************************************/

/***** FUNCTIONS I AM OFTEN LOOKING FOR ****************************************
//window.opener.document.form.field.value=...

function mouseX(evt) {
  if (evt.pageX) return evt.pageX;
  else if (evt.clientX)
    return evt.clientX + (document.documentElement.scrollLeft ?
    document.documentElement.scrollLeft :
    document.body.scrollLeft);
  else return null;
}
function mouseY(evt) {
  if (evt.pageY) return evt.pageY;
  else if (evt.clientY)
    return evt.clientY + (document.documentElement.scrollTop ?
    document.documentElement.scrollTop :
    document.body.scrollTop);
  else return null;
}


# Page Load Test Script Share by inrev teamz
$load_time = microtime();
$load_time = explode(' ',$load_time);
$load_time = $load_time[1] + $load_time[0];
$page_start = $load_time;

//**********[ SCRIPT HERE ]************

$load_time = microtime();
$load_time = explode(' ',$load_time);
$load_time = $load_time[1] + $load_time[0];
$page_end = $load_time;
$final_time = ($page_end - $page_start);
$page_load_time = number_format($final_time, 4, '.', '');
echo 'Page generated in ' . $page_load_time . ' seconds';
*******************************************************************************/
declare(strict_types=1);

//$months_arr = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ); // month list array
$months_arr = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ); // months array
$months_wdays_arr = array( 'January' => 31, 'February' => 29, 'March' => 31, 'April' => 30, 'May' => 31, 'June' => 30, 'July' => 31, 'August' => 31, 'September' =>  30, 'October' => 31, 'November' => 30, 'December' => 31 ); // months array with days

// if older than 4.3.0 or Windows PHP
if(!function_exists('fnmatch')) {
  function fnmatch ($pattern, $string) {
    return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
  } // end of fnmatch
}

/*******************************************************************************
* Functions I commonly use.                                                    *
********************************************************************************
* convert_smart_quotes ($string)                                               *
* xml_date_break ($date_str)                                                   *
* strtodate ($str, $add_date = false)                                          *
* limit_str_size ($str, $size)                                                 *
* add_slash ($str)                                                             *
* jumptopage ($href)                                                           *
* alpha_numerics ($str)                                                        *
* username_filter ($str)                                                       *
* alphas ($str)                                                                *
* umerics ($str)                                                               *
* numbersuffix ($num)                                                          *
* urldecode_to_array ($url)                                                    *
* validEmail ($email)                                                          *
* password_strength2 ($password)                                               *
* sendemail ($to, $from, $sub, $reply, $message)                               *
* generate_random_password ($length = 10, $simple = false)                     *
* encrypt ($value)                                                             *
* decrypt ($value)                                                             *
* set_key ($keystr = false)                                                    *
* generate_random_key ($bytes = 32)                                            *
* recreate_query_string ($start_char = '?')                                    *
* time_ago ($tm, $rcs = 0)                                                     *
* time_disp ($secs, $rcs = 0, $denomname = false, $denomval = false)           *
* redirect ($url)                                                              *
* english_implode ($arr)                                                       *
* even_dist_arr ($arr, $size)                                                  *
*******************************************************************************/
class common {

  // used in encode/decode cookies
  public $encryptkey = false;
  public $encryptstr = false;

  // Converts "smart quotes" and certain dashes to simple-HTML friendly ones
  public function convert_smart_quotes (string $string): string {

    //maybe this could be used instead...
    /*
    // replace Microsoft Word version of single  and double quotations marks (“ ” ‘ ’) with regular quotes (' and ")
    $output = iconv('UTF-8', 'ASCII//TRANSLIT', $input);
    */

    $search = array(chr(133),
                    chr(145), 
                    chr(146), 
                    chr(147), 
                    chr(148), 
                    chr(150), 
                    chr(151)); 
    
    $replace = array('...',
                     "'", 
                     "'", 
                     '"', 
                     '"', 
                     '-', 
                     '-'); 
    
    return str_replace($search, $replace, $string); 
  }


  // Take mm/dd/yy, mm/dd/yyyy or mm/dd format convert to Unix timestamp
  // If date is invalid, return 0
  public function strtodate (string $str, $add_date = false): int {
    $str = trim($str);
    $sec = 0;
    if ((false !== ($date_array = explode('/', $str))) && count($date_array) == 3){
      if (strlen($date_array[2]) == 2) {
        $date_array[2] = '20'.$date_array[2];                                   // make it in the 2000s (if not specified)
      }
      if (strlen($date_array[2]) != 4)
        return 0;
      if ($add_date) {
        $date_array[1]++;
        $sec--;
      }
      return mktime(0, 0, $sec, $date_array[0], $date_array[1], $date_array[2] + 0); // create unit date value from input string
    } elseif (count($date_array) == 2) {
      if ($add_date) {
        $date_array[1]++;
        $sec--;
      }
      return mktime(0, 0, $sec, $date_array[0], $date_array[1] + 0);                // create unit date value from input string
    } else
      return 0;                                                                 // no date
  } // end of strtodate

  // returns a string limited to a size, with "..." if too long
  public function limit_str_size (string $str, int $size): string {
    if (strlen($str) > $size && $size > 3)
      return substr($str, 0, $size - 3).'...';
    else
      return $str;
  } // end of limit_str_size

  // add a slash to quotation marks
  public function add_slash (string $str): string {
    return str_replace('"', '\\"', $str);
  } // end of add_slash

  // strips all but letters and numbers
  public function alpha_numerics (string $str): string {
    return preg_replace('/[^a-zA-Z0-9]/', '', $str);
  }

  // strips all but letters, numbers, and underscores "_"
  public function username_filter (string $str): string {
    return preg_replace('/[\W]+/', '', $str);
  }

  // strip all but letters, and underscores "_"
  public function alphas (string $str): string {
    return preg_replace('/[\s\W\d]+/', '', $str);
  }

  // strip all but numbers
  public function numerics (string $str): string {
    return preg_replace('/[\D]+/', '', $str);
  }

  // add 'st', 'nd', 'rd', 'th' to a number
  public function numbersuffix ($num): string {
    if (intval(substr($num, -2)) <= 19 && intval(substr($num, -2)) >= 4)
      return $num . 'th';

    else {
      switch ((int)substr($num, -1)) {
        case 1: return $num . 'st';
        case 2: return $num . 'nd';
        case 3: return $num . 'rd';
        default: return $num . 'th';
      }
    }
  }

  // decode URL string into array with values (like in $_GET or $_POST)
  public function urldecode_to_array (string $url): array {
    $ret_ar = array();
    
    if (($pos = strpos($url, '?')) !== false)         // parse only what is after the ?
      $url = substr($url, $pos + 1);
    if (substr($url, 0, 1) == '&')                    // if leading with an amp, skip it
      $url = substr($url, 1);
  
    $elems_ar = explode('&', $url);                   // get all variables
    for ($i = 0; $i < count($elems_ar); $i++) {
      list($key, $val) = explode('=', $elems_ar[$i]); // split variable name from value
      $ret_ar[urldecode($key)] = urldecode($val);     // store to indexed array
    }
  
    return $ret_ar;
  }

  // var_dump(filter_var('bob@example.com', FILTER_VALIDATE_EMAIL)); would be better if available ... PHP >= 5.2.0
  public function validEmail (string $email): string {
    return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
  }

  // function to calculate the number of minutes it would take to crack a password.
  // use this with the above example to better determine password strength.
  public function password_strength2 (string $password): float {
    if (!preg_match("#[a-zA-Z]+#", $password)) { // must have a lowercase or uppercase letter
    	return -1;
    } else {
      $score = 0;

      if (preg_match("#[0-9]+#", $password)) // numbers
      	$score += 10;
      if (preg_match("#[A-Z]+#", $password)) // uppercase
      	$score += 26;
      if (preg_match("#[a-z]+#", $password)) // lowercase
      	$score += 26;
      if (preg_match("#[\!\@\#\$\%\^\&\*\(\)\_\+\-\=]+#", $password)) // lowercase
      	$score += 14;
      if (preg_match("#[\`\~\[\{\]\}\\\|\;\:\'\"\,\<\.\>\/\?\s]+#", $password)) // lowercase
      	$score += 19;
      if (preg_match("#[^a-zA-Z0-9\!\@\#\$\%\^\&\*\(\)\_\+\-\=\`\~\[\{\]\}\\\|\;\:\'\"\,\<\.\>\/\?\s]+#", $password)) // lowercase
      	$score += 94;
      return pow($score, strlen($password)) / 240000000000; // result is minutes at 4 Billion calcs/s to crack password
    }
  }

  // JS version of password_strength:
  public function js_password_strength () {
    return '
function check_pass (password) {
  var level = password_strength(password, 7);
  switch (level) {
    case -1: ret_str = \'Use a letter! \' + nok; break;
    case -2: ret_str = \'Make longer! \' + nok; break;
    case 0:
    case 1:
    case 2:
      ret_str = level_img(level) + \' \' + nok;
      break;
    case 6: // remove this if 6 levels are fine
      ret_str = level_img(5) + \' \' + ok;
      break;
    default: ret_str = level_img(level) + \' \' + ok;
  }

  return ret_str;
}

function level_img (level) {
  return \'<img alt="Level \' + level + \'" width="20" height="20" src="images/level\' + level + \'.png">\';
}

function password_strength (password, minlength) {
  if (!(/[a-zA-Z]/.test(password))) { // must have a lowercase or uppercase letter
  	return -1;
  } else if (password.length < minlength) { // must be a minimum length
  	return -2;
  } else {
    var score = 0;

    if (password.length >= 10) // good length
    	score++;
    if ((/[0-9]/.test(password))) // numbers
    	score++;
    if ((/[A-Z]/.test(password))) // uppercase
    	score++;
    if ((/[a-z]/.test(password))) // lowercase
    	score++;
    if ((/[^a-zA-Z0-9]/.test(password))) // special characters
    	score++;
    if (password.length >= 16) // longth length
    	score++;
    return score;
  }
}
';
  }

  public function sendemail (string $to, string $from, string $subject, string $reply, string $message): bool {
    //$from = 'no-reply@example.com';

    $message = preg_replace("(\?<!\r)\n", "\r\n", $message);
    $message = base64_encode($message);

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'Content-Transfer-Encoding: base64' . "\r\n";
  
    // Additional headers
    $headers .= 'To: '.$to. "\r\n";
    $headers .= 'From: ' .$from. "\r\n";
    $headers .= 'Reply-To: <' .$reply. '>'."\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    /*$msgenc = base64encode($message);
    $hdrs = "Content-Type: text/plain\r\n"
    . "Content-Transfer-Encoding: base64\r\n";
    mail($to, $subject, $msgenc, $hdrs);*/

    return mail($to, $subject, $message, $headers);
  }

  // generate a random password -- no cryptographically secure, but still somewhat useful
  public function generate_random_password (int $length = 10, bool $simple = false): string {
    $valid_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'; // list of simple characters
    if (!$simple)
      $valid_chars .= '!#$%&*+-/\\_<=>?@'; // omitted some symbols to prevent possible confusion while typing them
    $random_string = NULL;

    $num_valid_chars = strlen($valid_chars) - 1;

    for ($i = 0; $i < $length; $i++)
      $random_string .= $valid_chars[mt_rand(0, $num_valid_chars)];

    return $random_string;
  }

  // encrypt a value using AES. set_key assigns a 256-bit key
  // security could be increased by padding
  public function encrypt (string $value): string {
    $this->set_key();
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->encryptkey, $value, MCRYPT_MODE_ECB, $iv);
    return trim(base64_encode($crypttext)); //encode
  }

  // decrypt a value using AES. set_key assigns a 256-bit key
  // security could be increased by padding
  public function decrypt (string $value): string {
    $this->set_key();
    $crypttext = base64_decode($value); //decode
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->encryptkey, $crypttext, MCRYPT_MODE_ECB, $iv);
    return trim($decrypttext);
  }

  // key string example: '8b4004f3b6ff69397079bf7526f54e34e72dbc14ba10e9b098116c8521a3f0ee' -- 32 bytes
  // a good tool for getting random keys: www.random.org/bytes/ OR www.random.org/cgi-bin/randbyte?nbytes=32&format=h
  // has a "default" in case no key is given
  public function set_key ($keystr = false): string {
    if ($this->encryptkey === false && $keystr === false)
      $keystr = '8b4004f3b6ff69397079bf7526f54e34e72dbc14ba10e9b098116c8521a3f0ee'; // just a default value
    if ($this->encryptkey === false || $keystr !== false)
      $this->encryptkey = pack('H*', $keystr);

    $this->encryptstr .= $keystr;

    return $keystr;
  }

  // generate a random byte stream, great for keys
  public function generate_random_key (int $bytes = 32): string {
    $rand = openssl_random_pseudo_bytes($bytes);
    $str = NULL;
    for ($i = 0; $i < strlen($rand); $i++)
      $str .= str_pad(dechex(ord(substr($rand, $i, 1))), 2, '0', STR_PAD_LEFT);

    return $str . ' - ' . strlen($str);
  }

  public function recreate_query_string (string $start_char = '?') {
    if (count($_GET) > 0)
      return $start_char . http_build_query($_GET);
    else
      return false;
  }

  // display x time ago, $precision_depth is precision depth
  public function relative_time_ago (int $timestamp, int $precision_depth = 0): string {
    return $this->relative_time(abs(time() - $timestamp), $precision_depth);
  }

  // display time in "4 minutes 10 seconds" format, $precision_depth is precision depth
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

  // try to use the best redirect method
  public function redirect (string $url) {
    if (!headers_sent()) {                        // if headers not sent yet... then do php redirect
      header('Location: '.$url); exit;
    } else {                                      // if headers are sent... do java redirect... if java disabled, do html redirect.
      echo '<script>
  window.location.href="'.$url.'";
</script>
<noscript>
  <meta http-equiv="refresh" content="0;url='.$url.'" />
</noscript>';
      die();
    }
  }

  // implode array elements in a text/printable format using Oxford rules
  public function english_implode (array $arr): string {
    switch (count($arr)) {
      case 0: return '';
      case 1: return $arr[0];
      case 2: return $arr[0] . ' and ' . $arr[1];
      default:
        $ret_str = NULL;
        for ($i = 0; $i < count($arr) - 1; $i++) {
          $ret_str .= $arr[$i] . ', ';
        }
        return $ret_str . 'and '.$arr[count($arr) - 1];
    }
  }

  // returns an array with $size values [kindof] evenly distributed from $arr
  // if given array is too small, the array is returned
  public function even_dist_arr (array $arr, int $size): array {
    if (count($arr) < 2 || count($arr) <= $size)
      return $arr;

    if ($size == 0)
      return array();

    if ($size == 1)
      return array($arr[round(count($arr) / 2)]);
  
    if ($size / count($arr) <= 0.5)
      $size--;
  
    $dif = $size / count($arr);
    
    $newarr = array($arr[0]);
    $idx = $dif;
    for ($i = 1; $i < count($arr) - 1; $i++) {
      if ($idx >= 1) {
        $newarr[] = $arr[$i];
        $idx -= 1;
      }
      $idx += $dif;
    }
    $newarr[] = $arr[count($arr) - 1];
    
    return $newarr;
  }

} // end of common class


/*******************************************************************************
* Common mySQL functions.                                                      *
********************************************************************************
* myquery ($query, $line, $do_die = false)                                     *
*******************************************************************************/
class sql {

  protected $db;                                                                // store reference to database

  // $db should be a PDO connection with the EXCEPTION ERRMODE
  public function __construct (pdo &$db) {

    $this->db = &$db;                                                           // assign active database

  }

  // return mysql_query result, if mysql_query fails it calls log_error()
  public function myquery (string $query, array $parameters, int $line, bool $do_die = false): pdo {
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute($parameters);
    } catch (PDOException $e) {
      $this->log_error($query, $parameters, $e->getMessage(), $line, $do_die);
    }

    return $stmt;
  } // end of myquery

  // stores an error into the errors database
  private function log_error (string $query, array $parameters, string $sql_error, int $line, bool $do_die = false) {
    try {
      $stmt = $this->db->prepare('INSERT INTO `errors` (`query`,`params`,`time`,`page`,`pagepar`,`post`,`line`,`error`,`user`) VALUES (?,?,?,?,?,?,?,?,?)');
      $stmt->execute(array(
        $query,
        serialize($parameters),
        time(),
        substr($_SERVER['PHP_SELF'], 0, 32),
        $_SERVER['QUERY_STRING'],
        serialize($_POST),
        $line,
        $sql_error,
        $_SERVER['REMOTE_ADDR']
      ));
    } catch (PDOException $e) {
      die('<font color="red"><b>'.$e->getMessage().' Log Error 1</b></font>');
    }

    echo '<font color="red"><b>Error. Notify administrator.</b></font>';
    if ($do_die)
      die();
  } // end of log_error

}
/*
CREATE TABLE IF NOT EXISTS `errors` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `query` text NOT NULL,
  `params` text NOT NULL,
  `time` int(10) NOT NULL,
  `page` varchar(32) NOT NULL,
  `pagepar` varchar(64) DEFAULT NULL,
  `post` text NOT NULL,
  `code` varchar(5) DEFAULT NULL,
  `line` int(9) NOT NULL,
  `error` text NOT NULL,
  `user` varchar(32) NOT NULL,
  `viewed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Keep track of errors on the site' AUTO_INCREMENT=1 ;
*/

/*******************************************************************************
* Common file information or manipulation functions.                           *
********************************************************************************
* get_ftype ($file)                                                            *
* filesz ($size, $denom = false, $round = 2)                                   *
* write_text ($file, $contents)                                                *
* file_safe ($file)                                                            ** 
* del ($source)                                                                *
* cp ($source, $dest)                                                          *
*******************************************************************************/
class files {

  // input file, returns the extension
  // str 'extension of $file' get_ftype( str 'file name' )
  public function get_ftype (string $file): string {
    $info = pathinfo($file);
    if(!isset($info['extension']))
      $info['extension'] = 'none';
    return strtolower($info['extension']);
  } // end of get_ftype

  // return a string of the file size with corrent denominator
  public function filesz (int $size, $denom = false, int $round = 2, bool $binary = true) {
    if ($size < 0)
      return 'unknown size';
    $denom_ar = array('bytes', 'KB', 'MB', 'GB', 'TB');

    if (false === $denom) {
      for ($div = 0; $div < count($denom_ar) && $size > 980; $div++)
        $size /= ($binary ? 1024 : 1000);
      return round($size, $round).' '.($binary ? $denom_ar[$div] : strtolower($denom_ar[$div]));
    } else {
      if (false !== ($key = array_search($denom, $denom_ar))) {
        for ($div = 0; $div < $key; $div++)
          $size /= ($binary ? 1024 : 1000);
        return round($size, $round).' '.($binary ? $denom_ar[$key] : strtolower($denom_ar[$key]));
      } else
        return false;
    }
  } // end of filesz

  // creates a file (from scratch -- overwriting existing file) and stores contents to file
  public function write_text (string $file, string $contents) {
    $fp = fopen($file, 'w');
    fwrite($fp, $contents);
    fclose($fp);
  } // end of write_text

  // removes illegal characters from file name
  public function file_safe (string $file): string {
    $file = str_replace(' ', '_', $file);
    $illegal = array('/', '\\', ':', '*', '?', '"', '<', '>', '|', '%');
    return str_replace($illegal, '', $file);
  } // end of file_safe

  // delete any file or directory, returns false on any failure, or true if successful
  // requires PHP5
  public function del (string $source): bool {
    if (is_file($source)) {
      if (!@unlink($source))                                                    // if just a file...
        return false;
      return true;
    } elseif (is_dir($source)) {
      if (substr($source, -1) != '/')                                           // ensure ending slash
        $source .= '/';

      $filear = glob($source.'*');                                              // get list of items in directory
      foreach($filear as $file)
        if (false === $this->del($file))                                        // recurse!
          return false;
      if(!@rmdir($source))                                                      // delete source directory
        return false;
      return true;
    } else
      return false;
  } // end of del

  // copy any file or directory, returns false on any failure, or true if successful
  // requires PHP5
  public function cp (string $source, string $dest): bool {
    if (is_file($source)) {                                                     // if just a file
      if (!@copy($source, $dest))
        return false;
      return true;
    } elseif (is_dir($source)) {
      if (substr($source, -1) != '/')
        $source .= '/';
      if (substr($dest, -1) != '/')
        $dest .= '/';

      $filear = glob($source.'*');
      if(!@mkdir($dest))
        return false;
      foreach($filear as $file)
        if (false === $this->cp($file, $dest.basename($file)))                  // recurse!
          return false;
      return true;
    } else
      return false;
  } // end of del

} // end of files class


/*******************************************************************************
* Common image information or manipulation functions.                          *
* make_jpg is untested...my test server wouldn't allow me to enable GD         *
* extension.                                                                   *
********************************************************************************
* image_size_str ($source)                                                     *
* image_size_mp ($source)                                                      *
* make_jpg ($source, $dest, $size)    -- requires GD extension                 *
*******************************************************************************/
class images {

  // returns string of size in readable format: "800x600"
  public function image_size_str (string $source): string {
    if (!is_file($source))
      return false;
    if (false === ($size = getimagesize($source)))
      return false;
    return $size[0].'x'.$size[1];
  } // end of image_size_str

  // returns image's size in megapixels (or false if no file)
  public function image_size_mp (string $source): float {
    if (!is_file($source))
      return false;
    if (false === ($size = getimagesize($source)))
      return false;
    return $size[0] * $size[1] / 1000000;
  } // end of image_size_mp

  // make a JPG image from another at a specific maximum pixel size
  // returns $dest (location of image) or false if no/bad source, or destination file already exists 
  // requires GD image library (extension)
  public function make_jpg (string $source, string $dest, int $size) {
    if (!is_file($source))
      return false;

    if (is_file($dest))
      return false;

    if ($size < 1)
      return false;

    $newW = $size;
    $newH = $size;

    if (false === ($srcImage = imagecreatefromjpeg($source)))
      return false;

    $srcW = ImageSX($srcImage);
    $srcH = ImageSY($srcImage);

    $ratioW = $newW ? $srcW / $newW : 1;
    $ratioH = $newH ? $srcH / $newH : 1;

    if($srcW <= $newH && $srcW <= $newW) {
      $destW = $srcW;
      $destH = $srcH;
    } elseif ($ratioW < $ratioH) {
      $destW = floor($srcW / $ratioH);
      $destH = $newH ? $newH : $srcH;
    } else {
      $destW = $newW ? $newW : $srcW;
      $destH = floor($srcH / $ratioW);
    }

    $destImage = imagecreatetruecolor($destW, $destH);
    imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $destW, $destH, $srcW, $srcH);
    ImageJpeg ($destImage, $dest);
    ImageDestroy ($srcImage);
    ImageDestroy ($destImage);
    return $dest;
  } // end make_jpg

} // end of images class


/*******************************************************************************
* Little script to search and/or replace strings in all PHP files in current   *
* directory.                                                                   *
********************************************************************************
* searchreplace()                                                              *
*******************************************************************************/
class searchreplace {

  public $LINE_SPACER = 2;

  public function display_form () {
    $loc = isset($_GET['loc']) ? trim($_GET['loc']) : '';
    if (substr($loc, -1) != '/')
      $loc .= '/';
    if (substr($loc, 0, 1) == '/')
      $loc = substr($loc, 1);

    if (!isset($_POST['search']) || $_POST['search'] == '') {
      echo '<div align="center">';
      echo '<form name="search" method="post" action="?loc='.urlencode($loc).'">';
      echo '<table cellpadding="3" cellspacing="3" border="0"><tr>
              <th align="center">PHP</th>
              <th align="center">HTML</th>
              <th align="center">JS</th>
              <th align="center">CSS</th>
              <th align="center">TPL</th>
              <th align="center">TXT</th>
              <th align="left">Others</td>
            </tr>
            <tr>
              <td align="center"><input type="checkbox" name="filetype[]" value=".php" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".html" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".js" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".css" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".tpl" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".txt" checked></td>
              <td align="center"><input type="text" name="filetypelist" size="5"> (separate by commas ",")</td>
            </tr></table><br>';

      echo 'Search for: <input type="text" size="25" maxlength="100" name="search"><br>';
      echo 'Repalce with: <input type="text" size="25" maxlength="100" name="replace"> (optional)<br>';
      echo 'Case sensetive? <input type="checkbox" name="casesensetive"><br>';
      echo 'Sub-directories? <input type="checkbox" name="recurse"><br>';
      echo 'Only display files where search was found? <input type="checkbox" name="hidenoresults" checked="checked"><br>';
      echo '<input type="submit" name="searchreplace_submit" value="Search / Replace">';
      echo '</form>';
      echo '</div>';

    } else {

      echo '<div align="center">';
      echo '<a href="?loc='.urlencode($loc).'">New Search</a><br>';
      echo '</div>';
      

      if (trim($_POST['filetypelist'])) {
        $filetype_ar = explode(',', $_POST['filetypelist']);
        for ($i = 0; $i < count($filetype_ar); $i++) {
          $filetype_ar[$i] = strtolower(trim($filetype_ar[$i]));
          if (substr($filetype_ar[$i], 0, 1) != '.')
            $filetype_ar[$i] = '.'.$filetype_ar[$i];
        }
        $filetype_ar = array_merge($_POST['filetype'], $filetype_ar);
      } else
        $filetype_ar = $_POST['filetype'];
      

      /*echo '<pre>';
      print_r($filetype_ar);
      echo '</pre>';*/

      $tot_finds = $this->srlist2(
        $_POST['search'],
        $filetype_ar,
        (isset($_POST['replace']) && $_POST['replace'] != '' ? $_POST['replace'] : false),
        (isset($_POST['casesensetive']) ? true : false),
        (isset($_POST['recurse']) ? true : false),
        (isset($_POST['hidenoresults']) ? true : false),
        0,
        $loc
      );

      echo 'Total search strings found: '.$tot_finds;
    }
  } // end of searchreplace

  private function srlist2 (string $search, array $filetype_ar, $replace = false, bool $casesensetive = false, bool $recurse = false, bool $hidenoresults = true, int $tot_finds = 0, string $dir = ''): int {

    if (!$casesensetive)
      $search = strtolower($search);

    $file_ar1 = array();
    for ($i = 0; $i < count($filetype_ar); $i++)
      $file_ar1 = array_merge($file_ar1, glob($dir.'*'.$filetype_ar[$i]));

    sort($file_ar1);

    $file_ar = array();
    foreach ($file_ar1 as $file)
      if ($file != basename($_SERVER['PHP_SELF']))
        $file_ar[] = $file;

    foreach ($file_ar as $file) {
      $fileline_ar = file($file);
      $offset = 0;
      $finds = 0;
      $newfile_ar = $fileline_ar;

      if (!$hidenoresults)
        echo '<b>'.$file.'</b>';

      foreach($fileline_ar as $lineno => $line) {
        $offset = 0;

        if (!$casesensetive)
          $line = strtolower($line);

        while (false !== ($key = strpos($line, $search, $offset)) || (false === $replace && $this->match($search, substr($line, $offset)))) {
          if ($hidenoresults && $finds == 0)
            echo '<b>'.$file.'</b>';

          $finds++;
          $tot_finds++;
          if (false === $replace) {

            echo '<br><table cellpadding="0" cellspacing="0" border="1"><tr><td>';
            echo '<table cellpadding="0" cellspacing="0" border="0">';
            for($i = $lineno - $this->LINE_SPACER; $i <= $lineno + $this->LINE_SPACER; $i++)
              if (isset($fileline_ar[$i]))
                echo '<tr>
                  <td align="right" valign="top">
                    <font size="-1" face="Courier New">'.($i + 1).':&nbsp;</font>
                  </td>
                  <td>
                    <font size="-1" face="Courier New">'.str_replace(' ', '&nbsp;', htmlspecialchars($fileline_ar[$i])).'</font>
                  </td>
                </tr>';
            echo '</table></td></tr></table>';

          } else
            $newfile_ar[$lineno] = str_replace($search, $replace, $line);

          $offset = (false === $replace ? strlen($line) : $key + strlen($search));
        }

      }

      if ($replace) {

        if (!$hidenoresults || $finds > 0)
          echo '<br>Strings replaced in file: '.$finds.'<br><br>';
        if ($finds > 0)
          file_put_contents($file, implode('', $newfile_ar));

      } elseif (!$hidenoresults || $finds > 0)
          echo ($finds == 0 ? '<br>' : '').'Search strings found in file: '.$finds.'<br><br>';

    }

    if ($recurse) {
      $dir_ar = glob($dir.'*', GLOB_ONLYDIR);
      foreach ($dir_ar as $dir1)
        $tot_finds = $this->srlist2($search, $filetype_ar, $replace, $casesensetive, $recurse, $hidenoresults, $tot_finds, $dir1.'/');
    }

    return $tot_finds;
  } // end of srlist2

  // used instead of fnmatch due to 260 character limit in fnmatch
  private function match ($pattern, $string) {
    return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
  } // end of match
}


/*******************************************************************************
* Displays a caledar month (specified month or current if left blank. Can put  *
* a link on each day of the month. Use ^d as day placeholder, or ^/^/^ as      *
* m/d/y placeholder, it will be converted to that day's value in the link. You *
* can highlight a day of the month with $highlight_day (by default, today).    *
********************************************************************************
* basic_formatting ()                                                          *
* cal( [$month [, $highlight_day [, $ghost_adjacent [, $href [, $tableclass ]]]]] )
*******************************************************************************/
class calendar {

  // exaple formatting for the calendar
  public function basic_formatting () {
    return '
    <style>
      table.phpcal {
        border: 1px solid #448;
        background-color: #ccf;
      }

      table.phpcal .phpcal-monthtitle {
        background-color: #aac;
      }

      table.phpcal .phpcal-outofmonth {
        font-style: italic;
      }

      table.phpcal .phpcal-highlightday {
        font-weight: bold;
      }

      table.phpcal .phpcal-emptydays {
        background-color: #99b;
      }
    </style>
    ';
  }

  /***** Explaination of parameters: *****
  $month is which month to display, but it is relative to the current month, 0 is this month, -1 is last month, 12 is this month next year, etc.
  $highlight_day is the day of the selected month to highlight (bold), if false, and $month is this month, today (according to the system) will be highlighted automatically, disable hy setting to 0.
  $ghost_adjacent will fill in blank cells in the table of the month with adjacent month's dates. Use 'pre' or 'post' to enable previous or folling month respectively, or true to enable both.
  $href makes the day a link of your choosing. Use ^/^/^ to have a MM/DD/YYYY formatted day put in your link, or use ^M for MM, ^m for M, ^D for DD, ^d for D, ^y for YY, and ^Y for YYYY formats.
  $tableclass simply adds a class atribute to the table with this value.
  ***************************************/
  public function display_calendar ($month = false, $highlight_day = false, $ghost_adjacent = true, $href = false, $tableclass = false): string {
    $str = NULL;

    if ($highlight_day === false && !$month)                                    // if day is not set, and $month is current month (0 or false)
      $highlight_day = intval(date('j'));                                       // mark today as highlighted day
    $month = intval(date('n')) + $month;                                        // set month relatively
    $tsmonth = mktime(0, 0, 0, $month, 1);                                      // create time stamp
    $highlight_days_in_month = intval(date('t', $tsmonth));                     // determine number of days in this month

    $str .= '<table class="phpcal'.($tableclass ? ' '.$tableclass : '').'">
  <tr>
    <th colspan="7" align="center" class="phpcal-monthtitle">'.date('F Y', $tsmonth).'</th>
  </tr>
  <tr>'."\n";

    if (($col = date('w', $tsmonth)) != 0) {
      if ($ghost_adjacent === true or $ghost_adjacent == 'pre')
        for ($i = 0 - $col + 1; $i <= 0; $i++)
          $str .= $this->prep_day($month, $i, $highlight_day, true, $href, $highlight_days_in_month);
      else
        $str .= '    <td colspan="'.$col.'" class="phpcal-emptydays"></td>'."\n";
    }

    for ($day = 1; $day <= $highlight_days_in_month; $day++)
      $str .= $this->prep_day($month, $day, $highlight_day, false, $href, $highlight_days_in_month);

    if (date('w', mktime(0, 0, 0, $month, $day - 1)) != 6) {
      if ($ghost_adjacent === true or $ghost_adjacent == 'post')
        for ($i = 1; $i <= (6 - date('w', mktime(0, 0, 0, $month, $day - 1))); $i++)
          $str .= $this->prep_day($month + 1, $i, $highlight_day, true, $href, $highlight_days_in_month);
      else
        $str .= '    <td colspan="'.(6 - date('w', mktime(0, 0, 0, $month, $day - 1))).'" class="phpcal-emptydays"></td>'."\n";
    }

    $str .= '  </tr>
</table>'."\n\n";
    return $str;
  }

  private function prep_day (int $month, int $day, $highlight_day, bool $outofmonth, $href, int $highlight_days_in_month): string {
    $str = NULL;
    if ($href) {
      $link = str_replace(array('^/^/^', '^M', '^m', '^D', '^d', '^Y', '^y'),
      array(date('m/d/Y', mktime(0, 0, 0, $month, $day)),
      date('F', mktime(0, 0, 0, $month, $day)),
      date('m', mktime(0, 0, 0, $month, $day)),
      date('j', mktime(0, 0, 0, $month, $day)),
      date('d', mktime(0, 0, 0, $month, $day)),
      date('Y', mktime(0, 0, 0, $month, $day)),
      date('y', mktime(0, 0, 0, $month, $day))), $href);
    }

    $str .= '    <td align="center" class="phpcal-day'.($outofmonth ? ' phpcal-outofmonth' : '').($highlight_day == date('j', mktime(0, 0, 0, $month, $day)) ? ' phpcal-highlightday' : '').'">'.
              ($href ? '<a href="'.$link.'">' : '').
                date('j', mktime(0, 0, 0, $month, $day)).
              ($href ? '</a>' : '').
            '</td>'."\n";
    if (date('w', mktime(0, 0, 0, $month, $day)) == 6 && date('j', mktime(0, 0, 0, $month, $day)) != $highlight_days_in_month)
      $str .= '  </tr>'."\n".'  <tr>'."\n";

    return $str;
  }
}
