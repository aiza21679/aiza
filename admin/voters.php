<?php
// admin/voters.php
// Voters page (no CSS changes). Form posts to ../php/addVoter.php
// FIX: sidebar user name/role/avatar now load from ../php/getAdmin.php (same as register-voters.php)
//      instead of being hardcoded as "Gamaliel Valcorza".

session_start();
header('Content-Type: text/html; charset=utf-8');

// ---------- DB config ----------
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'eboto_db';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    $dbError = "DB connect failed: " . $mysqli->connect_error;
    $mysqli = null;
} else {
    $mysqli->set_charset('utf8mb4');
    $dbError = null;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function resolve_asset_path($raw) {
    if (!$raw || trim($raw) === '') return '../assests/default-avatar.jpg';
    $raw = trim((string)$raw);
    if (preg_match('#^https?://#i', $raw)) return $raw;
    $filename = basename($raw);
    $candidates = [
        __DIR__ . '/../assests/' . $filename,
        __DIR__ . '/../assets/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/eBotoni/eBotoni/assests/' . $filename,
        $_SERVER['DOCUMENT_ROOT'] . '/eBotoni/eBotoni/assets/' . $filename
    ];
    foreach ($candidates as $c) {
        if (@file_exists($c)) {
            if (strpos($c, __DIR__ . '/../assests/') === 0) return '../assests/' . basename($c);
            if (strpos($c, __DIR__ . '/../assets/') === 0) return '../assets/' . basename($c);
            return '/eBotoni/eBotoni/assests/' . basename($c);
        }
    }
    return $raw;
}

// prefer org=... in query
$orgId = null;
if (isset($_GET['org']) && is_numeric($_GET['org'])) $orgId = (int)$_GET['org'];

// -----------------------------
// Fetch organizations
// -----------------------------
$organizations = [];
if ($mysqli) {
    $existingCols = [];
    $colsRes = $mysqli->query("SHOW COLUMNS FROM `student_organization`");
    if ($colsRes) {
        while ($c = $colsRes->fetch_assoc()) $existingCols[] = $c['Field'];
        $colsRes->close();
    }
    $preferred = ['Org_ID', 'Org_Name', 'Description', 'Logo_Url', 'Email', 'Org_Code'];
    $selectCols = array_values(array_intersect($preferred, $existingCols));
    $selectSql = empty($selectCols) ? '*' : implode(', ', array_map(function($c){ return "`$c`"; }, $selectCols));
    $orderBy = in_array('Org_Name', $existingCols, true) ? "`Org_Name` ASC" : "`Org_ID` ASC";
    $sql = "SELECT {$selectSql} FROM `student_organization` ORDER BY {$orderBy}";
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) {
            $oid = isset($r['Org_ID']) ? (int)$r['Org_ID'] : null;
            if ($oid !== null) $organizations[$oid] = $r;
        }
        $res->close();
    }
}

