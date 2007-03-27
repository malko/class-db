<?php
/**
* Base class for databases object.
* @author Jonathan Gotti <nathan at the-ring dot homelinux dot net>
* @copyleft (l) 2004-2005  Jonathan Gotti
* @package DB
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @since 2006-04-16 first version
* get_field and list_fields have changed -> list_table_fields (list_fields indexed by name)
* smart '?' on conditions strings
* @changelog - 2007-03-26 - better fields name handling (auto-protect fieldsname even if string is given)
*            - 2007-01-12 - better values type handling in update and insert methods
*            - 2007-01-10 - correct a bug about page counting in set_slice_attrs() and add %page replacement to fromatStr
*                         - better params type handling in method process_conds()  (int/string/array/null)
*            - 2006-12-05 - new method select_field_to_array()
*            - 2006-05-15 - new methods set_slice_attrs() and select_array_slice() to easily paginate your results
*/

class db{
	
	/**Db hostname*/
	var $host = null;
	/**mysql username*/
	var $user = null;
	/**mysql password*/
	var $pass = null;

	/**resource connection (same as $conn if not applicable)*/
	var $conn = null;
	/**resource db selected*/
	var $db = null ;

	/**selected database*/
	var $dbname = '';

	/** resource result handler*/
	var $last_qres = null;
	/**array of last query to array results*/
	var $last_q2a_res = array();
	/**array of error number and msgs*/
	var $error = array();
	/**the last error array*/
	var $last_error = array();

	var $beverbose = FALSE;
	var $autoconnect = TRUE;
	/** 
	*chr to protect fields names in queries
	*@private 
	*/
	var $_protect_fldname = '`';

	function db(){
		if($this->autoconnect)
			$this->open();
	}

	###*** REQUIRED METHODS FOR EXTENDED CLASS ***###

	/** open connection to database */
	function open(){}

	/** close connection to previously opened database */
	function close(){}
	/**
	* Select the database to work on (it's the same as the use db command or mysql_select_db function)
	* @param string $dbname
	* @return bool
	* /
	function select_db($dbname=null){}*/
	/** 
	* take a resource result set and return an array of type 'ASSOC','NUM','BOTH' 
	* @see sqlitedb or mysqldb implementation for exemple
	*/
	function fetch_res($result_set,$result_type){}

	function last_insert_id(){}

	/**
	* base method you should replace this one in the extended class, to use the appropriate escape func regarding the database implementation
	* @param string $quotestyle (both/single/double) which type of quote to escape
	* @return str
	*/
	function escape_string($string,$quotestyle='both'){
		$escapes = array("\x00", "\x0a", "\x0d", "\x1a", "\x09","\\");
		$replace = array('\0',   '\n',    '\r',   '\Z' , '\t',  "\\\\");
		switch(strtolower($quotestyle)){
			case 'double':
			case 'd':
			case '"':
				$escapes[] = '"';
				$replace[] = '\"';
				break;
			case 'single':
			case 's':
			case "'":
				$escapes[] = "'";
				$replace[] = "\'";
				break;
			case 'both':
			case 'b':
			case '"\'':
			case '\'"':
				$escapes[] = '"';
				$replace[] = '\"';
				$escapes[] = "'";
				$replace[] = "\'";
				break;
		}
		return str_replace($escapes,$replace,$string);
	}

	/**
	* perform a query on the database
	* @param string $Q_str
	* @return= result id | FALSE
	**/
	function query($Q_str){}

	/**
	* perform a query on the database like query but return the affected_rows instead of result
	* give a most suitable answer on query such as INSERT OR DELETE
	* @param string $Q_str
	* @return int affected_rows or FALSE on error!
	* @can work without this method but less smart
	function query_affected_rows($Q_str){}
	*/

	/**
	* get the table list from $this->dbname
	* @return array
	*/
	function list_tables(){}
	/**
	* return the list of field in $table
	* @param string $table name of the sql table to work on
	* @param bool $extended_info if true will return the result of a show field query in a query_to_array fashion 
	*                           (indexed by fieldname instead of int if false)
	* @return array
	*/
	function list_table_fields($table,$extended_info=FALSE){}

	/** Verifier si cette methode peut s'appliquer a SQLite */
	function show_table_keys($table){}

