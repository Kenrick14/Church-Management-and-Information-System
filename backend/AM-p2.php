<?php
	session_start();
	
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nextbtn"])) {
		
		$add1 = htmlentities($_POST["add1"]);
		$add2 = htmlentities($_POST["add2"]);
		$parish = htmlentities($_POST["parish"]);
		$phone = htmlentities($_POST["phone"]);
		$email = htmlentities($_POST["email"]);
		
		function validateAdd1($add1)
		{
			return preg_match("/^\d{1,5}[A-Za-z]?\s+[A-Za-z- ]+$/", $add1);
		}
		
		function validateAdd2($add2)
		{
			if(!empty($add2))
			{
				return preg_match("/^\d{1,5}[A-Za-z]?$/", $add2);
			}else{
				return true;
			}
			
		}
		
		function validatePhone($phone)
		{
			return preg_match("/^\(\d{3}\)\d{3}-\d{4}$/", $phone);
		}
		
		function validateEmail($email)
		{
			return preg_match("/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$/", $email);
		}
		
		$errors = [];
		$old = ['add1' => $add1,
					'add2' => $add2,
					'parish' => $parish,
					'phone' => $phone,
					'email' => $email
					];
					
		if (!validateAdd1($add1)) $errors[] = "Invalid street address";
		if(!validateAdd2($add2)) $errors[] = "Invalid apartment/room number";
		if(!validatePhone($phone)) $errors[] = "Invalid phone number. Check format";
		if(!validateEmail($email)) $errors[] = "Invalid email. Check format";
		
		if (!empty($errors)) {
			$_SESSION["errors"] = $errors;
			$_SESSION["old"] = $old;
			header("Location: ../frontend/addMember-p2.php");
			exit();
		}else{
			$_SESSION["add1"] = $add1;
			$_SESSION["add2"] = $add1;
			$_SESSION["parish"] = $parish;
			$_SESSION["phone"] = $phone;
			$_SESSION["email"] = $email;
			
			header("Location: ../frontend/addMember-p3.php");
			exit();
		}
	}
?>