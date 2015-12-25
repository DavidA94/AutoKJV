<?php
// Load the Database login stuff
require_once('databaseVars.php');

// Open a connection to the DB
$db = new PDO("mysql:host=$host;dbname=$dbname", "$user", "$pass");

// Get the SQL KJV DB we want to insert
$sql = file_get_contents(dirname(__FILE__) . '/sql/bibledb_kjv.sql');

// Execute it.
$qr = $db->exec($sql);

?>