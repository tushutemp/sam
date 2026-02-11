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

// Handle language switching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'switch_language') {
    $language = $_POST['language'];
    $_SESSION['dashboard_language'] = $language;
    echo json_encode(['success' => true, 'language' => $language]);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Get current password from database
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
        }
        header('Location: dashboard.php');
        exit;
    }
}

// Get statistics
$members_count = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
$opinions_count = $conn->query("SELECT COUNT(*) as count FROM opinions")->fetch_assoc()['count'];

// Calculate growth percentage (dummy calculation)
$growth_percentage = rand(5, 25);

// Get members for table (with pagination)
$members_page = isset($_GET['members_page']) ? intval($_GET['members_page']) : 1;
$members_per_page = 6;
$members_offset = ($members_page - 1) * $members_per_page;
$members_result = $conn->query("SELECT * FROM members ORDER BY join_date DESC LIMIT $members_offset, $members_per_page");
$total_members_pages = ceil($members_count / $members_per_page);

// Get opinions for table (with pagination)
$opinions_page = isset($_GET['opinions_page']) ? intval($_GET['opinions_page']) : 1;
$opinions_per_page = 6;
$opinions_offset = ($opinions_page - 1) * $opinions_per_page;
$opinions_result = $conn->query("SELECT * FROM opinions ORDER BY submission_date DESC LIMIT $opinions_offset, $opinions_per_page");
$total_opinions_pages = ceil($opinions_count / $opinions_per_page);

// Get admin users
$admin_users_result = $conn->query("SELECT * FROM admin_users ORDER BY created_date DESC");

