<?php
// org-voters.php - combined server block (put this at top of file; no output before this)
session_start();

/**
 * Convert DB Logo_Url values to reliable root-relative or absolute URLs.
 * Adjust $projectPrefix if your app sits under a different subfolder.
 */
function resolve_logo_url(string $dbPath = ''): string {
    $projectPrefix = '/eBotoni/eBotoni/'; // <- change only if your project folder differs
    $default = $projectPrefix . 'assests/default-logo.jpg';

    $p = trim((string)$dbPath);
    if ($p === '') return $default;

    // absolute http(s) -> return as-is
    if (preg_match('#^https?://#i', $p)) return $p;

    // already root-relative -> return as-is
    if ($p[0] === '/') return $p;

    // remove leading ../ or ./
    $clean = preg_replace('#^(\.\/|(\.\.\/)+)#', '', $p);

    // if DB value already contains the project prefix (like 'eBotoni/eBotoni/assests/soce.jpg'),
    // make it root-relative with leading slash
    if (strpos($clean, trim($projectPrefix, '/')) !== false) {
        return '/' . ltrim($clean, '/');
    }

    // otherwise treat as relative to project root and prefix project folder
    return rtrim($projectPrefix, '/') . '/' . ltrim($clean, '/');
}

// Allow GET override for testing (e.g. org-voters.php?org=123)
$ORG_ID   = isset($_GET['org']) && $_GET['org'] !== '' ? (int) $_GET['org'] : (isset($_SESSION['org_id']) ? (int) $_SESSION['org_id'] : null);
$ORG_NAME = $_SESSION['org_name'] ?? null;
$ORG_ROLE = $_SESSION['org_role'] ?? 'Organization User';

// ---------- Fetch students (mysqli) ----------
$students = [];
$mysqli = new mysqli('localhost','root','','eboto_db');
if ($mysqli->connect_errno) {
    error_log('DB connect failed: ' . $mysqli->connect_error);
} else {
    $mysqli->set_charset('utf8mb4');

    // detect if student table exists
    $res = $mysqli->query("SHOW TABLES LIKE 'student'");
    $hasStudentTable = ($res && $res->num_rows > 0);

    // detect membership & org tables
    $res = $mysqli->query("SHOW TABLES LIKE 'student_organization_members'");
    $hasMembersTable = ($res && $res->num_rows > 0);
    $res = $mysqli->query("SHOW TABLES LIKE 'student_organization'");
    $hasOrgTable = ($res && $res->num_rows > 0);

    if ($hasStudentTable) {
        // try to detect org column in student table
        $studentColsRes = $mysqli->query("SHOW COLUMNS FROM `student`");
        $studentCols = [];
        while ($c = $studentColsRes->fetch_assoc()) $studentCols[] = $c['Field'];
        $studentColsRes->free();

        $orgCol = null;
        $possibleOrgCols = ['Org_ID','org_id','organization_id','OrganizationID','orgId','OrgId'];
        foreach ($possibleOrgCols as $col) {
            if (in_array($col, $studentCols, true)) { $orgCol = $col; break; }
        }

        if ($orgCol && $ORG_ID) {
            $sql = "SELECT Student_ID, Name AS FullName, Email, Year_Level, `status` AS StudentStatus
                    FROM `student`
                    WHERE `$orgCol` = ?
                    ORDER BY Name ASC";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $ORG_ID);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $students[] = [
                        'voter_id'   => $r['Student_ID'],
                        'name'       => $r['FullName'],
                        'email'      => $r['Email'],
                        'student_id' => $r['Student_ID'],
                        'year_level' => $r['Year_Level'],
                        'status'     => ucfirst(strtolower($r['StudentStatus'] ?? 'Active'))
                    ];
                }
                $stmt->close();
            } else {
                error_log('Prepare failed (Org_ID path): ' . $mysqli->error);
            }
        } elseif ($hasMembersTable && $ORG_ID) {
            // membership fallback
            $sql = "SELECT s.Student_ID, s.Name AS FullName, s.Email, s.Year_Level, s.`status` AS StudentStatus
                    FROM student s
                    JOIN student_organization_members m ON m.student_id = s.Student_ID
                    WHERE m.org_id = ?
                    ORDER BY s.Name ASC";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $ORG_ID);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $students[] = [
                        'voter_id'   => $r['Student_ID'],
                        'name'       => $r['FullName'],
                        'email'      => $r['Email'],
                        'student_id' => $r['Student_ID'],
                        'year_level' => $r['Year_Level'],
                        'status'     => ucfirst(strtolower($r['StudentStatus'] ?? 'Active'))
                    ];
                }
                $stmt->close();
            } else {
                error_log('Prepare failed (members fallback): ' . $mysqli->error);
            }
        } else {
            // default: return all students
            $sql = "SELECT Student_ID, Name AS FullName, Email, Year_Level, `status` AS StudentStatus
                    FROM `student`
                    ORDER BY Name ASC";
            $res = $mysqli->query($sql);
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $students[] = [
                        'voter_id'   => $r['Student_ID'],
                        'name'       => $r['FullName'],
                        'email'      => $r['Email'],
                        'student_id' => $r['Student_ID'],
                        'year_level' => $r['Year_Level'],
                        'status'     => ucfirst(strtolower($r['StudentStatus'] ?? 'Active'))
                    ];
                }
                $res->free();
            } else {
                error_log('Query failed (all students): ' . $mysqli->error);
            }
        }
    } else {
        error_log('student table not found in DB');
    }

    $mysqli->close();
}

