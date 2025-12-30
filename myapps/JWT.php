<?php
/**
 * Simple JWT Implementation for MyApps KEDA
 * Native PHP implementation - No external dependencies
 * 
 * @author MyApps KEDA Enterprise
 * @version 2.0
 */

class JWT {
    /**
     * Encode data to JWT token
     */
    public static function encode($payload, $secret, $algo = 'HS256') {
        $header = [
            'typ' => 'JWT',
            'alg' => $algo
        ];
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = self::sign($headerEncoded . '.' . $payloadEncoded, $secret, $algo);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Decode JWT token
     */
    public static function decode($token, $secret, $algo = 'HS256') {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = self::sign($headerEncoded . '.' . $payloadEncoded, $secret, $algo);
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Invalid signature');
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded));
        
        if (!$payload) {
            throw new Exception('Invalid payload');
        }
        
        // Check expiration
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new Exception('Token has expired');
        }
        
        return $payload;
    }
    
    /**
     * Sign data
     */
    private static function sign($data, $secret, $algo) {
        switch ($algo) {
            case 'HS256':
                return hash_hmac('sha256', $data, $secret, true);
            case 'HS384':
                return hash_hmac('sha384', $data, $secret, true);
            case 'HS512':
                return hash_hmac('sha512', $data, $secret, true);
            default:
                throw new Exception('Unsupported algorithm');
        }
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
?>