// Get current language from session or default to English
$current_language = isset($_SESSION['dashboard_language']) ? $_SESSION['dashboard_language'] : 'en';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sarvatantra</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #061e29;
            --secondary-blue: #1d546d;
            --accent-teal: #5f9598;
            --light-bg: #f3f4f4;
            --success-color: #27ae60;
            --border-radius: 8px;
            --transition-speed: 0.4s;
            --shadow-light: 0 4px 12px rgba(6, 30, 41, 0.08);
            --shadow-medium: 0 8px 24px rgba(6, 30, 41, 0.12);
            --shadow-heavy: 0 12px 36px rgba(6, 30, 41, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--primary-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 20px 0;
            z-index: 1000;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 3px 0 15px rgba(6, 30, 41, 0.15);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(95, 149, 152, 0.2);
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all var(--transition-speed) ease;
        }
        
        .logo i {
            color: var(--accent-teal);
            background: rgba(95, 149, 152, 0.1);
            padding: 10px;
            border-radius: 8px;
        }
        
        .logo:hover {
            transform: translateY(-2px);
            color: #fff;
        }
        
        .nav-links {
            list-style: none;
            padding-left: 0;
        }
        
        .nav-links li {
            margin-bottom: 5px;
        }
        
        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(95, 149, 152, 0.1) 0%, rgba(95, 149, 152, 0.05) 100%);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .nav-links a:hover::before, .nav-links a.active::before {
            left: 0;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--accent-teal);
        }
        
        .nav-links a i {
            width: 20px;
            text-align: center;
        }
        
        .logout-btn {
            margin-top: 20px;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            border-left: 4px solid transparent;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(231, 76, 60, 0.1) 0%, rgba(231, 76, 60, 0.05) 100%);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .logout-btn:hover::before {
            left: 0;
        }
        
        .logout-btn:hover {
            color: white;
            border-left-color: #e74c3c;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all var(--transition-speed) ease;
        }
        
        /* Header */
        .header {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(95, 149, 152, 0.1);
        }
        
        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            background: linear-gradient(90deg, var(--primary-dark), var(--secondary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-teal), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 8px rgba(95, 149, 152, 0.3);
        }
        
        /* Language Dropdown */
        .language-dropdown .btn {
            background: linear-gradient(135deg, rgba(95, 149, 152, 0.1), rgba(29, 84, 109, 0.1));
            color: var(--secondary-blue);
            border: 2px solid rgba(95, 149, 152, 0.3);
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all var(--transition-speed) ease;
            font-size: 0.9rem;
            min-width: 120px;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .language-dropdown .btn:hover {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-teal));
            color: white;
            border-color: var(--secondary-blue);
            transform: translateY(-2px);
        }
        
        .language-dropdown .dropdown-menu {
            border-radius: 8px;
            border: 2px solid rgba(95, 149, 152, 0.3);
            box-shadow: var(--shadow-medium);
            min-width: 120px;
        }
        
        .language-dropdown .dropdown-item {
            padding: 10px 16px;
            transition: all var(--transition-speed) ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .language-dropdown .dropdown-item:hover {
            background: linear-gradient(135deg, rgba(95, 149, 152, 0.1), rgba(29, 84, 109, 0.05));
            color: var(--secondary-blue);
        }
        
        .language-dropdown .dropdown-item i {
            width: 16px;
            text-align: center;
        }
        
        /* Dashboard Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, white 0%, #f8fafc 100%);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(95, 149, 152, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(95, 149, 152, 0.1), transparent);
            border-radius: 0 0 0 80px;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-medium);
        }
        
        .stat-card h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-dark);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.members .icon {
            background: linear-gradient(135deg, rgba(29, 84, 109, 0.1), rgba(95, 149, 152, 0.1));
            color: var(--secondary-blue);
        }
        
        .stat-card.opinions .icon {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1), rgba(46, 204, 113, 0.1));
            color: var(--success-color);
        }
        
        .stat-card.growth .icon {
            background: linear-gradient(135deg, rgba(155, 89, 182, 0.1), rgba(142, 68, 173, 0.1));
            color: #9b59b6;
        }
        
        /* Content Section */
        .content-section {
            background: linear-gradient(135deg, white 0%, #f8fafc 100%);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
            border: 1px solid rgba(95, 149, 152, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .content-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(95, 149, 152, 0.1), transparent);
            border-radius: 0 0 0 100px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(95, 149, 152, 0.2);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary-blue);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--accent-teal);
        }
        
        .section-subtitle {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 25px;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: linear-gradient(90deg, rgba(29, 84, 109, 0.1), rgba(95, 149, 152, 0.05));
        }
        
        .data-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-dark);
            border-bottom: 2px solid rgba(95, 149, 152, 0.3);
        }
        
        .data-table td {
            padding: 18px 15px;
            border-bottom: 1px solid rgba(95, 149, 152, 0.1);
            vertical-align: middle;
        }
        
        .data-table tbody tr {
            transition: all var(--transition-speed) ease;
        }
        
        .data-table tbody tr:hover {
            background-color: rgba(95, 149, 152, 0.05);
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-category {
            background: linear-gradient(135deg, rgba(95, 149, 152, 0.1), rgba(95, 149, 152, 0.2));
            color: var(--secondary-blue);
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-view {
            background: linear-gradient(135deg, rgba(29, 84, 109, 0.1), rgba(95, 149, 152, 0.1));
            color: var(--secondary-blue);
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, var(--secondary-blue), #15455a);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(29, 84, 109, 0.2);
        }
        
        .btn-create-admin {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1), rgba(46, 204, 113, 0.1));
            color: var(--success-color);
            padding: 10px 20px;
            margin-left: 10px;
        }
        
        .btn-create-admin:hover {
            background: linear-gradient(135deg, var(--success-color), #219653);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(39, 174, 96, 0.2);
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(95, 149, 152, 0.2);
        }
        
        .pagination-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .pagination-buttons {
            display: flex;
            gap: 8px;
        }
        
        .page-btn {
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 600;
            background: linear-gradient(135deg, rgba(29, 84, 109, 0.1), rgba(95, 149, 152, 0.1));
            color: var(--secondary-blue);
            border: 1px solid rgba(95, 149, 152, 0.3);
            cursor: pointer;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .page-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-teal));
            color: white;
            border-color: var(--secondary-blue);
            transform: translateY(-2px);
        }
        
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .page-btn.active {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-teal));
            color: white;
            border-color: var(--secondary-blue);
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow-heavy);
            overflow: hidden;
            border: 1px solid rgba(95, 149, 152, 0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-blue) 100%);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 25px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: all var(--transition-speed) ease;
        }
        
        .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        
        .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 700;
            color: var(--secondary-blue);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            border: 2px solid rgba(95, 149, 152, 0.3);
            border-radius: 8px;
            padding: 14px 18px;
            transition: all var(--transition-speed) ease;
            font-size: 1rem;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 4px rgba(95, 149, 152, 0.2);
            background: white;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--accent-teal), var(--secondary-blue));
            color: white;
            padding: 16px;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            width: 100%;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 1.1rem;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(95, 149, 152, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, var(--secondary-blue), #15455a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(95, 149, 152, 0.3);
        }
        
        .opinion-detail {
            margin-bottom: 20px;
        }
        
        .opinion-detail label {
            font-weight: 600;
            color: var(--primary-dark);
            display: block;
            margin-bottom: 5px;
        }
        
        .opinion-detail .value {
            padding: 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
            border-radius: 8px;
            border-left: 4px solid var(--accent-teal);
        }
        
        /* Admin Users Section */
        .admin-users-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .admin-user-card {
            background: linear-gradient(135deg, white 0%, #f8fafc 100%);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(95, 149, 152, 0.1);
            transition: all var(--transition-speed) ease;
        }
        
        .admin-user-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .admin-user-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(95, 149, 152, 0.2);
        }
        
        .admin-user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-teal), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
            box-shadow: 0 4px 8px rgba(95, 149, 152, 0.3);
        }
        
        .admin-user-info h4 {
            margin: 0;
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .admin-user-info p {
            margin: 5px 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .admin-user-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        
        .detail-item label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 3px;
            display: block;
        }
        
        .detail-item .value {
            font-weight: 600;
            color: var(--secondary-blue);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .sidebar {
                width: 100%;
                max-width: 300px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                align-self: flex-end;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Alert Messages */
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-heavy);
            border: none;
        }
        
        .alert-message.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-blue));
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(6, 30, 41, 0.2);
            cursor: pointer;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .mobile-menu-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(6, 30, 41, 0.3);
        }
        
        @media (max-width: 992px) {
            .mobile-menu-toggle {
                display: flex;
            }
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--accent-teal), var(--secondary-blue));
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--secondary-blue), #15455a);
        }
        
        /* Translation Loading */
        .translation-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(6, 30, 41, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            font-size: 1.2rem;
        }
        
        .translation-loading-content {
            text-align: center;
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-blue));
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--accent-teal);
        }
    </style>