	/**
	* optimize table statement query
	* @param string $table name of the table to optimize
	* @return bool
	*/
	function optimize($table){}

	function error_no(){}
	function error_str(){}

	###*** COMMON METHODS ***###

	/**
	* return the result of a query to an array
	* @param string $Q_str SQL query
	* @param string $result_type 'ASSOC', 'NUM' et 'BOTH' 
	* @return array | false if no result
	*/
	function query_to_array($Q_str,$result_type='ASSOC'){
		unset($this->last_q2a_res);
		if(! $this->query($Q_str)){
			$this->set_error(__FUNCTION__);
			return FALSE;
		}
		return $this->fetch_res($this->last_qres,$result_type);
	}

	/**
	* send a select query to $table with arr $fields requested (all by default) and with arr $conditions
	* @param string|array $Table
	* @param string|array $fields
	* @param string|array $conditions
	* @param string $res_type 'ASSOC', 'NUM' et 'BOTH' 
	* @Return  array | false
	**/
	function select_to_array($tables,$fields = '*', $conds = null,$result_type = 'ASSOC'){
		//we make the table list for the Q_str
		if(! $tb_str = $this->array_to_str($tables))
			return FALSE;
		//we make the fields list for the Q_str
		if(! $fld_str =  $this->protect_field_names($fields))
			$fld_str = '*';
		//now the WHERE str
		$conds_str = $this->process_conds($conds);
		
		$Q_str = "SELECT $fld_str FROM $tb_str $conds_str";
		# echo "SQL : $Q_str\n;";
		return $this->query_to_array($Q_str,$result_type);
	}
	/**
	* Same as select_to_array but return only the first row.
	* equal to $res = select_to_array followed by $res = $res[0];
	* @see select_to_array for details
	* @return array of fields
	*/
	function select_single_to_array($tables,$fields = '*', $conds = null,$result_type = 'ASSOC'){
		if(! $res = $this->select_to_array($tables,$fields,$conds,$result_type))
			return FALSE;
		return $res[0];
	}
	/**
	* just a quick way to do a select_to_array followed by a associative_array_from_q2a_res
	* see both thoose method for more information about parameters or return values
	*/
	function select2associative_array($tables,$fields='*',$conds=null,$index_field='id',$value_fields=null,$keep_index=FALSE){
		if(! $this->select_to_array($tables,$fields,$conds))
			return FALSE;
		return $this->associative_array_from_q2a_res($index_field,$value_fields,null,$keep_index);
	}
	/**
	* select a single value in database
	* @param string $table
	* @param string $field the field name where to pick-up value
	* @param mixed conds
	* @return mixed or FALSE
	*/
	function select_single_value($table,$field,$conds=null){
		if($res = $this->select_single_to_array($table,$field,$conds,'NUM'))
			return $res[0];
		else
			return FALSE;
	}
	/**
	* select a single table field and return all values 
	* @param string $table
	* @param string $field name of the single field to retrieve
	* @param mixed  $conds 
	* @return array or FALSE
	*/
	function select_field_to_array($table,$field,$conds=null){
		$conds_str = $this->process_conds($conds);
		$field = $this->protect_field_names($field);
		$Q_str = "SELECT $field FROM $table $conds_str";
		if(! $res = $this->query_to_array($Q_str,'NUM') )
			return FALSE;
		foreach($res as $row){
			$_res[] = $row[0];
		}
		return $_res;
	}

