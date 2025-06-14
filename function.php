<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('STORAGE_DIR', __DIR__ . '/storage');
define('IMAGES_DIR', STORAGE_DIR . '/images');

if (!is_dir(STORAGE_DIR))
    mkdir(STORAGE_DIR, 0755, true);
if (!is_dir(IMAGES_DIR))
    mkdir(IMAGES_DIR, 0755, true);

function fetchHtml($url)
{
    $context = stream_context_create([
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "timeout" => 15
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ]);
    return @file_get_contents($url, false, $context) ?: '';
}

function extractEmails($html)
{
    preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $html, $matches);
    $emails = array_unique(array_map('strtolower', $matches[0]));
    return array_values(array_filter($emails, fn($e) => !preg_match('/\.(png|jpg|jpeg|gif|svg|css|js)$/i', $e)));
}

function extractPhones($html) {
    $patterns = [
        '/\+[\d]{1,4}[\s\-]?[\d]{2,4}[\s\-]?[\d]{3,4}[\s\-]?[\d]{3,4}/',
        '/\b(?:800|855|866|877|888)[\s\-.]?\d{3}[\s\-.]?\d{4}\b/',
        '/\(\+855\)?\d{2,3}[-\s]?\d{3}[-\s]?\d{3}/',
        '/(?:phone|tel|telephone)[:\s]*([+\d][\d\s\-().]{7,})(?:\s*(?:ext|extension|x)[:\s]*(\d+))?/i'
    ];

    $phones = [];
    foreach ($patterns as $pattern) {
        preg_match_all($pattern, $html, $matches);
        foreach ($matches[0] as $num) {
            $cleaned = preg_replace('/[^\+\d]/', '', $num);
            if (str_starts_with($cleaned, '+') && strlen($cleaned) >= 9) $phones[] = $cleaned;
            elseif (preg_match('/^1?\d{10}$/', $cleaned)) $phones[] = (strlen($cleaned) === 10) ? '1'.$cleaned : $cleaned;
            elseif (strlen($cleaned) >= 8) $phones[] = $cleaned;
        }
    }
    return array_values(array_unique($phones));
}

// function extractPhones($html) {
//     // Universal phone patterns (works worldwide)
//     $patterns = [
//         // International format (+country code)
//         '/\+[\d]{1,4}[\s\-]?[\d]{2,5}[\s\-]?[\d]{3,5}[\s\-]?[\d]{3,7}/',
        
//         // Standard formats with separators
//         // '/\(?\d{2,5}\)?[\s\-.]?\d{2,5}[\s\-.]?\d{2,5}[\s\-.]?\d{2,5}/',
        
//         // Toll-free numbers
//         '/\b(?:800|855|866|877|888|1(?:800|81|82|83|84|85|86|87|88|89))[\s\-.]?\d{3}[\s\-.]?\d{4}\b/',
        
//         // Common patterns in text
//         '/(?:phone|tel|telephone|mobile|call)[:\s]*([+\d][\d\s\-().]{7,})/i',
        
//         // Special Cambodia patterns (enhanced)
//         '/\(\+855\)?\s?\d{2,3}[-\s]?\d{3}[-\s]?\d{3}/',  // (+855)12-345-678
//         '/0\d{2,3}[-\s]?\d{3}[-\s]?\d{3}/',             // 012-345-678
//         '/\d{8,9}(?![0-9])/'                             // 12345678 or 123456789
//     ];
    
//     $phones = [];
//     $cambodiaPrefixes = [
//         '10', '11', '12', '14', '15', '16', '17', '18', '19', // Mobitel
//         '23', '24', '25', '26', '27', '28', '29', '41', '42', // Cellcard
//         '31', '32', '33', '34', '35', '36', '37', '38', '39', '60', '66', '67', '68', '69', '71', '76', '77', '78', '79', '85', '89', '90', '92', '95', '99' // Smart
//     ];
    
