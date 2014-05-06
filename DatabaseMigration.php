<?php

$pdo = new \PDO(/*...*/);

$migration = new \Suburb\DatabaseMigration(
	$pdo,
	'/path/to/folder'
);

$migration->migrate();