<?php
/**
 * Image Optimization Helper Functions
 * @version 1.0
 */

/**
 * Get optimized image path with caching
 */
function getOptimizedImagePath($image_path, $max_width = 200) {
    if (empty($image_path)) {
        return 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
    }
    
    // If external URL, return as-is
    if (strpos($image_path, 'http') === 0) {
        return $image_path;
    }
    
    // Check if file exists
    if (!file_exists($image_path)) {
        return 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
    }
    
    return $image_path;
}

/**
 * Compress and optimize uploaded image
 */
function optimizeUploadedImage($source_file, $max_width = 300, $quality = 85) {
    if (!file_exists($source_file)) {
        return false;
    }
    
    // Get image info
    $info = getimagesize($source_file);
    if (!$info) {
        return false;
    }
    
    $mime = $info['mime'];
    $width = $info[0];
    $height = $info[1];
    
    // Load image based on type
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_file);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_file);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source_file);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Calculate new dimensions (maintain aspect ratio)
    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = intval($height * ($max_width / $width));
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    // Create resized image
    $resized = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG
    if ($mime === 'image/png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }
    
    // Resize
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save optimized image
    $output_quality = min($quality, 100);
    
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($resized, $source_file, $output_quality);
            break;
        case 'image/png':
            imagepng($resized, $source_file, intval(9 - ($output_quality / 100) * 9));
            break;
        case 'image/webp':
            imagewebp($resized, $source_file, $output_quality);
            break;
    }
    
    // Free up memory
    imagedestroy($image);
    imagedestroy($resized);
    
    return true;
}

/**
 * Get image dimensions safely
 */
function getImageDimensions($image_path) {
    if (!file_exists($image_path)) {
        return ['width' => 0, 'height' => 0];
    }
    
    $info = getimagesize($image_path);
    if (!$info) {
        return ['width' => 0, 'height' => 0];
    }
    
    return [
        'width' => $info[0],
        'height' => $info[1],
        'mime' => $info['mime']
    ];
}

/**
 * Create responsive image HTML with srcset
 */
function createResponsiveImage($image_path, $alt_text = 'Image', $classes = '', $max_width = 200) {
    $image_path = getOptimizedImagePath($image_path);
    
    return sprintf(
        '<img src="%s" alt="%s" class="%s" loading="lazy" style="image-rendering: crisp-edges; max-width: %dpx; height: auto;" />',
        htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($alt_text, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'),
        intval($max_width)
    );
}

/**
 * Get file size in human readable format
 */
function getHumanFileSize($bytes) {
    $size = intval($bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $size > 1024 && $i < 3; $i++) {
        $size /= 1024;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

?>
