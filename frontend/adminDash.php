<?php
	session_start();
	include_once "../backend/DBConnect.php";

	$events = []; // Initialize empty array to store events

	try {
		$stmt = $conn->prepare("SELECT 
			e.event_id, 
			e.event, 
			e.eDate, 
			e.eNotes,
			GROUP_CONCAT(CONCAT(m.first_name, ' ', m.last_name) SEPARATOR ', ') as member_names,
			COUNT(em.mem_id) as total_members,
			CASE 
				WHEN e.date = CURDATE() THEN 'Today'
				ELSE 'Upcoming'
			END as event_status
		FROM events e
		LEFT JOIN members m ON e.member_id = m.mem_id
		WHERE e.date >= CURDATE()
		GROUP BY e.event_id, e.event_type, e.date, e.notes
		ORDER BY e.date ASC");
		
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows === 0) {
			$no_events = true;
		} else {
			// Store all events in an array
			while ($row = $result->fetch_assoc()) {
				$events[] = $row;
			}
		}
		$stmt->close();
		
	} catch (Exception $e) {
		$error = "Error fetching events: " . $e->getMessage();
	}
	$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="./styles/dashboard.css">
</head>
<body>
    <div class="container">
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <p>Welcome to your administration panel</p>
            </div>
            
            <!-- Main dashboard content goes here -->
            <div class="dashboard-content">
                <p>Your main dashboard content will appear here. You can add charts, statistics, quick actions, etc.</p>
                
                <!-- Example stats grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number">42</span>
                        <span class="stat-label">Total Members</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">12</span>
                        <span class="stat-label">Upcoming Events</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">8</span>
                        <span class="stat-label">Active Projects</span>
                    </div>
                </div>
                
                <!-- Add your main content components here -->
            </div>
        </div>

        <!-- Sidebar for Events -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Upcoming Events</h2>
            </div>
            <div class="sidebar-body">
                <?php if (isset($no_events)): ?>
                    <div class="no-events">
                        No upcoming events found.
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <div class="event-header">
                                <h3 class="event-title"><?php echo htmlspecialchars($event['event']); ?></h3>
                                <div class="event-date">
                                    <?php 
                                    $event_date = strtotime($event['eDate']);
                                    $today = strtotime('today');
                                    $days_until = round(($event_date - $today) / (60 * 60 * 24));
                                    
                                    if ($days_until == 0) {
                                        echo '<span class="status-today">Today - </span>';
                                    } else {
                                        echo '<span class="status-upcoming">In ' . $days_until . ' day(s) - </span>';
                                    }
                                    echo date('F j, Y', $event_date);
                                    ?>
                                </div>
                            </div>
                            <div class="event-members">
                                <strong>Members involved:</strong> 
                                <?php 
                                if (!empty($event['member_names'])) {
                                    echo htmlspecialchars($event['member_names']);
                                } else {
                                    echo "No members assigned";
                                }
                                ?>
                            </div>
                            <?php if (!empty($event['eNotes'])): ?>
                                <div class="event-notes">
                                    <strong>Notes:</strong> <?php echo htmlspecialchars($event['eNotes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>