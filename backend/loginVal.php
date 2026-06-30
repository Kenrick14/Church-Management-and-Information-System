<?php
	session_start();
	//database connection
	include_once "DBConnect.php";

	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["loginbtn"])) {
		$username = htmlspecialchars($_POST["username"]);
		$password = $_POST["password"]; // Keep raw for password_verify

		function validateLogin($username, $password, $conn) {
			$stmt = $conn->prepare("SELECT * FROM logins WHERE username = ?");
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$result = $stmt->get_result();

			if ($result->num_rows === 1) {
				$row = $result->fetch_assoc();
				if (password_verify($password, $row['password'])) {
					$_SESSION['username'] = $username;
					$_SESSION["login-complete"] = true;
					header("Location: ../frontend/addMember-p1.php");
					exit();
				} else {
					return false;
				}
			} else {
				return false;
			}

			$stmt->close();
		}

		$errors = [];
		$old = ['username' => $username];

		if (!validateLogin($username, $password, $conn)) {
			$errors[] = "Invalid username or password";
		}

		if (!empty($errors)) {
			$_SESSION["errors"] = $errors;
			$_SESSION["old"] = $old;
			header("Location: ../frontend/login.php");
			exit();
		}

		$conn->close(); 
	}	
?>
