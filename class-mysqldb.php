<?php
/**
* @author Jonathan Gotti <jgotti at jgotti dot org>
* @copyleft (l) 2003-2008  Jonathan Gotti
* @package class-db
* @file
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @since 2004-11-26 first splitted version
* @changelog
*            - 2008-05-12 - add parameter $setNames to select_db() that will default to new static property
*                           $setNamesOnSelectDb if both are null then nothing will happen else it will perform
*                           a SET NAMES query on the selected database.
*            - 2008-04-06 - autoconnect is now a static property
*            - 2008-03-20 - new static parameter (bool) $useNewLink used as mysql_connect new_link parameter
*                           (really usefull when working on different databases on the same host.
*                           you'd better set this to false as default if you don't need that feature.)
*            - 2007-11-20 - changing call to vebose() method according to changed made in class-db
*            - 2007-03-28 - move last_q2a_res assignment from fetch_res() method to query_to_array() (seems more logical to me)
*            - 2007-01-12 - now dump_to_file() use method escape_string instead of mysql_escape_string
*            - 2005-02-28 - add method optimize
*            - 2004-12-03 - now the associative_array_from_q2a_res method won't automaticly ksort the results
*            - 2004-12-02 - use the show fields query in place of a select statement and add extended_info mode to the get_fields method
* @todo revoir la methode check_conn() et open et close de facon a ce que check_conn aille dans base_db
*/

/**
* exented db class to use with mysql databases.
* @class mysqldb
* @example sample-mysqldb.php
*/
class mysqldb extends db{
	/**
	* allow the setting of mysql_connect $new_link parameter.
	* @see mysql_connect for more info
	*/
	static public $useNewLink = false;
	/**
	* used to perform a query "SET NAMES '$dfltEncoding'" when select a database on the server
	* leave null if you don't want this to be done
	*/
	static public $setNamesOnSelectDb='UTF8';

	function __construct($dbname,$dbhost='localhost',$dbuser='root',$dbpass=''){ # most common config ?
		$this->host   = $dbhost;
		$this->user   = $dbuser;
		$this->pass   = $dbpass;
		$this->dbname = $dbname;
		if(db::$autoconnect)
			$this->open();
	}

	/** open connection to database */
	function open(){  # only for convenience and because backport of sqlitedb
		return $this->check_conn('active');
	}

  /** close connection to previously opened database */
	function close(){
		return $this->check_conn('kill');
	}

	/**
	* Select the database to work on (it's the same as the use db command or mysql_select_db function)
	* @param string $dbname
	* @param string $setNames permit to enforce encoding connection to the given character set
	*                         if null will default to self::$setNamesOnSelectDb.
	*                         if both are null then no SET NAMES will be performed
	* @return bool
	*/
	function select_db($dbname=null,$setNames=null){
		if(! ($dbname || $this->dbname) )
			return FALSE;
		if($dbname)
			$this->dbname = $dbname;
		if(! $this->db = mysql_select_db($this->dbname,$this->conn)){
			$this->verbose("can't connect to database ".$this->dbname,__FUNCTION__,1);
			$this->set_error(__FUNCTION__);
			return FALSE;
		}else{
			if( null=== $setNames && null !== mysqldb::$setNamesOnSelectDb )
				$setNames = mysqldb::$setNamesOnSelectDb;
			if( null!== $setNames)
				$this->query("SET NAMES '$setNames'");
			return $this->db;
		}
	}

