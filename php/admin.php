<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';
require_once 'functions.php';

$action = $_GET['action'] ?? 'dashboard';
$success = '';
$errors = [];

// Handle register form submission
if (isset($_POST['register_gymnast'])) {
    $data = [
        'full_name'        => trim($_POST['full_name']),
        'email'            => trim($_POST['email']),
        'membership_id'    => trim($_POST['membership_id']),
        'contact_no'       => trim($_POST['contact_no']),
        'date_of_birth'    => $_POST['date_of_birth'],
        'training_program' => $_POST['training_program'],
        'enrollment_date'  => $_POST['enrollment_date'],
        'password'         => $_POST['password'],
        'status'           => STATUS_ACTIVE
    ];

    if (empty($data['full_name']))         $errors[] = 'Full name is required.';
    if (empty($data['email']))             $errors[] = 'Email is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($data['membership_id']))     $errors[] = 'Membership ID is required.';
    if (empty($data['contact_no']))        $errors[] = 'Contact number is required.';
    if (empty($data['date_of_birth']))     $errors[] = 'Date of birth is required.';
    if (empty($data['training_program']))  $errors[] = 'Training program is required.';
    if (empty($data['enrollment_date']))   $errors[] = 'Enrollment date is required.';
    if (empty($data['password']))          $errors[] = 'Password is required.';

    if (empty($errors)) {
        try {
            registerGymnast($pdo, $data);
            $success = 'Gymnast registered successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Membership ID or email may already exist.';
        }
    }
}

