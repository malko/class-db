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
* try to determine the native driver presence else will default back using pdo
*/
if( class_exists('SQLite3',false) ){
	define('SQLITE3DB_NATIVE_DRIVER',true);
}else if ( class_exists('PDO',false) && in_array('sqlite',PDO::getAvailableDrivers()) ){
	define('SQLITE3DB_NATIVE_DRIVER',false);
}else{
	define('SQLITE3DB_NATIVE_DRIVER',null);
}



/**
* exented db class to use with sqlite3 databases.
* require php sqlite3 extension to work
* @class sqlite3db
*/
class sqlite3db extends db{
	public $autocreate= true;

	public $db_file = '';
	public $_protect_fldname = "`";
	public $encryptionKey=null;
	/**
	* create a sqlitedb object for managing locale data
	* if DATA_PATH is define will force access in this directory
	* @param string $db_file
	* @param string $encryptionKey (only available with native driver and if the encryption was enable at compilation time.)
	* @return sqlitedb object
	*/
	function __construct($db_file,$encryptionKey=null){
		if( null === SQLITE3DB_NATIVE_DRIVER ){
			throw new Exception("sqlite3db: No sqlite3 driver available");
		}
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
		if( SQLITE3DB_NATIVE_DRIVER ){
			if( $this->autocreate){
				$this->db = new SQLite3($this->db_file,SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,$this->encryptionKey);
			}else{
				$this->db = new SQLite3($this->db_file,SQLITE3_OPEN_READWRITE,$this->encryptionKey);
			}
		}else{ //- no support for encryptionKey, autocreate is default to true.
			$this->db = new PDO("sqlite:$this->db_file");
			$this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			$this->db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
			$this->db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
		}
		if( (SQLITE3DB_NATIVE_DRIVER && $this->db instanceof SQLite3) ||  (! SQLITE3DB_NATIVE_DRIVER && $this->db instanceof PDO)){
			return $this->db;
		}else{
			$this->set_error(__FUNCTION__);
			return false;
		}
	}
	/** close connection to previously opened database */
	function close(){
		if( !is_null($this->db) ){
			$this->freeResults();
			if( SQLITE3DB_NATIVE_DRIVER)
				$this->db->close();
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
		$result_type = constant((SQLITE3DB_NATIVE_DRIVER?'SQLITE3_':'PDO::FETCH_').$result_type);
		if( SQLITE3DB_NATIVE_DRIVER ){
			while($res[]=$result_set->fetchArray($result_type));
			array_pop($res);//unset last empty row
		}else{
			$res = $result_set->fetchAll($result_type);
		}
		$this->num_rows = count($res);
		return $this->last_q2a_res = count($res)?$res:false;
	}

	function last_insert_id(){
		return $this->db?(SQLITE3DB_NATIVE_DRIVER?$this->db->lastInsertRowID():$this->db->lastInsertId()):false;
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
		#- close unclosed previous qres
		$this->freeResults();

		if( preg_match('!^\s*(select|optimize|vacuum|pragma)!i',$Q_str) ){
			$this->last_qres = $this->db->query($Q_str);
			$res = $this->last_qres;
		}else{
			$res = $this->db->exec($Q_str);
		}
		if(false === $res)
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
		if( SQLITE3DB_NATIVE_DRIVER ){
			if(! $this->query($Q_str) )
				return false;
			return $this->db->changes();
		}else{
			return $this->query($Q_str);
		}
	}

	/**
	* free some memory by dropping cached results of last datas
  * return $this for method chaining
  */
	public function freeResults(){
		if($this->last_qres !==null ){#- close unclosed previous qres
			if( SQLITE3DB_NATIVE_DRIVER && $this->last_qres instanceof SQLite3Result)
				$this->last_qres->finalize();
			else if( (! SQLITE3DB_NATIVE_DRIVER ) && $this->last_qres instanceof PDOStatement )
				$this->last_qres->closeCursor();
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
		if( (! $extended_info) && $res = $this->query_to_array("SELECT * FROM ".$this->protect_field_names($table)." LIMIT 0,1")){
			return array_keys($res[0]);
		}else{ # There 's no row in this table so we try an alternate method or we want extended infos
			if(! $fields = $this->query_to_array('PRAGMA table_info('.$this->protect_field_names($table).')') )
				return false;
			$indexes = (array) $this->query_to_array('PRAGMA index_list('.$this->protect_field_names($table).')');
			if(!empty($indexes)){
				$indexes = $this->associative_array_from_q2a_res('name','unique',$indexes);
				foreach($indexes as $iname=>$uni){
					$info = $this->query_to_array('PRAGMA index_info('.$iname.')');
					$indexes[$info[0]['name']] = $indexes[$iname];
					unset($indexes[$iname]);
				}
			}

			foreach($fields as $k=>$f){
				$fields[$k]['Field'] = $f["name"];
				$fields[$k]['Type'] = $f["type"];
				$fields[$k]['Null'] = $f['notnull']?'NO':'YES';
				$fields[$k]['Key'] = $f['pk']?'PRI':(isset($indexes[$f['name']])?($indexes[$f['name']]?'UNI':'MUL'):'');
				$fields[$k]['Default'] = $f['dflt_value'];
				#- $fields[$k]['Extra'] = $f[]; #-- @todo find a way to detect autoIncrement
				$fields[$f['name']] = $fields[$k];
				unset($fields[$k]);
			}
			return $fields;
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
		$ids = $this->query_to_array('PRAGMA index_list('.$table.')');
		$res = array();
		$fields = $this->list_table_fields($table,true);
		foreach($fields as $k=>$v){
			if( !empty($v['Key']) ){
				$kname = $v['Key']==="PRI"?$v['Key']:$k;
				$unique = $v['Key']==="PRI"?1:($v['Key']==="UNIQUE"?1:0);
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
				$tmp = $this->query_to_array('PRAGMA index_info('.$id['name'].')');
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
		$string = SQLITE3DB_NATIVE_DRIVER?$this->db->escapeString($string):preg_replace("!^'|'$!",'',$this->db->quote($string));
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
		if( SQLITE3DB_NATIVE_DRIVER ){
			return $this->db?$this->db->lastErrorCode():false;
		}else{
			if( ! $this->db )
				return false;
			if( ($tmp = $this->db->errorCode()) !== null)
				return $tmp;
			else if( $this->last_qres instanceof PDOStatement )
				return $this->last_qres->errorCode();
		}
	}

	function error_str($errno=null){
		if( SQLITE3DB_NATIVE_DRIVER ){
			return $this->db?$this->db->lastErrorMsg():false;
		}else{
			if( ! $this->db )
				return false;
			if( ($tmp = $this->db->errorInfo()) !== null){
				return $tmp[2];
			}else if( $this->last_qres instanceof PDOStatement ){
				$tmp =  $this->last_qres->errorInfo();
				return $tmp[2];
			}
		}
	}

}
