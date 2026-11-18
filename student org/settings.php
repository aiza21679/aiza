<?php
// top of file (no output before this)
session_start();

$ORG_ID = isset($_SESSION['org_id']) ? (int)$_SESSION['org_id'] : null;

// default fallback
$profile = [
  'name' => 'Organization User',
  'position' => 'Organization User',
  'email' => '',
  'avatar' => '/eBotoni/eBotoni/assests/default-logo.jpg'
];

if ($ORG_ID) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=eboto_db;charset=utf8mb4','root','',[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->prepare("SELECT Org_Name, Email, Logo_Url FROM student_organization WHERE Org_ID = ?");
        $stmt->execute([$ORG_ID]);
        $row = $stmt->fetch();

        if ($row) {
            $profile['name'] = $row['Org_Name'] ?: $profile['name'];
            $profile['email'] = $row['Email'] ?: $profile['email'];
            $profile['position'] = $row['Org_Name'] ? 'Organization User' : $profile['position']; // or store role in DB
            // make sure avatar URL is absolute or relative to project root
            $profile['avatar'] = $row['Logo_Url'] ? $row['Logo_Url'] : $profile['avatar'];
        }
    } catch (Exception $e) {
        error_log("Profile load error: " . $e->getMessage());
        // keep defaults on error
    }
}