	/**
	* @return array  array((array) results,(str) navigationstring, (int) totalrows)
	*/
	function select_array_slice($table,$fields='*',$conds=null,$pageId=1,$pageNbRows=10){
		$conds = $this->process_conds($conds);
		if(! ($tot = $this->select_single_value($table,'count(*)',$conds) ) )
			return FALSE;
		$limitStart = (int) $pageNbRows * ($pageId-1);
		$res = $this->select_to_array($table,$fields,$conds." Limit $limitStart,$pageNbRows");
		# now prepare navigation links
		$attrs = $this->set_slice_attrs();
		extract($attrs);
		$nbpages = ceil($tot/max(1,$pageNbRows));
		
		# start/prev link
		if($nbpages > 1 && $pageId != 1){
			$first = str_replace('%lnk',str_replace('%page',1,$linkStr),$first);
			$prev = str_replace('%lnk',str_replace('%page',$pageId-1,$linkStr),$prev);
		}else{
			$first = $prev = '';
		}
		# next/end link
		if( $pageId < $nbpages ){
			$last  = str_replace('%lnk',str_replace('%page',$nbpages,$linkStr),$last);
			$next = str_replace('%lnk',str_replace('%page',$pageId+1,$linkStr),$next);
		}else{
			$last = $next = '';
		}
		
		# pages links
		if(preg_match('!%(\d+)?links!',$formatStr,$m)){
			$nblinks = isset($m[1])?$m[1]:'';
			if(! $nblinks){ # all pages links
				$slideStart = 1;
				$slideEnd   = $nbpages;
			}else{ # range pages link
				$delta      = $nblinks%2?($nblinks-1)/2:$nblinks/2;
				$slideStart = max(1,$pageId - $delta - (($pageId+$delta)<=$nbpages?0: $pageId -($nbpages-$delta)) );
				$slideEnd   = min($nbpages,$pageId + $delta + ($pageId > $delta?0: $delta - $pageId + 1 ) );
			}
			for($i=$slideStart;$i<=$slideEnd;$i++){
				$pageLinks[] = str_replace( array('%lnk','%page'),
																		array(str_replace('%page',$i,$linkStr),$i),
																		($i==$pageId?$curpage:$pages)
																	);
			}
			
			$links = implode($linkSep,$pageLinks);
		}
		
		$formatStr = str_replace( array('%first','%prev','%next','%last','%'.$nblinks.'links','%tot','%nbpages','%page'),
															array($first,$prev,$next,$last,$links,$tot,$nbpages,$pageId),
															$formatStr
														);
		return array($res,$formatStr,$tot);
	}

	/**
	* set attributes for slice rendering.
	* take an associative array of format strings to render slice links.
	* - firt:  first page link %lnk and %page will be replaced by the link to the page and the number of the page
	* - prev:  previous page link %lnk and %page will be replaced by the link to the page and the number of the page
	* - next:  next page link %lnk and %page will be replaced by the link to the page and the number of the page
	* - last:  last page link %lnk and %page will be replaced by the link to the page and the number of the page
	* - pages: pages link %lnk and %page will be replaced by the link to the page and the number of the page
	* - curpage: selected page link %lnk and %page will be replaced by the link to the page and the number of the page
	* - linkStr: is used for rendering the url of pages %page will be replaced by the corresponding page number
	* - linkSep: separator between pages links
	* - formatStr: is used to render the full pagination string
	*              %start, %prev, %next, %last will be replaced respectively by corresponding links
	*              %Nlinks will be replaced by the pages links. N is the number of link to display 
	*              including the selected page ex: %5links will show 5 pages links 
	* you can pass only the keys you want to replace ex: db::set_slice_attrs(array('linkStr'=>"myslice.php?page=%page"))
	* all keys can also contain a %tot and %nbpages which will be replaced respectively by 
	* the total amount of result and the total number of pages
	*@param array $attrs
	*@return array
	*/
	function set_slice_attrs($attrs=null){
		static $sliceAttrs;
		if(! isset($sliceAttrs) ){
			$sliceAttrs = array( 'first' => "<a href=\"%lnk\" class=\"pagelnk\"><<</a>",
														'prev'  => "<a href=\"%lnk\" class=\"pagelnk\"><</a>",
														'next'  => "<a href=\"%lnk\" class=\"pagelnk\">></a>",
														'last'   => "<a href=\"%lnk\" class=\"pagelnk\">>></a>",
														'pages'  => "<a href=\"%lnk\" class=\"pagelnk\">%page</a>",
														'curpage'  => "<b><a href=\"%lnk\" class=\"pagelnk\">%page</a></b>",
														'linkStr'  => "?page=%page",
														'linkSep'  => " ",
														'formatStr'=> " %first %prev %5links %next %last"
													);
		}
		if( is_array($attrs) ){
			foreach($sliceAttrs as $k=>$v){
				$sliceAttrs[$k] = isset($attrs[$k])?$attrs[$k]:$v;
			}
		}
		return $sliceAttrs;
	}

