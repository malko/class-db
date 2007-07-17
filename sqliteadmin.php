#!/usr/bin/php
<?php
/**
* Sqlite Admin tool
* @changelog - 2007-07-17 - now can export a query to a csv file
*            - 2007-04-19 - start php5 port
*/
error_reporting(E_ALL);
$working_dir = getcwd();
chdir(dirname(__file__));
require('Console_app.php');
# require('class-db.php');
require('./class-sqlitedb.php');
# return to original path
chdir($working_dir);

#setting apps params and desc
$app = new console_app();

console_app::$lnOnRead = FALSE; # don't add a new line on read
console_app::$dflt_styles['table']['nolines'] = true; # no separation lines between results rows

$app->set_app_desc("SQLiteAdmin is a command line tool to manage SQLite database.
sqliteadmin [options] path/to/dbfile.db
");
$app->define_arg('mode','m','w','open database in (w)rite or (r)eadonly mode');
$app->define_arg('file','f','','file of SQL command to execute','is_file');
$app->define_flag('verbose','v',FALSE,'set verbose mode on error');
$app->define_flag('buffer',array('b','B'),TRUE,'set query buffer on or false');

if( function_exists('readline') ){
	readline_completion_function('autocompletion');
	$history_file = $_SERVER['HOME'].'/.sqliteadmin_hist';
	readline_read_history($history_file);
}

# read command line args
$app->parse_args();
$args = $app->get_args();
# check file validity
if(! isset($args[0]) ){
	$app->msg_error("No database file given",TRUE);
}
$dbfile = $args[0];
if( empty($dbfile) )
	console_app::msg_error("You must provide a path to your database file",true); # EXIT

if(! is_file($dbfile) ){
	if(! console_app::msg_confirm("database file $dbfile doesn't exist do you want to create it ? ") )
		exit();
	touch($dbfile);
}
$mode = $app->get_arg('mode');
# create a sqlitedb object
$db = &new sqlitedb($dbfile,'r');
$db->beverbose = $app->get_arg('verbose');
$db->buffer_on = $app->get_arg('buffer');

# some var use for csv import/export
$separator = ';';
$protect   = '';

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
			if(preg_match('!^tables\s*;$!i',strtolower(trim($args)))){
				if(! $res = $db->list_tables())
					console_app::msg_error("No Tables in database create some first!");
				foreach($res as $table)
					$tables[]=array('table name'=>$table,'nb row'=>$db->get_count($table));
				console_app::print_table($tables);unset($tables);
			}elseif(preg_match('!^(\w+)\s+fields\s*;$!i',strtolower(trim($args)),$m)){
				if(! $res = $db->list_table_fields($m[1]))
					console_app::msg_error($db->last_error['str']);
        # console_app::show($res);
				foreach($res as $field)
					$fields[]=array('field name'=>$field);
				console_app::print_table($fields);unset($fields);
			}
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
    case 'vacuum':
      $db->optimize(str_replace(';','',$args));
      break;
		case 'master':
			console_app::print_table($db->query_to_array('SELECT * FROM SQLITE_MASTER'));
			break;
		case 'select':
			$Q_str = check_query($read);
			# $app->show($Q_str);
			if(! $res = $db->query_to_array($Q_str))
				console_app::msg("No Result!");
			else
				console_app::print_table($res);
			break;
		case 'insert':
		case 'delete':
		case 'create':
		case 'drop':
		case 'update':
			$Q_str = check_query($read);
			perform_query($Q_str);
			break;
    case 'help':
    case 'h':
    case '?':
      display_help();
      break;
		case '';
			if(console_app::msg_confirm("Exit SQLiteAdmin ?"))
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
		return $query;
	}else{
		$buff .= $query;
		while(TRUE){
			$query = console_app::read('',null,FALSE);
			if( substr(trim($query),-1)==';' )
				break;
			$buff .= $query.' ';
		}
		return $buff.$query;
	}
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
?,h,help will display this help.
exit,quit,q                       quit application
tb,show tablename                 will list tables in the databases
show tablename fields             will list fields in table tablename
vacuum tablename                  optimize a table
master                            display the content of SQLITE_MASTER
SQL statements                    perform a query on the database such as select, 
                                  insert update or delete
export tablename filename         export the given table as a csv file
export SQL statements; filname    export the given query as csv to filename 
															    ( don't forget the ';' at the end of the query
import filename tablename         import the given csv file in table
";
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
  if( ($histsize = count($hist)) > $_SERVER['HISTSIZE'] ){
    $hist = array_slice($hist, $histsize - $_SERVER['HISTSIZE']);
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
	# console_app::show(func_get_args());
	if(! isset( $completion)){
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
