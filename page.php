<?php
// Fetch ads and settings from database
$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
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

$site_logo = $settings['site_logo'] ?? 'assets/img/Logo.png';

$allowed_slugs = ['about', 'contact', 'privacy'];
$page_slug = $_GET['p'] ?? 'about';
if (!in_array($page_slug, $allowed_slugs)) {
    $page_slug = 'about';
}
$setting_key = 'page_' . $page_slug;

$page_content = $settings[$setting_key] ?? '<h1>Page Not Found</h1><p>The page you are looking for does not exist.</p>';

// Determine title based on slug
$page_title = ucfirst($page_slug) . ' - RVD. videos downloader';
if ($page_slug === 'about') $page_title = 'About Us - RVD. videos downloader';
if ($page_slug === 'contact') $page_title = 'Contact Us - RVD. videos downloader';
if ($page_slug === 'privacy') $page_title = 'Privacy Policy - RVD. videos downloader';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-D2V623S87G"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-D2V623S87G');
    </script>
    <meta name="google-site-verification" content="D1FDSuHD1h1DnLdSfybcPhgLo22kb4lQ9tJ8BXihAY4" />
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="Information about RVD. videos downloader.">
    <meta name="robots" content="noindex, follow"> <!-- usually better to not index pure info pages unless they have strong content, but follow is good -->

    <!-- Favicon -->
    <link rel="icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/png">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header class="header">
        <div class="container header-inner">
            <div class="logo">
                <a href="index.php" style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="RVD. videos downloader Logo" style="height: 40px; width: auto;">
                    <span style="font-size: 24px; font-weight: 700; color: var(--text-dark); letter-spacing: -0.5px;">RVD. <span style="font-weight: 400;">videos downloader</span></span>
                </a>
            </div>
            <nav class="nav-links">
                <a href="index.php">YouTube</a>
                <a href="index.php#facebook">Facebook</a>
                <a href="index.php#instagram">Instagram</a>
                <a href="index.php#tiktok">TikTok</a>
                <a href="thumbnail.php">Thumbnails</a>
            </nav>
        </div>
    </header>

    <main class="main-content container">
        
        <div class="page-header">
            <div class="breadcrumbs">
                <a href="index.php">Home</a> &gt; <span><?php echo htmlspecialchars(ucfirst($page_slug)); ?></span>
            </div>
        </div>

        <!-- Top Ad Space -->
        <div class="ad-space top-ad" style="margin-bottom: 30px;">
            <?php echo $ad_content['top_ad'] ?? ''; ?>
        </div>

        <div class="dynamic-page-container">
            <?php echo $page_content; ?>
        </div>

        <!-- Bottom Ad Space -->
        <div class="ad-space bottom-ad" style="margin-top: 30px;">
            <?php echo $ad_content['bottom_ad'] ?? ''; ?>
        </div>

    </main>

    <!-- Global Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-links">
                <a href="page.php?p=about">About Us</a>
                <a href="page.php?p=contact">Contact Us</a>
                <a href="page.php?p=privacy">Privacy Policy</a>
            </div>
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> RVD. videos downloader. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
