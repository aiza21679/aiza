<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Settings - eBOTO</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* ===== Core theme variables (matches your system colors) ===== */
    :root{
      --ustp-blue:#003366;
      --ustp-light-blue:#0066cc;
      --ustp-gold:#ffcc00;
      --ustp-white:#ffffff;
      --ustp-light-gray:#f5f5f5;
      --ustp-dark-gray:#333333;
      --sidebar-width:250px;
      --header-height:60px;
      --card-radius:10px;
      --transition-fast:180ms;
      --transition-medium:280ms;
    }

    /* ===== Minimal reset & font ===== */
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif}
    body{background:var(--ustp-light-gray);color:var(--ustp-dark-gray);display:flex;min-height:100vh}

    /* ===== Sidebar (kept as your style) ===== */
    .sidebar{
      width:var(--sidebar-width);
      background:linear-gradient(180deg,var(--ustp-blue) 0%,var(--ustp-light-blue) 100%);
      color:var(--ustp-white);
      padding:20px 0;
      display:flex;
      flex-direction:column;
      align-items:center;
      position:fixed;top:0;left:0;height:100vh;
      box-shadow:2px 0 10px rgba(0,0,0,0.1);z-index:100;transition:0.3s ease;
    }
    .logo-container{width:100%;text-align:center;padding:0 20px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px}
    .logo{width:130px;height:130px;border-radius:50%;overflow:hidden;margin:0 auto 10px auto;border:4px solid var(--ustp-gold);background:var(--ustp-white)}
    .logo img{width:100%;height:100%;object-fit:cover}
    .nav-menu{flex:1;list-style:none;padding:0;width:100%}
    .nav-item{padding:14px 25px;display:flex;align-items:center;gap:12px;cursor:pointer;transition:all .3s;font-size:15px}
    .nav-item:hover{background:#004b99}
    .nav-item.active{background:var(--ustp-gold);color:var(--ustp-blue);font-weight:700;border-left:4px solid var(--ustp-gold)}
    .nav-item i{width:20px;text-align:center}
    .nav-item a{text-decoration:none;color:inherit;display:block;width:100%}

    /* ===== User profile in sidebar ===== */
    .user-profile{padding:16px 18px;border-top:1px solid rgba(255,255,255,0.12);display:flex;align-items:center;gap:12px;position:relative;cursor:pointer;width:100%;transition:background var(--transition-fast)}
    .user-profile:hover{background:rgba(255,255,255,0.06)}
    .user-avatar{width:48px;height:48px;border-radius:50%;border:2px solid var(--ustp-gold);object-fit:cover;transition:transform var(--transition-fast) ease}
    .user-profile:hover .user-avatar{transform:scale(1.03)}
    .user-info h4{font-size:1rem;font-weight:600;color:var(--ustp-white);line-height:1}
    .user-info p{font-size:.78rem;opacity:.9;margin-top:2px;color:rgba(255,255,255,0.92)}
    .user-dropdown{display:none;position:absolute;bottom:74px;left:18px;width:170px;background:var(--ustp-blue);border:1px solid var(--ustp-gold);border-radius:8px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.18);z-index:12}
    .user-dropdown a{display:block;padding:11px 14px;color:var(--ustp-white);text-decoration:none;transition:background var(--transition-fast)}
    .user-dropdown a:hover{background:var(--ustp-gold);color:var(--ustp-blue);font-weight:600}

    /* ===== Main content area ===== */
    .main-content{flex:1;margin-left:var(--sidebar-width);display:flex;flex-direction:column;transition:margin-left .3s;min-height:100vh}
    .header{height:var(--header-height);background:var(--ustp-white);display:flex;align-items:center;justify-content:space-between;padding:0 30px;box-shadow:0 2px 4px rgba(0,0,0,0.06);position:sticky;top:0;z-index:10}
    .header h1{color:var(--ustp-blue);font-size:22px;font-weight:700}
    .menu-toggle{display:none;font-size:22px;color:var(--ustp-blue);cursor:pointer}
    .content{padding:30px;flex:1}

    /* ===== Settings header & nav ===== */
    .settings-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;gap:12px}
    .settings-title{color:var(--ustp-blue);font-size:24px}
    .save-all-btn{background:var(--ustp-gold);color:var(--ustp-blue);border:none;border-radius:6px;padding:10px 20px;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:8px}
    .save-all-btn:disabled{opacity:.7;cursor:not-allowed}
    .settings-nav{display:flex;gap:10px;margin-bottom:22px;border-bottom:2px solid #eee}
    .settings-tab{padding:12px 20px;background:transparent;border:none;border-bottom:3px solid transparent;cursor:pointer;font-weight:500;color:var(--ustp-dark-gray);transition:color var(--transition-fast),border-color var(--transition-fast)}
    .settings-tab.active{color:var(--ustp-blue);border-bottom-color:var(--ustp-blue);font-weight:600}

    /* ===== Panels ===== */
    .settings-panels{display:grid;grid-template-columns:1fr;gap:20px}
    .settings-panel{background:var(--ustp-white);border-radius:var(--card-radius);box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;transition:transform var(--transition-medium),box-shadow var(--transition-medium)}
    .panel-header{background:var(--ustp-blue);color:var(--ustp-white);padding:14px 18px;display:flex;justify-content:space-between;align-items:center;font-weight:600}
    .panel-content{padding:20px}

    .form-group{margin-bottom:16px}
    .form-group label{display:block;margin-bottom:8px;font-weight:600;color:var(--ustp-dark-gray)}
    .form-control{width:100%;padding:10px 12px;border-radius:6px;border:1px solid #ddd;font-size:14px}
    .form-control:focus{outline:none;border-color:var(--ustp-blue)}
    .form-text{font-size:13px;color:#666;margin-top:6px}

    .toggle-group{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding:12px;background:#fafafa;border-radius:8px}
    .toggle-switch{position:relative;display:inline-block;width:50px;height:24px}
    .toggle-switch input{opacity:0;width:0;height:0}
    .toggle-slider{position:absolute;inset:0;background:#ccc;border-radius:24px;transition:background var(--transition-fast)}
    .toggle-slider:before{content:'';position:absolute;left:4px;bottom:4px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform var(--transition-fast)}
    .toggle-switch input:checked + .toggle-slider{background:var(--ustp-blue)}
    .toggle-switch input:checked + .toggle-slider:before{transform:translateX(26px)}

    /* ===== Admin Profile Card (new) ===== */
    .admin-profile-card{display:flex;gap:20px;align-items:center;padding:18px;border-radius:10px;background:linear-gradient(180deg,#ffffff 0%,#f7fbff 100%);box-shadow:0 6px 18px rgba(0,0,0,0.04);margin-bottom:18px}
    /* remove hard blue square: make the background gradient frame soft and rounded */
    .admin-avatar-wrap{position:relative;width:104px;height:104px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:8px;background:linear-gradient(180deg,var(--ustp-white),rgba(0,0,0,0.02));box-shadow:0 4px 14px rgba(0,0,0,0.06)}
    .admin-avatar{width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--ustp-white);box-shadow:0 6px 18px rgba(0,0,0,0.08);transition:transform var(--transition-fast)}
    .admin-avatar:hover{transform:scale(1.03)}

    .admin-info{flex:1;display:flex;flex-direction:column;gap:8px}
    .admin-info .name{font-size:1.1rem;font-weight:700;color:var(--ustp-blue)}
    .admin-info .role{font-size:.9rem;color:#666}
    .admin-actions{display:flex;gap:10px;align-items:center;margin-top:6px}
    .btn{background:var(--ustp-blue);color:white;border:none;border-radius:8px;padding:8px 12px;cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:8px;transition:transform var(--transition-fast),box-shadow var(--transition-fast)}
    .btn.secondary{background:transparent;color:var(--ustp-blue);border:1px solid var(--ustp-blue)}
    .btn:active{transform:translateY(1px);box-shadow:none}

    .avatar-upload-controls{display:flex;gap:8px;align-items:center}
    .file-label{background:var(--ustp-light-blue);color:#fff;padding:8px 10px;border-radius:8px;cursor:pointer;font-weight:600}
    .file-input{display:none}
    .small-muted{font-size:13px;color:#666;margin-top:6px}

    /* subtle fade-in for updated avatar */
    .avatar-updated{animation:avatarFade 420ms ease}
    @keyframes avatarFade{from{opacity:0;transform:translateY(-6px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}

    /* responsive tweaks */
    @media (max-width:900px){
      .admin-profile-card{flex-direction:column;align-items:flex-start}
      .admin-avatar-wrap{width:92px;height:92px}
      .admin-avatar{width:74px;height:74px}
      .sidebar{width:70px}
      .main-content{margin-left:70px}
      .menu-toggle{display:block}
    }

    /* toast */
    .toast{position:fixed;right:20px;bottom:20px;padding:12px 18px;background:var(--ustp-blue);color:#fff;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.12);transform:translateY(80px);opacity:0;transition:transform 260ms,opacity 260ms;z-index:2000}
    .toast.show{transform:translateY(0);opacity:1}
    .toast.success{background:#4caf50}
    .toast.error{background:#f44336}

    .hidden{display:none!important}
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
      <div class="logo"><img src="../assests/suffage.png" alt="Logo"></div>
    </div>

    <ul class="nav-menu">
      <li class="nav-item"><i class="fas fa-home"></i><a href="admin_dashboard.html">Dashboard</a></li>
      <li class="nav-item"><i class="fas fa-user-graduate"></i><a href="register-voters.php">Voters</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item "><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
      <li class="nav-item"><i class="fas fa-file-alt"></i><a href="audit-reports.php">Reports</a></li>
      <li class="nav-item active"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <!-- user-profile -->
    <div class="user-profile" id="userProfile" style="cursor:pointer">
      <img id="adminAvatar" src="../assests/default-avatar.jpg" class="user-avatar" alt="Admin Avatar" width="56" height="56" />
      <div class="user-info">
        <h4 id="adminName">Administrator</h4>
        <p id="adminRole">Administration</p>
      </div>
      <div class="user-dropdown" id="dropdown" style="display:none;">
        <a href="../login.html" id="logoutBtn">Logout</a>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <div class="header">
      <h1>Settings</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <div class="settings-header">
        <h2 class="settings-title">System Configuration</h2>
        <button class="save-all-btn" id="saveAllBtn" disabled>
          <i class="fas fa-save"></i> Save All Changes
        </button>
      </div>

      <!-- Settings Navigation -->
      <div class="settings-nav">
        <button class="settings-tab active" data-tab="general">General</button>
      </div>

      <!-- Settings Panels -->
      <div class="settings-panels">
        <!-- General Settings (includes Admin Profile card at top) -->
        <div class="settings-panel active" id="general-panel">
          <div class="panel-header">
            <span>General Settings</span>
            <i class="fas fa-cog"></i>
          </div>

          <div class="panel-content">
            <!-- Admin Profile Card -->
            <div class="admin-profile-card" id="adminProfileCard">
              <div class="admin-avatar-wrap">
                <img id="profileAvatarLarge" class="admin-avatar" src="../assests/default-avatar.jpg" alt="Admin avatar preview">
              </div>

              <div class="admin-info">
                <div>
                  <div class="name" id="profileNameDisplay">Administrator</div>
                  <div class="role" id="profileRoleDisplay">Administration</div>
                </div>

                <div class="admin-actions">
                  <div class="avatar-upload-controls">
                    <label class="file-label" for="avatarInput"><i class="fas fa-camera"></i> Change Avatar</label>
                    <input id="avatarInput" class="file-input" type="file" accept="image/png,image/jpeg,image/webp">
                    <button class="btn secondary" id="removeAvatarBtn" type="button" title="Remove avatar"><i class="fas fa-trash"></i> Remove</button>
                  </div>

                  <div style="margin-left:auto">
                    <button class="btn" id="editProfileBtn" type="button"><i class="fas fa-edit"></i> Edit Profile</button>
                  </div>
                </div>

                <div class="small-muted">Change admin avatar and display name. Changes persist to server if available.</div>
              </div>
            </div>

            <!-- Existing general settings form -->
            <div style="margin-top:10px;">
              <div class="form-group">
                <label for="systemName">System Name</label>
                <input type="text" id="systemName" class="form-control" value="eBOTO Voting System">
                <div class="form-text">The name displayed throughout the system</div>
              </div>

              <div class="form-group">
                <label for="adminEmail">Administrator Email</label>
                <input type="email" id="adminEmail" class="form-control" value="admin@ustp.edu.ph">
                <div class="form-text">Primary contact email for system notifications</div>
              </div>

              <div class="form-group">
                <label for="timezone">Timezone</label>
                <select id="timezone" class="form-control">
                  <option value="UTC+8">(UTC+8) Philippine Time</option>
                  <option value="UTC+0">(UTC+0) Greenwich Mean Time</option>
                  <option value="UTC-5">(UTC-5) Eastern Time</option>
                </select>
              </div>

              <div class="form-group">
                <label for="dateFormat">Date Format</label>
                <select id="dateFormat" class="form-control">
                  <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                  <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                  <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                </select>
              </div>

              <div class="toggle-group">
                <div>
                  <div class="toggle-label">Maintenance Mode</div>
                  <div class="toggle-description">Temporarily disable the system for maintenance</div>
                </div>
                <label class="toggle-switch">
                  <input type="checkbox" id="maintenanceMode">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
          </div>
        </div> <!-- /general-panel -->
      </div> <!-- /settings-panels -->
    </div> <!-- /content -->
  </div> <!-- /main-content -->

  <!-- Toast -->
  <div id="toast" class="toast" aria-live="polite" role="status"></div>

  <!-- Edit profile template -->
  <template id="profileEditTemplate">
    <div style="padding:18px;max-width:480px;">
      <div style="display:flex;gap:12px;align-items:center;margin-bottom:14px;">
        <img id="editAvatarPreview" src="" alt="preview" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 6px 18px rgba(0,0,0,0.08);">
        <div>
          <div style="font-weight:700;color:var(--ustp-blue);font-size:1.05rem;" id="editNamePreview">Administrator</div>
          <div style="color:#666;font-size:0.9rem;" id="editRolePreview">Administration</div>
        </div>
      </div>

      <div style="display:grid;gap:10px;">
        <label style="font-weight:700">Display Name</label>
        <input id="editNameInput" type="text" class="form-control" value="Administrator">

        <label style="font-weight:700">Role / Position</label>
        <input id="editRoleInput" type="text" class="form-control" value="Administration">

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
          <button id="cancelEditProfile" class="btn secondary" type="button">Cancel</button>
          <button id="saveEditProfile" class="btn" type="button">Save</button>
        </div>
      </div>
    </div>
  </template>

  <script>
  (function(){
    const AVATAR_UPLOAD_URL = '../php/uploadAvatar.php';
    const ADMIN_UPDATE_URL = '../php/updateAdmin.php';
    const GET_ADMIN_URL = '../php/getAdmin.php';
    const LS_KEY = 'eBOTO_admin_profile';

    // DOM refs
    const adminAvatar = document.getElementById('adminAvatar');
    const profileAvatarLarge = document.getElementById('profileAvatarLarge');
    const avatarInput = document.getElementById('avatarInput');
    const removeAvatarBtn = document.getElementById('removeAvatarBtn');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const profileNameDisplay = document.getElementById('profileNameDisplay');
    const profileRoleDisplay = document.getElementById('profileRoleDisplay');
    const toastEl = document.getElementById('toast');

    let adminProfile = { id:null, name:'Administrator', role:'Administration', avatar:'../assests/default-avatar.jpg' };

    function showToast(msg, type='info'){
      toastEl.textContent = msg;
      toastEl.className = 'toast';
      if(type==='success') toastEl.classList.add('success');
      if(type==='error') toastEl.classList.add('error');
      toastEl.classList.add('show');
      clearTimeout(toastEl._t);
      toastEl._t = setTimeout(()=> toastEl.classList.remove('show'), 3000);
    }

    function readProfileFromLocal(){
      try{ const raw = localStorage.getItem(LS_KEY); return raw ? JSON.parse(raw) : null }catch(e){ return null }
    }
    function saveProfileToLocal(p){ try{ localStorage.setItem(LS_KEY, JSON.stringify(p)) }catch(e){} }

    function updateUIFromProfile(profile, animate=true){
      if(!profile) return;
      adminProfile = Object.assign({}, adminProfile, profile);
      const avatar = adminProfile.avatar || '../assests/default-avatar.jpg';
      if(adminAvatar){ adminAvatar.src = avatar; if(animate) adminAvatar.classList.add('avatar-updated'); setTimeout(()=>adminAvatar.classList.remove('avatar-updated'),500) }
      if(profileAvatarLarge){ profileAvatarLarge.src = avatar; if(animate) profileAvatarLarge.classList.add('avatar-updated'); setTimeout(()=>profileAvatarLarge.classList.remove('avatar-updated'),500) }
      if(profileNameDisplay) profileNameDisplay.textContent = adminProfile.name || 'Administrator';
      if(profileRoleDisplay) profileRoleDisplay.textContent = adminProfile.role || 'Administration';
      // also update sidebar text elements
      const adminNameEl = document.getElementById('adminName');
      const adminRoleEl = document.getElementById('adminRole');
      if(adminNameEl) adminNameEl.textContent = adminProfile.name || 'Administrator';
      if(adminRoleEl) adminRoleEl.textContent = adminProfile.role || 'Administration';
    }

    async function loadAdminProfile(){
      // try server first
      try{
        const res = await fetch(GET_ADMIN_URL, { cache:'no-store' });
        if(res && res.ok){
          const json = await res.json().catch(()=>null);
          if(json && json.success !== false){
            const name = json.name || json.admin_name || json.fullname || adminProfile.name;
            const role = json.role || json.position || adminProfile.role;
            let avatar = json.avatar || json.avatar_path || json.avatarUrl || json.profile || adminProfile.avatar;
            // normalize path: if relative (no protocol, not starting with / or ../) assume it's relative to project root (uploads or assets)
            if(avatar && !/^https?:\/\//i.test(avatar) && !avatar.startsWith('/') && !avatar.startsWith('../')) {
              // if it's already like uploads/..., prefix '../' for pages in subfolders; adjust if your structure differs
              avatar = avatar.startsWith('uploads/') || avatar.startsWith('assests/') ? ('../' + avatar) : ('../assests/' + avatar);
            }
            adminProfile = Object.assign({}, adminProfile, { id: json.id || json.admin_id || null, name, role, avatar });
            updateUIFromProfile(adminProfile, false);
            saveProfileToLocal(adminProfile);
            return;
          }
        }
      }catch(e){ /* ignore network errors */ }

      // fallback to local
      const local = readProfileFromLocal();
      if(local){ adminProfile = Object.assign({}, adminProfile, local); updateUIFromProfile(adminProfile, false) }
      else updateUIFromProfile(adminProfile, false);
    }

    // avatar upload flow
    avatarInput.addEventListener('change', async (ev)=>{
      const f = ev.target.files && ev.target.files[0];
      if(!f) return;
      const allowed = ['image/jpeg','image/png','image/webp'];
      if(!allowed.includes(f.type)){ showToast('Unsupported image type. Use JPG, PNG or WEBP.','error'); avatarInput.value=''; return }
      if(f.size > 3 * 1024 * 1024){ showToast('Avatar too large. Max 3MB.','error'); avatarInput.value=''; return }

      const reader = new FileReader();
      reader.onload = ()=> {
        const dataUrl = reader.result;
        profileAvatarLarge.src = dataUrl;
        adminAvatar.src = dataUrl;
      };
      reader.readAsDataURL(f);

      // upload
      try{
        showToast('Uploading avatar...','info');
        const form = new FormData();
        form.append('avatar', f);
        if(adminProfile.id) form.append('adminId', adminProfile.id);
        const resp = await fetch(AVATAR_UPLOAD_URL, { method:'POST', body: form, cache:'no-store' });
        if(!resp.ok) throw new Error('Upload failed');
        const json = await resp.json().catch(()=>null);
        if(json && json.success){
          let newPath = json.path || json.url || json.avatar || '';
          if(newPath && !/^https?:\/\//i.test(newPath) && !newPath.startsWith('/')){
            if(!newPath.startsWith('../')) newPath = '../' + newPath.replace(/^\/+/, '');
          }
          adminProfile.avatar = newPath || adminProfile.avatar;
          updateUIFromProfile(adminProfile, true);
          // persist
          try{ await fetch(ADMIN_UPDATE_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: adminProfile.id, avatar: adminProfile.avatar }), cache:'no-store' }); }catch(e){}
          saveProfileToLocal(adminProfile);
          showToast('Avatar uploaded','success');
        } else {
          // server didn't return success — keep preview locally
          adminProfile.avatar = profileAvatarLarge.src;
          saveProfileToLocal(adminProfile);
          showToast((json && json.message) ? json.message : 'Upload stored locally','info');
        }
      }catch(err){
        adminProfile.avatar = profileAvatarLarge.src;
        saveProfileToLocal(adminProfile);
        showToast('Upload failed — saved locally','error');
      }finally{
        avatarInput.value = '';
      }
    });

    // remove avatar
    removeAvatarBtn.addEventListener('click', async ()=>{
      if(!confirm('Remove admin avatar and use default?')) return;
      adminProfile.avatar = '../assests/default-avatar.jpg';
      updateUIFromProfile(adminProfile, true);
      saveProfileToLocal(adminProfile);
      try{ await fetch(ADMIN_UPDATE_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: adminProfile.id, avatar: adminProfile.avatar }), cache:'no-store' }); }catch(e){}
      showToast('Avatar removed','success');
    });

    // edit profile modal
    editProfileBtn.addEventListener('click', ()=>{
      const t = document.getElementById('profileEditTemplate');
      const clone = t.content.cloneNode(true);
      const modal = document.createElement('div');
      modal.style.position='fixed'; modal.style.inset=0; modal.style.display='flex'; modal.style.alignItems='center'; modal.style.justifyContent='center';
      modal.style.background='rgba(0,0,0,0.35)'; modal.style.zIndex=3000;
      const card = document.createElement('div');
      card.style.background='#fff'; card.style.borderRadius='10px'; card.style.boxShadow='0 12px 36px rgba(0,0,0,0.28)';
      card.appendChild(clone); modal.appendChild(card); document.body.appendChild(modal);

      const editNameInput = card.querySelector('#editNameInput');
      const editRoleInput = card.querySelector('#editRoleInput');
      const editAvatarPreview = card.querySelector('#editAvatarPreview');
      const editNamePreview = card.querySelector('#editNamePreview');
      const editRolePreview = card.querySelector('#editRolePreview');
      const cancelBtn = card.querySelector('#cancelEditProfile');
      const saveBtn = card.querySelector('#saveEditProfile');

      editNameInput.value = adminProfile.name || 'Administrator';
      editRoleInput.value = adminProfile.role || 'Administration';
      editAvatarPreview.src = adminProfile.avatar || '../assests/default-avatar.jpg';
      editNamePreview.textContent = editNameInput.value;
      editRolePreview.textContent = editRoleInput.value;

      editNameInput.addEventListener('input', ()=> editNamePreview.textContent = editNameInput.value || 'Administrator');
      editRoleInput.addEventListener('input', ()=> editRolePreview.textContent = editRoleInput.value || 'Administration');

      cancelBtn.addEventListener('click', ()=> document.body.removeChild(modal));

      saveBtn.addEventListener('click', async ()=>{
        const newName = editNameInput.value.trim() || 'Administrator';
        const newRole = editRoleInput.value.trim() || 'Administration';
        adminProfile.name = newName; adminProfile.role = newRole;
        updateUIFromProfile(adminProfile, true);
        saveProfileToLocal(adminProfile);
        try{
          showToast('Saving profile...','info');
          const res = await fetch(ADMIN_UPDATE_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id: adminProfile.id, name: newName, role: newRole }), cache:'no-store' });
          if(res && res.ok){
            const json = await res.json().catch(()=>null);
            if(json && json.success) showToast('Profile updated','success');
            else showToast('Saved locally (server did not confirm)','info');
          } else showToast('Saved locally (server unreachable)','error');
        }catch(e){ showToast('Saved locally (network error)','error') }finally{ document.body.removeChild(modal) }
      });
    });

    // sidebar dropdown toggle
    const userProfileEl = document.getElementById('userProfile');
    const dropdownEl = document.getElementById('dropdown');
    userProfileEl && userProfileEl.addEventListener('click', (e)=>{ e.stopPropagation(); if(!dropdownEl) return; dropdownEl.style.display = dropdownEl.style.display === 'block' ? 'none' : 'block'; });
    document.addEventListener('click', (e)=>{ if(!userProfileEl) return; if(dropdownEl && !userProfileEl.contains(e.target)) dropdownEl.style.display = 'none'; });

    // init
    document.addEventListener('DOMContentLoaded', loadAdminProfile);
  })();
  </script>

</body>
</html>
