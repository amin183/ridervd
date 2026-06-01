<?php
// Suppress warnings so they don't corrupt JSON output
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
// Use output buffering to discard any stray output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

@set_time_limit(90);

// ─── Helper: clean JSON output ─────────────────────────────────────────────────
function jsonOut($data, $status = 200) {
    if ($status !== 200) http_response_code($status);
    ob_clean();
    echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Multi-method command runner ───────────────────────────────────────────────
function runCommand($cmd, &$output, &$returnCode) {
    $output = []; $returnCode = -1;
    // Try exec() first
    if (function_exists('exec')) {
        $tOut = []; $tRet = -1;
        @exec('echo 1', $tOut, $tRet);
        if ($tRet === 0) { @exec($cmd, $output, $returnCode); return true; }
    }
    // Try proc_open() as fallback
    if (function_exists('proc_open')) {
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $p = @proc_open($cmd, $desc, $pipes);
        if (is_resource($p)) {
            fclose($pipes[0]);
            $raw = stream_get_contents($pipes[1]);
            fclose($pipes[1]); fclose($pipes[2]);
            $returnCode = proc_close($p);
            $output = $raw !== false && $raw !== '' ? explode("\n", rtrim($raw)) : [];
            return true;
        }
    }
    // Try shell_exec() as last resort
    if (function_exists('shell_exec')) {
        $result = @shell_exec($cmd);
        if ($result !== null && $result !== false) {
            $output = explode("\n", rtrim($result));
            $returnCode = 0;
            return true;
        }
    }
    return false;
}

// ─── Verify at least one execution method works ────────────────────────────────
$canRun = runCommand(PHP_OS === 'WINNT' ? 'echo 1' : 'echo 1', $dummyOut, $dummyRet);
if (!$canRun) {
    jsonOut(['error' => 'This server does not allow running external commands (exec, proc_open, shell_exec all disabled). Contact your hosting provider.'], 500);
}

// ─── Find yt-dlp (cross-platform) ─────────────────────────────────────────────
$ytdlp = '';
foreach (['yt-dlp.exe', 'yt-dlp'] as $binary) {
    $candidate = __DIR__ . DIRECTORY_SEPARATOR . $binary;
    if (file_exists($candidate) && is_file($candidate)) {
        $ytdlp = $candidate;
        break;
    }
}
if (!$ytdlp) {
    $whichCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where yt-dlp 2>nul' : 'which yt-dlp 2>/dev/null';
    $pathOut = []; $pathRet = -1;
    runCommand($whichCmd, $pathOut, $pathRet);
    $pathResult = trim($pathOut[0] ?? '');
    if ($pathResult && file_exists($pathResult)) {
        $ytdlp = $pathResult;
    }
}
if (!$ytdlp) {
    jsonOut(['error' => 'yt-dlp is not installed on the server.'], 500);
}

$url = trim($_GET['url'] ?? '');

if (empty($url)) {
    jsonOut(['error' => 'No URL provided.']);
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    jsonOut(['error' => 'Invalid URL.']);
}

// Only allow known platforms
$allowedHosts = [
    'youtube.com', 'youtu.be', 'www.youtube.com',
    'facebook.com', 'www.facebook.com', 'fb.watch',
    'instagram.com', 'www.instagram.com',
    'tiktok.com', 'www.tiktok.com', 'vm.tiktok.com',
    'twitter.com', 'x.com', 'www.twitter.com',
    'dailymotion.com', 'www.dailymotion.com',
    'vimeo.com', 'www.vimeo.com',
];

$parsedHost = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
$isAllowed = false;
foreach ($allowedHosts as $host) {
    if ($parsedHost === $host || substr($parsedHost, -(strlen($host) + 1)) === '.' . $host) {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    jsonOut(['error' => 'Unsupported platform. Supported: YouTube, Facebook, Instagram, TikTok, Twitter/X, Vimeo, Dailymotion.']);
}

// ─── Fetch full metadata with yt-dlp ─────────────────────────────────────────
$escapedUrl = escapeshellarg($url);
$command    = "\"{$ytdlp}\" --dump-json --no-playlist --no-warnings {$escapedUrl} 2>&1";

$output     = [];
$returnCode = -1;
$ran = runCommand($command, $output, $returnCode);
if (!$ran) {
    jsonOut(['error' => 'Failed to execute yt-dlp. No process execution method available.']);
}

$rawJson = implode('', $output);

if ($returnCode !== 0 || empty($rawJson)) {
    $errLine = '';
    foreach ($output as $line) {
        if (stripos($line, 'error') !== false || stripos($line, 'unsupported') !== false) {
            $errLine = strip_tags($line);
            break;
        }
    }
    jsonOut(['error' => $errLine ?: 'Could not fetch video info. The video may be private or unsupported.']);
}

$meta = json_decode($rawJson, true);
if (!$meta) {
    jsonOut(['error' => 'Failed to parse video metadata.']);
}

// ─── Build format list: ONLY single-stream formats (video+audio in one file) ──
// These can be delivered via direct CDN redirect without server-side merging.
$formats = [];
$seen    = [];

if (!empty($meta['formats'])) {
    foreach ($meta['formats'] as $fmt) {
        $ext    = $fmt['ext']    ?? '';
        $vcodec = $fmt['vcodec'] ?? 'none';
        $acodec = $fmt['acodec'] ?? 'none';
        $height = (int)($fmt['height'] ?? 0);
        $fmtId  = $fmt['format_id'] ?? '';

        // Single-stream: has BOTH video and audio tracks in one container
        $hasBothTracks = ($vcodec !== 'none' && $acodec !== 'none');

        if ($hasBothTracks && $height > 0 && in_array($ext, ['mp4', 'webm', 'mkv'])) {
            $label = "{$height}p " . strtoupper($ext);
            if (!isset($seen[$label])) {
                $seen[$label] = true;
                $formats[] = [
                    'id'       => $fmtId,
                    'label'    => $label,
                    'type'     => 'video',
                    'height'   => $height,
                    'ext'      => $ext,
                    'filesize' => $fmt['filesize'] ?? $fmt['filesize_approx'] ?? null,
                ];
            }
        }
    }
    // Sort highest quality first
    usort($formats, fn($a, $b) => $b['height'] - $a['height']);
}

// If no single-stream formats found, add a generic "best available" fallback
if (empty($formats)) {
    $formats[] = [
        'id'       => 'best[vcodec!=none][acodec!=none]/best[ext=mp4]/best',
        'label'    => 'Best Available (MP4)',
        'type'     => 'video',
        'height'   => 0,
        'ext'      => 'mp4',
        'filesize' => null,
    ];
}

// Audio-only option (redirects to bestaudio CDN URL — typically m4a/opus)
$formats[] = [
    'id'       => 'bestaudio[ext=m4a]/bestaudio',
    'label'    => 'Audio Only (M4A)',
    'type'     => 'audio',
    'height'   => 0,
    'ext'      => 'm4a',
    'filesize' => null,
];

// ─── Thumbnail (pick highest resolution) ─────────────────────────────────────
$thumbnail = $meta['thumbnail'] ?? '';
if (empty($thumbnail) && !empty($meta['thumbnails'])) {
    $thumbs = $meta['thumbnails'];
    usort($thumbs, fn($a, $b) =>
        (($b['width'] ?? 0) * ($b['height'] ?? 0)) - (($a['width'] ?? 0) * ($a['height'] ?? 0))
    );
    $thumbnail = $thumbs[0]['url'] ?? '';
}

// ─── Duration ─────────────────────────────────────────────────────────────────
$durationSecs = (int)($meta['duration'] ?? 0);
$durationStr  = '--:--';
if ($durationSecs > 0) {
    $h = floor($durationSecs / 3600);
    $m = floor(($durationSecs % 3600) / 60);
    $s = $durationSecs % 60;
    $durationStr = $h > 0
        ? sprintf('%d:%02d:%02d', $h, $m, $s)
        : sprintf('%02d:%02d', $m, $s);
}

// ─── Platform name ────────────────────────────────────────────────────────────
$extractor  = strtolower($meta['extractor_key'] ?? $meta['extractor'] ?? 'unknown');
$platformMap = [
    'youtube'     => 'YouTube',
    'facebook'    => 'Facebook',
    'instagram'   => 'Instagram',
    'tiktok'      => 'TikTok',
    'twitter'     => 'Twitter',
    'dailymotion' => 'Dailymotion',
    'vimeo'       => 'Vimeo',
];
$platform = $platformMap[$extractor] ?? ucfirst($extractor);

jsonOut([
    'success'     => true,
    'title'       => $meta['title']    ?? 'Untitled Video',
    'author'      => $meta['uploader'] ?? $meta['channel'] ?? $platform,
    'platform'    => $platform,
    'thumbnail'   => $thumbnail,
    'duration'    => $durationStr,
    'description' => function_exists('mb_substr') ? mb_substr($meta['description'] ?? 'No description available.', 0, 300) : substr($meta['description'] ?? 'No description available.', 0, 300),
    'view_count'  => $meta['view_count'] ?? null,
    'formats'     => array_values($formats),
]);
