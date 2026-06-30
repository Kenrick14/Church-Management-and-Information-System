<?php
	session_start();
	
	$old = isset($_SESSION['old']) ? $_SESSION['old'] : [];
	unset($_SESSION['old']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Member</title>
    <link rel="stylesheet" href="./styles/pages.css">
</head>
<body>
	<div class="container">
		<div class="header">
			<h1>Add Member</h1>
			<h2>Personal Information</h2>
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
		<form action="../backend/AM-p1.php" method="POST">
			<div>
				<label>Name</label>
				<div class="input-row">
					<div class="input-group">
						<input type="text" name="fName" placeholder="First" value="<?php echo htmlspecialchars($old['fName'] ?? ''); ?>" required>
					</div>
					<div class="input-group">
						<input type="text" name="mInitial" placeholder="Middle Initial" value="<?php echo htmlspecialchars($old['mInitial'] ?? ''); ?>" required>
					</div>
					<div class="input-group">
						<input type="text" name="lName" placeholder="Last" value="<?php echo htmlspecialchars($old['lName'] ?? ''); ?>" required>
					</div>
				</div>
			</div>
			
			<div>
				<label>Date of Birth</label>
				<input type="date" name="dob" required />
			</div>
			
			<div>
				<label>Gender</label>
				<div class="radio-group">
					<div class="radio-option">
						<input type="radio" id="male" name="gender" value="male" checked>
						<label for="male">Male</label>
					</div>
					<div class="radio-option">
						<input type="radio" id="female" name="gender" value="female">
						<label for="female">Female</label>
					</div>
				</div>
			</div>
			
			<div>
				<label>Date Joined</label>
				<input type="date" name="dateJoined" required />
			</div>
			
			<div>
				<label>Status</label>
				<div class="radio-group">
					<div class="radio-option">
						<input type="radio" id="member" name="status" value="Member" checked>
						<label for="member">Member</label>
					</div>
					<div class="radio-option">
						<input type="radio" id="adherent" name="status" value="Adherent">
						<label for="adherent">Adherent</label>
					</div>
					<div class="radio-option">
						<input type="radio" id="visitor" name="status" value="Visitor">
						<label for="visitor">Visitor</label>
					</div>
				</div>
			</div>
			
			<div>
				<label>Passing Date <span style="color: #666; font-weight: 400; font-size: 0.9rem;">(Optional)</span></label>
				<input type="date" name="passing" />
			</div>
			
			<div class="button-container">
				<button type="reset" name="clearbtn">Clear</button>
				<button type="submit" name="nextbtn">Next</button>
			</div>
		</form>
	</div>
</body>
</html>