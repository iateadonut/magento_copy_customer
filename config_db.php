<?php

//SOURCE DATABASE
$uname	= '';
$pword	= '';
$host	= '';
$db		= '';

$dsn = 'mysql:dbname=' . $db . ';host=' . $host;

try {
	$this->pdo_source = new PDO($dsn, $uname, $pword);
} catch (PDOException $e) {
	echo 'Connection failed (SOURCE): ' . $e->getMessage();
}


//TARGET DATABASE
$uname	= '';
$pword	= '';
$host	= '';
$db		= '';

$dsn = 'mysql:dbname=' . $db . ';host=' . $host;

try {
	$this->pdo_target = new PDO($dsn, $uname, $pword);
} catch (PDOException $e) {
	echo 'Connection failed (TARGET): ' . $e->getMessage();
}
