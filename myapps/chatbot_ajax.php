<?php
require 'db.php';

if (isset($_POST['mesej'])) {
    // ============================================================
    // RATE LIMITING - Prevent abuse
    // ============================================================
    if (!isset($_SESSION['last_chat_time'])) {
        $_SESSION['last_chat_time'] = time();
        $_SESSION['chat_count'] = 0;
    }

    $time_diff = time() - $_SESSION['last_chat_time'];
    
    if ($time_diff < 60) { // Within 1 minute window
        $_SESSION['chat_count']++;
        if ($_SESSION['chat_count'] > 15) { // Max 15 requests per minute
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
    $ollama_url = 'http://localhost:11434/api/generate';
    $nama_user = htmlspecialchars($_SESSION['nama'] ?? 'Pengguna'); // Sanitize session data

    // 3. ARAHAN WATAK (SYSTEM PROMPT) - TEGAS UNTUK BAHASA MELAYU
    $system_instruction = "Anda adalah Melur, chatbot pembantu yang berbahasa Melayu. PENTING: SEMUA jawapan MESTI dalam Bahasa Melayu. Jangan jawab dalam English. Jawab dengan mesra, ringkas, dan jelas. Panggil pengguna sebagai '$nama_user'.";

    // 4. BINA PAYLOAD UNTUK OLLAMA
    $prompt = $system_instruction . "\n\nSoalan Pengguna: " . $mesej_user . "\n\nJawapan (dalam Bahasa Melayu):";
    
    $data = [
        "model" => "mistral",  // mistral lebih baik untuk Bahasa Melayu daripada llama2
        "prompt" => $prompt,
        "stream" => false,     // Jangan stream, ambil response penuh
        "temperature" => 0.5   // Lower temperature = lebih focus pada instruction
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
    $curl_error = curl_error($ch);
    curl_close($ch);

    // 6. PROSES RESPONSE
    if (!empty($curl_error)) {
        error_log('Ollama Connection Error: ' . $curl_error);
        echo "Maaf, Ollama tidak berjalan. Pastikan Ollama buka (jalankan 'ollama serve' di terminal). 😊";
    } else {
        $response = json_decode($result, true);
        
        if (isset($response['response'])) {
            $jawapan_ai = trim($response['response']);
            
            // Format Bold (**teks** kepada <b>teks</b>)
            $jawapan_ai = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $jawapan_ai);
            echo nl2br(htmlspecialchars($jawapan_ai));
            error_log('Chatbot: Ollama response sent successfully');
        } else {
            error_log('Ollama Error: ' . json_encode($response));
            echo "Ralat dari Ollama. Sila cuba sebentar lagi.";
        }
    }
}
?>
