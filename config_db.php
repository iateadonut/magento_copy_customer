<?php

//SOURCE DATABASE
$uname	= '';
$pword	= '';
$host	= '';
$db		= '';

$dsn = 'mysql:dbname=' . $db . ';host=' . $host;

try {
	$this->pdo_source = new PDO_Ex($dsn, $uname, $pword);
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
	$this->pdo_target = new PDO_Ex($dsn, $uname, $pword);
} catch (PDOException $e) {
	echo 'Connection failed (TARGET): ' . $e->getMessage();
}





class PDO_Ex extends PDO {
	
	function query($query){
		
		$statement = parent::query($query);
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		return $statement;
	}

}
