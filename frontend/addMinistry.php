<?php
	session_start();
	
	$old = isset($_SESSION['old']) ? $_SESSION['old'] : [];
	unset($_SESSION['old']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Ministry</title>
    <link rel="stylesheet" href="./styles/pages.css">
</head>
<body>
	<div class="container">
		<div class="header">
			<h1>Add Ministry</h1>
		</div>
		<?php if(isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
			<div class="error-container">
				<?php 
					foreach($_SESSION['errors'] as $error){
						echo '<p>'.nl2br(htmlspecialchars($error)) . '</p>';
					}
					// Clear errors after displaying
					unset($_SESSION['errors']);
				?>
			</div>
		<?php endif; ?>
		<form action="../backend/addM.php" method="POST">
			<div>
				<label>Ministry</label>
				<select name="ministry" required>
					<option value="">Select Ministry</option>
					<option value="Evangalism & Outreach">Evangalism & Outreach</option>
					<option value="Spiritual Growth">Spiritual Growth</option>
					<option value="Support & Care">Support & Care</option>
					<option value="Worship & Fellowship">Worship & Fellowship</option>
					<option value="Bible Education">Bible Education</option>
					<option value="Men's Ministry">Men's Ministry</option>
					<option value="Women's Ministry">Women's Ministry</option>
					<option value="Youth Ministry">Youth Ministry</option>
					<option value="Choir">Choir</option>
					<option value="Ushering">Ushering</option>
				</select>
			</div>
			
			<div>
				<label>Description</label>
				<textarea name="description" placeholder="Enter a short description of the ministry..." maxlength="500"></textarea>
			</div>
			
			<div class="button-container">
				<button type="reset" name="clearbtn">Clear</button>
				<button type="submit" name="savebtn">Save</button>
				<button type="submit" name="addmbtn">Add Members</button>
			</div>
		</form>
	</div>
</body>
</html>