// -----------------------------
// Fetch voters for selected org (read-only for table rendering)
// -----------------------------
$voters = [];
$orgMeta = null;
if ($orgId && $mysqli) {
    if (!empty($organizations) && isset($organizations[$orgId])) {
        $orgMeta = $organizations[$orgId];
    } else {
        foreach ($organizations as $o) {
            if ((int)($o['Org_ID'] ?? 0) === (int)$orgId) { $orgMeta = $o; break; }
        }
    }

    $studentCols = [];
    $colsRes = $mysqli->query("SHOW COLUMNS FROM `student`");
    if ($colsRes) {
        while ($c = $colsRes->fetch_assoc()) $studentCols[] = $c['Field'];
        $colsRes->close();
    }

    // Build name expr (prefer Name)
    function build_name_expr($cols) {
        if (in_array('Name', $cols, true)) return "COALESCE(s.`Name`, CONCAT('Student ', s.Student_ID)) AS `Name`";
        $firstCandidates  = ['First_Name','FirstName','first_name','firstname','first'];
        $middleCandidates = ['Middle_Name','MiddleName','middle_name','middlename','middle'];
        $lastCandidates   = ['Last_Name','LastName','last_name','lastname','last','Surname','surname'];
        $parts = [];
        foreach ($firstCandidates as $c) { if (in_array($c, $cols, true)) { $parts[] = "NULLIF(TRIM(s.`$c`),'')"; break; } }
        foreach ($middleCandidates as $c) { if (in_array($c, $cols, true)) { $parts[] = "NULLIF(TRIM(s.`$c`),'')"; break; } }
        foreach ($lastCandidates as $c) { if (in_array($c, $cols, true)) { $parts[] = "NULLIF(TRIM(s.`$c`),'')"; break; } }
        if (!empty($parts)) {
            $concat = "NULLIF(TRIM(CONCAT_WS(' ', " . implode(", ", $parts) . ")), '')";
            return "COALESCE({$concat}, CONCAT('Student ', s.Student_ID)) AS `Name`";
        }
        return "CONCAT('Student ', s.Student_ID) AS `Name`";
    }
    $nameExpr = build_name_expr($studentCols);

    $hasOrgCol = in_array('Org_ID', $studentCols, true) || in_array('org_id', $studentCols, true);
    $orgCol = in_array('Org_ID', $studentCols, true) ? 'Org_ID' : (in_array('org_id', $studentCols, true) ? 'org_id' : null);

    if ($hasOrgCol && $orgCol) {
        $safeOrgCol = $orgCol === 'Org_ID' ? '`Org_ID`' : '`org_id`';
        $sql = "SELECT s.Student_ID, {$nameExpr}, s.Email, s.Course, s.Year_Level, s.`status`, s.{$safeOrgCol} AS Org_ID
                FROM `student` s
                WHERE s.{$safeOrgCol} = ?
                ORDER BY `Name` ASC";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $orgId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) $voters[] = $r;
            $stmt->close();
        }
    } else {
        $tblCheck = $mysqli->query("SHOW TABLES LIKE 'student_organization_members'");
        if ($tblCheck && $tblCheck->num_rows > 0) {
            $joinSql = "
                SELECT s.Student_ID, {$nameExpr}, s.Email, s.Course, s.Year_Level, s.`status`, m.org_id
                FROM student_organization_members m
                LEFT JOIN student s ON (m.student_id = s.Student_ID)
                WHERE m.org_id = ?
                ORDER BY `Name` ASC
            ";
            $stmt = $mysqli->prepare($joinSql);
            if ($stmt) {
                $stmt->bind_param('i', $orgId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) $voters[] = $r;
                $stmt->close();
            }
        }
    }
}

