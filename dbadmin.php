#!/usr/bin/php
<?php
/**
* Sqlite Admin tool
* @changelog - 2008-03-20 - add the rehash command to refresh completion table
*                         - add maxcolsize and maxcolwidth command
*                         - bug correction that offer page navigation on agregation query (ie: select count(*) from...)
*            - 2008-03-19 - new possibility to call some callBack on tables to clean datas
*                         - add some methods to help using mbstring on entire tables
*                         - add support for use database command (only for mysqldb for now at least)
*                         - add verbosity command
*                         - now pagination on one page result + allow to change pagesize
*                         - verbose is not anymore a flag but an arg (forget last time)
*                          (today's modifs are not the better code i can write, only the one i have time for)
*            - 2007-12-19 - remove read/write mode argument and some other options
*                         - add support for any db extended class
*                         - first integration of pagination (quick and dirty)
*            - 2007-10-03 - remove some warning errors
*                         - add the whole last multiline command to history
*                         - if no HISTSIZE value is found in $_SERVER then default to console_app::$historyMaxLen
*            - 2007-07-17 - now can export a query to a csv file
*            - 2007-04-19 - start php5 port
* @todo add a config file support
*/
error_reporting(E_ALL);
$working_dir = getcwd();
chdir(dirname(__file__));
require('../../console_app/trunk/class-console_app.php');
require('class-db.php');

#setting apps params and desc
$app = new console_app();

console_app::$lnOnRead = FALSE; # don't add a new line on read
console_app::$dflt_styles['table']['nolines'] = true; # no separation lines between results rows

