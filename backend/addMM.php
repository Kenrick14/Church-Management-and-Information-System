<?php
	session_start();
	include_once "DBConnect.php";

	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['savebtn'])) {
		$ministry = $_POST['ministry'];
		$role = $_POST['role'];
		$selected_members_json = $_POST['selected_members'];

		// DEBUG: Log what we're receiving
		error_log("Received ministry: " . $ministry);
		error_log("Received role: " . $role);
		error_log("Received selected_members_json: " . $selected_members_json);

		// Initialize errors array
		$_SESSION['errors'] = [];

		// Decode the JSON string of member IDs
		$member_ids = json_decode($selected_members_json, true);

		// DEBUG: Log the decoded member IDs
		error_log("Decoded member_ids: " . print_r($member_ids, true));
		
		if ($member_ids && is_array($member_ids)) {
			error_log("First member_id: " . $member_ids[0]);
			error_log("All member_ids: " . implode(', ', $member_ids));
		}

		if ($member_ids && is_array($member_ids)) {
			try {
				// Get ministry_id from ministry name
				$stmt = $conn->prepare("SELECT ministry_id FROM ministries WHERE ministry_name = ?");
				$stmt->bind_param("s", $ministry);
				$stmt->execute();
				$result = $stmt->get_result();
				
				if ($result->num_rows === 0) {
					throw new Exception("Ministry '$ministry' not found in database.");
				}
				
				$row = $result->fetch_assoc();
				$ministry_id = $row['ministry_id'];
				$stmt->close();
				
				// Prepare insert statement
				$stmt = $conn->prepare("INSERT INTO ministry_members (member_id, ministry_id, role, assigned_date) VALUES (?, ?, ?, NOW())");
				$success_count = 0;
				$error_count = 0;
				$errors = [];
				
				foreach ($member_ids as $member_id) {
					// DEBUG: Log each member_id before insertion
					error_log("Attempting to insert member_id: " . $member_id);
					
					if (empty($member_id)) {
						$errors[] = "Empty member ID encountered";
						$error_count++;
						continue;
					}
					
					$stmt->bind_param("iss", $member_id, $ministry_id, $role);
					if ($stmt->execute()) {
						$success_count++;
					} else {
						$error_count++;
						$errors[] = "Error adding member ID $member_id: " . $stmt->error;
						error_log("Error adding member $member_id to ministry $ministry_id: " . $stmt->error);
					}
				}
				
				$stmt->close();
				
				if ($error_count === 0 && $success_count > 0) {
					$_SESSION['success'] = "Successfully added $success_count members to $ministry!";
				} else if ($success_count > 0) {
					$_SESSION['errors'] = array_merge($_SESSION['errors'], $errors);
					$_SESSION['errors'][] = "Added $success_count members, but $error_count failed.";
				} else {
					$_SESSION['errors'][] = "No members were successfully added.";
				}
				
			} catch (Exception $e) {
				$_SESSION['errors'][] = $e->getMessage();
			}
			
		} else {
			$_SESSION['errors'][] = "No members selected or invalid member data.";
			// DEBUG: Add more info about what went wrong
			if (json_last_error() !== JSON_ERROR_NONE) {
				$_SESSION['errors'][] = "JSON decode error: " . json_last_error_msg();
			}
		}
		
		// Redirect back to the form page
		header("Location: ../frontend/addMinistryMembers.php");
		exit();
		
	} else {
		// If not a valid POST request, redirect
		header("Location: ../frontend/addMinistryMembers.php");
		exit();
	}

	$conn->close();
?>