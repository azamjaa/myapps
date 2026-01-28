<?php
/**
 * Text Helper Functions
 * Utility functions for text processing
 */

/**
 * Convert all string values in an array to uppercase (recursive)
 * This ensures all text data stored in database is in uppercase
 * 
 * @param mixed $data Array or string to convert
 * @return mixed Converted data with all strings in uppercase
 */
if (!function_exists('convertToUppercase')) {
function convertToUppercase($data) {
    if (is_array($data)) {
        $result = [];
        foreach ($data as $key => $value) {
            // Skip geometry coordinates (numbers, not text)
            if ($key === 'geometry' || $key === 'coordinates') {
                $result[$key] = $value;
            } elseif (is_array($value)) {
                // Recursive for nested arrays
                $result[$key] = convertToUppercase($value);
            } elseif (is_string($value) && !empty(trim($value))) {
                // Convert string to uppercase
                $result[$key] = mb_strtoupper(trim($value), 'UTF-8');
            } else {
                // Keep non-string values as is (numbers, null, etc)
                $result[$key] = $value;
            }
        }
        return $result;
    } elseif (is_string($data) && !empty(trim($data))) {
        return mb_strtoupper(trim($data), 'UTF-8');
    }
    return $data;
}
}