$app->set_app_desc("dbAdmin is a command line tool to manage databases.
dbadmin [options] databaseConnectionStr
where databaseConnectionStr depend on what type of database you want to use and according to the definition of the extended class-db constructor.
example connecting a mysql database:  dbadmin mysqldb://dbname,dbhost:port,dbuser,dbpass
example connecting a sqlite database: sqlitedb://dbfile,mode
if no database type is specified dbadmin will lookup for a sqlite local file.
");
$app->define_arg('file','f','','file of SQL command to execute','is_file');
$app->define_arg('verbose','v',1,'set level between 0 and 3 where 0 is no verbosity, 1 output only errors, 2 will echo all sql query before executing them and 3 will output both sql queries and errors.','is_numeric');

if( function_exists('readline') ){
	readline_completion_function('autocompletion');
	$history_file = $_SERVER['HOME'].'/.sqliteadmin_hist';
	readline_read_history($history_file);
}

# read command line args
$app->parse_args();
$args = $app->get_args();

# check file validity
if(empty($args[0]) ){
	$app->msg_error("No database connection string given",TRUE); # EXIT
}
$connectionStr = $args[0];

#- attempt to load the required class
$dbType = strtolower(preg_replace('!^(.*?)://.*$!','\1',$connectionStr));
if( empty($dbType) ){
  if( preg_match('!^.*?://!',$connectionStr) ){
    console_app::msg_error("please check your database connection string.",true);
  }else{
    $dbType = 'sqlitedb';
    $connectionStr = 'sqlitedb://'.$connectionStr;
  }
}

# now that include are done return to original path
chdir($working_dir);


$connectionStr = str_replace(',',';',$connectionStr);

#- check for file creation if sqlitedb
if($dbType==='sqlitedb'){
  $sqliteFile = preg_replace('!^(.*?://)?([^;]+)(;.*)?$!','\2',$connectionStr);
  if(! is_file($sqliteFile) ){
    if(! console_app::msg_confirm("database file $dbfile doesn't exist do you want to create it ? ") )
      exit();
    touch($dbfile);
  }
}

# create a db object
$db = db::getInstance($connectionStr);
$db->beverbose = $app->get_arg('verbose');
if(! $db->check_conn('active'))
	console_app::msg_error("database Connection failed",true);

# setting navigation parameters
$db->set_slice_attrs(array(
  'first' => "<<",
  'prev'  => "<",
  'next'  => ">",
  'last'   => ">>",
  'pages'  => "%page",
  'curpage'  => console_app::tagged_string('%page','bold|underline'),
  'linkSep'  => " ",
  'formatStr'=> " %first %prev %tot results, page %1links of %nbpages %next %last\nnavigate through results: "
));

# some var use for csv import/export
$separator = ';';
$protect   = '';

# mbstring settings
$mbStringAvailable  = function_exists('mb_convert_encoding')?true:false;
$mbStringDetectorder= 'UTF-8, ISO-8859-1, ISO-8859-15';
$mbStringDetectStrict= true;
$mbStringConvertTo   = 'UTF-8';

$_cmdBuff = '';
while(TRUE){
  $read = console_app::read('>',null,FALSE);
  # break;
  @list($cmd,$args) = explode(' ',trim($read),2);
  switch(strtolower($cmd)){
    case 'exit':
    case 'quit':
    case 'q':
      $db->close();
      break 2;
    case 'tb': $cmd = 'show';$args='tables;';
    case 'show':
    	$args = trim($args);
      if(preg_match('!^tables\s*;?$!i',$args)){
        if(! $res = $db->list_tables()){
          console_app::msg_error("No Tables in database create some first!");
        }else{
        	$tables = array();
          foreach($res as $table)
            $tables[]=array('table name'=>$table,'nb row'=>$db->get_count($table));
          console_app::print_table($tables);
          unset($tables);
        }
      }elseif(preg_match('!^(\w+)\s+fields(infos)?\s*;?$!i',$args,$m)){
      	$extended = empty($m[2])?false:true;
        if(! $res = $db->list_table_fields($m[1],$extended)){
          console_app::msg_error($db->last_error['str']);
          break;
        }
        #- ~ console_app::show($res);
				if(! $extended){
					foreach($res as $k=>$field)
						$res[$k]=array('Field'=>$field);
				}
				console_app::print_table($res);
      }elseif($db instanceof mysqldb && preg_match('!^d(bs|atabases)\s*;?$!i',$args) ){
				if(! $res = $db->query_to_array("show databases"))
          console_app::msg("No databases!");
        else
          console_app::print_table($res);
			}
      break;
    case 'mb_detectconvert':
    case 'mb_detectorder':
    case 'mb_detectstrict':
    case 'mb_setconvert':
    	if(! $mbStringAvailable){
    		console_app::msg_error('missing mbstring extension');
    		break;
    	}
    	if($cmd==='mb_detectorder'){
				$mbStringDetectorder = trim($args);
				break;
			}
			if($cmd==='mb_detectstrict'){
				$mbStringDetectStrict = preg_match('!^\s*true|on\s*$!i',$args)?true:false;
				break;
			}
			if($cmd==='mb_setconvert'){
				$mbStringDetectStrict = trim($args);
				break;
			}
			# do conversion
			if(! preg_match('!^(.*?)\s+filter:(.*?)$!',$args,$m) ){
				$tables = explode(',',$args);
				$filter = null;
			}else{
				$tables = explode(',',$m[1]);
				$filter = $m[2];
			}
			foreach($tables as $tb)
				callbackOnTable('detectConvert',$tb,$filter);
    	break;
    case 'maptable':
    	  @list($func,$tables) = preg_split('!\s+!',$args,2);
    	  if(empty($tables)){
					console_app::msg_error("Invalid aguments given to reencode");
					display_help();
					break;
				}
				if(! preg_match('!^(.*?)\s+filter:(.*?)$!',$tables,$m) ){
					$tables = explode(',',$tables);
					$filter = null;
				}else{
					$tables = explode(',',$m[1]);
					$filter = $m[2];
				}
				foreach($tables as $tb)
					callbackOnTable($func,$tb,$filter);
				break;
    	break;
    case 'import': # import from a csv file
      if(! preg_match('!^\s*(.*)\s+(\S+)(?:\s*;)?$!',trim($args),$m) ){
        console_app::msg_error("What do you mean?");
        break;
      }
      list(,$filename,$table) = $m;
      # demander le separateur / detecter le type de protecteur de champ / demander les noms des champs si non fournis
      $separator = console_app::read('enter char used as a field separator (for tab use \t) (dflt:'.$separator.')',$separator);
      $protect   = trim(console_app::read("enter char used to protect field values (dflt:$protect)",$protect));
      # recuperation des noms de champs si fournis
      if( console_app::msg_confirm("first row is fields name",'')){
        $fldnames = null;
      }else{
        if( !($fldnames = console_app::read("Enter fields names separated by '$separator' or leave blank to insert fields by ids")) ){
          $fldnames = $db->list_table_fields($table);
        }
      }
      if(! ($res = csv2array($filename,$fldnames,$separator)) ){
        console_app::msg_error("can't read $filename");
        break;
      }
      $inserted = $inserterr = 0;
      foreach($res as $row){
        if($protect)
          $row = preg_replace(array("!\\$protect!","!$protect!",),array($protect),$row);
        if($db->insert($table,$row))
          $inserted++;
        else
          $inserterr++;
      }
      if($inserted) console_app::msg("$inserted rows inserted",'green');
      if($inserterr) console_app::msg("$inserterr rows can't be inserted",'red');
      break;
    case 'export': # export a table to csv
      if(preg_match('!(select\s*.+?;)\s*([^;]+)$!i',trim($args),$m)){
        list(,$qStr,$filename) = $m;
        $res = $db->query_to_array($qStr);
      }else{
        list($table,$filename) = preg_split("!\s+!",$args,2);
        $res = $db->select_to_array($table);
      }
      if(! $res ){
        console_app::msg("No Result!");
      }else{
        if(! $f= fopen(trim($filename),'w') ){
          console_app::msg_error("Can't open $filename for writing");
          break;
        }
        $separator= console_app::read('enter char to use as a field separator (for tab use \t) (dflt:'.$separator.')',$separator);
        if(strlen($separator)>1 && $separator[0] =='\\'){ # allow \t and other \ chars to be entered
          eval('$separator = "'.$separator.'";');
        }elseif(! preg_match('!^\s+$!',$separator)){
          $separator = trim($separator);
        }
        $protect  = trim(console_app::read("enter char to use to protect field values (dflt:$protect)",$protect));
        # add headers
        if(console_app::msg_confirm("first row is field names",'')){
          array_unshift($res,array_keys($res[0]));
        }
        foreach($res as $row){
          if($protect)
            foreach($row as $k=>$v) $row[$k] = $protect.($protect?str_replace($protect,'\\'.$protect,$v):$v).$protect;
          fwrite($f,implode($separator,$row)."\n");
        }
        fclose($f);unset($f);
      }
      break;
    case 'optimise':
    case 'vacuum':
      $db->optimize(str_replace(';','',$args));
      break;
    case 'master':
      if($dbType!=='sqlitedb'){
        console_app::msg_info("master is a sqlitedb ONLY command.");
      }else{
        console_app::print_table($db->query_to_array('SELECT * FROM SQLITE_MASTER'));
      }
      break;
    case 'select':
      $Q_str = check_query($read);
      $clauseExp = '(?:\s+((where\s+|group\s+by\s+|order by\s+|having\s+|procedure\s+|limit\s+|for\s+update\s+|lock\s+in\s+share\s+).*?)?\s*)?';
      if(! preg_match('!^\s*select\s+(.*?)\s+from\s+(.*?)'.$clauseExp.';\s*$!si',$Q_str,$m)){
        #- ~ console_app::dbg($m);
        if(! $res = $db->query_to_array($Q_str))
          console_app::msg("No Result!");
        else
          console_app::print_table($res);
      }else{
        printPagedTable($m[2],$m[1],empty($m[3])?null:$m[3]);
      }
      break;
    case 'insert':
    case 'do':
    case 'truncate':
    case 'replace':
    case 'delete':
    case 'create':
    case 'drop':
    case 'update':
      $Q_str = check_query($read);
      perform_query($Q_str);
      break;
    case 'use':
    	if(! ($db instanceof mysqldb) ){
    		console_app::msg_info('only supported by mysqldb for now. (i\'m sure you can code and submit it, aren\'t you)?');
    		break;
    	}
      $Q_str = check_query($read);
      perform_query($Q_str);
    case 'rehash':
    case '#':
    	autocompletion();
    	break;
    case 'verbosity':
    	$db->beverbose = (int) $args;
    	break;
    case 'pagesize':
    	$pageSize = (int) $args;
    	break;
    case 'maxcolwidth':
    	console_app::$dflt_styles['table']['maxwidth']['dflt'] = (int) $args;
    	break;
    case 'maxcolsize':
			$maxColSize = (int) $args;
    	break;
    case 'help':
    case 'h':
    case '?':
      display_help();
      break;
    case '';
      if(console_app::msg_confirm("Exit dbAdmin ?"))
        break 2;
      break;
    default:
      echo "'$read'\n";
      console_app::msg("Unknown command");
  }
}
if( function_exists('readline') )
  save_history($history_file);

function check_query($query){
  global $app;
  $buff='';
  if( substr(trim($query),-1)==';' ){
    return history_append_multiline($query);
  }else{
    $buff .= $query;
    while(TRUE){
      $query = console_app::read('',null,FALSE);
      if( substr(trim($query),-1)==';' )
        break;
      $buff .= $query.' ';
    }
    return history_append_multiline($buff.$query);
  }
}

function history_append_multiline($cmd){
  if( function_exists('readline_add_history'))
    readline_add_history($cmd);
  return $cmd;
}

function perform_query($query){
  global $app,$db;
  echo "Perform: $query\n";
  if( ($ct = $db->query_affected_rows($query))===FALSE)
    return console_app::msg_error("Error");
  console_app::msg("Ok $ct rows changed",'green');
  return TRUE;
}

function display_help(){
  echo "
###--- Common commands ---###
?,h,help will display this help.
exit,quit,q                       quit application
show tablename fields[infos]      will list fields in table tablename
                                  with or without infos
tb,show tablename                 will list tables in the databases
show databases                    will list databases available on server
                                  (mysqldb only )
use databasename                  change the database to work on (same server)
                                  (mysqldb only )
optimise tablename                optimize a table
vacuum tablename                  alias for optimize
master                            display the content of SQLITE_MASTER
                                  (sqlitedb only)
SQL statements                    perform a query on the database such as
                                  select, insert update or delete
rehash,#                          refresh completion table

###--- Display settings ---###
verbosity n                       change verbose level while inside the app
pagesize n                        set the number of results by page
maxcolwidth n                     set the max cols width to n
maxcolsize n                      set the max chars in cols to n
                                  (results will be truncated)
###--- Paging commands (only when displaying results) ---###
n                                 go directly to page n
<, >, <<, >>                      respectively go to
                                  previous, next, first and last page
pagesize n                        set the number of results by page

###--- Import/Export datas from/to csv files ---###
export tablename filename         export the given table as a csv file
export SQL statements; filename   export the given query as csv to filename
                                  (don't forget the ';' at the end of query)
import filename tablename         import the given csv file in table

###--- Datas cleaning methods ---###
maptable callback tablename [filter:condition] [PK:field_primaryKey]
                                  allow you to array_map a phpfunction
                                  on all datas in given table
mb_detectconvert tablename [filter:condition] [PK:field_primaryKey]
                                  try to convert datas charset in given table
                                  this REQUIRE mbstring extension to be loaded
                                  and is totally independant of the database
                                  settings
mb_detectorder charsets           set the mb_string detect order see php manual
                                  for more info
                                  (default: UTF-8,ISO-8859-1,ISO-8859-15)
mb_detectstrict true|false        set mb_detect_encoding strict parameter
                                  (default: true)
mb_setconvert  charset            set output encoding for mb_convert_encoding
                                  (default: UTF-8)
";
}

/** remove accented chars (iso-8859-1 and UTF8) */
function removeMoreAccents($str){
	static $convTable;
	# create conversion table on first call
	if(! isset($convTable) ){
		$tmpTable = array(
			'µ'=>'u',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'AE',
			'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
			'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ð'=>'D', 'Ñ'=>'N',
			'Ò'=>'O', 'Œ'=>'OE', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O',
			'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'ß'=>'s',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae',
			'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
			'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ñ'=>'n',
			'ð'=>'o', 'œ'=>'oe', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o',
			'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ÿ'=>'y',
			'’'=>'\'','`'=>'\'',
		);
		$keys  = array_keys($tmpTable);
		$values= array_values($tmpTable);
		# check internal encoding
		if(ord('µ')===194){ # we are already in utf 8
			$utf8keys = $keys;
			$keys     = array_map('utf8_decode',$keys);
			$utf8x2keys = array_map('utf8_encode',$utf8keys);
		}else{
			$utf8keys = array_map('utf8_encode',$keys);
			$utf8x2keys = array_map('utf8_encode',$utf8keys);
		}
		if(function_exists('array_combine')){
			$convTable = array_merge(array_combine($utf8x2keys,$values),array_combine($utf8keys,$values),array_combine($keys,$values));
		}else{
			foreach($utf8keys as $n=>$k){
				$convTable[$utf8x2keys[$k]] = $convTable[$k] = $convTable[$keys[$n]] = $values[$n];
			}
		}
	}
	if(is_array($str)){
		foreach($str as $k=>$v)
			$str[$k] = strtr($v,$convTable);
		return $str;
	}
	return strtr($str,$convTable);
}

function detectConvert($str){
	global $mbStringAvailable,$mbStringDetectorder,$mbStringDetectStrict,$mbStringConvertTo;
	return mb_convert_encoding($str,$mbStringConvertTo,mb_detect_encoding($str,$mbStringDetectorder, $mbStringDetectStrict));
}

/**
* apply a callback on a table datas using array_map on each row
* @param mixed  $callBack  any valid callback
* @param string $table     name of the table where apply the callback
* @param mixed  $filter    filter to select data as in class-db conds
*/
function callbackOnTable($callBack,$table,$filter=null){
	global $db;
	$rows = $db->select_to_array($table,'*',$filter);
  if(! $rows )
  	return console_app::msg_info("no result from $table.");
  console_app::progress_bar($i=0,"applying $callBack on $table",count($rows));
  #- ~ check for primary key
  $fieldInfos = $db->list_table_fields($table,true);
  $PK = false;
  foreach($fieldInfos as $f){
		if($f['Key']==='PRI'){
			$PK = $f['Field'];
			break;
		}
	}
  foreach($rows as $row){
  	console_app::refresh_progress_bar(++$i);
  	if($PK){
  		$where = array("WHERE $PK=?",$row[$PK]);
		}else{
			$where = array('WHERE '.implode('=? AND ',array_keys($row)).'=?');
			$where = array_merge($where,array_values($row));
		}
		$row = array_map($callBack,$row);
		$db->update($table,$row,$where);
		/**
		$row = array_map($callBack,$row);
    $db->update($t,$row,array("WHERE $pk=?",$row[$pk]));
    */
  }
}
/**
* print table and manage page navigation
* @param array $sliceRes result as returned by db::select_array_slice()
*/
function printPagedTable($table,$fields,$conds,$pageId=1){
  global $pageSize,$db,$maxColSize;
  if(! isset($pageSize) )$pageSize = 10;
  $res = $db->select_array_slice($table,$fields,$conds,$pageId,$pageSize);

  if(! $res )
    return console_app::msg("No Result!");

  # on affiche le tableau:
  list($results,$nav,$total) = $res;
  if( !empty($maxColSize) ){
  	foreach($results as $k=>$row)
			$results[$k] = array_map('truncateMap',$row);
	}
  console_app::print_table($results);

	# no navigation on unique page
  if($total <= $pageSize || ($pageId===1 && count($results) < $pageSize) )
  	return;

  # affiche la navigation
  $e = console_app::read($nav);
  $lastPage = ceil($total/$pageSize);

  if( preg_match('!^\s*pagesize (\d+)\s*!i',$e,$m) ){
		$pageSize = (int) $m[1];
		return printPagedTable($table,$fields,$conds,1);
	}

  if( is_numeric($e) ){ # numero de page on rappel la fonction avec le num de page
    if($e < 1)
      $e = 1;
    elseif( $e > $lastPage)
      $e = $lastPage;
    return printPagedTable($table,$fields,$conds,$e);
  }

  if( $e === '>' ) # page suivante
    return printPagedTable($table,$fields,$conds,min($lastPage,$pageId+1));

  if( $e === '<' ) # page precedante
    return printPagedTable($table,$fields,$conds,max(1,$pageId-1));

  if( $e === '<<' ) # first page
    return printPagedTable($table,$fields,$conds,1);

  if( $e === '>>' ) # last page
    return printPagedTable($table,$fields,$conds,$lastPage);

  return;
}

function truncateMap($str){
	global $maxColSize;
	if(strlen($str)<=$maxColSize)
		return $str;
	return substr($str,0,max(1,$maxColSize-3)).($maxColSize>4?"...":"");
}

/**
* read a csv file and return an indexed array.
* @param string $cvsfile path to csv file
* @param array $fldnames array of fields names. Leave this to null to use the first row values as fields names.
* @param string $sep string used as a field separator (default ';')
* @param array  $filters array of regular expression that row must match to be in the returned result.
*                        ie: array('fldname'=>'/pcre_regexp/')
* @return array
*/
function csv2array($csvfile,$fldnames=null,$sep=';',$filters=null){
  if(! $csv = file($csvfile) )
    return FALSE;
  # use the first line as fields names
  if( is_null($fldnames) ){
    $fldnames = array_shift($csv);
    $fldnames = explode($sep,$fldnames);
    $fldnames = array_map('trim',$fldnames);
  }elseif( is_string($fldnames) ){
    $fldnames = explode($sep,$fldnames);
    $fldnames = array_map('trim',$fldnames);
  }
  $i=0;
  foreach($csv as $row){
  	$row = preg_replace('!(\\\\r)?\\\\n!',"\n",$row);
    $row = explode($sep,trim($row));
    foreach($row as $fldnb=>$fldval)
      $res[$i][(isset($fldnames[$fldnb])?$fldnames[$fldnb]:$fldnb)] = $fldval;
    if( is_array($filters) ){
      foreach($filters as $k=>$exp){
        if(! preg_match($exp,$res[$i][$k]) )
          unset($res[$i]);
      }
    }
    $i++;
  }
  return $res;
}

function save_history($history_file){
  global $app;
  # dump history to file if needed
  if(! readline_write_history($history_file) ){
    console_app::msg_error("Can't write history file");
  }
  # nettoyage de l'historique
  $hist = readline_list_history();
  $histMaxSize = isset($_SERVER['HISTSIZE'])?$_SERVER['HISTSIZE']:console_app::$historyMaxLen;
  if( ($histsize = count($hist)) > $histMaxSize ){
    $hist = array_slice($hist, $histsize - $histMaxSize);
    if(! $fhist = fopen($history_file,'w') ){
      console_app::msg_error("Can't open history file");
    }else{
      fwrite($fhist,implode("\n",$hist));
      fclose($fhist);
    }
  }
}

function autocompletion(){
  global $db;
  static $completion;
  if( ! func_num_args() )
  	$completion = array() ;
  #- ~ console_app::show(func_get_args());
  if( empty($completion) ){
  	$completion = array(
			'show','use','optimise','vacuum','master','verbosity','pagesize',
			'export','import','maptable','mb_detectconvert','mb_detectorder',
			'mb_detectstrict','mb_setconvert','rehash','maxcolwidth','maxcolsize'
  	);
    if( $tables = $db->list_tables())
      foreach($tables as $table){
        $completion[] = $table;
        if($fields = $db->list_table_fields($table,FALSE))
          foreach($fields as $fld)
        		$completion[] = $fld;
      }
  }
  return $completion;
}

##### ARGS VALIDATION FUNCTIONS #####
function valid_mode($mode){
  if(! in_array($mode,array('r','w','0666','0444')) )
    return FALSE;
  switch($mode){
    case '0444':
      return 'r';
    case '0666':
      return 'w';
  }
}
?>
