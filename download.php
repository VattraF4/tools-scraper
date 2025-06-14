<?php
require_once 'function.php';

$type = $_GET['type'] ?? '';
$validTypes = ['emails', 'phones', 'images'];

if (!in_array($type, $validTypes)) {
    header("HTTP/1.0 400 Bad Request");
    exit;
}

switch ($type) {
    case 'emails':
        $file = STORAGE_DIR . '/emails.txt';
        $mime = 'text/plain';
        $name = 'emails.txt';
        break;

    case 'phones':
        $file = STORAGE_DIR . '/phones.txt';
        $mime = 'text/plain';
        $name = 'phones.txt';
        break;

    case 'images':
        // If no images exist, return 404
        $files = glob(IMAGES_DIR . '/*');
        if (empty($files)) {
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        $zipPath = STORAGE_DIR . '/images.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            header("HTTP/1.0 500 Server Error");
            exit;
        }

        foreach ($files as $image) {
            $zip->addFile($image, basename($image));
        }
        $zip->close();

        $file = $zipPath;
        $mime = 'application/zip';
        $name = 'images.zip';
        break;
}

if (!file_exists($file)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

header("Content-Type: $mime");
header("Content-Disposition: attachment; filename=\"$name\"");
header("Content-Length: " . filesize($file));
readfile($file);

if ($type === 'images')
    unlink($file);
exit;
?>