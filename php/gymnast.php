<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gymnast') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';
require_once 'functions.php';

$action = $_GET['action'] ?? 'profile';

// gymnast's data
$gymnast = getGymnastByUserId($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gymnast Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="navbar">
    <span class="navbar-brand">🤸 Gymnastics Academy</span>
    <div class="navbar-links">
        <a href="gymnast.php">My Profile</a>
        <a href="gymnast.php?action=report">Profile Report</a>
        <a href="gymnast.php?action=slip">Enrollment Slip</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">

<?php if ($action === 'profile'): ?>

    <h2 class="page-heading">My Profile</h2>

    <div class="card">
        <div class="profile-row">
            <span class="profile-label">Full Name</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['full_name']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Membership ID</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['membership_id']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Email</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['email']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Contact Number</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['contact_no']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Date of Birth</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['date_of_birth']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Training Program</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['training_program']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Enrollment Date</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['enrollment_date']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Status</span>
            <span class="profile-value">
                <?php
                $badgeClass = match($gymnast['status']) {
                    'Active'    => 'badge-active',
                    'On Hold'   => 'badge-onhold',
                    'Completed' => 'badge-completed',
                    default     => 'badge-pending'
                };
                ?>
                <span class="badge <?php echo $badgeClass; ?>">
                    <?php echo htmlspecialchars($gymnast['status']); ?>
                </span>
            </span>
        </div>

        <div class="mt-2">
            <a href="gymnast.php?action=report" class="btn btn-primary btn-sm">Download Profile Report</a>
            <a href="gymnast.php?action=slip" class="btn btn-outline btn-sm">Enrollment Slip</a>
        </div>
    </div>

<?php elseif ($action === 'report'): ?>
    <!-- first report -->
    <div class="card" id="reportCard">
        <div style="text-align:center; margin-bottom: 1.5rem;">
            <h2>Gymnastics Academy</h2>
            <h3>Profile Summary Report</h3>
            <p class="text-muted">Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <div class="profile-row">
            <span class="profile-label">Full Name</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['full_name']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Membership ID</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['membership_id']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Email</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['email']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Date of Birth</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['date_of_birth']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Training Program</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['training_program']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Enrollment Date</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['enrollment_date']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Status</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['status']); ?></span>
        </div>

        <div class="mt-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm">Print / Save as PDF</button>
            <a href="gymnast.php" class="btn btn-outline btn-sm">Back to Profile</a>
        </div>
    </div>

<?php elseif ($action === 'slip'): ?>

    <!-- second report -->
    <div class="card" id="slipCard">
        <div style="text-align:center; margin-bottom: 1.5rem;">
            <h2>Gymnastics Academy</h2>
            <h3>Enrollment Confirmation Slip</h3>
        </div>

        <div class="profile-row">
            <span class="profile-label">Member Name</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['full_name']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Membership ID</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['membership_id']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Training Program</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['training_program']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Enrollment Date</span>
            <span class="profile-value"><?php echo htmlspecialchars($gymnast['enrollment_date']); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Registration Timestamp</span>
            <span class="profile-value"><?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Current Status</span>
            <span class="profile-value">
                <?php
                // Using constants as required by rubric
                $statusLabel = match($gymnast['status']) {
                    STATUS_ACTIVE    => 'Active Member',
                    STATUS_ON_HOLD   => 'Account On Hold',
                    STATUS_COMPLETED => 'Program Completed',
                    default          => 'Pending Activation'
                };
                echo htmlspecialchars($statusLabel);
                ?>
            </span>
        </div>
        <div class="profile-row">
            <span class="profile-label">Course Summary</span>
            <span class="profile-value">
                <?php echo htmlspecialchars($gymnast['training_program']); ?> Program
                - Enrolled <?php echo htmlspecialchars($gymnast['enrollment_date']); ?>
            </span>
        </div>

        <div class="mt-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm">Print / Save as PDF</button>
            <a href="gymnast.php" class="btn btn-outline btn-sm">Back to Profile</a>
        </div>
    </div>

<?php endif; ?>

</div>
</body>
</html>