	/**
	* check and activate db connection
	* @param string $action (active, kill, check) active by default
	* @return string or bool
	*/
	function check_conn($action = ''){
		$host = false;
		if((! $this->conn) || ! $host = mysql_get_host_info($this->conn)){
			switch ($action){
				case 'kill':
					return $host;
					break;
				case 'check':
					return $host;
					break;
				default:
				case 'active':
					if(! $this->conn = mysql_connect($this->host,$this->user,$this->pass,self::$useNewLink)){
						$this->verbose("connection to $this->host failed",__FUNCTION__,1);
						return FALSE;
					}
					$this->verbose("connection to $this->host established",__FUNCTION__,2);
					$this->select_db();
					return mysql_get_host_info($this->conn);
					break;
			}
		}else{
			switch($action){
				case 'kill':
					mysql_close($this->conn);
					$this->conn = $this->db = null;
					return true;
					break;
				case 'check':
					return $host;
					break;
				default:
				case 'active':
					return $host;
					break;
			}
		}
	}
	/**
	* take a resource result set and return an array of type 'ASSOC','NUM','BOTH'
	* @see sqlitedb or mysqldb implementation for exemple
	* @return array
	*/
	function fetch_res($result_set,$result_type='ASSOC'){
		$result_type = strtoupper($result_type);
		if(! in_array($result_type,array('NUM','ASSOC','BOTH')) )
			$result_type = 'ASSOC';
		eval('$result_type = MYSQL_'.strtoupper($result_type).';');

		while($res[]=mysql_fetch_array($result_set,$result_type));
		unset($res[count($res)-1]);//unset last empty row

		$this->num_rows = mysql_affected_rows($this->conn);
		return count($res)?$res:FALSE;
	}

	/**
	*return the last inserted id if insert is made on a table with autoincrement field
	*@return mixed (certainly int)
	*/
	function last_insert_id(){
		return $this->conn?mysql_insert_id($this->conn):FALSE;
	}

	/**
	* there's a base method you should replace in the extended class, to use the appropriate escape func regarding the database implementation
	* @param string $quotestyle (both/single/double) which type of quote to escape
	* @return str
	*/
	function escape_string($string,$quotestyle='both'){
		$string = mysql_real_escape_string($string,$this->conn);
		switch(strtolower($quotestyle)){
			case 'double':
			case 'd':
			case '"':
				$string = str_replace("\'","'",$string);
			case 'single':
			case 's':
			case "'":
				$string = str_replace("\"",'"',$string);
				break;
			case 'both':
			case 'b':
			case '"\'':
			case '\'"':
				break;
		}
		return $string;
	}

	/**
	* perform a query on the database
	* @param string $Q_str
	* @return result id | FALSE
	**/
	function query($Q_str){
		if(! $this->db ){
			if(! (db::$autoconnect && $this->check_conn('active')))
				return FALSE;
		}
		$this->verbose($Q_str,__FUNCTION__,2);
		if(! $this->last_qres = mysql_query($Q_str,$this->conn))
			$this->set_error(__FUNCTION__);
		return $this->last_qres;
	}
	/**
	* perform a query on the database like query but return the affected_rows instead of result
	* give a most suitable answer on query such as INSERT OR DELETE
	* @param string $Q_str
	* @return int affected_rows or FALSE on error!
	*/
	function query_affected_rows($Q_str){
		if(! $this->query($Q_str) )
			return FALSE;
		$num = mysql_affected_rows($this->conn);
		if( $num == -1){
			$this->set_error(__FUNCTION__);
			return FALSE;
		}else{
			return $num;
		}
	}

	/**
	* get the table list from $this->dbname
	* @return array
	*/
	function list_tables(){
		if(! $tables = $this->query_to_array('SHOW tables','NUM') )
			return FALSE;
		foreach($tables as $v){
			$ret[] = $v[0];
		}
		return $ret;
	}
	/*
	* return the list of field in $table
	* @param string $table name of the sql table to work on
	* @param bool $extended_info if true will return the result of a show field query in a query_to_array fashion
	*                           (indexed by fieldname instead of int if false)
	* @return array
	*/
	function list_table_fields($table,$extended_info=FALSE){
		if(! $res = $this->query_to_array("SHOW FIELDS FROM $table"))
			return FALSE;
		if($extended_info)
			return $res;
		foreach($res as $row){
			$res_[]=$row['Field'];
		}
		return $res_;
	}

