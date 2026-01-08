<?php
class MyAppsClient {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $authorize_url;
    private $token_url;
    private $userinfo_url;

    public function __construct($client_id, $client_secret, $redirect_uri) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;
        $this->authorize_url = 'https://myapps.keda.gov.my/sso/authorize.php';
        $this->token_url = 'https://myapps.keda.gov.my/sso/token.php';
        $this->userinfo_url = 'https://myapps.keda.gov.my/api/userinfo.php';
    }

    // 1. Generate login URL
    public function getLoginUrl($state = '') {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'state' => $state
        ];
        return $this->authorize_url . '?' . http_build_query($params);
    }

    // 2. Exchange auth_code for access token
    public function getAccessToken($auth_code) {
        $post = [
            'grant_type' => 'authorization_code',
            'code' => $auth_code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];
        $ch = curl_init($this->token_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    // 3. Get user profile from access token
    public function getUserProfile($access_token) {
        $ch = curl_init($this->userinfo_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
?>
