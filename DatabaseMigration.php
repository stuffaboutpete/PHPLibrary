<?php

$pdo = new \PDO(/*...*/);

$migration = new \PO\DatabaseMigration(
	$pdo,
	'/path/to/folder'
);

$migration->migrate();