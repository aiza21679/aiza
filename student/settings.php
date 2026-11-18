<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - USTP Jasaan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --ustp-blue: #003366;
            --ustp-light-blue: #0066cc;
            --ustp-gold: #ffcc00;
            --ustp-white: #ffffff;
            --ustp-light-gray: #f5f5f5;
            --ustp-dark-gray: #333333;
            --ustp-green: #28a745;
            --ustp-red: #dc3545;
            --ustp-orange: #fd7e14;
        }

        body {
            background-color: var(--ustp-light-gray);
            color: var(--ustp-dark-gray);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Consistent with Dashboard */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: var(--ustp-white);
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            overflow: hidden;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .logo-container {
            text-align: center;
            width: 100%;
            padding: 0 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px auto;
            border: 4px solid var(--ustp-gold);
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--ustp-white);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logoW h1 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--ustp-white);
        }

        .nav-menu {
            flex: 1;
            width: 100%;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--ustp-white);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .nav-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--ustp-gold);
        }

        .nav-menu a.active {
            background-color: var(--ustp-gold);
            color: black;
            border-left: 4px solid var(--ustp-gold);
        }

        .nav-menu a i {
            width: 20px;
            text-align: center;
        }

        .user-profile {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--ustp-gold);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info h4 {
            font-size: 0.95rem;
            margin-bottom: 2px;
        }

        .user-info p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            bottom: 70px;
            left: 20px;
            background: rgba(0, 51, 102, 0.95);
            border: 1px solid var(--ustp-gold);
            border-radius: 8px;
            overflow: hidden;
            width: 150px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: var(--ustp-white);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .user-dropdown a:hover {
            background: var(--ustp-gold);
            color: var(--ustp-blue);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        /* Header - Consistent with Dashboard */
        .header {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .page-title h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .page-title p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Settings Navigation */
        .settings-nav {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .nav-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-tab {
            padding: 12px 24px;
            background: var(--ustp-light-gray);
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--ustp-dark-gray);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tab.active {
            background: var(--ustp-blue);
            color: var(--ustp-white);
        }

        .nav-tab:hover:not(.active) {
            background: #e0e0e0;
        }

        /* Settings Content */
        .settings-content {
            display: none;
        }

        .settings-content.active {
            display: block;
        }

        .settings-section {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--ustp-light-blue);
            color: var(--ustp-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .section-title {
            font-size: 1.4rem;
            color: var(--ustp-blue);
            font-weight: 600;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--ustp-dark-gray);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: var(--ustp-white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--ustp-light-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--ustp-white);
            cursor: pointer;
        }

        .form-text {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 6px;
        }

        /* Toggle Switch */
        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .toggle-label {
            font-weight: 600;
            color: var(--ustp-dark-gray);
        }

        .toggle-description {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 4px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--ustp-light-blue);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        /* Profile Picture */
        .profile-picture-section {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 25px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--ustp-gold);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: var(--ustp-white);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--ustp-blue);
            border: 2px solid var(--ustp-blue);
        }

        .btn-outline:hover {
            background: var(--ustp-blue);
            color: var(--ustp-white);
        }

        .btn-danger {
            background: #e74c3c;
            color: var(--ustp-white);
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        /* Danger Zone */
        .danger-zone {
            border: 2px solid #e74c3c;
            background: rgba(231, 76, 60, 0.05);
        }

        .danger-zone .section-icon {
            background: #e74c3c;
        }

        .danger-zone .section-title {
            color: #e74c3c;
        }

        .danger-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            border-bottom: 1px solid #ffebee;
        }

        .danger-info h4 {
            color: #e74c3c;
            margin-bottom: 5px;
        }

        .danger-info p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        /* Toast Notifications */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: var(--ustp-white);
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(150%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left: 4px solid #27ae60;
        }

        .toast.error {
            border-left: 4px solid #e74c3c;
        }

        .toast.warning {
            border-left: 4px solid #f39c12;
        }

        .toast-icon {
            font-size: 1.2rem;
        }

        .toast.success .toast-icon {
            color: #27ae60;
        }

        .toast.error .toast-icon {
            color: #e74c3c;
        }

        .toast.warning .toast-icon {
            color: #f39c12;
        }

        .toast-message {
            font-weight: 500;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e74c3c;
            color: var(--ustp-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .modal-title {
            font-size: 1.4rem;
            color: var(--ustp-dark-gray);
            font-weight: 600;
        }

        .modal-body {
            margin-bottom: 25px;
        }

        .modal-body p {
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            border-radius: 2px;
            background: #ecf0f1;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
        }

        .strength-weak {
            background: #e74c3c;
            width: 25%;
        }

        .strength-medium {
            background: #f39c12;
            width: 50%;
        }

        .strength-strong {
            background: #27ae60;
            width: 75%;
        }

        .strength-very-strong {
            background: #2ecc71;
            width: 100%;
        }

        .strength-text {
            font-size: 0.8rem;
            margin-top: 4px;
            font-weight: 500;
        }

        .strength-weak-text { color: #e74c3c; }
        .strength-medium-text { color: #f39c12; }
        .strength-strong-text { color: #27ae60; }
        .strength-very-strong-text { color: #2ecc71; }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .nav-tabs {
                flex-direction: column;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
            width: 100%;
            display: flex;
            flex-direction: column;
        }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
                width: 100%;
            }
            
            .nav-menu a {
                flex-shrink: 0;
                white-space: nowrap;
            }
            
            .user-profile {
                display: none;
            }
            
            .profile-picture-section {
                flex-direction: column;
                text-align: center;
            }
            
            .danger-item {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-actions {
                flex-direction: column;
            }
        }
    </style>

</head>
<body>
    <!-- Sidebar - Consistent with Dashboard -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <img src="../assests/suffage.png" alt="Logo">
            </div>
            <div class="logoW">
               
            </div>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="vote.php"><i class="fas fa-vote-yea"></i><span>Active Elections</span></a>
            <a href="votinghistory.php"><i class="fas fa-history"></i><span>Voting History</span></a>
            <a href="candidates.php"><i class="fas fa-user-friends"></i><span>Candidates</span></a>
            <a href="results.php"><i class="fas fa-chart-bar"></i><span>Results</span></a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i><span>Settings</span></a>
        </div>
<div class="user-profile" onclick="toggleDropdown()">
  <div class="user-avatar">
    <!-- id so JS can update src -->
    <img id="sidebarAvatar" src="../assests/default-avatar.jpg" alt="User Avatar" width="48" height="48">
  </div>
  <div class="user-info">
    <!-- ids so JS can update name & role -->
    <h4 id="sidebarName">Guest</h4>
    <p id="sidebarRole">Student</p>
  </div>
  <div class="user-dropdown" id="dropdown">
    <a href="../login.html">Logout</a>
  </div>
</div>

    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header - Consistent with Dashboard -->
        <div class="header">
            <div class="page-title">
                <h1>Settings</h1>
                <p>The ballot speaks once, but its echo shapes the future.</p>
            </div>
        </div>

        <!-- Settings Navigation -->
        <div class="settings-nav">
            <div class="nav-tabs">
                <button class="nav-tab active" data-tab="profile">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </button>
                <button class="nav-tab" data-tab="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </button>
                <button class="nav-tab" data-tab="privacy">
                    <i class="fas fa-shield-alt"></i>
                    <span>Privacy & Security</span>
                </button>
                <button class="nav-tab" data-tab="preferences">
                    <i class="fas fa-sliders-h"></i>
                    <span>Preferences</span>
                </button>

            </div>
        </div>

        <!-- Profile Settings (ONLY area modified) -->
        <div class="settings-content active" id="profile">
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="section-title">Profile Information</h2>
                </div>

                <div class="profile-picture-section">
                    <div class="profile-avatar">
                        <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" alt="Profile" id="profile-picture">
                    </div>
                    <div class="profile-actions">
                        <button class="btn btn-primary" id="change-photo-btn">
                            <i class="fas fa-camera"></i>
                            <span>Change Photo</span>
                        </button>
                        <button class="btn btn-outline" id="remove-photo-btn">
                            <i class="fas fa-trash"></i>
                            <span>Remove Photo</span>
                        </button>
                        <input type="file" id="photo-upload" accept="image/*" style="display: none;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first-name" value="">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last-name" value="">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" value="">
                    <div class="form-text">Your email address is used for account notifications and recovery.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Student ID</label>
                        <!-- id added so JS can populate it; disabled to make it read-only -->
                        <input type="text" class="form-control" id="student-id" value="" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Program</label>
                        <!-- id added so JS can populate it; disabled to make it read-only -->
                        <input type="text" class="form-control" id="program" value="" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bio</label>
                    <!-- bio shown but disabled (not editable). Hidden input below ensures server receives bio on save -->
                    <textarea class="form-control" id="bio" rows="4" placeholder="Tell us about yourself..." disabled></textarea>
                    <input type="hidden" id="bio-hidden" name="bio" value="">
                </div>

                <div class="action-buttons">
                    <button class="btn btn-outline" id="cancel-profile">Cancel</button>
                    <button class="btn btn-primary" id="save-profile">Save Changes</button>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="settings-content" id="notifications">
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h2 class="section-title">Notification Preferences</h2>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Email Notifications</div>
                        <div class="toggle-description">Receive election updates and important announcements via email</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="email-notifications" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Push Notifications</div>
                        <div class="toggle-description">Get real-time alerts about election activities</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="push-notifications" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Election Reminders</div>
                        <div class="toggle-description">Remind me before elections start and end</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="election-reminders" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Result Announcements</div>
                        <div class="toggle-description">Notify me when election results are published</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="result-announcements" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Candidate Updates</div>
                        <div class="toggle-description">Updates about candidates and their campaigns</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="candidate-updates">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-outline" id="reset-notifications">Reset to Default</button>
                    <button class="btn btn-primary" id="save-notifications">Save Preferences</button>
                </div>
            </div>
        </div>

        <!-- Privacy & Security Settings -->
        <div class="settings-content" id="privacy">
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h2 class="section-title">Privacy & Security</h2>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Two-Factor Authentication</div>
                        <div class="toggle-description">Add an extra layer of security to your account</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="two-factor-auth">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Show Voting History</div>
                        <div class="toggle-description">Allow others to see your voting participation (anonymous)</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="show-voting-history" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Data Collection</div>
                        <div class="toggle-description">Help improve the system by sharing usage data</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="data-collection" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current-password" placeholder="Enter current password">
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new-password" placeholder="Enter new password">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <div class="strength-text" id="password-strength-text"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm-password" placeholder="Confirm new password">
                    <div class="form-text" id="password-match-text"></div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary" id="update-password">Update Security Settings</button>
                </div>
            </div>
        </div>

        <!-- Preferences Settings -->
        <div class="settings-content" id="preferences">
            <div class="settings-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <h2 class="section-title">System Preferences</h2>
                </div>

                <div class="form-group">
                    <label class="form-label">Language</label>
                    <select class="form-select" id="language">
                        <option>English</option>
                        <option>Filipino</option>
                        <option>Cebuano</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Time Zone</label>
                    <select class="form-select" id="timezone">
                        <option>Philippine Standard Time (UTC+8)</option>
                        <option>GMT+8:00</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date Format</label>
                    <select class="form-select" id="date-format">
                        <option>MM/DD/YYYY</option>
                        <option>DD/MM/YYYY</option>
                        <option>YYYY-MM-DD</option>
                    </select>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Dark Mode</div>
                        <div class="toggle-description">Switch to dark theme for better visibility</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="dark-mode">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div class="toggle-info">
                        <div class="toggle-label">Auto-logout</div>
                        <div class="toggle-description">Automatically log out after 30 minutes of inactivity</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="auto-logout" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-outline" id="reset-preferences">Reset Preferences</button>
                    <button class="btn btn-primary" id="save-preferences">Save Settings</button>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="settings-content" id="danger">
            <div class="settings-section danger-zone">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="section-title">Danger Zone</h2>
                </div>

                <div class="danger-item">
                    <div class="danger-info">
                        <h4>Clear Voting History</h4>
                        <p>Permanently delete your voting history from our records. This action cannot be undone.</p>
                    </div>
                    <button class="btn btn-outline" id="clear-history-btn">
                        <i class="fas fa-trash"></i>
                        <span>Clear History</span>
                    </button>
                </div>

                <div class="danger-item">
                    <div class="danger-info">
                        <h4>Deactivate Account</h4>
                        <p>Temporarily deactivate your account. You can reactivate it anytime by logging back in.</p>
                    </div>
                    <button class="btn btn-outline" id="deactivate-account-btn">
                        <i class="fas fa-user-slash"></i>
                        <span>Deactivate Account</span>
                    </button>
                </div>

                <div class="danger-item">
                    <div class="danger-info">
                        <h4>Delete Account</h4>
                        <p>Permanently delete your account and all associated data. This action is irreversible.</p>
                    </div>
                    <button class="btn btn-danger" id="delete-account-btn">
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete Account</span>
                    </button>
                </div>
            </div>
        </div>

        <footer>
            <p>© 2025 eBOTO | USTP Jasaan | All Rights Reserved</p>
        </footer>
    </main>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-message" id="toast-message">Settings saved successfully!</div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="modal-title" id="modal-title">Confirm Action</h3>
            </div>
            <div class="modal-body">
                <p id="modal-message">Are you sure you want to perform this action? This cannot be undone.</p>
            </div>
            <div class="modal-actions">
                <button class="btn btn-outline" id="modal-cancel">Cancel</button>
                <button class="btn btn-danger" id="modal-confirm">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Global state to track changes
        const settingsState = {
            profile: {
                firstName: 'Aiza',
                lastName: 'Montebon',
                email: 'aiza.montebon@ustp.edu.ph',
                bio: 'Active student leader passionate about campus development and student welfare.',
                profilePicture: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80'
            },
            notifications: {
                email: true,
                push: true,
                reminders: true,
                results: true,
                candidates: false
            },
            privacy: {
                twoFactor: false,
                showHistory: true,
                dataCollection: true
            },
            preferences: {
                language: 'English',
                timezone: 'Philippine Standard Time (UTC+8)',
                dateFormat: 'MM/DD/YYYY',
                darkMode: false,
                autoLogout: true
            }
        };

        // DOM Elements
        const tabs = document.querySelectorAll('.nav-tab');
        const contents = document.querySelectorAll('.settings-content');
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        const modal = document.getElementById('confirmation-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalCancel = document.getElementById('modal-cancel');
        const modalConfirm = document.getElementById('modal-confirm');

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            initializeTabs();
            initializeProfileSection();
            initializeNotificationSection();
            initializePrivacySection();
            initializePreferencesSection();
            initializeDangerZone();
            loadSettings();
        });

        function initializeTabs() {
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });
        }

        /* ====== Student profile loader (unchanged aside from not duplicating profile JS below) ====== */
        // NOTE: This function is left as a tolerant loader if your project uses it elsewhere.
       function loadStudentProfile() {
  // Use absolute path to the working endpoint you opened in the browser.
  // Adjust if your project is at a different base path.
  const url = `${location.origin}/eBotoni/eBotoni/student/getStudent.php`;

  fetch(url, { cache: 'no-store', credentials: 'same-origin' })
    .then(r => r.text())
    .then(text => {
      if (!text) {
        console.warn('getStudent returned empty response');
        return;
      }

      // if server accidentally returned HTML (PHP warning), warn and bail
      if (text.trim().startsWith('<')) {
        console.warn('getStudent returned HTML (check PHP warnings):', text.slice(0,400));
        return;
      }

      let data;
      try {
        data = JSON.parse(text);
      } catch (err) {
        console.warn('getStudent returned non-JSON:', text.slice(0,400));
        return;
      }

      // DEBUG: show exactly what came from server
      console.log('getStudent response:', data);

      // tolerance for many shapes
      let student = data.student ?? data.data?.student ?? data.data ?? null;
      if (!student) {
        if (data.Name || data.Student_ID || data.email || data.id) student = data;
        else {
          const maybe = Object.values(data).find(v => v && typeof v === 'object' && (v.Name || v.Student_ID || v.email || v.id));
          student = maybe ?? null;
        }
      }

      if (!student) {
        console.warn('Student not found in getStudent response', data);
        return;
      }

      // populate sidebar avatar, name, role
      const userAvatarEl = document.getElementById('sidebarAvatar');
      const userNameEl = document.getElementById('sidebarName');
      const userRoleEl = document.getElementById('sidebarRole');

      // avatar resolution (handles relative paths and absolute urls)
      const photo = student.profile_photo_url ?? student.profile_photo ?? student.avatar ?? student.Avatar ?? '';
      const avatarUrl = (photo && String(photo).trim())
        ? (/^https?:\/\//i.test(photo) ? photo : (location.origin + '/' + String(photo).replace(/^\/+/, '')))   
        : '../assests/default-avatar.jpg';
        const avatarFull = (/^https?:\/\//i.test(path)) ? path : (location.origin + '/' + path.replace(/^\/+/, ''));


      if (userAvatarEl && userAvatarEl.tagName.toLowerCase() === 'img') userAvatarEl.src = avatarUrl;
      if (userNameEl) userNameEl.textContent = (student.Name ?? student.name ?? student.FullName ?? '') || 'Student';
      if (userRoleEl) userRoleEl.textContent = student.role ?? student.Role ?? 'Student';

      // Fill profile inputs if present
      const firstNameEl = document.getElementById('first-name');
      const lastNameEl  = document.getElementById('last-name');
      const emailEl     = document.getElementById('email');
      const bioEl       = document.getElementById('bio');
      const bioHiddenEl = document.getElementById('bio-hidden');
      const studentIdEl = document.getElementById('student-id');
      const programEl   = document.getElementById('program');
      const profilePic  = document.getElementById('profile-picture');

      // name parsing tolerant
      let first = student.firstName ?? student.firstname ?? '';
      let last  = student.lastName ?? student.lastname ?? '';
      if (!first && !last) {
        const full = student.Name ?? student.name ?? student.FullName ?? '';
        const parts = String(full).trim().split(' ').filter(Boolean);
        first = parts.shift() ?? '';
        last = parts.join(' ') ?? '';
      }

      const email = student.Email ?? student.email ?? '';
      const bio   = student.bio ?? student.Bio ?? '';
      const program = student.Course ?? student.course ?? student.program ?? '';

      // tolerate multiple id keys
      const sid = student.Student_ID ?? student.StudentID ?? student.student_id ?? student.id ?? '';

      if (firstNameEl) firstNameEl.value = first;
      if (lastNameEl) lastNameEl.value = last;
      if (emailEl) emailEl.value = email;
      if (bioEl) bioEl.value = bio;
      if (bioHiddenEl) bioHiddenEl.value = bio;
      if (studentIdEl) studentIdEl.value = sid;
      if (programEl) programEl.value = program;
      if (profilePic && profilePic.tagName.toLowerCase() === 'img') profilePic.src = avatarUrl;

      // expose for other scripts
      window.CURRENT_USER = window.CURRENT_USER || {};
      window.CURRENT_USER.id = sid || window.CURRENT_USER.id || null;
    })
    .catch(err => {
      console.error('getStudent fetch error:', err);
    });
}

        function initializeProfileSection() {
            const changePhotoBtn = document.getElementById('change-photo-btn');
            const removePhotoBtn = document.getElementById('remove-photo-btn');
            const photoUpload = document.getElementById('photo-upload');
            const saveProfileBtn = document.getElementById('save-profile');
            const cancelProfileBtn = document.getElementById('cancel-profile');

            changePhotoBtn.addEventListener('click', () => photoUpload.click());
            
            photoUpload.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('profile-picture').src = e.target.result;
                        settingsState.profile.profilePicture = e.target.result;
                        showToast('Profile picture updated successfully!', 'success');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });

            removePhotoBtn.addEventListener('click', function() {
                const defaultPhoto = 'https://via.placeholder.com/200/003366/FFFFFF?text=AM';
                document.getElementById('profile-picture').src = defaultPhoto;
                settingsState.profile.profilePicture = defaultPhoto;
                showToast('Profile picture removed successfully!', 'success');
            });

            saveProfileBtn.addEventListener('click', function() {
                // Only editable fields are updated from inputs
                settingsState.profile.firstName = document.getElementById('first-name').value;
                settingsState.profile.lastName = document.getElementById('last-name').value;
                settingsState.profile.email = document.getElementById('email').value;
                // bio is display-only; we keep the value from hidden field or saved state
                const hiddenBio = document.getElementById('bio-hidden');
                if (hiddenBio && hiddenBio.value) settingsState.profile.bio = hiddenBio.value;

                showToast('Profile updated locally (client). Saving to server...', 'success');
                // Save locally and call server save (profile JS below will handle actual server call)
                saveSettings();
                // call server save (non-blocking; server save logic included later)
                if (typeof saveProfileToServer === 'function') {
                    saveProfileToServer();
                }
            });

            cancelProfileBtn.addEventListener('click', function() {
                document.getElementById('first-name').value = settingsState.profile.firstName;
                document.getElementById('last-name').value = settingsState.profile.lastName;
                document.getElementById('email').value = settingsState.profile.email;
                document.getElementById('bio').value = settingsState.profile.bio;
                showToast('Changes discarded', 'warning');
            });
        }

        function initializeNotificationSection() {
            const saveNotificationsBtn = document.getElementById('save-notifications');
            const resetNotificationsBtn = document.getElementById('reset-notifications');

            saveNotificationsBtn.addEventListener('click', function() {
                settingsState.notifications.email = document.getElementById('email-notifications').checked;
                settingsState.notifications.push = document.getElementById('push-notifications').checked;
                settingsState.notifications.reminders = document.getElementById('election-reminders').checked;
                settingsState.notifications.results = document.getElementById('result-announcements').checked;
                settingsState.notifications.candidates = document.getElementById('candidate-updates').checked;
                
                showToast('Notification preferences saved!', 'success');
                saveSettings();
            });

            resetNotificationsBtn.addEventListener('click', function() {
                document.getElementById('email-notifications').checked = true;
                document.getElementById('push-notifications').checked = true;
                document.getElementById('election-reminders').checked = true;
                document.getElementById('result-announcements').checked = true;
                document.getElementById('candidate-updates').checked = false;
                showToast('Notifications reset to default', 'success');
            });
        }

        function initializePrivacySection() {
            const updatePasswordBtn = document.getElementById('update-password');
            const newPasswordInput = document.getElementById('new-password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            const currentPasswordInput = document.getElementById('current-password');

            // Password strength checker
            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    checkPasswordMatch();
                });
            }
            if (confirmPasswordInput) confirmPasswordInput.addEventListener('input', checkPasswordMatch);

            updatePasswordBtn.addEventListener('click', function() {
                const currentPassword = currentPasswordInput.value;
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (!currentPassword) {
                    showToast('Please enter your current password', 'error');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showToast('New passwords do not match', 'error');
                    return;
                }

                if (newPassword.length < 8) {
                    showToast('Password must be at least 8 characters long', 'error');
                    return;
                }

                // In a real app, you would send this to your backend
                showToast('Password updated successfully!', 'success');
                currentPasswordInput.value = '';
                newPasswordInput.value = '';
                confirmPasswordInput.value = '';
                const strengthBar = document.getElementById('password-strength-bar');
                if (strengthBar) strengthBar.className = 'password-strength-bar';
                const strengthText = document.getElementById('password-strength-text');
                if (strengthText) strengthText.textContent = '';
                const matchText = document.getElementById('password-match-text');
                if (matchText) matchText.textContent = '';
            });

            // Update privacy toggles
            const twoFactor = document.getElementById('two-factor-auth');
            const showHistory = document.getElementById('show-voting-history');
            const dataCollection = document.getElementById('data-collection');

            if (twoFactor) twoFactor.addEventListener('change', function() {
                settingsState.privacy.twoFactor = this.checked;
                saveSettings();
            });
            if (showHistory) showHistory.addEventListener('change', function() {
                settingsState.privacy.showHistory = this.checked;
                saveSettings();
            });
            if (dataCollection) dataCollection.addEventListener('change', function() {
                settingsState.privacy.dataCollection = this.checked;
                saveSettings();
            });
        }

        function initializePreferencesSection() {
            const savePreferencesBtn = document.getElementById('save-preferences');
            const resetPreferencesBtn = document.getElementById('reset-preferences');

            savePreferencesBtn.addEventListener('click', function() {
                settingsState.preferences.language = document.getElementById('language').value;
                settingsState.preferences.timezone = document.getElementById('timezone').value;
                settingsState.preferences.dateFormat = document.getElementById('date-format').value;
                settingsState.preferences.darkMode = document.getElementById('dark-mode').checked;
                settingsState.preferences.autoLogout = document.getElementById('auto-logout').checked;
                
                showToast('Preferences saved successfully!', 'success');
                saveSettings();
            });

            resetPreferencesBtn.addEventListener('click', function() {
                document.getElementById('language').value = 'English';
                document.getElementById('timezone').value = 'Philippine Standard Time (UTC+8)';
                document.getElementById('date-format').value = 'MM/DD/YYYY';
                document.getElementById('dark-mode').checked = false;
                document.getElementById('auto-logout').checked = true;
                showToast('Preferences reset to default', 'success');
            });
        }

        function initializeDangerZone() {
            const clearHistoryBtn = document.getElementById('clear-history-btn');
            const deactivateAccountBtn = document.getElementById('deactivate-account-btn');
            const deleteAccountBtn = document.getElementById('delete-account-btn');

            clearHistoryBtn.addEventListener('click', function() {
                showConfirmationModal(
                    'Clear Voting History',
                    'Are you sure you want to permanently delete your voting history? This action cannot be undone.',
                    function() {
                        // In a real app, this would call an API
                        showToast('Voting history cleared successfully', 'success');
                    }
                );
            });

            deactivateAccountBtn.addEventListener('click', function() {
                showConfirmationModal(
                    'Deactivate Account',
                    'Your account will be temporarily deactivated. You can reactivate it by logging back in.',
                    function() {
                        // In a real app, this would call an API
                        showToast('Account deactivated successfully', 'success');
                    }
                );
            });

            deleteAccountBtn.addEventListener('click', function() {
                showConfirmationModal(
                    'Delete Account',
                    'This will permanently delete your account and all associated data. This action is irreversible!',
                    function() {
                        // In a real app, this would call an API
                        showToast('Account deleted successfully', 'success');
                        setTimeout(() => {
                            window.location.href = '../login.html';
                        }, 2000);
                    }
                );
            });
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            let strength = 0;
            let text = '';
            let className = '';

            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    text = 'Weak';
                    className = 'strength-weak';
                    break;
                case 2:
                    text = 'Medium';
                    className = 'strength-medium';
                    break;
                case 3:
                    text = 'Strong';
                    className = 'strength-strong';
                    break;
                case 4:
                    text = 'Very Strong';
                    className = 'strength-very-strong';
                    break;
            }

            if (strengthBar) strengthBar.className = `password-strength-bar ${className}`;
            if (strengthText) { strengthText.textContent = text; strengthText.className = `strength-text ${className}-text`; }
        }

        function checkPasswordMatch() {
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const matchText = document.getElementById('password-match-text');

            if (!newPassword || !confirmPassword) {
                if (matchText) matchText.textContent = '';
                return;
            }

            if (newPassword === confirmPassword) {
                matchText.textContent = 'Passwords match';
                matchText.style.color = '#27ae60';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.style.color = '#e74c3c';
            }
        }

        function showConfirmationModal(title, message, confirmCallback) {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modal.classList.add('show');

            const confirmHandler = function() {
                confirmCallback();
                modal.classList.remove('show');
                modalConfirm.removeEventListener('click', confirmHandler);
            };

            modalConfirm.addEventListener('click', confirmHandler);
            modalCancel.addEventListener('click', function() {
                modal.classList.remove('show');
                modalConfirm.removeEventListener('click', confirmHandler);
            });
        }

        function showToast(message, type = 'success') {
            toastMessage.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function loadSettings() {
            // Load from localStorage or use default state
            const savedSettings = localStorage.getItem('electionSettings');
            if (savedSettings) {
                Object.assign(settingsState, JSON.parse(savedSettings));
            }

            // Apply settings to UI
            document.getElementById('first-name').value = settingsState.profile.firstName;
            document.getElementById('last-name').value = settingsState.profile.lastName;
            document.getElementById('email').value = settingsState.profile.email;
            document.getElementById('bio').value = settingsState.profile.bio;
            document.getElementById('profile-picture').src = settingsState.profile.profilePicture;

            document.getElementById('email-notifications').checked = settingsState.notifications.email;
            document.getElementById('push-notifications').checked = settingsState.notifications.push;
            document.getElementById('election-reminders').checked = settingsState.notifications.reminders;
            document.getElementById('result-announcements').checked = settingsState.notifications.results;
            document.getElementById('candidate-updates').checked = settingsState.notifications.candidates;

            document.getElementById('two-factor-auth').checked = settingsState.privacy.twoFactor;
            document.getElementById('show-voting-history').checked = settingsState.privacy.showHistory;
            document.getElementById('data-collection').checked = settingsState.privacy.dataCollection;

            document.getElementById('language').value = settingsState.preferences.language;
            document.getElementById('timezone').value = settingsState.preferences.timezone;
            document.getElementById('date-format').value = settingsState.preferences.dateFormat;
            document.getElementById('dark-mode').checked = settingsState.preferences.darkMode;
            document.getElementById('auto-logout').checked = settingsState.preferences.autoLogout;
        }

        function saveSettings() {
            localStorage.setItem('electionSettings', JSON.stringify(settingsState));
        }

        function toggleDropdown(){
            const dropdown = document.getElementById('dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
        
        document.addEventListener('click', function(e){
            const profile = document.querySelector('.user-profile');
            const dropdown = document.getElementById('dropdown');
            if (!profile.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>

    <!-- PROFILE-only JS: loads student and saves profile to server (only touches Profile behavior) -->
 <script>
/* Consolidated settings + profile JS
   - Replaces the previous two script blocks
   - Only changes behavior for Profile area (loads student, fills fields, saves)
   - Keeps other settings behavior intact
*/

(() => {
  // -----------------------
  // Config / state
  // -----------------------
  // Use the same path you opened successfully in the browser.
  // Adjust if your project root differs.
  const GET_STUDENT_API = `${location.origin}/eBotoni/eBotoni/student/getStudent.php`;
const SAVE_PROFILE_API = `${location.origin}/eBotoni/eBotoni/student/save_profile.php`;
  const DEFAULT_AVATAR = '../assests/default-avatar.jpg';

  const settingsState = {
    profile: {
      firstName: 'Aiza',
      lastName: 'Montebon',
      email: 'aiza.montebon@ustp.edu.ph',
      bio: 'Active student leader passionate about campus development and student welfare.',
      profilePicture: 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80'
    },
    notifications: {
      email: true,
      push: true,
      reminders: true,
      results: true,
      candidates: false
    },
    privacy: {
      twoFactor: false,
      showHistory: true,
      dataCollection: true
    },
    preferences: {
      language: 'English',
      timezone: 'Philippine Standard Time (UTC+8)',
      dateFormat: 'MM/DD/YYYY',
      darkMode: false,
      autoLogout: true
    }
  };

  // -----------------------
  // DOM refs (global)
  // -----------------------
  const tabs = document.querySelectorAll('.nav-tab');
  const contents = document.querySelectorAll('.settings-content');
  const toast = document.getElementById('toast');
  const toastMessage = document.getElementById('toast-message');
  const modal = document.getElementById('confirmation-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalMessage = document.getElementById('modal-message');
  const modalCancel = document.getElementById('modal-cancel');
  const modalConfirm = document.getElementById('modal-confirm');

  // Profile-specific DOM refs
  const profilePic = document.getElementById('profile-picture');
  const sidebarAvatar = document.getElementById('sidebarAvatar');
  const sidebarName = document.getElementById('sidebarName');
  const sidebarRole = document.getElementById('sidebarRole');

  const firstNameEl = document.getElementById('first-name');
  const lastNameEl  = document.getElementById('last-name');
  const emailEl     = document.getElementById('email');
  const bioEl       = document.getElementById('bio');        // disabled textarea shown in UI
  const bioHiddenEl = document.getElementById('bio-hidden');// hidden input to send bio to server
  const studentIdEl = document.getElementById('student-id');
  const programEl   = document.getElementById('program');

  const photoUploadEl = document.getElementById('photo-upload');
  const changePhotoBtn = document.getElementById('change-photo-btn');
  const removePhotoBtn = document.getElementById('remove-photo-btn');
  const saveProfileBtn = document.getElementById('save-profile');
  const cancelProfileBtn = document.getElementById('cancel-profile');

  // -----------------------
  // Helpers
  // -----------------------
  function showToast(message, type = 'success') {
    if (!toast || !toastMessage) {
      console.log(`[toast:${type}]`, message);
      return;
    }
    toastMessage.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }

  function resolveAvatarUrl(raw) {
    if (!raw) return DEFAULT_AVATAR;
    const s = String(raw).trim();
    if (!s) return DEFAULT_AVATAR;
    if (/^https?:\/\//i.test(s)) return s;
    const cleaned = s.replace(/^\/+/, '');
    const base = window.location.origin;
    return base + '/' + cleaned;
  }

  function extractStudentId(student) {
    return student?.Student_ID ?? student?.StudentID ?? student?.student_id ?? student?.id ?? null;
  }

  // -----------------------
  // Populate profile UI + sync state
  // -----------------------
  function populateProfile(student) {
    if (!student) return;

    // name parsing (tolerant)
    let first = student.firstName ?? student.firstname ?? '';
    let last  = student.lastName  ?? student.lastname  ?? '';
    if (!first && !last) {
      const full = student.Name ?? student.name ?? student.FullName ?? student.fullname ?? '';
      const parts = String(full).trim().split(' ').filter(Boolean);
      first = parts.shift() ?? '';
      last = parts.join(' ') ?? '';
    }

    const email = student.Email ?? student.email ?? '';
    const bio   = student.bio ?? student.Bio ?? '';
    const sid   = extractStudentId(student) ?? '';
   const program = student.Course ?? student.course ?? student.program ?? '';

    // fill UI
    
    if (firstNameEl) firstNameEl.value = first;
    if (lastNameEl) lastNameEl.value = last;
    if (emailEl) emailEl.value = email;
    if (bioEl) bioEl.value = bio;
    if (bioHiddenEl) bioHiddenEl.value = bio; // ensure server receives existing bio on save
    if (studentIdEl) studentIdEl.value = sid;
    if (programEl) programEl.value = program;

    // update local settingsState so other UI uses same data
    settingsState.profile.firstName = first || settingsState.profile.firstName;
    settingsState.profile.lastName  = last  || settingsState.profile.lastName;
    settingsState.profile.email     = email || settingsState.profile.email;
    settingsState.profile.bio       = bio   || settingsState.profile.bio;

    // avatar
    const rawPhoto = student.profile_photo_url ?? student.profile_photo ?? student.avatar ?? student.Avatar ?? '';
    const avatarUrl = resolveAvatarUrl(rawPhoto) || DEFAULT_AVATAR;
    if (profilePic && profilePic.tagName.toLowerCase() === 'img') profilePic.src = avatarUrl;
    if (sidebarAvatar && sidebarAvatar.tagName.toLowerCase() === 'img') sidebarAvatar.src = avatarUrl;

    // sidebar name and role
    const displayName = ((first + ' ' + last).trim()) || (student.Name ?? student.name ?? 'Student');
    if (sidebarName) sidebarName.textContent = displayName;
    if (sidebarRole) sidebarRole.textContent = student.role ?? student.Role ?? 'Student';

    // expose current user id for other code if needed
    window.CURRENT_USER = window.CURRENT_USER || {};
    window.CURRENT_USER.id = sid || window.CURRENT_USER.id || null;
  }

  // -----------------------
  // Load student from server
  // -----------------------
  async function loadStudentProfile() {
    try {
      const res = await fetch(GET_STUDENT_API, { cache: 'no-store', credentials: 'same-origin' });
      if (!res.ok) {
        console.warn('GET student returned', res.status);
        return;
      }
      const text = (await res.text()).trim();
      if (!text) return;
      if (text.startsWith('<')) {
        console.warn('getStudent returned HTML (check PHP warnings):', text.slice(0,400));
        return;
      }

      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.warn('Invalid JSON from getStudent:', text.slice(0,400));
        return;
      }

      // tolerate multiple shapes
      let student = data.student ?? data.data?.student ?? data.data ?? null;
      if (!student) {
        // maybe top-level fields
        if (data.Name || data.Student_ID || data.email || data.id) student = data;
        else {
          const maybe = Object.values(data).find(v => v && typeof v === 'object' && (v.Name || v.Student_ID || v.email || v.id));
          student = maybe ?? null;
        }
      }

      if (!student) {
        console.warn('No student object found in getStudent response', data);
        return;
      }

      populateProfile(student);
    } catch (err) {
      console.error('loadStudentProfile error', err);
    }
  }

  // -----------------------
  // Save profile to server
  // -----------------------
 async function saveProfileToServer() {
  const first = firstNameEl?.value.trim() ?? '';
  const last  = lastNameEl?.value.trim() ?? '';
  const email = emailEl?.value.trim() ?? '';
  const bio   = bioHiddenEl?.value ?? '';

  if (!first || !last || !email) {
    showToast('First, Last and Email are required', 'error');
    return;
  }

  const fd = new FormData();
  fd.append('firstName', first);
  fd.append('lastName', last);
  fd.append('email', email);
  fd.append('bio', bio);

  if (photoUploadEl && photoUploadEl.files && photoUploadEl.files[0]) {
    fd.append('photo', photoUploadEl.files[0]);
  }

  try {
    console.info('Saving profile to', SAVE_PROFILE_API);
    const resp = await fetch(SAVE_PROFILE_API, {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    });

    // Always capture raw text to help debugging (server might return HTML on error)
    const text = (await resp.text()).trim();
    console.group('save_profile response');
    console.log('HTTP status:', resp.status);
    console.log('Raw response (first 2000 chars):', text.slice(0,2000));
    console.groupEnd();

    if (!resp.ok) {
      showToast('Server returned HTTP ' + resp.status, 'error');
      return;
    }

    if (!text) {
      showToast('Empty response from server', 'error');
      return;
    }

    // if server returned HTML (usually an error), show debug message
    if (text.startsWith('<')) {
      console.warn('save_profile returned HTML (likely PHP error or 404). Inspect server logs.');
      showToast('Server error while saving (check PHP logs)', 'error');
      return;
    }

    const json = JSON.parse(text);
    if (!json || !json.success) {
      const err = (json && (json.error || json.message)) ? (json.error || json.message) : 'Failed to save profile';
      showToast(err, 'error');
      console.error('save_profile response object:', json);
      return;
    }

    // success: update UI
    if (json.profile_photo_url) {
      const resolved = resolveAvatarUrl(json.profile_photo_url);
      if (profilePic) profilePic.src = resolved;
      if (sidebarAvatar) sidebarAvatar.src = resolved;
    }

    const updatedStudent = json.student ?? json.data?.student ?? json;
    if (updatedStudent) populateProfile(updatedStudent);

    showToast('Profile updated', 'success');

    // persist to local state
    settingsState.profile.firstName = first;
    settingsState.profile.lastName  = last;
    settingsState.profile.email     = email;
    settingsState.profile.bio       = bio;
    saveSettings();

  } catch (err) {
    console.error('saveProfileToServer error', err);
    showToast('Network/server error while saving profile', 'error');
  }
}
  // -----------------------
  // Handlers wiring
  // -----------------------
  function attachProfileHandlers() {
    changePhotoBtn && changePhotoBtn.addEventListener('click', () => photoUploadEl && photoUploadEl.click());

    photoUploadEl && photoUploadEl.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          if (profilePic) profilePic.src = e.target.result;
          if (sidebarAvatar && sidebarAvatar.tagName.toLowerCase() === 'img') sidebarAvatar.src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
      }
    });

    removePhotoBtn && removePhotoBtn.addEventListener('click', function() {
      if (profilePic) profilePic.src = DEFAULT_AVATAR;
      if (sidebarAvatar && sidebarAvatar.tagName.toLowerCase() === 'img') sidebarAvatar.src = DEFAULT_AVATAR;
    });

    saveProfileBtn && saveProfileBtn.addEventListener('click', saveProfileToServer);

    cancelProfileBtn && cancelProfileBtn.addEventListener('click', function() {
      loadStudentProfile();
      showToast('Changes discarded', 'info');
    });
  }

  // -----------------------
  // Existing settings code (tabs etc) left intact, but init functions here
  // -----------------------
  function initializeTabs() {
    tabs.forEach(tab => {
      tab.addEventListener('click', function() {
        const targetTab = this.getAttribute('data-tab');
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        const el = document.getElementById(targetTab);
        if (el) el.classList.add('active');
      });
    });
  }

  // reuse earlier functions for notifications, privacy, preferences, danger zone, password checks, etc.
  // For brevity, we call the existing initializer functions if they exist in the page scope.
  // (In your file these already exist; this block keeps behavior the same.)
  function safeCall(name) {
    if (typeof window[name] === 'function') {
      try { window[name](); } catch (e) { console.warn('initializer', name, 'failed', e); }
    }
  }

  // load from localStorage and apply to UI
  function loadSettingsLocal() {
    const savedSettings = localStorage.getItem('electionSettings');
    if (savedSettings) Object.assign(settingsState, JSON.parse(savedSettings));
    // apply settingsState to UI
    if (firstNameEl) firstNameEl.value = settingsState.profile.firstName;
    if (lastNameEl) lastNameEl.value = settingsState.profile.lastName;
    if (emailEl) emailEl.value = settingsState.profile.email;
    if (bioEl) bioEl.value = settingsState.profile.bio;
    if (profilePic) profilePic.src = settingsState.profile.profilePicture;

    const notifEmail = document.getElementById('email-notifications');
    if (notifEmail) notifEmail.checked = settingsState.notifications.email;
    const notifPush = document.getElementById('push-notifications');
    if (notifPush) notifPush.checked = settingsState.notifications.push;
    const reminders = document.getElementById('election-reminders');
    if (reminders) reminders.checked = settingsState.notifications.reminders;
    const results = document.getElementById('result-announcements');
    if (results) results.checked = settingsState.notifications.results;
    const candidates = document.getElementById('candidate-updates');
    if (candidates) candidates.checked = settingsState.notifications.candidates;

    const twoFactor = document.getElementById('two-factor-auth');
    if (twoFactor) twoFactor.checked = settingsState.privacy.twoFactor;
    const showHistory = document.getElementById('show-voting-history');
    if (showHistory) showHistory.checked = settingsState.privacy.showHistory;
    const dataCollection = document.getElementById('data-collection');
    if (dataCollection) dataCollection.checked = settingsState.privacy.dataCollection;

    const language = document.getElementById('language');
    if (language) language.value = settingsState.preferences.language;
    const timezone = document.getElementById('timezone');
    if (timezone) timezone.value = settingsState.preferences.timezone;
    const dateFormat = document.getElementById('date-format');
    if (dateFormat) dateFormat.value = settingsState.preferences.dateFormat;
    const darkMode = document.getElementById('dark-mode');
    if (darkMode) darkMode.checked = settingsState.preferences.darkMode;
    const autoLogout = document.getElementById('auto-logout');
    if (autoLogout) autoLogout.checked = settingsState.preferences.autoLogout;
  }

  function saveSettings() {
    localStorage.setItem('electionSettings', JSON.stringify(settingsState));
  }

  // -----------------------
  // Initializer (runs once)
  // -----------------------
  document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    // call existing initializer functions in your file if they exist (keeps previous behavior)
    safeCall('initializeProfileSection');      // existing if present (no-op if already defined)
    safeCall('initializeNotificationSection');
    safeCall('initializePrivacySection');
    safeCall('initializePreferencesSection');
    safeCall('initializeDangerZone');

    // our profile handlers (ensures profile area works as intended)
    attachProfileHandlers();

    // load local state and then attempt server load (server overrides local values)
    loadSettingsLocal();
    loadStudentProfile();
  });

  // close IIFE
})();

</script>
<script>
/* Unified profile handlers — prevents duplicate event attachments
   Drop this in once (replace older duplicate handlers).
*/

(() => {
  const PROJECT_BASE = '/eBotoni/eBotoni'; // change if your project root is different
  const GET_STUDENT_API = window.location.origin + PROJECT_BASE + '/student/getStudent.php';
  const SAVE_PROFILE_API = window.location.origin + PROJECT_BASE + '/student/save_profile.php';
  const DEFAULT_AVATAR = window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg';

  // DOM refs
  const changePhotoBtn = document.getElementById('change-photo-btn');
  const photoUploadEl  = document.getElementById('photo-upload');
  const removePhotoBtn = document.getElementById('remove-photo-btn');
  const saveProfileBtn = document.getElementById('save-profile');
  const profilePic     = document.getElementById('profile-picture');
  const sidebarAvatar  = document.getElementById('sidebarAvatar');
  const firstNameEl    = document.getElementById('first-name');
  const lastNameEl     = document.getElementById('last-name');
  const emailEl        = document.getElementById('email');
  const bioHiddenEl    = document.getElementById('bio-hidden');

  // guard: don't attach twice
  if (changePhotoBtn && changePhotoBtn.dataset.profileInit === '1') return;
  if (changePhotoBtn) changePhotoBtn.dataset.profileInit = '1';

  function showToast(msg, type='success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    if (!toast || !toastMessage) return console.log(type, msg);
    toastMessage.textContent = msg;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    setTimeout(()=> toast.classList.remove('show'), 3000);
  }

  // Resolve DB path (e.g. "student/uploads/avatars/..." => absolute URL)
  function resolveAvatarUrl(rawPath) {
    if (!rawPath) return DEFAULT_AVATAR;
    const s = String(rawPath).trim();
    if (!s) return DEFAULT_AVATAR;
    if (/^https?:\/\//i.test(s)) return s;
    // remove leading ../ or / and join with PROJECT_BASE
    const cleaned = s.replace(/^(\.\.\/)+/, '').replace(/^\/+/, '');
    return window.location.origin + PROJECT_BASE + '/' + cleaned;
  }

  // Preview local file immediately
  function previewLocalFile(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      if (profilePic && profilePic.tagName.toLowerCase() === 'img') profilePic.src = e.target.result;
      if (sidebarAvatar && sidebarAvatar.tagName.toLowerCase() === 'img') sidebarAvatar.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  // Click -> open file dialog (only one handler)
  changePhotoBtn && changePhotoBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (photoUploadEl) photoUploadEl.click();
  });

  // When user selects a file — preview it (will not re-open dialog)
  photoUploadEl && photoUploadEl.addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;
    // preview immediately
    previewLocalFile(this.files[0]);
    // optionally auto-submit to server, or wait for Save Changes — I'll auto-save:
    saveProfileToServer();
  });

  // Remove photo -> set default preview (but don't change DB until saved)
  removePhotoBtn && removePhotoBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (profilePic) profilePic.src = DEFAULT_AVATAR;
    if (sidebarAvatar) sidebarAvatar.src = DEFAULT_AVATAR;
    // mark hidden input so server knows to remove? (optional)
    bioHiddenEl && (bioHiddenEl.value = bioHiddenEl.value || '');
  });

  // Save button (if user edits names/email and clicks Save)
  saveProfileBtn && saveProfileBtn.addEventListener('click', (e) => {
    e.preventDefault();
    saveProfileToServer();
  });

  // Upload to server and update UI from server response
  async function saveProfileToServer() {
    const first = firstNameEl?.value?.trim() ?? '';
    const last  = lastNameEl?.value?.trim() ?? '';
    const email = emailEl?.value?.trim() ?? '';
    const bio   = bioHiddenEl?.value ?? '';

    if (!first || !last || !email) {
      showToast('First, last and valid email required', 'error');
      return;
    }

    const fd = new FormData();
    fd.append('firstName', first);
    fd.append('lastName', last);
    fd.append('email', email);
    fd.append('bio', bio);

    if (photoUploadEl && photoUploadEl.files && photoUploadEl.files[0]) {
      fd.append('photo', photoUploadEl.files[0]);
    }

    try {
      const resp = await fetch(SAVE_PROFILE_API, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const text = await resp.text();
      if (!resp.ok) { showToast('Server error: ' + resp.status, 'error'); console.error(text); return; }
      if (!text) { showToast('Empty response from server', 'error'); return; }
      if (text.trim().startsWith('<')) { showToast('Server error (see console)', 'error'); console.warn(text); return; }

      const json = JSON.parse(text);
      if (!json.success) {
        showToast(json.error || json.message || 'Save failed', 'error');
        console.error('save_profile result:', json);
        return;
      }

      // If server returned profile_photo_url use it to build absolute url
      const serverPhoto = json.profile_photo_url ?? (json.student && json.student.profile_photo_url) ?? '';
      if (serverPhoto) {
        const resolved = resolveAvatarUrl(serverPhoto);
        if (profilePic) profilePic.src = resolved;
        if (sidebarAvatar) sidebarAvatar.src = resolved;
      }

      // If server returned student object, repopulate
      const updated = json.student ?? null;
      if (updated) {
        // update name/email fields (tolerant)
        if (updated.Name) {
          const parts = String(updated.Name).trim().split(' ');
          if (parts.length) firstNameEl.value = parts.shift();
          lastNameEl.value = parts.join(' ');
        }
        if (updated.Email) emailEl.value = updated.Email;
      }

      showToast('Profile saved', 'success');

    } catch (err) {
      console.error('saveProfileToServer', err);
      showToast('Network/server error while saving', 'error');
    }
  }

  // Always fetch current student on load so other pages show saved avatar
  async function loadStudentProfileOnce() {
    try {
      const r = await fetch(GET_STUDENT_API, { cache: 'no-store', credentials: 'same-origin' });
      if (!r.ok) return;
      const text = (await r.text()).trim();
      if (!text) return;
      if (text.startsWith('<')) { console.warn('getStudent returned HTML'); return; }
      const json = JSON.parse(text);
      let s = json.student ?? json.data?.student ?? null;
      if (!s) {
        if (json.Name || json.Student_ID || json.Email) s = json;
        else {
          const maybe = Object.values(json).find(v => v && typeof v === 'object' && (v.Name || v.Student_ID || v.Email));
          s = maybe ?? null;
        }
      }
      if (!s) return;

      // populate UI
      if (s.Name) {
        const parts = String(s.Name).trim().split(' ');
        if (parts.length) firstNameEl.value = parts.shift();
        lastNameEl.value = parts.join(' ');
      }
      if (s.Email) emailEl.value = s.Email;
      if (s.bio) { const bioEl = document.getElementById('bio'); if (bioEl) bioEl.value = s.bio; if (bioHiddenEl) bioHiddenEl.value = s.bio; }

      const photo = s.profile_photo_url ?? s.profile_photo ?? s.avatar ?? '';
      const avatarUrl = resolveAvatarUrl(photo);
      if (profilePic && profilePic.tagName.toLowerCase() === 'img') profilePic.src = avatarUrl;
      if (sidebarAvatar && sidebarAvatar.tagName.toLowerCase() === 'img') sidebarAvatar.src = avatarUrl;

      // expose id
      window.CURRENT_USER = window.CURRENT_USER || {};
      window.CURRENT_USER.id = s.Student_ID ?? s.id ?? null;

    } catch (err) {
      console.error('loadStudentProfileOnce', err);
    }
  }

  // run on DOM ready
  document.addEventListener('DOMContentLoaded', loadStudentProfileOnce);
})();
</script>


</body>
</html>
