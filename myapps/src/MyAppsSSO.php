<?php
// src/MyAppsSSO.php
// Client Library untuk SSO/SSOT Integration ke MyApps KEDA
// Digunakan oleh aplikasi pihak ketiga (eTanah, ePelawat, dsb)

namespace MyApps;

class MyAppsSSO {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $auth_endpoint;
    private $token_endpoint;
    private $userinfo_endpoint;

    /**
     * Constructor
     * @param string $client_id - Client ID dari MyApps
     * @param string $client_secret - Client Secret dari MyApps
     * @param string $redirect_uri - URL redirect selepas login
     * @param string $base_url - URL MyApps (contoh: http://localhost/myapps)
     */
    public function __construct($client_id, $client_secret, $redirect_uri, $base_url = 'http://localhost/myapps') {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;
        $this->auth_endpoint = rtrim($base_url, '/') . '/sso/authorize.php';
        $this->token_endpoint = rtrim($base_url, '/') . '/sso/token.php';
        $this->userinfo_endpoint = rtrim($base_url, '/') . '/api/userinfo.php';
    }

    /**
     * Dapatkan URL login untuk redirect user
     * @param string $state - Optional state parameter untuk CSRF protection
     * @return string - Full login URL
     */
    public function getLoginUrl($state = null) {
        if (!$state) {
            $state = bin2hex(random_bytes(16));
        }
        return $this->auth_endpoint . '?' . http_build_query([
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'state' => $state
        ]);
    }

    /**
     * Exchange authorization code untuk access token
     * @param string $code - Authorization code dari MyApps
     * @return array - Token response atau error
     */
    public function getAccessToken($code) {
        $post_data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];

        $response = $this->curl_post($this->token_endpoint, $post_data);
        return json_decode($response, true);
    }

    /**
     * Dapatkan profil user menggunakan access token
     * @param string $access_token - Access token dari getAccessToken()
     * @return array - User profile atau error
     */
    public function getUserProfile($access_token) {
        $response = $this->curl_get($this->userinfo_endpoint, $access_token);
        return json_decode($response, true);
    }

    /**
     * Helper: cURL POST
     */
    private function curl_post($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['error' => 'curl_error', 'message' => curl_error($ch)]);
        }
        curl_close($ch);
        return $result;
    }

    /**
     * Helper: cURL GET dengan Bearer token
     */
    private function curl_get($url, $token) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['error' => 'curl_error', 'message' => curl_error($ch)]);
        }
        curl_close($ch);
        return $result;
    }
}
