<?php
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/middleware/security.php';
require_once __DIR__ . '/../../../includes/common_functions.php';
SecurityMiddleware::initialize();

// Require admin access
requireAdmin();

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

$conn = getDBConnection();

// Get total owners count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM applicants");
$stmt->execute();
$total_owners = $stmt->get_result()->fetch_assoc()['count'];

// Get total vehicles count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM vehicles");
$stmt->execute();
$total_vehicles = $stmt->get_result()->fetch_assoc()['count'];

// Get total drivers count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM authorized_driver");
$stmt->execute();
$total_drivers = $stmt->get_result()->fetch_assoc()['count'];

// Get recent registrations with enhanced applicant details
$stmt = $conn->prepare("
    SELECT 
        v.*,
        a.fullName as owner_name,
        a.registrantType,
        a.studentRegNo,
        a.staffsRegNo,
        a.Email,
        a.college,
        a.licenseNumber,
        a.licenseClass,
        DATE_FORMAT(v.last_updated, '%M %d, %Y %h:%i %p') as formatted_last_updated
    FROM vehicles v 
    JOIN applicants a ON v.applicant_id = a.applicant_id 
    ORDER BY v.registration_date DESC 
    LIMIT 5
");
$stmt->execute();
$recent_registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total counts by registrant type
$stmt = $conn->prepare("
    SELECT 
       registrantType,
        COUNT(*) as count
    FROM applicants 
    GROUP BY registrantType
");
$stmt->execute();
$registrant_counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get vehicle counts by status
$stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM vehicles 
    GROUP BY status
");
$stmt->execute();
$vehicle_status_counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unread notifications
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE is_read = FALSE 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Vehicle Registration System</title>
    <!-- Add Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #d00000;
            --primary-red-dark: #b00000;
            --white: #ffffff;
            --black: #000000;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            background-color: var(--primary-red);
            padding: 1rem 2rem;
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        header .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        header .header-logo img {
            height: 40px;
        }

        .logout-button {
            background-color: var(--black);
            color: var(--white);
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .logout-button:hover {
            background-color: #111;
            transform: translateY(-1px);
        }

        .admin-nav {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .admin-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .admin-nav a {
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.2s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: var(--primary-red);
            color: var(--white);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .stat-number {
            font-size: 2.5rem;
            color: var(--primary-red);
            font-weight: 700;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--gray-600);
        }

        .stat-breakdown {
            margin-top: 1rem;
            font-size: 0.9rem;
            text-align: left;
            padding: 0 1rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .stat-type {
            color: var(--gray-600);
        }

        .stat-value {
            font-weight: 600;
            color: var(--gray-800);
        }

        .quick-actions,
        .recent-activity,
        .notifications-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .quick-actions h2,
        .recent-activity h2,
        .notifications-section h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
            font-size: 1.5rem;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .action-button {
            background-color: var(--primary-red);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
            display: inline-block;
        }

        .action-button:hover {
            background-color: var(--primary-red-dark);
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            border: 1px solid var(--gray-300);
            padding: 0.75rem;
            text-align: left;
        }

        .table th {
            background-color: var(--gray-100);
            color: var(--gray-800);
            font-weight: 600;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .table tbody tr:hover {
            background-color: var(--gray-200);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .admin-nav ul {
                flex-direction: column;
            }

            .admin-nav a {
                width: 100%;
                text-align: center;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .btn-primary {
            background-color: var(--primary-red);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: var(--primary-red-dark);
            transform: translateY(-1px);
        }

        .status-toggle {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .status-toggle.active {
            background-color: #dc3545;
            color: white;
        }

        .status-toggle.inactive {
            background-color: #28a745;
            color: white;
        }

        .status-toggle:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-logo">
                        <a href="admin-dashboard.php">
                            <img src="AULogo.png" alt="AULogo">
                        </a>
                    </div>
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="header-right">
                    <button type="button" onclick="logout()" class="logout-button">Logout</button>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="admin-nav">
            <ul>
                <li><a href="admin-dashboard.php" class="active">Dashboard</a></li>
                <li><a href="owner-list.php">Manage Owners</a></li>
                <li><a href="vehicle-list.php">Manage Vehicles</a></li>
                <li><a href="manage-vehicle-status.php">Manage Vehicle Status</a></li>
                <li><a href="admin_reports.php">Reports</a></li>
            </ul>
        </nav>

        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-number"><?= $total_owners ?></div>
                <div class="stat-label">Total Owners</div>
                <div class="stat-breakdown">
                    <?php foreach ($registrant_counts as $count): ?>
                        <div class="stat-item">
                            <span class="stat-type"><?= ucfirst($count['registrantType']) ?>:</span>
                            <span class="stat-value"><?= $count['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $total_vehicles ?></div>
                <div class="stat-label">Total Vehicles</div>
                <div class="stat-breakdown">
                    <?php foreach ($vehicle_status_counts as $count): ?>
                        <div class="stat-item">
                            <span class="stat-type"><?= ucfirst($count['status']) ?>:</span>
                            <span class="stat-value"><?= $count['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $total_drivers ?></div>
                <div class="stat-label">Authorized Drivers</div>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <a href="vehicle-list.php" class="action-button">View All Vehicles</a>
                <a href="owner-list.php" class="action-button">Manage Owners</a>
                <a href="registration-form.html" class="action-button">New Registration</a>
                <a href="search-vehicle.php" class="action-button">Search Vehicle</a>
                <a href="admin_reports.php" class="action-button">Create Report</a>
            </div>
        </div>

        <div class="recent-activity">
            <h2>Recent Registrations</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Owner</th>
                            <th>Type</th>
                            <th>Registration</th>
                            <th>College</th>
                            <th>License</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_registrations as $registration): ?>
                            <tr>
                                <td><?= htmlspecialchars($registration['make']) ?></td>
                                <td><?= htmlspecialchars($registration['owner_name']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($registration['registrantType'])) ?></td>
                                <td>
                                    <?php
                                    if ($registration['registrantType'] === 'student') {
                                        echo htmlspecialchars($registration['studentRegNo']);
                                    } elseif ($registration['registrantType'] === 'staff') {
                                        echo htmlspecialchars($registration['staffsRegNo']);
                                    } else {
                                        echo htmlspecialchars($registration['Email']);
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($registration['college']) ?></td>
                                <td>
                                    <?= htmlspecialchars($registration['licenseNumber']) ?>
                                    (<?= htmlspecialchars($registration['licenseClass']) ?>)
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $registration['status'] ?>">
                                        <?= ucfirst($registration['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($registration['formatted_last_updated']) ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="vehicle-details.php?id=<?= $registration['vehicle_id'] ?>" class="btn-primary">View</a>
                                        <button class="status-toggle <?= $registration['status'] === 'active' ? 'active' : 'inactive' ?>" onclick="toggleStatus(this, <?= $registration['vehicle_id'] ?>)">
                                            <?= $registration['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="alert" class="alert"></div>

    <script>
        function logout() {
            window.location.href = 'logout.php';
        }

        function toggleStatus(buttonEl, vehicleId) {
            const currentStatus = buttonEl.textContent.includes('Deactivate') ? 'active' : 'inactive';
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            if (!confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this vehicle?`)) {
                return;
            }

            const originalText = buttonEl.textContent;
            buttonEl.disabled = true;
            buttonEl.textContent = 'Please wait...';

            const params = new URLSearchParams();
            params.append('vehicle_id', String(vehicleId));
            params.append('new_status', newStatus);

            fetch('manage-vehicle-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                },
                body: params.toString()
            })
            .then(response => response.ok ? response.json().catch(() => ({success: true})) : Promise.reject())
            .then((data) => {
                showAlert('Status updated successfully', 'success');
                buttonEl.classList.toggle('active', newStatus === 'active');
                buttonEl.classList.toggle('inactive', newStatus === 'inactive');
                buttonEl.textContent = newStatus === 'active' ? 'Deactivate' : 'Activate';
            })
            .catch((error) => {
                showAlert('An error occurred while updating the status', 'error');
            })
            .finally(() => {
                buttonEl.disabled = false;
            });
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert alert-${type}`;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
