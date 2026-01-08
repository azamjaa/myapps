<?php
// sso/MyAppsClient.php

class MyAppsClient {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $baseUrl;

    public function __construct($client_id, $client_secret, $redirect_uri) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;
        
        // Ganti URL ini dengan URL sebenar MyApps anda jika dah upload ke server
        // Contoh: 'https://myapps.keda.gov.my'
        $this->baseUrl = 'http://localhost/myapps'; 
    }

    /**
     * 1. Hasilkan URL untuk redirect user ke halaman Login
     */
    public function getLoginUrl($state = 'random_state_string') {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'state' => $state
        ];
        
        return $this->baseUrl . '/sso/authorize.php?' . http_build_query($params);
    }

    /**
     * 2. Tukar Auth Code kepada Access Token (POST Request)
     */
    public function getAccessToken($auth_code) {
        $url = $this->baseUrl . '/sso/token.php';

        $data = [
            'grant_type' => 'authorization_code',
            'code' => $auth_code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        
        // Matikan semakan SSL untuk localhost (Wajib untuk XAMPP/Laragon)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL Error (Token): ' . curl_error($ch));
        }
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['error'])) {
            throw new Exception('Token Error: ' . ($result['message'] ?? $result['error']));
        }

        if (!isset($result['access_token'])) {
            throw new Exception('Gagal mendapatkan Access Token. Respons: ' . $response);
        }

        return $result['access_token'];
    }

    /**
     * 3. Dapatkan Profil Pengguna guna Token (GET Request)
     */
    public function getUserInfo($access_token) {
        $url = $this->baseUrl . '/api/userinfo.php';

        $ch = curl_init($url);
        
        // PENTING: Set Header Authorization dengan betul
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Matikan semakan SSL untuk localhost
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL Error (UserInfo): ' . curl_error($ch));
        }
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $msg = isset($data['message']) ? $data['message'] : 'Gagal dapatkan info user (HTTP '.$httpCode.')';
            throw new Exception($msg);
        }

        return $data;
    }
}
?>