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
$db = new mysqldb($dbname,$dbhost,$dbuser,$dbpass);
# put on the devel mode
$db->beverbose = true;

# then create a test table
$Qstr = "CREATE TABLE `test` (
  `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `date` DATE NOT NULL ,
  `description` VARCHAR( 255 ) NOT NULL
)";
$db->query($Qstr);

# insert some records and getting the inserted ids if the table as an auto increment field.
$ids[] = $db->insert('test',array('date'=>'2007-01-01','description'=>"some text to insert as first row"));
$ids[] = $db->insert('test',array('date'=>'2007-01-02','description'=>"some text to insert as second row"));
echo "inserted ids: ".print_r($ids,1)."\n";

# if your table doesn't have an auto increment field and/or you only want a bool value
# to know the status of the insert then add false as last parameter
if($db->insert('test',array('date'=>'2007-02-01','description'=>"insert without getting the rowid")))
  echo "insert succeed\n";
else
  echo "Error while inserting\n";

# we can get some info on the database and tables 
echo "list tables in $dbname:\n";
print_r($db->list_tables());

echo "list fields from test table:\n";
echo "- only names\n";
print_r($db->list_table_fields('test'));
echo "- with full info\n";
print_r($db->list_table_fields('test',true));

echo "get table keys:\n";
print_r($db->show_table_keys('test'));

echo "get nb rows from test table:\n".$db->get_count('test')."\n";

echo "get the 'protected' date field: ".($fldDate = $db->protect_field_names('date'))."\n";

# then we have a lot of method to retrieve easily datas from the table.
echo "select all rows as an array:\n";
print_r($db->select_to_array('test'));

echo "select only date and descriptions with a condition string:\n";
print_r($db->select_to_array('test',$db->protect_field_names(array('date','description')),"WHERE $fldDate >= '2007-01-02' ORDER BY $fldDate desc"));

echo "select a single row as an array using smart params:\n";
print_r($db->select_single_to_array('test','*', array("WHERE $fldDate = ?",'2007-01-02')));

echo "select all rows as an array indexed by the date field (supposing they are unique):\n";
print_r($db->select2associative_array('test','*',null,'date')); 
echo "<small>note about above, the date field doesn't need to be protect there as it's an array key not a field in the query</small>\n";

echo "select all description indexed by ids:\n";
print_r($db->select2associative_array('test','id,description',null,'id','description'));


echo "select a single value in the table: \n".$db->select_single_value('test',$fldDate,array("WHERE id = ?",2))."\n";

echo "select multiple values in a unique column:\n";
print_r($db->select_field_to_array('test',$fldDate));

# make an update
echo "update first row:\n";
$db->update('test',array('date'=>'2007-02-02'),array("WHERE id = ?",$ids[1]));
print_r($db->select_to_array('test'));

# now delete some rows
echo "delete some rows:\n";
$db->delete('test',array("WHERE id < 2"));
print_r($db->select_to_array('test'));

echo "now create some more tables:\n";
$Qstr = "CREATE TABLE `owners` (
  `oid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `oname` VARCHAR( 255 ) NOT NULL
)";
$db->query($Qstr);
$Qstr = "CREATE TABLE `owners_desc` (
  `oid` INT( 10 ) UNSIGNED NOT NULL,
  `descid` INT( 10 ) UNSIGNED NOT NULL
)";
$db->query($Qstr);

echo "inserting some datas\n";
for($i=1;$i<100;$i++){
  $db->insert('owners',array('oname'=>"owner$i"));
}
$db->insert('owners_desc',array('oid'=>1,'descid'=>2));
$db->insert('owners_desc',array('oid'=>3,'descid'=>3));

echo "perform a select with a join:\n";
print_r($db->select_to_array('owners as o LEFT JOIN owners_desc as od ON o.oid = od.oid LEFT JOIN test as t ON od.descid = t.id','*','WHERE description != "" '));

echo "or in an other way:\n";
print_r($db->select_to_array('owners as o,test as t,owners_desc as od','*',"WHERE o.oid = od.oid AND od.descid = t.id AND description !='' "));

# we can also get a only a slice of results and a pagination bar:
list($rows,$paging,$total) = $db->select_array_slice('owners','*',null,$pageId=1,$pageNbRows=10);
echo "- results:\n";
print_r($rows);
echo "- total nb rows = $total\n- and here a sample pagination bar\n$paging\n";
echo "see method set_slice_attrs() to customize the pagination bar";

echo "ending the sample by cleaning up the database\n";
# clean up the database:
$db->query('DROP TABLE test,owners,owners_desc');

?>
</pre>