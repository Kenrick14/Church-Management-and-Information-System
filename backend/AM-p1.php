<?php
	session_start();
	
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nextbtn"])) {
		
		$fName = htmlentities($_POST["fName"]);
		$mInitial = htmlentities($_POST["mInitial"]);
		$lName = htmlentities($_POST["lName"]);
		$dob = htmlentities($_POST["dob"]);
		$gender = htmlentities($_POST["gender"]);
		$dateJoined = htmlentities($_POST["dateJoined"]);
		$status = htmlentities($_POST["status"]);
		$passing = htmlentities($_POST["passing"]);
		
		
		function validateFName($fName)
		{
			return preg_match("/^[A-Za-z-]{1,15}$/", $fName);
		}
		
		function validateMInitial($mInitial)
		{
			return preg_match("/^[A-Za-z]$/", $mInitial);
		}
		
		function validateLName($lName)
		{
			return preg_match("/^[A-Za-z-]{1,15}$/", $lName);
		}
		
		function validateDob($dob)
		{
			if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dob)) {
				return false;
			}
			
			//Validate that it is not a future date
			$inputDate = strtotime($dob);
			$today = strtotime(date("Y-m-d"));
			
			return $inputDate <= $today;
		}
		
		$errors = [];
		$old = ['fName' => $fName,
					'mInitial' => $mInitial,
					'lName' => $lName,
					'dob' => $dob,
					'gender' => $gender,
					'dateJoined' => $dateJoined,
					'status' => $status,
					'passing' => $passing
					];
					
		if (!validateFName($fName)) $errors[] = "Invalid first name.  Max 15 Characters";
		if(!validateMInitial($mInitial)) $errors[] = "Invalid middle initial.  Only 1 Character";
		if(!validateLName($lName)) $errors[] = "Invalid last name.  Max 15 Characters";
		if(!validateDob($dob)) $errors[] = "Invalid date of birth. DOB cannot be in the future";
		
		
		if (!empty($errors)) {
			$_SESSION["errors"] = $errors;
			$_SESSION["old"] = $old;
			header("Location: ../frontend/addMember-p1.php");
			exit();
		}else{
			$_SESSION["fName"] = $fName;
			$_SESSION["mInitial"] = $mInitial;
			$_SESSION["lName"] = $lName;
			$_SESSION["dob"] = $dob;
			$_SESSION["gender"] = $gender;
			$_SESSION["dateJoined"] = $dateJoined;
			$_SESSION["status"] = $status;
			$_SESSION["passing"] = $passing;
			
			
			header("Location: ../frontend/addMember-p2.php");
			exit();
		}
	}
?>