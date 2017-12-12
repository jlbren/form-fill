<?php
/*
 * In employees.php, we display the existing employee table 
 * and then provide a form for adding a new employee.
 * 
 * prepared statements are used!
 * filtering:  
 *	htmlspecialchars() for html filtering
 */

include 'lib353pdo.php';

// main program here
// decide which button was clicked: 'submit' or 'update'

$hostname = 'localhost';
$username = 'pld';
$dbname   = 'plddb';
include 'password.php';


main($hostname, $username, $dbname, $password);

function main($hostname, $username, $dbname, $password) {
	print 	"<html><title>Adding Employees</title><body>\n";
	//print "<p>hostname=$hostname, username=$username, dbname=$dbname, password=$password";

	$db = connect_pdo($hostname, $username, $password, $dbname);
	//print "<p>connection succeeded!";

	//$db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false); 

	if (array_key_exists('submit', $_POST)) {   // submit button pressed		
		submit_employee($db);
		get_employees($db);
	} else if ($_POST['update']) {		// update button pressed
		get_employees($db);
	} else if ($_POST['deralph']) {		// de_ralph button pressed
		de_ralph($db);
		get_employees($db);
	} else {				// no button pressed; initial arrival
		get_employees($db);
	}
	printform($db);	// done in all cases
	print	"</body></html>";
	// end of main program
}

function submit_employee($db) {
	// extract employee names from the fields POSTed
	print ("starting submit_employee()<br>\n");
      	$fname= $_POST['fname'];
	$minit= $_POST['minit'];
	$lname= $_POST['lname'];
	$ssn  = $_POST['ssn'];
	$bdate= $_POST['bdate'];
	$address=$_POST['address'];
	$sex  = $_POST['sex'];
	$salary=$_POST['salary'];
	$super_ssn=$_POST['super_ssn'];
	$dno  = $_POST['dno'];

	//validate($fname, ...., $dno);			// placehoder
	// diagnostics
	print htmlspecialchars("inserting record: lname=$lname, fname=$fname, ssn=$ssn, fssn=$fssn") . "<p>";

	$insertion="insert into employee values (?,?,?,?,?,?,?,?,?,?)";

	/*
	$types = array('text', 'text', 'text', 'text', 'text', 'text', 'text',
			'decimal', 	// salary
			'text', 
			'integer');	// dept number
	/* */

	$stmt = $db->prepare($insertion);

	if ($stmt == FALSE) {
		print("failed to prepare statement: \"$insertion\"<p>\n");
		$errarray = $db->errorInfo();
		$errmsg = $errarray[2];  
		print("<b>Prepare error: $errmsg</b><p>\n");	// error would live in $db
		die();
	}

	$queryargs = array($fname, $minit, $lname, $ssn, $bdate, $address,
		$sex, $salary, $super_ssn, $dno);

	$ret = $stmt->execute($queryargs);

	if ($ret == FALSE) {
		print("execution of query not successful: \"$insertion\"<p>\n");
		$errarray = $stmt->errorInfo();
		$errmsg = $errarray[2];  
		print("<b>Execute error: $errmsg</b><p>\n");
		$fail=1;
	} else {
		print "record was inserted<p>";
		$stmt->closeCursor();
	}
}

// despite the name, get_employees() also prints the employee table
function get_employees($db) {
	$query="select e.fname, e.minit, e.lname, e.ssn, e.sex, e.bdate, e.salary, 
	concat(s.fname, ' ', s.lname) as supervisor,
	concat(d.dnumber, ' (', d.dname, ')' ) as dept
	from (employee e left join employee s on e.super_ssn = s.ssn) 
	left outer join department d on e.dno = d.dnumber";

	$qstmt = $db->prepare($query);    // , array(), MDB2_PREPARE_RESULT);
	if ($qstmt == FALSE) {
		print("failed to prepare statement: \"$query\"<p>\n");
		$errarray = $db->errorInfo();
		$errmsg = $errarray[2];  
		print("<b>Prepare error: $errmsg</b><p>\n");
		die();
	}

	$ret = $qstmt->execute();

	if ($ret == FALSE) {
		print("query not successful: \"$query\"<p>\n");
		$errarray = $qstmt->errorInfo();
		$errmsg = $errarray[2];  
		print("<b>Execute error: $errmsg</b><p>\n");
		die();
	}

	print "<h3>Table of Employees</h3>";
	table_format_pdo($qstmt);
	print "<p>";
}


// remove ralph

function de_ralph($db) {de_ralph_old($db);}

// de_ralph_new will only work if you've created table employeebackup.
// "set foreign_key_checks=0/1" is only needed if you use innodb.
// But because I do all the queries in a batch, the error-testing is broken
function de_ralph_new($db) {
	$query1 = "set foreign_key_checks=0";	
	$query2 = "delete from employee";	
	$query3 = "insert into employee select * from employeebackup";	
	$query4 = "set foreign_key_checks=1";	
	$query = $query1 . ';' . $query2 . ';' . $query3 . ';' . $query4;
	$retstmt = $db->query($query);		// returns FALSE on failure
	//$retstmt2 = $db->query($query2);		
	//$retstmt3 = $db->query($query3);		
	//$retstmt4 = $db->query($query4);		
	if ($retstmt == FALSE) {
		print ("failed query: \"$query\"<p>\n");
		$errarray = $db->errorInfo();	// error would live in $db
		$errmsg = $errarray[2];  
		print("<b>Execute error: $errmsg</b><p>"); 
		die();
	}
}

function de_ralph_old($db) {
	$query = "delete from employee where fname = 'ralph'";
	$retstmt = $db->query($query);		// returns FALSE on failure
	if ($retstmt == FALSE) {
		print ("failed query: \"$query\"<p>\n");
		$errarray = $db->errorInfo();	// error would live in $db
		$errmsg = $errarray[2];  
		print("<b>Execute error: $errmsg</b><p>"); 
		die();
	}
}

// 	<br><input type="text" name="dno" value="4"> department number

function printform($db) {
	$query = "select dnumber from department order by dnumber";
	$stmt  = simple_query($db, $query, array());
	if (is_string($stmt)) {
		print ("failed query: \"$query\"");
		die();
	}
	// query was successful
	$theDepts = $stmt->fetchAll(PDO::FETCH_COLUMN);
	$deptmenu = makeSelectMenuString("dno", $theDepts);
print <<<FORMEND
	<form method="post" action="">
	Use this page to enter new employees<p>
	<input type="text" name="fname" value="ralph">
	<input type="text" name="minit" size="1" value="j">
	<input type="text" name="lname" value="wiggums"> required
	<p>
	<input type="text" name="ssn" value="abcdefghi"> required
	<br><input type="text" name="sex" value="M"> M/F
	<br><input type="text" name="bdate" value="1980-07-04"> YYYY-MM-DD
	<br><input type="text" name="salary" value="9999"> (annual salary)
	<br><input type="text" name="super_ssn" value="999887777"> supervisor (by SSN)
	<br><input type="text" name="address" value="no fixed abode"> address
	<br>$deptmenu department number
        <p><input type="submit" name="submit" value="submit data">
	<p><input type="submit" name="update" value="update">
	&nbsp; &nbsp; &nbsp; <input type="submit" name="deralph" value="de-Ralph">
	</form>
FORMEND;
	makePageButtons();
}


?>