//     foreach ($patterns as $pattern) {
//         preg_match_all($pattern, $html, $matches);
//         foreach ($matches[0] as $num) {
//             $cleaned = preg_replace('/[^\+\d]/', '', $num);
            
//             // Special handling for Cambodia numbers
//             if (str_starts_with($cleaned, '+855')) {
//                 $localNum = substr($cleaned, 4);
//                 $prefix = substr($localNum, 0, 2);
                
//                 if (in_array($prefix, $cambodiaPrefixes) && (strlen($localNum) === 8 || strlen($localNum) === 9)) {
//                     $phones[] = $cleaned;
//                 }
//             }
//             elseif (str_starts_with($cleaned, '0') && strlen($cleaned) >= 9 && strlen($cleaned) <= 10) {
//                 $prefix = substr($cleaned, 1, 2);
//                 if (in_array($prefix, $cambodiaPrefixes)) {
//                     $phones[] = '+855' . substr($cleaned, 1); // Convert to international format
//                 }
//             }
//             elseif (preg_match('/^[1-9]\d{7,9}$/', $cleaned)) {
//                 // Check if it's a Cambodia number without country code
//                 $prefix = substr($cleaned, 0, 2);
//                 if (in_array($prefix, $cambodiaPrefixes)) {
//                     $phones[] = '+855' . $cleaned;
//                 } else {
//                     // Generic international number
//                     $phones[] = $cleaned;
//                 }
//             }
//             else {
//                 // Generic international number validation
//                 if (strlen($cleaned) >= 7 && strlen($cleaned) <= 15 && !preg_match('/^[0]+$/', $cleaned)) {
//                     $phones[] = $cleaned;
//                 }
//             }
//         }
//     }
    
//     // Remove duplicates and return
//     return array_values(array_unique($phones));

// }
function extractImages($html, $baseUrl)
{
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $images = [];

    // Extract from img tags
    preg_match_all('/<img[^>]+src\s*=\s*[\'"]([^\'">]+)[\'"]/i', $html, $matches);
    $images = array_merge($images, $matches[1]);

    // Extract from CSS backgrounds
    preg_match_all('/background(?:-image)?\s*:\s*url\([\'"]?([^"\')]+)[\'"]?\)/i', $html, $matches);
    $images = array_merge($images, $matches[1]);

    // Process all found images
    $result = [];
    foreach ($images as $src) {
        $src = trim($src, "\"' ");
        if (str_starts_with($src, 'data:'))
            continue;

        $url = filter_var($src, FILTER_VALIDATE_URL) ? $src : resolveUrl($src, $baseUrl);
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (in_array($ext, $extensions))
            $result[] = $url;
    }
    return array_unique($result);
}

function resolveUrl($relative, $base)
{
    if (empty($relative))
        return '';
    if (filter_var($relative, FILTER_VALIDATE_URL))
        return $relative;

    $parsed = parse_url($base);
    $scheme = $parsed['scheme'] ?? 'http';
    $host = $parsed['host'] ?? '';

    if (str_starts_with($relative, '//'))
        return "$scheme:$relative";
    if (str_starts_with($relative, '/'))
        return "$scheme://$host$relative";

    $path = dirname($parsed['path'] ?? '/');
    return "$scheme://$host$path/" . ltrim($relative, '/');
}

function downloadImages($urls)
{
    array_map('unlink', glob(IMAGES_DIR . '/*'));

    foreach ($urls as $url) {
        try {
            $name = basename(parse_url($url, PHP_URL_PATH));
            $path = IMAGES_DIR . '/' . $name;

            $context = stream_context_create([
                "http" => ["header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"],
                "ssl" => ["verify_peer" => false]
            ]);

            if ($data = @file_get_contents($url, false, $context)) {
                file_put_contents($path, $data);
            }
        } catch (Exception $e) {
            continue;
        }
    }
}
?>