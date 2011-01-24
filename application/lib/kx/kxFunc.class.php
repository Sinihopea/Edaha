<?/* * This file is part of kusaba. * * kusaba is free software; you can redistribute it and/or modify it under the * terms of the GNU General Public License as published by the Free Software * Foundation; either version 2 of the License, or (at your option) any later * version. * * kusaba is distributed in the hope that it will be useful, but WITHOUT ANY * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR * A PARTICULAR PURPOSE. See the GNU General Public License for more details. *   * You should have received a copy of the GNU General Public License along with * kusaba; if not, write to the Free Software Foundation, Inc., * 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA *//* * Static functions that don't fit anywhere else * Last Updated: $Date$  * @author 		$Author$ * @package		kusaba * @version		$Revision$ * */  class kxFunc {  	/**	 * Cleans input	 *	 * @access	public	 * @param	array 	Input data      * @param  int           Iteration	 * @return	array 	Cleaned data	 */	static public function cleaninput(&$data, $i = 0) {  		// Don't parse arrays deeper than 10, as it's most likely someone trying to crash PHP		if ($i > 10) {			return;		}						foreach($data as $k => $v) {			if (is_array($v) ) {				self::cleaninput($data[$k], ++$i);			}			else {				// Decimal places. Script kiddies might think they can try to access files outside the board				$v = str_replace("&#46;&#46;/", "../", $v);          			// This litte bugger changes the formatting to be right-to-left, like on a hebrew locale.				$v = str_replace('&#8238;', '', $v);          // Null byte characters can mess with formatting as well, so we remove them				$v = str_replace("\x00", '', $v);				$v = str_replace(chr('0'), '', $v);				$v = str_replace("\0", '', $v);								$data[$k] = $v;			}		}	}	/**	 * Recursively cleans keys and values and	 * inserts them into the input array	 *	 * @access	public	 * @param	mixed		Input data	 * @param	array		Parsed data	 * @param	integer		Iteration	 * @return	array 		Cleaned data	 */	static public function parseinput(&$data, $input=array(), $i = 0) {    if ($i > 10) {			return $input;		}		foreach($data as $k => $v) {			if (is_array($v)) {				$input[$k] = self::parseinput($data[$k], array(), $i++);			}      else {				$k = self::cleanInputKey($k);				$v = self::cleanInputVal($v, false);				$input[$k] = $v;			}		}		return $input;	}	/**	 * Clean up input key	 *	 * @access	public	 * @param	string		Key name	 * @return	string		Cleaned key name	 */    static public function cleanInputKey($key) {    	if ($key == ""){    		return "";    	}    	$key = htmlspecialchars(urldecode($key));    	$key = str_replace("..", "", $key);    	$key = preg_replace( "/\_\_(.+?)\_\_/", "", $key );    	$key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );    	return $key;    }  /**	 * Clean up input data	 *	 * @access	public	 * @param	string		Input	 * @return	string		Cleaned Input	 */    static public function cleanInputVal($txt) {    	if (empty($txt)){    		return "";    	}      $txt = kxFunc::strip_magic($txt);            $search = array("&#032;",                      array( "\r\n", "\n\r", "\r" ),                      "&",                      "<!--",                      "-->",                      "<",                      ">",                      "\n",                      '"',                      "<script",                      "$",                      "!",                      "'");      $replace = array( " ",                        "\n",                        "&amp",                        "&#60;&#33;--",                        "--&#62;",                        "&lt;",                        "&gt;",                        "<br />",                        "&quot;",                        "&#60;script",                        "&#036;",                        "&#33;",                        "&#39;");      $txt = str_replace( $search, $replace, $txt );      			$txt = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $txt );			$txt = preg_replace("/&#(\d+?)([^\d;])/i", "&#\\1;\\2", $txt );		      return $txt;    }  	/**	 * Returns only alphanumeric characters	 *	 * @access	public	 * @param	string		Input String	 * @param	string		Additional characters	 * @return	string		Parsed string	 */	static public function alphanum($txt, $extra="") {		if ( $extra ){			$extra = preg_quote( $extra, "/" );		}		return preg_replace( "/[^a-zA-Z0-9\-\_" . $extra . "]/", "" , $txt );    }    	/**      * Generates a path for an application, with module if applicable	 *	 * @access	public	 * @param	string		application	 * @param	string		module (optional)	 * @return	mixed		Directory to app or module (or false if error)	 */	static public function getAppDir( $app, $module='' ) {		if (empty($app) || !is_string($app)) {			return FALSE;		}		$appFolder       = KX_ROOT . '/application/' . $app;		$modulesFolder = (IN_MANAGE) ? 'manage' : 'public';		if ( $module ) {			return $appFolder . "/" . $modulesFolder . "/" . $module;		}		else{			return $appFolder;		}	}  	/**	 * Remove slashes if magic_quotes enabled	 *	 * @access	public	 * @param	string		Input String	 * @return	string		Parsed string	 */	static public function strip_magic($t) {		if ( KX_MAGIC_QUOTES ){    		$t = stripslashes($t);    		$t = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $t );    }    return $t;  }}