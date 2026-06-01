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
    <title>RVD. videos downloader - Download HD Thumbnails Free</title>
    <meta name="description" content="Free tool to download YouTube video thumbnails in Full HD (1080p), High Quality, and standard sizes. Fast, secure, and no registration required.">
    <meta name="keywords" content="youtube thumbnail downloader, download youtube thumbnail, grab youtube thumbnail, save youtube thumbnail, hd thumbnail downloader, youtube thumbnail grabber">
    <meta name="robots" content="index, follow">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://<?php echo $_SERVER['HTTP_HOST']; ?>/thumbnail.php">

    <!-- Open Graph -->
    <meta property="og:title" content="RVD. videos downloader - HD Thumbnails">
    <meta property="og:description" content="Download YouTube thumbnails instantly in Full HD for free.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo $_SERVER['HTTP_HOST']; ?>/thumbnail.php">
    <meta property="og:image" content="https://<?php echo $_SERVER['HTTP_HOST']; ?>/<?php echo htmlspecialchars($site_logo); ?>">

    <!-- Favicon -->
    <link rel="icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/png">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebApplication",
      "name": "RVD. videos downloader",
      "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/thumbnail.php",
      "description": "Download YouTube video thumbnails in multiple qualities including Full HD.",
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
        /* ── Thumbnail Page Specific Styles ─────────────────────────────────── */
        .thumb-input-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            max-width: 860px;
            margin: 0 auto 40px auto;
        }

        .thumb-input-card .input-group {
            margin-bottom: 0;
        }

        /* ── Thumbnail Grid ───────────────────────────────────────────────────── */
        .thumb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1000px;
            margin: 0 auto 40px auto;
        }

        .thumb-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
        }

        .thumb-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .thumb-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .thumb-card-header h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .thumb-card-header .quality-badge {
            background: rgba(255,51,102,0.08);
            color: var(--primary-color);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        .thumb-card-img-wrap {
            position: relative;
            background: #111;
            aspect-ratio: 16/9;
            overflow: hidden;
        }

        .thumb-card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: opacity 0.3s;
        }

        .thumb-card-img-wrap .placeholder-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #1a1a2e;
            color: #555;
            font-size: 13px;
            gap: 10px;
        }

        .thumb-card-img-wrap .placeholder-overlay i {
            font-size: 2.5rem;
            color: #333;
        }

        .thumb-card-footer {
            padding: 14px 16px;
            background: var(--bg-light);
            border-top: 1px solid var(--border-color);
        }

        .btn-thumb-download {
            width: 100%;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s;
            font-family: var(--font-family);
        }

        .btn-thumb-download:hover {
            background: var(--primary-hover);
            transform: scale(1.02);
        }

        .btn-thumb-download:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Video Info Box ───────────────────────────────────────────────────── */
        .video-meta-box {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            max-width: 1000px;
            margin: 0 auto 40px auto;
            display: none;
        }

        .video-meta-box.visible { display: block; }

        .video-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .meta-item h4 {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }

        .meta-value {
            font-size: 15px;
            font-weight: 500;
            color: var(--text-dark);
            word-break: break-word;
        }

        .category-pill {
            display: inline-block;
            background: rgba(255,51,102,0.08);
            color: var(--primary-color);
            font-size: 13px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 20px;
        }

        /* ── Keywords & Tags ──────────────────────────────────────────────────── */
        .kw-section {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            max-width: 1000px;
            margin: 0 auto 24px auto;
        }

        .kw-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .kw-section-header h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .btn-copy {
            background: #1a73e8;
            color: var(--white);
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: var(--font-family);
            transition: background 0.2s;
        }

        .btn-copy:hover { background: #1557b0; }

        .chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chip {
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            color: var(--text-dark);
            transition: border-color 0.2s;
        }

        .chip:hover { border-color: var(--primary-color); }

        .chip.tag { color: #1a73e8; }

        /* ── Fetch button ─────────────────────────────────────────────────────── */
        .btn-fetch-thumb {
            background: var(--white);
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            padding: 12px 28px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-family);
            transition: background 0.2s;
            white-space: nowrap;
        }

        .btn-fetch-thumb:hover { background: var(--bg-light); }

        @media (max-width: 768px) {
            .thumb-grid { grid-template-columns: 1fr; }
            .video-meta-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
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
                <a href="thumbnail.php" class="active">Thumbnails</a>
                <a href="index.php#howto">How to</a>
            </nav>
        </div>
    </header>

    <!-- ── Main Content ───────────────────────────────────────────────────── -->
    <main class="main-content container">

        <!-- Page Header -->
        <div class="page-header">
            <div class="breadcrumbs">
                <a href="index.php">Home</a> &gt; <span>YouTube Thumbnail Downloader</span>
            </div>
            <h1 class="page-title">YouTube Thumbnail Downloader</h1>
            <p class="subtitle">Download HD, Medium & Small YouTube thumbnails instantly — free, no signup.</p>
        </div>

        <!-- Top Ad -->
        <div class="ad-space top-ad" style="margin-bottom:30px;">
            <?php echo $ad_content['top_ad'] ?? ''; ?>
        </div>

        <!-- URL Input Card -->
        <div class="thumb-input-card">
            <div class="input-group">
                <input type="text" id="thumbUrlInput" placeholder="Paste YouTube video URL here..." autocomplete="off">
                <button id="thumbClearBtn" class="btn-clear" style="display:none;"><i class="fa-solid fa-xmark"></i> Clear</button>
                <button id="thumbFetchBtn" class="btn-fetch-thumb"><i class="fa-solid fa-magnifying-glass"></i> Get Thumbnails</button>
            </div>
        </div>

        <!-- Loading -->
        <div id="thumbLoading" class="hidden" style="text-align:center;margin:30px 0;">
            <div class="spinner"></div>
            <p>Fetching thumbnails...</p>
        </div>

        <!-- Video Meta Info (shown after fetch) -->
        <div class="video-meta-box" id="videoMetaBox">
            <div class="video-meta-grid">
                <div class="meta-item">
                    <h4><i class="fa-solid fa-heading" style="color:var(--primary-color);margin-right:5px;"></i> Title</h4>
                    <p class="meta-value" id="metaTitle">—</p>
                </div>
                <div class="meta-item">
                    <h4><i class="fa-solid fa-tag" style="color:var(--primary-color);margin-right:5px;"></i> Category</h4>
                    <p class="meta-value" id="metaCategory">—</p>
                </div>
            </div>
        </div>

        <!-- Thumbnail Cards -->
        <div class="thumb-grid" id="thumbGrid">

            <!-- Full HD -->
            <div class="thumb-card">
                <div class="thumb-card-header">
                    <h3>Full HD Thumbnail</h3>
                    <span class="quality-badge">1280×720</span>
                </div>
                <div class="thumb-card-img-wrap">
                    <div class="placeholder-overlay" id="phMaxres">
                        <i class="fa-solid fa-image"></i>
                        <span>Paste a URL above</span>
                    </div>
                    <img id="imgMaxres" src="" alt="Full HD Thumbnail" style="display:none;" crossorigin="anonymous">
                </div>
                <div class="thumb-card-footer">
                    <button class="btn-thumb-download" id="btnMaxres" onclick="downloadThumb('maxres')" disabled>
                        <i class="fa-solid fa-download"></i> Download Full HD
                    </button>
                </div>
            </div>

            <!-- Medium -->
            <div class="thumb-card">
                <div class="thumb-card-header">
                    <h3>Medium Thumbnail</h3>
                    <span class="quality-badge">640×480</span>
                </div>
                <div class="thumb-card-img-wrap">
                    <div class="placeholder-overlay" id="phMedium">
                        <i class="fa-solid fa-image"></i>
                        <span>Paste a URL above</span>
                    </div>
                    <img id="imgMedium" src="" alt="Medium Thumbnail" style="display:none;" crossorigin="anonymous">
                </div>
                <div class="thumb-card-footer">
                    <button class="btn-thumb-download" id="btnMedium" onclick="downloadThumb('medium')" disabled>
                        <i class="fa-solid fa-download"></i> Download Medium
                    </button>
                </div>
            </div>

            <!-- Small -->
            <div class="thumb-card">
                <div class="thumb-card-header">
                    <h3>Small Thumbnail</h3>
                    <span class="quality-badge">320×180</span>
                </div>
                <div class="thumb-card-img-wrap">
                    <div class="placeholder-overlay" id="phSmall">
                        <i class="fa-solid fa-image"></i>
                        <span>Paste a URL above</span>
                    </div>
                    <img id="imgSmall" src="" alt="Small Thumbnail" style="display:none;" crossorigin="anonymous">
                </div>
                <div class="thumb-card-footer">
                    <button class="btn-thumb-download" id="btnSmall" onclick="downloadThumb('small')" disabled>
                        <i class="fa-solid fa-download"></i> Download Small
                    </button>
                </div>
            </div>

        </div>

        <!-- Keywords Section -->
        <div class="kw-section" id="kwSection" style="display:none;">
            <div class="kw-section-header">
                <h3><i class="fa-solid fa-key" style="color:var(--primary-color);margin-right:6px;"></i> Keywords</h3>
                <button class="btn-copy" onclick="copyChips('keywords')"><i class="fa-regular fa-copy"></i> Copy All</button>
            </div>
            <div class="chips-wrap" id="keywordsWrap"></div>
        </div>

        <!-- Tags Section -->
        <div class="kw-section" id="tagsSection" style="display:none;">
            <div class="kw-section-header">
                <h3><i class="fa-solid fa-hashtag" style="color:var(--primary-color);margin-right:6px;"></i> Tags</h3>
                <button class="btn-copy" onclick="copyChips('tags')"><i class="fa-regular fa-copy"></i> Copy All</button>
            </div>
            <div class="chips-wrap" id="tagsWrap"></div>
        </div>

        <!-- Bottom Ad -->
        <div class="ad-space bottom-ad" style="margin-top:30px;">
            <?php echo $ad_content['bottom_ad'] ?? ''; ?>
        </div>

        <!-- SEO Content -->
        <section class="seo-content">
            <h2>How to Download YouTube Thumbnails</h2>
            <p>Simply paste any YouTube video URL into the box above and click "Get Thumbnails". You'll instantly see three quality options — Full HD (1280×720), Medium (640×480), and Small (320×180) — ready to download with one click.</p>
            <div class="features-grid" style="margin-top:40px;">
                <div class="feature">
                    <i class="fa-solid fa-bolt" style="color:#f39c12;"></i>
                    <h3>Instant Fetch</h3>
                    <p>Thumbnails load immediately after you paste your URL — no waiting required.</p>
                </div>
                <div class="feature">
                    <i class="fa-solid fa-star" style="color:#f1c40f;"></i>
                    <h3>Multiple Qualities</h3>
                    <p>Get Full HD, Medium, and Small sizes to use anywhere — websites, apps, social media.</p>
                </div>
                <div class="feature">
                    <i class="fa-solid fa-lock-open" style="color:#2ecc71;"></i>
                    <h3>Free & No Login</h3>
                    <p>Completely free. No account, no registration, no limits.</p>
                </div>
                <div class="feature">
                    <i class="fa-solid fa-mobile-screen" style="color:#3498db;"></i>
                    <h3>Works on Mobile</h3>
                    <p>Responsive design works on any device — phone, tablet, or desktop.</p>
                </div>
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

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

<script>
// ── Category detection map ────────────────────────────────────────────────────
const categoryRules = [
    { keywords: ['game','gameplay','gaming','gamer'],          label: 'Gaming' },
    { keywords: ['music','song','audio','lyrics','beat'],      label: 'Music' },
    { keywords: ['tutorial','learn','education','course'],     label: 'Education' },
    { keywords: ['news','politics','breaking'],                label: 'News & Politics' },
    { keywords: ['tech','technology','science','programming'], label: 'Science & Technology' },
    { keywords: ['sports','match','cricket','football'],       label: 'Sports' },
    { keywords: ['how to','diy','style','fashion','beauty'],   label: 'Howto & Style' },
    { keywords: ['comedy','funny','meme','prank'],             label: 'Comedy' },
    { keywords: ['pets','animals','dog','cat'],                label: 'Pets & Animals' },
    { keywords: ['travel','vlog','explore'],                   label: 'Travel & Events' },
    { keywords: ['cooking','food','recipe'],                   label: 'Food & Cooking' },
    { keywords: ['fitness','workout','gym','health'],          label: 'Health & Fitness' },
];

// ── Helpers ───────────────────────────────────────────────────────────────────
let currentVideoId = '';

function showToast(msg, type = 'success') {
    const tc = document.getElementById('toastContainer');
    const t  = document.createElement('div');
    t.className = `toast ${type}`;
    const icon = type === 'error'
        ? '<i class="fa-solid fa-circle-exclamation"></i>'
        : '<i class="fa-solid fa-circle-check"></i>';
    t.innerHTML = `${icon} <span>${msg}</span>`;
    tc.appendChild(t);
    setTimeout(() => {
        t.style.animation = 'slideIn 0.3s ease reverse forwards';
        setTimeout(() => t.remove(), 300);
    }, 3000);
}

function getVideoId(url) {
    const re = /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const m  = url.match(re);
    return (m && m[2].length === 11) ? m[2] : null;
}

function detectCategory(title) {
    const t = title.toLowerCase();
    for (const rule of categoryRules) {
        if (rule.keywords.some(k => t.includes(k))) return rule.label;
    }
    return 'Entertainment';
}

// ── Show / hide a thumbnail card ──────────────────────────────────────────────
function showThumb(id, src) {
    const img = document.getElementById('img' + id);
    const ph  = document.getElementById('ph'  + id);
    const btn = document.getElementById('btn' + id);

    img.src = src;
    img.style.display = 'none';
    img.onload  = () => { ph.style.display = 'none'; img.style.display = 'block'; btn.disabled = false; };
    img.onerror = () => { ph.innerHTML = '<i class="fa-solid fa-image-slash"></i><span>Not available</span>'; btn.disabled = true; };
}

// ── Main fetch ────────────────────────────────────────────────────────────────
async function fetchThumbnails() {
    const url = document.getElementById('thumbUrlInput').value.trim();
    if (!url) { showToast('Please paste a YouTube URL', 'error'); return; }

    const videoId = getVideoId(url);
    if (!videoId) { showToast('Could not find a valid YouTube video ID', 'error'); return; }

    currentVideoId = videoId;

    document.getElementById('thumbLoading').classList.remove('hidden');
    document.getElementById('thumbFetchBtn').disabled = true;

    // Reset thumbnails
    ['Maxres','Medium','Small'].forEach(id => {
        const img = document.getElementById('img' + id);
        const ph  = document.getElementById('ph'  + id);
        img.style.display = 'none';
        ph.style.display = 'flex';
        ph.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Loading...</span>';
        document.getElementById('btn' + id).disabled = true;
    });

    // YouTube thumbnail URLs
    const thumbUrls = {
        Maxres: `https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`,
        Medium: `https://img.youtube.com/vi/${videoId}/mqdefault.jpg`,
        Small:  `https://img.youtube.com/vi/${videoId}/default.jpg`,
    };

    Object.entries(thumbUrls).forEach(([id, src]) => showThumb(id, src));

    // Fetch title via noembed
    try {
        const res  = await fetch(`https://noembed.com/embed?url=https://www.youtube.com/watch?v=${videoId}`);
        const data = await res.json();
        const title = data.title || 'YouTube Video';

        document.getElementById('metaTitle').textContent = title;
        document.getElementById('metaCategory').innerHTML =
            `<span class="category-pill">${detectCategory(title)}</span>`;
        document.getElementById('videoMetaBox').classList.add('visible');

        // Build keywords & tags from title
        const words = title.split(/\s+/).filter(w => w.length > 2).map(w => w.replace(/[^\w]/g, '')).filter(Boolean);
        const extras = ['YouTube', 'HD Thumbnail', 'Video Content', detectCategory(title)];
        const allKw  = [...new Set([...extras, ...words.map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())])];
        const allTags = [...new Set([...words, 'youtube', 'thumbnail', 'video'].map(w => '#' + w.toLowerCase().replace(/[^\w]/g, '')))];

        renderChips('keywordsWrap', allKw,  'keyword');
        renderChips('tagsWrap',     allTags, 'tag');
        document.getElementById('kwSection').style.display  = 'block';
        document.getElementById('tagsSection').style.display = 'block';

    } catch (e) {
        document.getElementById('metaTitle').textContent    = 'Title unavailable';
        document.getElementById('metaCategory').textContent = 'Entertainment';
        document.getElementById('videoMetaBox').classList.add('visible');
    }

    document.getElementById('thumbLoading').classList.add('hidden');
    document.getElementById('thumbFetchBtn').disabled = false;
    showToast('Thumbnails loaded successfully!');
}

function renderChips(containerId, items, cls) {
    const wrap = document.getElementById(containerId);
    wrap.innerHTML = '';
    items.forEach(text => {
        const s = document.createElement('span');
        s.className = 'chip ' + cls;
        s.textContent = text;
        wrap.appendChild(s);
    });
}

// ── Copy chips ────────────────────────────────────────────────────────────────
function copyChips(type) {
    const cls  = type === 'keywords' ? 'keyword' : 'tag';
    const text = Array.from(document.querySelectorAll('.chip.' + cls)).map(el => el.textContent).join(type === 'tags' ? ' ' : ', ');
    navigator.clipboard.writeText(text).then(() => showToast(type === 'keywords' ? 'Keywords copied!' : 'Tags copied!'));
}

// ── Download thumbnail ────────────────────────────────────────────────────────
async function downloadThumb(quality) {
    const imgMap = { maxres: 'Maxres', medium: 'Medium', small: 'Small' };
    const img    = document.getElementById('img' + imgMap[quality]);
    const src    = img.src;
    if (!src) return;

    showToast('Downloading thumbnail...');
    try {
        const res  = await fetch(src);
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `yt-thumbnail-${currentVideoId}-${quality}.jpg`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch {
        // Fallback: open direct URL in new tab
        window.open(src, '_blank');
    }
}

// ── Input events ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const input    = document.getElementById('thumbUrlInput');
    const clearBtn = document.getElementById('thumbClearBtn');
    const fetchBtn = document.getElementById('thumbFetchBtn');

    input.addEventListener('input', () => {
        clearBtn.style.display = input.value ? 'flex' : 'none';
        if (input.value.includes('youtube.com/') || input.value.includes('youtu.be/')) {
            fetchThumbnails();
        }
    });

    input.addEventListener('paste', () => setTimeout(fetchThumbnails, 120));

    clearBtn.addEventListener('click', () => {
        input.value = '';
        clearBtn.style.display = 'none';
        ['Maxres','Medium','Small'].forEach(id => {
            document.getElementById('img' + id).style.display = 'none';
            const ph = document.getElementById('ph' + id);
            ph.style.display = 'flex';
            ph.innerHTML = '<i class="fa-solid fa-image"></i><span>Paste a URL above</span>';
            document.getElementById('btn' + id).disabled = true;
        });
        document.getElementById('videoMetaBox').classList.remove('visible');
        document.getElementById('kwSection').style.display   = 'none';
        document.getElementById('tagsSection').style.display = 'none';
        currentVideoId = '';
    });

    fetchBtn.addEventListener('click', fetchThumbnails);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') fetchThumbnails(); });
});
</script>
</body>
</html>
