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
    <title>RVD. videos downloader - Download Videos for Free in HD</title>
    
    <meta name="description" content="Download YouTube videos to your PC or mobile device for free. Fast, secure, and easy online video downloader for YouTube, Facebook, Instagram, and TikTok in MP4/MP3 formats.">
    <meta name="keywords" content="youtube video downloader, video downloader, free youtube downloader, download youtube videos, facebook video downloader, instagram downloader, tiktok downloader, mp4, mp3">
    <meta name="author" content="VideoDownloader">
    <meta name="robots" content="index, follow">
    
    <!-- Canonical URL to prevent duplicate content issues -->
    <link rel="canonical" href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/">
    
    <!-- Open Graph for Social Media -->
    <meta property="og:title" content="RVD. videos downloader - Download Videos for Free">
    <meta property="og:description" content="Fast, secure, and easy online video downloader for YouTube, Facebook, Instagram, and TikTok.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo $_SERVER['HTTP_HOST']; ?>/">
    <meta property="og:image" content="https://<?php echo $_SERVER['HTTP_HOST']; ?>/<?php echo htmlspecialchars($site_logo); ?>">

    <!-- Favicon -->
    <link rel="icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/png">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebApplication",
      "name": "RVD. videos downloader",
      "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/",
      "description": "A free online tool to download videos from YouTube, Facebook, Instagram, and TikTok in HD quality.",
      "applicationCategory": "MultimediaApplication",
      "operatingSystem": "All",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
      }
    }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .ad-space { margin: 20px 0; }
        .comments-section { margin-top: 60px; padding-top: 40px; border-top: 1px solid var(--border-color); }
        .comment-form { background: var(--white); padding: 20px; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 30px; }
        .comment-form input, .comment-form textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid var(--border-color); border-radius: 4px; font-family: inherit; }
        .comment-form button { background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .comment-list { display: flex; flex-direction: column; gap: 15px; }
        .comment-item { background: var(--white); padding: 15px; border-radius: var(--radius); border: 1px solid var(--border-color); }
        .comment-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: var(--text-light); }
        .comment-header strong { color: var(--text-dark); font-size: 16px; }
    </style>
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
                <a href="#youtube" class="active">YouTube</a>
                <a href="#facebook">Facebook</a>
                <a href="#instagram">Instagram</a>
                <a href="#tiktok">TikTok</a>
                <a href="thumbnail.php">Thumbnails</a>
                <a href="#howto">How to</a>
            </nav>
        </div>
    </header>

    <main class="main-content container">
        
        <div class="page-header">
            <div class="breadcrumbs">
                <a href="/">Home</a> &gt; <a href="#youtube">YouTube Video Downloader</a> &gt; <span>YouTube Video Downloader</span>
            </div>
            <h1 class="page-title">YouTube Video Downloader</h1>
            <p class="subtitle">Free. No signup. Download now.</p>
        </div>

        <!-- Top Ad Space -->
        <div class="ad-space top-ad">
            <?php echo $ad_content['top_ad'] ?? ''; ?>
        </div>

        <!-- Download Input Section -->
        <div class="downloader-card">
            <div class="input-group">
                <input type="text" id="urlInput" placeholder="Paste your video link here..." autocomplete="off">
                <button id="clearBtn" class="btn-clear" style="display: none;"><i class="fa-solid fa-xmark"></i> Clear</button>
                <button id="fetchBtn" class="btn-primary"><i class="fa-solid fa-download"></i> Fetch</button>
            </div>
            <p class="terms-note">Copyrighted content is not available for download with this tool. <i class="fa-solid fa-circle-info"></i></p>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="hidden">
            <div class="spinner"></div>
            <p>Fetching video info...</p>
        </div>

        <!-- Result Section -->
        <div id="resultCard" class="result-container hidden">
            
            <!-- Video Info (Left) -->
            <div class="video-info-box">
                <h3 class="box-title">Information</h3>
                <div class="video-preview">
                    <!-- Video Thumbnail or Player -->
                    <img id="videoThumb" src="" alt="Video Thumbnail">
                    <span id="videoDuration" class="duration-badge"><i class="fa-regular fa-clock"></i> 00:00</span>
                </div>
                <div class="video-details">
                    <p id="videoAuthor" class="author"><i class="fa-solid fa-user"></i> <span>Author Name</span></p>
                    <h4 id="videoTitle" class="title">Video Title Placeholder</h4>
                    <p id="videoDesc" class="description">Video description or metadata goes here...</p>
                </div>
            </div>

            <!-- Media Options (Right) -->
            <div class="media-options-box">
                <h3 class="box-title">Media</h3>
                
                <div class="format-selection">
                    <select id="formatSel" class="custom-select">
                        <option value="mp4">MP4 - (Best Quality)</option>
                        <option value="mp3">Audio - MP3</option>
                    </select>
                </div>

                <div class="file-size-info">
                    <span class="line"></span>
                    <span class="text">File Size: <span id="fileSize">Calculating...</span></span>
                    <span class="line"></span>
                </div>

                <!-- NEW: Direct Download Status -->
                <div id="downloadStatus" style="text-align:center; color: var(--primary-color); font-size:14px; margin-bottom:10px; display:none;">Downloading... Please wait.</div>

                <button id="downloadBtn" class="btn-download-action"><i class="fa-solid fa-download"></i> Direct Download</button>
                
                <p class="terms-agree">By downloading, you are agreeing to our terms and conditions</p>
            </div>

        </div>

        <!-- Bottom Ad Space -->
        <div class="ad-space bottom-ad">
            <?php echo $ad_content['bottom_ad'] ?? ''; ?>
        </div>

        <!-- SEO Content Section -->
        <section class="seo-content">
            <h2>The Best Online Video Downloader</h2>
            <p>Welcome to our all-in-one Video Downloader. Whether you're looking to save a tutorial from YouTube, a funny clip from Facebook, an aesthetic reel from Instagram, or a viral TikTok video, we've got you covered. Our tool is fast, free, and requires no software installation or user registration.</p>
            
            <div class="features-grid">
                <div class="feature">
                    <i class="fa-brands fa-youtube" style="color: #ff0000;"></i>
                    <h3>YouTube Downloader</h3>
                    <p>Download YouTube videos in MP4 format or extract audio as high-quality MP3.</p>
                </div>
                <div class="feature">
                    <i class="fa-brands fa-facebook" style="color: #1877f2;"></i>
                    <h3>Facebook Downloader</h3>
                    <p>Easily save public Facebook videos directly to your device.</p>
                </div>
                <div class="feature">
                    <i class="fa-brands fa-instagram" style="color: #e1306c;"></i>
                    <h3>Instagram Downloader</h3>
                    <p>Save Instagram Reels and videos securely with our simple tool.</p>
                </div>
                <div class="feature">
                    <i class="fa-brands fa-tiktok" style="color: #000000;"></i>
                    <h3>TikTok Downloader</h3>
                    <p>Download your favorite TikTok videos for the best offline viewing experience.</p>
                </div>
            </div>
        </section>

        <!-- Comments Section -->
        <section class="comments-section">
            <h2>User Comments</h2>
            
            <div class="comment-form">
                <h4>Leave a Comment</h4>
                <form id="commentForm">
                    <input type="text" id="commentName" placeholder="Your Name" required>
                    <textarea id="commentText" rows="3" placeholder="Write a comment..." required></textarea>
                    <button type="submit">Post Comment</button>
                </form>
            </div>

            <div class="comment-list" id="commentList">
                <!-- Comments loaded via JS -->
            </div>
        </section>

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

    <div id="toastContainer" class="toast-container"></div>

    <script src="assets/js/app.js?v=<?php echo filemtime(__DIR__ . '/assets/js/app.js'); ?>"></script>
</body>
</html>
