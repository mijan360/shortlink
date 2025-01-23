<?php

define('API_TOKEN', '7999809913:AAH_szcQ0BMZBNA1MWI-KNdOgmqwzTgUQcE'); // 
define('SHORTENER_API_TOKEN', '8a34c1e22dc09b14d14549f7cc090371e26e744a'); // 
define('SHORTENER_API_URL', 'https://linkfly.infy.uk/api'); // Shortener API URL, For Example:

// Get the incoming webhook updates
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update["message"])) {
    exit("Invalid webhook payload");
}

$message = $update["message"];
$chat_id = $message["chat"]["id"];
$text = $message["text"] ?? '';

if (strpos($text, '/start') === 0) {
    // Handle /start command
    $keyboard = [
        [
            ['text' => '🎁 Updates Channel', 'url' => 'https://t.me/pndzbots'],
            ['text' => '⚡️ Support Group', 'url' => 'https://t.me/pndaotsgroup']
        ],
        [
            ['text' => '❤️ Source Code', 'url' => 'https://github.com/PandazNeork/adnkfly-shortner-bot']
        ]
    ];

    $response = [
        'chat_id' => $chat_id,
        'text' => "⚡️ Hello!\n\nI'm your link shortener bot. Just send me a link, and I'll shorten it for you!",
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ];

    sendRequest("sendMessage", $response);
} elseif (preg_match_all('/https?:\/\/\S+/', $text, $matches)) {
    // Handle links in the message
    $links = $matches[0];
    $shortened_links = [];
    foreach ($links as $link) {
        $short_link = getShortLink($link);
        if ($short_link) {
            $shortened_links[] = $short_link;
        } else {
            $shortened_links[] = "Error shortening: $link";
        }
    }

    $response_text = implode("\n", $shortened_links);

    sendRequest("sendMessage", [
        'chat_id' => $chat_id,
        'text' => $response_text
    ]);
}

function sendRequest($method, $data) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/" . $method;

    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

function getShortLink($url) {
    $long_url = urlencode($url);
    $api_url = SHORTENER_API_URL . "?api=" . SHORTENER_API_TOKEN . "&url=" . $long_url;

    $response = @file_get_contents($api_url);
    if (!$response) {
        return null; // Return null if the request fails
    }

    $result = json_decode($response, true);
    if (isset($result["status"]) && $result["status"] === "success") {
        return $result["shortenedUrl"];
    } elseif (isset($result["message"])) {
        return "Error: " . $result["message"];
    }

    return null; // Return null for unknown errors
}

?>
