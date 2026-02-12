<?php
/**
 * CRM Setup Check
 * Проверка установки и состояния базы данных
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'db');
define('DB_NAME', 'joomla_crm');
define('DB_USER', 'joomla');
define('DB_PASS', 'joomla_password');

$checks = [];
$dbConnected = false;
$pdo = null;

// Check 1: Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $checks['database'] = ['status' => true, 'message' => 'Подключение к MySQL успешно'];
    $dbConnected = true;
} catch (PDOException $e) {
    $checks['database'] = ['status' => false, 'message' => 'Ошибка подключения: ' . $e->getMessage()];
}

// Check 2: Tables exist
$requiredTables = ['crm_companies', 'crm_events', 'crm_discovery', 'crm_demos', 'crm_invoices', 'crm_certificates'];

if ($dbConnected) {
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingTables = array_diff($requiredTables, $existingTables);
    
    if (empty($missingTables)) {
        $checks['tables'] = ['status' => true, 'message' => 'Все таблицы созданы (' . count($requiredTables) . ')'];
    } else {
        $checks['tables'] = ['status' => false, 'message' => 'Отсутствуют таблицы: ' . implode(', ', $missingTables)];
    }
    
    // Check 3: Test data
    $stmt = $pdo->query("SELECT COUNT(*) FROM crm_companies");
    $companyCount = $stmt->fetchColumn();
    
    if ($companyCount > 0) {
        $checks['data'] = ['status' => true, 'message' => "Тестовые данные загружены ($companyCount компаний)"];
    } else {
        $checks['data'] = ['status' => false, 'message' => 'Нет тестовых данных'];
    }
    
    // Check 4: Events count
    $stmt = $pdo->query("SELECT COUNT(*) FROM crm_events");
    $eventCount = $stmt->fetchColumn();
    $checks['events'] = ['status' => $eventCount > 0, 'message' => "События в базе: $eventCount"];
}

// Check 5: PHP version
$checks['php'] = [
    'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'message' => 'PHP версия: ' . PHP_VERSION
];

// Check 6: PDO MySQL extension
$checks['pdo'] = [
    'status' => extension_loaded('pdo_mysql'),
    'message' => extension_loaded('pdo_mysql') ? 'PDO MySQL расширение загружено' : 'PDO MySQL не найдено'
];

$allPassed = !in_array(false, array_column($checks, 'status'));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Setup Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-12">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6">
                <h1 class="text-2xl font-bold">🔧 CRM Setup Check</h1>
                <p class="text-blue-100 mt-1">Проверка установки и конфигурации</p>
            </div>
            
            <div class="p-6">
                <!-- Overall Status -->
                <div class="mb-6 p-4 rounded-xl <?= $allPassed ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                    <div class="flex items-center gap-3">
                        <span class="text-4xl"><?= $allPassed ? '✅' : '⚠️' ?></span>
                        <div>
                            <h2 class="font-bold text-lg <?= $allPassed ? 'text-green-800' : 'text-red-800' ?>">
                                <?= $allPassed ? 'Система готова к работе!' : 'Требуется настройка' ?>
                            </h2>
                            <p class="text-sm <?= $allPassed ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $allPassed ? 'Все проверки пройдены успешно' : 'Некоторые проверки не пройдены' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Check Results -->
                <div class="space-y-3">
                    <?php foreach ($checks as $key => $check): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg <?= $check['status'] ? 'bg-green-50' : 'bg-red-50' ?>">
                        <span class="text-xl"><?= $check['status'] ? '✅' : '❌' ?></span>
                        <div class="flex-1">
                            <span class="font-medium <?= $check['status'] ? 'text-green-800' : 'text-red-800' ?>">
                                <?= ucfirst($key) ?>
                            </span>
                            <p class="text-sm <?= $check['status'] ? 'text-green-600' : 'text-red-600' ?>">
                                <?= htmlspecialchars($check['message']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Database Info -->
                <?php if ($dbConnected): ?>
                <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                    <h3 class="font-semibold text-gray-900 mb-3">📊 Информация о базе данных</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Host:</span>
                            <span class="font-medium"><?= DB_HOST ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Database:</span>
                            <span class="font-medium"><?= DB_NAME ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">User:</span>
                            <span class="font-medium"><?= DB_USER ?></span>
                        </div>
                        <div>
                            <span class="text-gray-600">Tables:</span>
                            <span class="font-medium"><?= count($existingTables ?? []) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Tables List -->
                <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                    <h3 class="font-semibold text-gray-900 mb-3">📋 Таблицы в базе</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($existingTables ?? [] as $table): ?>
                        <span class="px-3 py-1 bg-white rounded-full text-sm font-medium text-gray-700 border border-gray-200">
                            <?= htmlspecialchars($table) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="mt-6 flex gap-3">
                    <a href="/crm-api.php" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg font-medium text-center hover:bg-blue-700 transition-colors">
                        🚀 Открыть CRM
                    </a>
                    <button onclick="location.reload()" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                        🔄 Обновить
                    </button>
                </div>

                <!-- Troubleshooting -->
                <?php if (!$allPassed): ?>
                <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <h3 class="font-semibold text-amber-900 mb-2">🔧 Troubleshooting</h3>
                    <ul class="space-y-2 text-sm text-amber-800">
                        <li>• Убедитесь, что Docker контейнеры запущены: <code class="bg-amber-100 px-1 rounded">docker-compose ps</code></li>
                        <li>• Подождите 30-60 секунд после запуска для инициализации MySQL</li>
                        <li>• Проверьте логи MySQL: <code class="bg-amber-100 px-1 rounded">docker-compose logs db</code></li>
                        <li>• Перезапустите контейнеры: <code class="bg-amber-100 px-1 rounded">docker-compose down && docker-compose up -d</code></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <a href="/crm-api.php" class="text-blue-600 hover:underline">CRM Interface</a>
            <span class="mx-2">•</span>
            <a href="/crm-api.php?action=test" class="text-blue-600 hover:underline">Run Tests (JSON)</a>
            <span class="mx-2">•</span>
            <a href="http://localhost:8081" target="_blank" class="text-blue-600 hover:underline">phpMyAdmin</a>
        </div>
    </div>
</body>
</html>