// Handle update form submission
if (isset($_POST['update_gymnast'])) {
    $id = $_GET['id'];
    $data = [
        'full_name'        => trim($_POST['full_name']),
        'email'            => trim($_POST['email']),
        'membership_id'    => trim($_POST['membership_id']),
        'contact_no'       => trim($_POST['contact_no']),
        'date_of_birth'    => $_POST['date_of_birth'],
        'training_program' => $_POST['training_program'],
        'enrollment_date'  => $_POST['enrollment_date'],
        'status'           => $_POST['status']
    ];

    if (empty($data['full_name']))        $errors[] = 'Full name is required.';
    if (empty($data['email']))            $errors[] = 'Email is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($data['membership_id']))    $errors[] = 'Membership ID is required.';
    if (empty($data['contact_no']))       $errors[] = 'Contact number is required.';

    if (empty($errors)) {
        try {
            updateGymnast($pdo, $id, $data);
            $success = 'Gymnast updated successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Update failed. Please try again.';
        }
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        deleteGymnast($pdo, $_GET['id']);
        header('Location: admin.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        $errors[] = 'Delete failed.';
    }
}

if (isset($_GET['deleted'])) {
    $success = 'Gymnast deleted successfully!';
}

$gymnasts = getAllGymnasts($pdo);

$gymnast = null;
if (($action === 'view' || $action === 'edit') && isset($_GET['id'])) {
    $gymnast = getGymnastById($pdo, $_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/main.js" defer></script>
</head>
<body>

<div class="navbar">
    <span class="navbar-brand">Gymnastics Academy</span>
    <div class="navbar-links">
        <a href="admin.php">Dashboard</a>
        <a href="admin.php?action=register">Register Gymnast</a>
        <a href="../react-dashboard.html">React Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($action === 'dashboard'): ?>

    <h2 class="page-heading">All Gymnasts</h2>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search by name or ID...">
        <select id="filterProgram">
            <option value="">All Programs</option>
            <option value="Beginner">Beginner</option>
            <option value="Intermediate">Intermediate</option>
            <option value="Advanced">Advanced</option>
        </select>
        <select id="filterStatus">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="On Hold">On Hold</option>
            <option value="Completed">Completed</option>
            <option value="Pending">Pending</option>
        </select>
    </div>

    <div class="table-card">
        <table id="gymnastsTable">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Membership ID</th>
                    <th>Program</th>
                    <th>Enrollment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gymnasts as $g): ?>
                <tr>
                    <td><?php echo htmlspecialchars($g['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($g['membership_id']); ?></td>
                    <td><?php echo htmlspecialchars($g['training_program']); ?></td>
                    <td><?php echo htmlspecialchars($g['enrollment_date']); ?></td>
                    <td>
                        <?php
                        $status = $g['status'];
                        $badgeClass = match($status) {
                            'Active'    => 'badge-active',
                            'On Hold'   => 'badge-onhold',
                            'Completed' => 'badge-completed',
                            default     => 'badge-pending'
                        };
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="admin.php?action=view&id=<?php echo $g['id']; ?>" class="btn btn-outline btn-sm">View</a>
                            <a href="admin.php?action=edit&id=<?php echo $g['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="admin.php?action=delete&id=<?php echo $g['id']; ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this gymnast? This cannot be undone.')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($gymnasts)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No gymnasts registered yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action === 'register'): ?>

    <h2 class="page-heading">Register New Gymnast</h2>

    <div class="card">
        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                           placeholder="e.g. Jane Doe" required>
                </div>
                <div class="form-group">
                    <label for="membership_id">Membership ID</label>
                    <input type="text" id="membership_id" name="membership_id"
                           value="<?php echo htmlspecialchars($_POST['membership_id'] ?? ''); ?>"
                           placeholder="e.g. GYM003" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="reg_email">Email Address</label>
                    <input type="email" id="reg_email" name="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="jane@example.com" required>
                </div>
                <div class="form-group">
                    <label for="contact_no">Contact Number</label>
                    <input type="text" id="contact_no" name="contact_no"
                           value="<?php echo htmlspecialchars($_POST['contact_no'] ?? ''); ?>"
                           placeholder="e.g. 0821234567" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="enrollment_date">Enrollment Date</label>
                    <input type="date" id="enrollment_date" name="enrollment_date"
                           value="<?php echo htmlspecialchars($_POST['enrollment_date'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="training_program">Training Program</label>
                <select id="training_program" name="training_program" required>
                    <option value="">Select a program</option>
                    <option value="Beginner">Beginner</option>
                    <option value="Intermediate">Intermediate</option>
                    <option value="Advanced">Advanced</option>
                </select>
            </div>

            <div class="form-group">
                <label for="reg_password">Temporary Password</label>
                <input type="password" id="reg_password" name="password"
                       placeholder="Set a login password for this gymnast" required>
            </div>

            <button type="submit" name="register_gymnast" class="btn btn-primary btn-full">
                Register Gymnast
            </button>
        </form>
    </div>

<?php elseif ($action === 'edit' && $gymnast): ?>

    <h2 class="page-heading">Edit Gymnast</h2>

    <div class="card">
        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label for="edit_full_name">Full Name</label>
                    <input type="text" id="edit_full_name" name="full_name"
                           value="<?php echo htmlspecialchars($gymnast['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_membership_id">Membership ID</label>
                    <input type="text" id="edit_membership_id" name="membership_id"
                           value="<?php echo htmlspecialchars($gymnast['membership_id']); ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="edit_email">Email Address</label>
                    <input type="email" id="edit_email" name="email"
                           value="<?php echo htmlspecialchars($gymnast['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_contact_no">Contact Number</label>
                    <input type="text" id="edit_contact_no" name="contact_no"
                           value="<?php echo htmlspecialchars($gymnast['contact_no']); ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="edit_dob">Date of Birth</label>
                    <input type="date" id="edit_dob" name="date_of_birth"
                           value="<?php echo htmlspecialchars($gymnast['date_of_birth']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_enrollment_date">Enrollment Date</label>
                    <input type="date" id="edit_enrollment_date" name="enrollment_date"
                           value="<?php echo htmlspecialchars($gymnast['enrollment_date']); ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="edit_program">Training Program</label>
                    <select id="edit_program" name="training_program" required>
                        <?php foreach (['Beginner', 'Intermediate', 'Advanced'] as $prog): ?>
                            <option value="<?php echo $prog; ?>"
                                <?php echo $gymnast['training_program'] === $prog ? 'selected' : ''; ?>>
                                <?php echo $prog; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <?php foreach (['Active', 'On Hold', 'Completed', 'Pending'] as $st): ?>
                            <option value="<?php echo $st; ?>"
                                <?php echo $gymnast['status'] === $st ? 'selected' : ''; ?>>
                                <?php echo $st; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" name="update_gymnast" class="btn btn-primary btn-full">
                Save Changes
            </button>
        </form>
    </div>

<?php elseif ($action === 'view' && $gymnast): ?>

    <h2 class="page-heading">Gymnast Profile</h2>

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
            <a href="admin.php?action=edit&id=<?php echo $gymnast['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
            <a href="admin.php" class="btn btn-outline btn-sm">Back to Dashboard</a>
        </div>
    </div>

<?php endif; ?>

</div>
</body>
</html>