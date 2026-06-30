<?php
	session_start();
	//database connection
	include_once "DBConnect.php";
	
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["savebtn"])) {
		
		$kfn = htmlentities($_POST["kfn"]);
		$kln = htmlentities($_POST["kln"]);
		$relation = htmlentities($_POST["relation"]);
		$kAdd1 = htmlentities($_POST["kAdd1"]);
		$kAdd2 = htmlentities($_POST["kAdd2"]);
		$kParish = htmlentities($_POST["kParish"]);
		$kPhone = htmlentities($_POST["kPhone"]);
		$kEmail = htmlentities($_POST["kEmail"]);
		
		function validateKFN($kfn)
		{
			return preg_match("/^[A-Za-z-]{1,15}$/", $kfn);
		}
		
		function validateKLN($kln)
		{
			return preg_match("/^[A-Za-z-]{1,15}$/", $kln);
		}
		
		function validateKAdd1($kAdd1)
		{
			return preg_match("/^\d{1,5}[A-Za-z]?\s+[A-Za-z- ]+$/", $kAdd1);
		}
		
		function validateKAdd2($kAdd2)
		{
			return preg_match("/^\d{1,5}[A-Za-z]?$/", $kAdd2);
		}
		
		function validateKPhone($kPhone)
		{
			return preg_match("/^\(\d{3}\)\d{3}-\d{4}$/", $kPhone);
		}
		
		function validateKEmail($kEmail)
		{
			return preg_match("/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$/", $kEmail);
		}
		
		$errors = [];
		$old = ['kfn' => $kfn,
					'kln' => $kln,
					'relation' => $relation,
					'kAdd1' => $kAdd1,
					'kAdd2' => $kAdd2,
					'kParish' => $kParish,
					'kPhone' => $kPhone,
					'kEmail' => $kEmail
					];
		
		if(!validateKFN($kfn)) $errors[] = "Invalid first name.  Max 15 Characters";
		if(!validateKLN($kln)) $errors[] = "Invalid last name.  Max 15 Characters";
		if (!validateKAdd1($kAdd1)) $errors[] = "Invalid street address";
		if(!validateKAdd2($kAdd2)) $errors[] = "Invalid apartment/room number";
		if(!validateKPhone($kPhone)) $errors[] = "Invalid phone number. Check format";
		if(!validateKEmail($kEmail)) $errors[] = "Invalid email. Check format";
		
		if (!empty($errors)) {
			$_SESSION["errors"] = $errors;
			$_SESSION["old"] = $old;
			header("Location: ../frontend/addMember-p3.php");
			exit();
		}else{
			$_SESSION["kfn"] = $kfn;
			$_SESSION["kln"] = $kln;
			$_SESSION["relation"] = $relation;
			$_SESSION["kAdd1"] = $kAdd1;
			$_SESSION["kAdd2"] = $kAdd2;
			$_SESSION["kParish"] = $kParish;
			$_SESSION["kPhone"] = $kPhone;
			$_SESSION["kEmail"] = $kEmail;
			
			$stmt = $conn->prepare("INSERT INTO members (fName, mInitial, lName, dob, gender, dateJoined, status, passing, add1, add2, parish, phone, email, kfn, kln, relation, kAdd1, kAdd2, kParish, kPhone, kEmail) 
														VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

			if ($stmt === false) {
				die("Prepare failed: " . $conn->error);
			}
			
			$stmt->bind_param("sssssssssssssssssssss", 
				$_SESSION["fName"], 
				$_SESSION["mInitial"], 
				$_SESSION["lName"], 
				$_SESSION["dob"], 
				$_SESSION["gender"], 
				$_SESSION["dateJoined"], 
				$_SESSION["status"], 
				$_SESSION["passing"], 
				$_SESSION["add1"], 
				$_SESSION["add2"], 
				$_SESSION["parish"], 
				$_SESSION["phone"], 
				$_SESSION["email"], 
				$_SESSION["kfn"], 
				$_SESSION["kln"], 
				$_SESSION["relation"], 
				$_SESSION["kAdd1"], 
				$_SESSION["kAdd2"],  
				$_SESSION["kParish"], 
				$_SESSION["kPhone"], 
				$_SESSION["kEmail"]
			);

			if ($stmt->execute()) {
				echo "Record inserted successfully";
			} else {
				echo "Error: " . $stmt->error;
			}

			$stmt->close();
			
			
			header("Location: ../frontend/adminDash.php");
			exit();
		}
	}
?>