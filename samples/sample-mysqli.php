<?php
require 'class-db.php';

define ('DB_CONNECTION','mysqlidb://orpi;localhost:3306');
define ('DB_CONNECTION2','mysqlidb://brainstorme;localhost:3306');
$db = db::getInstance(DB_CONNECTION);
$db2 = db::getInstance(DB_CONNECTION2);

print_r($tables = $db->list_tables());
print_r($tables2 = $db2->list_tables());

print_r($db->select_row($tables[1]));
print_r($db2->select_row($tables2[1]));
