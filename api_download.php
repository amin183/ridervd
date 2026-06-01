<?php
/**
 * api_download.php — Zero-storage hybrid strategy
 *
 * ALL platforms: yt-dlp pipes stdout → PHP → browser. No file saved to disk.
 * - For TikTok/Instagram/Facebook: proc_open with -o - (pipe mode)
 * - For YouTube/Vimeo/others: yt-dlp --get-url + cURL proxy
 */

// Suppress warnings so they don't corrupt output
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

@set_time_limit(300);
@ini_set('output_buffering', 'Off');

// ─── Helper: send error and exit ───────────────────────────────────────────────
function sendError($msg, $code = 500) {
    if (ob_get_level()) ob_clean();
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    die($msg);
}

// ─── Multi-method command runner ───────────────────────────────────────────────
function runCommand($cmd, &$output, &$returnCode) {
    $output = []; $returnCode = -1;
    if (function_exists('exec')) {
        $tOut = []; $tRet = -1;
        @exec('echo 1', $tOut, $tRet);
        if ($tRet === 0) { @exec($cmd, $output, $returnCode); return true; }
    }
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

// ─── Test available execution methods ──────────────────────────────────────────
$canRun = runCommand(PHP_OS === 'WINNT' ? 'echo 1' : 'echo 1', $dummyOut, $dummyRet);
if (!$canRun) {
    sendError('This server does not allow running external commands (exec, proc_open, shell_exec all disabled).');
}
// Test proc_open specifically for pipe mode
$procOpenWorks = false;
if (function_exists('proc_open')) {
    $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $tp = @proc_open('echo 1', $desc, $tpipes);
    if (is_resource($tp)) { fclose($tpipes[0]); fclose($tpipes[1]); fclose($tpipes[2]); proc_close($tp); $procOpenWorks = true; }
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
    sendError('yt-dlp is not installed on this server.');
}

$url      = trim($_GET['url']    ?? '');
$formatId = trim($_GET['format'] ?? '');
$type     = trim($_GET['type']   ?? 'video');

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400); die('Invalid or missing URL.');
}

if (empty($formatId) || !preg_match('/^[a-zA-Z0-9\+\/\-\_\.\[\]=!]+$/', $formatId)) {
    $formatId = 'best[vcodec!=none][acodec!=none][ext=mp4]/best[ext=mp4]/18/best';
}

$escapedUrl = escapeshellarg($url);
$parsedHost = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
$isPipe     = strpos($parsedHost, 'tiktok.com') !== false    ||
              strpos($parsedHost, 'instagram.com') !== false ||
              strpos($parsedHost, 'facebook.com') !== false  ||
              strpos($parsedHost, 'fb.watch') !== false;

$fileExt  = ($type === 'audio') ? 'm4a' : 'mp4';
$filename = 'rvd_video_' . time() . '.' . $fileExt;
$mime     = ($fileExt === 'm4a') ? 'audio/mp4' : 'video/mp4';

// ═══════════════════════════════════════════════════════════════════════════════
// STAGE A — TikTok / Instagram / Facebook
// Pipe yt-dlp stdout directly to browser. Zero disk usage.
// ═══════════════════════════════════════════════════════════════════════════════
if ($isPipe) {
    if (!$procOpenWorks) {
        sendError('PHP proc_open() is disabled on this server (required for direct download from this platform).');
    }
    // Force a pre-merged single-stream format so yt-dlp can pipe to stdout
    // (merging two streams requires seekable output and cannot be piped)
    $pipeFmt = escapeshellarg(
        'b[vcodec!=none][acodec!=none][ext=mp4]/' .
        'best[vcodec!=none][acodec!=none][ext=mp4]/' .
        'best[ext=mp4]/best'
    );

    $cmd = "\"{$ytdlp}\" -f {$pipeFmt} --no-playlist --no-warnings "
         . "--add-header \"User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\" "
         . "-o - {$escapedUrl}";

    $descriptors = [
        0 => ['pipe', 'r'],  // stdin  — closed immediately
        1 => ['pipe', 'w'],  // stdout — yt-dlp writes video data here
        2 => ['pipe', 'w'],  // stderr — captured for error messages
    ];

    $process = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($process)) {
        http_response_code(500); die('Could not start download process.');
    }

    fclose($pipes[0]); // stdin not needed

    // Block until yt-dlp produces at least one byte of data (or exits on error)
    $peek = fread($pipes[1], 1024);

    if (empty($peek)) {
        // yt-dlp wrote nothing — read stderr for the reason
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        http_response_code(500);
        $errMsg = 'Download failed. The video may be private or region-locked.';
        foreach (explode("\n", $stderr) as $line) {
            $line = trim($line);
            if ($line && stripos($line, 'error') !== false) {
                $errMsg = strip_tags($line); break;
            }
        }
        die($errMsg);
    }

    // yt-dlp is producing data — stream to browser
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    if (ob_get_level()) ob_end_clean();
    flush();

    echo $peek; // first chunk already read
    flush();

    while (!feof($pipes[1])) {
        $chunk = fread($pipes[1], 131072); // 128 KB chunks
        if ($chunk !== false && $chunk !== '') {
            echo $chunk;
            flush();
        }
    }

    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// STAGE B — YouTube / Vimeo / Dailymotion
// yt-dlp --get-url → cURL proxy. Zero disk usage.
// ═══════════════════════════════════════════════════════════════════════════════
$escapedFmt = escapeshellarg($formatId);
$cmd        = "\"{$ytdlp}\" --get-url -f {$escapedFmt} --no-playlist --no-warnings {$escapedUrl} 2>&1";
$output     = [];
$retCode    = -1;
runCommand($cmd, $output, $retCode);

$cdnUrl = null;
foreach ($output as $line) {
    $line = trim($line);
    if (strpos($line, 'https://') === 0 || strpos($line, 'http://') === 0) {
        $cdnUrl = $line; break;
    }
}

// Fallback format
if (!$cdnUrl) {
    runCommand("\"{$ytdlp}\" --get-url -f " . escapeshellarg('best[ext=mp4]/18/best') .
         " --no-playlist --no-warnings {$escapedUrl} 2>&1", $out2, $retCode2);
    foreach ($out2 as $line) {
        $line = trim($line);
        if (strpos($line, 'https://') === 0 || strpos($line, 'http://') === 0) {
            $cdnUrl = $line; break;
        }
    }
}

if (!$cdnUrl) {
    http_response_code(500);
    $errMsg = 'Could not retrieve download link. Video may be private or unavailable.';
    foreach (array_merge($output, $out2 ?? []) as $line) {
        if (stripos($line, 'error') !== false || stripos($line, 'unavailable') !== false) {
            $errMsg = strip_tags(trim($line)); break;
        }
    }
    die($errMsg);
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
if (ob_get_level()) ob_end_clean();
flush();

$ch = curl_init($cdnUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 0,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_BUFFERSIZE     => 131072,
]);
curl_exec($ch);
curl_close($ch);
exit;
