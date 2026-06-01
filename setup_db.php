<?php
// setup_db.php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create comments table
    $db->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        comment TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create ads table
    $db->exec("CREATE TABLE IF NOT EXISTS ads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        position TEXT UNIQUE NOT NULL,
        html_content TEXT
    )");

    // Insert default ad slots if they don't exist
    $stmt = $db->prepare("INSERT OR IGNORE INTO ads (position, html_content) VALUES (?, ?)");
    $stmt->execute(['top_ad', '<div style="background:#eee; padding:20px; text-align:center; border:1px dashed #ccc;">Top Ad Placeholder - Edit in Admin</div>']);
    $stmt->execute(['bottom_ad', '<div style="background:#eee; padding:20px; text-align:center; border:1px dashed #ccc;">Bottom Ad Placeholder - Edit in Admin</div>']);

    // Create settings table
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT
    )");

    // Insert default settings if they don't exist
    $stmt = $db->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['site_logo', 'assets/img/Logo.png']);
    $stmt->execute(['page_about', '']);
    $stmt->execute(['page_contact', '']);
    $stmt->execute(['page_privacy', '']);

    echo "Database setup complete! SQLite file created.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