// Count pending
$pendingCount = 0;
if ($orgId && $mysqli) {
    if (isset($orgCol)) {
        $safeOrgCol = $orgCol === 'Org_ID' ? '`Org_ID`' : '`org_id`';
        $q = $mysqli->prepare("SELECT COUNT(*) AS c FROM `student` WHERE {$safeOrgCol} = ? AND LOWER(COALESCE(`status`,'')) = 'pending'");
        if ($q) {
            $q->bind_param('i', $orgId);
            $q->execute();
            $r = $q->get_result()->fetch_assoc();
            $pendingCount = (int)($r['c'] ?? 0);
            $q->close();
        }
    } else {
        $q = $mysqli->prepare("SELECT COUNT(*) AS c FROM student_organization_members m JOIN student s ON (m.student_id = s.Student_ID) WHERE m.org_id = ? AND LOWER(COALESCE(s.`status`,'')) = 'pending'");
        if ($q) {
            $q->bind_param('i', $orgId);
            $q->execute();
            $r = $q->get_result()->fetch_assoc();
            $pendingCount = (int)($r['c'] ?? 0);
            $q->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>eBOTO | Admin Voters</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="../logout.js" defer></script>

  <style>
    :root {
      --ustp-blue: #003366;
      --ustp-light-blue: #0066cc;
      --ustp-gold: #ffcc00;
      --ustp-white: #ffffff;
      --ustp-light-gray: #f5f5f5;
      --ustp-dark-gray: #333333;
      --sidebar-width: 250px;
      --header-height: 60px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display: flex;
      min-height: 100vh;
      background-color: var(--ustp-light-gray);
      color: var(--ustp-dark-gray);
    }

    /* SIDEBAR - EXACTLY THE SAME AS VOTERS PAGE */
    .sidebar {
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
      color: var(--ustp-white);
      padding: 20px 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 100;
      transition: 0.3s ease;
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
      margin: 0 auto 10px auto;
      border: 4px solid var(--ustp-gold);
      background-color: var(--ustp-white);
    }

    .logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .logo-container h2 {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--ustp-white);
    }

    /* NAVIGATION - EXACTLY THE SAME AS VOTERS PAGE */
    .nav-menu {
      flex: 1;
      list-style: none;
      padding: 0;
      width: 100%;
    }

    .nav-item {
      padding: 14px 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 15px;
    }

    .nav-item:hover {
      background-color: #004b99;
    }

    .nav-item.active {
      background-color: var(--ustp-gold);
      color: var(--ustp-blue);
      font-weight: bold;
      border-left: 4px solid var(--ustp-gold);
    }

    .nav-item i {
      width: 20px;
      text-align: center;
    }

    .nav-item a {
      text-decoration: none;
      color: inherit;
      display: block;
      width: 100%;
    }

    /* USER PROFILE - EXACTLY THE SAME AS VOTERS PAGE */
    .user-profile {
      padding: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      cursor: pointer;
      width: 100%;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid var(--ustp-gold);
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
      background: var(--ustp-blue);
      border: 1px solid var(--ustp-gold);
      border-radius: 8px;
      overflow: hidden;
      width: 150px;
      z-index: 10;
    }

    .user-dropdown a {
      display: block;
      padding: 10px 20px;
      color: var(--ustp-white);
      text-decoration: none;
    }

    .user-dropdown a:hover {
      background: var(--ustp-gold);
      color: var(--ustp-blue);
    }

    /* MAIN CONTENT - EXACTLY THE SAME AS VOTERS PAGE */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      display: flex;
      flex-direction: column;
      transition: margin-left 0.3s ease;
    }

    .header {
      height: var(--header-height);
      background: var(--ustp-white);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .header h1 {
      color: var(--ustp-blue);
      font-size: 22px;
      font-weight: 700;
    }

    .menu-toggle {
      display: none;
      font-size: 22px;
      color: var(--ustp-blue);
      cursor: pointer;
    }

    .content {
      padding: 30px;
      flex: 1;
    }

    /* Department Header - SIMILAR TO VOTERS PAGE */
    .department-header {
      background: var(--ustp-white);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      padding: 25px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .department-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid var(--ustp-gold);
      object-fit: cover;
      background-color: var(--ustp-white);
    }

    .department-info h2 {
      color: var(--ustp-blue);
      font-size: 24px;
      margin-bottom: 5px;
    }

    .department-info p {
      color: var(--ustp-dark-gray);
      font-size: 16px;
      opacity: 0.8;
    }

    .department-stats {
      display: flex;
      gap: 30px;
      margin-left: auto;
    }

    .stat {
      text-align: center;
      padding: 10px 20px;
      background: rgba(0, 102, 204, 0.05);
      border-radius: 8px;
    }

    .stat-value {
      font-size: 24px;
      font-weight: 700;
      color: var(--ustp-blue);
    }

    .stat-label {
      font-size: 14px;
      color: #666;
    }

    /* Voter Header - SIMILAR TO VOTERS PAGE */
    .voter-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .voter-title {
      color: var(--ustp-blue);
      font-size: 24px;
    }

    .search-box {
      display: flex;
      gap: 10px;
    }

    .search-input {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      width: 300px;
      font-size: 14px;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--ustp-blue);
    }

    .search-btn {
      background: var(--ustp-blue);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 0 20px;
      cursor: pointer;
      transition: 0.3s;
    }

    .search-btn:hover {
      background: var(--ustp-light-blue);
    }

    .add-voter-btn {
      background: var(--ustp-gold);
      color: var(--ustp-blue);
      border: none;
      border-radius: 6px;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-voter-btn:hover {
      background: #e6b800;
    }

    /* Organization Filter - SIMILAR TO VOTERS PAGE */
    .org-filter {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .org-filter-label {
      font-weight: 600;
      color: var(--ustp-blue);
    }

    .org-filter-select {
      padding: 8px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      background: white;
      font-size: 14px;
    }

    /* Voter Table - SIMILAR TO VOTERS PAGE */
    .voter-table-container {
      background: var(--ustp-white);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }

    .voter-table {
      width: 100%;
      border-collapse: collapse;
    }

    .voter-table th {
      background-color: var(--ustp-blue);
      color: var(--ustp-white);
      padding: 15px;
      text-align: left;
      font-weight: 600;
    }

    .voter-table td {
      padding: 15px;
      border-bottom: 1px solid #eee;
    }

    .voter-table tr:last-child td {
      border-bottom: none;
    }

    .voter-table tr:hover {
      background-color: rgba(0, 102, 204, 0.03);
    }

    .voter-actions {
      display: flex;
      gap: 8px;
    }

    .action-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
      transition: 0.3s;
    }

    .edit-btn {
      background: var(--ustp-gold);
      color: var(--ustp-blue);
    }

    .edit-btn:hover {
      background: #e6b800;
    }

    .delete-btn {
      background: #dc3545;
      color: white;
    }

    .delete-btn:hover {
      background: #c82333;
    }

    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-active {
      background: #d4edda;
      color: #155724;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-inactive {
      background: #f8d7da;
      color: #721c24;
    }

    .org-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 11px;
      margin: 2px;
      background: rgba(0, 102, 204, 0.1);
      color: var(--ustp-blue);
      border: 1px solid rgba(0, 102, 204, 0.2);
    }

    /* ---------- MODAL ---------- */

    .modal {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      background: rgba(0,0,0,0.45);
      justify-content: center;
      z-index: 9999;
      padding: 20px;
      box-sizing: border-box;
      -webkit-overflow-scrolling: touch;
    }

    .modal.show,
    .modal[aria-hidden="false"] {
      display: flex;
    }

    body.modal-open { overflow: hidden; }

    #voterModal .modal-content {
      width: 760px;
      max-width: calc(100% - 40px);
      max-height: 85vh;
      overflow-y: auto;
      background: #fff;
      border-radius: 12px;
      padding: 26px 28px;
      margin-left: 400px;
      position: relative;
      box-sizing: border-box;
      -webkit-overflow-scrolling: touch;
      margin-top: 40px;
    }

    #deleteModal .modal-content {
      width: 420px;
      max-width: calc(100% - 40px);
      padding: 20px;
      border-radius: 10px;
      background: #fff;
      box-shadow: 0 10px 24px rgba(0,0,0,0.2);
      position: relative;
      box-sizing: border-box;
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 20px;
      font-weight: 700;
      color: #0b355e;
    }

    .close-modal {
      background: transparent;
      border: 0;
      font-size: 20px;
      cursor: pointer;
      color: #223;
      padding: 6px;
      line-height: 1;
      border-radius: 6px;
      position: absolute;
      right: 14px;
      top: 12px;
      z-index: 1001;
    }

    .close-modal:hover { background: rgba(0,0,0,0.06); }

    @media (max-width: 720px) {
      #voterModal .modal-content,
      #deleteModal .modal-content {
        width: 100%;
        max-width: calc(100% - 32px);
        padding: 18px;
        border-radius: 8px;
      }
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 12px;
    }

    .form-group { margin-bottom: 12px; }
    .form-label { display:block; font-weight:600; color:#113; margin-bottom:6px; font-size:14px; }
    .form-input, select.form-input {
      width:100%;
      padding: 10px 12px;
      border-radius: 6px;
      border: 1px solid #d6e0ea;
      background: #fff;
      box-sizing: border-box;
      font-size: 14px;
      color: #0b2b4a;
    }

    .org-section {
      margin-top: 10px;
      padding: 14px;
      background: #f4fafc;
      border-radius: 8px;
      border: 1px solid #e6f0f6;
    }

    .checkbox-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .checkbox-item {
      display:flex;
      align-items:center;
      gap:8px;
      font-size: 14px;
    }

    .modal-actions {
      display:flex;
      gap:10px;
      justify-content: flex-end;
      margin-top: 16px;
    }

    .btn { padding:8px 14px; border-radius:6px; border:0; cursor:pointer; font-weight:600; }
    .btn-primary { background: #0b4d78; color: #fff; }
    .btn-secondary { background: #f2f4f6; color:#123; border:1px solid #d6e0ea; }

    @media (max-width: 720px) {
      .modal-content { width: 100%; max-height: 92vh; padding: 18px; }
      .form-row { grid-template-columns: 1fr; }
      .checkbox-group { grid-template-columns: repeat(1, minmax(0,1fr)); }
    }
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-primary {
      background: var(--ustp-blue);
      color: white;
    }

    .btn-primary:hover {
      background: var(--ustp-light-blue);
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background: #5a6268;
    }

    .btn-danger {
      background: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background: #c82333;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--ustp-blue);
    }

    .form-input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--ustp-blue);
    }

    .form-row {
      display: flex;
      gap: 15px;
    }

    .form-row .form-group {
      flex: 1;
    }

    .checkbox-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }

    .checkbox-item {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-right: 15px;
    }

    .checkbox-item input[type="checkbox"] {
      width: 18px;
      height: 18px;
    }

    .checkbox-item label {
      font-weight: normal;
    }

    .org-section {
      background: rgba(0, 102, 204, 0.05);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .org-section h3 {
      color: var(--ustp-blue);
      margin-bottom: 10px;
      font-size: 16px;
    }

    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 15px 25px;
      background-color: var(--ustp-blue);
      color: white;
      border-radius: 5px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 1001;
      display: flex;
      align-items: center;
      gap: 10px;
      transform: translateY(100px);
      opacity: 0;
      transition: transform 0.3s, opacity 0.3s;
    }

    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }

    .toast.success {
      background-color: #4caf50;
    }

    .toast.error {
      background-color: #f44336;
    }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 15px;
      color: #ccc;
    }

    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .logo-container h2, .nav-item a { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .voter-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      .search-box {
        width: 100%;
      }
      .search-input {
        width: 100%;
      }
      .department-header {
        flex-direction: column;
        text-align: center;
      }
      .department-stats {
        margin-left: 0;
        justify-content: center;
      }
      .voter-table-container {
        overflow-x: auto;
      }
      .form-row {
        flex-direction: column;
        gap: 0;
      }
      .checkbox-group {
        flex-direction: column;
        gap: 5px;
      }
    }
  </style>
