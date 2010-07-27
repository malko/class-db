<?php
/**
* @author Jonathan Gotti <jgotti at jgotti dot org>
* @copyleft (l) 2008  Jonathan Gotti
* @package class-db
* @file
* @since 2008-04
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @svnInfos:
*            - $LastChangedDate$
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL$
* @changelog
*            - 2010-07-27 - first attempt for native php5 sqlite3 rewrite
*            - 2008-07-29 - suppress a bug to avoid some error while trying to destroy twice the same last_qres.
* @todo add transactions support
*/

/**
* exented db class to use with sqlite3 databases.
* require php sqlite3 extension to work
* @class sqlite3db
*/
class sqlite3db extends db{
	public $autocreate= TRUE;

	public $db_file = '';
	public $_protect_fldname = "'";
	public $encryptionKey=null;
	/**
	* create a sqlitedb object for managing locale data
	* if DATA_PATH is define will force access in this directory
	* @param string $db_file
	* @param string $encryptionKey
	* @return sqlitedb object
	*/
	function __construct($db_file,$encryptionKey=null){
		$this->host = 'localhost';
		$this->db_file = $db_file;
		$this->conn = &$this->db; # only for better compatibility with other db implementation
		if(db::$autoconnect)
			$this->open();
	}

	###*** REQUIRED METHODS FOR EXTENDED CLASS ***###
	/** open connection to database */
	function open(){
		//prevent multiple db open
		if($this->db)
			return $this->db;
		if(! $this->db_file )
			return false;
		if(! (is_file($this->db_file) || $this->autocreate) )
			return false;
		if( $this->autocreate)
			$this->db = new SQLite3($this->db_file,SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,$this->encryptionKey);
		else
			$this->db = new SQLite3($this->db_file,SQLITE3_OPEN_READWRITE,$this->encryptionKey);
		if( $this->db instanceof SQLite3){
			return $this->db;
		}else{
			$this->set_error(__FUNCTION__);
			return false;
		}
	}
	/** close connection to previously opened database */
	function close(){
		if( !is_null($this->db) ){
			if($this->last_qres){
				sqlite3_query_close($this->last_qres);
				$this->last_qres = null;
			}
			sqlite3_close($this->db);
		}
		$this->db = null;
	}

	/**
	* check and activate db connection
	* @param string $action (active, kill, check) active by default
	* @return bool
	*/
	function check_conn($action = ''){
		if( is_null($this->db)){
			if($action !== 'active')
				return $action==='kill'?true:false;
			return $this->open()===false?false:true;
		}else{
			if($action==='kill'){
				$this->close();
				$this->db = null;
			}
			return true;
		}
	}

	/**
	* take a resource result set and return an array of type 'ASSOC','NUM','BOTH'
	* @param resource $result_set
	* @param string $result_type in 'ASSOC','NUM','BOTH'
	*/
	function fetch_res($result_set,$result_type='ASSOC'){
		$result_type = strtoupper($result_type);
		if(! in_array($result_type,array('NUM','ASSOC','BOTH')) )
			$result_type = 'ASSOC';
		$result_type = constant('SQLITE3_'.$result_type);

		while($res[]=$result_set->fetch_array($result_type));
		array_pop($res);//unset last empty row

		$this->num_rows = count($res);
		return $this->last_q2a_res = count($res)?$res:false;
	}

	function last_insert_id(){
		return $this->db?$this->db->lastInsertRowID():false;
	}

	/**
	* perform a query on the database
	* @param string $Q_str
	* @return result id or bool depend on the query type| FALSE
	*/
	function query($Q_str){
		if(is_null($this->db) ){
			if(! (db::$autoconnect && $this->open()) )
				return false;
		}
		$this->verbose($Q_str,__FUNCTION__,2);
		if($this->last_qres instanceof SQLite3Result){#- close unclosed previous qres
			$this->last_qres->finalize();
			$this->last_qres = null;
		}

		if( preg_match('!^\s*select!i',$Q_str) ){
			$this->last_qres = $this->db->query($Q_str);
			$res = $this->last_qres;
		}else{
			$res = $this->db->exec($Q_str);
		}
		if(! $res)
			$this->set_error(__FUNCTION__);

		return $res;
	}

	/**
	* perform a query on the database like query but return the affected_rows instead of result
	* give a most suitable answer on query such as INSERT OR DELETE
	* Be aware that delete without where clause can return 0 even if several rows were deleted that's a sqlite bug!
	*    i will add a workaround when i'll get some time! (use get_count before and after such query)
	* @param string $Q_str
	* @return int affected_rows
	*/
	function query_affected_rows($Q_str){
		if(! $this->query($Q_str) )
			return false;
		return $this->db->changes();
	}

	/**
	* free some memory by dropping cached results of last datas
  * return $this for method chaining
  */
	public function freeResults(){
		if( $this->last_qres instanceof SQLite3Result ){
			$this->last_qres->finalize();
		}
		$this->last_qres = null;
		$this->last_q2a_res = array();
		return $this;
	}

