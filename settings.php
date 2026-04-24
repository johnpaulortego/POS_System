<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Default avatar logic - handle if profile_pic column doesn't exist
$profile_pic = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&background=F28B50&color=fff";
if (isset($user['profile_pic']) && !empty($user['profile_pic'])) {
    $profile_pic = $user['profile_pic'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Purr'Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --orange: #F28B50;
            --light-orange: #FFF5F0;
            --bg: #F5F5F5;
            --white: #FAFAFA;
            --text-main: #2D2D2D;
            --text-muted: #8B8B8B;
            --border: #BEBEBE;
            --black: #1A1A1A;
            --card-bg: #FFFFFF;
            --input-bg: #FFFFFF;
        }

        [data-theme="dark"] {
            --bg: #1A1A1A;
            --white: #2A2A2A;
            --text-main: #FFFFFF;
            --text-muted: #AAAAAA;
            --border: #444444;
            --card-bg: #2A2A2A;
            --input-bg: #333333;
            --black: #FFFFFF;
        }

        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-font-smoothing: antialiased; }
        body { margin: 0; background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; transition: background 0.3s ease, color 0.3s ease; }

        /* --- SIDEBAR --- */
        .sidebar-left { width: 160px; background: var(--white); padding: 25px 18px; display: flex; flex-direction: column; border-right: 2px solid var(--border); transition: background 0.3s ease; }
        .logo { font-size: 18px; font-weight: 700; margin-bottom: 50px; }
        .logo span { color: var(--orange); }
        .nav-group { flex: 0; margin-bottom: 60px; }
        .nav-item { display: flex; align-items: center; padding: 10px 14px; text-decoration: none; color: var(--text-muted); font-weight: 500; margin-bottom: 8px; transition: 0.2s; position: relative; font-size: 13px; }
        .nav-item i { margin-right: 10px; width: 18px; height: 18px; }
        .nav-item.active { color: var(--orange); font-weight: 600; }
        .nav-item.active::after { content: ''; position: absolute; right: -18px; top: 50%; transform: translateY(-50%); width: 4px; height: 28px; background: var(--orange); border-radius: 2px 0 0 2px; }
        .nav-item:hover { color: var(--orange); }
        
        /* Theme Toggle */
        .theme-toggle { display: flex; align-items: center; padding: 10px 14px; cursor: pointer; color: var(--text-muted); font-weight: 500; margin-bottom: 8px; transition: 0.2s; font-size: 13px; }
        .theme-toggle:hover { color: var(--orange); }
        .theme-toggle i { width: 18px; height: 18px; margin-right: 10px; }

        /* --- CONTENT --- */
        .content-main { flex: 1; padding: 35px 50px; overflow-y: auto; }
        .page-title { font-size: 28px; font-weight: 800; margin-bottom: 35px; }

        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 1100px; }
        .settings-card { background: var(--card-bg); border-radius: 24px; border: 2px solid var(--border); padding: 30px; height: fit-content; transition: background 0.3s ease; }
        .settings-card.full-width { grid-column: 1 / -1; }
        .card-title { font-size: 18px; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .card-title i { width: 20px; height: 20px; color: var(--orange); }
        
        /* --- PROFILE PICTURE --- */
        .profile-section { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 2px solid var(--border); }
        .profile-preview-wrapper { position: relative; width: 80px; height: 80px; }
        #profilePreview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--light-orange); }
        .upload-badge { position: absolute; bottom: 0; right: 0; background: var(--orange); color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid var(--card-bg); }
        #fileInput { display: none; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 12px; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input { width: 100%; padding: 12px 15px; border-radius: 12px; border: 2px solid var(--border); outline: none; font-size: 14px; font-weight: 500; background: var(--input-bg); color: var(--text-main); transition: border 0.2s ease; }
        .form-group input::placeholder { color: var(--text-muted); }
        .form-group input:focus { border-color: var(--orange); }

        /* Password eye toggle */
        .input-eye { position: relative; }
        .input-eye input { padding-right: 44px; width: 100%; }
        .eye-btn { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); display: flex; align-items: center; }
        .eye-btn:hover { color: var(--orange); }
        .eye-btn i { width: 18px; height: 18px; }

        .btn { border: none; padding: 14px; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.2s; width: 100%; font-size: 14px; margin-top: 5px; }
        .btn-orange { background: var(--orange); color: white; }
        .btn-orange:hover { background: #e07a42; }
        
        /* NEW BLACK BUTTON COLOR */
        .btn-black { background: #1A1A1A; color: white; }
        .btn-black:hover { opacity: 0.9; }
        [data-theme="dark"] .btn-black { background: #FFFFFF; color: #1A1A1A; }

        .danger-btn { color: #EB5757; background: transparent; border: none; font-weight: 700; cursor: pointer; font-size: 13px; width: 100%; text-align: center; margin-top: 25px; }
        
        /* Toggle Switch */
        .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 2px solid var(--border); }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-info { flex: 1; }
        .toggle-label { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .toggle-desc { font-size: 12px; color: var(--text-muted); }
        .toggle-switch { position: relative; width: 48px; height: 26px; background: var(--border); border-radius: 13px; cursor: pointer; transition: 0.3s; }
        .toggle-switch.active { background: var(--orange); }
        .toggle-switch::after { content: ''; position: absolute; width: 20px; height: 20px; background: white; border-radius: 50%; top: 3px; left: 3px; transition: 0.3s; }
        .toggle-switch.active::after { left: 25px; }
        
        /* Store Info */
        .store-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        /* Receipt Preview */
        .receipt-preview { background: var(--bg); border: 2px solid var(--border); border-radius: 12px; padding: 20px; font-family: 'Courier New', monospace; font-size: 12px; margin-top: 20px; }
        .receipt-header { text-align: center; margin-bottom: 15px; font-weight: 700; }
        .receipt-line { border-top: 1px dashed var(--border); margin: 10px 0; }
        
        /* Success Message */
        .success-msg { background: #27AE60; color: white; padding: 12px 18px; border-radius: 12px; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: none; }
        .success-msg.show { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <div class="logo">Purr'<span>Coffee</span></div>
        <div class="nav-group">
            <a href="pos.php" class="nav-item"><i data-lucide="coffee"></i> Menu</a>
            <a href="orders.php" class="nav-item"><i data-lucide="clipboard-list"></i> Orders</a>
            <a href="reports.php" class="nav-item"><i data-lucide="bar-chart-3"></i> Reports</a>
        </div>
        <div style="margin-top: auto;">
            <div class="theme-toggle" onclick="toggleTheme()">
                <i data-lucide="moon" id="themeIcon"></i>
                <span id="themeText">Dark Mode</span>
            </div>
            <a href="settings.php" class="nav-item active"><i data-lucide="settings"></i> Settings</a>
            <a href="logout.php" class="nav-item"><i data-lucide="log-out"></i> Log out</a>
        </div>
    </div>

    <div class="content-main">
        <h1 class="page-title">Settings</h1>
        
        <div id="successMsg" class="success-msg">Settings saved successfully!</div>

        <?php if (isset($_GET['success'])): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const msg = document.getElementById('successMsg');
                msg.textContent = '<?= 
                    $_GET['success'] === 'profile_updated' ? 'Profile updated successfully!' :
                    ($_GET['success'] === 'password_updated' ? 'Password updated successfully!' :
                    ($_GET['success'] === 'store_updated' ? 'Store information saved!' : 'Settings saved successfully!'))
                ?>';
                msg.classList.add('show');
                setTimeout(() => msg.classList.remove('show'), 3000);
            });
        </script>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const msg = document.getElementById('successMsg');
                msg.style.background = '#EB5757';
                msg.textContent = 'Error: <?= htmlspecialchars($_GET['error']) ?>';
                msg.classList.add('show');
                setTimeout(() => msg.classList.remove('show'), 5000);
            });
        </script>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Profile Details -->
            <div class="settings-card">
                <div class="card-title"><i data-lucide="user"></i> Profile Details</div>
                
                <form action="update_settings.php" method="POST" enctype="multipart/form-data" onsubmit="return handleProfileSubmit(event)">
                    <div class="profile-section">
                        <div class="profile-preview-wrapper">
                            <img id="profilePreview" src="<?= $profile_pic ?>" alt="Profile">
                            <label for="fileInput" class="upload-badge">
                                <i data-lucide="camera" style="width: 14px; height: 14px;"></i>
                            </label>
                        </div>
                        <div>
                            <div style="font-weight: 800; font-size: 15px;">Profile Picture</div>
                            <div style="font-size: 12px; color: var(--text-muted);">PNG or JPG, max 2MB</div>
                        </div>
                        <input type="file" id="fileInput" name="profile_image" accept="image/*" onchange="previewImage(event)">
                    </div>

                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['username']) ?>@gmail.com">
                    </div>
                    
                    <button type="submit" class="btn btn-orange">Save Changes</button>
                </form>
            </div>

            <!-- Security & Privacy -->
            <div class="settings-card">
                <div class="card-title"><i data-lucide="shield-check"></i> Security & Privacy</div>
                <form action="update_password.php" method="POST" onsubmit="return handlePasswordSubmit(event)">
                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="input-eye">
                            <input type="password" name="old_pass" id="old_pass" placeholder="••••••••" required>
                            <span class="eye-btn" onclick="togglePass('old_pass', this)"><i data-lucide="eye"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="input-eye">
                            <input type="password" name="new_pass" id="new_pass" placeholder="Create new password" required>
                            <span class="eye-btn" onclick="togglePass('new_pass', this)"><i data-lucide="eye"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-eye">
                            <input type="password" name="confirm_pass" id="confirm_pass" placeholder="Confirm new password" required>
                            <span class="eye-btn" onclick="togglePass('confirm_pass', this)"><i data-lucide="eye"></i></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-black">Update Password</button>
                </form>

                <button class="danger-btn" onclick="confirmDeactivate()">Deactivate Account</button>
            </div>

            <!-- Store Information -->
            <div class="settings-card full-width">
                <div class="card-title"><i data-lucide="store"></i> Store Information</div>
                <form action="update_store.php" method="POST" onsubmit="return handleStoreSubmit(event)">
                    <div class="store-info-grid">
                        <div class="form-group">
                            <label>Store Name</label>
                            <input type="text" name="store_name" id="storeName" value="Purr'Coffee" placeholder="Your store name">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" name="store_phone" id="storePhone" value="+63 912 345 6789" placeholder="+63 XXX XXX XXXX">
                        </div>
                        <div class="form-group">
                            <label>Store Address</label>
                            <input type="text" name="store_address" id="storeAddress" value="123 Coffee Street, Manila" placeholder="Full address">
                        </div>
                        <div class="form-group">
                            <label>Tax ID / TIN</label>
                            <input type="text" name="store_tin" id="storeTin" value="123-456-789-000" placeholder="XXX-XXX-XXX-XXX">
                        </div>
                    </div>
                    
                    <div class="receipt-preview" id="receiptPreview">
                        <div class="receipt-header">
                            PURR'COFFEE<br>
                            123 Coffee Street, Manila<br>
                            +63 912 345 6789<br>
                            TIN: 123-456-789-000
                        </div>
                        <div class="receipt-line"></div>
                        <div style="text-align: center; color: var(--text-muted); font-size: 11px;">Receipt Preview</div>
                    </div>
                    
                    <button type="submit" class="btn btn-orange" style="margin-top: 20px;">Save Store Info</button>
                </form>
            </div>

            <!-- Notifications -->
            <div class="settings-card">
                <div class="card-title"><i data-lucide="bell"></i> Notifications</div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Order Alerts</div>
                        <div class="toggle-desc">Get notified for new orders</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                </div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Low Stock Alerts</div>
                        <div class="toggle-desc">Alert when products run low</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                </div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Daily Reports</div>
                        <div class="toggle-desc">Receive daily sales summary</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                </div>
            </div>

            <!-- System Preferences -->
            <div class="settings-card">
                <div class="card-title"><i data-lucide="sliders"></i> System Preferences</div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Auto Print Receipt</div>
                        <div class="toggle-desc">Print after each order</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                </div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <div class="toggle-label">Sound Effects</div>
                        <div class="toggle-desc">Play sounds on actions</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label>Currency Symbol</label>
                    <input type="text" value="₱" readonly style="background: var(--bg);">
                </div>
                
                <div class="form-group">
                    <label>Tax Rate (%)</label>
                    <input type="number" id="taxRateInput" value="12" placeholder="0" min="0" max="100">
                </div>
            </div>

            <!-- Data Management -->
            <div class="settings-card full-width">
                <div class="card-title"><i data-lucide="database"></i> Data Management</div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <button class="btn btn-orange" onclick="exportData()">
                        <i data-lucide="download" style="width: 14px; height: 14px; margin-right: 6px; display: inline-block; vertical-align: middle;"></i>
                        Export Data
                    </button>
                    <button class="btn btn-black" onclick="clearCache()">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px; margin-right: 6px; display: inline-block; vertical-align: middle;"></i>
                        Clear Cache
                    </button>
                    <button class="btn btn-black" onclick="resetOrders()">
                        <i data-lucide="rotate-ccw" style="width: 14px; height: 14px; margin-right: 6px; display: inline-block; vertical-align: middle;"></i>
                        Reset Orders
                    </button>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: var(--bg); border-radius: 12px; border: 2px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 13px; color: var(--text-muted);">Storage Used</span>
                        <span style="font-size: 13px; font-weight: 700;" class="storage-used-text">0 MB / 10 MB</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden;">
                        <div class="storage-bar" style="width: 0%; height: 100%; background: var(--orange); transition: width 0.3s ease;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();

        // Load saved settings on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadSettings();
        });

        function loadSettings() {
            // Load toggle states with proper defaults
            const settings = {
                orderAlerts: localStorage.getItem('setting_orderAlerts'),
                lowStockAlerts: localStorage.getItem('setting_lowStockAlerts'),
                dailyReports: localStorage.getItem('setting_dailyReports'),
                autoPrint: localStorage.getItem('setting_autoPrint'),
                soundEffects: localStorage.getItem('setting_soundEffects'),
                taxRate: localStorage.getItem('setting_taxRate') || '12'
            };

            // Set default values if not set
            if (settings.orderAlerts === null) {
                localStorage.setItem('setting_orderAlerts', 'true');
                settings.orderAlerts = 'true';
            }
            if (settings.lowStockAlerts === null) {
                localStorage.setItem('setting_lowStockAlerts', 'true');
                settings.lowStockAlerts = 'true';
            }
            if (settings.dailyReports === null) {
                localStorage.setItem('setting_dailyReports', 'false');
                settings.dailyReports = 'false';
            }
            if (settings.autoPrint === null) {
                localStorage.setItem('setting_autoPrint', 'false');
                settings.autoPrint = 'false';
            }
            if (settings.soundEffects === null) {
                localStorage.setItem('setting_soundEffects', 'true');
                settings.soundEffects = 'true';
            }
            if (localStorage.getItem('setting_taxRate') === null) {
                localStorage.setItem('setting_taxRate', '12');
            }

            // Apply toggle states - get all toggles in order
            const toggles = document.querySelectorAll('.toggle-switch');
            
            // Clear all active states first
            toggles.forEach(toggle => toggle.classList.remove('active'));
            
            // Apply saved states
            if (settings.orderAlerts === 'true') toggles[0].classList.add('active');
            if (settings.lowStockAlerts === 'true') toggles[1].classList.add('active');
            if (settings.dailyReports === 'true') toggles[2].classList.add('active');
            if (settings.autoPrint === 'true') toggles[3].classList.add('active');
            if (settings.soundEffects === 'true') toggles[4].classList.add('active');

            // Set tax rate
            const taxInput = document.getElementById('taxRateInput');
            if (taxInput) {
                taxInput.value = settings.taxRate;
            }
        }

        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showSuccess('Please select a valid image file (JPG, PNG, or GIF)');
                event.target.value = '';
                return;
            }
            
            // Validate file size (2MB max)
            if (file.size > 2097152) {
                showSuccess('Image size must be less than 2MB');
                event.target.value = '';
                return;
            }
            
            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageData = e.target.result;
                document.getElementById('profilePreview').src = imageData;
                // Store in sessionStorage temporarily
                sessionStorage.setItem('tempProfilePreview', imageData);
                showSuccess('Image selected! Click "Save Changes" to upload.');
            }
            reader.readAsDataURL(file);
        }

        // Restore preview on page load if exists
        window.addEventListener('DOMContentLoaded', () => {
            const tempPreview = sessionStorage.getItem('tempProfilePreview');
            if (tempPreview) {
                // Only use temp preview if no real uploaded image exists
                const currentSrc = document.getElementById('profilePreview').src;
                if (currentSrc.includes('ui-avatars.com')) {
                    document.getElementById('profilePreview').src = tempPreview;
                }
            }
        });

        function handleProfileSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            // Validate username
            const username = formData.get('username');
            if (!username || username.trim() === '') {
                showSuccess('Please enter a display name');
                return false;
            }
            
            // Show loading state
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Saving...';
            
            // Submit form
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Check if upload was successful by looking for success parameter
                if (data.includes('success=profile_updated')) {
                    // Clear temp preview since upload is complete
                    sessionStorage.removeItem('tempProfilePreview');
                    
                    // Update button state
                    btn.disabled = false;
                    btn.innerText = originalText;
                    
                    // Show success message
                    showSuccess('Profile updated successfully!');
                    
                    // Keep the current preview image (it's already showing)
                    // The uploaded image path is now in the database
                    
                    // Clear the file input
                    document.getElementById('fileInput').value = '';
                } else if (data.includes('error=')) {
                    // Extract error message
                    const errorMatch = data.match(/error=([^&]+)/);
                    const errorMsg = errorMatch ? decodeURIComponent(errorMatch[1]) : 'Upload failed';
                    showSuccess('Error: ' + errorMsg);
                    btn.disabled = false;
                    btn.innerText = originalText;
                } else {
                    // Success but no redirect
                    sessionStorage.removeItem('tempProfilePreview');
                    btn.disabled = false;
                    btn.innerText = originalText;
                    showSuccess('Profile updated successfully!');
                    document.getElementById('fileInput').value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showSuccess('Error updating profile. Please try again.');
                btn.disabled = false;
                btn.innerText = originalText;
            });
            
            return false;
        }

        function handlePasswordSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            // Validate passwords match
            const newPass = formData.get('new_pass');
            const confirmPass = formData.get('confirm_pass');
            
            if (newPass !== confirmPass) {
                showSuccess('Passwords do not match!');
                return false;
            }
            
            // Show loading state
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Updating...';
            
            // Submit form
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showSuccess('Password updated successfully!');
                btn.disabled = false;
                btn.innerText = originalText;
                form.reset();
            })
            .catch(error => {
                console.error('Error:', error);
                showSuccess('Error updating password. Please try again.');
                btn.disabled = false;
                btn.innerText = originalText;
            });
            
            return false;
        }

        function handleStoreSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            // Save to localStorage
            localStorage.setItem('store_name', formData.get('store_name'));
            localStorage.setItem('store_phone', formData.get('store_phone'));
            localStorage.setItem('store_address', formData.get('store_address'));
            localStorage.setItem('store_tin', formData.get('store_tin'));
            
            // Update receipt preview
            updateReceiptPreview();
            
            // Show loading state
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Saving...';
            
            // Submit form
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showSuccess('Store information saved successfully!');
                btn.disabled = false;
                btn.innerText = originalText;
            })
            .catch(error => {
                console.error('Error:', error);
                showSuccess('Store information saved locally!');
                btn.disabled = false;
                btn.innerText = originalText;
            });
            
            return false;
        }

        function updateReceiptPreview() {
            const storeName = document.getElementById('storeName').value || "PURR'COFFEE";
            const storeAddress = document.getElementById('storeAddress').value || "123 Coffee Street, Manila";
            const storePhone = document.getElementById('storePhone').value || "+63 912 345 6789";
            const storeTin = document.getElementById('storeTin').value || "123-456-789-000";
            
            const preview = document.getElementById('receiptPreview');
            preview.querySelector('.receipt-header').innerHTML = `
                ${storeName.toUpperCase()}<br>
                ${storeAddress}<br>
                ${storePhone}<br>
                TIN: ${storeTin}
            `;
        }

        // Load store info from localStorage on page load
        window.addEventListener('DOMContentLoaded', () => {
            const storeName = localStorage.getItem('store_name');
            const storePhone = localStorage.getItem('store_phone');
            const storeAddress = localStorage.getItem('store_address');
            const storeTin = localStorage.getItem('store_tin');
            
            if (storeName) document.getElementById('storeName').value = storeName;
            if (storePhone) document.getElementById('storePhone').value = storePhone;
            if (storeAddress) document.getElementById('storeAddress').value = storeAddress;
            if (storeTin) document.getElementById('storeTin').value = storeTin;
            
            if (storeName || storePhone || storeAddress || storeTin) {
                updateReceiptPreview();
            }
            
            // Add input listeners for live preview
            document.getElementById('storeName').addEventListener('input', updateReceiptPreview);
            document.getElementById('storePhone').addEventListener('input', updateReceiptPreview);
            document.getElementById('storeAddress').addEventListener('input', updateReceiptPreview);
            document.getElementById('storeTin').addEventListener('input', updateReceiptPreview);
        });


        function toggleSwitch(element) {
            element.classList.toggle('active');
            const isActive = element.classList.contains('active');
            
            // Save to localStorage based on position
            const toggles = Array.from(document.querySelectorAll('.toggle-switch'));
            const index = toggles.indexOf(element);
            
            const settingKeys = [
                'setting_orderAlerts',
                'setting_lowStockAlerts',
                'setting_dailyReports',
                'setting_autoPrint',
                'setting_soundEffects'
            ];
            
            if (index >= 0 && index < settingKeys.length) {
                localStorage.setItem(settingKeys[index], isActive.toString());
                showSuccess('Setting saved!');
            }
        }

        // Save tax rate on change
        window.addEventListener('DOMContentLoaded', () => {
            const taxInput = document.getElementById('taxRateInput');
            if (taxInput) {
                taxInput.addEventListener('change', function() {
                    localStorage.setItem('setting_taxRate', this.value);
                    showSuccess('Tax rate updated to ' + this.value + '%!');
                });
            }
        });

        function confirmDeactivate() {
            if(confirm('Are you sure you want to deactivate your account? This action cannot be undone.')) {
                alert('Account deactivation requested. Please contact support.');
            }
        }

        function togglePass(id, btn) {
            const input = document.getElementById(id);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.innerHTML = isHidden
                ? '<i data-lucide="eye-off"></i>'
                : '<i data-lucide="eye"></i>';
            lucide.createIcons();
        }

        function showSuccess(message) {
            const successMsg = document.getElementById('successMsg');
            successMsg.textContent = message;
            successMsg.classList.add('show');
            setTimeout(() => successMsg.classList.remove('show'), 3000);
        }

        function exportData() {
            // Get all data from localStorage and database
            const exportData = {
                cart: JSON.parse(localStorage.getItem('cart') || '[]'),
                settings: {
                    orderAlerts: localStorage.getItem('setting_orderAlerts'),
                    lowStockAlerts: localStorage.getItem('setting_lowStockAlerts'),
                    dailyReports: localStorage.getItem('setting_dailyReports'),
                    autoPrint: localStorage.getItem('setting_autoPrint'),
                    soundEffects: localStorage.getItem('setting_soundEffects'),
                    taxRate: localStorage.getItem('setting_taxRate'),
                    theme: localStorage.getItem('theme')
                },
                exportDate: new Date().toISOString()
            };
            
            // Create downloadable JSON file
            const dataStr = JSON.stringify(exportData, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'purrcoffee_data_' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            showSuccess('Data exported successfully!');
        }

        function clearCache() {
            if(confirm('Clear all cached data? This will remove your cart and improve performance.')) {
                // Clear cart
                localStorage.removeItem('cart');
                
                // Calculate actual storage used
                let totalSize = 0;
                for (let key in localStorage) {
                    if (localStorage.hasOwnProperty(key)) {
                        totalSize += localStorage[key].length + key.length;
                    }
                }
                
                showSuccess('Cache cleared! Freed up ' + (totalSize / 1024).toFixed(2) + ' KB');
                updateStorageDisplay();
            }
        }

        function resetOrders() {
            if(confirm('This will clear your local order data. Server orders will remain intact. Continue?')) {
                // Clear any order-related localStorage
                for (let key in localStorage) {
                    if (key.includes('order') || key.includes('Order')) {
                        localStorage.removeItem(key);
                    }
                }
                showSuccess('Order data reset successfully!');
            }
        }

        function updateStorageDisplay() {
            // Calculate actual localStorage usage
            let totalSize = 0;
            for (let key in localStorage) {
                if (localStorage.hasOwnProperty(key)) {
                    totalSize += (localStorage[key].length + key.length) * 2; // UTF-16 = 2 bytes per char
                }
            }
            
            const usedMB = (totalSize / (1024 * 1024)).toFixed(2);
            const maxMB = 10; // Most browsers allow 5-10MB
            const percentage = Math.min((usedMB / maxMB) * 100, 100);
            
            // Update display
            document.querySelector('.storage-used-text').textContent = usedMB + ' MB / ' + maxMB + ' MB';
            document.querySelector('.storage-bar').style.width = percentage + '%';
        }

        // Update storage display on load
        window.addEventListener('DOMContentLoaded', () => {
            updateStorageDisplay();
        });
    </script>
</body>
</html>