// ------------------------
// Now load organization profile (Logo_Url) into $profile using PDO and resolve_logo_url()
// ------------------------
$profile = [
  'name' => 'Organization User',
  'position' => $ORG_ROLE ?: 'Organization User',
  'email' => '',
  // default relative path to your default avatar (adjust if needed)
  'avatar' => '/eBotoni/eBotoni/assests/default-avatar.jpg'
];

if ($ORG_ID) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=eboto_db;charset=utf8mb4','root','',[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare("SELECT Org_Name, Email, Logo_Url FROM student_organization WHERE Org_ID = ? LIMIT 1");
        $stmt->execute([$ORG_ID]);
        $row = $stmt->fetch();

        if ($row) {
            $profile['name'] = $row['Org_Name'] ?: $profile['name'];
            $profile['email'] = $row['Email'] ?: $profile['email'];
            $profile['position'] = 'Organization User';

            if (!empty($row['Logo_Url'])) {
                $logo = trim($row['Logo_Url']);
                if (preg_match('#^https?://#i', $logo)) {
                    $profile['avatar'] = $logo;
                } else {
                    // Normalize relative path using resolve_logo_url() (preferred)
                    $profile['avatar'] = resolve_logo_url($logo);

                    // As a fallback if you want an absolute URL instead of root-relative, you could build absolute:
                    // $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    // $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    // $profile['avatar_abs'] = $scheme.'://'.rtrim($host, '/').$profile['avatar'];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Profile load error: " . $e->getMessage());
    }
}

// Expose server profile and students to client side (for existing JS to use)
// IMPORTANT: Output this script block before your page's other JS that relies on window.SERVER_PROFILE / window.STUDENTS
?>
<script>
  window.SERVER_PROFILE = <?php echo json_encode($profile, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
  window.SESSION_ORG_ID = <?php echo json_encode($ORG_ID); ?>;
  window.SESSION_ORG_NAME = <?php echo json_encode($ORG_NAME); ?>;
  window.SESSION_ORG_ROLE = <?php echo json_encode($ORG_ROLE); ?>;
  window.STUDENTS = <?php echo json_encode($students, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCE Student Organization - Organization Voters</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
      <div class="logo"><img src="../assests/suffage.png" alt="Logo" /></div>
    </div>
    <ul class="nav-menu">
      <li class="nav-item"><i class="fas fa-home"></i><a href="org-dashboard.php">Dashboard</a></li>
      <li class="nav-item active"><i class="fas fa-user-graduate"></i><a href="org-voters.php">Organization Voters</a></li>
      <li class="nav-item"><i class="fas fa-user-tie"></i><a href="manage-candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="manage-elections.php">Election Monitoring</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="election-results.php">Election Results</a></li>
      <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <div class="user-profile" onclick="toggleDropdown()">
<div class="user-avatar">
  <img id="sidebarAvatar" src="../assests/default-avatar.jpg" alt="Org Avatar" />
</div>

      <div class="user-info">
        <h4 id="orgUserName"><?php echo htmlspecialchars($ORG_NAME ?? 'Organization User'); ?></h4>
        <p id="orgPosition"><?php echo htmlspecialchars($ORG_ROLE); ?></p>
      </div>
      <div class="user-dropdown" id="dropdown">
        <a href="../login.html">Logout</a>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <div class="header">
      <div class="page-title">
        <h1>Organization Voters</h1>
        <p>View all registered voters categorized by year level</p>
      </div>
    </div>

    <div class="access-notice">
      <i class="fas fa-info-circle"></i>
      <p><strong>Stakeholder Access:</strong> You have limited access to your organization only.</p>
    </div>

    <!-- VOTER STAT CARDS -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info"><h3 id="totalVoters">0</h3><p>Total Voters</p></div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><h3 id="activeVoters">0</h3><p>Active Voters</p></div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="stat-info"><h3 id="pendingVoters">0</h3><p>Pending Verification</p></div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-times-circle"></i></div>
        <div class="stat-info"><h3 id="inactiveVoters">0</h3><p>Inactive Voters</p></div>
      </div>
    </div>

    <!-- YEAR SUMMARY -->
    <div class="year-summary" id="yearSummary"></div>

    <!-- MANAGEMENT HEADER -->
    <div class="management-header">
      <h2>Voter Management</h2>
      <div class="management-actions">
        <button class="btn btn-primary" id="exportBtn"><i class="fas fa-file-export"></i> Export</button>
      </div>
    </div>

    <!-- YEAR TABS -->
    <div class="year-tabs" id="yearTabs">
      <div class="year-tab active" data-year="all">All Voters</div>
      <div class="year-tab" data-year="1st Year">1st Year</div>
      <div class="year-tab" data-year="2nd Year">2nd Year</div>
      <div class="year-tab" data-year="3rd Year">3rd Year</div>
      <div class="year-tab" data-year="4th Year">4th Year</div>
    </div>

    <!-- VOTER TABLE -->
    <div class="voter-table-container">
      <table class="voter-table" id="voterTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Student ID</th>
            <th>Year Level</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="voterTableBody"></tbody>
      </table>
    </div>

    <div class="empty-state" id="emptyState" style="display:none;">
      <i class="fas fa-users"></i>
      <h3>No Voters Found</h3>
      <p>There are no voters in this category.</p>
    </div>

  </main>

  <!-- inject the server-side student rows directly into JS -->
  <script>
    // server-side injected student data
    const votersFromServer = <?php echo json_encode($students, JSON_UNESCAPED_UNICODE); ?> || [];
    console.log('Injected voters count:', votersFromServer.length);
  </script>

  <!-- TOAST -->
  <div class="toast" id="toast"></div>

  <!-- your page script (uses injected data instead of fetch) -->
  <script>


// ---- Year tab clicks + normalization helper ----
function normalizeYearLabel(v) {
  if (v == null) return '';
  v = String(v).trim().toLowerCase();
  // map common formats to the labels used in UI
  if (v === '1' || v === '1st' || v === '1st year' || v === 'first year') return '1st Year';
  if (v === '2' || v === '2nd' || v === '2nd year' || v === 'second year') return '2nd Year';
  if (v === '3' || v === '3rd' || v === '3rd year' || v === 'third year') return '3rd Year';
  if (v === '4' || v === '4th' || v === '4th year' || v === 'fourth year') return '4th Year';
  // return capitalized form for anything else
  return v.split(' ').map(w => w.charAt(0).toUpperCase()+w.slice(1)).join(' ');
}

function wireYearTabs() {
  const tabs = document.querySelectorAll('.year-tab');
  tabs.forEach(tab => {
    tab.style.cursor = 'pointer';
    tab.addEventListener('click', () => {
      // remove active from all tabs, add to clicked
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      // get requested year from data-year attribute
      let target = tab.getAttribute('data-year') || 'all';
      // If your data uses numeric year levels, normalize when filtering.
      // We'll ensure filteredVoters contains normalized year_level values.
      filteredVoters = (target === 'all')
        ? [...voters]
        : voters.filter(v => normalizeYearLabel(v.year_level) === target);

      // update UI
      renderTable();
    });
  });
}

// normalize all voters' year_level values once after data loads
function normalizeVoterYears() {
  voters = voters.map(v => ({ ...v, year_level: normalizeYearLabel(v.year_level) }));
  filteredVoters = [...voters];
}

// Call these during initialization (replace your existing DOMContentLoaded handler or call inside it)
document.addEventListener('DOMContentLoaded', () => {
  normalizeVoterYears();
  wireYearTabs();
  // ensure default tab is active and UI is rendered
  const defaultTab = document.querySelector('.year-tab.active') || document.querySelector('.year-tab[data-year="all"]');
  if (defaultTab) defaultTab.classList.add('active');
  computeAndRenderFromVoters(); // your existing function to compute stats/render
});

    // PAGE VARIABLES
    let voters = votersFromServer; // use injected data
    let filteredVoters = [...voters];
    let selectedYear = "all";

    // ORG_ID provided from PHP session
    const ORG_ID = <?php echo json_encode($ORG_ID ?? null); ?>;
    console.log("ORG_ID from PHP:", ORG_ID);

    // simple on-page debug area (hidden by default)
    (function ensureDebugArea(){
      if (!document.getElementById('debugArea')) {
        const d = document.createElement('div');
        d.id = 'debugArea';
        d.style.cssText = 'position:fixed;right:10px;bottom:10px;background:#fff;border:1px solid #ddd;padding:8px;z-index:9999;font-size:12px;max-width:320px;display:none;';
        document.body.appendChild(d);
      }
    })();

    // INITIALIZE UI from injected data
    function updateStats(stats) {
      document.getElementById("totalVoters").textContent = stats.total;
      document.getElementById("activeVoters").textContent = stats.active;
      document.getElementById("pendingVoters").textContent = stats.pending;
      document.getElementById("inactiveVoters").textContent = stats.inactive;
    }

    function computeAndRenderFromVoters() {
      // compute stats
      const stats = { total: voters.length, active: 0, pending: 0, inactive: 0 };
      voters.forEach(v => {
        const st = (v.status||'').toLowerCase();
        if (st === 'active') stats.active++;
        else if (st === 'pending') stats.pending++;
        else if (st === 'inactive' || st === 'disabled') stats.inactive++;
      });
      updateStats(stats);
      renderYearSummary();
      renderTable();
    }

    function renderYearSummary() {
      const container = document.getElementById("yearSummary");
      container.innerHTML = "";
      const years = ["1st Year","2nd Year","3rd Year","4th Year"];
      years.forEach(year => {
        const count = voters.filter(v => v.year_level === year).length;
        const activeCount = voters.filter(v => v.year_level === year && v.status === "Active").length;
        const pendingCount = voters.filter(v => v.year_level === year && v.status === "Pending").length;

        const card = document.createElement("div");
        card.className = "year-card";
        card.innerHTML = `
          <h3>${year}</h3>
          <div class="year-count">${count}</div>
          <div class="year-stats">${activeCount} Active • ${pendingCount} Pending</div>
        `;
        card.onclick = () => selectYear(year);
        container.appendChild(card);
      });
    }

    function selectYear(year) {
      selectedYear = year;
      filteredVoters = year === "all" ? [...voters] : voters.filter(v => v.year_level === year);
      renderTable();
    }

    function renderTable() {
      const tbody = document.getElementById("voterTableBody");
      tbody.innerHTML = "";

      if (filteredVoters.length === 0) {
        document.getElementById("emptyState").style.display = "block";
        return;
      }
      document.getElementById("emptyState").style.display = "none";

      filteredVoters.forEach(v => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${v.voter_id}</td>
          <td>${v.name}</td>
          <td>${v.email ?? ''}</td>
          <td>${v.student_id ?? ''}</td>
          <td>${v.year_level ?? ''}</td>
          <td><span class="status-badge status-${(v.status||'').toLowerCase()}">${v.status}</span></td>
        `;
        tbody.appendChild(row);
      });
    }

    function showToast(msg, type="info") {
      const t = document.getElementById("toast");
      if (!t) return console.log('Toast:', msg);
      t.textContent = msg;
      t.style.display = "block";
      setTimeout(()=> t.style.display = "none", 3500);
    }


    document.addEventListener('DOMContentLoaded', () => {
      computeAndRenderFromVoters();
    });

 
  </script>
  <script>
document.addEventListener("DOMContentLoaded", () => {

    const p = window.SERVER_PROFILE || {};
    const avatarEl = document.getElementById("sidebarAvatar");
    const nameEl   = document.getElementById("orgUserName");
    const roleEl   = document.getElementById("orgPosition");

    function resolveAvatar(url) {
        const fallback = "/eBotoni/eBotoni/assests/default-avatar.jpg";

        if (!url) return fallback;

        url = url.trim();

        if (url.startsWith("http://") || url.startsWith("https://"))
            return url;

        // Fix "../uploads/filename.png"
        url = url.replace(/^(\.\.\/)+/, "");

        return window.location.origin + "/" + url;
    }

    try {
        if (avatarEl) {
            const finalUrl = resolveAvatar(p.avatar || p.Logo_Url || "");
            avatarEl.src = finalUrl;
            avatarEl.onerror = () => avatarEl.src = "/eBotoni/eBotoni/assests/default-avatar.jpg";
            console.log("Avatar applied:", finalUrl);
        }

        if (nameEl && p.name)  nameEl.textContent = p.name;
        if (roleEl && p.position) roleEl.textContent = p.position;

    } catch (e) {
        console.error("Apply profile failed:", e);
    }
});
</script>

  
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

    /* SIDEBAR - EXACTLY THE SAME AS DASHBOARD */
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

    /* NAVIGATION - EXACTLY THE SAME AS DASHBOARD */
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

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--ustp-white);
            color: var(--ustp-dark-gray);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 400px;
            border-left: 4px solid var(--ustp-blue);
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success {
            border-left-color: var(--ustp-green);
        }

        .toast.error {
            border-left-color: var(--ustp-red);
        }

        .toast.warning {
            border-left-color: var(--ustp-orange);
        }

        .toast.info {
            border-left-color: var(--ustp-light-blue);
        }

        .toast-icon {
            font-size: 1.2rem;
        }

        .toast.success .toast-icon {
            color: var(--ustp-green);
        }

        .toast.error .toast-icon {
            color: var(--ustp-red);
        }

        .toast.warning .toast-icon {
            color: var(--ustp-orange);
        }

        .toast.info .toast-icon {
            color: var(--ustp-light-blue);
        }

        .toast-message {
            font-weight: 500;
            flex: 1;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s ease;
        }

        .toast-close:hover {
            color: var(--ustp-dark-gray);
        }

        /* Access Notice */
        .access-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .access-notice i {
            color: #856404;
            font-size: 1.2rem;
        }

        .access-notice p {
            color: #856404;
            margin: 0;
            font-size: 0.9rem;
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

        .stat-icon.purple {
            background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%);
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

        /* Year Level Summary */
        .year-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .year-card {
            background: var(--ustp-white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .year-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .year-card.active {
            border-color: var(--ustp-blue);
            background: rgba(0, 51, 102, 0.05);
        }

        .year-card h3 {
            color: var(--ustp-blue);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .year-count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--ustp-blue);
            margin-bottom: 5px;
        }

        .year-stats {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        /* Voter Management */
        .voter-management {
            background: var(--ustp-white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .management-header h2 {
            color: var(--ustp-blue);
            font-size: 1.5rem;
        }

        .management-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e9ecef;
            color: var(--ustp-dark-gray);
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn-success {
            background: var(--ustp-green);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        /* Year Level Tabs */
        .year-tabs {
            display: flex;
            background: #e9ecef;
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .year-tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
            min-width: 120px;
        }

        .year-tab:hover {
            background: rgba(0, 51, 102, 0.1);
        }

        .year-tab.active {
            background: var(--ustp-blue);
            color: white;
        }

        /* Voter Table */
        .voter-table-container {
            overflow-x: auto;
        }

        .voter-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .voter-table th {
            background: #e9ecef;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--ustp-blue);
            border-bottom: 2px solid var(--ustp-blue);
        }

        .voter-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .voter-table tr:hover {
            background: rgba(0, 51, 102, 0.03);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.1);
            color: var(--ustp-green);
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.1);
            color: var(--ustp-red);
        }

        .status-pending {
            background: rgba(255, 204, 0, 0.1);
            color: var(--ustp-gold);
        }

        /* Read-only notice */
        .read-only-notice {
            background: rgba(0, 51, 102, 0.05);
            border: 1px solid var(--ustp-blue);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .read-only-notice i {
            color: var(--ustp-blue);
            font-size: 1.2rem;
        }

        .read-only-notice p {
            color: var(--ustp-blue);
            margin: 0;
            font-size: 0.9rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--ustp-white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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
            .year-tabs {
                flex-direction: column;
            }
            
            .year-tab {
                min-width: auto;
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
            
            .stats-container {
                grid-template-columns: 1fr;
            }

            .year-summary {
                grid-template-columns: 1fr 1fr;
            }

            .management-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .management-actions {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 576px) {
            .year-summary {
                grid-template-columns: 1fr;
            }

            .management-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .toast {
                left: 20px;
                right: 20px;
                max-width: none;
            }
        }
    </style>
</head>

<body>
</body>
</html>