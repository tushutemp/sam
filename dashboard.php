<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error_message'] = 'Please login first';
    header('Location: index.php');
    exit;
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_email = $_SESSION['admin_email'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                $sql = "SELECT password FROM admin_users WHERE id = $admin_id";
                $result = $conn->query($sql);
                $admin = $result->fetch_assoc();

                if (password_verify($current_password, $admin['password'])) {
                    if ($new_password === $confirm_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE admin_users SET password = '$hashed_password' WHERE id = $admin_id";

                        if ($conn->query($sql)) {
                            $_SESSION['success_message'] = 'Password changed successfully!';
                        } else {
                            $_SESSION['error_message'] = 'Error changing password';
                        }
                    } else {
                        $_SESSION['error_message'] = 'New passwords do not match';
                    }
                } else {
                    $_SESSION['error_message'] = 'Current password is incorrect';
                }
                break;

            case 'create_admin':
                $username = $conn->real_escape_string($_POST['username']);
                $email = $conn->real_escape_string($_POST['email']);
                $full_name = $conn->real_escape_string($_POST['full_name']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $conn->real_escape_string($_POST['role']);

                $sql = "INSERT INTO admin_users (username, email, full_name, password, role, created_by)
                        VALUES ('$username', '$email', '$full_name', '$password', '$role', '$admin_username')";

                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = 'Admin user created successfully!';
                } else {
                    $_SESSION['error_message'] = 'Error creating admin user: ' . $conn->error;
                }
                break;

            case 'fetch_opinion':
                if (isset($_POST['opinion_id']) && is_numeric($_POST['opinion_id'])) {
                    $opinion_id = intval($_POST['opinion_id']);
                    $sql = "SELECT * FROM opinions WHERE id = $opinion_id";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        $opinion = $result->fetch_assoc();
                        $submission_date = date('d M Y, h:i A', strtotime($opinion['submission_date']));

                        echo json_encode([
                            'success' => true,
                            'data' => [
                                'id' => $opinion['id'],
                                'name' => htmlspecialchars($opinion['name']),
                                'email' => htmlspecialchars($opinion['email']),
                                'phone' => htmlspecialchars($opinion['phone'] ?: 'N/A'),
                                'category' => htmlspecialchars($opinion['category'] ?: 'General'),
                                'opinion' => htmlspecialchars($opinion['opinion']),
                                'language' => ucfirst($opinion['language']),
                                'submission_date' => $submission_date,
                                'status' => ucfirst($opinion['status'])
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Opinion not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid opinion ID']);
                }
                exit;
                break;
        }
        header('Location: dashboard.php');
        exit;
    }
}

// Get statistics
$members_count = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
$opinions_count = $conn->query("SELECT COUNT(*) as count FROM opinions")->fetch_assoc()['count'];
$growth_percentage = rand(5, 25);

// Members pagination and search
$members_page = isset($_GET['members_page']) ? intval($_GET['members_page']) : 1;
$members_per_page = 12;
$members_offset = ($members_page - 1) * $members_per_page;

$members_search = isset($_GET['members_search']) ? $conn->real_escape_string($_GET['members_search']) : '';
$members_where = '';
if (!empty($members_search)) {
    $members_where = " WHERE name LIKE '%$members_search%' OR email LIKE '%$members_search%' OR phone LIKE '%$members_search%'";
}

$members_query = "SELECT * FROM members $members_where ORDER BY join_date DESC LIMIT $members_offset, $members_per_page";
$members_result = $conn->query($members_query);

$total_members_query = "SELECT COUNT(*) as count FROM members $members_where";
$total_members_count = $conn->query($total_members_query)->fetch_assoc()['count'];
$total_members_pages = ceil($total_members_count / $members_per_page);

// Opinions pagination and search
$opinions_page = isset($_GET['opinions_page']) ? intval($_GET['opinions_page']) : 1;
$opinions_per_page = 12;
$opinions_offset = ($opinions_page - 1) * $opinions_per_page;

$opinions_search = isset($_GET['opinions_search']) ? $conn->real_escape_string($_GET['opinions_search']) : '';
$opinions_where = '';
if (!empty($opinions_search)) {
    $opinions_where = " WHERE name LIKE '%$opinions_search%' OR email LIKE '%$opinions_search%' OR phone LIKE '%$opinions_search%' OR category LIKE '%$opinions_search%' OR opinion LIKE '%$opinions_search%'";
}

$opinions_query = "SELECT * FROM opinions $opinions_where ORDER BY submission_date DESC LIMIT $opinions_offset, $opinions_per_page";
$opinions_result = $conn->query($opinions_query);

$total_opinions_query = "SELECT COUNT(*) as count FROM opinions $opinions_where";
$total_opinions_count = $conn->query($total_opinions_query)->fetch_assoc()['count'];
$total_opinions_pages = ceil($total_opinions_count / $opinions_per_page);

// Get admin users
$admin_users_result = $conn->query("SELECT * FROM admin_users ORDER BY created_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sarvatantra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <!-- Alert Message Container -->
    <div id="alertContainer">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-message show">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?></span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-message show">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?></span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>

    <!-- Mobile Menu Toggle -->
    <div class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo">
                <i class="fas fa-democrat"></i>
                <span>Sarvatantra</span>
            </a>
        </div>

        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a></li>
            <li><a href="dashboard.php?section=members" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'members') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Members</span>
            </a></li>
            <li><a href="dashboard.php?section=opinions" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'opinions') ? 'active' : ''; ?>">
                <i class="far fa-comment-dots"></i>
                <span>Opinions</span>
            </a></li>
            <li><a href="dashboard.php?section=admin_users" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'admin_users') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span>Admin Users</span>
            </a></li>
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a></li>
        </ul>

        <div class="logout-btn" id="logoutBtn" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header fade-in">
            <h1>
                <?php
                $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
                switch($section) {
                    case 'members': echo 'Members'; break;
                    case 'opinions': echo 'Opinions'; break;
                    case 'admin_users': echo 'Admin Users'; break;
                    default: echo 'Dashboard'; break;
                }
                ?>
            </h1>
            <div class="user-info">
                <div class="user-avatar">
                    <span><?php echo strtoupper(substr($admin_name, 0, 1)); ?></span>
                </div>
                <div>
                    <div style="font-weight: 700;"><?php echo $admin_name; ?></div>
                    <div style="font-size: 0.85rem; color: #666;"><?php echo $admin_email; ?></div>
                </div>
            </div>
        </div>

        <?php
        $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

        if ($section === 'dashboard'): ?>
        <!-- Dashboard Stats -->
        <div class="stats-cards fade-in">
            <div class="stat-card members">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Total Members</h3>
                <div class="value"><?php echo $members_count; ?></div>
            </div>
            <div class="stat-card opinions">
                <div class="icon"><i class="far fa-comment-dots"></i></div>
                <h3>Total Opinions</h3>
                <div class="value"><?php echo $opinions_count; ?></div>
            </div>
            <div class="stat-card growth">
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <h3>Growth This Month</h3>
                <div class="value">+<?php echo $growth_percentage; ?>%</div>
            </div>
        </div>

        <!-- Members Section on Dashboard -->
        <div class="content-section fade-in">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-users"></i> All Members</h2>
                <div class="section-subtitle"><?php echo $members_count; ?> members found</div>
            </div>

            <div class="search-container">
                <form method="GET" action="dashboard.php" class="search-box">
                    <input type="hidden" name="section" value="members">
                    <input type="text" class="search-input" name="members_search" placeholder="Search members by name, email or phone..." value="<?php echo isset($_GET['members_search']) ? htmlspecialchars($_GET['members_search']) : ''; ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($members_search)): ?>
                    <a href="dashboard.php?section=members" class="clear-search-btn"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($members_search)): ?>
            <div class="search-results-info fade-in">
                <div>Search results for: <span class="search-term">"<?php echo htmlspecialchars($members_search); ?>"</span> - found <strong><?php echo $total_members_count; ?></strong> results</div>
            </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SR. NO.</th>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>PHONE</th>
                            <th>JOIN DATE</th>
                            <th>GENDER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 1;
                        while ($member = $members_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count + $members_offset; ?></td>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td><?php echo date('d M Y', strtotime($member['join_date'])); ?></td>
                            <td><?php echo ucfirst($member['gender']); ?></td>
                        </tr>
                        <?php $count++; endwhile;
                        if ($members_result->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-users fa-2x mb-3" style="color: #ddd;"></i>
                                <div>No members found</div>
                                <?php if (!empty($members_search)): ?>
                                <div class="mt-2"><a href="dashboard.php?section=members" style="color: var(--accent-teal); text-decoration: none;">Clear search and show all members</a></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <div class="pagination-info">Showing <?php echo min($total_members_count, $members_offset + 1); ?>-<?php echo min($total_members_count, $members_offset + $members_per_page); ?> of <?php echo $total_members_count; ?></div>
                <div class="pagination-buttons">
                    <a class="page-btn" <?php echo $members_page <= 1 ? 'disabled' : ''; ?> href="dashboard.php?section=members&members_page=<?php echo $members_page - 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>">Previous</a>
                    <?php for ($i = 1; $i <= min(4, $total_members_pages); $i++): ?>
                    <a class="page-btn <?php echo $members_page == $i ? 'active' : ''; ?>" href="dashboard.php?section=members&members_page=<?php echo $i; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($total_members_pages > 4): ?><span class="page-btn disabled">...</span><?php endif; ?>
                    <a class="page-btn" <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?> href="dashboard.php?section=members&members_page=<?php echo $members_page + 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>">Next</a>
                </div>
            </div>
        </div>

        <?php elseif ($section === 'members'): ?>
        <!-- Members Section -->
        <div class="content-section fade-in">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-users"></i> All Members</h2>
                <div class="section-subtitle"><?php echo $total_members_count; ?> members found</div>
            </div>

            <div class="search-container">
                <form method="GET" action="dashboard.php" class="search-box">
                    <input type="hidden" name="section" value="members">
                    <input type="text" class="search-input" name="members_search" placeholder="Search members by name, email or phone..." value="<?php echo isset($_GET['members_search']) ? htmlspecialchars($_GET['members_search']) : ''; ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($members_search)): ?>
                    <a href="dashboard.php?section=members" class="clear-search-btn"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($members_search)): ?>
            <div class="search-results-info fade-in">
                <div>Search results for: <span class="search-term">"<?php echo htmlspecialchars($members_search); ?>"</span> - found <strong><?php echo $total_members_count; ?></strong> results</div>
            </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SR. NO.</th>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>PHONE</th>
                            <th>JOIN DATE</th>
                            <th>GENDER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 1;
                        while ($member = $members_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count + $members_offset; ?></td>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td><?php echo date('d M Y', strtotime($member['join_date'])); ?></td>
                            <td><?php echo ucfirst($member['gender']); ?></td>
                        </tr>
                        <?php $count++; endwhile;
                        if ($members_result->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-users fa-2x mb-3" style="color: #ddd;"></i>
                                <div>No members found</div>
                                <?php if (!empty($members_search)): ?>
                                <div class="mt-2"><a href="dashboard.php?section=members" style="color: var(--accent-teal); text-decoration: none;">Clear search and show all members</a></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <div class="pagination-info">Showing <?php echo min($total_members_count, $members_offset + 1); ?>-<?php echo min($total_members_count, $members_offset + $members_per_page); ?> of <?php echo $total_members_count; ?></div>
                <div class="pagination-buttons">
                    <a class="page-btn" <?php echo $members_page <= 1 ? 'disabled' : ''; ?> href="dashboard.php?section=members&members_page=<?php echo $members_page - 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>">Previous</a>
                    <?php for ($i = 1; $i <= min(4, $total_members_pages); $i++): ?>
                    <a class="page-btn <?php echo $members_page == $i ? 'active' : ''; ?>" href="dashboard.php?section=members&members_page=<?php echo $i; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($total_members_pages > 4): ?><span class="page-btn disabled">...</span><?php endif; ?>
                    <a class="page-btn" <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?> href="dashboard.php?section=members&members_page=<?php echo $members_page + 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>">Next</a>
                </div>
            </div>
        </div>

        <?php elseif ($section === 'opinions'): ?>
        <!-- Opinions Section -->
        <div class="content-section fade-in">
            <div class="section-header">
                <h2 class="section-title"><i class="far fa-comment-dots"></i> Opinions</h2>
                <div class="section-subtitle"><?php echo $total_opinions_count; ?> opinions found</div>
            </div>

            <div class="search-container">
                <form method="GET" action="dashboard.php" class="search-box">
                    <input type="hidden" name="section" value="opinions">
                    <input type="text" class="search-input" name="opinions_search" placeholder="Search opinions by name, email, phone, category or content..." value="<?php echo isset($_GET['opinions_search']) ? htmlspecialchars($_GET['opinions_search']) : ''; ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($opinions_search)): ?>
                    <a href="dashboard.php?section=opinions" class="clear-search-btn"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($opinions_search)): ?>
            <div class="search-results-info fade-in">
                <div>Search results for: <span class="search-term">"<?php echo htmlspecialchars($opinions_search); ?>"</span> - found <strong><?php echo $total_opinions_count; ?></strong> results</div>
            </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SR. NO.</th>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>PHONE</th>
                            <th>CATEGORY</th>
                            <th>DATE</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 1;
                        while ($opinion = $opinions_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count + $opinions_offset; ?></td>
                            <td><?php echo htmlspecialchars($opinion['name']); ?></td>
                            <td><?php echo htmlspecialchars($opinion['email']); ?></td>
                            <td><?php echo htmlspecialchars($opinion['phone'] ?: 'N/A'); ?></td>
                            <td><span class="badge badge-category"><?php echo htmlspecialchars($opinion['category'] ?: 'General'); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($opinion['submission_date'])); ?></td>
                            <td>
                                <button class="action-btn btn-view" onclick="viewOpinion(<?php echo $opinion['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php $count++; endwhile;
                        if ($opinions_result->num_rows == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                <i class="far fa-comment-dots fa-2x mb-3" style="color: #ddd;"></i>
                                <div>No opinions found</div>
                                <?php if (!empty($opinions_search)): ?>
                                <div class="mt-2"><a href="dashboard.php?section=opinions" style="color: var(--accent-teal); text-decoration: none;">Clear search and show all opinions</a></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <div class="pagination-info">Showing <?php echo min($total_opinions_count, $opinions_offset + 1); ?>-<?php echo min($total_opinions_count, $opinions_offset + $opinions_per_page); ?> of <?php echo $total_opinions_count; ?></div>
                <div class="pagination-buttons">
                    <a class="page-btn" <?php echo $opinions_page <= 1 ? 'disabled' : ''; ?> href="dashboard.php?section=opinions&opinions_page=<?php echo $opinions_page - 1; ?><?php echo !empty($opinions_search) ? '&opinions_search=' . urlencode($opinions_search) : ''; ?>">Previous</a>
                    <?php for ($i = 1; $i <= min(4, $total_opinions_pages); $i++): ?>
                    <a class="page-btn <?php echo $opinions_page == $i ? 'active' : ''; ?>" href="dashboard.php?section=opinions&opinions_page=<?php echo $i; ?><?php echo !empty($opinions_search) ? '&opinions_search=' . urlencode($opinions_search) : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($total_opinions_pages > 4): ?><span class="page-btn disabled">...</span><?php endif; ?>
                    <a class="page-btn" <?php echo $opinions_page >= $total_opinions_pages ? 'disabled' : ''; ?> href="dashboard.php?section=opinions&opinions_page=<?php echo $opinions_page + 1; ?><?php echo !empty($opinions_search) ? '&opinions_search=' . urlencode($opinions_search) : ''; ?>">Next</a>
                </div>
            </div>
        </div>

        <?php elseif ($section === 'admin_users'): ?>
        <!-- Admin Users Section -->
        <div class="content-section fade-in">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-user-shield"></i> Admin Users</h2>
                <button class="action-btn btn-create-admin" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                    <i class="fas fa-user-plus"></i> Create Admin User
                </button>
            </div>

            <div class="admin-users-container">
                <?php while ($admin_user = $admin_users_result->fetch_assoc()):
                    $role_label = ucfirst(str_replace('_', ' ', $admin_user['role']));
                    $status_color = $admin_user['status'] == 'active' ? 'var(--success-color)' : '#e74c3c';
                ?>
                <div class="admin-user-card">
                    <div class="admin-user-header">
                        <div class="admin-user-avatar"><?php echo strtoupper(substr($admin_user['username'], 0, 1)); ?></div>
                        <div class="admin-user-info">
                            <h4><?php echo htmlspecialchars($admin_user['username']); ?></h4>
                            <p><?php echo htmlspecialchars($admin_user['full_name']); ?></p>
                        </div>
                    </div>
                    <div class="admin-user-details">
                        <div class="detail-item">
                            <label>Role</label>
                            <div class="value"><?php echo $role_label; ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Email</label>
                            <div class="value"><?php echo htmlspecialchars($admin_user['email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Created</label>
                            <div class="value"><?php echo $admin_user['created_by'] ? 'By ' . htmlspecialchars($admin_user['created_by']) : 'System'; ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <div class="value" style="color: <?php echo $status_color; ?>;"><?php echo ucfirst($admin_user['status']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="pagination-container">
                <div class="pagination-info">Showing all admin users</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Opinion Detail Modal -->
    <div class="modal fade" id="opinionDetailModal" tabindex="-1" aria-labelledby="opinionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="far fa-comment-dots me-2"></i>Opinion Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="opinionDetailBody">
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" placeholder="Enter current password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="Enter new password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Admin User Modal -->
    <div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create Admin User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createAdminForm" method="POST">
                        <input type="hidden" name="action" value="create_admin">
                        <div class="mb-3">
                            <label for="adminUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="adminUsername" name="username" placeholder="Enter username" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="adminEmail" name="email" placeholder="admin@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="adminFullName" name="full_name" placeholder="Enter full name" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="adminPassword" name="password" placeholder="Enter password" required>
                            <div class="form-text">Password must be at least 6 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label for="adminConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="adminConfirmPassword" name="confirm_password" placeholder="Confirm password" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminRole" class="form-label">Role</label>
                            <select class="form-control" id="adminRole" name="role" required>
                                <option value="admin">Administrator</option>
                                <option value="moderator">Moderator</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-user-plus me-2"></i>Create Admin User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>

    <!-- Custom Language Selector (powered by Google Translate) -->
    <div class="gt-widget" id="gtWidget">
        <div class="gt-toggle" id="gtToggle" onclick="toggleGTDropdown(event)">
            <i class="fas fa-globe gt-icon"></i>
            <span class="gt-current" id="gtCurrentLang">Hindi</span>
            <i class="fas fa-chevron-down gt-arrow" id="gtArrow"></i>
        </div>
        <div class="gt-dropdown" id="gtDropdown">
            <div class="gt-dropdown-header">Select Language</div>
            <div class="gt-options" id="gtOptions"></div>
        </div>
        <div id="google_translate_element" style="display:none !important;height:0 !important;overflow:hidden !important;"></div>
    </div>

    <script type="text/javascript">
        // Language list
        var gtLanguages = [
            {code:'en', name:'English'},
            {code:'hi', name:'Hindi'},
            {code:'bn', name:'Bengali'},
            {code:'ta', name:'Tamil'},
            {code:'te', name:'Telugu'},
            {code:'mr', name:'Marathi'},
            {code:'gu', name:'Gujarati'},
            {code:'kn', name:'Kannada'},
            {code:'ml', name:'Malayalam'},
            {code:'or', name:'Odia'},
            {code:'pa', name:'Punjabi'},
            {code:'as', name:'Assamese'},
            {code:'ur', name:'Urdu'},
            {code:'ne', name:'Nepali'},
            {code:'sd', name:'Sindhi'},
            {code:'fr', name:'French'},
            {code:'es', name:'Spanish'},
            {code:'ar', name:'Arabic'},
            {code:'zh-CN', name:'Chinese'},
            {code:'ru', name:'Russian'},
            {code:'de', name:'German'},
            {code:'ja', name:'Japanese'},
            {code:'pt', name:'Portuguese'}
        ];

        // Set Hindi as default language on first visit
        (function() {
            var match = document.cookie.match(/googtrans=([^;]*)/);
            if (!match || !match[1] || match[1] === '') {
                document.cookie = "googtrans=/en/hi; path=/";
                document.cookie = "googtrans=/en/hi; path=/; domain=." + location.hostname;
            }
        })();

        // Detect current language from cookie
        function getCurrentLang() {
            var match = document.cookie.match(/googtrans=\/en\/([^;]*)/);
            return match ? match[1] : 'hi';
        }

        // Build the custom dropdown options
        function buildGTDropdown() {
            var container = document.getElementById('gtOptions');
            var currentLang = getCurrentLang();
            var html = '';
            gtLanguages.forEach(function(lang) {
                var isActive = lang.code === currentLang;
                html += '<div class="gt-option' + (isActive ? ' active' : '') + '" data-lang="' + lang.code + '" onclick="selectLanguage(\'' + lang.code + '\')">' +
                    '<span class="gt-option-name">' + lang.name + '</span>' +
                    (isActive ? '<i class="fas fa-check"></i>' : '') +
                '</div>';
            });
            container.innerHTML = html;

            var current = gtLanguages.find(function(l) { return l.code === currentLang; });
            if (current) {
                document.getElementById('gtCurrentLang').textContent = current.name;
            }
        }

        // Toggle dropdown with timing guard
        var gtLastToggle = 0;
        function toggleGTDropdown(e) {
            e.stopPropagation();
            e.preventDefault();
            gtLastToggle = Date.now();
            var dropdown = document.getElementById('gtDropdown');
            var arrow = document.getElementById('gtArrow');
            dropdown.classList.toggle('show');
            arrow.classList.toggle('open');
        }

        // Select a language - programmatically trigger Google Translate
        function selectLanguage(langCode) {
            gtLastToggle = Date.now();
            var combo = document.querySelector('.goog-te-combo');
            if (combo) {
                combo.value = langCode;
                combo.dispatchEvent(new Event('change'));
            } else {
                document.cookie = "googtrans=/en/" + langCode + "; path=/";
                document.cookie = "googtrans=/en/" + langCode + "; path=/; domain=." + location.hostname;
                location.reload();
            }

            var langObj = gtLanguages.find(function(l) { return l.code === langCode; });
            if (langObj) {
                document.getElementById('gtCurrentLang').textContent = langObj.name;
            }
            document.getElementById('gtDropdown').classList.remove('show');
            document.getElementById('gtArrow').classList.remove('open');
            setTimeout(function() { buildGTDropdown(); }, 100);
        }

        // Close dropdown when clicking outside (with timing guard)
        document.addEventListener('click', function(e) {
            if (Date.now() - gtLastToggle < 400) return;
            var widget = document.getElementById('gtWidget');
            if (widget && !widget.contains(e.target)) {
                document.getElementById('gtDropdown').classList.remove('show');
                document.getElementById('gtArrow').classList.remove('open');
            }
        });

        // Google Translate init (hidden, only used as engine)
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'hi,bn,ta,te,mr,gu,kn,ml,or,pa,as,ur,ne,sd,en,fr,es,ar,zh-CN,ru,de,ja,pt',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }

        // Hide ALL Google Translate UI (we use our own custom dropdown)
        function hideAllGTUI() {
            var kids = document.body.children;
            for (var i = 0; i < kids.length; i++) {
                if (kids[i].classList && kids[i].classList.contains('skiptranslate') && kids[i].id !== 'gtWidget') {
                    kids[i].style.setProperty('display', 'none', 'important');
                    kids[i].style.setProperty('height', '0', 'important');
                }
            }
            document.querySelectorAll('.goog-te-banner-frame, .VIpgJd-ZVi9od-ORHb-OEVmcd, #goog-gt-tt').forEach(function(el) {
                el.style.setProperty('display', 'none', 'important');
            });
            document.body.style.setProperty('top', '0px', 'important');
        }
        setInterval(hideAllGTUI, 100);

        document.addEventListener('DOMContentLoaded', buildGTDropdown);
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <style>
        /* Custom Language Widget */
        .gt-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            font-family: 'Inter', sans-serif;
        }
        .gt-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 12px 20px;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 24px rgba(6, 30, 41, 0.10), 0 1px 4px rgba(6, 30, 41, 0.06);
            border: 1.5px solid rgba(95, 149, 152, 0.15);
            transition: all 0.3s ease;
            user-select: none;
        }
        .gt-toggle:hover {
            box-shadow: 0 8px 32px rgba(6, 30, 41, 0.16);
            transform: translateY(-2px);
            border-color: rgba(95, 149, 152, 0.35);
        }
        .gt-toggle:active {
            transform: translateY(0);
        }
        .gt-icon {
            color: #5f9598;
            font-size: 1.1rem;
        }
        .gt-current {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1d546d;
        }
        .gt-arrow {
            font-size: 0.65rem;
            color: #999;
            transition: transform 0.3s ease;
            margin-left: 2px;
        }
        .gt-arrow.open {
            transform: rotate(180deg);
        }
        .gt-dropdown {
            position: absolute;
            bottom: calc(100% + 10px);
            right: 0;
            width: 230px;
            max-height: 340px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 16px 48px rgba(6, 30, 41, 0.16), 0 2px 8px rgba(6, 30, 41, 0.06);
            border: 1px solid rgba(95, 149, 152, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(0.97);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }
        .gt-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        .gt-dropdown-header {
            padding: 14px 18px 10px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #5f9598;
            border-bottom: 1px solid rgba(95, 149, 152, 0.1);
        }
        .gt-options {
            max-height: 280px;
            overflow-y: auto;
            padding: 6px 0;
        }
        .gt-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 18px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 0.88rem;
            color: #444;
        }
        .gt-option:hover {
            background: rgba(95, 149, 152, 0.08);
            color: #1d546d;
        }
        .gt-option.active {
            color: #1d546d;
            font-weight: 700;
            background: rgba(95, 149, 152, 0.06);
        }
        .gt-option .fa-check {
            color: #5f9598;
            font-size: 0.7rem;
        }
        .gt-options::-webkit-scrollbar {
            width: 4px;
        }
        .gt-options::-webkit-scrollbar-track {
            background: transparent;
        }
        .gt-options::-webkit-scrollbar-thumb {
            background: rgba(95, 149, 152, 0.2);
            border-radius: 10px;
        }
        /* Hide ALL Google Translate UI */
        body > .skiptranslate {
            display: none !important;
            height: 0 !important;
            overflow: hidden !important;
        }
        .goog-te-banner-frame {
            display: none !important;
        }
        #goog-gt-tt {
            display: none !important;
        }
        #google_translate_element {
            display: none !important;
        }
        body {
            top: 0px !important;
        }
    </style>
</body>
</html>