// Expose to javascript
?>
<script>
  window.SERVER_PROFILE = <?php echo json_encode($profile, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
  window.SESSION_ORG_ID = <?php echo json_encode($ORG_ID); ?>;
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCE Student Organization - Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
  <div class="logo-container">
    <div class="logo"><img src="../assests/suffage.png" alt="Logo"></div>
  </div>

  <ul class="nav-menu">
    <li class="nav-item"><i class="fas fa-home"></i><a href="org-dashboard.php">Dashboard</a></li>
    <li class="nav-item"><i class="fas fa-user-graduate"></i><a href="org-voters.php">Organization Voters</a></li>
    <li class="nav-item"><i class="fas fa-user-tie"></i><a href="manage-candidates.php">Candidates</a></li>
    <li class="nav-item"><i class="fas fa-user-tag"></i><a href="manage-elections.php">Election Monitoring</a></li>
    <li class="nav-item"><i class="fas fa-check-circle"></i><a href="election-results.php">Election Results</a></li>
    <li class="nav-item active"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
  </ul>

  <div class="user-profile" id="userProfile">
    <img src="https://ui-avatars.com/api/?name=Maria+Santos&background=003366&color=fff" class="user-avatar" id="profileAvatar" />
    <div class="user-info">
      
      <h4 id="sidebarName">Maria Santos</h4>
      <p id="sidebarRole">SOCE President</p>
    </div>
    <div class="user-dropdown" id="dropdown">
      <a href="../login.html" id="logoutBtn">Logout</a>
    </div>
  </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-content" id="mainContent">

  <div class="header">
    <div class="page-title">
      <h1>Settings</h1>
      <p>Manage your profile and organization controls</p>
    </div>
    <div class="header-actions">
      <div class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
      </div>
    </div>
  </div>

  <!-- Access Notice -->
  <div class="access-notice">
    <i class="fas fa-info-circle"></i>
    <p><strong>Stakeholder Access:</strong> You have limited access to SOCE organization data only.</p>
  </div>

  <!-- SETTINGS WRAPPER -->
  <div class="settings-container">

    <!-- PROFILE SETTINGS -->
    <div class="settings-section">
      <h3>Profile Settings</h3>

      <div class="profile-section">
        <img src="https://ui-avatars.com/api/?name=Maria+Santos&background=003366&color=fff&size=100"
             class="profile-avatar" id="profileAvatarDisplay">

        <div class="profile-info">
          <h3 id="profileNameDisplay">Maria Santos</h3>
          <p id="profilePositionDisplay">SOCE President • maria.santos@university.edu</p>

          <label class="upload-btn">
            <i class="fas fa-camera"></i> Change Profile Picture
            <input type="file" class="file-upload" id="avatarUpload" accept="image/*">
          </label>
        </div>
      </div>

      <div class="settings-option">
        <div class="option-info">
          <h4>Personal Information</h4>
          <p>Update your name, email, and contact information</p>
        </div>
        <div class="option-actions">
          <button class="btn btn-primary" id="editProfileBtn">
            <i class="fas fa-edit"></i> Edit Profile
          </button>
        </div>
      </div>

      <div class="settings-option">
        <div class="option-info">
          <h4>Change Password</h4>
          <p>Update your password for security</p>
        </div>
        <div class="option-actions">
          <button class="btn btn-secondary" id="changePasswordBtn">
            <i class="fas fa-key"></i> Change Password
          </button>
        </div>
      </div>
    </div>

    <!-- NOTE: Organization Settings section removed as requested -->

  </div>
</div>

<!-- EDIT PROFILE MODAL -->
<div class="modal" id="editProfileModal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Profile</h3>
      <button class="close-modal" id="closeEditProfileModal">&times;</button>
    </div>

    <div class="modal-body">
      <form id="editProfileForm" enctype="multipart/form-data" onsubmit="return false;">
        <label>Full Name</label>
        <input type="text" id="profileName" required>

        <label>Email Address</label>
        <input type="email" id="profileEmail" required>

        <label>Phone Number</label>
        <input type="text" id="profilePhone">

        <label>Position</label>
        <input type="text" id="profilePosition" required>

        <label>Bio</label>
        <textarea id="profileBio"></textarea>

        <!-- Hidden preview avatar display (optional) -->
        <input type="hidden" id="currentAvatarUrl" value="">
      </form>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelEditProfile">Cancel</button>
      <button class="btn btn-primary" id="saveProfile">Save Changes</button>
    </div>
  </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal" id="changePasswordModal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Change Password</h3>
      <button class="close-modal" id="closeChangePasswordModal">&times;</button>
    </div>

    <div class="modal-body">
      <form id="changePasswordForm" onsubmit="return false;">
        <label>Current Password</label>
        <input type="password" id="currentPassword" required>

        <label>New Password</label>
        <input type="password" id="newPassword" required>

        <label>Confirm Password</label>
        <input type="password" id="confirmPassword" required>
      </form>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelChangePassword">Cancel</button>
      <button class="btn btn-primary" id="savePassword">Change Password</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast" style="display:none;"></div>

<script>
class SettingsManager {

  constructor() {
    this.settings = this.loadSettings();
    this.init();
  }

loadSettings() {
  // client-side defaults (kept for fallback)
  const defaults = {
    profile: {
      name: "Maria Santos",
      email: "maria.santos@university.edu",
      phone: "+1 (555) 123-4567",
      position: "SOCE President",
      bio: "As the SOCE President, I'm dedicated to improving student engagement.",
      avatar: "https://ui-avatars.com/api/?name=Maria+Santos&background=003366&color=fff&size=100"
    }
  };

  // server profile injected by PHP (window.SERVER_PROFILE)
  const server = (window.SERVER_PROFILE && typeof window.SERVER_PROFILE === 'object')
    ? window.SERVER_PROFILE
    : null;

  // prefer server values -> then saved localStorage -> then defaults
  try {
    const saved = JSON.parse(localStorage.getItem("soceSettings")) || null;
    if (saved && saved.profile) {
      // merge priority: server > saved > defaults
      return {
        profile: Object.assign({}, defaults.profile, saved.profile, server || {})
      };
    } else {
      return {
        profile: Object.assign({}, defaults.profile, server || {})
      };
    }
  } catch (e) {
    // if parsing fails, use server or defaults
    return {
      profile: Object.assign({}, defaults.profile, server || {})
    };
  }
}


  save() {
    localStorage.setItem("soceSettings", JSON.stringify(this.settings));
  }

  init() {
    // UI hooks
    const menuToggle = document.getElementById("menuToggle");
    if (menuToggle) menuToggle.addEventListener("click", () => {
      document.getElementById("sidebar").classList.toggle("active");
    });

    const userProfile = document.getElementById("userProfile");
    if (userProfile) userProfile.addEventListener("click", (e) => {
      const d = document.getElementById("dropdown");
      d.style.display = d.style.display === "block" ? "none" : "block";
    });

    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) logoutBtn.addEventListener("click", e => {
      e.preventDefault();
      this.toast("Logging out...");
      setTimeout(() => (window.location = "../login.html"), 800);
    });

    // buttons
    const editProfileBtn = document.getElementById("editProfileBtn");
    if (editProfileBtn) editProfileBtn.addEventListener("click", () => this.openEditModal());

    const changePasswordBtn = document.getElementById("changePasswordBtn");
    if (changePasswordBtn) changePasswordBtn.addEventListener("click", () => this.openPasswordModal());

    const avatarUpload = document.getElementById("avatarUpload");
    if (avatarUpload) avatarUpload.addEventListener("change", e => this.avatarPreview(e));

    const closeEdit = document.getElementById("closeEditProfileModal");
    if (closeEdit) closeEdit.addEventListener("click", () => this.closeModal("editProfileModal"));

    const cancelEdit = document.getElementById("cancelEditProfile");
    if (cancelEdit) cancelEdit.addEventListener("click", () => this.closeModal("editProfileModal"));

    const saveBtn = document.getElementById("saveProfile");
    if (saveBtn) saveBtn.addEventListener("click", () => this.saveProfileToServer());

    const closeChangePass = document.getElementById("closeChangePasswordModal");
    if (closeChangePass) closeChangePass.addEventListener("click", () => this.closeModal("changePasswordModal"));

    const cancelPass = document.getElementById("cancelChangePassword");
    if (cancelPass) cancelPass.addEventListener("click", () => this.closeModal("changePasswordModal"));

    const savePass = document.getElementById("savePassword");
    if (savePass) savePass.addEventListener("click", () => this.changePassword());

    // close dropdown when clicking outside
    document.addEventListener('click', (ev) => {
      const up = document.getElementById('userProfile');
      const dd = document.getElementById('dropdown');
      if (up && dd && !up.contains(ev.target)) dd.style.display = 'none';
    });

    // apply settings to UI
    this.apply();
  }

  apply() {
    const p = this.settings.profile;
    const displayName = document.getElementById("profileNameDisplay");
    if (displayName) displayName.textContent = p.name;

    const posDisplay = document.getElementById("profilePositionDisplay");
    if (posDisplay) posDisplay.textContent = `${p.position} • ${p.email}`;

    // two avatar elements: sidebar and profile section
    const sidebarAvatar = document.getElementById("profileAvatar");
    if (sidebarAvatar) sidebarAvatar.src = p.avatar;

    const profileAvatarDisplay = document.getElementById("profileAvatarDisplay");
    if (profileAvatarDisplay) profileAvatarDisplay.src = p.avatar;

    const sidebarName = document.getElementById("sidebarName");
    if (sidebarName) sidebarName.textContent = p.name;

    const sidebarRole = document.getElementById("sidebarRole");
    if (sidebarRole) sidebarRole.textContent = p.position;
  }

  openEditModal() {
    const p = this.settings.profile;
    document.getElementById("profileName").value = p.name || "";
    document.getElementById("profileEmail").value = p.email || "";
    document.getElementById("profilePhone").value = p.phone || "";
    document.getElementById("profilePosition").value = p.position || "";
    document.getElementById("profileBio").value = p.bio || "";
    document.getElementById("currentAvatarUrl").value = p.avatar || "";
    document.getElementById("editProfileModal").style.display = "flex";
  }

  openPasswordModal() {
    document.getElementById("changePasswordForm").reset();
    document.getElementById("changePasswordModal").style.display = "flex";
  }

  closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = "none";
  }

  avatarPreview(e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = evt => {
      const preview1 = document.getElementById("profileAvatarDisplay");
      const preview2 = document.getElementById("profileAvatar");
      if (preview1) preview1.src = evt.target.result;
      if (preview2) preview2.src = evt.target.result;
    };
    reader.readAsDataURL(file);
  }

  // ----- NEW: server-backed save function -----
  async saveProfileToServer() {
    const nameEl = document.getElementById("profileName");
    const emailEl = document.getElementById("profileEmail");
    const phoneEl = document.getElementById("profilePhone");
    const positionEl = document.getElementById("profilePosition");
    const bioEl = document.getElementById("profileBio");
    const avatarInput = document.getElementById("avatarUpload");

    const name = nameEl ? nameEl.value.trim() : "";
    const email = emailEl ? emailEl.value.trim() : "";
    const phone = phoneEl ? phoneEl.value.trim() : "";
    const position = positionEl ? positionEl.value.trim() : "";
    const bio = bioEl ? bioEl.value.trim() : "";
    const avatarFile = (avatarInput && avatarInput.files && avatarInput.files[0]) ? avatarInput.files[0] : null;

    // Basic client validation
    if (!name) return this.toast("Full name is required.", "error");
    if (!email) return this.toast("Email is required.", "error");

    // Build FormData
    const fd = new FormData();
    fd.append("name", name);
    fd.append("email", email);
    fd.append("phone", phone);
    fd.append("position", position);
    fd.append("bio", bio);
    if (avatarFile) fd.append("avatar", avatarFile);

    // Endpoint - adjust path if your PHP is elsewhere
    const API_URL = "/eBotoni/eBotoni/student_org/save_profile.php";

    try {
      const res = await fetch(API_URL, {
        method: "POST",
        body: fd,
        credentials: "same-origin"
      });

      const text = await res.text();

      // If PHP printed warnings and returned HTML, bail with helpful message
      if (text.trim().startsWith("<")) {
        console.error("Server returned HTML (likely PHP error):", text);
        this.toast("Server error while saving (check PHP error logs).", "error");
        return;
      }

      let json;
      try {
        json = JSON.parse(text);
      } catch (err) {
        console.error("Invalid JSON from save_profile:", text);
        this.toast("Invalid response from server.", "error");
        return;
      }

      if (!json || json.success !== true) {
        const message = (json && (json.message || json.error)) ? (json.message || json.error) : "Failed to save profile.";
        this.toast(message, "error");
        console.error("save_profile returned error JSON:", json);
        return;
      }

      // Success - JSON expected to include updated data in json.data
      const updated = json.data || {};

      // Update local settings and UI
      this.settings.profile.name = updated.name || name;
      this.settings.profile.email = updated.email || email;
      this.settings.profile.phone = updated.phone || phone;
      this.settings.profile.position = updated.position || position;
      this.settings.profile.bio = updated.bio || bio;
      if (updated.avatar_url) this.settings.profile.avatar = updated.avatar_url;
      // if server didn't return avatar_url but user uploaded, keep previewed data URL (already applied)

      this.save();
      this.apply();
      this.toast("Profile updated successfully!", "success");
      this.closeModal("editProfileModal");
    } catch (err) {
      console.error("Network/Save error:", err);
      this.toast("Network or server error while saving.", "error");
    }
  }

  // keep previous simple local-only saveProfile for compatibility (calls server-backed save)
  saveProfile() {
    // backwards-compatible method in case other code calls this
    this.saveProfileToServer();
  }

  changePassword() {
    const n = document.getElementById("newPassword") ? document.getElementById("newPassword").value : "";
    const c = document.getElementById("confirmPassword") ? document.getElementById("confirmPassword").value : "";
    if (n !== c) return this.toast("Passwords do not match!", "error");
    // If you have a password change API, call it here. For now show toast and close.
    this.toast("Password changed (client-side demo).", "success");
    this.closeModal("changePasswordModal");
  }

  toast(msg, type = "info") {
    const t = document.getElementById("toast");
    if (!t) {
      // fallback
      console.log(type.toUpperCase(), msg);
      return;
    }
    t.textContent = msg;
    t.style.display = "block";
    // optional: style by type (success/error) via class if you later add CSS
    clearTimeout(this._toastTimeout);
    this._toastTimeout = setTimeout(() => {
      t.style.display = "none";
    }, 2200);
  }
}

