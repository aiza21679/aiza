<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - USTP Jasaan</title>
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
            --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--ustp-light-gray);
            color: var(--ustp-dark-gray);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Now Consistent with Dashboard */
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

        /* Header - Now Consistent with Dashboard */
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

        /* Stats Cards - Consistent with Dashboard */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--ustp-white);
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, var(--ustp-green) 0%, #20c997 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, var(--ustp-orange) 0%, #ff922b 100%);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--ustp-dark-gray);
        }

        .stat-info p {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        /* Filter Section */
        .filter-section {
            background: var(--ustp-white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--ustp-blue);
            font-size: 0.85rem;
        }

        .filter-select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: var(--ustp-white);
            font-size: 0.85rem;
            color: var(--ustp-dark-gray);
            transition: var(--transition);
            cursor: pointer;
        }

        .search-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 2;
            min-width: 280px;
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 35px 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--ustp-light-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            height: fit-content;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 10px rgba(0, 51, 102, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 6px 15px rgba(0, 51, 102, 0.4);
            transform: translateY(-2px);
        }

        /* Party List Container */
        .party-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 25px;
        }

        .party-card {
            background: var(--ustp-white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .party-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .party-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: var(--ustp-white);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .party-logo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--ustp-gold);
            background: var(--ustp-white);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .party-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .party-info {
            flex: 1;
        }

        .party-name {
            font-size: 1.4rem;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .party-tagline {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .party-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* Candidates Grid within Party */
        .candidates-grid {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .candidate-card {
            background: var(--ustp-light-gray);
            border-radius: 8px;
            padding: 18px;
            border-left: 4px solid var(--ustp-gold);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .candidate-card:hover {
            background: #e8f4ff;
            transform: translateX(5px);
        }

        .candidate-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .candidate-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--ustp-gold);
            flex-shrink: 0;
        }

        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-basic-info {
            flex: 1;
        }

        .candidate-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--ustp-blue);
            margin-bottom: 2px;
        }

        .candidate-position {
            font-size: 0.85rem;
            color: var(--ustp-dark-gray);
            font-weight: 500;
            background: rgba(255, 204, 0, 0.2);
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .candidate-details {
            border-top: 1px solid #e0e0e0;
            padding-top: 12px;
        }

        .detail-section {
            margin-bottom: 12px;
        }

        .detail-section h4 {
            color: var(--ustp-blue);
            margin-bottom: 6px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .detail-section h4 i {
            color: var(--ustp-blue);
            font-size: 0.75rem;
        }

        .platform-points, .achievement-list {
            list-style: none;
            padding-left: 0;
        }

        .platform-points li, .achievement-list li {
            margin-bottom: 5px;
            padding-left: 14px;
            position: relative;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .platform-points li:before, .achievement-list li:before {
            content: "•";
            color: var(--ustp-gold);
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .education-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .education-item {
            font-size: 0.8rem;
            color: var(--ustp-dark-gray);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 750px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 12px;
        }

        .modal-title {
            font-size: 1.3rem;
            color: var(--ustp-blue);
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--ustp-red);
        }

        .candidate-modal-content {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .candidate-modal-header {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .candidate-modal-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--ustp-gold);
            flex-shrink: 0;
        }

        .candidate-modal-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-modal-info {
            flex: 1;
        }

        .candidate-modal-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ustp-blue);
            margin-bottom: 5px;
        }

        .candidate-modal-position {
            font-size: 1rem;
            color: var(--ustp-dark-gray);
            margin-bottom: 8px;
            background: rgba(255, 204, 0, 0.2);
            padding: 4px 10px;
            border-radius: 5px;
            display: inline-block;
        }

        .candidate-modal-party {
            font-size: 0.9rem;
            color: var(--ustp-light-blue);
            font-weight: 600;
        }

        .modal-detail-section {
            margin-bottom: 18px;
        }

        .modal-detail-section h3 {
            color: var(--ustp-blue);
            margin-bottom: 8px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #eee;
            padding-bottom: 6px;
        }

        .modal-detail-section h3 i {
            color: var(--ustp-gold);
        }

        .modal-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .modal-detail-item {
            background: var(--ustp-light-gray);
            padding: 12px;
            border-radius: 6px;
        }

        .modal-detail-item h4 {
            color: var(--ustp-blue);
            margin-bottom: 6px;
            font-size: 0.85rem;
        }

        .modal-detail-item p {
            font-size: 0.8rem;
            color: var(--ustp-dark-gray);
            line-height: 1.5;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--ustp-white);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            display: none;
        }

        .empty-icon {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: var(--ustp-blue);
            margin-bottom: 8px;
            font-size: 1.2rem;
        }

        .empty-state p {
            color: #7f8c8d;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .filter-section {
                flex-direction: column;
            }
            
            .filter-group, .search-group {
                min-width: 100%;
            }
            
            .candidates-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
            }
            
            .nav-menu a {
                flex-shrink: 0;
            }
            
            .user-profile {
                display: none;
            }
            
            .party-header {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            
            .party-meta {
                justify-content: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .candidate-modal-header {
                flex-direction: column;
                text-align: center;
            }
            
        }
    </style>
  <script>
    /* small helpers used by templates */
    function escapeHTML(s){ if (s === null || s === undefined) return ''; return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;'); }
    function escapeAttr(s){ return escapeHTML(s); }
    function truncate(s, n=140){ if (!s) return ''; s=String(s); return s.length>n ? s.slice(0,n)+'…' : s; }
    function toggleDropdown(){
      const d = document.getElementById('dropdown');
      if (!d) return;
      d.style.display = (d.style.display === 'block') ? 'none' : 'block';
    }
    function logoutUser(){
      fetch('../php/logout.php', { method: 'POST' }).finally(()=> window.location.href = '/');
    }
  </script>
</head>

<body>
    <!-- Sidebar (kept as before, no CSS included) -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo"><img src="../assests/suffage.png" alt="Logo"></div>
            <div class="logoW"></div>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="vote.php"><i class="fas fa-vote-yea"></i><span>Active Elections</span></a>
            <a href="votinghistory.php"><i class="fas fa-history"></i><span>Voting History</span></a>
            <a href="candidates.php" class="active"><i class="fas fa-user-friends"></i><span>Candidates</span></a>
            <a href="results.php"><i class="fas fa-chart-bar"></i><span>Results</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
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
        <div class="header">
            <div class="page-title">
                <h1>Party Lists & Candidates</h1>
                <p>When the votes are counted, leadership is defined — not by luck, but by trust</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3 id="total-candidates">0</h3><p>Total Candidates</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-flag"></i></div>
                <div class="stat-info"><h3 id="total-parties">0</h3><p>Party Lists</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-vote-yea"></i></div>
                <div class="stat-info"><h3 id="active-elections">0</h3><p>Active Elections</p></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <div class="filter-group">
                <label for="election-filter">Election</label>
                <select id="election-filter" class="filter-select">
                    <option value="all">All Elections</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="party-filter">Party List</label>
                <select id="party-filter" class="filter-select">
                    <option value="all">All Party Lists</option>
                </select>
            </div>
            <div class="search-group">
                <label for="candidate-search">Search Candidates</label>
                <div class="search-box">
                    <input type="text" id="candidate-search" class="search-input" placeholder="Search by candidate name, position, or platform...">
                </div>
            </div>
            <button class="btn-primary" id="apply-filters"><i class="fas fa-filter"></i> Apply Filters</button>
        </div>

        <!-- Party Lists Container -->
        <div class="party-container"></div>

        <!-- Empty State -->
        <div class="empty-state" id="empty-state" style="display:none;">
            <div class="empty-icon"><i class="fas fa-user-friends"></i></div>
            <h3>No Candidates Found</h3>
            <p>Try adjusting your filters or search terms to find candidates.</p>
            <button class="btn-primary" onclick="resetFilters()"><i class="fas fa-refresh"></i> Reset Filters</button>
        </div>

        <footer><p>© 2025 eBOTO | USTP Jasaan | All Rights Reserved</p></footer>
    </main>

    <!-- Candidate Detail Modal (keeps your modal layout, shows full details) -->
    <div id="candidate-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-candidate-name">Candidate Details</h2>
                <button class="close-modal" onclick="closeCandidateModal()">&times;</button>
            </div>
            <div class="candidate-modal-content" id="candidate-modal-content"></div>
        </div>
    </div>

<script>
/* ========== CONFIG ========== */

/* canonical directories (use encoded path where folder contains a space) */
const PROVIDED_DIR_ENCODED = '/eBotoni/eBotoni/student%20org/uploads/candidates/';
const PROVIDED_DIR_HTTP = location.origin + '/eBotoni/eBotoni/student%20org/uploads/candidates/';

/* default fallback — used only if everything else fails */
const DEFAULT_AVATAR = '/eBotoni/eBotoni/assests/default-avatar.jpg';

/* API endpoint (use encoded space) */
const API_PATH = "../student%20org/get_candidates.php";

/* DOM shortcuts */
const partyContainer = document.querySelector('.party-container');
const emptyState = document.getElementById('empty-state');
const totalCandidatesEl = document.getElementById('total-candidates');
const totalPartiesEl = document.getElementById('total-parties');
const activeElectionsEl = document.getElementById('active-elections');
const electionFilter = document.getElementById('election-filter');
const partyFilter = document.getElementById('party-filter');
const searchInput = document.getElementById('candidate-search');
const applyFiltersBtn = document.getElementById('apply-filters');

let rawCandidates = [];
let grouped = {};
let partyNames = [];
let electionIds = new Set();

/* ---------- boot ---------- */
document.addEventListener('DOMContentLoaded', () => {
  loadCandidates();
  loadActiveElectionCount();
  applyFiltersBtn?.addEventListener('click', applyFilters);
  searchInput?.addEventListener('input', applyFilters);
  partyFilter?.addEventListener('change', applyFilters);
  electionFilter?.addEventListener('change', applyFilters);
});

/* ---------- fetch & normalize ---------- */
async function loadCandidates() {
  try {
    const resp = await fetch(API_PATH, { credentials: 'same-origin' });
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const data = await resp.json();
    if (!Array.isArray(data)) throw new Error('API returned non-array');

    rawCandidates = data.map(normalizeCandidate);
    buildGroups();
    populateFilters();
    renderParties();
    applyFilters();
  } catch (err) {
    console.error('loadCandidates error:', err);
    partyContainer.innerHTML = `<div style="color:#b00;padding:16px">Failed to load candidates. Check console.</div>`;
  }
}

/* normalizeCandidate: compute working avatar URL (uses confirmed folder) */
function normalizeCandidate(r) {
  // raw avatar path from DB (ex: "uploads/candidates/1764000249_bfec461dfbb0.jpg")
  const avatarRaw = r.Avatar ?? r.avatar ?? r.Avatar_Path ?? r.image ?? r.avatar_path ?? r.photo ?? '';
  // extract filename only (handles "uploads/candidates/..." and plain filenames)
  const filename = String(avatarRaw || '').split('/').pop() || '';
  // build working absolute image URL (confirmed pattern)
  const avatarUrl = filename ? (PROVIDED_DIR_HTTP + encodeURIComponent(filename)) : DEFAULT_AVATAR;

  const party = r.PartyList ?? r.Party ?? r.partylist ?? r.Org_Name ?? 'Independent';
  const status = String(r.Status ?? r.status ?? '').toLowerCase();
  const electionId = r.Election_ID ?? r.election_id ?? r.ElectionId ?? r.election ?? null;

  return {
    raw: r,
    id: r.id ?? r.Candidate_ID ?? r.candidate_id ?? null,
    fullName: r.FullName ?? r.full_name ?? r.name ?? r.Name ?? '',
    email: r.Email ?? r.email ?? '',
    positionId: r.Position_ID ?? r.PositionId ?? r.position_id ?? null,
    positionName: r.Position_Name ?? r.PositionName ?? r.Position ?? r.position ?? 'N/A',
    program: r.Program ?? r.program ?? '',
    yearLevel: r.YearLevel ?? r.year_level ?? r.yearLevel ?? '',
    platform: r.Platform ?? r.platform ?? '',
    experience: r.Experience ?? r.experience ?? '',
    achievements: r.Achievements ?? r.achievements ?? r.Key_Achievements ?? r.key_achievements ?? '',
    // Force-correct avatar (use the working confirmed URL). Keep as the primary candidate list so the onerror fallback still works.
    avatarCandidates: [avatarUrl],
    avatar: avatarUrl || DEFAULT_AVATAR,
    status,
    party: String(party),
    orgId: r.Org_ID ?? r.org_id ?? null,
    orgName: r.Org_Name ?? r.orgName ?? '',
    electionId: electionId ?? null
  };
}

/* (Optional) kept for backward compatibility — not required by normalizeCandidate above.
   You can remove this if you want. It remains harmless. */
function buildAvatarCandidates(raw) {
  const list = [];
  if (!raw) {
    list.push(DEFAULT_AVATAR);
    return list;
  }
  const s = String(raw).trim();
  // If DB contains a full URL, try it
  if (/^https?:\/\//i.test(s)) list.push(s);
  // canonical guesses
  const name = s.split('/').pop();
  if (name) {
    list.push(PROVIDED_DIR_HTTP + encodeURIComponent(name));
    list.push(PROVIDED_DIR_ENCODED + name);
    list.push('/eBotoni/eBotoni/' + name);
    list.push('/eBotoni/' + name);
    list.push('/' + name);
    list.push(name);
  }
  list.push(DEFAULT_AVATAR);
  return Array.from(new Set(list));
}

/* ---------- grouping & filters ---------- */
function buildGroups() {
  grouped = {};
  partyNames = [];
  electionIds = new Set();

  rawCandidates.forEach(c => {
    const p = c.party || 'Independent';
    if (!grouped[p]) grouped[p] = [];
    grouped[p].push(c);

    if (c.electionId !== null && c.electionId !== undefined && String(c.electionId).trim()) {
      const s = (c.status || '').toLowerCase();
      if (s.includes('ongo') || s.includes('active') || s === '1') {
        electionIds.add(String(c.electionId));
      }
    }
  });

  partyNames = Object.keys(grouped).sort((a,b)=>a.localeCompare(b));
  totalCandidatesEl && (totalCandidatesEl.textContent = rawCandidates.length);
  totalPartiesEl && (totalPartiesEl.textContent = partyNames.length);
  activeElectionsEl && (activeElectionsEl.textContent = electionIds.size);
}

function populateFilters() {
  if (!partyFilter) return;
  partyFilter.innerHTML = `<option value="all">All Party Lists</option>`;
  partyNames.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p;
    opt.textContent = p;
    partyFilter.appendChild(opt);
  });
}

/* ---------- rendering (cards show ONLY: avatar, name, position, Education, Platform) ---------- */
function renderParties(filteredGrouped = null) {
  const source = filteredGrouped ?? grouped;
  partyContainer.innerHTML = '';

  const names = Object.keys(source);
  if (names.length === 0) {
    if (emptyState) emptyState.style.display = 'block';
    return;
  } else {
    if (emptyState) emptyState.style.display = 'none';
  }

  for (const party of names) {
    const list = source[party];

    const partyCard = document.createElement('div');
    partyCard.className = 'party-card';
    partyCard.setAttribute('data-party', party);
    partyCard.innerHTML = `
      <div class="party-header">
        <div class="party-logo"><img src="../assests/eboto logo.png" alt="${escapeHTML(party)}" /></div>
        <div class="party-info">
          <h2 class="party-name">${escapeHTML(party)}</h2>
          <p class="party-tagline">${list.length} Candidates</p>
        </div>
      </div>
      <div class="candidates-grid"></div>
    `;

    const grid = partyCard.querySelector('.candidates-grid');

    list.forEach(c => {
      const card = document.createElement('div');
      card.className = 'candidate-card';
      card.setAttribute('data-candidate-id', c.id ?? '');

      // JSON-encoded avatar candidates used by onerror cycling
      const candsJSON = escapeAttr(JSON.stringify(c.avatarCandidates || [DEFAULT_AVATAR]));

      card.innerHTML = `
        <div class="candidate-header" style="display:flex; gap:12px; align-items:flex-start;">
          <div class="candidate-avatar" style="width:64px;height:64px;border-radius:50%;overflow:hidden;border:3px solid #ffd54f;flex:0 0 64px;">
            <img
              src="${escapeAttr(c.avatar)}"
              alt="${escapeHTML(c.fullName)}"
              data-cands='${candsJSON}'
              data-idx="0"
              style="width:100%;height:100%;object-fit:cover;"
              onerror="(function(img){ try{ const list = JSON.parse(img.dataset.cands||'[]'); let idx = Number(img.dataset.idx||0)+1; const next = list[idx]; console.warn('avatar failed, trying next ->', next, 'from', img.src); if(next){ img.dataset.idx = idx; img.src = next; } else { img.src = '${escapeAttr(DEFAULT_AVATAR)}'; } }catch(e){ img.src = '${escapeAttr(DEFAULT_AVATAR)}'; } })(this)"
            />
          </div>

          <div class="candidate-basic-info" style="flex:1;">
            <h3 class="candidate-name" style="margin:0 0 6px 0;">${escapeHTML(c.fullName)}</h3>
            <div class="candidate-position" style="color:#666">${escapeHTML(c.positionName)}</div>
          </div>
        </div>

        <div class="candidate-details" style="margin-top:12px;">
          <div class="detail-section">
            <strong style="display:block;margin-bottom:6px;"><i class="fas fa-user-graduate"></i> Education</strong>
            <div class="education-info" style="color:#444;">
              <div>${escapeHTML(c.program || '')}${c.program && c.yearLevel ? ' • ' : ''}${escapeHTML(c.yearLevel || '')}</div>
            </div>
          </div>

          <div class="detail-section" style="margin-top:8px;">
            <strong style="display:block;margin-bottom:6px;"><i class="fas fa-bullhorn"></i> Platform</strong>
            <div class="platform-info" style="color:#444;">${formatPlatformPreview(c.platform)}</div>
          </div>
        </div>
      `;

      // open modal on click (modal still shows full details)
      card.addEventListener('click', () => showCandidateModal(c));
      grid.appendChild(card);
    });

    partyContainer.appendChild(partyCard);
  }
}

function formatPlatformPreview(platform) {
  if (!platform) return '';
  const parts = String(platform).split(/\r?\n/).map(s => s.trim()).filter(Boolean);
  if (parts.length <= 1) {
    return `<div style="white-space:normal;">${escapeHTML(truncate(parts[0]||'', 240))}</div>`;
  } else {
    const listItems = parts.slice(0,3).map(it => `<li>${escapeHTML(truncate(it, 160))}</li>`).join('');
    return `<ul style="margin:0;padding-left:18px;">${listItems}</ul>`;
  }
}

/* ---------- filtering ---------- */
function applyFilters() {
  const q = (searchInput?.value || '').trim().toLowerCase();
  const selectedParty = partyFilter?.value || 'all';
  const filtered = {};

  for (const p of partyNames) {
    if (selectedParty !== 'all' && p !== selectedParty) continue;
    const items = (grouped[p] || []).filter(c => {
      if (!q) return true;
      const hay = `${c.fullName} ${c.positionName} ${c.program} ${c.platform}`.toLowerCase();
      return hay.includes(q);
    });
    if (items.length) filtered[p] = items;
  }
  renderParties(filtered);
}

/* ---------- modal rendering (full details) ---------- */
function showCandidateModal(c) {
  const modal = document.getElementById('candidate-modal');
  const modalName = document.getElementById('modal-candidate-name');
  const modalContent = document.getElementById('candidate-modal-content');
  if (!modal || !modalName || !modalContent) return;

  modalName.textContent = c.fullName || 'Candidate Details';

  let html = `
    <div class="candidate-modal-header" style="display:flex;gap:16px;align-items:center;">
      <div class="candidate-modal-avatar" style="width:96px;height:96px;border-radius:50%;overflow:hidden;border:3px solid #ffd54f;flex:0 0 96px;">
        <img src="${escapeAttr(c.avatar)}" alt="${escapeHTML(c.fullName)}" style="width:100%;height:100%;object-fit:cover;"
             onerror="(function(i){ if(i.__fallback) return; i.__fallback=true; i.src='${escapeAttr(DEFAULT_AVATAR)}'; })(this)"/>
      </div>
      <div class="candidate-modal-info">
        <h2 class="candidate-modal-name" style="margin:0 0 6px 0;">${escapeHTML(c.fullName)}</h2>
        <div class="candidate-modal-position" style="color:#666;margin-bottom:6px;">${escapeHTML(c.positionName)}</div>
        <div class="candidate-modal-party" style="color:#0a66c2;">${escapeHTML(c.party)}</div>
      </div>
    </div>
  `;

  // Education block
  const eduPieces = [];
  if (c.program) eduPieces.push({title:'Degree Program', value:c.program});
  if (c.yearLevel) eduPieces.push({title:'Year Level', value:c.yearLevel});
  const gwa = c.raw?.GWA ?? c.raw?.gwa ?? null;
  if (gwa) eduPieces.push({title:'GWA', value: gwa});

  if (eduPieces.length) {
    html += `<div class="modal-detail-section"><h3><i class="fas fa-user-graduate"></i> Education & Academic Background</h3><div class="modal-detail-grid">`;
    eduPieces.forEach(p => {
      html += `<div class="modal-detail-item" style="margin-bottom:8px;"><h4 style="margin:0 0 4px 0;">${escapeHTML(p.title)}</h4><p style="margin:0;">${escapeHTML(String(p.value))}</p></div>`;
    });
    html += `</div></div>`;
  }

  // Platform
  if (c.platform) {
    html += `<div class="modal-detail-section"><h3><i class="fas fa-bullhorn"></i> Platform & Agenda</h3>`;
    const parts = String(c.platform).split(/\r?\n/).filter(Boolean);
    if (parts.length > 1) {
      html += `<ul class="platform-points">` + parts.map(p => `<li>${escapeHTML(p)}</li>`).join('') + `</ul>`;
    } else {
      html += `<p style="white-space:pre-wrap">${escapeHTML(c.platform)}</p>`;
    }
    html += `</div>`;
  }

  // Achievements
  const ach = c.achievements || c.raw?.Key_Achievements || c.raw?.Achievements || '';
  if (ach) {
    html += `<div class="modal-detail-section"><h3><i class="fas fa-trophy"></i> Key Achievements</h3>${formatList(ach)}</div>`;
  }

  // Experience
  const exp = c.experience || c.raw?.Experience || '';
  if (exp) {
    html += `<div class="modal-detail-section"><h3><i class="fas fa-briefcase"></i> Relevant Experience</h3>${formatList(exp)}</div>`;
  }

  // Details block
  html += `<div class="modal-detail-section"><h3><i class="fas fa-info-circle"></i> Details</h3><div class="modal-detail-grid">`;
  if (c.email) html += `<div style="margin-bottom:6px;"><strong>Contact:</strong><div>${escapeHTML(c.email)}</div></div>`;
  if (c.studentId) html += `<div style="margin-bottom:6px;"><strong>Student ID:</strong><div>${escapeHTML(c.studentId)}</div></div>`;
  if (c.program) html += `<div style="margin-bottom:6px;"><strong>Program:</strong><div>${escapeHTML(c.program)}</div></div>`;
  if (c.yearLevel) html += `<div style="margin-bottom:6px;"><strong>Year Level:</strong><div>${escapeHTML(c.yearLevel)}</div></div>`;
  html += `</div></div>`;

  modalContent.innerHTML = html;
  modal.style.display = 'flex';
  modal.onclick = (ev) => { if (ev.target === modal) closeCandidateModal(); };
}

function closeCandidateModal() {
  const modal = document.getElementById('candidate-modal');
  if (modal) modal.style.display = 'none';
}

/* simple helper to convert newline/semicolon/separator text or arrays into <ul> */
function formatList(x) {
  if (!x) return '';
  if (Array.isArray(x)) return `<ul style="margin:0;padding-left:18px">` + x.map(i => `<li>${escapeHTML(String(i))}</li>`).join('') + `</ul>`;
  const parts = String(x).split(/\r?\n|;|•|—|-/).map(s => s.trim()).filter(Boolean);
  if (!parts.length) return '';
  return `<ul style="margin:0;padding-left:18px">` + parts.map(p => `<li>${escapeHTML(p)}</li>`).join('') + `</ul>`;
}

/* ---------- active elections loader (tries elections endpoint then fallback) ---------- */
async function loadActiveElectionCount() {
  const TARGET = activeElectionsEl;
  if (!TARGET) return;
  const validId = (v) => { if (v === null || v === undefined) return false; const s = String(v).trim(); if (!s) return false; if (s.toLowerCase() === 'null' || s === '0') return false; return true; };

  try {
    const resp = await fetch('../student%20org/get_elections.php', { credentials: 'same-origin' });
    if (resp.ok) {
      const elections = await resp.json();
      if (Array.isArray(elections)) {
        const active = new Set();
        elections.forEach(e => {
          const id = e.Election_ID ?? e.id ?? e.election_id ?? e.ElectionId ?? null;
          const status = String(e.Status ?? e.status ?? '').toLowerCase();
          if (!validId(id)) return;
          if (status.includes('ongo') || status.includes('active') || status === '1') active.add(String(id));
        });
        TARGET.textContent = String(active.size);
        return;
      }
    }
  } catch (err) {
    console.debug('get_elections fetch failed (fallback):', err);
  }

  try {
    const activeSet = new Set();
    rawCandidates.forEach(c => {
      const rid = c.raw?.Election_ID ?? c.raw?.election_id ?? c.electionId ?? null;
      if (!validId(rid)) return;
      const s = (c.status || '') + ' ' + String(c.raw?.Election_Status ?? '');
      const small = s.toLowerCase();
      if (small.includes('ongo') || small.includes('active') || small === '1' || small.includes('ongoing')) activeSet.add(String(rid));
    });
    TARGET.textContent = String(activeSet.size);
  } catch (e) {
    console.error('active election fallback error:', e);
    TARGET.textContent = '0';
  }
}

/* ---------- utility: reset filters ---------- */
function resetFilters() {
  if (partyFilter) partyFilter.value = 'all';
  if (electionFilter) electionFilter.value = 'all';
  if (searchInput) searchInput.value = '';
  applyFilters();
}
</script>

<!-- Single robust profile loader: REPLACES previous duplicate loaders -->
<script>
/*
  Robust student profile loader (single copy).
  - Change PROJECT_BASE and GET_STUDENT_API to match your deployment if needed.
  - Uses existing element IDs: sidebarAvatar, sidebarName, sidebarRole
*/
const PROJECT_BASE = '/eBotoni/eBotoni'; // <-- change if your project root differs
const GET_STUDENT_API = window.location.origin + PROJECT_BASE + '/student/getStudent.php'; // <-- change if endpoint differs
const FALLBACK_AVATAR = window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg';

async function loadStudentProfileRobust() {
  const avatarEl = document.getElementById('sidebarAvatar');
  const nameEl = document.getElementById('sidebarName');
  const roleEl = document.getElementById('sidebarRole');

  function applyFallback() {
    try {
      if (avatarEl && avatarEl.tagName && avatarEl.tagName.toLowerCase() === 'img' && (!avatarEl.src || avatarEl.src.length === 0)) {
        avatarEl.src = FALLBACK_AVATAR;
      }
      if (nameEl && (!nameEl.textContent || nameEl.textContent.trim() === '')) nameEl.textContent = 'Student';
      if (roleEl && (!roleEl.textContent || roleEl.textContent.trim() === '')) roleEl.textContent = 'Student';
    } catch(e) { /* ignore */ }
  }

  try {
    const res = await fetch(GET_STUDENT_API, { cache: 'no-store', credentials: 'same-origin' });
    if (!res.ok) { console.warn('getStudent returned non-OK', res.status); applyFallback(); return; }

    // read text first so PHP warnings/HTML don't break JSON.parse
    const text = (await res.text()).trim();
    if (!text) { applyFallback(); return; }

    let payload;
    try { payload = JSON.parse(text); } catch (err) {
      // JSON parse failed — log and abort to fallback
      console.warn('getStudent returned non-JSON; first 800 chars:', text.slice(0,800));
      applyFallback();
      return;
    }

    // Accept different shapes: {status:'success', student: {...}} OR { student: ... } OR { data: { student: ... } } OR direct student object
    let s = payload.student ?? payload.data?.student ?? null;
    if (!s) {
      // if payload itself looks like student fields, use it
      if (payload.Name || payload.FullName || payload.Student_ID || payload.email) s = payload;
      else {
        // try to find nested object that looks like student
        for (const v of Object.values(payload)) {
          if (v && typeof v === 'object' && (v.Name || v.FullName || v.Student_ID || v.email)) { s = v; break; }
        }
      }
    }

    if (!s) { applyFallback(); return; }

    // tolerant getters
    const name = s.Name ?? s.name ?? s.FullName ?? s.fullname ?? s.name_display ?? '';
    const photo = s.profile_photo_url ?? s.profile_photo ?? s.Avatar ?? s.avatar ?? s.photo ?? '';
    const role = s.role ?? 'Student';

    // build avatar URL: absolute if starts with http, else join with PROJECT_BASE
    let avatarUrl = FALLBACK_AVATAR;
    if (photo && String(photo).trim()) {
      const p = String(photo).trim();
      if (/^https?:\/\//i.test(p)) avatarUrl = p;
      else {
        const cleaned = p.replace(/^(\.\.\/)+/, '').replace(/^\/+/, '');
        avatarUrl = window.location.origin + PROJECT_BASE + '/' + cleaned;
      }
    }

    // apply to DOM
    if (avatarEl && avatarEl.tagName && avatarEl.tagName.toLowerCase() === 'img') {
      avatarEl.src = avatarUrl;
      avatarEl.alt = (name || 'User') + ' avatar';
    }
    if (nameEl) nameEl.textContent = name || 'Student';
    if (roleEl) roleEl.textContent = role || 'Student';

    // set CURRENT_USER globally for other scripts if helpful
    try {
      window.CURRENT_USER = { id: s.id ?? s.Student_ID ?? s.student_id ?? null, name, avatar: avatarUrl, email: s.email ?? s.Email ?? null };
    } catch(e){}
  } catch (err) {
    console.error('loadStudentProfileRobust error', err);
    applyFallback();
  }
}

// Run once on DOM ready
document.addEventListener('DOMContentLoaded', loadStudentProfileRobust);
</script>

</body>
</html>