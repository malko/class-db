<pre><?php
/**
* sample script for class-mysqldb
*/

require('class-mysqldb.php');

$dbname = 'test';
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';

# instantiate database object with autoconnection
$db = mysqldb($dbname,$dbhost,$dbuser,$dbpass);
# put on the devel mode
$db->beverbose = true;

# then create a test table
$Qstr = "CREATE TABLE `test` (
  `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `date` DATE NOT NULL ,
  `desc` VARCHAR( 255 ) NOT NULL
)";
$db->query($Qstr);

# insert some records and getting the inserted ids if the table as an auto increment field.
$id[] = $db->insert('test',array('date'=>'2007-01-01','desc'=>"some text to insert as first row"));
$id[] = $db->insert('test',array('date'=>'2007-01-02','desc'=>"some text to insert as second row"));
echo "inserted ids: ".print_r($ids,1)."\n";

# if your table doesn't have an auto increment field and/or you only want a bool value
# to know the status of the insert then add false as last parameter
if($db->insert('test',array('date'=>'2007-02-01','desc'=>"insert without getting the rowid")))
  echo "insert succeed";
else
  echo "Error while inserting\n";

# we can get some info on the database and tables 
echo "list tables in $dbname:";
print_r($db->list_tables());

echo "list fields from test table:";
echo "only names";
print_r($db->list_table_fields('test'));
echo "with full info";
print_r($db->list_table_fields('test',true));

echo "get table keys:";
print_r($db->show_table_keys('test'));

echo "get nb rows from test table:";
print_r($db->get_count('test'));

# then we have a lot of method to retrieve easily datas from the table.
echo "select all table as an array:";
print_r($db->select_to_array('test'));

echo "select only date and descs with a condition string:";
print_r($db->select_to_array('test','date,desc','WHERE `date` >= "2007-01-02" ORDER BY `date` desc'));

echo "select a single row as an array using smart params:";
print_r($db->select_single_to_array('test','*', array('`date` = ?','2007-01-02')));

echo "select all rows as an array indexed by the date field (supposing they are unique):";
print_r($db->select2associative_array('test','*',null,'date'));

echo "select a single value in the table: ".$db->select_single_value('test','date',array("WHERE id = ?",2))."\n";

echo "select multiple values in a unique column:";
print_r($db->select_field_to_array('test','date'));

# make an update
echo "update first row:";
$db->update('test',array('date'=>'2007-02-02'),array("WHERE id = ?",$ids[1]));
print_r($db->select_to_array('test'));

# now delete some rows
echo "delete some rows:";
$db->delete('test',array("WHERE id < 2"));
print_r($db->select_to_array('test'));


# set_slice_attrs
# select_array_slice($table,$fields='*',$conds=null,$pageId=1,$pageNbRows=10)


?>
</pre>