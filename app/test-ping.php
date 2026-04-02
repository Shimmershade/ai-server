<?php

$baseUrl = 'http://localhost:8000/api';

echo "Testing Laravel Ping Server\n";
echo "============================\n\n";

// Тест 1: GET пинг
echo "1. Testing GET /ping\n";
$response = file_get_contents($baseUrl . '/ping');
echo "Response: " . $response . "\n\n";

// Тест 2: POST пинг с данными
echo "2. Testing POST /ping\n";
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode(['test' => 'data', 'message' => 'hello'])
    ]
];
$context = stream_context_create($options);
$response = file_get_contents($baseUrl . '/ping', false, $context);
echo "Response: " . $response . "\n\n";

// Тест 3: Статус сервера
echo "3. Testing GET /status\n";
$response = file_get_contents($baseUrl . '/status');
echo "Response: " . $response . "\n\n";

echo "All tests completed!\n";