	/**
	* return the list of field in $table
	* @param string $table name of the sql table to work on
	* @param bool $extended_info if true will return the result of a show field query in a query_to_array fashion
	*                           (indexed by fieldname instead of int if false)
	* @return array
	*/
	function list_table_fields($table,$extended_info=FALSE){
		# Try the simple method
		if( (! $extended_info) && $res = $this->query_to_array("SELECT * FROM $table LIMIT 0,1")){
			return array_keys($res[0]);
		}else{ # There 's no row in this table so we try an alternate method or we want extended infos
			if(! $fields = $this->query_to_array("SELECT sql FROM sqlite_master WHERE type='table' AND name ='$table'") )
				return FALSE;
			# get fields from the create query
			$flds_str = $fields[0]['sql'];
			$flds_str = substr($flds_str,strpos($flds_str,'('));
			$type = "((?:[a-z]+)\s*(?:\(\s*\d+\s*(?:,\s*\d+\s*)?\))?)?\s*";
			$default = '(?:DEFAULT\s+((["\']).*?(?<!\\\\)\\4|[^\s,]+))?\s*';
			if( preg_match_all('/(\w+)\s+'.$type.$default.'[^,]*(,|\))/i',$flds_str,$m,PREG_SET_ORDER) ){
				$key  = "PRIMARY|UNIQUE|CHECK";
				$Extra = 'AUTOINCREMENT';
				$default = 'DEFAULT\s+((["\'])(.*?)(?<!\\\\)\\2|\S+)';
				foreach($m as $v){
					list($field,$name,$type,$default) = $v;
					# print_r($field);
					if(!$extended_info){
						$res[] = $name;
						continue;
					}
					$res[$name] = array('Field'=>$name,'Type'=>$type,'Null'=>'YES','Key'=>'','Default'=>$default,'Extra'=>'');
					if( preg_match("!($key)!i",$field,$n))
						$res[$name]['Key'] = $n[1];
					if( preg_match("!($Extra)!i",$field,$n))
						$res[$name]['Extra'] = $n[1];
					if( preg_match('!(NO)T\s+NULL!i',$field,$n))
						$res[$name]['Null'] = $n[1];
				}
				return $res;
			}
			return FALSE;
		}
	}
	/**
	* get the table list
	* @return array
	*/
	function list_tables(){
		if(! $tables = $this->query_to_array('SELECT name FROM sqlite_master WHERE type=\'table\'') )
			return FALSE;
		foreach($tables as $v){
			$ret[] = $v['name'];
		}
		return $ret;
	}

	/** return informations about table indexes */
	function show_table_keys($table){
		$ids = $this->query_to_array('PRAGMA INDEX_LIST('.$table.')');
		$res = array();
		$fields = $this->list_table_fields($table,true);
		foreach($fields as $k=>$v){
			if( !empty($v['Key']) ){
				$kname = $v['Key']==="PRIMARY"?$v['Key']:$k;
				$unique = $v['Key']==="PRIMARY"?1:($v['Key']==="UNIQUE"?1:0);
				$res[] = array(
					'Table'=>$table,
					'Key_name'  => $kname,
					'name'      => $kname,
					'unique'    => $unique,
					'Non_unique'=> $unique?0:1,
					'Column_name'=>$k,
					'Null' => $v['Null'] === 'YES'?true:false
				);
			}
		}
		if( $ids ){
			foreach($ids as $id){
				$key = array(
					'Table'=>$table,
					'Key_name'=>$id['name'],
					'name'=>$id['name'],
					'unique'=> $id['unique'],
					'Non_unique'=> $id['unique']?0:1,
				);
				$tmp = $this->query_to_array('PRAGMA INDEX_INFO('.$id['name'].')');
				foreach($tmp as $idCol){
					$res[] = array_merge($key,array('Column_name'=>$idCol['name'],'Null'=>$fields[$idCol['name']]['Null']==='YES'?true:false));
				}
			}
		}
		return $res;

	}

	/**
	* optimize table statement query
	* @param string $table name of the table to optimize
	* @return bool
	*/
	function optimize($table){
		return $this->vacuum($table);
	}
	/**
	* sqlitedb specific method to use the vacuum statement (used as replacement for mysql optimize statements)
	* you should use db::optimize() method instead for better portability
	* @param string $table_or_index name of table or index to vacuum
	* @return bool
	*/
	function vacuum($table_or_index){
		return $this->query("VACUUM $table_or_index;");
	}

	/**
	* base method you should replace this one in the extended class, to use the appropriate escape func regarding the database implementation
	* @param string $quotestyle (both/single/double) which type of quote to escape
	* @return str
	*/
	function escape_string($string,$quotestyle='both'){
		$string = $this->db->escapeString($string);
		switch(strtolower($quotestyle)){
			case 'double':
			case 'd':
			case '"':
				$string = str_replace("''","'",$string);
				$string = str_replace('"','\"',$string);
				break;
			case 'single':
			case 's':
			case "'":
				break;
			case 'both':
			case 'b':
			case '"\'':
			case '\'"':
				$string = str_replace('"','\"',$string);
				break;
		}
		return $string;
	}

	function error_no(){
		return $this->db?$this->db->lastErrorCode():false;
	}

	function error_str($errno=null){
		return $this->db?$this->db->lastErrorMsg():false;
	}

}
