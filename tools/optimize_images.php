<?php

/**
 * Creates lightweight WebP delivery copies while preserving every original image.
 * Run after replacing source artwork: php tools/optimize_images.php
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}

if (! function_exists('imagewebp')) {
    fwrite(STDERR, "GD with WebP support is required.\n");
    exit(1);
}

function optimizeImage(string $source, string $destination, int $maximum, int $quality): void
{
    $info = @getimagesize($source);
    if (! $info) {
        throw new RuntimeException('Invalid image: '.$source);
    }

    $image = match ($info['mime']) {
        'image/jpeg' => @imagecreatefromjpeg($source),
        'image/png' => @imagecreatefrompng($source),
        'image/webp' => @imagecreatefromwebp($source),
        default => false,
    };
    if (! $image) {
        throw new RuntimeException('Unsupported image: '.$source);
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $scale = min(1, $maximum / max($width, $height));
    $targetWidth = max(1, (int) round($width * $scale));
    $targetHeight = max(1, (int) round($height * $scale));
    $output = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($output, false);
    imagesavealpha($output, true);
    imagecopyresampled($output, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    if (! imagewebp($output, $destination, $quality)) {
        throw new RuntimeException('Could not write: '.$destination);
    }

    imagedestroy($image);
    imagedestroy($output);
}

$public = dirname(__DIR__).'/public/images';
$targets = [
    [$public.'/gym-bg.png', $public.'/gym-bg.webp', 1600, 76],
    [$public.'/logo-sidebar.png', $public.'/logo-sidebar.webp', 420, 82],
];

foreach (glob($public.'/ejercicios/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [] as $source) {
    if (in_array(basename($source), ['LOGO.png', 'gym-bg.png', 'logo-sidebar.png'], true)) {
        continue;
    }
    $targets[] = [$source, substr($source, 0, (int) strrpos($source, '.')).'.webp', 960, 78];
}

foreach (glob($public.'/avatars/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [] as $source) {
    $targets[] = [$source, substr($source, 0, (int) strrpos($source, '.')).'.webp', 320, 82];
}

foreach ($targets as [$source, $destination, $maximum, $quality]) {
    optimizeImage($source, $destination, $maximum, $quality);
    echo basename($destination).' '.filesize($destination)." bytes\n";
}