	/**
	* Send an insert query to $table
	* @param string $table
	* @param array $values (arr(FLD=>VALUE,)
	* @param bool $return_id the function will return the inserted_id if $return_id is true (the default value), else it'll return only true or false.
	* @return insert id or FALSE
	**/
	function insert($table,$values,$return_id=TRUE){
		if(!is_array($values))
			return FALSE;
		$fld = $this->protect_field_names(array_keys($values));
		$val = array_map(array($this,'prepare_smart_param'),$values);
    
		$Q_str = "INSERT INTO $table ($fld) VALUES (".$this->array_to_str($val).")";
		if(! $this->query($Q_str) )
			return FALSE;
		$this->last_id = $this->last_insert_id();
		return $return_id?$this->last_id:TRUE;
	}
	/**
	* Send a delete query to $table
	* @param string $table
	* @param mixed $conds
	* @return int affected_rows
	**/
	function delete($table,$conds=null){
		$conds_str = $this->process_conds($conds);
		$Q_str = "DELETE FROM $table $conds_str";
		if(method_exists($this,'query_affected_rows')){
			$res = $this->query_affected_rows($Q_str);
			return ($res===FALSE || $res === -1)?FALSE:$res;
		}else{
			$count = (int) $this->get_count($table);
			if(! $this->query($Q_str) )
				return FALSE;
			$count2 = (int) $this->get_count($table);
			return (int) ($count - $count2);
		}
	}
	/**
	* Send an update query to $table
	* @param string $table
	* @param string|array $values ( 'fld=value, fld2=value2' arr(FLD=>VALUE,))
	* @return int affected_rows or bool (depends on the database implementation (have we a query_affected_rows or not?))
	**/
	function update($table,$values,$conds = null){
		if(is_array($values)){
			$str = array();
			foreach( $values as $k=>$v)
				$str[] = $this->protect_field_names($k)." = ".$this->prepare_smart_param($v).' ';
		}elseif(! is_string($values)){
			return FALSE;
		}
		# now the WHERE str
		$conds_str = $this->process_conds($conds);
		$Q_str = "UPDATE $table SET ".(is_array($values)?$this->array_to_str($str):$values)." $conds_str";
		if(method_exists($this,'query_affected_rows')){
			$res = $this->query_affected_rows($Q_str);
			return ($res===FALSE || $res === -1)?FALSE:$res;
		}else{
			return (bool) $this->query($Q_str);
		}
	}

	/**
	* get the number of row in $table
	* @param string $table table name
	* @return int
	*/
	function get_count($table){
		return $this->select_single_value($table,'count(*) as c');
	}

	/**
	*return an associative array indexed by $index_field with values $value_fields from
	*a mysqldb->select_to_array result
	*@param string $index_field default value is id
	*@param mixed $value_fields (string field name or array of fields name default is null so keep all fields
	*@param array $res the mysqldb->select_to_array result
	*@param bool $keep_index if set to true then the index field will be keep in the values associated (unused if $value_fields is string)
	*@param bool $sort_keys will automaticly sort the array by key if set to true @deprecated argument
	*@return array
	*/
	function associative_array_from_q2a_res($index_field='id',$value_fields=null,$res = null,$keep_index=FALSE,$sort_keys=FALSE){
		if($res===null)
			$res = $this->last_q2a_res;
		if(! is_array($res)){
			$this->verbose("[error] db::associative_array_from_q2a_res with invalid result\n");
			return FALSE;
		}
		# then verify index exists
		if(!isset($res[0][$index_field])){
			$this->verbose("[error] db::associative_array_from_q2a_res with invalid index field '$index_field'\n");
			return FALSE;
		}
		# then we do the trick
		if(is_string($value_fields)){
			foreach($res as $row)
				$associatives_res[$row[$index_field]] = $row[$value_fields];
		}elseif(is_array($value_fields)||$value_fields===null){
			foreach($res as $row){
				$associatives_res[$row[$index_field]] = $row;
				if(!$keep_index)
					unset($associatives_res[$row[$index_field]][$index_field]);
			}
		}
		if(! count($associatives_res))
			return FALSE;
		if($sort_keys)
			ksort($associatives_res); 
		return $this->last_q2a_res = $associatives_res;
	}
	/*########## INTERNAL METHOD ##########*/

