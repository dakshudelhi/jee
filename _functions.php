<?php
//=============================================================================//
// FOR EDUCATION PURPOSE ONLY. Don't Sell this Script, This is 100% Free.
// Join Community https://t.me/ygxworld, https://t.me/ygx_chat
//=============================================================================//

function generateDDToken() {
    return base64_encode(json_encode([
        'schema_version' => '1',
        'os_name' => 'N/A',
        'os_version' => 'N/A',
        'platform_name' => 'Chrome',
        'platform_version' => '104',
        'device_name' => '',
        'app_name' => 'Web',
        'app_version' => '2.52.31',
        'player_capabilities' => [
            'audio_channel' => ['STEREO'],
            'video_codec' => ['H264'],
            'container' => ['MP4', 'TS'],
            'package' => ['DASH', 'HLS'],
            'resolution' => ['240p', 'SD', 'HD', 'FHD'],
            'dynamic_range' => ['SDR']
        ],
        'security_capabilities' => [
            'encryption' => ['WIDEVINE_AES_CTR'],
            'widevine_security_level' => ['L3'],
            'hdcp_version' => ['HDCP_V1', 'HDCP_V2', 'HDCP_V2_1', 'HDCP_V2_2']
        ]
    ]));
}

function generateGuestToken() {
    $bin = bin2hex(random_bytes(16));
    return substr($bin, 0, 8) . '-' .
           substr($bin, 8, 4) . '-' .
           substr($bin, 12, 4) . '-' .
           substr($bin, 16, 4) . '-' .
           substr($bin, 20);
}

function fetchPlatformToken() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.zee5.com/live-tv/aaj-tak/0-9-aajtak',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'
            ]
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode !== 200) {
        exit("Your server IP is blocked.");
    }
    
    preg_match('/"gwapiPlatformToken"\s*:\s*"([^"]+)"/', $response, $matches);
    return $matches[1] ?? '';
}

function fetchM3U8url() {
    $guestToken = generateGuestToken();
    $platformToken = fetchPlatformToken();
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://spapi.zee5.com/singlePlayback/getDetails/secure?channel_id=0-9-9z583538&device_id=' . $guestToken . '&platform_name=desktop_web&translation=en&user_language=en,hi,te&country=IN&state=&app_version=4.24.0&user_type=guest&check_parental_control=false',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'origin: https://www.zee5.com',
            'referer: https://www.zee5.com/',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'x-access-token' => $platformToken,
            'X-Z5-Guest-Token' => $guestToken,
            'x-dd-token' => generateDDToken()
        ])
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    $responseData = json_decode($response, true);
    
    if (!$responseData) {
        exit("Error: Invalid response received from API. Most probably your server IP is blocked.");
    }

    if (isset($responseData['keyOsDetails']['video_token'])) {
        if (!filter_var($responseData['keyOsDetails']['video_token'], FILTER_VALIDATE_URL)) {
            exit("Error: Invalid URL received.");
        }
        return $responseData['keyOsDetails']['video_token'];
    } else {
        exit("Error: Could not fetch m3u8 URL");
    }
}

function generateCookieZee5($userAgent) {
    $m3u8Url = fetchM3U8url();
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $m3u8Url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode !== 200) {
        exit("Error: Required hdntl token can't be extracted, most probably your server IP is blocked.");
    }
    
    if (preg_match('/hdntl=([^\s"]+)/', $result, $matches)) {
        return ['cookie' => $matches[0]];
    }
    exit("Error: Something went wrong.");
}

//@yuvraj824
