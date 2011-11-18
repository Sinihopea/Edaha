<?php
/*
 * This file is part of kusaba.
 *
 * kusaba is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * kusaba is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *  
 * You should have received a copy of the GNU General Public License along with
 * kusaba; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
 /*
  * Static functions that don't fit anywhere else
  * Last Updated: $Date$
  
  * @author 		$Author$
  
  * @package		kusaba
  
  * @version		$Revision$
  *
  */
 
class kxFunc {
    
    /**
     * Cleans input
     *
     * @access	public
     * @param	array 	Input data
     * @param  int           Iteration
     * @return	array 	Cleaned data
     */
    static public function cleaninput(&$data, $i = 0) {
        
        // Don't parse arrays deeper than 10, as it's most likely someone trying to crash PHP
        if ($i > 10) {
            return;
        }
        
        foreach($data as $k => $v) {
            if (is_array($v) ) {
                self::cleaninput($data[$k], ++$i);
            }
            else {
                // Decimal places. Script kiddies might think they can try to access files outside the board
                $v = str_replace("&#46;&#46;/", "../", $v);      
                
                // This litte bugger changes the formatting to be right-to-left, like on a hebrew locale.
                $v = str_replace('&#8238;', '', $v);
                
                // Null byte characters can mess with formatting as well, so we remove them
                $v = str_replace("\x00", '', $v);
                $v = str_replace(chr('0'), '', $v);
                $v = str_replace("\0", '', $v);
                
                $data[$k] = $v;
            }
        }
    }
    
    /**
     * Recursively cleans keys and values and
     * inserts them into the input array
     *
     * @access	public
     * @param	mixed		Input data
     * @param	array		Parsed data
     * @param	integer		Iteration
     * @return	array 		Cleaned data
     */
    static public function parseinput(&$data, $input=array(), $i = 0) {
        if ($i > 10) {
            return $input;
        }
        
        foreach($data as $k => $v) {
            if (is_array($v)) {
                $input[$k] = self::parseinput($data[$k], array(), $i++);
            }
            else {
                $k = self::cleanInputKey($k);
                $v = self::cleanInputVal($v, false);
                
                $input[$k] = $v;
            }
        }
        return $input;
    }
    /**
     * Clean up input key
     *
     * @access	public
     * @param	string		Key name
     * @return	string		Cleaned key name
     */
    static public function cleanInputKey($key) {
        if ($key == ""){
            return "";
        }
        
        $key = htmlspecialchars(urldecode($key));
        $key = str_replace("..", "", $key);
        $key = preg_replace( "/\_\_(.+?)\_\_/", "", $key );
        $key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );
        