	/**
	* used by other methods to parse the conditions param of a QUERY.
	* If $conds is string then nothing more is done. 
	* If it's an array, the first value (index 0) will be consider as the full condition string and all '?' will be replaced by other values in the array (sort of sprintf).
	* You can add a number before a ? to replace it by a given index in the array like 2? 
	* @param string|array $conds
	* @return string
	* @private
	*/
	function process_conds($conds=null){
		if(is_string($conds) )
			return $conds;
		elseif(! is_array($conds) )
			return '';
		$conds_str = array_shift($conds);
		array_unshift($conds,'');
		$i=0;
		return preg_replace('!(\d*)\?!e',"\$this->prepare_smart_param('\\1'!==''?\$conds['\\1']:(isset(\$conds[++\$i])?\$conds[\$i]:null),'single')",$conds_str);
	}

	/**
	* used internally for smart params processing
	* @private
	*/
	function prepare_smart_param($val){
		if(is_null($val)){
			return 'NULL';
		}elseif (is_int($val) || is_float($val)) {
			return $val;
		} elseif(is_string($val)) {
			return "'".$this->escape_string($val,'single')."'";
		} elseif(is_array($val)) {
			return implode(',', array_map(array(&$this,'prepare_smart_param'),$val));
		}else{
			return "''";
		}
	}
	/**
	* used internally to prepare fields for queries 
	* @param string|array $fields list of fields
	* @private
	*/
	function protect_field_names($fields){
		if(is_array($fields)){
			foreach($fields as $k=>$f)
				$fields[$k] = $this->_protect_fldname.$v.$this->_protect_fldname;
			$fields = implode(',',$fields);
		}elseif($fields){
			if(! substr_count($fields,$this->_protect_fldname) ) # if already protected we do nothing
				$fields = preg_replace('!\s*,\s*!',$this->_protect_fldname.','.$this->_protect_fldname,$fields);
				$fields = $this->_protect_fldname . trim($fields) . $this->_protect_fldname;
			}
		}
		return $fields?$fields:false;
	}
	
	function array_to_str($var,$sep=','){
		return (is_string($var)?$var:(is_array($var)?implode($sep,$var):''));
	}

	function set_error($callingfunc=null){
		static $i=0;
		if(! $this->db ){
			$this->error[$i]['nb']  = null;
			$this->error[$i]['str'] = '[ERROR] No Db Handler';
		}else{
			$this->error[$i]['nb']  = $this->error_no();
			$this->error[$i]['str'] = $this->error_str($this->error[$i]['nb']);
		}
		$this->last_error = $this->error[$i];
		if(class_exists('console_app') && php_sapi_name()=='cli')
			$this->verbose(console_app::tagged_string($this->error[$i]['str'],'red|bold'),$callingfunc);
		else
			$this->verbose($this->error[$i]['str'],$callingfunc);
		$i++;
	}

	/**
	* print a msg on STDOUT if $this->beverbose is set to true
	* @param string $string
	* @private
	*/
	function verbose($string,$callingfunc=null){
		if($this->beverbose)
			echo (isset($this)?get_class($this):'db').($callingfunc?"::$callingfunc ":' ')."=> $string\n";
	}

	###*** DEPRECATED METHODS ***###

	/**
	* return the list of field in $table
	* @deprecated still here for compatibility with old version 
	* @use and @see db::list_table_fields() instead
	* @param string $table name of the sql table to work on
	* @param bool $extended_info will return the result of a show field query in a query_to_array fashion
	*/
	function get_fields($table,$extended_info=FALSE){
		return $this->list_table_fields($table,$extended_info);
	}

	/**
	* get the fields list of table
	* @deprecated now the $indexed_by_name args won't exists anymore but will considered as TRUE in all case
	* @see db::list_table_fields as a replacement method
	* @param string $table
	* @param bool $indexed_by_name the return array will be indexed by the fields name if set to true (default is FALSE)
	* @return array
	*/
	function list_fields($table,$indexed_by_name=FALSE){
		return $this->list_table_fields($table,TRUE);
	}

	function __destruct(){
		$this->close();
	}

}

?>