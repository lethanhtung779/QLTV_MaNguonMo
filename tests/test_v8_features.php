<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use Library\Core\Database;
use Library\Services\IntentClassifier;
use Library\Services\ChatService;

$checks = 0;
$assert = static function(bool $condition, string $msg) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $msg);
    }
};

echo "Running MASTER V8 test suite...\n";

// 1. Test IntentClassifier with V8 intents
$classifier = new IntentClassifier();

$c1 = $classifier->classify('Cho tôi xem sách yêu thích của tôi');
$assert($c1['intent'] === 'personal_favorites', 'Classified personal_favorites for "sách yêu thích của tôi"');

$c2 = $classifier->classify('Tủ sách của tôi có những gì');
$assert($c2['intent'] === 'personal_favorites', 'Classified personal_favorites for "Tủ sách của tôi"');

$c3 = $classifier->classify('Hướng dẫn in tem mã vạch gáy sách');
$assert($c3['intent'] === 'print_labels_guide', 'Classified print_labels_guide for "in tem mã vạch"');

$c4 = $classifier->classify('Cách kiểm kê kho thư viện theo đợt');
$assert($c4['intent'] === 'inventory_guide', 'Classified inventory_guide for "kiểm kê kho"');

// 2. Test ChatService responses for V8 intents
$db = Database::connection();
$chatService = new ChatService($db);

// Simulate logged-in reader session
$_SESSION['user'] = [
    'id' => 103,
    'email' => 'minhpham11589@gmail.com',
    'role' => 'reader',
    'name' => 'Phạm Nhật Minh',
    'reader_id' => 103,
];

// Test favorites in chat
$r1 = $chatService->reply('Cho tôi xem sách yêu thích');
$assert($r1['intent'] === 'personal_favorites', 'Chat reply intent is personal_favorites');
$assert(str_contains($r1['message'], 'Tủ sách') || str_contains($r1['message'], 'yêu thích'), 'Chat reply mentions favorites/bookshelf');
$assert(!empty($r1['links']), 'Chat reply includes direct link to bookshelf');

// Test print labels guide in chat
$r2 = $chatService->reply('Làm sao để in tem mã vạch');
$assert($r2['intent'] === 'print_labels_guide', 'Chat reply intent is print_labels_guide');
$assert(str_contains($r2['message'], 'In tem nhãn') && str_contains($r2['message'], 'Code 128'), 'Chat reply explains barcode printing');

// Test inventory guide in chat
$r3 = $chatService->reply('Hướng dẫn đợt kiểm kê kho');
$assert($r3['intent'] === 'inventory_guide', 'Chat reply intent is inventory_guide');
$assert(str_contains($r3['message'], 'Kiểm kê kho'), 'Chat reply explains inventory audit module');

// Clean session
unset($_SESSION['user'], $_SESSION['_chat']);

echo "PASS: Master V8 test suite ({$checks} assertions passed).\n";
