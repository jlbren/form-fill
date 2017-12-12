<?php

error_reporting (E_ALL & ~E_NOTICE);
//error_reporting (E_ALL | E_STRICT);

error_reporting (E_ALL & ~E_NOTICE);
//error_reporting (E_ALL | E_STRICT);

/* 
 * In table_format_pdo(), we get rows with fetchRow.
 * Rows themselves are int-indexed arrays.
 * To fetch rows, we use fetchRow in ORDERED ("FETCH_NUM") (versus associative-array) mode.
 * To fetch columns of a row, we use an ordinary php while loop.
 * To fetch column names, we use getColumnMeta and take the 'name' attribute.
 */

function table_format_pdo($queryres) {
    if ($queryres == FALSE) die("table_format_pdo() called on failed query!");
    if (get_class($queryres) != "PDOStatement") {
	print("table_format_pdo() called on failed query!");
	var_dump($queryres);
	die();
    }
    $nullval = "null";		// you can use "" as desired
    // print 'rowcount=' . $queryres->rowCount() . " <br>";
    // print 'colcount=' . $queryres->columnCount() . " <br>";
    $colcount = $queryres->columnCount();
    $rowcount = $queryres->rowCount();
    print "<table border=\"1\">\n";

    print "<tr>";
    for ($i = 0; $i <= $colcount; $i ++) {
    	$meta = $queryres->getColumnMeta($i);
    	$colname = $meta['name'];
	print "<th>";
	print $colname;
	print "</th>\n";
    }
    print "</tr>\n";

    $rownum = 0;

    while ($rownum < $rowcount) {
	// FETCH_NUM: fetch as a numeric-indexed array, starting at 0
        $row = $queryres->fetch(PDO::FETCH_NUM);
        print "<tr>";
	$col = 0;
	while ($col < count($row)) {
	    print "<td>";
	    if ($row[$col] == NULL) print $nullval; else print ($row[$col]);
	    $col++;
	    print "</td>";
	}
	print "</tr>\n";
        $rownum++;
    }
    print "</table>\n";

}

function connect_pdo($hostname, $username, $password, $database) {
    try {
    	$dbconn = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    /*** echo a message saying we have connected ***/
    //	echo 'Connected to database<p>';
	return $dbconn;
    }
    catch(PDOException $e) {
    	echo $e->getMessage();
	return null;
    }
}


/*
 * simple_query executes a simple prepared query.
 * In the event of failure it returns a string describing the error;
 * this can be identified using is_string().
 * The PDOStatement object is returned if the query is successful.
 * One row of the return object is obtained by the $stmt->fetch() method
 */

function simple_query($db, $query, $queryargs) {
	$stmt = $db->prepare($query);

	if ($stmt == FALSE) {
		$errarray = $db->errorInfo(); // error would live in $db
		$errmsg = $errarray[2];  
		
		$error = "Prepare-statement error for query \"$query\": error is $errmsg";
		return $error;
	}

	$ret = $stmt->execute($queryargs);	// $stmt is the PDOStmt object

	if ($ret == FALSE) {
		print("execution of query not successful: \"$query\"<p>\n");
		$errarray = $stmt->errorInfo();
		$errmsg = $errarray[2];  
		$error = "query execution unsuccessful: \"$query\"; error is $errmsg";
		return $error;
	} 
	return $stmt;
}

/*
 * $menuname is the name given to the menu itself, that will be used to retrieve
 * the selected value from $_POST. 
 * $names is an array of strings like ['foo', 'bar', 'quux'], these are used 
 * as the menu choices (both name and value)
 */
function makeSelectMenu($menuname, $names) {
	print "<select size=1 name=\"$menuname\">";
	foreach ($names as $name) {
		print "<option> $name";
	}
	print "</select>";
}

function makeSelectMenuString($menuname, $names) {
	$result = "<select size=1 name=\"$menuname\">\n";
	foreach ($names as $name) {
		$result = $result . "<option> $name\n";
	}
	$result = $result . "</select>\n";
	return $result;
}

function makePageButtons () {
print <<<END
	<p>
	<hr align="left" width="55%">
	<table><tr><td>
	<form method="post" action="employees.php">
	<input type="submit" value="add employees">
	</form>
	</td><td>
	<form method="post" action="employee_update.php">
	<input type="submit" value="update employees">
	</form>
	</td><td>
	<form method="post" action="projects.php">
	<input type="submit" value="projects">
	</form>
	</td><td>
	<form method="post" action="works_on.php">
	<input type="submit" value="work assignments">
	</form>
	</td></tr></table>
END;
}

?>
