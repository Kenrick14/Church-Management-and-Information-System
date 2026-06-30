<?php
	session_start();
	
	$old = isset($_SESSION['old']) ? $_SESSION['old'] : [];
	unset($_SESSION['old']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="./styles/pages.css">
</head>
<body>
	<div class="container">
		<div class="header">
            <h1>Login</h1>
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
	
		<form action="../backend/loginVal.php" method="POST">
			<div>
				<input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>" required>
			</div>

			<div>
				<input type="password" name="password" placeholder="Password" required>
			</div>
		
			<div class="button-container">
				<input type="submit" value="LOG IN" name="loginbtn"></br>
			</div>
		</form>
	</div>
</body>
</html>