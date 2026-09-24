<?php
/**
 * includes/upload_validator.php
 *
 * Report finding M-6: every upload point checked only the file's
 * extension (a string an attacker fully controls), never the actual
 * file content — and banner_settings.php didn't check anything at all.
 * A file can be named photo.jpg while actually containing PHP.
 *
 * This checks the real content type using finfo (reads the file's
 * magic bytes), and cross-checks it against the claimed extension.
 */

const UPLOAD_IMAGE_TYPES = [
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'webp' => ['image/webp'],
    'gif'  => ['image/gif'],
    'svg'  => ['image/svg+xml'], // only allow if the caller explicitly opts in — SVG can carry embedded scripts
];

const UPLOAD_VIDEO_TYPES = [
    'mp4'  => ['video/mp4'],
    'webm' => ['video/webm'],
    'ogg'  => ['video/ogg', 'application/ogg'],
    'mov'  => ['video/quicktime'],
];

/**
 * @param array $file       one entry of $_FILES (already error-checked by the caller)
 * @param array $typeMap    e.g. UPLOAD_IMAGE_TYPES
 * @param int   $maxBytes   reject anything larger than this
 * @return array ['valid' => bool, 'ext' => string|null, 'error' => string|null]
 */
function validate_uploaded_file(array $file, array $typeMap, int $maxBytes = 8 * 1024 * 1024): array
{
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['valid' => false, 'ext' => null, 'error' => 'ফাইলের আকার সীমার বাইরে (max ' . round($maxBytes / 1024 / 1024) . 'MB)'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!isset($typeMap[$ext])) {
        return ['valid' => false, 'ext' => null, 'error' => 'এই ফাইল টাইপ অনুমোদিত নয়'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($realMime, $typeMap[$ext], true)) {
        error_log("Upload rejected: claimed .{$ext} but real content-type is {$realMime} (file: " . ($file['name'] ?? '?') . ")");
        return ['valid' => false, 'ext' => null, 'error' => 'ফাইলের প্রকৃত কন্টেন্ট তার এক্সটেনশনের সাথে মেলে না'];
    }

    return ['valid' => true, 'ext' => $ext, 'error' => null];
}
