#!/usr/bin/php
<?php
/**
* script de remplacement de valeurs dans la base de données.
*/


$working_dir = getcwd();
chdir(dirname(__file__));
require('libs/class-console_app.php');
require('../class-db.php');

ini_set('default_charset','utf-8');
#setting apps params and desc
$app = new console_app();

$app->set_app_desc("dbreplace is a command line tool to search and replace a pattern in a full database.
dbreplace [options] databaseConnectionStr
where databaseConnectionStr depend on what type of database you want to use and according to the definition of the extended class-db constructor.
example connecting a mysql database:  dbadmin mysqldb://dbname,dbhost:port,dbuser,dbpass
example connecting a sqlite database: sqlitedb://dbfile
");
$app->define_arg('search','s',null,'PCRE_REGEX without delimiters');
$app->define_arg('replace','r','','Replacement string (as in preg_replace can contain some reference to captured content). If replacement is not set then you will be asked it on each match');
$app->define_arg('table','t','','Limit replacement to the given table');
$app->define_arg('verbose','v',1,'Set level between 0 and 3 where 0 is no verbosity, 1 output only errors, 2 will echo all sql query before executing them and 3 will output both sql queries and errors.','is_numeric');

$app->define_flag('withconfirm',array('c','C'),false,"If true then will ask manual confirmation for each match before replacement");
$app->define_flag('caseinsensitive',array('i','I'),false,"Set search as case insensitive");

$app->define_flag('no-replace',null,false,'If set then will not perform the update in database and just show a report of matches found');


#- console_app::$dflt_styles['table']['nolines'] = true;
console_app::$dflt_styles['table']['maxwidth']['dflt'] = 25;

# read command line args
$app->parse_args();
$args = $app->get_args();


# check database validity
if(empty($args[0]) ){
	$app->msg_error("No database connection string given",TRUE); # EXIT
}
db::$_default_verbosity = $args['verbose'];
$connectionStr = $args[0];

$db = db::getInstance(str_replace(',',';',$connectionStr));
if( (! $db instanceof db)  || ! $db->open() ){
	console_app::msg_error("Erreur lors de la connection à la base de données",true);
}

#-- here start the program don't edit further
$tables = $args['table']?array($args['table']):$db->list_tables();
if( empty($tables) ){
	console_app::msg_info("no tables found...");exit(0);
}

$searchExp = '/'.str_replace('/','\/',$args['search']).'/'.($args['caseinsensitive']?'i':'');
$replace = $args['replace'] = $args['replace']?$args['replace']:null;
foreach( $tables as $t){
	#- recupere la liste des champs
	if( ! $db->get_count($t))
		continue;
	$fields = $db->list_table_fields($t,false);
	$pk = null;
	#lookup for primary key
	if( $keys = $db->show_table_keys($t) ){
		foreach($keys as $k){
			if( stripos($k['Key_name'],'PRI') !== false ){
				$pk = $k['Column_name'];
				break;
			}
		}
	}
	if( $pk ){
		$_conds = 'WHERE '.$db->protect_field_names($pk).'=?';
	}else{
		$_conds = array();
		foreach($fields as $f){
			$_conds[] = $db->protect_field_names($f).'=?';
		}
		$_conds = 'WHERE '.implode(' AND ',$_conds);
	}

	$rows = $db->select_rows($t);
	if( empty($rows)){
		continue;
	}
	$_rows = array();
	foreach($rows as $row){
		$matchFound = 0;
		$_row = $row;
		foreach($fields as $f){
			if( preg_match($searchExp,$row[$f]) ){
				$matchFound++;
				$_row[$f] = preg_replace($searchExp,console_app::tagged_string('$0','reverse'),$row[$f]);
				$row[$f]  = preg_replace($searchExp,$replace,$row[$f],-1,$res);

				/*
				if( $args['verbose'] > 1 || $args['withconfirm'] || $args['replace'] === null){
					$_row[$f] = preg_replace($searchExp,console_app::tagged_string('$0','reverse'),$row[$f]);
					console_app::print_table(array($_row));
					if(! console_app::msg_confirm("Match found in table ".console_app::tagged_string("$t.$f",'reverse')." do you want to replace ?",null,true) )
						continue;
				}
				if( $args['replace'] === null ){
					$replace = console_app::msg_read('Please enter replacement string (default: '.$replace.')','normal',$replace);
				}

				if( $pk !== null){
					$conds = array('WHERE '.$db->protect_field_names($pk).'=?',$row[$pk]);
				}else{
					$conds = array_values($row);
					array_unshift($conds,$_conds);
				}
				$row[$f] = preg_replace($searchExp,$replace,$row[$f],-1,$res);
				if( $res < 1 ){
					console_app::msg_error('replacement error');
					continue;
				}
				*/

			}

		}//<- end of field return to row level

		if( $matchFound > 0){
			$_rows[] = $_row;
			if( $args['no-replace'] )
				continue;
			if( $args['withconfirm'] || $args['verbose'] > 1){
				console_app::print_table(array($_row,$row));
				if( $args['withconfirm'] ){
					if(! console_app::msg_confirm("Match found in table ".console_app::tagged_string($t,'reverse')." do you want to replace ?",null,true) )
						continue;
				}
			}
			if( $pk !== null){
				$conds = array($_conds,$row[$pk]);
			}else{
				$conds = array_values($row);
				array_unshift($conds,$_conds);
			}
			$db->update($t,$row,$conds);
		}

	}//<- end of row return to table level
	if( $args['no-replace'] ){
		console_app::msg('Found '.count($_rows).' matching rows in '.$t,'bold');
		console_app::print_table($_rows);
	}
}