<?php
// utils/JWTHandler.php

class JWTHandler {
    
    // Fungsi untuk menghasilkan Token (Encode)
    public static function generate($payload, $secret) {
        // 1. Header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = self::base64UrlEncode($header);

        // 2. Payload
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        // 3. Signature
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        // Gabungkan
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    // Fungsi untuk menyemak Token (Decode & Validate)
    public static function validate($token, $secret) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        // Semak Signature
        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            return false; // Signature tak sah (Token diubah orang)
        }

        // Decode Payload
        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);

        // Semak Expiration (exp)
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token dah expired
        }

        return $payload;
    }

    // Helper: Base64Url Encode
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // Helper: Base64Url Decode
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
?>