<?php
/**
 * Converts rendered PNGs to web formats with GD.
 * Usage: php convert.php <manifest.json>
 * Manifest: [{"src": "...png", "dest": "...jpg|webp", "quality": 86}]
 */
declare(strict_types=1);

$manifest = json_decode((string)file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
foreach ($manifest as $job) {
    $image = imagecreatefrompng($job['src']);
    if ($image === false) {
        fwrite(STDERR, "Cannot read {$job['src']}\n");
        exit(1);
    }
    if (!is_dir(dirname($job['dest']))) {
        mkdir(dirname($job['dest']), 0777, true);
    }
    $ext = strtolower(pathinfo($job['dest'], PATHINFO_EXTENSION));
    if ($ext === 'webp') {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        $ok = imagewebp($image, $job['dest'], $job['quality']);
    } else {
        $ok = imagejpeg($image, $job['dest'], $job['quality']);
    }
    if (!$ok) {
        fwrite(STDERR, "Cannot write {$job['dest']}\n");
        exit(1);
    }
}
echo 'Converted ' . count($manifest) . " images\n";