</head>
<body data-language="<?php echo $current_language; ?>">
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
                <span class="translatable" data-key="logo">सर्वतंत्र</span>
            </a>
        </div>
        
        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="translatable" data-key="dashboard">Dashboard</span>
            </a></li>
            <li><a href="dashboard.php?section=members" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'members') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="translatable" data-key="members">Members</span>
            </a></li>
            <li><a href="dashboard.php?section=opinions" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'opinions') ? 'active' : ''; ?>">
                <i class="far fa-comment-dots"></i>
                <span class="translatable" data-key="opinions">Opinions</span>
            </a></li>
            <li><a href="dashboard.php?section=admin_users" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'admin_users') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span class="translatable" data-key="admin_users">Admin Users</span>
            </a></li>
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fas fa-key"></i>
                <span class="translatable" data-key="change_password">Change Password</span>
            </a></li>
        </ul>
        
        <div class="logout-btn" id="logoutBtn" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span class="translatable" data-key="logout">Logout</span>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header fade-in">
            <h1 id="pageTitle">
                <?php 
                $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
                switch($section) {
                    case 'members':
                        echo '<span class="translatable" data-key="members_title">Members</span>';
                        break;
                    case 'opinions':
                        echo '<span class="translatable" data-key="opinions_title">Opinions</span>';
                        break;
                    case 'admin_users':
                        echo '<span class="translatable" data-key="admin_users_title">Admin Users</span>';
                        break;
                    default:
                        echo '<span class="translatable" data-key="dashboard_title">Dashboard</span>';
                        break;
                }
                ?>
            </h1>
            <div class="user-info">
                <div class="dropdown language-dropdown">
                    <button class="btn dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-language"></i>
                        <span id="currentLanguageText">
                            <?php 
                            $languages = [
                                'en' => 'English',
                                'hi' => 'हिन्दी',
                                'es' => 'Español',
                                'fr' => 'Français',
                                'de' => 'Deutsch',
                                'ja' => '日本語'
                            ];
                            echo isset($languages[$current_language]) ? $languages[$current_language] : 'English';
                            ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                        <li><a class="dropdown-item language-option" href="#" data-lang="en"><i class="flag-icon flag-icon-us"></i> English</a></li>
                        <li><a class="dropdown-item language-option" href="#" data-lang="hi"><i class="flag-icon flag-icon-in"></i> हिन्दी</a></li>
                        <li><a class="dropdown-item language-option" href="#" data-lang="es"><i class="flag-icon flag-icon-es"></i> Español</a></li>
                        <li><a class="dropdown-item language-option" href="#" data-lang="fr"><i class="flag-icon flag-icon-fr"></i> Français</a></li>
                        <li><a class="dropdown-item language-option" href="#" data-lang="de"><i class="flag-icon flag-icon-de"></i> Deutsch</a></li>
                        <li><a class="dropdown-item language-option" href="#" data-lang="ja"><i class="flag-icon flag-icon-jp"></i> 日本語</a></li>
                    </ul>
                </div>
                
                <div class="user-avatar">
                    <span id="userInitial"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></span>
                </div>
                <div>
                    <div style="font-weight: 700;" id="userName"><?php echo $admin_name; ?></div>
                    <div style="font-size: 0.85rem; color: #666;" id="userEmail"><?php echo $admin_email; ?></div>
                </div>
            </div>
        </div>
        
        <?php
        $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
        
        if ($section === 'dashboard'): ?>
        <!-- Dashboard Stats -->
        <div class="stats-cards fade-in" id="dashboardStats">
            <div class="stat-card members">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="translatable" data-key="total_members">Total Members</h3>
                <div class="value" id="totalMembers"><?php echo $members_count; ?></div>
            </div>
            <div class="stat-card opinions">
                <div class="icon">
                    <i class="far fa-comment-dots"></i>
                </div>
                <h3 class="translatable" data-key="total_opinions">Total Opinions</h3>
                <div class="value" id="totalOpinions"><?php echo $opinions_count; ?></div>
            </div>
            <div class="stat-card growth">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="translatable" data-key="growth_month">Growth This Month</h3>
                <div class="value" id="growthValue">+<?php echo $growth_percentage; ?>%</div>
            </div>
        </div>
        
        <!-- Members Section -->
        <div class="content-section fade-in" id="membersSection">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-users"></i> <span class="translatable" data-key="all_members">All Members</span></h2>
                <div class="section-subtitle" id="membersCount"><span id="membersCountValue"><?php echo $members_count; ?></span> <span class="translatable" data-key="members_found">members found</span></div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="translatable" data-key="sr_no">SR. NO.</th>
                            <th class="translatable" data-key="name">NAME</th>
                            <th class="translatable" data-key="email">EMAIL</th>
                            <th class="translatable" data-key="phone">PHONE</th>
                            <th class="translatable" data-key="join_date">JOIN DATE</th>
                            <th class="translatable" data-key="gender">GENDER</th>
                        </tr>
                    </thead>
                    <tbody id="membersTableBody">
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
                        <?php 
                        $count++;
                        endwhile; 
                        
                        if ($members_result->num_rows == 0):
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-users fa-2x mb-3" style="color: #ddd;"></i>
                                <div class="translatable" data-key="no_members">No members found</div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="membersPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> <?php echo min($members_count, $members_offset + 1); ?>-<?php echo min($members_count, $members_offset + $members_per_page); ?> <span class="translatable" data-key="of">of</span> <?php echo $members_count; ?>
                </div>
                <div class="pagination-buttons">
                    <a class="page-btn translatable" data-key="previous" <?php echo $members_page <= 1 ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page - 1; ?>" 
                       <?php echo $members_page <= 1 ? 'disabled' : ''; ?>>
                        Previous
                    </a>
                    
                    <?php for ($i = 1; $i <= min(4, $total_members_pages); $i++): ?>
                    <a class="page-btn <?php echo $members_page == $i ? 'active' : ''; ?>" 
                       href="dashboard.php?section=members&members_page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($total_members_pages > 4): ?>
                    <span class="page-btn disabled">...</span>
                    <?php endif; ?>
                    
                    <a class="page-btn translatable" data-key="next" <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page + 1; ?>" 
                       <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?>>
                        Next
                    </a>
                </div>
            </div>
        </div>
        
        <?php elseif ($section === 'members'): ?>
        <!-- Members Section -->
        <div class="content-section fade-in" id="membersSection">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-users"></i> <span class="translatable" data-key="all_members">All Members</span></h2>
                <div class="section-subtitle" id="membersCount"><span id="membersCountValue"><?php echo $members_count; ?></span> <span class="translatable" data-key="members_found">members found</span></div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="translatable" data-key="sr_no">SR. NO.</th>
                            <th class="translatable" data-key="name">NAME</th>
                            <th class="translatable" data-key="email">EMAIL</th>
                            <th class="translatable" data-key="phone">PHONE</th>
                            <th class="translatable" data-key="join_date">JOIN DATE</th>
                            <th class="translatable" data-key="gender">GENDER</th>
                        </tr>
                    </thead>
                    <tbody id="membersTableBody">
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
                        <?php 
                        $count++;
                        endwhile; 
                        
                        if ($members_result->num_rows == 0):
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-users fa-2x mb-3" style="color: #ddd;"></i>
                                <div class="translatable" data-key="no_members">No members found</div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="membersPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> <?php echo min($members_count, $members_offset + 1); ?>-<?php echo min($members_count, $members_offset + $members_per_page); ?> <span class="translatable" data-key="of">of</span> <?php echo $members_count; ?>
                </div>
                <div class="pagination-buttons">
                    <a class="page-btn translatable" data-key="previous" <?php echo $members_page <= 1 ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page - 1; ?>" 
                       <?php echo $members_page <= 1 ? 'disabled' : ''; ?>>
                        Previous
                    </a>
                    
                    <?php for ($i = 1; $i <= min(4, $total_members_pages); $i++): ?>
                    <a class="page-btn <?php echo $members_page == $i ? 'active' : ''; ?>" 
                       href="dashboard.php?section=members&members_page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($total_members_pages > 4): ?>
                    <span class="page-btn disabled">...</span>
                    <?php endif; ?>
                    
                    <a class="page-btn translatable" data-key="next" <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page + 1; ?>" 
                       <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?>>
                        Next
                    </a>
                </div>
            </div>
        </div>
        
        <?php elseif ($section === 'opinions'): ?>
        <!-- Opinions Section -->
        <div class="content-section fade-in" id="opinionsSection">
            <div class="section-header">
                <h2 class="section-title"><i class="far fa-comment-dots"></i> <span class="translatable" data-key="opinions">Opinions</span></h2>
                <div class="section-subtitle" id="opinionsCount"><span id="opinionsCountValue"><?php echo $opinions_count; ?></span> <span class="translatable" data-key="opinions_found">opinions found</span></div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="translatable" data-key="sr_no">SR. NO.</th>
                            <th class="translatable" data-key="name">NAME</th>
                            <th class="translatable" data-key="email">EMAIL</th>
                            <th class="translatable" data-key="phone">PHONE</th>
                            <th class="translatable" data-key="category">CATEGORY</th>
                            <th class="translatable" data-key="date">DATE</th>
                            <th class="translatable" data-key="action">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="opinionsTableBody">
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
                                    <i class="fas fa-eye"></i> <span class="translatable" data-key="view">View</span>
                                </button>
                            </td>
                        </tr>
                        <?php 
                        $count++;
                        endwhile; 
                        
                        if ($opinions_result->num_rows == 0):
                        ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                <i class="far fa-comment-dots fa-2x mb-3" style="color: #ddd;"></i>
                                <div class="translatable" data-key="no_opinions">No opinions found</div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="opinionsPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> <?php echo min($opinions_count, $opinions_offset + 1); ?>-<?php echo min($opinions_count, $opinions_offset + $opinions_per_page); ?> <span class="translatable" data-key="of">of</span> <?php echo $opinions_count; ?>
                </div>
                <div class="pagination-buttons">
                    <a class="page-btn translatable" data-key="previous" <?php echo $opinions_page <= 1 ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=opinions&opinions_page=<?php echo $opinions_page - 1; ?>" 
                       <?php echo $opinions_page <= 1 ? 'disabled' : ''; ?>>
                        Previous
                    </a>
                    
                    <?php for ($i = 1; $i <= min(4, $total_opinions_pages); $i++): ?>
                    <a class="page-btn <?php echo $opinions_page == $i ? 'active' : ''; ?>" 
                       href="dashboard.php?section=opinions&opinions_page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($total_opinions_pages > 4): ?>
                    <span class="page-btn disabled">...</span>
                    <?php endif; ?>
                    
                    <a class="page-btn translatable" data-key="next" <?php echo $opinions_page >= $total_opinions_pages ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=opinions&opinions_page=<?php echo $opinions_page + 1; ?>" 
                       <?php echo $opinions_page >= $total_opinions_pages ? 'disabled' : ''; ?>>
                        Next
                    </a>
                </div>
            </div>
        </div>
        
        <?php elseif ($section === 'admin_users'): ?>
        <!-- Admin Users Section -->
        <div class="content-section fade-in" id="adminUsersSection">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-user-shield"></i> <span class="translatable" data-key="admin_users">Admin Users</span></h2>
                <button class="action-btn btn-create-admin" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                    <i class="fas fa-user-plus"></i> <span class="translatable" data-key="create_admin">Create Admin User</span>
                </button>
            </div>
            
            <div class="admin-users-container" id="adminUsersContainer">
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
                            <label class="translatable" data-key="role">Role</label>
                            <div class="value"><?php echo $role_label; ?></div>
                        </div>
                        <div class="detail-item">
                            <label class="translatable" data-key="email">Email</label>
                            <div class="value"><?php echo htmlspecialchars($admin_user['email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <label class="translatable" data-key="created">Created</label>
                            <div class="value"><?php echo $admin_user['created_by'] ? 'By ' . htmlspecialchars($admin_user['created_by']) : 'System'; ?></div>
                        </div>
                        <div class="detail-item">
                            <label class="translatable" data-key="status">Status</label>
                            <div class="value" style="color: <?php echo $status_color; ?>;"><?php echo ucfirst($admin_user['status']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="adminUsersPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> 1-<?php echo $admin_users_result->num_rows; ?> <span class="translatable" data-key="of">of</span> <?php echo $admin_users_result->num_rows; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Opinion Detail Modal -->
    <div class="modal fade" id="opinionDetailModal" tabindex="-1" aria-labelledby="opinionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="far fa-comment-dots me-2"></i><span class="translatable" data-key="opinion_details">Opinion Details</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="opinionDetailBody">
                    <!-- Opinion details will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i><span class="translatable" data-key="change_password">Change Password</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label"><span class="translatable" data-key="current_password">Current Password</span></label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter current password', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label"><span class="translatable" data-key="new_password">New Password</span></label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter new password', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label"><span class="translatable" data-key="confirm_password">Confirm New Password</span></label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Confirm new password', $current_language)); ?>" required>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-key me-2"></i><span class="translatable" data-key="change_password_btn">Change Password</span>
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
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i><span class="translatable" data-key="create_admin_user">Create Admin User</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createAdminForm" method="POST">
                        <input type="hidden" name="action" value="create_admin">
                        <div class="mb-3">
                            <label for="adminUsername" class="form-label"><span class="translatable" data-key="username">Username</span></label>
                            <input type="text" class="form-control" id="adminUsername" name="username" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter username', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminEmail" class="form-label"><span class="translatable" data-key="email_address">Email Address</span></label>
                            <input type="email" class="form-control" id="adminEmail" name="email" placeholder="admin@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminFullName" class="form-label"><span class="translatable" data-key="full_name">Full Name</span></label>
                            <input type="text" class="form-control" id="adminFullName" name="full_name" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter full name', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label"><span class="translatable" data-key="password">Password</span></label>
                            <input type="password" class="form-control" id="adminPassword" name="password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter password', $current_language)); ?>" required>
                            <div class="form-text translatable" data-key="password_hint">Password must be at least 6 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label for="adminConfirmPassword" class="form-label"><span class="translatable" data-key="confirm_password">Confirm Password</span></label>
                            <input type="password" class="form-control" id="adminConfirmPassword" name="confirm_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Confirm password', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminRole" class="form-label"><span class="translatable" data-key="role">Role</span></label>
                            <select class="form-control" id="adminRole" name="role" required>
                                <option value="admin" class="translatable" data-key="administrator">Administrator</option>
                                <option value="moderator" class="translatable" data-key="moderator">Moderator</option>
                                <option value="viewer" class="translatable" data-key="viewer">Viewer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-user-plus me-2"></i><span class="translatable" data-key="create_admin_user_btn">Create Admin User</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Translation configuration
        const TRANSLATION_CONFIG = {
            apiKey: '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', // Your OpenRouter API Key
            endpoint: 'https://openrouter.ai/api/v1/chat/completions',
            model: 'openai/gpt-3.5-turbo',
            targetLanguage: '<?php echo $current_language; ?>'
        };
        
        // Language names mapping
        const LANGUAGE_NAMES = {
            'en': 'English',
            'hi': 'हिन्दी',
            'es': 'Español',
            'fr': 'Français',
            'de': 'Deutsch',
            'ja': '日本語'
        };
        
        // Store original texts
        let originalTexts = new Map();
        let isTranslating = false;
        
        // Initialize on DOM load
        document.addEventListener('DOMContentLoaded', function() {
            // Store all translatable texts
            storeOriginalTexts();
            
            // If language is not English, translate the page
            if (TRANSLATION_CONFIG.targetLanguage !== 'en') {
                translatePage(TRANSLATION_CONFIG.targetLanguage);
            }
            
            // Setup language switcher
            setupLanguageSwitcher();
            
            // Setup existing functionality
            setupExistingFunctionality();
        });
        
        function storeOriginalTexts() {
            document.querySelectorAll('.translatable').forEach(element => {
                const key = element.getAttribute('data-key') || element.textContent;
                originalTexts.set(element, element.innerHTML);
            });
        }
        
        function setupLanguageSwitcher() {
            document.querySelectorAll('.language-option').forEach(option => {
                option.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const newLanguage = this.getAttribute('data-lang');
                    
                    if (newLanguage !== TRANSLATION_CONFIG.targetLanguage && !isTranslating) {
                        await switchLanguage(newLanguage);
                    }
                });
            });
        }
        
        async function switchLanguage(newLanguage) {
            if (isTranslating) return;
            
            isTranslating = true;
            showLoadingOverlay(LANGUAGE_NAMES[newLanguage] || newLanguage);
            
            try {
                // Update session via AJAX
                await updateSessionLanguage(newLanguage);
                
                // Update target language
                TRANSLATION_CONFIG.targetLanguage = newLanguage;
                
                // Update UI language display
                document.getElementById('currentLanguageText').textContent = LANGUAGE_NAMES[newLanguage] || newLanguage;
                
                // Update body attribute
                document.body.setAttribute('data-language', newLanguage);
                
                // Translate the page
                if (newLanguage === 'en') {
                    // Restore original English texts
                    restoreOriginalTexts();
                    showSuccessMessage('Language switched to English');
                } else {
                    // Translate to new language
                    await translatePage(newLanguage);
                    showSuccessMessage(`Language switched to ${LANGUAGE_NAMES[newLanguage] || newLanguage}`);
                }
            } catch (error) {
                console.error('Language switch error:', error);
                showErrorMessage('Failed to switch language. Please try again.');
            } finally {
                hideLoadingOverlay();
                isTranslating = false;
            }
        }
        
        async function updateSessionLanguage(language) {
            return fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=switch_language&language=${language}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error('Failed to update session');
                }
                return data;
            });
        }
        
        async function translatePage(targetLanguage) {
            // Get all translatable elements
            const translatableElements = Array.from(document.querySelectorAll('.translatable'));
            
            if (translatableElements.length === 0) return;
            
            // Group texts by approximate length to avoid API limits
            const textGroups = groupTextsForTranslation(translatableElements);
            
            // Translate each group
            for (const group of textGroups) {
                await translateTextGroup(group, targetLanguage);
            }
        }
        
        function groupTextsForTranslation(elements) {
            const groups = [];
            let currentGroup = [];
            let currentLength = 0;
            const MAX_GROUP_LENGTH = 2000; // Characters per API call
            
            elements.forEach(element => {
                const text = originalTexts.get(element) || element.textContent;
                const textLength = text.length;
                
                if (currentLength + textLength > MAX_GROUP_LENGTH) {
                    groups.push(currentGroup);
                    currentGroup = [element];
                    currentLength = textLength;
                } else {
                    currentGroup.push(element);
                    currentLength += textLength;
                }
            });
            
            if (currentGroup.length > 0) {
                groups.push(currentGroup);
            }
            
            return groups;
        }
        
        async function translateTextGroup(elements, targetLanguage) {
            // Prepare texts for translation
            const texts = elements.map(element => {
                return originalTexts.get(element) || element.textContent;
            });
            
            try {
                // Call OpenRouter API
                const translatedTexts = await callOpenRouterAPI(texts, 'en', targetLanguage);
                
                // Apply translations to elements
                elements.forEach((element, index) => {
                    if (translatedTexts[index]) {
                        // Preserve HTML structure if original had HTML
                        const original = originalTexts.get(element);
                        if (original && original.includes('<')) {
                            // Simple HTML preservation - replace text nodes
                            element.innerHTML = translatedTexts[index];
                        } else {
                            element.textContent = translatedTexts[index];
                        }
                    }
                });
            } catch (error) {
                console.error('Translation error for group:', error);
                throw error;
            }
        }
        
        async function callOpenRouterAPI(texts, sourceLang, targetLang) {
            // Prepare the prompt
            const prompt = `Translate the following English text(s) to ${LANGUAGE_NAMES[targetLang] || targetLang}. 
            IMPORTANT: Return ONLY the translations in the EXACT SAME ORDER, separated by "|||". 
            Do not add any explanations, notes, or additional text.
            
            Texts to translate:
            ${texts.join(' ||| ')}`;
            
            try {
                const response = await fetch(TRANSLATION_CONFIG.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TRANSLATION_CONFIG.apiKey}`,
                        'HTTP-Referer': window.location.origin,
                        'X-Title': 'Sarvatantra Dashboard'
                    },
                    body: JSON.stringify({
                        model: TRANSLATION_CONFIG.model,
                        messages: [
                            {
                                role: "system",
                                content: "You are a professional translator. Translate exactly what is given, maintaining the same format and order. Return only the translations separated by |||."
                            },
                            {
                                role: "user",
                                content: prompt
                            }
                        ],
                        max_tokens: 4000,
                        temperature: 0.1
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API error (${response.status}): ${errorText}`);
                }
                
                const data = await response.json();
                
                if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                    throw new Error('Invalid API response format');
                }
                
                const translatedText = data.choices[0].message.content.trim();
                
                // Split the response
                const translations = translatedText.split('|||').map(text => text.trim());
                
                if (translations.length !== texts.length) {
                    console.warn(`Translation count mismatch: expected ${texts.length}, got ${translations.length}`);
                }
                
                return translations;
                
            } catch (error) {
                console.error('OpenRouter API call failed:', error);
                throw error;
            }
        }
        
        function restoreOriginalTexts() {
            originalTexts.forEach((text, element) => {
                element.innerHTML = text;
            });
        }
        
        function showLoadingOverlay(languageName) {
            // Remove existing overlay
            hideLoadingOverlay();
            
            const overlay = document.createElement('div');
            overlay.id = 'translationLoading';
            overlay.className = 'translation-loading';
            overlay.innerHTML = `
                <div class="translation-loading-content">
                    <div class="spinner-border text-light mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Switching to ${languageName}...</div>
                    <div class="mt-2" style="font-size: 0.9rem; opacity: 0.8;">Please wait while we translate the content</div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }
        
        function hideLoadingOverlay() {
            const overlay = document.getElementById('translationLoading');
            if (overlay) {
                overlay.remove();
            }
        }
        
        function showSuccessMessage(message) {
            // Remove existing alerts
            document.querySelectorAll('.alert-message').forEach(alert => {
                alert.remove();
            });
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-message show';
            alertDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-2"></i>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.getElementById('alertContainer').appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 3000);
        }
        
        function showErrorMessage(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-message show';
            alertDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-circle me-2"></i>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.getElementById('alertContainer').appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 5000);
        }
        
        function setupExistingFunctionality() {
            // Mobile menu toggle
            document.getElementById('mobileMenuToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const menuToggle = document.getElementById('mobileMenuToggle');
                
                if (window.innerWidth <= 992 && 
                    !sidebar.contains(event.target) && 
                    !menuToggle.contains(event.target) && 
                    sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            });
            
            // Auto remove alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.alert-message').forEach(alert => {
                    alert.classList.remove('show');
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                });
            }, 5000);
        }
        
        // Existing opinion viewing function (keep this as is)
        function viewOpinion(opinionId) {
            const modalBody = document.getElementById('opinionDetailBody');
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="opinion-detail">
                            <label class="translatable" data-key="name">Name</label>
                            <div class="value" id="detailName">Loading...</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="opinion-detail">
                            <label class="translatable" data-key="email">Email</label>
                            <div class="value" id="detailEmail">Loading...</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="opinion-detail">
                            <label class="translatable" data-key="phone">Phone</label>
                            <div class="value" id="detailPhone">Loading...</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="opinion-detail">
                            <label class="translatable" data-key="category">Category</label>
                            <div class="value" id="detailCategory">Loading...</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="opinion-detail">
                            <label class="translatable" data-key="date_submitted">Date Submitted</label>
                            <div class="value" id="detailDate">Loading...</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="opinion-detail">
                            <label class="translatable" data-key="language">Language</label>
                            <div class="value" id="detailLanguage">Loading...</div>
                        </div>
                    </div>
                </div>
                <div class="opinion-detail">
                    <label class="translatable" data-key="opinion">Opinion</label>
                    <div class="value" id="detailOpinion" style="min-height: 100px;">Loading...</div>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('opinionDetailModal'));
            modal.show();
            
            // Store modal elements for translation
            storeOriginalTexts();
            
            // Simulate data loading
            setTimeout(() => {
                document.getElementById('detailName').textContent = 'John Doe';
                document.getElementById('detailEmail').textContent = 'john@email.com';
                document.getElementById('detailPhone').textContent = '+1-234-567-8901';
                document.getElementById('detailCategory').textContent = 'Feedback';
                document.getElementById('detailDate').textContent = new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
                document.getElementById('detailLanguage').textContent = 'English';
                document.getElementById('detailOpinion').textContent = 'This is the detailed opinion text provided by the user. It can be multiple lines long and contain detailed feedback about the service or product.';
            }, 500);
        }
    </script>
</body>
</html>

<?php
// Helper function for server-side placeholder translation (optional)
function translatePlaceholder($text, $language) {
    // Simple placeholder translations - in production, use a proper translation system
    $translations = [
        'en' => [
            'Enter current password' => 'Enter current password',
            'Enter new password' => 'Enter new password',
            'Confirm new password' => 'Confirm new password',
            'Enter username' => 'Enter username',
            'Enter full name' => 'Enter full name',
            'Enter password' => 'Enter password',
            'Confirm password' => 'Confirm password'
        ],
        'hi' => [
            'Enter current password' => 'वर्तमान पासवर्ड दर्ज करें',
            'Enter new password' => 'नया पासवर्ड दर्ज करें',
            'Confirm new password' => 'नए पासवर्ड की पुष्टि करें',
            'Enter username' => 'उपयोगकर्ता नाम दर्ज करें',
            'Enter full name' => 'पूरा नाम दर्ज करें',
            'Enter password' => 'पासवर्ड दर्ज करें',
            'Confirm password' => 'पासवर्ड की पुष्टि करें'
        ],
        'es' => [
            'Enter current password' => 'Ingrese la contraseña actual',
            'Enter new password' => 'Ingrese nueva contraseña',
            'Confirm new password' => 'Confirmar nueva contraseña',
            'Enter username' => 'Ingrese nombre de usuario',
            'Enter full name' => 'Ingrese nombre completo',
            'Enter password' => 'Ingrese contraseña',
            'Confirm password' => 'Confirmar contraseña'
        ],
        'fr' => [
            'Enter current password' => 'Entrez le mot de passe actuel',
            'Enter new password' => 'Entrez un nouveau mot de passe',
            'Confirm new password' => 'Confirmez le nouveau mot de passe',
            'Enter username' => 'Entrez le nom d\'utilisateur',
            'Enter full name' => 'Entrez le nom complet',
            'Enter password' => 'Entrez le mot de passe',
            'Confirm password' => 'Confirmez le mot de passe'
        ]
    ];
    
    return isset($translations[$language][$text]) ? $translations[$language][$text] : $text;
}
?>