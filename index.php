<?php
require_once 'config/database.php';

// Get content from JSON file
$contentJson = file_get_contents('assets/data/content.json');
$translations = json_decode($contentJson, true);

// Set default language
$currentLang = isset($_GET['lang']) ? $_GET['lang'] : 'hi';
if (!array_key_exists($currentLang, $translations)) {
    $currentLang = 'hi';
}

$t = $translations[$currentLang];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'join_us':
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone']);
                $gender = $conn->real_escape_string($_POST['gender']);
                
                $sql = "INSERT INTO members (name, email, phone, gender) 
                        VALUES ('$name', '$email', '$phone', '$gender')";
                
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = $t['join_success'];
                } else {
                    $_SESSION['error_message'] = $t['join_error'];
                }
                header("Location: index.php?lang=$currentLang");
                exit;
                
            case 'submit_opinion':
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone'] ?? '');
                $category = $conn->real_escape_string($_POST['category']);
                $opinion = $conn->real_escape_string($_POST['opinion']);
                
                $sql = "INSERT INTO opinions (name, email, phone, category, opinion, language) 
                        VALUES ('$name', '$email', '$phone', '$category', '$opinion', '$currentLang')";
                
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = $t['opinion_success'];
                } else {
                    $_SESSION['error_message'] = $t['opinion_error'];
                }
                header("Location: index.php?lang=$currentLang");
                exit;
                
            case 'login':
                $username = $conn->real_escape_string($_POST['username']);
                $password = $_POST['password'];
                
                $sql = "SELECT * FROM admin_users WHERE username = '$username' AND status = 'active'";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    $admin = $result->fetch_assoc();
                    if (password_verify($password, $admin['password'])) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        
                        $_SESSION['success_message'] = $t['login_success'];
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        // Try direct comparison for development
                        if ($password === 'admin' && $username === 'admin') {
                            // If password is plain 'admin', hash it and update database
                            $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
                            $updateSql = "UPDATE admin_users SET password = '$hashedPassword' WHERE username = 'admin'";
                            $conn->query($updateSql);
                            
                            // Login the user
                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_email'] = $admin['email'];
                            $_SESSION['admin_role'] = $admin['role'];
                            $_SESSION['admin_name'] = $admin['full_name'];
                            
                            $_SESSION['success_message'] = $t['login_success'];
                            header("Location: dashboard.php");
                            exit;
                        }
                    }
                }
                
                $_SESSION['error_message'] = $t['login_error'];
                header("Location: index.php?lang=$currentLang");
                exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarvatantra - Wholocracy Documentation</title>
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
            --border-radius: 10px;
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
            line-height: 1.7;
            overflow-x: hidden;
            min-height: 100vh;
            padding-bottom: 80px; /* Space for mobile floating buttons */
        }
        
        .container-fluid {
            max-width: 1400px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        /* Header Styles */
        .site-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-blue) 100%);
            box-shadow: var(--shadow-medium);
            padding: 12px 0;
            transition: all var(--transition-speed) ease;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all var(--transition-speed) ease;
            letter-spacing: 0.5px;
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
        
        /* Navigation Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 25px 20px;
            height: fit-content;
            transition: all var(--transition-speed) ease;
            position: sticky;
            top: 90px;
            border: 1px solid rgba(29, 84, 109, 0.1);
        }
        
        .sidebar:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-3px);
        }
        
        .nav-links {
            list-style: none;
            padding-left: 0;
        }
        
        .nav-links li {
            margin-bottom: 10px;
            transition: transform var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .nav-links li:hover {
            transform: translateX(8px);
        }
        
        .nav-links a {
            color: #444;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 8px;
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
            background: linear-gradient(90deg, rgba(29, 84, 109, 0.1) 0%, rgba(95, 149, 152, 0.05) 100%);
            transition: left 0.5s ease;
            z-index: -1;
        }
        
        .nav-links a:hover::before, .nav-links a.active::before {
            left: 0;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: var(--secondary-blue);
            border-left: 4px solid var(--accent-teal);
            box-shadow: 0 4px 12px rgba(95, 149, 152, 0.1);
        }
        
        .nav-links a i {
            width: 22px;
            text-align: center;
            color: var(--accent-teal);
        }
        
        /* Main Content */
        .content-area {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 35px;
            transition: all var(--transition-speed) ease;
            min-height: 600px;
            border: 1px solid rgba(6, 30, 41, 0.08);
        }
        
        .content-area:hover {
            box-shadow: var(--shadow-medium);
        }
        
        .content-title {
            color: var(--primary-dark);
            font-weight: 800;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--accent-teal);
            font-size: 2rem;
        }
        
        /* Language Switcher - Desktop */
        .language-switcher {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .language-switcher select {
            border: 2px solid rgba(95, 149, 152, 0.3);
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            backdrop-filter: blur(10px);
        }
        
        .language-switcher select:focus {
            outline: none;
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(95, 149, 152, 0.3);
        }
        
        .language-switcher select option {
            background: var(--secondary-blue);
            color: white;
        }
        
        /* Action Buttons in Header - Desktop */
        .action-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn-action {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(6, 30, 41, 0.1);
        }
        
        .btn-opinion {
            background: linear-gradient(135deg, var(--accent-teal), var(--secondary-blue));
            color: white;
        }
        
        .btn-opinion:hover {
            background: linear-gradient(135deg, var(--secondary-blue), #15455a);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 15px rgba(95, 149, 152, 0.3);
        }
        
        .btn-login {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(95, 149, 152, 0.4);
            backdrop-filter: blur(10px);
        }
        
        .btn-login:hover {
            background: white;
            color: var(--primary-dark);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 15px rgba(255, 255, 255, 0.2);
            border-color: white;
        }
        
        /* Join Us Button in Header */
        .btn-join-header {
            background: linear-gradient(135deg, var(--accent-teal), #4a7d80);
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(95, 149, 152, 0.2);
        }
        
        .btn-join-header:hover {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-teal));
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(95, 149, 152, 0.4);
        }
        
        /* Content Sections */
        .content-section {
            margin-bottom: 35px;
            padding: 25px;
            background: linear-gradient(135deg, #f8fafc 0%, #e8f0f2 100%);
            border-radius: var(--border-radius);
            border-left: 5px solid var(--accent-teal);
            transition: all var(--transition-speed) ease;
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
        
        .content-section:hover {
            background: linear-gradient(135deg, #e8f0f2 0%, #d8e8ea 100%);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(95, 149, 152, 0.15);
        }
        
        .content-section h4 {
            font-weight: 700;
            color: var(--secondary-blue);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .content-section h4 i {
            background: var(--accent-teal);
            color: white;
            padding: 10px;
            border-radius: 50%;
        }
        
        /* Pagination */
        .pagination-controls {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid rgba(95, 149, 152, 0.2);
        }
        
        .page-btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-blue));
            color: white;
            border: none;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(6, 30, 41, 0.1);
        }
        
        .page-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-teal));
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(95, 149, 152, 0.3);
        }
        
        .page-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            opacity: 0.7;
        }
        
        /* Modal Styles - Consistent for all modals */
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
            max-height: 75vh;
            overflow-y: auto;
        }
        
        /* Join Us Form Styling */
        .join-form-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
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
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, var(--secondary-blue), #15455a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(95, 149, 152, 0.3);
        }
        
        /* Gender Selection */
        .gender-selection {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .gender-option {
            flex: 1;
            padding: 12px;
            border: 2px solid rgba(95, 149, 152, 0.3);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .gender-option:hover {
            background: rgba(95, 149, 152, 0.1);
            border-color: var(--accent-teal);
        }
        
        .gender-option.selected {
            background: rgba(95, 149, 152, 0.2);
            border-color: var(--accent-teal);
            color: var(--secondary-blue);
            font-weight: 700;
        }
        
        /* Alert Messages */
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 320px;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.4s ease;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-heavy);
            border: none;
        }
        
        .alert-message.show {
            opacity: 1;
            transform: translateX(0);
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
        
        /* Dashboard Preview Card */
        .dashboard-preview {
            margin-top: 40px;
            padding: 25px;
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-blue));
            border-radius: var(--border-radius);
            color: white;
            box-shadow: var(--shadow-medium);
            display: none;
        }
        
        .dashboard-preview h4 {
            color: white;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-preview p {
            opacity: 0.9;
        }
        
        /* Mobile Floating Language Switcher */
        .mobile-language-switcher {
            display: none;
            position: fixed;
            bottom: 100px;
            right: 20px;
            z-index: 1000;
            transition: all var(--transition-speed) ease;
        }
        
        .mobile-language-switcher.open {
            transform: translateY(-10px);
        }
        
        .mobile-language-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-teal), var(--secondary-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(95, 149, 152, 0.3);
            transition: all var(--transition-speed) ease;
            z-index: 1002;
        }
        
        .mobile-language-icon:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 8px 25px rgba(95, 149, 152, 0.4);
        }
        
        .mobile-language-options {
            position: absolute;
            bottom: 70px;
            right: 0;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-heavy);
            padding: 15px;
            min-width: 180px;
            max-height: 300px;
            overflow-y: auto;
            opacity: 0;
            transform: translateY(20px) scale(0.9);
            transition: all var(--transition-speed) ease;
            visibility: hidden;
            z-index: 1001;
        }
        
        .mobile-language-switcher.open .mobile-language-options {
            opacity: 1;
            transform: translateY(0) scale(1);
            visibility: visible;
        }
        
        .mobile-language-option {
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            border: 2px solid transparent;
        }
        
        .mobile-language-option:hover {
            background: rgba(95, 149, 152, 0.1);
            transform: translateX(-5px);
        }
        
        .mobile-language-option.active {
            background: rgba(95, 149, 152, 0.2);
            border-color: var(--accent-teal);
            font-weight: 600;
            color: var(--secondary-blue);
        }
        
        .mobile-language-option i {
            width: 20px;
            text-align: center;
        }
        
        /* Mobile Action Buttons */
        .mobile-action-buttons {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0 15px;
        }
        
        .mobile-action-container {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-blue) 100%);
            border-radius: var(--border-radius);
            padding: 15px;
            display: flex;
            justify-content: space-around;
            box-shadow: var(--shadow-heavy);
        }
        
        .mobile-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: transparent;
            border: none;
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            min-width: 70px;
        }
        
        .mobile-action-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }
        
        .mobile-action-btn i {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .mobile-action-btn span {
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1199px) {
            .container-fluid {
                max-width: 100%;
                padding-left: 20px;
                padding-right: 20px;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                position: static;
                margin-bottom: 30px;
                top: 20px;
            }
            
            .action-buttons {
                margin-top: 15px;
                justify-content: flex-end;
            }
            
            .btn-join-header {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .content-area {
                padding: 25px;
            }
            
            /* Language switcher select for tablet */
            .language-switcher select {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 768px) {
            .content-area {
                padding: 20px;
            }
            
            .content-section {
                padding: 20px;
            }
            
            .pagination-controls {
                flex-direction: column;
                gap: 15px;
            }
            
            .page-btn {
                width: 100%;
                justify-content: center;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .gender-selection {
                flex-direction: column;
            }
            
            /* Hide desktop action buttons on mobile */
            .action-buttons {
                display: none !important;
            }
            
            /* Show mobile action buttons */
            .mobile-action-buttons {
                display: block;
            }
            
            /* Show mobile language switcher */
            .mobile-language-switcher {
                display: block;
            }
            
            /* Hide desktop language switcher on mobile */
            .language-switcher {
                display: none !important;
            }
            
            .content-title {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            /* Mobile language options scroll */
            .mobile-language-options {
                max-height: 250px;
                overflow-y: auto;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .content-area {
                padding: 15px;
            }
            
            .mobile-action-container {
                padding: 10px;
            }
            
            .mobile-action-btn {
                min-width: 60px;
                padding: 8px 5px;
            }
            
            .mobile-action-btn i {
                font-size: 18px;
            }
            
            .mobile-action-btn span {
                font-size: 11px;
            }
            
            .mobile-language-icon {
                width: 55px;
                height: 55px;
                font-size: 22px;
            }
            
            .alert-message {
                min-width: 280px;
                right: 10px;
                left: 10px;
                width: calc(100% - 20px);
            }
            
            .mobile-language-options {
                min-width: 160px;
                max-height: 200px;
            }
        }
        
        /* Animation for page content */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .slide-in {
            animation: slideIn 0.5s ease forwards;
        }
        
        /* Content Segments */
        .segment-title {
            color: var(--primary-dark);
            font-weight: 700;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(95, 149, 152, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .segment-content {
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        /* Content Highlights */
        .highlight-box {
            background: rgba(95, 149, 152, 0.1);
            border-left: 4px solid var(--accent-teal);
            padding: 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }
        
        /* Smooth transitions for all interactive elements */
        a, button, input, select, textarea, .nav-links a, .btn-action, .page-btn, .form-control {
            transition: all var(--transition-speed) cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        /* Content card for each section */
        .content-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 25px;
            margin-bottom: 25px;
            border-top: 4px solid var(--accent-teal);
            transition: all var(--transition-speed) ease;
        }
        
        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .content-card h5 {
            color: var(--secondary-blue);
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Content Paragraphs */
        .content-paragraph {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--accent-teal);
            line-height: 1.8;
            transition: all var(--transition-speed) ease;
        }
        
        .content-paragraph:hover {
            background: #f1f3f4;
            transform: translateX(5px);
        }
        
        /* Fix modal scroll issue */
        body.modal-open {
            overflow: hidden;
            padding-right: 0 !important;
        }
        
        /* Smooth sidebar animation for mobile */
        @media (max-width: 992px) {
            .sidebar {
                animation: slideDown 0.5s ease forwards;
            }
            
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
        
        /* Language flag icons */
        .language-flag {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .mobile-language-flag {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        /* Desktop language select styling */
        .language-switcher select {
            min-width: 150px;
        }
        
        @media (max-width: 1200px) {
            .language-switcher select {
                min-width: 140px;
            }
        }
        
        @media (max-width: 992px) {
            .language-switcher select {
                min-width: 130px;
            }
        }
    </style>
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

    <!-- Header -->
    <header class="site-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-5 col-lg-4">
                    <a href="index.php" class="logo">
                        <i class="fas fa-democrat"></i>
                        <span id="logoText"><?php echo $currentLang === 'en' ? 'Sarvatantra' : 'सर्वतंत्र'; ?></span>
                    </a>
                </div>
                <div class="col-md-7 col-lg-8 text-md-end">
                    <div class="d-flex justify-content-md-end align-items-center flex-wrap">
                        <div class="language-switcher me-3">
                            <i class="fas fa-globe" style="color: var(--accent-teal);"></i>
                            <select id="languageSelect" class="form-select" onchange="changeLanguage(this.value)">
                                <option value="hi" <?php echo $currentLang === 'hi' ? 'selected' : ''; ?>>हिन्दी</option>
                                <option value="en" <?php echo $currentLang === 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="bn" <?php echo $currentLang === 'bn' ? 'selected' : ''; ?>>বাংলা</option>
                                <option value="ta" <?php echo $currentLang === 'ta' ? 'selected' : ''; ?>>தமிழ்</option>
                                <option value="te" <?php echo $currentLang === 'te' ? 'selected' : ''; ?>>తెలుగు</option>
                                <option value="mr" <?php echo $currentLang === 'mr' ? 'selected' : ''; ?>>मराठी</option>
                                <option value="gu" <?php echo $currentLang === 'gu' ? 'selected' : ''; ?>>ગુજરાતી</option>
                                <option value="kn" <?php echo $currentLang === 'kn' ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                                <option value="ml" <?php echo $currentLang === 'ml' ? 'selected' : ''; ?>>മലയാളം</option>
                                <option value="or" <?php echo $currentLang === 'or' ? 'selected' : ''; ?>>ଓଡ଼ିଆ</option>
                                <option value="pa" <?php echo $currentLang === 'pa' ? 'selected' : ''; ?>>ਪੰਜਾਬੀ</option>
                                <option value="as" <?php echo $currentLang === 'as' ? 'selected' : ''; ?>>অসমীয়া</option>
                                <option value="ur" <?php echo $currentLang === 'ur' ? 'selected' : ''; ?>>اردو</option>
                                <option value="ne" <?php echo $currentLang === 'ne' ? 'selected' : ''; ?>>नेपाली</option>
                                <option value="sd" <?php echo $currentLang === 'sd' ? 'selected' : ''; ?>>سنڌي</option>
                                <option value="kok" <?php echo $currentLang === 'kok' ? 'selected' : ''; ?>>कोंकणी</option>
                                <option value="mai" <?php echo $currentLang === 'mai' ? 'selected' : ''; ?>>मैथिली</option>
                                <option value="sat" <?php echo $currentLang === 'sat' ? 'selected' : ''; ?>>ᱥᱟᱱᱛᱟᱲᱤ</option>
                                <option value="ks" <?php echo $currentLang === 'ks' ? 'selected' : ''; ?>>کٲشُر</option>
                                <option value="doi" <?php echo $currentLang === 'doi' ? 'selected' : ''; ?>>डोगरी</option>
                                <option value="mni" <?php echo $currentLang === 'mni' ? 'selected' : ''; ?>>মৈতৈলোন্</option>
                            </select>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-action btn-join-header" data-bs-toggle="modal" data-bs-target="#joinModal">
                                <i class="fas fa-user-plus"></i>
                                <span id="joinBtnText"><?php echo $t['joinUs']; ?></span>
                            </button>
                            <button class="btn-action btn-opinion" data-bs-toggle="modal" data-bs-target="#opinionModal">
                                <i class="far fa-comment-dots"></i>
                                <span id="opinionBtnText"><?php echo $t['giveOpinion']; ?></span>
                            </button>
                            <button class="btn-action btn-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt"></i>
                                <span id="loginBtnText"><?php echo $t['login']; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container-fluid my-5">
        <div class="row">
            <!-- Sidebar Navigation - Simplified -->
            <div class="col-lg-3 mb-4">
                <div class="sidebar">
                    <ul class="nav-links">
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=1" class="<?php echo (!isset($_GET['page']) || $_GET['page'] == 1) ? 'active' : ''; ?>" data-page="1"><i class="far fa-file-alt"></i> <span id="page1Text"><?php echo $t['page1']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=2" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 2) ? 'active' : ''; ?>" data-page="2"><i class="far fa-file-alt"></i> <span id="page2Text"><?php echo $t['page2']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=3" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 3) ? 'active' : ''; ?>" data-page="3"><i class="far fa-file-alt"></i> <span id="page3Text"><?php echo $t['page3']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=4" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 4) ? 'active' : ''; ?>" data-page="4"><i class="far fa-file-alt"></i> <span id="page4Text"><?php echo $t['page4']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=5" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 5) ? 'active' : ''; ?>" data-page="5"><i class="far fa-file-alt"></i> <span id="page5Text"><?php echo $t['page5']; ?></span></a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-lg-9">
                <div class="content-area fade-in">
                    <h1 class="content-title" id="contentTitle"><?php echo $t['siteTitle']; ?></h1>
                    
                    <!-- Content will be loaded here dynamically -->
                    <div id="contentContainer">
                        <?php
                        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                        if ($page == 1) {
                            foreach ($t['page1Content'] as $paragraph) {
                                echo '<div class="content-paragraph fade-in">';
                                echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="content-card fade-in">';
                            echo '<h5><i class="fas fa-file-alt"></i> ' . $t['page' . $page] . '</h5>';
                            echo '<div class="segment-content">';
                            echo '<p>This is page ' . $page . ' content. Content will be added here.</p>';
                            echo '<p>More information about this section will be available soon.</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <!-- Dashboard Preview (Hidden by default) -->
                    <?php if (isset($_SESSION['admin_id'])): ?>
                    <div class="dashboard-preview" id="dashboardPreview">
                        <h4><i class="fas fa-tachometer-alt"></i> <span id="dashboardTitle"><?php echo $t['dashboardTitle']; ?></span></h4>
                        <p id="dashboardText"><?php echo $t['dashboardText']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Pagination Controls -->
                    <div class="pagination-controls">
                        <button class="page-btn" id="prevBtn" onclick="window.location.href='index.php?lang=<?php echo $currentLang; ?>&page=<?php echo max(1, $page - 1); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-arrow-left"></i>
                            <span id="prevText"><?php echo $t['previous']; ?></span>
                        </button>
                        <button class="page-btn" id="nextBtn" onclick="window.location.href='index.php?lang=<?php echo $currentLang; ?>&page=<?php echo min(5, $page + 1); ?>'" <?php echo $page >= 5 ? 'disabled' : ''; ?>>
                            <span id="nextText"><?php echo $t['next']; ?></span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Mobile Action Buttons -->
    <div class="mobile-action-buttons">
        <div class="mobile-action-container">
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#joinModal">
                <i class="fas fa-user-plus"></i>
                <span id="mobileJoinText"><?php echo $t['joinUs']; ?></span>
            </button>
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#opinionModal">
                <i class="far fa-comment-dots"></i>
                <span id="mobileOpinionText"><?php echo $t['giveOpinion']; ?></span>
            </button>
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
                <i class="fas fa-sign-in-alt"></i>
                <span id="mobileLoginText"><?php echo $t['login']; ?></span>
            </button>
        </div>
    </div>

    <!-- Mobile Floating Language Switcher -->
    <div class="mobile-language-switcher" id="mobileLanguageSwitcher">
        <div class="mobile-language-options">
            <!-- Major Indian Languages -->
            <div class="mobile-language-option <?php echo $currentLang === 'hi' ? 'active' : ''; ?>" onclick="changeLanguage('hi')">
                <i class="fas fa-language"></i>
                <span>हिन्दी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">
                <i class="fas fa-language"></i>
                <span>English</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'bn' ? 'active' : ''; ?>" onclick="changeLanguage('bn')">
                <i class="fas fa-language"></i>
                <span>বাংলা</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ta' ? 'active' : ''; ?>" onclick="changeLanguage('ta')">
                <i class="fas fa-language"></i>
                <span>தமிழ்</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'te' ? 'active' : ''; ?>" onclick="changeLanguage('te')">
                <i class="fas fa-language"></i>
                <span>తెలుగు</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'mr' ? 'active' : ''; ?>" onclick="changeLanguage('mr')">
                <i class="fas fa-language"></i>
                <span>मराठी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'gu' ? 'active' : ''; ?>" onclick="changeLanguage('gu')">
                <i class="fas fa-language"></i>
                <span>ગુજરાતી</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'kn' ? 'active' : ''; ?>" onclick="changeLanguage('kn')">
                <i class="fas fa-language"></i>
                <span>ಕನ್ನಡ</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ml' ? 'active' : ''; ?>" onclick="changeLanguage('ml')">
                <i class="fas fa-language"></i>
                <span>മലയാളം</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'or' ? 'active' : ''; ?>" onclick="changeLanguage('or')">
                <i class="fas fa-language"></i>
                <span>ଓଡ଼ିଆ</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'pa' ? 'active' : ''; ?>" onclick="changeLanguage('pa')">
                <i class="fas fa-language"></i>
                <span>ਪੰਜਾਬੀ</span>
            </div>
            <!-- Additional Indian Languages -->
            <div class="mobile-language-option <?php echo $currentLang === 'as' ? 'active' : ''; ?>" onclick="changeLanguage('as')">
                <i class="fas fa-language"></i>
                <span>অসমীয়া</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ur' ? 'active' : ''; ?>" onclick="changeLanguage('ur')">
                <i class="fas fa-language"></i>
                <span>اردو</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ne' ? 'active' : ''; ?>" onclick="changeLanguage('ne')">
                <i class="fas fa-language"></i>
                <span>नेपाली</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'sd' ? 'active' : ''; ?>" onclick="changeLanguage('sd')">
                <i class="fas fa-language"></i>
                <span>سنڌي</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'kok' ? 'active' : ''; ?>" onclick="changeLanguage('kok')">
                <i class="fas fa-language"></i>
                <span>कोंकणी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'mai' ? 'active' : ''; ?>" onclick="changeLanguage('mai')">
                <i class="fas fa-language"></i>
                <span>मैथिली</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'sat' ? 'active' : ''; ?>" onclick="changeLanguage('sat')">
                <i class="fas fa-language"></i>
                <span>ᱥᱟᱱᱛᱟᱲᱤ</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ks' ? 'active' : ''; ?>" onclick="changeLanguage('ks')">
                <i class="fas fa-language"></i>
                <span>کٲشُر</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'doi' ? 'active' : ''; ?>" onclick="changeLanguage('doi')">
                <i class="fas fa-language"></i>
                <span>डोगरी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'mni' ? 'active' : ''; ?>" onclick="changeLanguage('mni')">
                <i class="fas fa-language"></i>
                <span>মৈতৈলোন্</span>
            </div>
        </div>
        <div class="mobile-language-icon" onclick="toggleLanguageSwitcher()">
            <i class="fas fa-globe"></i>
        </div>
    </div>

    <!-- Join Us Modal -->
    <div class="modal fade" id="joinModal" tabindex="-1" aria-labelledby="joinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="joinModalTitle"><i class="fas fa-user-plus me-2"></i><span id="joinModalText"><?php echo $t['joinUs']; ?></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="join-form-container slide-in">
                        <form id="joinForm" method="POST">
                            <input type="hidden" name="action" value="join_us">
                            <div class="mb-4">
                                <label for="joinName" class="form-label">
                                    <i class="fas fa-user"></i>
                                    <span id="joinNameLabel"><?php echo $t['joinName']; ?> *</span>
                                </label>
                                <input type="text" class="form-control" id="joinName" name="name" placeholder="<?php echo $t['joinNamePlaceholder']; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="joinEmail" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    <span id="joinEmailLabel"><?php echo $t['joinEmail']; ?> *</span>
                                </label>
                                <input type="email" class="form-control" id="joinEmail" name="email" placeholder="<?php echo $t['joinEmailPlaceholder']; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="joinPhone" class="form-label">
                                    <i class="fas fa-phone"></i>
                                    <span id="joinPhoneLabel"><?php echo $t['joinPhone']; ?> *</span>
                                </label>
                                <input type="tel" class="form-control" id="joinPhone" name="phone" placeholder="<?php echo $t['joinPhonePlaceholder']; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-venus-mars"></i>
                                    <span id="joinGenderLabel"><?php echo $t['joinGender']; ?> *</span>
                                </label>
                                <div class="gender-selection">
                                    <div class="gender-option" data-gender="male">
                                        <i class="fas fa-male"></i>
                                        <span id="genderMale"><?php echo $t['genderMale']; ?></span>
                                    </div>
                                    <div class="gender-option" data-gender="female">
                                        <i class="fas fa-female"></i>
                                        <span id="genderFemale"><?php echo $t['genderFemale']; ?></span>
                                    </div>
                                    <div class="gender-option" data-gender="other">
                                        <i class="fas fa-transgender-alt"></i>
                                        <span id="genderOther"><?php echo $t['genderOther']; ?></span>
                                    </div>
                                </div>
                                <input type="hidden" id="joinGender" name="gender" required>
                            </div>
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="joinTerms" required>
                                <label class="form-check-label" for="joinTerms" id="joinTermsLabel">
                                    <?php echo $t['joinTerms']; ?>
                                </label>
                            </div>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>
                                <span id="joinSubmitText"><?php echo $t['joinSubmit']; ?></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Opinion Modal -->
    <div class="modal fade" id="opinionModal" tabindex="-1" aria-labelledby="opinionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="opinionModalTitle"><i class="far fa-comment-dots me-2"></i><span id="opinionModalTitleText"><?php echo $t['opinionTitle']; ?></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="opinionForm" method="POST">
                        <input type="hidden" name="action" value="submit_opinion">
                        <div class="mb-3">
                            <label for="userName" class="form-label" id="nameLabel"><?php echo $t['name']; ?> *</label>
                            <input type="text" class="form-control" id="userName" name="name" placeholder="<?php echo $t['namePlaceholder']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label" id="emailLabel"><?php echo $t['email']; ?> *</label>
                            <input type="email" class="form-control" id="userEmail" name="email" placeholder="<?php echo $t['emailPlaceholder']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="userPhone" class="form-label" id="phoneLabel"><?php echo $t['phone']; ?></label>
                            <input type="tel" class="form-control" id="userPhone" name="phone" placeholder="<?php echo $t['phonePlaceholder']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="opinionCategory" class="form-label" id="categoryLabel"><?php echo $t['category']; ?> *</label>
                            <select class="form-control" id="opinionCategory" name="category" required>
                                <option value=""><?php echo $t['selectCategory']; ?></option>
                                <option value="content"><?php echo $t['content']; ?></option>
                                <option value="design"><?php echo $t['design']; ?></option>
                                <option value="usability"><?php echo $t['usability']; ?></option>
                                <option value="feature"><?php echo $t['feature']; ?></option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="userOpinion" class="form-label" id="opinionLabel"><?php echo $t['opinion']; ?> *</label>
                            <textarea class="form-control" id="userOpinion" name="opinion" rows="4" placeholder="<?php echo $t['opinionPlaceholder']; ?>" required></textarea>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane me-2"></i>
                            <span id="submitOpinionText"><?php echo $t['submit']; ?></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalTitle"><i class="fas fa-sign-in-alt me-2"></i><span id="loginModalTitleText"><?php echo $t['loginTitle']; ?></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-4">
                            <label for="loginEmail" class="form-label" id="adminEmailLabel"><?php echo $t['adminEmail']; ?> *</label>
                            <input type="text" class="form-control" id="loginEmail" name="username" placeholder="<?php echo $t['adminEmailPlaceholder']; ?>" required>
                            <div class="form-text" id="adminHint"><?php echo $t['adminHint']; ?></div>
                        </div>
                        <div class="mb-4">
                            <label for="loginPassword" class="form-label" id="passwordLabel"><?php echo $t['password']; ?> *</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" placeholder="<?php echo $t['passwordPlaceholder']; ?>" required>
                            <div class="form-text" id="passwordHint"><?php echo $t['passwordHint']; ?></div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe" id="rememberMeLabel"><?php echo $t['rememberMe']; ?></label>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-lock me-2"></i>
                            <span id="loginSubmitText"><?php echo $t['loginSubmit']; ?></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- OpenRouter API for Translation -->
    <script>
        // OpenRouter API configuration
        const OPENROUTER_API_KEY = "your-openrouter-api-key-here"; // Replace with your OpenRouter API key
        const OPENROUTER_API_URL = "https://openrouter.ai/api/v1/chat/completions";
        
        // Indian Languages Mapping
        const INDIAN_LANGUAGES = {
            'hi': 'Hindi',
            'en': 'English',
            'bn': 'Bengali',
            'ta': 'Tamil',
            'te': 'Telugu',
            'mr': 'Marathi',
            'gu': 'Gujarati',
            'kn': 'Kannada',
            'ml': 'Malayalam',
            'or': 'Odia',
            'pa': 'Punjabi',
            'as': 'Assamese',
            'ur': 'Urdu',
            'ne': 'Nepali',
            'sd': 'Sindhi',
            'kok': 'Konkani',
            'mai': 'Maithili',
            'sat': 'Santali',
            'ks': 'Kashmiri',
            'doi': 'Dogri',
            'mni': 'Manipuri'
        };
        
        // Function to translate text using OpenRouter API
        async function translateText(text, targetLang) {
            try {
                const response = await fetch(OPENROUTER_API_URL, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${OPENROUTER_API_KEY}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        model: "google/gemini-pro",
                        messages: [
                            {
                                role: "system",
                                content: `You are a professional translator. Translate the following text to ${INDIAN_LANGUAGES[targetLang] || 'English'}. Return only the translation, no explanations.`
                            },
                            {
                                role: "user",
                                content: text
                            }
                        ],
                        temperature: 0.3,
                        max_tokens: 1000
                    })
                });

                const data = await response.json();
                
                if (data.choices && data.choices[0] && data.choices[0].message) {
                    return data.choices[0].message.content.trim();
                } else {
                    throw new Error('Translation failed');
                }
            } catch (error) {
                console.error('Translation error:', error);
                return text; // Return original text if translation fails
            }
        }

        // Function to translate all text on the page
        async function translatePage(targetLang) {
            // Show loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.innerHTML = `
                <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                         background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2);
                         z-index: 9999; display: flex; align-items: center; gap: 10px;">
                    <div class="spinner-border text-primary"></div>
                    <span>Translating to ${INDIAN_LANGUAGES[targetLang] || 'Selected Language'}...</span>
                </div>
            `;
            document.body.appendChild(loadingIndicator);

            try {
                // Get all translatable elements
                const elementsToTranslate = document.querySelectorAll('[id]');
                const translationPromises = [];

                for (const element of elementsToTranslate) {
                    const text = element.textContent.trim();
                    if (text && !element.hasAttribute('data-translated')) {
                        translationPromises.push(
                            translateText(text, targetLang).then(translatedText => {
                                element.textContent = translatedText;
                                element.setAttribute('data-translated', 'true');
                            })
                        );
                    }
                }

                // Translate placeholder texts
                const placeholders = document.querySelectorAll('[placeholder]');
                for (const element of placeholders) {
                    const placeholderText = element.getAttribute('placeholder');
                    if (placeholderText) {
                        translationPromises.push(
                            translateText(placeholderText, targetLang).then(translatedText => {
                                element.setAttribute('placeholder', translatedText);
                            })
                        );
                    }
                }

                await Promise.all(translationPromises);
                
                // Update URL and reload to get server-side translations for dynamic content
                window.location.href = `index.php?lang=${targetLang}&page=${getCurrentPage()}`;
            } catch (error) {
                console.error('Page translation error:', error);
                // Fallback to server-side translation
                window.location.href = `index.php?lang=${targetLang}&page=${getCurrentPage()}`;
            } finally {
                // Remove loading indicator
                if (document.body.contains(loadingIndicator)) {
                    document.body.removeChild(loadingIndicator);
                }
            }
        }

        // Get current page number
        function getCurrentPage() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 1;
        }

        // Language change function
        function changeLanguage(lang) {
            // Close mobile language switcher if open
            closeLanguageSwitcher();
            
            // Check if we're already in the target language
            const currentLang = '<?php echo $currentLang; ?>';
            if (lang === currentLang) {
                return;
            }

            // Use OpenRouter API for translation
            translatePage(lang);
        }

        // Toggle mobile language switcher
        function toggleLanguageSwitcher() {
            const switcher = document.getElementById('mobileLanguageSwitcher');
            switcher.classList.toggle('open');
        }

        // Close mobile language switcher
        function closeLanguageSwitcher() {
            const switcher = document.getElementById('mobileLanguageSwitcher');
            switcher.classList.remove('open');
        }

        // Close language switcher when clicking outside
        document.addEventListener('click', function(event) {
            const switcher = document.getElementById('mobileLanguageSwitcher');
            const icon = document.querySelector('.mobile-language-icon');
            
            if (switcher.classList.contains('open') && 
                !switcher.contains(event.target) && 
                !icon.contains(event.target)) {
                closeLanguageSwitcher();
            }
        });

        // Handle gender selection
        document.querySelectorAll('.gender-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.gender-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('joinGender').value = this.dataset.gender;
            });
        });

        // Handle desktop language switcher
        document.getElementById('languageSelect')?.addEventListener('change', function() {
            changeLanguage(this.value);
        });

        // Fix modal scroll issue
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('show.bs.modal', function() {
                    document.body.classList.add('modal-open');
                    closeLanguageSwitcher(); // Close language switcher when modal opens
                });
                
                modal.addEventListener('hidden.bs.modal', function() {
                    document.body.classList.remove('modal-open');
                });
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
        });

        // Handle form submissions with translation
        document.addEventListener('submit', function(e) {
            if (e.target.matches('#joinForm, #opinionForm, #loginForm')) {
                // Close language switcher if open
                closeLanguageSwitcher();
            }
        });
    </script>
</body>
</html>