</head>
<body>
  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
      <div class="logo"><img src="<?= esc(resolve_asset_path('eboto logo.png')) ?>" alt="Logo"></div>
    </div>
    <ul class="nav-menu">
      <li class="nav-item"><i class="fas fa-home"></i><a href="admin_dashboard.html">Dashboard</a></li>
      <li class="nav-item active"><i class="fas fa-user-graduate"></i><a href="register-voters.php">Voters</a></li>
      <li class="nav-item"><i class="fas fa-user-tie"></i><a href="register-officers.php">Officers</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item"><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
      <li class="nav-item"><i class="fas fa-file-alt"></i><a href="audit-reports.php">Reports</a></li>
      <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <!-- FIXED: no more hardcoded name. Filled by loadAdminProfile() from ../php/getAdmin.php -->
    <div class="user-profile" id="userProfile">
      <img id="sidebarAvatar" src="<?= esc(resolve_asset_path('default-avatar.jpg')) ?>" class="user-avatar" alt="User" />
      <div class="user-info">
        <h4 id="sidebarName">Administrator</h4>
        <p id="sidebarRole">Administrator</p>
      </div>
      <div class="user-dropdown" id="dropdown"><a href="../login.html" id="logoutBtn" onclick="logoutUser()">Logout</a></div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <div class="header">
      <h1>Voters Management</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <div class="department-header">
        <img src="<?= esc(resolve_asset_path($orgMeta['Logo_Url'] ?? 'soce.jpg')) ?>" class="department-avatar" alt="Department Logo" id="deptAvatar">
        <div class="department-info">
          <h2 id="deptName"><?= esc($orgMeta['Org_Name'] ?? 'Information Technology') ?></h2>
          <p id="deptDescription"><?= esc($orgMeta['Description'] ?? 'Department of Information Technology') ?></p>
        </div>
        <div class="department-stats">
          <div class="stat"><div class="stat-value" id="totalVoters"><?= count($voters) ?></div><div class="stat-label">Total Voters</div></div>
          <div class="stat"><div class="stat-value" id="pendingVoters"><?= (int)$pendingCount ?></div><div class="stat-label">Pending</div></div>
        </div>
      </div>

      <div class="voter-header">
        <h2 class="voter-title">Manage <?= esc($orgMeta['Org_Name'] ?? 'Voters') ?></h2>
        <div class="search-box">
          <input type="text" class="search-input" placeholder="Search voters..." id="searchInput">
          <button class="search-btn"><i class="fas fa-search"></i></button>
          <button class="add-voter-btn" id="addVoterBtn"><i class="fas fa-plus"></i> Add Voter</button>
        </div>
      </div>

      <!-- Organization Filter -->
      <div class="org-filter">
        <span class="org-filter-label">Filter by Organization:</span>
        <select class="org-filter-select" id="orgFilter">
          <option value="all">All Organizations</option>
          <?php foreach ($organizations as $oid => $org): ?>
            <option value="<?= esc($org['Org_ID']) ?>" <?= ($orgId && $orgId == $org['Org_ID']) ? 'selected' : '' ?>>
              <?= esc($org['Org_Name'] ?? $org['Org_Code'] ?? "Org #{$oid}") ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Year Level Filter -->
      <div class="org-filter">
        <span class="org-filter-label">Filter by Year Level:</span>
        <select class="org-filter-select" id="yearLevelFilter">
          <option value="all">All Year Levels</option>
          <option value="1">1st Year</option>
          <option value="2">2nd Year</option>
          <option value="3">3rd Year</option>
          <option value="4">4th Year</option>
        </select>
      </div>

      <!-- Voter Table -->
      <div class="voter-table-container">
        <table class="voter-table" id="voterTable">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Year Level</th>
              <th>Organizations</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="voterTableBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add/Edit Voter Modal -->
  <div class="modal" id="voterModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
      <div class="modal-header">
        <h2 id="modalTitle">Add New Voter</h2>
        <button class="close-modal" id="closeVoterModal" aria-label="Close">&times;</button>
      </div>

      <form id="voterForm" method="POST" action="../php/addVoter.php" autocomplete="off">
        <input type="hidden" id="voterId" name="voterId" value="">
        <input type="hidden" id="Org_ID" name="Org_ID" value="<?= $orgId ? (int)$orgId : '' ?>">

        <div class="form-row">
          <div class="form-group" style="flex:1; margin-right:16px;">
            <label class="form-label" for="student_id">Student ID</label>
            <input type="text" class="form-input" id="student_id" name="student_id" placeholder="e.g., 2023563780" required>
          </div>

          <div class="form-group" style="width:200px;">
            <label class="form-label" for="year_level">Year Level</label>
            <select class="form-input" id="year_level" name="year_level" required>
              <option value="">Select Year Level</option>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <input type="text" class="form-input" id="full_name" name="full_name" placeholder="e.g., Juan Tamad" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="course">Course</label>
          <select class="form-input" id="course" name="course" required>
            <option value="" disabled selected>Select Course</option>
            <option value="Information Technology">Information Technology</option>
            <option value="Energy System and Management">Energy System and Management</option>
            <option value="Manufacturing Engineering Technology">Manufacturing Engineering Technology</option>
            <option value="Naval Architecture and Marine Engineering">Naval Architecture and Marine Engineering</option>
            <option value="Technology Communication Management">Technology Communication Management</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" class="form-input" id="email" name="email" placeholder="e.g., juan.delacruz@ustp.edu.ph" required>
        </div>

        <div class="org-section">
          <h3>Organization Membership</h3>
          <p class="form-label">Select organizations this voter belongs to:</p>

          <div class="checkbox-group" style="max-height:320px; overflow:auto; padding:12px; border-radius:6px; background:#f7fbfe;">
            <?php foreach ($organizations as $oid => $org): ?>
              <div class="checkbox-item" style="margin-bottom:10px;">
                <input type="checkbox" id="org_<?= (int)$oid ?>" name="organizations[]" value="<?= (int)$oid ?>">
                <label for="org_<?= (int)$oid ?>"><?= esc($org['Org_Name'] ?? $org['Org_Code'] ?? "Org #{$oid}") ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="status">Status</label>
          <select class="form-input" id="status" name="status" required>
            <option value="">Select Status</option>
            <option value="Active">Active</option>
            <option value="Pending">Pending</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>

        <div class="modal-actions" style="display:flex; gap:8px; justify-content:flex-end; margin-top:18px;">
          <button type="button" id="cancelVoterBtn" class="btn btn-secondary">Cancel</button>
          <button type="submit" id="saveVoterBtn" class="btn btn-primary">Save Voter</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal" aria-hidden="true">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Confirm Deletion</h2>
        <button class="close-modal" id="closeDeleteModal" aria-label="Close">&times;</button>
      </div>
      <p>Are you sure you want to delete this voter? This action cannot be undone.</p>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>

