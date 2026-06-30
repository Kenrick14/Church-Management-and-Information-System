<?php
    session_start();
	
	$old = isset($_SESSION['old']) ? $_SESSION['old'] : [];
	unset($_SESSION['old']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Ministry Members</title>
    <link rel="stylesheet" href="./styles/pages.css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add Ministry Members</h1>
            <h2>Assign members to ministries</h2>
        </div>
		 <?php if(isset($_SESSION['success'])): ?>
            <div class="success-container">
                <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
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
        <form action="../backend/addMM.php" method="POST">
            <h3>Ministry Assignment</h3>

            <div>
                <label>Ministry <span class="required">*</span></label>
                <select name="ministry" required>
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

			<div class="form-group">
				<label>Member(s) Involved</label>
				<input type="text" id="memberSearch" placeholder="Search by member first name..." autocomplete="off" class="form-control" />
				<div id="searchResults" class="autocomplete-results"></div>
				<p class="description">Search and select members to add to this ministry.</p>
				
				<!-- Hidden field to store selected member IDs -->
				<input type="hidden" id="selectedMembers" name="selected_members" />
				
				<!-- Container to show selected members -->
				<div id="selectedMembersList" class="selected-members-container"></div>
			</div>

            <div>
                <label>Role <span class="required">*</span></label>
                <select name="role" required>
                    <option value="Worship Leader">Worship Leader</option>
                    <option value="Director">Director</option>
                    <option value="Missionary">Missionary</option>
                    <option value="Educator">Educator</option>
                    <option value="Secretary">Secretary</option>
                    <option value="Treasurer">Treasurer</option>
                    <option value="Custodian">Custodian</option>
                    <option value="Cook">Cook</option>
                    <option value="Usher">Usher</option>
                </select>
            </div>

            <div class="button-container">
                <button type="reset" name="clearbtn">Clear</button>
                <button type="submit" name="savebtn">Save</button>
            </div>
        </form>
    </div>
	
	<script src="../backend/memberSearch.js"></script>
</body>
</html>
