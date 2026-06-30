<?php
	session_start();
	//database connection
	include_once "DBConnect.php";

	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["savebtn"])) {
		
		$ministry = trim($_POST["ministry"]);
		$description = trim($_POST["description"]);
		
		try {
			$stmt = $conn->prepare("INSERT INTO ministries (ministry_name, description) VALUES (?, ?)");
			
			if ($stmt === false) {
				throw new Exception("Prepare failed: " . $conn->error);
			}
			
			$stmt->bind_param("ss", $ministry, $description);
			
			if ($stmt->execute()) {
				$_SESSION['success'] = "Ministry added successfully!";
			} else {
				throw new Exception("Insert failed: " . $stmt->error);
			}
			
			$stmt->close();
			
		} catch (Exception $e) {
			$_SESSION['error'] = "Error: " . $e->getMessage();
		}
		
		header("Location: ../frontend/adminDash.php");
		exit();
	}

	if (isset($_POST['addmbtn'])) {
		header("Location: ../frontend/addMinistryMembers.php");
		exit();
	}

	$conn->close();
?>