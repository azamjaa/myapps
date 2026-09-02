<?php
require 'db.php';
global $config;

// Konfigurasi boleh dikawal melalui .env (lihat config.php)
$ollama_url    = $config['chatbot']['ollama_url']    ?? 'http://localhost:11434/api/generate';
$ollama_model  = $config['chatbot']['ollama_model']  ?? 'mistral';
$rate_limit    = (int)($config['chatbot']['rate_limit']   ?? 15);
$rate_window   = (int)($config['chatbot']['rate_window']  ?? 60); // dalam saat

if (isset($_POST['mesej'])) {
    // ============================================================
    // RATE LIMITING - Prevent abuse
    // ============================================================
    if (!isset($_SESSION['last_chat_time'])) {
        $_SESSION['last_chat_time'] = time();
        $_SESSION['chat_count'] = 0;
    }

    $time_diff = time() - $_SESSION['last_chat_time'];
    
    if ($time_diff < $rate_window) { // Within rate window
        $_SESSION['chat_count']++;
        if ($_SESSION['chat_count'] > $rate_limit) { // Max requests per window
            http_response_code(429);
            echo "⏳ Terlalu banyak permintaan. Sila tunggu sebentar dan cuba lagi.";
            exit;
        }
    } else {
        // Reset counter after 1 minute
        $_SESSION['chat_count'] = 1;
        $_SESSION['last_chat_time'] = time();
    }
    
    // ============================================================
    // INPUT SANITIZATION & VALIDATION
    // ============================================================
    $mesej_user = strip_tags($_POST['mesej']); // Remove HTML tags
    $mesej_user = trim($mesej_user);
    
    // Limit message length
    if (strlen($mesej_user) > 500) {
        $mesej_user = substr($mesej_user, 0, 500);
    }
    
    // Check if message is empty after sanitization
    if (empty($mesej_user)) {
        echo "Sila taip soalan anda.";
        exit;
    }
    
    // 1. CARI FAQ RELEVAN TERLEBIH DAHULU
    $relevant_faq = null;
    try {
        $sql = "SELECT keyword, jawapan FROM chatbot_faq";
        $stmt = $db->query($sql);
        $all_faq = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tokenize & cari FAQ dengan skor tertinggi
        $soalan_text = strtolower($mesej_user); // Already sanitized above
        $soalan_words = preg_split('/[^a-z0-9]+/i', $soalan_text);
        $soalan_words = array_filter($soalan_words, function($w){ return strlen($w) > 2; });

        $best_score = 0;
        foreach ($all_faq as $f) {
            $kw = strtolower(strip_tags($f['keyword'] ?? ''));
            $jaw = strtolower(strip_tags($f['jawapan'] ?? ''));
            $text = $kw . ' ' . $jaw;
            $text_words = preg_split('/[^a-z0-9]+/i', $text);
            $text_words = array_filter($text_words, function($w){ return strlen($w) > 2; });

            $common = array_intersect($soalan_words, $text_words);
            $score = count($common);
            
            foreach ($soalan_words as $sw) {
                if (stripos($kw, $sw) !== false) $score += 1;
            }

            // Debug: Log scoring details
            // error_log('FAQ Check - Keyword: "' . substr($kw, 0, 60) . '" | Score: ' . $score . ' | Common words: ' . implode(',', $common));

            if ($score > $best_score) {
                $best_score = $score;
                $relevant_faq = $f;
            }
        }
    } catch (Exception $e) {
        error_log('FAQ search error: ' . $e->getMessage());
    }
    
    // JIKA ADA FAQ MATCH KUAT (MINIMUM 3 WORD), RETURN TERUS (TANPA AI)
    // Threshold > 2 means 3+ matching words required untuk yakin match
    if ($relevant_faq && $best_score > 2) {
        $jawapan = strip_tags($relevant_faq['jawapan']);
        $jawapan = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $jawapan);
        // DEBUG: Show which FAQ was matched and score
        echo "<!-- FAQ Match: Score=$best_score, Keyword=" . htmlspecialchars($relevant_faq['keyword'] ?? '') . " -->\n";
        error_log('Chatbot: Returning FAQ answer (score: ' . $best_score . ') for user question: ' . $mesej_user);
        echo nl2br(htmlspecialchars($jawapan));
        exit;
    }
    
    // 2. GUNAKAN LOCAL OLLAMA (TANPA QUOTA, TANPA API KEY)
    $nama_user = htmlspecialchars($_SESSION['nama'] ?? 'Pengguna'); // Sanitize session data

    // 3. ARAHAN WATAK (SYSTEM PROMPT) - BAHASA MELAYU SAHAJA
    $system_instruction = "Anda adalah Mawar, chatbot pembantu MyApps KEDA. WAJIB: Jawab 100% dalam Bahasa Melayu (Malaysia) sahaja. Elakkan istilah Indonesia (contoh 'membutuhkan', 'pikir', 'kemudian'); gunakan padanan BM seperti 'memerlukan', 'fikir', 'kemudian'. Jika soalan bukan BM, terjemah dan jawab dalam BM. Jawab mesra, ringkas, jelas; guna emoji jika sesuai. Panggil pengguna sebagai '$nama_user'.";

    // 4. BINA PAYLOAD UNTUK OLLAMA
    $prompt = $system_instruction . "\n\nSoalan dari pengguna: " . $mesej_user . "\n\nJawapan (MESTI dalam Bahasa Melayu Malaysia):";
    
    $data = [
        "model" => $ollama_model,
        "prompt" => $prompt,
        "stream" => false,
        "temperature" => 0.3   // Lower temperature = lebih ketat pada instruction, kurang kreatif = lebih jelas
    ];

    // 5. SETTING cURL UNTUK OLLAMA
    $ch = curl_init($ollama_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // 6. PROSES RESPONSE
    if (!empty($curl_error)) {
        error_log('Ollama Connection Error: ' . $curl_error);
        echo "Maaf, Ollama tidak berjalan. Pastikan Ollama dibuka dengan 'ollama serve' dan boleh dicapai di $ollama_url";
        exit;
    }

    if ($http_code >= 400 || empty($result)) {
        error_log('Ollama HTTP Error (' . $http_code . '): ' . substr((string)$result, 0, 300));
        echo "Maaf, tidak dapat berhubung dengan enjin AI. Sila cuba lagi.";
        exit;
    }

    $response = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Ollama JSON Decode Error: ' . json_last_error_msg() . ' | Raw: ' . substr($result, 0, 300));
        echo "Maaf, respons AI tidak sah. Sila cuba sebentar lagi.";
        exit;
    }
    
    if (!empty($response['error'])) {
        error_log('Ollama Model Error: ' . $response['error']);
        echo "Model AI tidak sedia. Pastikan model '$ollama_model' sudah di-download (contoh: 'ollama run $ollama_model').";
        exit;
    }
    
    if (isset($response['response'])) {
        $jawapan_ai = trim($response['response']);
        
        // Format Bold (**teks** kepada <b>teks</b>)
        $jawapan_ai = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $jawapan_ai);
        echo nl2br(htmlspecialchars($jawapan_ai));
        error_log('Chatbot: Ollama response sent successfully');
    } else {
        error_log('Ollama Unknown Response: ' . json_encode($response));
        echo "Ralat dari Ollama. Sila cuba sebentar lagi.";
    }
}
?>
