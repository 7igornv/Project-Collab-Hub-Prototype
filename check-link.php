<?php
$baseUrl = 'http://project-collab-api.local';
$pages = [
    '/',
    '/index.html',
    '/login.html',
    '/profile.html',
    '/create-project.html',
    '/project.html?id=1',
    '/project.html?id=2'
];

echo "Проверка битых ссылок...\n\n";

foreach($pages as $page) {
    $url = $baseUrl . $page;
    $headers = get_headers($url);
    $status = substr($headers[0], 9, 3);
    
    $statusText = $status == 200 ? '✅ OK' : '❌ БИТАЯ';
    echo "$statusText $url ($status)\n";
    
    if($status != 200) {
        file_put_contents('broken-links.log', date('Y-m-d H:i:s') . " - $url - $status\n", FILE_APPEND);
    }
}
?>