	/** Verifier si cette methode peut s'appliquer a SQLite */
	function show_table_keys($table){
		return $this->query_to_array("SHOW KEYS FROM $table");
	}
	/**
	* optimize table statement query
	* @param string $table name of the table to optimize
	* @return bool
	*/
	function optimize($table){
		return $this->query("OPTIMIZE TABLE $table");
	}
	function error_no(){
		return $this->conn?mysql_errno($this->conn):FALSE;
	}

	/**
	* @param void $errno only there for compatibility with other db implementation so totally unused there
	*/
	function error_str($errno=null){
		return mysql_error($this->conn);
	}
	/**
	* return an array of databases names on server
	* @return array
	*/
	function list_dbs(){
		if(! $dbs = $this->query_to_array("SHOW databases",'NUM'))
			return FALSE;
		foreach($dbs as $db){
			$dbs_[]=$db[0];
		}
		return $dbs_;
	}

	/**
	* dump the database to a file
	* @param string $out_file name of the output file
	* @param bool $droptables add 'drop table'  if set to true (defult=TRUE)
	* @param bool $gziped (default = TRUE) if set to true output will be compressed
	* @param gtkprogress &$progress is an optional progressbar to trace activity (will received a value between 0 to 100)
	*/
	function dump_to_file($out_file,$droptables=TRUE,$gziped=TRUE){
		set_time_limit(0); # deactivate time limit when doable for big database dumping
		if($gziped){
			if(! $fout = gzopen($out_file,'w'))
				return FALSE;
		}else{
			if(! $fout = fopen($out_file,'w'))
				return FALSE;
		}
		$entete = "# PHP class mysqldb SQL Dump\n#\n# Host: $this->host\n# generate on: ".date("Y-m-d")."\n#\n# Db name: `$this->dbname`\n#\n#\n# --------------------------------------------------------\n\n";
		if($gziped)
			gzwrite($fout,$entete);
		else
			fwrite($fout,$entete);
		$tables = $this->list_tables();
		foreach($tables as $table){
      $table_create = $this->query_to_array("SHOW CREATE TABLE $table",'NUM');
			$table_create = $table_create[0]; # now we have the create statement
			$create_str = "\n\n#\n# Table Structure `$table`\n#\n\n".($droptables?"DROP TABLE IF EXISTS $table;\n":'').$table_create[1].";\n";
			if($gziped)
				gzwrite($fout,$create_str);
			else
				fwrite($fout,$create_str);
			$i=0;#init line counter at the begining of a table
			if($tabledatas = $this->select_to_array($table)){ # put datas if available
				if($gziped)
					gzwrite($fout,"\n# `$table` DATAS\n\n");
				else
					fwrite($fout,"\n# `$table` DATAS\n\n");
				unset($stringsfields);$z=0;

				foreach($tabledatas as $row){
					unset($values,$fields);
					foreach($row as $field=>$value){
						if($i==0){ # on the first line we get fields
							$fields[] = "`$field`";
              if( mysql_field_type($this->last_qres,$z++) === 'string') # will permit to correctly protect number in string fields
								$stringsfields[$field]  = TRUE;
						}
            if(preg_match("!^-?\d+(\.\d+)?$!",$value) && empty($stringsfields[$field]) )
							$value = $value;
						elseif($value==null)
							$value =  $stringsfields[$field]?"''":"NULL";
						else
							$value = "'".$this->escape_string($value,"single")."'";
						$values[] = $value;
					}
          $insert_str = ($i==0?"INSERT INTO `$table` (".implode(',',$fields).")\n\tVALUES ":",\n\t")."(".implode(',',$values).')';
					if($gziped)
						gzwrite($fout,$insert_str);
					else
						fwrite($fout,$insert_str);
					$i++; # increment line number
				}
				if($gziped)
					gzwrite($fout,";\n\n");
				else
					fwrite($fout,";\n\n");
			}
		}
		if($gziped)
			gzclose($fout);
		else
			fclose($fout);
	}

	function __destruct(){
		parent::__destruct();
	}
}
