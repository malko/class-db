<?php
/**
* @author Jonathan Gotti <nathan at the-ring dot homelinux dot net>
* @copyleft (l) 2003-2004  Jonathan Gotti
* @package DB
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @subpackage SQLITE
* @changelog 2005-02-25 now the associative_array_from_q2a_res method won't automaticly ksort the results anymore
*                       re-enable the possibility to choose between SQLITE_ASSOC or SQLITE_NUM
*            2005-02-28 new method optimize and vacuum
*            2005-04-05 get_fields will now try to get fields from sqlite_master if no data found in the table
* @todo add transactions
*/

# load the extension if needed
if(! extension_loaded('php_sqlite') && (strtoupper(substr(PHP_OS, 0,3)) == 'WIN'))
  dl('php_sqlite.dll');
elseif(! extension_loaded('sqlite') && (strtoupper(substr(PHP_OS, 0,3)) != 'WIN'))
  dl('sqlite.so');

/** class to deal with a sqlite database */
class sqlitedb{

  /**
  * create a sqlitedb object for managing locale data 
  * if DATA_PATH is define will force access in this directory
  * @param string $Db_file
  * @return sqlitedb object
  */
  function sqlitedb($db_file,$mode=null){
    $this->autocreate=FALSE;
    # readwrite mode to open database
    switch ($mode){
      case 'r':
        $mod = 0444;
        break;
      case 'w':
        $mod = 0666;
        $this->autocreate = TRUE;
        break;
      default:
        if(is_numeric($mode))
          $mod = $mode;
    }
    $this->mode       = ($mod?$mod:0666);
    $this->buffer_on  = TRUE;
    $this->autoconnect= TRUE;
    $this->open($db_file);
    $this->beverbose    = FALSE;
  }

  /**
  * open local $db_file, and create it if needed and $this->autocreate = TRUE
  * @param  string $Db_file
  * @return dbhandler | FALSE
  **/
  function open($db_file){
    //prevent multiple db open
    if(@$this->db)
      return $this->db;
    if(! $this->db_file = $this->check_file($db_file,1)){
      $this->db_file = $db_file;
      if(! $this->autocreate){
        return false;
      }
      if( DATA_PATH == substr($db_file,0,strlen(DATA_PATH) ))
        $this->db_file = $db_file;
      else
        $this->db_file = DATA_PATH.$db_file;
    }
    if( $this->db = sqlite_open($this->db_file, $this->mode, $this->error)){
      return $this->db;
    }else{
      echo $this->error;
      return FALSE;
    }
  }
  function close(){
    if( $this->db !==null )
      sqlite_close($this->db);
    $this->db = null;
  }
  /**
  * get the table list from $this->dbname
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
  /**
  * send a select query to $table with arr $fields requested (all by default) and with arr $conditions
  * sample conds array is array(0=>'field1 = field2','ORDER'=>'field desc','GROUP'=>'fld')
  * @param str|array $Table
  * @param str|array $fields
  * @param str|array $conditions
  * @param SQLITE_CONST $res_type
  * @Return  array | false
  **/
  function select_to_array($tables,$fields = '*', $conds = null,$result_type = SQLITE_ASSOC){
    # we make the table list for the Q_str
    if(! $tb_str = $this->array_to_str($tables))
      return FALSE;
    # we make the fields list for the Q_str
    if(! $fld_str =  $this->array_to_str($fields))
      $fld_str = '*';
    # now the WHERE str
    if($conds)
      $conds_str = $this->process_conds($conds);
    $Q_str = "SELECT $fld_str FROM $tb_str $conds_str";
    return $this->query_to_array($Q_str,$result_type);
  }
  