        return $key;
    }
    
    /**
     * Clean up input data
     *
     * @access	public
     * @param	string		Input
     * @return	string		Cleaned Input
     */
    static public function cleanInputVal($txt) {
        if (empty($txt)){
            return "";
        }
        $txt = kxFunc::strip_magic($txt);
        
        $search = array("&#032;",
            "\r\n", "\n\r", "\r",
            "&",
            "<!--",
            "-->",
            "<",
            ">",
            "\n",
            '"',
            "<script",
            "$",
            "!",
        "'");
        $replace = array( " ",
            "\n", "\n", "\n",
            "&amp;",
            "&#60;&#33;--",
            "--&#62;",
            "&lt;",
            "&gt;",
            "<br />",
            "&quot;",
            "&#60;script",
            "&#036;",
            "&#33;",
        "&#39;");
        $txt = str_replace( $search, $replace, $txt );
        
        $txt = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $txt );
        $txt = preg_replace("/&#(\d+?)([^\d;])/i", "&#\\1;\\2", $txt );		
        
        return $txt;
    }
    
    /**
     * Returns only alphanumeric characters
     *
     * @access	public
     * @param	string		Input String
     * @param	string		Additional characters
     * @return	string		Parsed string
     */
    static public function alphanum($txt, $extra="") {
        if ( $extra ){
            $extra = preg_quote( $extra, "/" );
        }
        
        return preg_replace( "/[^a-zA-Z0-9\-\_" . $extra . "]/", "" , $txt );
    }
    
    /**
     * Generates a path for an application, with module if applicable
     *
     * @access	public
     * @param	string		application
     * @param	string		module (optional)
     * @return	mixed		Directory to app or module (or false if error)
     */
    static public function getAppDir( $app, $module='' ) {
        if (empty($app) || !is_string($app)) {
            return FALSE;
        }
        
        $appFolder       = KX_ROOT . '/application/' . $app;
        $modulesFolder = (defined("IN_MANAGE") && IN_MANAGE) ? 'manage' : 'public';
        
        if ( $module ) {
            return $appFolder . "/" . $modulesFolder . "/" . $module;
        }
        else{
            return $appFolder;
        }
    }
    
    /**
     * Remove slashes if magic_quotes enabled
     *
     * @access	public
     * @param	string		Input String
     * @return	string		Parsed string
     */
    static public function strip_magic($t) {
        if (get_magic_quotes_gpc()){
            $t = stripslashes($t);
            $t = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $t );
        }
        return $t;
    }
    
    /* Depending on the configuration, use either a meta refresh or a direct header */
    static public function doRedirect($url, $ispost = false, $file = '') {
        $headermethod = true;
        
        if ($headermethod) {
            if ($ispost) {
                header('Location: ' . $url);
            } else {
                die('<meta http-equiv="refresh" content="1;url=' . $url . '">');
            }
        } else {
            if ($ispost && $file != '') {
                echo sprintf(_gettext('%s uploaded.'), $file) . ' ' . _gettext('Updating pages.');
            } elseif ($ispost) {
                echo _gettext('Post added.') . ' ' . _gettext('Updating pages.'); # TEE COME BACK
            } else {
                echo '---> ---> --->';
            }
            die('<meta http-equiv="refresh" content="1;url=' . $url . '">');
        }
    }
    static public function showError($errormsg, $extended = '') {
        
        $twigData['styles'] = explode(':', kxEnv::Get('kx:styles:menustyles'));
        $twigData['errormsg'] = $errormsg;
        
        if ($extended != '') {
            $twigData['errormsgext'] = '<br /><div style="text-align: center;font-size: 1.25em;">' . $extended . '</div>';
        }
        
        kxTemplate::output('error', $twigData);
        
        die();
    }
    
    /**
     * Check if the supplied md5 file hash is currently recorded inside of the database, attached to a non-deleted post
     */
    static public function checkMD5($md5, $boardid) {
        
        $matches = kxDB::getinstance()->select("posts");
        $matches->innerJoin("post_files", "", "file_post = post_id AND file_board = post_board");
        $matches = $matches->fields("posts", array("post_id", "post_parent"))
            ->condition("post_board", $boardid)
            ->condition("post_deleted", 0)
            ->condition("file_md5", $md5)
            ->range(0, 1)
            ->execute()
            ->fetchAll();
        if (count($matches) > 0) {
            $real_parentid = ($matches[0]->post_parent == 0) ? $matches[0]->post_id : $matches[0]->post_parent;
            return array($real_parentid, $matches[0]->post_id);
        }
        
        return false;
    }
    
    static private function get_rnd_iv($iv_len) {
        $iv = '';
        while ($iv_len-- > 0) {
            $iv .= chr(mt_rand() & 0xff);
        }
        return $iv;
    }
    static public function encryptMD5($plain_text, $password, $iv_len = 16) {
        $plain_text .= "\x13";
        $n = strlen($plain_text);
        if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
        $i = 0;
        $enc_text = self::get_rnd_iv($iv_len);
        $iv = substr($password ^ $enc_text, 0, 512);
        while ($i < $n) {
            $block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
            $enc_text .= $block;
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        return base64_encode($enc_text);
    }
    static public function decryptMD5($enc_text, $password, $iv_len = 16) {
        $enc_text = base64_decode($enc_text);
        $n = strlen($enc_text);
        $i = $iv_len;
        $plain_text = '';
        $iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
        while ($i < $n) {
            $block = substr($enc_text, $i, 16);
            $plain_text .= $block ^ pack('H*', md5($iv));
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        return preg_replace('/\\x13\\x00*$/', '', $plain_text);
    }
    /**
     * Calculate the number of pages which will be needed for the supplied number of posts
     *
     * @param integer $boardtype Board type
     * @param integer $numposts Number of posts
     * @return integer Number of pages required
     */
    static public function pageCount($boardtype, $numposts) {
        if ($boardtype==1) {
            return (floor($numposts/kxEnv::Get('kx:display:txtthreads')));
        } elseif ($boardtype==3) {
            return (floor($numposts/30));
        }
        
        return (floor($numposts/kxEnv::Get('kx:display:imgthreads')));
    }
    /**
     * Gets information about the filetype provided, which is specified in the manage panel
     *
     * @param string $filetype Filetype
     * @return array Filetype image, width, and height
     */
    static public function getFileTypeInfo($filetype) {
        
        $results = kxDB::getinstance()->select("filetypes")
            ->fields("filetypes", array("type_image", "type_image_width", "type_image_height"))
            ->condition("type_ext", $filetype)
            ->range(0, 1)
            ->execute()
            ->fetchAll();
        if (count($results) > 0) {
            foreach($results AS $line) {
                return array($line->type_image,$line->type_image_width,$line->type_image_height);
            }
        } else {
            /* No info was found, return the generic icon */
            return array('generic.png',48,48);
        }
    }
    
    static public function formatDate($timestamp, $type = 'post', $locale = 'en', $email = '') {
        $output = '';
        if ($email != '') {
            $output .= '<a href="mailto:' . $email . '">';
        }
        
        if ($type == 'post') {
            if ($locale == 'ja') {
                /* Format the timestamp japanese style */
                $fulldate = strftime ("%Yy%mm%dd(DAYOFWEEK) %HH%MM%SS", $timestamp);
                $dayofweek = strftime('%a', $timestamp);
                
                
                /* I don't like this method, but I can't rely on PHP's locale settings to do it for me... */
                switch ($dayofweek) {
                    case 'Sun':
                        $dayofweek = '&#26085;';
                        break;
                        
                    case 'Mon':
                        $dayofweek = '&#26376;';
                        break;
                        
                    case 'Tue':
                        $dayofweek = '&#28779;';
                        break;
                        
                    case 'Wed':
                        $dayofweek = '&#27700;';
                        break;
                        
                    case 'Thu':
                        $dayofweek = '&#26408;';
                        break;
                        
                    case 'Fri':
                        $dayofweek = '&#37329;';
                        break;
                        
                    case 'Sat':
                        $dayofweek = '&#22303;';
                        break;
                        
                    default:
                        // The date must be in the correct language already, so let's convert it to unicode if it isn't already.
                        $dayofweek = mb_convert_encoding($dayofweek, "UTF-8", "JIS, eucjp-win, sjis-win");
                    break;
                    
                }
                $fulldate = self::formatJapaneseNumbers($fulldate);
                //Convert the symbols for year, month, etc to unicode equivalents. We couldn't do this above beause the numbers would be formatted to japanese.
                $fulldate = str_replace(array("y","m","d","H","M","S"), array("&#24180;","&#26376;","&#26085;","&#26178;","&#20998;","&#31186;"), $fulldate);
                $fulldate = str_replace('DAYOFWEEK', $dayofweek, $fulldate);
                return $output.$fulldate.(($email != '') ? ('</a>') : (""));
            } else {
                /* Format the timestamp english style */
                return $output.date('y/m/d(D)H:i', $timestamp).(($email != '') ? ('</a>') : (""));
            }
        }
        
        return $output.date('y/m/d(D)H:i', $timestamp).(($email != '') ? ('</a>') : (""));
    }
    static public function formatJapaneseNumbers($input) {
        $patterns = array('/1/', '/2/', '/3/', '/4/', '/5/', '/6/', '/7/', '/8/', '/9/', '/0/');
        $replace = array('１', '２', '３', '４', '５', '６', '７', '８', '９', '０');
        
        return preg_replace($patterns, $replace, $input);
    }
    
    /* <3 coda for this wonderful snippet
     print $contents to $filename by using a temporary file and renaming it */
    static public function outputToFile($filename, $contents, $board) {
        $tempfile = tempnam(KX_BOARD . '/' . $board . '/res', 'tmp'); /* Create the temporary file */
        $fp = fopen($tempfile, 'w');
        fwrite($fp, $contents);
        fclose($fp);
        /* If we aren't able to use the rename function, try the alternate method */
        if (!@rename($tempfile, $filename)) {
            copy($tempfile, $filename);
            unlink($tempfile);
        }
        chmod($filename, 0664); /* it was created 0600 */
    }
    
    static public function getManageSession() {
        
        $_session = (isset(kxEnv::$request['sid'])) ?  kxEnv::$request['sid'] : '' ;
        
        // Do we have a session at all?
        if (!$_session) {
            return false;
        }
        else {
            // So far so good, let's check it
            $session_data = kxDB::getInstance()->select("manage_sessions")
                ->fields("manage_sessions")
                ->condition("session_id", $_session)
                ->execute()
                ->fetchAll();
            if ( empty($session_data[0]->session_id) ) {
                // No session found
                return false;
            }
            else if (empty($session_data[0]->session_staff_id)) {
                // No staffer assigned to that sid
                return false;
            }
            else {
                // Alright! Looks good so far. let's do some triple and quadruple checking though.
                
                // Check if the user ID is valid
                $userid = kxDB::getInstance()->select("staff")
                    ->fields("staff", array("user_id"))
                    ->condition("user_id", $session_data[0]->session_staff_id)
                    ->execute()
                    ->fetchField();
                
                if (!$userid) {
                    // Welp...
                    return false;
                }
                
                // Now, we'll check the IP address to see if it matches the stored one.
                $first_ip  = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", $session_data[0]->session_ip );
                $second_ip = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", $_SERVER['REMOTE_ADDR'] );
                
                if ( $first_ip != $second_ip ) {
                    // Man you just can't win today can you?
                    return false;
                }
                // Okay, last one I promise. Is our session expired?
                if ($session_data[0]->session_last_action < (time() - 60*60)) {
                    // Argh!!
                    return false;
                }
                
                // Congratulations!
                return true;
            }
        }
    }
    
    static public function convertBytes($number) {
        $len = strlen($number);
        if($len < 4) {
            return sprintf("%dB", $number);
        } elseif($len <= 6) {
            return sprintf("%0.2fKB", $number/1024);
        } elseif($len <= 9) {
            return sprintf("%0.2fMB", $number/1024/1024);
        }
        
        return sprintf("%0.2fGB", $number/1024/1024/1024);						
    }

    static public function fullBoardList() {
      $sections = kxDB::getInstance()->select("sections")
                                     ->fields("sections")
                                     ->orderBy("section_order")
                                     ->execute()
                                     ->fetchAll();

      $boards = kxDB::getInstance()->select("boards")
                                   ->fields("boards", array('board_name', 'board_desc'))
                                   ->where("board_section = ?")
                                   ->orderBy("board_order")
                                   ->build();

      // Add boards to an array within their section
      foreach ($sections as &$section) {
        $boards->execute(array($section->id));
        $section->boards = $boards->fetchAll();
      }
      
      // Prepend boards with no section
      $boards->execute(array(0));
      return(array_merge($boards->fetchAll(), $sections));
    }
}

class kxBans {
    
    /* Perform a check for a ban record for a specified IP address */
    static public function BanCheck($ip, $board = '', $force_display = false) {
        return false;
        if (!isset($_COOKIE['tc_previousip'])) {
            $_COOKIE['tc_previousip'] = '';
        }
        
        $bans = Array();
        $results = kxDB::getinstance()->query("SELECT * FROM `".kxEnv::Get('kx:db:prefix')."banlist` WHERE ((`type` = '0' AND ( `ipmd5` = '" . md5($ip) . "' OR `ipmd5` = '". md5($_COOKIE['tc_previousip']) . "' )) OR `type` = '1') AND (`expired` = 0)" );
        if (count($results)>0) {
            foreach($results AS $line) {
                if(($line['type'] == 1 && strpos($ip, md5_decrypt($line['ip'], kxEnv::Get('kx:misc:randomseed'))) === 0) || $line['type'] == 0) {
                    if ($line['until'] != 0 && $line['until'] < time()){
                        kxDB::getinstance()->exec("UPDATE `".kxEnv::Get('kx:db:prefix')."banlist` SET `expired` = 1 WHERE `id` = ".$line['id']);
                        $line['expired'] = 1;
                        $this->UpdateHtaccess();
                    }
                    if ($line['globalban']!=1) {
                        if ((in_array($board, explode('|', $line['boards'])) || $board == '')) {
                            $line['appealin'] = substr(timeDiff($line['appealat'], true, 2), 0, -1);
                            $bans[] = $line;
                        }
                    } else {
                        $line['appealin'] = substr(timeDiff($line['appealat'], true, 2), 0, -1);
                        $bans[] = $line;
                    }
                }
            }
        }
        if(count($bans) > 0){
            kxDB::getinstance()->exec("END TRANSACTION");
            echo $this->DisplayBannedMessage($bans);
            die();
        }
        
        if ($force_display) {
            /* Instructed to display a page whether banned or not, so we will inform them today is their rucky day */
            echo '<title>'._gettext('YOU ARE NOT BANNED!').'</title><div align="center"><img src="'. kxEnv::Get('kx:paths:main:folder') .'youarenotbanned.jpg"><br /><br />'._gettext('Unable to find record of your IP being banned.').'</div>';
        } else {
            return true;
        }
    }
    
    /* Add a ip/ip range ban */
    static public function BanUser($ip, $modname, $globalban, $duration, $boards, $reason, $staffnote, $appealat=0, $type=0, $allowread=1, $proxyban=false) {
        
        if ($duration>0) {
            $ban_globalban = '0';
        } else {
            $ban_globalban = '1';
        }
        if ($duration>0) {
            $ban_until = time()+$duration;
        } else {
            $ban_until = '0';
        }
        
        kxDB::getinstance()->exec("INSERT INTO `".kxEnv::Get('kx:db:prefix')."banlist` ( `ip` , `ipmd5` , `type` , `allowread` , `globalban` , `boards` , `by` , `at` , `until` , `reason`, `staffnote`, `appealat` ) VALUES ( ".$kx_db->qstr(md5_encrypt($ip, kxEnv::Get('kx:misc:randomseed')))." , ".$kx_db->qstr(md5($ip))." , ".intval($type)." , ".intval($allowread)." , ".intval($globalban)." , ".$kx_db->qstr($boards)." , ".$kx_db->qstr($modname)." , ".time()." , ".intval($ban_until)." , ".$kx_db->qstr($reason)." , ".$kx_db->qstr($staffnote).", ".intval($appealat)." ) ");
        
        if (!$proxyban && $type == 1) {
            $this->UpdateHtaccess();
        }
        return true;
    }
    
    /* Return the page which will inform the user a quite unfortunate message */
    static private function DisplayBannedMessage($bans, $board='') {
        /* Set a cookie with the users current IP address in case they use a proxy to attempt to make another post */
        setcookie('tc_previousip', $_SERVER['REMOTE_ADDR'], (time() + 604800), kxEnv::Get('kx:paths:boards:folder'));
        
        require_once KX_ROOT . '/lib/dwoo.php';
        
        kxTemplate::assign('bans', $bans);
        
        return $dwoo->get(KX_ROOT . kxEnv::Get('kx:templates:dir') .'/banned.tpl', $twigData);
    }
    
    static public function UpdateHtaccess() {
        
        $htaccess_contents = file_get_contents(KX_BOARD.'.htaccess');
        $htaccess_contents_preserve = substr($htaccess_contents, 0, strpos($htaccess_contents, '## !KU_BANS:')+12)."\n";
        
        $htaccess_contents_bans_iplist = '';
        $results = $kx_db->GetAll("SELECT `ip` FROM `".kxEnv::Get('kx:db:prefix')."banlist` WHERE `allowread` = 0 AND `type` = 0 AND (`expired` =  1) ORDER BY `ip` ASC");
        if (count($results) > 0) {
            $htaccess_contents_bans_iplist .= 'RewriteCond %{REMOTE_ADDR} (';
            foreach($results AS $line) {
                $htaccess_contents_bans_iplist .= str_replace('.', '\\.', md5_decrypt($line['ip'], kxEnv::Get('kx:misc:randomseed'))) . '|';
            }
            $htaccess_contents_bans_iplist = substr($htaccess_contents_bans_iplist, 0, -1);
            $htaccess_contents_bans_iplist .= ')$' . "\n";
        }
        if ($htaccess_contents_bans_iplist!='') {
            $htaccess_contents_bans_start = "<IfModule mod_rewrite.c>\nRewriteEngine On\n";
            $htaccess_contents_bans_end = "RewriteRule !^(banned.php|youarebanned.jpg|favicon.ico|css/site_futaba.css)$ " . kxEnv::Get('kx:paths:boards:folder') . "banned.php [L]\n</IfModule>";
        } else {
            $htaccess_contents_bans_start = '';
            $htaccess_contents_bans_end = '';
        }
        $htaccess_contents_new = $htaccess_contents_preserve.$htaccess_contents_bans_start.$htaccess_contents_bans_iplist.$htaccess_contents_bans_end;
        file_put_contents(KX_BOARD.'.htaccess', $htaccess_contents_new);
    }
}