document.addEventListener("DOMContentLoaded", () => new SettingsManager());
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

    /* SIDEBAR - EXACTLY THE SAME AS OTHER PAGES */
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
    /* Button styles (edit / cancel / save) */
#editProfileBtn,
.btn--save,
.btn--cancel {
  -webkit-appearance: none;
  appearance: none;
  border: 0;
  cursor: pointer;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  border-radius: 8px;
  padding: .6rem .9rem;
  font-size: .95rem;
  transition: transform .08s ease, box-shadow .12s ease, opacity .12s ease;
}

#editProfileBtn { background: #003366; color: #fff; box-shadow: 0 6px 18px rgba(0, 0, 0, .08); }
#editProfileBtn:hover { transform: translateY(-1px); }

.btn--save { background: #003366; color: #fff; }
.btn--save:hover { filter: brightness(.96); }

.btn--cancel { background: #eef2f6; color: #213242; box-shadow: inset 0 -1px 0 rgba(0,0,0,.02); }
.btn--cancel:hover { filter: brightness(.98); }

/* Modal overlay and base */
.modal {
  position: fixed;
  inset: 0;
  display: none;           /* toggled via JS (e.g. add/remove class 'show' or set display:flex) */
  align-items: center;
  justify-content: center;
  background: rgba(6, 18, 36, 0.56);
  z-index: 1200;
  padding: 24px;
  -webkit-overflow-scrolling: touch;
}

/* show helper (JS can add/remove this class) */
.modal.show { display: flex; }

/* Modal panel */
.modal-content {
  width: 560px;
  max-width: 96%;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 18px 40px rgba(10, 20, 40, 0.35);
  overflow: hidden;
  transform: translateY(6px);
  transition: transform .16s cubic-bezier(.2,.9,.3,1), opacity .14s ease;
}

/* header / body / footer */
.modal-header {
  background: #003366;
  color: #fff;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
}

.close-modal {
  background: transparent;
  border: 0;
  color: rgba(255,255,255,.95);
  font-size: 1.25rem;
  cursor: pointer;
  line-height: 1;
  padding: 6px;
  border-radius: 6px;
}
.close-modal:hover { background: rgba(255,255,255,.06); }

.modal-body {
  padding: 18px;
  background: #fff;
}

/* footer buttons alignment */
.modal-footer {
  padding: 12px 16px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  background: #fff;
  border-top: 1px solid #f2f5f8;
}

/* Form inputs inside modal */
.modal-body label { display: block; margin-bottom: 6px; font-weight: 600; color: #213242; }
.modal-body input[type="text"],
.modal-body input[type="email"],
.modal-body input[type="password"],
.modal-body textarea {
  width: 100%;
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #e6eef6;
  font-size: .95rem;
  color: #23303a;
  margin-bottom: 12px;
  background: #fff;
}
.modal-body textarea { min-height: 88px; resize: vertical; }

/* Small screens */
@media (max-width: 520px) {
  .modal-content { width: 100%; margin: 0 6px; }
  .modal-body { padding: 14px; }
  .modal-footer { padding: 10px 14px; flex-direction: column-reverse; gap: 8px; align-items: stretch; }
  .btn--save, .btn--cancel { width: 100%; justify-content: center; }
}

/* Accessible focus outlines */
#editProfileBtn:focus, .close-modal:focus, .btn--save:focus, .btn--cancel:focus,
.modal-body input:focus, .modal-body textarea:focus {
  outline: 3px solid rgba(0, 51, 102, 0.12);
  outline-offset: 2px;
}

/* Toast (optional, small helper) */
.toast {
  position: fixed;
  right: 20px;
  bottom: 20px;
  background: #003366;
  color: #fff;
  padding: 10px 14px;
  border-radius: 8px;
  display: none;
  z-index: 1300;
  box-shadow: 0 8px 24px rgba(0,0,0,.18);
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

    /* NAVIGATION - EXACTLY THE SAME AS OTHER PAGES */
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
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .user-info h4 {
            font-size: 1rem;
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
            background: var(--primary);
            border: 1px solid var(--accent);
            border-radius: 8px;
            overflow: hidden;
            width: 150px;
            z-index: 10;
        }

        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: var(--light);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .user-dropdown a:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .page-title h1 {
            font-size: 1.8rem;
           color: var(--gray);
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        /* Page Content */
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

        /* Settings Container */
        .settings-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .settings-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .settings-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .settings-section h3 {
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
        }

        .settings-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .settings-option:last-child {
            border-bottom: none;
        }

        .option-info {
            flex: 1;
        }

        .option-info h4 {
            color: var(--dark);
            margin-bottom: 5px;
        }

        .option-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .option-actions {
            display: flex;
            align-items: center;
            gap: 15px;
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
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #3d9a35;
        }

        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }

        .btn-warning:hover {
            background: #e6b800;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Profile Section */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
        }

        .profile-info h3 {
            color: var(--primary);
            margin-bottom: 5px;
        }

        .profile-info p {
            color: var(--gray);
            margin-bottom: 10px;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: var(--primary);
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
            background-color: var(--success);
        }

        .toast.error {
            background-color: var(--danger);
        }

        .toast.warning {
            background-color: var(--warning);
            color: var(--dark);
        }

       /* Modal Header */
.modal-header {
    background: #0b3b66;   /* deep navy blue like your screenshot */
    color: #fff;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}

/* Close (X) button */
.close-modal {
    background: transparent;
    border: none;
    color: #ffffff;        /* <- change this to change X color */
    font-size: 1.4rem;
    cursor: pointer;
    padding: 6px;
    line-height: 1;
}
.close-modal:hover {
    opacity: 0.8;
}
/* Modal Panel */
.modal-content {
    width: 560px;
    max-width: 95%;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,0.25);
}

/* Modal Body */
.modal-body {
    padding: 20px;
}

.modal-body label {
    display: block;
    margin: 12px 0 6px;
    font-weight: 600;
    color: #1a3761;   /* label color */
}

.modal-body input,
.modal-body textarea {
    width: 100%;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    font-size: 0.95rem;
    background: #fff;
}

.modal-body textarea {
    min-height: 100px;
    resize: vertical;
}

/* Modal Footer */
.modal-footer {
    padding: 14px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

/* Buttons */
.btn-secondary {
    background: #eef1f4;
    color: #1f2d3d;
    border: 1px solid #d0d4d9;
}

.btn-secondary:hover {
    background: #e3e7eb;
}

.btn-primary {
    background: #0b3b66;   /* deep navy like screenshot */
    color: #fff;
}

.btn-primary:hover {
    filter: brightness(1.05);
}
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal[style*="display: flex"] {
    display: flex !important;
}


        /* Confirmation Modal Styles */
        .confirmation-modal .modal-content {
            width: 400px;
        }

        .confirmation-icon {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .confirmation-icon.warning {
            color: var(--warning);
        }

        .confirmation-icon.danger {
            color: var(--danger);
        }

        .confirmation-icon.info {
            color: var(--primary);
        }

        .confirmation-message {
            text-align: center;
            margin-bottom: 20px;
        }

        .confirmation-message h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .confirmation-input {
            margin: 15px 0;
        }

        .confirmation-input input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 0.9rem;
            text-align: center;
        }

        .confirmation-input input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* File Upload */
        .file-upload {
            display: none;
        }

        .upload-btn {
            background: var(--light-gray);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-block;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .settings-option {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .option-actions {
                width: 100%;
                justify-content: space-between;
            }

            .profile-section {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .modal-footer {
                flex-direction: column;
            }
        }

        /* Dark Mode Styles */
        body.dark-mode {
            --primary: #1a365d;
            --secondary: #2c5282;
            --light: #2d3748;
            --dark: #f7fafc;
            --light-gray: #4a5568;
            background-color: #1a202c;
        }

        body.dark-mode .settings-container,
        body.dark-mode .header {
            background: #2d3748;
            color: #f7fafc;
        }

        body.dark-mode .option-info h4 {
            color: #f7fafc;
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group select,
        body.dark-mode .form-group textarea {
            background: #4a5568;
            border-color: #718096;
            color: #f7fafc;
        }
    </style>
</body>
</html>
