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
			<h2>Next of Kin</h2>
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
		<form action="../backend/AM-p3.php" method="POST">
			<div>
				<label>Name</label>
				<div class="input-row">
					<div class="input-group">
						<input type="text" name="kfn" placeholder="First" value="<?php echo htmlspecialchars($old['kfn'] ?? ''); ?>" required>
					</div>
					<div class="input-group">
						<input type="text" name="kln" placeholder="Last" value="<?php echo htmlspecialchars($old['kln'] ?? ''); ?>" required>
					</div>
				</div>
			</div>
			
			<div>
				<label>Relation</label>
				<select name="relation" required>
					<option value="Spouse">Spouse</option>
					<option value="Child">Child</option>
					<option value="Sibling">Sibling</option>
					<option value="Parent">Parent</option>
					<option value="Friend">Friend</option>
				</select>
			</div>
			
			<div>
				<label>Address</label>
				<input type="text" name="kAdd1" placeholder="Address line 1" required />
				<input type="text" name="kAdd2" placeholder="Address line 2" />
				
				<div style="margin-top: 15px;">
					<label>Parish</label>
					<select name="kParish" required>
						<option value="Clarendon">Clarendon</option>
						<option value="Hanover">Hanover</option>
						<option value="Kingston">Kingston</option>
						<option value="Manchester">Manchester</option>
						<option value="Portland">Portland</option>
						<option value="St. Andrew">St. Andrew</option>
						<option value="St. Ann">St. Ann</option>
						<option value="St. Catherine">St. Catherine</option>
						<option value="St. Elizabeth">St. Elizabeth</option>
						<option value="St. James">St. James</option>
						<option value="St. Mary">St. Mary</option>
						<option value="St. Thomas">St. Thomas</option>
						<option value="Trelawny">Trelawny</option>
						<option value="Westmoreland">Westmoreland</option>
					</select>
				</div>
			</div>
			
			<div>
				<label>Phone Number</label>
				<input type="tel" name="kPhone" placeholder="(876) XXX-XXXX" required />
			</div>
			
			<div>
				<label>Email</label>
				<input type="email" name="kEmail" placeholder="xxxxx@xxxx.xxx" value="<?php echo htmlspecialchars($old['kEmail'] ?? ''); ?>" required>
			</div>
		
			<div class="button-container">
				<button type="reset" name="clearbtn">Clear</button>
				<button type="submit" name="savebtn">Save</button>
			</div>
		</form>
	</div>
</body>
</html>