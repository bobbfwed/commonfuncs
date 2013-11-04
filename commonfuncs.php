<?PHP

/*******************************************************************************
 * Created By Brandon Nimon
 * bnimon@gmail.com
 * www.nimonpro.com
 *   
 * (C) 2013 Brandon Nimon
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

//$months_arr = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ); // month list array
$months_arr = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ); // months array
$months_wdays_arr = array( 'January' => 31, 'February' => 29, 'March' => 31, 'April' => 30, 'May' => 31, 'June' => 30, 'July' => 31, 'August' => 31, 'September' =>  30, 'October' => 31, 'November' => 30, 'December' => 31 ); // months array with days

// if older than 4.3.0 or Windows PHP
if(!function_exists('fnmatch')) {
  function fnmatch($pattern, $string) {
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
  var $encryptkey = false;
  var $encryptstr = false;

  // Converts "smart quotes" and certain dashes to simple-HTML friendly ones
  function convert_smart_quotes ($string) {

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

  // Convert global tracking's XML date format to Unix timestamp.
  function xml_date_break ($date_str) {
    // 08:17 AM 07/14/2008 (convert this format)
    $date_str = trim($date_str);
    $hour = substr($date_str, 0, 2);
    $min = substr($date_str, 3, 2);
    $ampm = substr($date_str, 6, 2);
    $month = substr($date_str, 9, 2);
    $day = substr($date_str, 12, 2);
    $year = substr($date_str, 15, 4);

    //echo $hour.':'.$min.' '.$ampm.' '.$month.' '.$day.' '.$year;
    if ($ampm == 'PM')
      $hour += 12;

    return mktime($hour, $min, 0, $month, $day, $year);
  } // end of xml_date_break


  // Take mm/dd/yy, mm/dd/yyyy or mm/dd format convert to Unix timestamp
  // If date is invalid, return 0
  function strtodate ($str, $add_date = false) {
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
  function limit_str_size ($str, $size) {
    if (strlen($str) > $size && $size > 3)
      return substr($str, 0, $size - 3).'...';
    else
      return $str;
  } // end of limit_str_size


  // add a slash to quotation marks
  function add_slash ($str) {
    return str_replace('"', '\\"', $str);
  } // end of add_slash


  // use javascript to instantly move to another page
  function jumptopage ($href) {
    echo '<script language="JavaScript">';
    // the genius JavaScript function name below originally coined by Bradley Hart!
    echo 'var gotothispagenow = window.setTimeout(location.href=\''.$href.'\', 1)';
    echo '</script>';
  } // end of jumptopage


  // strips all but letters and numbers
  function alpha_numerics ($str) {
    return preg_replace('/[^a-zA-Z0-9]/', '', $str);
  }


  // strips all but letters, numbers, and underscores "_"
  function username_filter ($str) {
    return preg_replace('/[\W]+/', '', $str);
  }


  // strip all but letters, and underscores "_"
  function alphas ($str) {
    return preg_replace('/[\s\W\d]+/', '', $str);
  }


  // strip all but numbers
  function numerics ($str) {
    return preg_replace('/[\D]+/', '', $str);
  }


  // add 'st', 'nd', 'rd', 'th' to a number
  function numbersuffix ($num) {
    if ((int)substr($num, -2) <= 19 && (int)substr($num, -2) >= 4)
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
  function urldecode_to_array ($url) {
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
  function validEmail ($email) {
    return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
  }

  // JS version below
  /*function password_strength ($password, $minlength = 7) {
    if (!preg_match("#[a-z]+#", $password) && !preg_match("#[A-Z]+#", $password)) { // must have a lowercase or uppercase letter
    	return -1;
    } elseif (strlen($password) < $minlength) { // must be a minimum length
    	return -2;
    } else {
      $score = 0;

      if (strlen($password) >= 10) // good length
      	$score++;
      if (preg_match("#[0-9]+#", $password)) // numbers
      	$score++;
      if (preg_match("#[A-Z]+#", $password)) // uppercase
      	$score++;
      if (preg_match("#[a-z]+#", $password)) // lowercase
      	$score++;
      if (preg_match("#\W+#", $password)) // symbols
      	$score++;
      if (strlen($password) >= 16) // long length
      	$score++;
      return $score;
    }
  }*/

  // function to calculate the number of minutes it would take to crack a password.
  // use this with the above example to better determine password strength.
  function password_strength2 ($password) {
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

/* JS version of password_strength:
function check_pass (password) {
  var level = password_strength(password, 7);
  switch (level) {
    case -1: ret_str = 'Use a letter! ' + nok; break;
    case -2: ret_str = 'Make longer! ' + nok; break;
    case 0:
    case 1:
    case 2:
      ret_str = level_img(level) + ' ' + nok;
      break;
    case 6: // remove this if 6 levels are fine
      ret_str = level_img(5) + ' ' + ok;
      break;
    default: ret_str = level_img(level) + ' ' + ok;
  }

  return ret_str;
}

function level_img (level) {
  return '<img alt="Level ' + level + '" width="20" height="20" src="images/level' + level + '.png">';
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
*/

  function sendemail ($to, $from, $sub, $reply, $message) {
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

    return mail($to, $sub, $message, $headers);
  }

  function generate_random_password ($length = 10, $simple = false) {
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
  function encrypt ($value) {
    if (!$value)
      return false;

    $this->set_key();
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->encryptkey, $value, MCRYPT_MODE_ECB, $iv);
    return trim(base64_encode($crypttext)); //encode
  }

  // decrypt a value using AES. set_key assigns a 256-bit key
  // security could be increased by padding
  function decrypt ($value) {
    if (!$value)
      return false;

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
  function set_key ($keystr = false) {
    if ($this->encryptkey === false && $keystr === false)
      $keystr = '8b4004f3b6ff69397079bf7526f54e34e72dbc14ba10e9b098116c8521a3f0ee'; // just a default value
    if ($this->encryptkey === false || $keystr !== false)
      $this->encryptkey = pack('H*', $keystr);

    $this->encryptstr .= $keystr;

    return $keystr;
  }

  // generate a random byte stream, great for keys
  function generate_random_key ($bytes = 32) {
    $rand = openssl_random_pseudo_bytes($bytes);
    $str = NULL;
    for ($i = 0; $i < strlen($rand); $i++)
      $str .= str_pad(dechex(ord(substr($rand, $i, 1))), 2, '0', STR_PAD_LEFT);

    return $str . ' - ' . strlen($str);
  }

  function recreate_query_string ($start_char = '?') {
    if (count($_GET) > 0)
      return $start_char . http_build_query($_GET);
    else
      return false;
  }

  // display x time ago, $rcs is precision depth
  function time_ago ($tm, $rcs = 0) {
    $cur_tm = time(); 
    $dif = $cur_tm - $tm;
    $pds = array('second','minute','hour','day','week','month','year','decade');
    $lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);
    
    for ($v = count($lngh) - 1; ($v >= 0) && (($no = $dif / $lngh[$v]) <= 1); $v--);
      if ($v < 0)
        $v = 0;
    $_tm = $cur_tm - ($dif % $lngh[$v]);
  
    $no = ($rcs ? floor($no) : round($no)); // if last denomination, round
  
    if ($no != 1)
      $pds[$v] .= 's';
    $x = $no . ' ' . $pds[$v];
  
    if (($rcs > 0) && ($v >= 1))
      $x .= ' ' . $this->time_ago($_tm, $rcs - 1);
  
    return $x;
  }

  // display time in "4 minutes 10 seconds" format, $rcs is precision depth
  function time_disp ($secs, $rcs = 0, $denomname = false, $denomval = false) {
    if ($denomname === false)
      $denomname = array('second','minute','hour','day','week','month','year','decade');
    if ($denomval === false)
      $denomval = array(1,60,3600,86400,604800,2630880,31570560,315705600);
    
    for ($v = count($denomval) - 1; ($v >= 0) && (($no = $secs / $denomval[$v]) <= 1); $v--);
      if ($v < 0)
        $v = 0;
    $secs %= $denomval[$v];
  
    $no = ($rcs ? floor($no) : round($no)); // if last denomination, round
  
    if ($no != 1)
      $denomname[$v] .= 's';
    $x = $no . ' ' . $denomname[$v];
  
    if (($rcs > 0) && ($v >= 1))
      $x .= ' ' . $this->time_disp($secs, $rcs - 1, $denomname, $denomval);
  
    return $x;
  }

  function redirect ($url) {
    if (!headers_sent()) {    //If headers not sent yet... then do php redirect
      header('Location: '.$url); exit;
    } else {                    //If headers are sent... do java redirect... if java disabled, do html redirect.
      echo '<script type="text/javascript">';
      echo 'window.location.href="'.$url.'";';
      echo '</script>';
      echo '<noscript>';
      echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
      echo '</noscript>'; exit;
    }
  }//==== End -- Redirect

  function english_implode ($arr) {
    if (!is_array($arr))
      return false;
  
    switch (count($arr)) {
      case 0: return false;
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
  function even_dist_arr ($arr, $size) {
    if (!is_array($arr))
      return false;
  
    if ($size < 2)
      return false;
  
    if (count($arr) < 2)
      return array($arr[0]);
  
    if (count($arr) <= $size)
      return $arr;
  
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
* Common mySQL fcuntions.                                                      *
********************************************************************************
* myquery ($query, $line, $do_die = false)                                     *
* log_error ($query, $line, $do_die = false)                                   *
*******************************************************************************/
class sql extends common {

  // return mysql_query result, if mysql_query fails it calls log_error()
  function myquery ($query, $line, $do_die = false) {
    $result = mysql_query($query) or $this->log_error($query, $line, $do_die);
    return $result;
  } // end of myquery

  // stores an error into the errors database
  function log_error ($query, $line, $do_die = false) {
    $sql_error = mysql_error();
    $query = 'INSERT INTO errors (query, time, page, pagepar, post, line, error, user)
    VALUES ("'.mysql_real_escape_string($query).'", '.time().', "'.substr($_SERVER['PHP_SELF'], 0, 32).'", "'.mysql_real_escape_string($_SERVER['QUERY_STRING']).'", "'.mysql_real_escape_string(serialize($_POST)).'", '.$line.', "'.mysql_real_escape_string($sql_error).'", "'.mysql_real_escape_string($_SERVER['REMOTE_ADDR']).'")';
    mysql_query($query) or die('<font color="red"><b>'.mysql_error().' Log Error 1</b></font>');

    echo '<font color="red"><b>Error. Notify administrator.</b></font>';
    if ($do_die)
      die();
  } // end of log_error

}
/*
CREATE TABLE IF NOT EXISTS `errors` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `query` text NOT NULL,
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
  function get_ftype ($file) {
    $info = pathinfo($file);
    if(!isset($info['extension']))
      $info['extension'] = 'none';
    return strtolower($info['extension']);
  } // end of get_ftype


  // return a string of the file size with corrent denominator
  function filesz ($size, $denom = false, $round = 2, $binary = true) {
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
  function write_text ($file, $contents) {
    $fp = fopen($file, 'w');
    fwrite($fp, $contents);
    fclose($fp);
  } // end of write_text


  // removes illegal characters from file name
  function file_safe ($file) {
    $file = str_replace(' ', '_', $file);
    $illegal = array('/', '\\', ':', '*', '?', '"', '<', '>', '|', '%');
    return str_replace($illegal, '', $file);
  } // end of file_safe


  // delete any file or directory, returns false on any failure, or true if successful
  // requires PHP5
  function del ($source) {
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
  function cp ($source, $dest) {
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
* * (probably doesn't wo * anymor )                                              *
********************************************************************************
* image_size_str ($source)                                                     *
* image_size_mp ($source)                                                      *
* make_jpg ($source, $dest, $size)                                             *
*******************************************************************************/
class images {

  // returns string of size in readable format: "800x600"
  function image_size_str ($source) {
    if (!is_file($source))
      return false;
    if (false === ($size = getimagesize($source)))
      return false;
    return $size[0].'x'.$size[1];
  } // end of image_size_str


  // returns image's size in megapixels (or false if no file)
  function image_size_mp ($source) {
    if (!is_file($source))
      return false;
    if (false === ($size = getimagesize($source)))
      return false;
    return $size[0] * $size[1] / 1000000;
  } // end of image_size_mp


  // make a JPG image from another at a specific maximum pixel size
  // returns $dest (location of image) or false if no/bad source, or destination file already exists 
  // requires GD image library (extension)
  function make_jpg ($source, $dest, $size) {
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

  var $SPACER_LENGTH = 50;
  var $LINE_SPACER = 2;

  function searchreplace() {
    $loc = isset($_GET['loc']) ? trim($_GET['loc']) : NULL;
    if (substr($loc, -1) != '/')
      $loc .= '/';
    if (substr($loc, 0, 1) == '/')
      $loc = substr($loc, 1);
//echo $loc;
    if (!isset($_POST['search']) || $_POST['search'] == '') {
      echo '<div align="center">';
      echo '<form name="search" method="post" action="?loc='.urlencode($loc).'">';
      echo '<table cellpadding="3" cellspacing="3" border="0"><tr>
              <th align="center">PHP</th>
              <th align="center">HTML</th>
              <th align="center">HTM</th>
              <th align="center">CSS</th>
              <th align="center">TPL</th>
              <th align="center">TXT</th>
              <th align="left">Others</td>
            </tr>
            <tr>
              <td align="center"><input type="checkbox" name="filetype[]" value=".php" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".html" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".htm" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".css" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".tpl" checked></td>
              <td align="center"><input type="checkbox" name="filetype[]" value=".txt" checked></td>
              <td align="center"><input type="text" name="filetypelist" size="5"> (separate by commas ",")</td>
            </tr></table><br>';
      echo 'Search: <input type="text" size="25" maxlength="100" name="search"><br />';
      echo 'Sub-directories? <input type="checkbox" name="recurse"><br />';
      echo '<input type="submit" name="search_submit" value="Search">';
      echo '</form>';

      echo '<form name="search" method="post" action="?loc='.urlencode($loc).'">';
      echo 'Search for: <input type="text" size="25" maxlength="100" name="search"><br />';
      echo 'Repalce with: <input type="text" size="25" maxlength="100" name="replace"><br />';
      echo 'Sub-directories? <input type="checkbox" name="recurse"><br />';
      echo '<input type="submit" name="replace_submit" value="Replace">';
      echo '</form>';
      echo '</div>';

    } else {

      echo '<div align="center">';
      echo '<a href="?loc='.urlencode($loc).'">New Search</a><br />';
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

      $tot_finds = $this->srlist2($_POST['search'], $filetype_ar, (isset($_POST['replace']) ? $_POST['replace'] : false), (isset($_POST['recurse']) ? true : false), 0, $loc);

      echo 'Total search strings found: '.$tot_finds;
    }
  } // end of searchreplace

/*
  function srlist ($search, $replace = false, $recurse = false, $tot_finds = 0, $dir = NULL) {
    $file_ar1 = array_merge(glob($dir.'*.php'), glob($dir.'*.css'), glob($dir.'*.html'), glob($dir.'*.htm'), glob($dir.'*.tpl'));
    sort($file_ar1);
    $file_ar = array();
    foreach ($file_ar1 as $file)
      if ($file != basename($_SERVER['PHP_SELF']))//'replace.php')
        $file_ar[] = $file;

    foreach ($file_ar as $file) {
      $file_str = file_get_contents($file);
      $offset = 0;
      $finds = 0;
      $new_str = NULL;

      echo '<b>'.$file.'</b><br />';
      while (false !== ($key = strpos($file_str, $search, $offset))) {
        $finds++;
        $tot_finds++;
        if (false === $replace) {
          echo '<table cellpadding="2" cellspacing="0" border="1"><tr><td>';
          echo nl2br(str_replace(' ', '&nbsp;', htmlspecialchars(substr($file_str, $key - $this->SPACER_LENGTH, $this->SPACER_LENGTH)).
          '<b><u>'.htmlspecialchars(substr($file_str, $key, strlen($search))).'</u></b>'.
          htmlspecialchars(substr($file_str, $key + strlen($search), $this->SPACER_LENGTH)))) . '</td></tr></table><br />';
        } else {
          $new_str .= substr($file_str, $offset, $key - $offset);
          $new_str .= $replace;
        }

        $offset = $key + strlen($search);
      }
      if ($replace) {
        echo 'Strings replaced in file: '.$finds.'<br />';
        if ($finds > 0) {
          $new_str .= substr($file_str, $offset);
          file_put_contents($file, $new_str);
        }
      } else
        echo 'Search strings found in file: '.$finds.'<br />';
      echo '<br />';
    }

    if ($recurse) {
      $dir_ar = glob($dir.'*', GLOB_ONLYDIR);
      foreach ($dir_ar as $dir1)
        $tot_finds = $this->srlist($search, $replace, $recurse, $tot_finds, $dir1.'/');
    }
    return $tot_finds;
  } // end of srlist
*/

  function srlist2 ($search, $filetype_ar, $replace = false, $recurse = false, $tot_finds = 0, $dir = NULL) {
    //$file_ar1 = array_merge(glob($dir.'*.php'), glob($dir.'*.css'), glob($dir.'*.html'), glob($dir.'*.htm'), glob($dir.'*.tpl'));
    $file_ar1 = array();
    for ($i = 0; $i < count($filetype_ar); $i++) {
      //echo $dir.'*'.$filetype_ar[$i].'<br>';
      $file_ar1 += glob($dir.'*'.$filetype_ar[$i]);
      }
    sort($file_ar1);
    $file_ar = array();
    foreach ($file_ar1 as $file)
      if ($file != basename($_SERVER['PHP_SELF']))//'replace.php')
        $file_ar[] = $file;

    foreach ($file_ar as $file) {
      $fileline_ar = file($file);
      $offset = 0;
      $finds = 0;
      $newfile_ar = $fileline_ar;

      echo '<b>'.$file.'</b>';
      foreach($fileline_ar as $lineno => $line) {
        $offset = 0;
        //$new_str = NULL;
        while (false !== ($key = strpos($line, $search, $offset)) || (false === $replace && $this->match($search, substr($line, $offset)))) {
          $finds++;
          $tot_finds++;
          if (false === $replace) {
            echo '<br /><table cellpadding="0" cellspacing="0" border="1"><tr><td>';
            echo '<table cellpadding="0" cellspacing="0" border="0">';
            for($i = $lineno - $this->LINE_SPACER; $i <= $lineno + $this->LINE_SPACER; $i++)
              if (isset($fileline_ar[$i]))
                echo '<tr><td align="right" valign="top"><font size="-1" face="Courier New">'.($i + 1).':&nbsp;</font></td><td><font size="-1" face="Courier New">'.str_replace(' ', '&nbsp;', htmlspecialchars($fileline_ar[$i])).'</font></td></tr>';
            echo '</table></td></tr></table>';
          } else {
            /*$new_str = substr($line, $offset, $key - $offset);
            $new_str .= $replace;
            $new_str .= substr($line, $offset);*/
            $newfile_ar[$lineno] = str_replace($search, $replace, $line);
          }
          $offset = (false === $replace ? strlen($line) : $key + strlen($search));
        }

        /*if ($replace) {
          $newfile_ar[$lineno] = $new_str;
          echo $new_str;
        }*/
      }

      if ($replace) {
        echo '<br />Strings replaced in file: '.$finds.'<br />';
        if ($finds > 0)
          file_put_contents($file, implode('', $newfile_ar));
      } else
        echo ($finds == 0 ? '<br />' : '').'Search strings found in file: '.$finds.'<br />';
      echo '<br />';
    }

    if ($recurse) {
      $dir_ar = glob($dir.'*', GLOB_ONLYDIR);
      foreach ($dir_ar as $dir1)
        $tot_finds = $this->srlist2($search, $replace, $recurse, $tot_finds, $dir1.'/');
    }
    return $tot_finds;
  } // end of srlist2


  function match($pattern, $string) {
    return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
  } // end of match

}


/*******************************************************************************
* Displays a caledar month (specified month or current if left blank. Can put  *
* a link on each day of the month. Use ^d as day placeholder, or ^/^/^ as      *
* m/d/y placeholder, it will be converted to that day's value in the link. You *
* can highlight a day of the month with $day (by default, today).              *
********************************************************************************
* cal( [$month [, $day [, $ghost_adjacent [, $href [, $tableclass ]]]]] )      *
*******************************************************************************/
class calendar {

  /***** Explaination of parameters: *****
  $month is which month to display, but it is relative to the current month, 0 is this month, -1 is last month, 12 is this month next year, etc.
  $day is the day of the selected month to highlight (bold), if false, and $month is this month, today (according to the system) will be highlighted automatically, disable hy setting to 0.
  $ghost_adjacent will fill in blank cells in the table of the month with adjacent month's dates. Use 'pre' or 'post' to enable previous or folling month respectively, or true to enable both.
  $href makes the day a link of your choosing. Use ^/^/^ to have a MM/DD/YYYY formatted day put in your link, or use ^M for MM, ^m for M, ^D for DD, ^d for D, ^y for YY, and ^Y for YYYY formats.
  $tableclass simply adds a class atribute to the table with this value.
  ***************************************/
  function cal ($month = false, $day = false, $ghost_adjacent = true, $href = false, $tableclass = false) {
    $str = NULL;

    if ($day === false && !$month)                                              // if day is not set, and $month is current month (0 or false)
      $day = date('j');                                                         // mark today as highlighted day
    $month = date('n') + $month;                                                // set month relatively
    $tsmonth = mktime(0, 0, 0, $month, 1);                                      // create time stamp
    $days_in_month = date('t', $tsmonth);                                       // determine number of days in this month

    $str .= '<table'.($tableclass ? ' class="'.$tableclass.'"' : '').'>
  <tr><th colspan="7" align="center">'.date('F Y', $tsmonth).'</th></tr>
  <tr>'."\n";

    if (($col = date('w', $tsmonth)) != 0) {
      if ($ghost_adjacent === true or $ghost_adjacent == 'pre')
        for ($i = 0 - $col + 1; $i <= 0; $i++)
          $str .= $this->prep_day($month, $i, $day, true, $href, $days_in_month);
      else
        $str .= '    <td colspan="'.$col.'"></td>'."\n";
    }

    for ($d = 1; $d <= $days_in_month; $d++)
      $str .= $this->prep_day($month, $d, $day, false, $href, $days_in_month);

    if (date('w', mktime(0, 0, 0, $month, $d - 1)) != 6) {
      if ($ghost_adjacent === true or $ghost_adjacent == 'post')
        for ($i = 1; $i <= (6 - date('w', mktime(0, 0, 0, $month, $d - 1))); $i++)
          $str .= $this->prep_day($month + 1, $i, $day, true, $href, $days_in_month);
      else
        $str .= '    <td colspan="'.(6 - date('w', mktime(0, 0, 0, $month, $d - 1))).'"></td>'."\n";
    }

    $str .= '  </tr>
</table>'."\n\n";
    return $str;
  }

  function prep_day ($month, $d, $day, $ital, $href, $days_in_month) {
    $str = NULL;
    if ($href) {
      $link = str_replace(array('^/^/^', '^M', '^m', '^D', '^d', '^Y', '^y'),
      array(date('m/d/Y', mktime(0, 0, 0, $month, $d)),
      date('F', mktime(0, 0, 0, $month, $d)),
      date('m', mktime(0, 0, 0, $month, $d)),
      date('j', mktime(0, 0, 0, $month, $d)),
      date('d', mktime(0, 0, 0, $month, $d)),
      date('Y', mktime(0, 0, 0, $month, $d)),
      date('y', mktime(0, 0, 0, $month, $d))), $href);
    }
    $str .= '    <td align="center">'.($href ? '<a href="'.$link.'">' : '').($ital ? '<i>': '').($day == date('j', mktime(0, 0, 0, $month, $d)) ? '<b>'.date('j', mktime(0, 0, 0, $month, $d)).'</b>' : date('j', mktime(0, 0, 0, $month, $d))).($ital ? '</i>': '').($href ? '</a>' : '').'</td>'."\n";
    if (date('w', mktime(0, 0, 0, $month, $d)) == 6 && date('j', mktime(0, 0, 0, $month, $d)) != $days_in_month)
      $str .= '  </tr>'."\n".'  <tr>'."\n";
    return $str;
  }
}


/*******************************************************************************
* Login control.                                                               *
********************************************************************************
* logintest ()                                                                 *
* create_user ($user, $email, $pass, $groupid, $emailconfirm = '1')            *
* login_form ($header = false, $add_action = NULL, $keep_query = true, $action = false) *
* sessdestroy ()                                                               *
* bad_login ($type, $user)                                                     *
* log_visits ()                                                                *
* ipallowdeny ($ipallow_ar, $ipblock_ar)                                       *
*******************************************************************************/
class login extends sql {
  var $login = false;
  var $groupid = false;
  var $TABLE_PREFIX = 's_';
  var $SHA1_SEED1 = '"o7U';
  var $SHA1_SEED2 = '>?2Z';
  var $SITE_NAME = 'tvg';
  var $error_ar = array();
  var $REMEMBER_ME_TIMEOUT = 1209600;

  // checks session data or post information against database login information
  // returns false on failure or user's id if successful
  function logintest () {

    //$GLOBALS['disp_login'] = true;
    $sets['session_timeout'] = 28800;                                           // eight hours
    $sets['login']['BAD_LOGIN_COOKIE_LIMIT'] = 20;
    $sets['login']['BAD_LOGIN_LIMIT'] = 5;
    $sets['login']['BAD_LOGIN_TIME'] = 300;

    ini_set("session.cookie_lifetime", $sets['session_timeout']);
    ini_set('session.gc_maxlifetime', $sets['session_timeout']);
    session_set_cookie_params($sets['session_timeout']);

    if (isset($_COOKIE[$this->SITE_NAME])) {
      $stored_sessid = $this->decrypt($_COOKIE[$this->SITE_NAME]);
      if (strlen($stored_sessid) == 26 || strlen($stored_sessid) == 32)         // in case encryption key changes
        session_id($stored_sessid);
    }

    session_start();

    // destroy all session info when user logs out
    if (isset($_GET['logout'])) {
      $this->sessdestroy();
      $this->redirect($_SERVER['PHP_SELF']);
      //$this->error_ar[] = 'DEVELOPMENT: logged out'; // DELETE
    }
    // check for bad login attempts
    $query = 'SELECT COUNT(1) FROM '.$this->TABLE_PREFIX.'badlogins WHERE date >= '.(time() - $sets['login']['BAD_LOGIN_TIME']).' AND (ip = "'.mysql_real_escape_string($_SERVER['REMOTE_ADDR']).'"'.(isset($_POST['user']) ? ' OR LOWER(user) = "'.mysql_real_escape_string(strtolower(substr($this->username_filter($_POST['user']), 0, 32))).'"' : '').')';
    $result = $this->myquery($query, __LINE__, true);
    list($bad_login_count) = mysql_fetch_row($result);

    if (isset($_POST['login']) && isset($_POST['user']) && isset($_POST['pw']) && $bad_login_count < $sets['login']['BAD_LOGIN_LIMIT']) {
      $user = @substr($_POST['user'], 0, 32);
      $pw = sha1($this->SHA1_SEED1.@substr($_POST['pw'], 0, 128).$this->SHA1_SEED2);
      $login_type = 1;
    } elseif ($bad_login_count >= $sets['login']['BAD_LOGIN_LIMIT'])
      $this->error_ar[] = 'Too many login attempts. Locked out for '.ceil($sets['login']['BAD_LOGIN_TIME'] / 60).' minutes.';

    if (!isset($user) && !isset($pw) && $bad_login_count < $sets['login']['BAD_LOGIN_COOKIE_LIMIT']) {
      if (!empty($_SESSION['user']) && !empty($_SESSION['pw'])) {
        $user = substr($this->username_filter($_SESSION['user']), 0, 32);
        $pw = substr($this->alpha_numerics($_SESSION['pw']), 0, 40);
        $login_type = 2;
      }
    } elseif ($bad_login_count >= $sets['login']['BAD_LOGIN_COOKIE_LIMIT'])
      $this->error_ar[] = 'Too many login attempts. Locked out for '.floor($sets['login']['BAD_LOGIN_TIME'] / 60).' minutes.';

    if (!isset($user) && !isset($pw) && $bad_login_count < $sets['login']['BAD_LOGIN_COOKIE_LIMIT']) {
      if (!empty($_COOKIE[$this->SITE_NAME . '-user']) && !empty($_COOKIE[$this->SITE_NAME . '-pw'])) {
        $user = substr($this->username_filter($this->decrypt($_COOKIE[$this->SITE_NAME . '-user'])), 0, 32);
        $pw = substr($this->alpha_numerics($this->decrypt($_COOKIE[$this->SITE_NAME . '-pw'])), 0, 40);
        $login_type = 3;
      }
    } elseif ($bad_login_count >= $sets['login']['BAD_LOGIN_COOKIE_LIMIT'])
      $this->error_ar[] = 'Too many login attempts. Locked out for '.floor($sets['login']['BAD_LOGIN_TIME'] / 60).' minutes.';

    if (isset($user) && isset($pw)) {
      $user = $this->username_filter($user);
      //$pw = preg_replace('/[^a-zA-Z0-9s]/', '', $pw);
      /*echo $user.'<br />';
      echo $pw.'<br />';*/
      $query = 'SELECT id, ip, sessname, groupid, emailconfirm FROM '.$this->TABLE_PREFIX.'users WHERE LOWER(user) = "'.mysql_real_escape_string(strtolower($user)).'" AND password = "'.mysql_real_escape_string($pw).'" AND deleted = 0 LIMIT 1';
      $result = $this->myquery($query, __LINE__, true);
      if (mysql_num_rows($result) > 0) {
        list($userid, $ip, $sessname, $groupid, $emailconfirm) = mysql_fetch_row($result);
        if ($emailconfirm == '1') {
          $_SESSION['user'] = $user;
          $_SESSION['pw'] = $pw;
  
          if ($login_type == 1) {
            $query = 'UPDATE '.$this->TABLE_PREFIX.'users SET ip = "'.mysql_real_escape_string($_SERVER['REMOTE_ADDR']).'", sessname = "'.mysql_real_escape_string(session_id()).'", lastlogin = '.time().', totlogins = totlogins + 1 WHERE id = '.$userid.' LIMIT 1';
            $this->myquery($query, __LINE__, true);
            if (isset($_POST['rememberme'])) {
              setcookie($this->SITE_NAME, $this->encrypt(session_id()), time() + $this->REMEMBER_ME_TIMEOUT);
              setcookie($this->SITE_NAME . '-user', $this->encrypt($user), time() + $this->REMEMBER_ME_TIMEOUT);
              setcookie($this->SITE_NAME . '-pw', $this->encrypt($pw), time() + $this->REMEMBER_ME_TIMEOUT);
            } else
              setcookie($this->SITE_NAME, $this->encrypt(session_id()));
          } else {
            if ($ip != $_SERVER['REMOTE_ADDR'] || $sessname != $this->decrypt($_COOKIE[$this->SITE_NAME])) {
              $this->sessdestroy();
              $this->bad_login($login_type, $user);
              //$this->error_ar[] = 'DEVELOPMENT: Bad ip or cookie<br>'.$ip.'--'.$_SERVER['REMOTE_ADDR'].'<br>'.$sessname.'--'.$_COOKIE[$this->SITE_NAME]; // DELETE
              return false;
            }
          }
          settype($userid, 'int');
          $this->login = $userid;
          settype($groupid, 'int');
          $this->groupid = $groupid;
          return $userid;
        } else {
          $this->sessdestroy();
          $this->error_ar[] = 'Please <a href="confirm.php?resend=1">confirm your email address</a>. <a href="contactus.php">Contact us</a> if you already have.';
          return false;
        }
      } else {
        $this->sessdestroy();
        $this->bad_login($login_type, $user);
        $this->error_ar[] = 'Incorrect username or password.';
        return false;
      }
    } else {
      //$this->error_ar[] = 'DEVELOPMENT: Not logged in...'; // DELETE
      $this->sessdestroy();
      return false;
    }
  } // end of logintest

  // create a user, add it to the database, return id on success, return false on failure
  function create_user ($user, $email, $pass, $groupid, $emailconfirm = '1') {
    $user = $this->username_filter(@substr(trim($user), 0, 32)); // clean up user
    $pw = sha1($this->SHA1_SEED1.@substr($pass, 0, 128).$this->SHA1_SEED2);
    $query = 'SELECT deleted FROM '.$this->TABLE_PREFIX.'users WHERE LOWER(user) = "'.mysql_real_escape_string(strtolower($user)).'"';
    $result = $this->myquery($query, __LINE__);
    if (list($deleted) = mysql_fetch_row($result))
      $query = 'UPDATE '.$this->TABLE_PREFIX.'users SET email = "'.mysql_real_escape_string(trim($email)).'", password = "'.mysql_real_escape_string($pw).'", groupid = '.(int)$groupid.', emailconfirm = "'.mysql_real_escape_string($emailconfirm).'", created = '.time().', deleted = 0 WHERE LOWER(user) = "'.mysql_real_escape_string(strtolower($user)).'" LIMIT 1';
    else
      $query = 'INSERT INTO '.$this->TABLE_PREFIX.'users (user, email, password, groupid, emailconfirm, created) VALUES ("'.mysql_real_escape_string($user).'", "'.mysql_real_escape_string(trim($email)).'", "'.mysql_real_escape_string($pw).'", '.$groupid.', "'.mysql_real_escape_string($emailconfirm).'", '.time().')';
    $this->myquery($query, __LINE__);
  }

  // display login form
  function login_form ($header = false, $add_action = NULL, $keep_query = true, $action = false) {
    if ($action === false)
      $action = $_SERVER['PHP_SELF'];
  
    echo '  <div align="center">
    <form name="login" method="post" action="'.$action . $add_action . ($keep_query ? $this->recreate_query_string($add_action ? '&' : '?') : '').'">
      <table class="loginform">
        <tr>
          <th colspan="2" align="center">'.($header ? $header : 'Login to access your account information').'</th>
        </tr>';
    if (isset($_POST['login'])) {
      echo '<tr><td colspan="2" align="center"><div class="error">';
      for ($i = 0; $i < count($this->error_ar); $i++)
        echo $this->error_ar[$i].'<br>';
      echo '</div></td></tr>';
    }
    echo '        <tr>
          <td align="right">Username:</td>
          <td align="left"><input name="user" type="text" class="inputtext" size="8"></td>
        </tr>
        <tr>
          <td align="right">Password:</td>
          <td align="left"><input name="pw" type="password" class="inputtext" size="8"></td>
        </tr>
        <tr>
          <td align="right"><label for="rememberme">Remember Me:</label></td>
          <td align="left"><input id="rememberme" name="rememberme" type="checkbox" checked="checked"></td>
        </tr>
        <tr>
          <td colspan="2" align="center">
            <input type="submit" name="login" value="Login >">
          </td>
        </tr>
      </table>
    </form>
    <a id="registerlink" href="register.php">Register New Account</a><br>
    <a href="forgotpw.php">Forgot Password</a>
  </div>';
  } // end of login_form
  
  
  // fully destroy session and all session info
  function sessdestroy () {
    $login = false;
    $groupid = false;
    $_SESSION = array();
    setcookie($this->SITE_NAME, '', time() - 86400);
    setcookie($this->SITE_NAME . '-user', '', time() - 86400);
    setcookie($this->SITE_NAME . '-pw', '', time() - 86400);
    if (@session_destroy())
      return true;
    else
      return false;
  } // end of sessdestroy


  // log an unseccessful login
  function bad_login ($type, $user) {
    $query = 'INSERT INTO '.$this->TABLE_PREFIX.'badlogins (user, ip, date, failtype) VALUES ("'.mysql_real_escape_string($user).'", "'.mysql_real_escape_string($_SERVER['REMOTE_ADDR']).'", '.time().', '.$type.')';
    $this->myquery($query, __LINE__, true);
  } // end of bad_logins


  // log visits, registered users get visits logged in their own field
  function log_visits () {
    if ($this->login) {
      $query = 'UPDATE '.$this->TABLE_PREFIX.'users SET lastvisit = '.time().', totvisits = totvisits + 1 WHERE id = '.$this->login.' LIMIT 1';
      $this->myquery($query, __LINE__);
    }
  } // end of log_visits


  // whitelist and/or blacklist IPs -- IPs can contain wild cards ("*")
  function ipallowdeny ($ipallow_ar, $ipblock_ar) {
    if (count($ipallow_ar) > 0) {
      $block = true;

      $i = 0;
      while (isset($ipallow_ar[$i]) && $block)
        if (fnmatch($ipallow_ar[$i++], $_SERVER['REMOTE_ADDR']))
          $block = false;
      if ($block)
        return false;
    }
    
    $i = 0;
    while (isset($ipblock_ar[$i]))
      if (fnmatch($ipblock_ar[$i++], $_SERVER['REMOTE_ADDR']))
        return false;
    
    return true;
  }
} // end of login class

/*****[ CODE FOR LOGIN ]********************************************************
***** SQL Tables: *****
CREATE TABLE IF NOT EXISTS `s_badlogins` (
  `id` int(9) NOT NULL auto_increment,
  `user` varchar(32) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `date` int(10) NOT NULL,
  `failtype` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Keep track of unsuccessful logins';

***** PHP Code: *****
$logintest = new login;
if (false === ($userid = $logintest->logintest()))
  die($logintest->login_form());
$logintest->log_visits();
*******************************************************************************/
