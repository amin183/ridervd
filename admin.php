<?php
session_start();
$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Admin credentials — use environment variables for production
$admin_username = getenv('ADMIN_USERNAME') ?: 'aminboss235@gmil.com';
// Pre-computed bcrypt hash of 'Allah100%' — avoids regenerating on every request
$admin_password_hash = getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$Y.HC6qA6BKARgrPf92krV.qEjKquYx660bEeu2CKijuGt1OS.w2Fi';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = $_POST['password'] ?? '';
    if ($inputUser === $admin_username && password_verify($inputPass, $admin_password_hash)) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_user'] = $inputUser;
    } else {
        $error = "Invalid credentials!";
    }
}

if (!isset($_SESSION['admin'])) {
    // Show Login Form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <style>
            .login-box { max-width: 400px; margin: 100px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; }
            .login-box input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
            .login-box button { width: 100%; padding: 10px; background: #ff3366; color: white; border: none; border-radius: 4px; cursor: pointer; }
        </style>
    </head>
    <body style="background: #f8f9fa;">
        <div class="login-box">
            <h2>Admin Login</h2>
            <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_comment'])) {
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$_POST['comment_id']]);
    }
    if (isset($_POST['update_ads'])) {
        $stmt = $db->prepare("UPDATE ads SET html_content = ? WHERE position = 'top_ad'");
        $stmt->execute([$_POST['top_ad']]);
        
        $stmt = $db->prepare("UPDATE ads SET html_content = ? WHERE position = 'bottom_ad'");
        $stmt->execute([$_POST['bottom_ad']]);
        $success = "Ads updated successfully!";
    }
    // Logo Upload
    if (isset($_POST['update_logo']) && isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/assets/img/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['logo_file']['name']));
        $uploadFile = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadFile)) {
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'");
            $stmt->execute(['assets/img/' . $newFileName]);
            $success = "Logo updated successfully!";
        } else {
            $error = "Failed to upload logo.";
        }
    }
    // Update Pages
    if (isset($_POST['update_pages'])) {
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$_POST['page_about'], 'page_about']);
        $stmt->execute([$_POST['page_contact'], 'page_contact']);
        $stmt->execute([$_POST['page_privacy'], 'page_privacy']);
        $success = "Pages updated successfully!";
    }
}

// Fetch Data
$comments = $db->query("SELECT * FROM comments ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$ads = $db->query("SELECT * FROM ads")->fetchAll(PDO::FETCH_ASSOC);
$ad_content = [];
foreach ($ads as $ad) {
    $ad_content[$ad['position']] = $ad['html_content'];
}

$settings_query = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_query as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-container { max-width: 1000px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        textarea { width: 100%; height: 100px; padding: 10px; margin-bottom: 15px; font-family: monospace; }
        .section { margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .btn-danger { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body style="background: #f8f9fa;">
    <div class="admin-container">
        <div class="admin-header">
            <h2>Admin Dashboard</h2>
            <a href="logout.php" style="color: #ff3366;">Logout</a>
        </div>
        
        <?php if (isset($success)) echo "<p style='color:green; margin-bottom:20px;'>$success</p>"; ?>

        <div class="section">
            <h3>Settings & Branding</h3>
            
            <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                <label><strong>Site Logo Image (Recommended: 200x50 PNG)</strong></label>
                <div style="margin: 10px 0;">
                    <img src="<?php echo htmlspecialchars($settings['site_logo'] ?? 'assets/img/Logo.png'); ?>" alt="Current Logo" style="height: 40px; background: #ddd; padding: 5px; border-radius: 4px;">
                </div>
                <input type="file" name="logo_file" accept="image/*" required style="margin-bottom: 10px;">
                <br>
                <button type="submit" name="update_logo" class="btn-primary" style="padding: 10px 20px;">Upload New Logo</button>
            </form>

            <form method="POST">
                <label><strong>About Us Page Content (HTML allowed)</strong></label>
                <textarea name="page_about"><?php echo htmlspecialchars($settings['page_about'] ?? ''); ?></textarea>
                
                <label><strong>Contact Us Page Content (HTML allowed)</strong></label>
                <textarea name="page_contact"><?php echo htmlspecialchars($settings['page_contact'] ?? ''); ?></textarea>
                
                <label><strong>Privacy Policy Page Content (HTML allowed)</strong></label>
                <textarea name="page_privacy"><?php echo htmlspecialchars($settings['page_privacy'] ?? ''); ?></textarea>
                
                <button type="submit" name="update_pages" class="btn-primary" style="padding: 10px 20px;">Save Pages</button>
            </form>
        </div>

        <div class="section">
            <h3>Manage Advertisements</h3>
            <form method="POST">
                <label><strong>Top Ad Placeholder (HTML)</strong></label>
                <textarea name="top_ad"><?php echo htmlspecialchars($ad_content['top_ad'] ?? ''); ?></textarea>
                
                <label><strong>Bottom Ad Placeholder (HTML)</strong></label>
                <textarea name="bottom_ad"><?php echo htmlspecialchars($ad_content['bottom_ad'] ?? ''); ?></textarea>
                
                <button type="submit" name="update_ads" class="btn-primary" style="padding: 10px 20px;">Save Ads</button>
            </form>
        </div>

        <div class="section">
            <h3>Manage Comments</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($comments as $c): ?>
                <tr>
                    <td><?php echo $c['id']; ?></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo htmlspecialchars($c['comment']); ?></td>
                    <td><?php echo $c['created_at']; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                            <button type="submit" name="delete_comment" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