  /**
  * Same as select_to_array but return only the first row.
  * equal to $res = select_to_array followed by $res = $res[0];
  * @see select_to_array for details
  * @return array of fields
  */
  function select_single_to_array($tables,$fields = '*', $conds = null,$result_type = SQLITE_ASSOC){
    if(! $res = $this->select_to_array($tables,$fields,$conds,$result_type))
      return FALSE;
    return $res[0];
  }
  /**
  * select a single value in database
  * @param string $table
  * @param string $field the field name where to pick-up value
  * @param mixed conds
  * @return mixed or FALSE
  */
  function select_single_value($table,$field,$conds=null){
    if($res = $this->select_single_to_array($table,$field,$conds,SQLITE_NUM))
      return $res[0];
    else
      return FALSE;
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
  * return the result of a query to an array
  * @param string $Q_str SQL query
  * @return array | false if no result
  */
  function query_to_array($Q_str,$result_type=SQLITE_ASSOC){
    unset($this->last_q2a_res);
    if(! $this->query($Q_str))
      return FALSE;
    while($res[]=sqlite_fetch_array($this->last_qres,$result_type));
    //unset last empty row
    unset($res[count($res)-1]);

    if($this->buffer_on)
      $this->num_rows = sqlite_num_rows($this->last_qres);
    else
      $this->num_rows = count($res)-1;
    return $this->last_q2a_res = count($res)?$res:FALSE;
  }
  /**
  * Send an insert query to $table
  * @param string $table
  * @param array $values array(FLD=>VALUE,...)
  * @param bool $returnid default value is TRUE else will return bool
  * @return insert id or bool depend on $returnid
  */
  function insert($table,$values,$returnid=TRUE){
    if(!is_array($values))
      return FALSE;
    foreach( $values as $k=>$v){
      $fld[]= $k;
      if(is_null($v))
        $val[]= 'NULL';
      else
        $val[]= "'".sqlite_escape_string($v)."'";
    }
    $Q_str = "INSERT INTO $table (".$this->array_to_str($fld).") VALUES (".$this->array_to_str($val).")";
    # echo $Q_str;
    if(! $this->query($Q_str))
      return FALSE;
    if($returnid)
      return $this->last_id = sqlite_last_insert_rowid($this->db);
    else
      return TRUE;
  }
  /**
  * Send a delete query to $table
  * @param string $table
  * @param mixed $conds 
  */
  function delete($table,$conds){
  
    # now the WHERE str
    if($conds)
      $conds_str = $this->process_conds($conds);
    
    $Q_str = "DELETE FROM $table $conds_str";
    # echo $Q_str;
    return $this->query($Q_str);
  }
  /**
  * Send an update query to $table
  * @param string $table
  * @param array $values array(FLD=>VALUE,...)
  * @return updateid or FALSE
  */
  function update($table,$values,$conds = null){
    if(is_array($values)){
      foreach( $values as $k=>$v){
        if(is_null($v))
          $str[]= " $k = NULL";
        else
          $str[]= " $k = '".sqlite_escape_string($v)."'";
      }
    }elseif(! is_string($values)){
      return FALSE;
    }
    # now the WHERE str
    if($conds)
      $conds_str = $this->process_conds($conds);
      
    $Q_str = "UPDATE $table SET ".(isset($str)?$this->array_to_str($str):$values)." $conds_str";
    return $this->query($Q_str);
  }
  /**
  * method to be compliant with mysql db
  * @param string $table table to optimize
  * @return bool;
  */
  function optimize($table){
    return $this->vacuum($table);
  }
  /**
  * sqlitedb specific method to use the vacuum statement (used as replacement for mysql optimize statements)
  * @param string $table_or_index name of table or index to vacuum
  * @return bool
  */
  function vacuum($table_or_index){
    return $this->query("VACUUM $table_or_index;");
  }
  /**
  * perform a query on the database
  * @param string $Q_str
  * @return resultid | FALSE
  */
  function query($Q_str){
    if(! $this->db ){
      if(! ($this->autoconnect && $this->open($this->db_file)))
        return FALSE;
    }
    if($this->buffer_on)
      $this->last_qres = sqlite_query($this->db,$Q_str);
    else
      $this->last_qres = sqlite_unbuffered_query($this->db,$Q_str);
    if(! $this->last_qres)
      $this->set_error(__FUNCTION__);
    return $this->last_qres;
  }
  /**
  * return the list of field in $table 
  * @param string $table name of the sql table to work on
  * @return array
  */
  function get_fields($table){
    # Try the simple method 
    if( $res = $this->query_to_array("SELECT * FROM $table LIMIT 0,1")){
      # We had some data in the table so we continue on the simple way
      foreach($res[0] as $k=>$v)
        $r[]=$k; # get field names by keys
      return $r;
    }else{
      # There 's no row in this table so we try an alternate method
      if(! $fields = $this->query_to_array("SELECT sql FROM sqlite_master WHERE type='table' AND name ='$table'") )
        return FALSE;
      # get fields from the create query
      $fields = $fields[0]['sql'];
      $fields = preg_replace("!create[^(]+\((.*)\)\s*;?$!si",'\\1',$fields);
      $fields = split(',',$fields);
      if(is_array($fields)){
        foreach($fields as $fld)
          $ret[] = preg_replace('!\W+(\w+)\W+.*!s','\\1',$fld); # clean the fieldname
        return $ret;
      }
      # We have'nt success in any way so return FALSE
      return FALSE;
    }
  }
  /**
  * get the number of row in $table
  * @param string $table table name
  * @return int
  */
  function get_count($table){
    $c = $this->query_to_array("SELECT count(*) as c FROM $table");
    return $c[0]['c'];
  }
  
  /**
  * return an associative array indexed by $index_field with values $value_fields from 
  * a sqlitedb->select_to_array result
  * @param string $index_field default value is id
  * @param mixed $value_fields (string field name or array of fields name default is null so keep all fields
  * @parama array $res the sqlitedb->select_to_array result
  * @param bool $keep_index if set to true then the index field will be keep in the values associated (unused if $value_fields is string)
  * @return array
  */
  function associative_array_from_q2a_res($index_field='id',$value_fields=null,$res = null,$keep_index=FALSE){
    if($res===null)
      $res = $this->last_q2a_res;
    if(! is_array($res)){
      $this->verbose("[error] sqlitedb::associative_array_from_q2a_res with invalid result\n",__FUNCTION__);
      return FALSE;
    }
    # then verify index exists
    if(!isset($res[0][$index_field])){
      $this->verbose("[error] sqlitedb::associative_array_from_q2a_res with invalid index field '$index_field'\n",__FUNCTION__);
      return FALSE;
    }
    # then we do the trick
    if(is_string($value_fields)){
      foreach($res as $row){
          $associatives_res[$row[$index_field]] = $row[$value_fields];
      }
    }elseif(is_array($value_fields)||$value_fields===null){
      foreach($res as $row){
        $associatives_res[$row[$index_field]] = $row;
        if(!$keep_index)
          unset($associatives_res[$row[$index_field]][$index_field]);
      }
    }
    if(! count($associatives_res))
      return FALSE;
    # ksort($associatives_res);
    return $this->last_q2a_res = $associatives_res;
  }
  /*########## INTERNAL METHOD ##########*/
  /**
  * used by other methods to parse the conditions param of a QUERY
  * @param string|array $conds
  * @return string
  * @private
  */
  function process_conds($conds=null){
    if(is_array($conds)){
      $WHERE = ($conds[WHERE]?'WHERE '.$this->array_to_str($conds[WHERE]):'');
      $WHERE.= ($WHERE?' ':'').$this->array_to_str($conds);
      $GROUP = ($conds[GROUP]?'GROUP BY '.$this->array_to_str($conds[GROUP]):'');
      $ORDER = ($conds[ORDER]?'ORDER BY '.$this->array_to_str($conds[GROUP]):'');
      $LIMIT = ($conds[LIMIT]?'LIMIT '.$conds[LIMIT]:'');
      $conds_str = "$WHERE $ORDER $GROUP $LIMIT";
    }elseif(is_string($conds)){
      $conds_str = $conds;
    }
    return $conds_str;
  }
  /**
  * Handle sqlite Error
  * @private
  */
  function set_error($callingfunc=null){
    static $i=0;
    if(! $this->db ){
      $this->error[$i]['nb']  = null;
      $this->error[$i]['str'] = '[ERROR] No Db Handler';
    }else{
      $this->error[$i]['nb']  = sqlite_last_error($this->db);
      $this->error[$i]['str'] = sqlite_error_string($this->error[$i]['nb']);
    }
    $this->last_error = $this->error[$i];
    $this->verbose($this->error[$i]['str'],$callingfunc);
    $i++;
  }
  
  /**
  * check if a db file exists or look in the default path
  * @param string $file_path
  * @param bool $out FALSE set it to true to get the path in return in place or TRUE
  * @Return bool | string valid_path
  */
  function check_file($f_path,$out=null){
    if(file_exists($f_path))
      return ($out!=null?$f_path:TRUE);
    if(file_exists(DATA_PATH.$f_path))
      return ($out!=null?DATA_PATH.$f_path:TRUE);
    return FALSE;
  }
  
  function array_to_str($var,$sep=','){
    if(is_string($var)){
      return $var;
    }elseif(is_array($var)){
      return implode($sep,$var);
    }else{
      return FALSE;
    }
  }
  
  /**
  * print a msg on STDOUT if $this->beverbose is set to true
  * @param string $string
  * @private
  */
  function verbose($string,$callingfunc=null){
    if($this->beverbose)
      echo 'sqlite'.($callingfunc?"::$callingfunc ":' ')."=> $string\n";
  }
}
?>