<script>
  // client-side helpers and modal logic
  const initialVoters = <?= json_encode($voters ?: [], JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>;
  const organizations = <?= json_encode($organizations ?: [], JSON_HEX_TAG|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>;
  const currentOrgId = <?= $orgId ? (int)$orgId : 'null' ?>;

  function el(sel){ return document.querySelector(sel); }
  function escapeHtml(s){ return String(s||'').replace(/[&<>"'/]/g, function(ch){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;'}[ch]); }); }

  // avoid confirm-on-refresh
  if (window.history && window.history.replaceState) {
    window.history.replaceState({}, document.title, window.location.href);
  }

  // ---------------------------------------------
  // Admin profile loader (same endpoint as register-voters.php)
  // ---------------------------------------------
  const GET_ADMIN = '../php/getAdmin.php';

  async function loadAdminProfile() {
    try {
      const res = await fetch(GET_ADMIN, { cache: 'no-store', credentials: 'same-origin' });
      if (!res.ok) return;
      const json = await res.json();
      if (!json || !json.success) return;

      const nameEl = el('#sidebarName');
      const roleEl = el('#sidebarRole');
      const avEl   = el('#sidebarAvatar');

      if (nameEl) nameEl.textContent = json.name || 'Administrator';
      if (roleEl) roleEl.textContent = json.role || 'Administrator';
      if (avEl && json.avatar) {
        const fallback = avEl.getAttribute('src');
        avEl.onerror = function(){ avEl.onerror = null; avEl.src = fallback; };
        avEl.src = json.avatar;
      }
    } catch (err) {
      console.error('loadAdminProfile error', err);
    }
  }

  // user dropdown toggle (logout menu)
  (function wireUserDropdown(){
    const profile = el('#userProfile');
    const dropdown = el('#dropdown');
    if (!profile || !dropdown) return;
    profile.addEventListener('click', function(e){
      e.stopPropagation();
      dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
    });
    document.addEventListener('click', function(){ dropdown.style.display = 'none'; });
  })();

  document.addEventListener('DOMContentLoaded', loadAdminProfile);

  // ---------------------------------------------
  // Voter table
  // ---------------------------------------------
  function renderVoterTable(voters){
    const tbody = el('#voterTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    if (!voters || voters.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#666;">No voters found</td></tr>';
      return;
    }
    voters.forEach(v => {
      let orgNames = [];
      if (v.org_id || v.Org_ID) {
        const o = v.org_id || v.Org_ID;
        const key = String(o);
        if (organizations[key]) {
          const name = organizations[key].Org_Name || organizations[key].Org_Code || ('Org #' + key);
          orgNames.push(escapeHtml(name));
        } else {
          orgNames.push('Org #' + escapeHtml(key));
        }
      }
      const orgBadges = orgNames.length ? orgNames.join(', ') : 'None';
      const row = document.createElement('tr');
      const name = v.Name || v.name || '';
      row.innerHTML = `
        <td>${escapeHtml(v.Student_ID || v.student_id || '')}</td>
        <td>${escapeHtml(name)}</td>
        <td>${escapeHtml(v.Email || v.email || '')}</td>
        <td>${escapeHtml(v.Year_Level || v.year_level || '')}</td>
        <td>${orgBadges}</td>
        <td>${escapeHtml(v.status || '')}</td>
        <td>
          <div class="voter-actions">
            <button class="action-btn edit-btn" data-id="${escapeHtml(v.Student_ID||v.student_id||'')}"><i class="fas fa-edit"></i> Edit</button>
            <button class="action-btn delete-btn" data-id="${escapeHtml(v.Student_ID||v.student_id||'')}"><i class="fas fa-trash"></i> Delete</button>
          </div>
        </td>
      `;
      tbody.appendChild(row);
    });
  }
  renderVoterTable(initialVoters);

  // modal handlers
  const voterModal = el('#voterModal');
  const addVoterBtn = el('#addVoterBtn');
  const closeVoterModal = el('#closeVoterModal');
  const cancelVoterBtn = el('#cancelVoterBtn');
  const voterForm = el('#voterForm');
  const modalTitle = el('#modalTitle');

  if (addVoterBtn) {
    addVoterBtn.addEventListener('click', () => {
      if (voterForm) voterForm.reset();
      if (currentOrgId && el('#Org_ID')) el('#Org_ID').value = currentOrgId;
      if (modalTitle) modalTitle.textContent = 'Add New Voter';
      if (voterModal) { voterModal.style.display = 'block'; voterModal.setAttribute('aria-hidden', 'false'); }
      const firstInput = el('#student_id') || el('#full_name') || el('#email');
      if (firstInput) firstInput.focus();
    });
  }
  if (closeVoterModal) closeVoterModal.addEventListener('click', ()=>{ if (voterModal){voterModal.style.display='none'; voterModal.setAttribute('aria-hidden','true');}});
  if (cancelVoterBtn) cancelVoterBtn.addEventListener('click', ()=>{ if (voterModal){voterModal.style.display='none'; voterModal.setAttribute('aria-hidden','true');}});

  window.addEventListener('click', (e) => { if (e.target === voterModal) { voterModal.style.display = 'none'; voterModal.setAttribute('aria-hidden','true'); }});
  window.addEventListener('keydown', (e) => { if (e.key === 'Escape' && voterModal && voterModal.style.display === 'block') { voterModal.style.display = 'none'; voterModal.setAttribute('aria-hidden','true'); }});
</script>
</body>
</html>
