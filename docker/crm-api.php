<?php
/**
 * CRM Stages - Full PHP Implementation
 * 
 * This is the main CRM interface with full stage management logic
 * Access: http://localhost:8080/crm-api.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'db');
define('DB_NAME', 'joomla_crm');
define('DB_USER', 'joomla');
define('DB_PASS', 'joomla_password');

// Stage definitions
define('STAGES', [
    'C0' => [
        'code' => 'C0',
        'name' => 'Ice',
        'displayName' => 'Ice (Холодный)',
        'restrictions' => ['create_application', 'send_cp', 'plan_demo', 'conduct_demo'],
        'availableActions' => ['make_call'],
        'entryCondition' => 'Начальная стадия',
        'exitCondition' => 'Есть разговор с ЛПР',
        'instruction' => "📞 Инструкция для менеджера:\n\n1. Ваша цель — установить первый контакт с компанией\n2. Найдите контакты ЛПР\n3. Совершите звонок и представьтесь\n4. При успешном разговоре — заполните форму комментария\n\n⚠️ На этой стадии доступна только кнопка звонка."
    ],
    'C1' => [
        'code' => 'C1',
        'name' => 'Touched',
        'displayName' => 'Touched (Контакт)',
        'restrictions' => ['create_application', 'send_cp', 'plan_demo', 'conduct_demo'],
        'availableActions' => ['make_call', 'log_conversation', 'fill_discovery'],
        'entryCondition' => 'Есть разговор с ЛПР',
        'exitCondition' => 'Заполнена форма Discovery',
        'instruction' => "📋 Инструкция:\n\n1. Контакт установлен! Проведите Discovery\n2. Выясните потребности клиента\n3. Заполните форму Discovery\n\n⛔ Запрещено: заявка, КП, демо"
    ],
    'C2' => [
        'code' => 'C2',
        'name' => 'Aware',
        'displayName' => 'Aware (Осведомлён)',
        'restrictions' => ['plan_demo', 'conduct_demo'],
        'availableActions' => ['make_call', 'log_conversation', 'fill_discovery'],
        'entryCondition' => 'Заполнена форма Discovery',
        'exitCondition' => 'Запланировано демо',
        'instruction' => "🎯 Discovery заполнен! Продолжайте общение."
    ],
    'W1' => [
        'code' => 'W1',
        'name' => 'Interested',
        'displayName' => 'Interested (Заинтересован)',
        'restrictions' => ['create_application', 'send_cp'],
        'availableActions' => ['make_call', 'log_conversation', 'plan_demo'],
        'entryCondition' => 'Заполнена форма Discovery',
        'exitCondition' => 'Запланировано демо',
        'instruction' => "📅 Клиент заинтересован! Планируйте демо.\n\n⛔ Запрещено: заявка, КП"
    ],
    'W2' => [
        'code' => 'W2',
        'name' => 'demo_planned',
        'displayName' => 'Demo Planned (Демо запланировано)',
        'restrictions' => ['create_application', 'send_cp'],
        'availableActions' => ['make_call', 'log_conversation', 'conduct_demo'],
        'entryCondition' => 'Есть дата демо',
        'exitCondition' => 'Проведено демо',
        'instruction' => "🎬 Демо запланировано! Проведите презентацию."
    ],
    'W3' => [
        'code' => 'W3',
        'name' => 'Demo_done',
        'displayName' => 'Demo Done (Демо проведено)',
        'restrictions' => [],
        'availableActions' => ['make_call', 'log_conversation', 'create_application', 'send_cp', 'mark_invoice_sent'],
        'entryCondition' => 'Демо проведено < 60 дней',
        'exitCondition' => 'Есть заявка и/или счёт',
        'instruction' => "💼 Демо проведено! Можно:\n- Завести заявку\n- Отправить КП\n- Выставить счёт"
    ],
    'H1' => [
        'code' => 'H1',
        'name' => 'Committed',
        'displayName' => 'Committed (Обязательство)',
        'restrictions' => [],
        'availableActions' => ['make_call', 'log_conversation', 'mark_payment_received'],
        'entryCondition' => 'Выставлен счёт',
        'exitCondition' => 'Получена оплата',
        'instruction' => "💰 Счёт выставлен! Ожидаем оплату."
    ],
    'H2' => [
        'code' => 'H2',
        'name' => 'Customer',
        'displayName' => 'Customer (Клиент)',
        'restrictions' => [],
        'availableActions' => ['make_call', 'log_conversation', 'issue_certificate'],
        'entryCondition' => 'Получена оплата',
        'exitCondition' => 'Выдано удостоверение',
        'instruction' => "🎓 Оплата получена! Выдайте удостоверение."
    ],
    'A1' => [
        'code' => 'A1',
        'name' => 'Activated',
        'displayName' => 'Activated (Активирован)',
        'restrictions' => [],
        'availableActions' => ['make_call', 'log_conversation'],
        'entryCondition' => 'Выдано удостоверение',
        'exitCondition' => 'Финальная стадия',
        'instruction' => "✅ Клиент активирован! Поддерживайте отношения."
    ],
    'N0' => [
        'code' => 'N0',
        'name' => 'Null',
        'displayName' => 'Null (Отказ)',
        'restrictions' => [],
        'availableActions' => ['make_call', 'log_conversation'],
        'entryCondition' => 'Отказ',
        'exitCondition' => '-',
        'instruction' => "❌ Отказ. Возможна повторная попытка."
    ]
]);

define('STAGE_ORDER', ['C0', 'C1', 'C2', 'W1', 'W2', 'W3', 'H1', 'H2', 'A1']);

define('ACTION_LABELS', [
    'make_call' => '📞 Позвонить',
    'log_conversation' => '💬 Записать разговор с ЛПР',
    'fill_discovery' => '📋 Заполнить Discovery',
    'plan_demo' => '📅 Запланировать демо',
    'conduct_demo' => '🎬 Провести демо',
    'create_application' => '📝 Завести заявку',
    'send_cp' => '📨 Отправить КП',
    'mark_invoice_sent' => '💳 Выставить счёт',
    'mark_payment_received' => '💰 Отметить оплату',
    'issue_certificate' => '🎓 Выдать удостоверение'
]);

define('EVENT_LABELS', [
    'contact_attempt' => 'Попытка контакта',
    'lpr_conversation' => 'Разговор с ЛПР',
    'discovery_filled' => 'Discovery заполнен',
    'demo_planned' => 'Демо запланировано',
    'demo_conducted' => 'Демо проведено',
    'invoice_sent' => 'Счёт выставлен',
    'payment_received' => 'Оплата получена',
    'certificate_issued' => 'Удостоверение выдано',
    'call_answered' => 'Звонок отвечен',
    'cp_sent' => 'КП отправлено',
    'application_created' => 'Заявка создана'
]);

/**
 * CRM Stage Engine - Core business logic
 */
class StageEngine {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get company by ID with all related data
     */
    public function getCompany(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM crm_companies WHERE id = ?");
        $stmt->execute([$id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($company) {
            $company['events'] = $this->getCompanyEvents($id);
            
            // Get discovery data
            $stmt = $this->pdo->prepare("SELECT * FROM crm_discovery WHERE company_id = ?");
            $stmt->execute([$id]);
            $discovery = $stmt->fetch(PDO::FETCH_ASSOC);
            $company['discovery_data'] = $discovery ?: null;
            
            // Get demo data
            $stmt = $this->pdo->prepare("SELECT * FROM crm_demos WHERE company_id = ? ORDER BY scheduled_at DESC LIMIT 1");
            $stmt->execute([$id]);
            $demo = $stmt->fetch(PDO::FETCH_ASSOC);
            $company['demo_planned_at'] = $demo['scheduled_at'] ?? null;
            $company['demo_conducted_at'] = $demo['conducted_at'] ?? null;
            
            // Get invoice data
            $stmt = $this->pdo->prepare("SELECT * FROM crm_invoices WHERE company_id = ? ORDER BY issued_at DESC LIMIT 1");
            $stmt->execute([$id]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            $company['invoice_sent_at'] = $invoice['issued_at'] ?? null;
            $company['invoice_number'] = $invoice['invoice_number'] ?? null;
            $company['payment_received_at'] = $invoice['paid_at'] ?? null;
            
            // Get certificate data
            $stmt = $this->pdo->prepare("SELECT * FROM crm_certificates WHERE company_id = ? ORDER BY issued_at DESC LIMIT 1");
            $stmt->execute([$id]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            $company['certificate_issued_at'] = $cert['issued_at'] ?? null;
        }
        
        return $company ?: null;
    }
    
    /**
     * Get all companies
     */
    public function getAllCompanies(): array {
        $stmt = $this->pdo->query("SELECT * FROM crm_companies ORDER BY updated_at DESC");
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($companies as &$company) {
            $company['events'] = $this->getCompanyEvents($company['id']);
        }
        
        return $companies;
    }
    
    /**
     * Get company events
     */
    public function getCompanyEvents(int $companyId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM crm_events WHERE company_id = ? ORDER BY created_at DESC");
        $stmt->execute([$companyId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($events as &$event) {
            $event['event_data'] = $event['event_data'] ? json_decode($event['event_data'], true) : [];
            // Add description if not exists
            if (empty($event['description'])) {
                $event['description'] = EVENT_LABELS[$event['event_type']] ?? $event['event_type'];
            }
        }
        
        return $events;
    }
    
    /**
     * Check if action is allowed
     */
    public function isActionAllowed(array $company, string $action): bool {
        $stage = STAGES[$company['current_stage']] ?? null;
        if (!$stage) return false;
        
        // Check restrictions
        if (in_array($action, $stage['restrictions'])) {
            return false;
        }
        
        // Check if action is available
        if (!in_array($action, $stage['availableActions'])) {
            return false;
        }
        
        // Additional validations
        switch ($action) {
            case 'fill_discovery':
                return $this->hasLPRConversation($company);
            case 'plan_demo':
                return $this->hasDiscoveryFilled($company);
            case 'conduct_demo':
                return $this->hasDemoPlanned($company);
            case 'create_application':
            case 'send_cp':
            case 'mark_invoice_sent':
                return $this->hasDemoConductedWithin60Days($company);
            case 'mark_payment_received':
                return $this->hasInvoiceSent($company);
            case 'issue_certificate':
                return $this->hasPaymentReceived($company);
            default:
                return true;
        }
    }
    
    /**
     * Get available actions for company
     */
    public function getAvailableActions(array $company): array {
        $stage = STAGES[$company['current_stage']] ?? null;
        if (!$stage) return [];
        
        $available = [];
        foreach ($stage['availableActions'] as $action) {
            if ($this->isActionAllowed($company, $action)) {
                $available[] = $action;
            }
        }
        
        return $available;
    }
    
    /**
     * Get blocked actions with reasons
     */
    public function getBlockedActions(array $company): array {
        $stage = STAGES[$company['current_stage']] ?? null;
        if (!$stage) return [];
        
        $blocked = [];
        
        foreach ($stage['restrictions'] as $action) {
            $blocked[] = [
                'action' => $action,
                'reason' => "Запрещено на стадии \"{$stage['displayName']}\""
            ];
        }
        
        if (in_array('fill_discovery', $stage['availableActions']) && !$this->hasLPRConversation($company)) {
            $blocked[] = ['action' => 'fill_discovery', 'reason' => 'Сначала проведите разговор с ЛПР'];
        }
        if (in_array('plan_demo', $stage['availableActions']) && !$this->hasDiscoveryFilled($company)) {
            $blocked[] = ['action' => 'plan_demo', 'reason' => 'Сначала заполните форму Discovery'];
        }
        if (in_array('conduct_demo', $stage['availableActions']) && !$this->hasDemoPlanned($company)) {
            $blocked[] = ['action' => 'conduct_demo', 'reason' => 'Сначала запланируйте демо'];
        }
        
        return $blocked;
    }
    
    /**
     * Execute action
     */
    public function executeAction(int $companyId, string $action, array $data = []): array {
        $company = $this->getCompany($companyId);
        if (!$company) {
            return ['success' => false, 'message' => 'Компания не найдена'];
        }
        
        if (!$this->isActionAllowed($company, $action)) {
            return ['success' => false, 'message' => 'Действие недоступно на текущей стадии'];
        }
        
        $eventType = null;
        $description = '';
        $newStage = null;
        
        try {
            $this->pdo->beginTransaction();
            
            switch ($action) {
                case 'make_call':
                    $eventType = 'contact_attempt';
                    $description = 'Совершён звонок';
                    break;
                    
                case 'log_conversation':
                    $eventType = 'lpr_conversation';
                    $description = 'Разговор с ЛПР: ' . ($data['comment'] ?? 'Без комментария');
                    if ($company['current_stage'] === 'C0') {
                        $newStage = 'C1';
                    }
                    break;
                    
                case 'fill_discovery':
                    $eventType = 'discovery_filled';
                    $description = 'Форма Discovery заполнена';
                    
                    // Insert discovery data
                    $stmt = $this->pdo->prepare("
                        INSERT INTO crm_discovery (company_id, budget, timeline, decision_maker, pain_points, requirements)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            budget = VALUES(budget),
                            timeline = VALUES(timeline),
                            decision_maker = VALUES(decision_maker),
                            pain_points = VALUES(pain_points),
                            requirements = VALUES(requirements),
                            filled_at = CURRENT_TIMESTAMP
                    ");
                    $stmt->execute([
                        $companyId,
                        $data['budget'] ?? null,
                        $data['timeline'] ?? null,
                        $data['decisionMaker'] ?? null,
                        $data['painPoints'] ?? null,
                        ($data['companySize'] ?? '') . ', ' . ($data['industry'] ?? '')
                    ]);
                    
                    if (in_array($company['current_stage'], ['C1', 'C2'])) {
                        $newStage = 'W1';
                    }
                    break;
                    
                case 'plan_demo':
                    $eventType = 'demo_planned';
                    $description = 'Демо запланировано на ' . ($data['date'] ?? 'дату');
                    
                    // Insert demo
                    $stmt = $this->pdo->prepare("INSERT INTO crm_demos (company_id, scheduled_at, status) VALUES (?, ?, 'planned')");
                    $stmt->execute([$companyId, $data['date'] ?? date('Y-m-d H:i:s')]);
                    
                    if ($company['current_stage'] === 'W1') {
                        $newStage = 'W2';
                    }
                    break;
                    
                case 'conduct_demo':
                    $eventType = 'demo_conducted';
                    $description = 'Демо проведено';
                    
                    // Update demo
                    $stmt = $this->pdo->prepare("UPDATE crm_demos SET conducted_at = NOW(), status = 'conducted' WHERE company_id = ? AND status = 'planned'");
                    $stmt->execute([$companyId]);
                    
                    if ($company['current_stage'] === 'W2') {
                        $newStage = 'W3';
                    }
                    break;
                    
                case 'create_application':
                    $eventType = 'application_created';
                    $description = 'Заявка создана: ' . ($data['applicationNumber'] ?? 'Без номера');
                    break;
                    
                case 'send_cp':
                    $eventType = 'cp_sent';
                    $description = 'КП отправлено';
                    break;
                    
                case 'mark_invoice_sent':
                    $eventType = 'invoice_sent';
                    $description = 'Счёт выставлен: ' . ($data['invoiceNumber'] ?? 'Без номера');
                    
                    // Insert invoice
                    $stmt = $this->pdo->prepare("INSERT INTO crm_invoices (company_id, invoice_number, amount, status) VALUES (?, ?, ?, 'issued')");
                    $stmt->execute([$companyId, $data['invoiceNumber'] ?? '', $data['amount'] ?? 0]);
                    
                    if ($company['current_stage'] === 'W3') {
                        $newStage = 'H1';
                    }
                    break;
                    
                case 'mark_payment_received':
                    $eventType = 'payment_received';
                    $description = 'Оплата получена: ' . ($data['amount'] ?? 'Сумма не указана');
                    
                    // Update invoice
                    $stmt = $this->pdo->prepare("UPDATE crm_invoices SET paid_at = NOW(), status = 'paid' WHERE company_id = ? AND status = 'issued'");
                    $stmt->execute([$companyId]);
                    
                    if ($company['current_stage'] === 'H1') {
                        $newStage = 'H2';
                    }
                    break;
                    
                case 'issue_certificate':
                    $eventType = 'certificate_issued';
                    $description = 'Удостоверение выдано: ' . ($data['certificateNumber'] ?? 'Без номера');
                    
                    // Insert certificate
                    $stmt = $this->pdo->prepare("INSERT INTO crm_certificates (company_id, certificate_number) VALUES (?, ?)");
                    $stmt->execute([$companyId, $data['certificateNumber'] ?? '']);
                    
                    if ($company['current_stage'] === 'H2') {
                        $newStage = 'A1';
                    }
                    break;
            }
            
            // Create event
            if ($eventType) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO crm_events (company_id, event_type, event_data, old_stage, new_stage)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, 
                    $eventType, 
                    json_encode($data),
                    $company['current_stage'],
                    $newStage
                ]);
            }
            
            // Update company stage
            if ($newStage) {
                $stmt = $this->pdo->prepare("UPDATE crm_companies SET current_stage = ? WHERE id = ?");
                $stmt->execute([$newStage, $companyId]);
                
                // Log transition
                $stmt = $this->pdo->prepare("INSERT INTO crm_stage_transitions (company_id, from_stage, to_stage, transition_reason) VALUES (?, ?, ?, ?)");
                $stmt->execute([$companyId, $company['current_stage'], $newStage, $description]);
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => $description,
                'newStage' => $newStage,
                'stageChanged' => $newStage !== null
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }
    
    // Condition checkers
    public function hasLPRConversation(array $company): bool {
        foreach ($company['events'] as $event) {
            if ($event['event_type'] === 'lpr_conversation') return true;
        }
        return false;
    }
    
    public function hasDiscoveryFilled(array $company): bool {
        return !empty($company['discovery_data']);
    }
    
    public function hasDemoPlanned(array $company): bool {
        return !empty($company['demo_planned_at']);
    }
    
    public function hasDemoConducted(array $company): bool {
        return !empty($company['demo_conducted_at']);
    }
    
    public function hasDemoConductedWithin60Days(array $company): bool {
        if (empty($company['demo_conducted_at'])) return false;
        $demoDate = strtotime($company['demo_conducted_at']);
        $daysDiff = (time() - $demoDate) / (60 * 60 * 24);
        return $daysDiff <= 60;
    }
    
    public function hasInvoiceSent(array $company): bool {
        return !empty($company['invoice_sent_at']);
    }
    
    public function hasPaymentReceived(array $company): bool {
        return !empty($company['payment_received_at']);
    }
    
    public function hasCertificateIssued(array $company): bool {
        return !empty($company['certificate_issued_at']);
    }
    
    // ============ UNIT TESTS ============
    
    public function runTests(): array {
        $results = [];
        
        // Test 1: Ice to Touched requires LPR conversation
        $company = [
            'id' => 999,
            'current_stage' => 'C0',
            'events' => [],
            'discovery_data' => null,
            'demo_planned_at' => null,
            'demo_conducted_at' => null,
            'invoice_sent_at' => null,
            'payment_received_at' => null,
            'certificate_issued_at' => null
        ];
        
        $canFillDiscovery = $this->isActionAllowed($company, 'fill_discovery');
        $results[] = [
            'name' => 'Ice: fill_discovery недоступен',
            'passed' => !$canFillDiscovery,
            'details' => 'fill_discovery должен быть недоступен на стадии Ice (ограничение + нет LPR)'
        ];
        
        // Test 2: Ice - only make_call available
        $canMakeCall = $this->isActionAllowed($company, 'make_call');
        $canPlanDemo = $this->isActionAllowed($company, 'plan_demo');
        $results[] = [
            'name' => 'Ice: доступен только звонок',
            'passed' => $canMakeCall && !$canPlanDemo,
            'details' => 'На стадии Ice доступен только make_call'
        ];
        
        // Test 3: Touched with LPR conversation can fill discovery
        $companyWithLPR = $company;
        $companyWithLPR['current_stage'] = 'C1';
        $companyWithLPR['events'] = [['event_type' => 'lpr_conversation']];
        
        $canFillDiscoveryNow = $this->isActionAllowed($companyWithLPR, 'fill_discovery');
        $results[] = [
            'name' => 'Touched: fill_discovery после LPR',
            'passed' => $canFillDiscoveryNow,
            'details' => 'fill_discovery должен быть доступен после LPR разговора'
        ];
        
        // Test 4: Restricted actions on Touched
        $canCreateApp = $this->isActionAllowed($companyWithLPR, 'create_application');
        $canSendCP = $this->isActionAllowed($companyWithLPR, 'send_cp');
        $canPlanDemoTouched = $this->isActionAllowed($companyWithLPR, 'plan_demo');
        
        $results[] = [
            'name' => 'Touched: ограничения',
            'passed' => !$canCreateApp && !$canSendCP && !$canPlanDemoTouched,
            'details' => 'Заявка, КП и планирование демо должны быть заблокированы'
        ];
        
        // Test 5: Demo 60 days expiry
        $companyOldDemo = $company;
        $companyOldDemo['current_stage'] = 'W3';
        $companyOldDemo['demo_conducted_at'] = date('Y-m-d H:i:s', strtotime('-61 days'));
        
        $canCreateAfterExpiry = $this->hasDemoConductedWithin60Days($companyOldDemo);
        $results[] = [
            'name' => 'Demo_done: истекает через 60 дней',
            'passed' => !$canCreateAfterExpiry,
            'details' => 'Действия заблокированы если демо > 60 дней назад'
        ];
        
        // Test 6: Demo within 60 days is valid
        $companyRecentDemo = $company;
        $companyRecentDemo['current_stage'] = 'W3';
        $companyRecentDemo['demo_conducted_at'] = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $canCreateWithinPeriod = $this->hasDemoConductedWithin60Days($companyRecentDemo);
        $results[] = [
            'name' => 'Demo_done: в пределах 60 дней',
            'passed' => $canCreateWithinPeriod,
            'details' => 'Действия доступны если демо < 60 дней назад'
        ];
        
        // Test 7: Cannot skip stages
        $companyIce = $company;
        $companyIce['current_stage'] = 'C0';
        
        $canConductDemo = $this->isActionAllowed($companyIce, 'conduct_demo');
        $canIssueInvoice = $this->isActionAllowed($companyIce, 'mark_invoice_sent');
        $results[] = [
            'name' => 'Ice: нельзя перепрыгнуть',
            'passed' => !$canConductDemo && !$canIssueInvoice,
            'details' => 'Нельзя провести демо или выставить счёт на стадии Ice'
        ];
        
        // Test 8: Interested can plan demo
        $companyInterested = $company;
        $companyInterested['current_stage'] = 'W1';
        $companyInterested['discovery_data'] = ['budget' => '100k'];
        
        $canPlanDemoInterested = $this->isActionAllowed($companyInterested, 'plan_demo');
        $results[] = [
            'name' => 'Interested: можно планировать демо',
            'passed' => $canPlanDemoInterested,
            'details' => 'plan_demo доступен на стадии Interested с заполненным Discovery'
        ];
        
        return $results;
    }
}

// ============ MAIN APPLICATION ============

// Check database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // Show friendly error page
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CRM - Ошибка подключения</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-lg w-full text-center">
            <div class="text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">База данных недоступна</h1>
            <p class="text-gray-600 mb-4">Подождите 30-60 секунд после запуска Docker</p>
            <div class="bg-red-50 rounded-lg p-4 text-left text-sm text-red-800 mb-4">
                <strong>Ошибка:</strong> <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <button onclick="location.reload()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                🔄 Обновить страницу
            </button>
            <div class="mt-6 text-sm text-gray-500">
                <p>Проверьте статус контейнеров:</p>
                <code class="bg-gray-100 px-2 py-1 rounded">docker-compose ps</code>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$engine = new StageEngine($pdo);

// Handle API requests
$action = $_GET['action'] ?? '';
$companyId = (int)($_GET['company_id'] ?? $_POST['company_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $actionType = $_POST['action_type'] ?? '';
    $data = $_POST['data'] ?? [];
    
    if (is_string($data)) {
        $data = json_decode($data, true) ?? [];
    }
    
    $result = $engine->executeAction($companyId, $actionType, $data);
    echo json_encode($result);
    exit;
}

if ($action === 'test') {
    header('Content-Type: application/json');
    $results = $engine->runTests();
    echo json_encode(['tests' => $results]);
    exit;
}

if ($action === 'api') {
    header('Content-Type: application/json');
    
    if ($companyId > 0) {
        $company = $engine->getCompany($companyId);
        echo json_encode([
            'company' => $company,
            'availableActions' => $engine->getAvailableActions($company),
            'blockedActions' => $engine->getBlockedActions($company),
            'stage' => STAGES[$company['current_stage']] ?? null
        ]);
    } else {
        echo json_encode([
            'companies' => $engine->getAllCompanies()
        ]);
    }
    exit;
}

// Get data for UI
$companies = $engine->getAllCompanies();
$selectedCompany = $companyId > 0 ? $engine->getCompany($companyId) : null;
$availableActions = $selectedCompany ? $engine->getAvailableActions($selectedCompany) : [];
$blockedActions = $selectedCompany ? $engine->getBlockedActions($selectedCompany) : [];

function getEventIcon($type) {
    $icons = [
        'contact_attempt' => '📞',
        'lpr_conversation' => '💬',
        'discovery_filled' => '📋',
        'demo_planned' => '📅',
        'demo_conducted' => '🎬',
        'invoice_sent' => '💳',
        'payment_received' => '💰',
        'certificate_issued' => '🎓',
        'call_answered' => '✅',
        'cp_sent' => '📨',
        'application_created' => '📝'
    ];
    return $icons[$type] ?? '📌';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Stage Manager - Joomla</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .stage-progress { display: flex; align-items: center; gap: 4px; overflow-x: auto; padding-bottom: 8px; }
        .stage-dot { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; flex-shrink: 0; }
        .stage-line { width: 24px; height: 4px; flex-shrink: 0; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 via-white to-blue-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <span class="text-2xl">📊</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">CRM Stage Manager</h1>
                        <p class="text-blue-200 text-sm">Joomla + PHP + MySQL</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="/setup-crm.php" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors text-sm">⚙️ Setup</a>
                    <button onclick="runTests()" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors text-sm">🧪 Тесты</button>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
        <!-- Test Results Panel -->
        <div id="testResults" class="hidden mb-6 bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold">🧪 Результаты тестов</h2>
                <button onclick="document.getElementById('testResults').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div id="testResultsContent"></div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Company List -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white p-4">
                        <h2 class="text-lg font-bold">🏢 Компании (<?= count($companies) ?>)</h2>
                    </div>
                    <div class="max-h-[600px] overflow-y-auto">
                        <?php
                        $groupedByStage = [];
                        foreach ($companies as $company) {
                            $stage = $company['current_stage'];
                            if (!isset($groupedByStage[$stage])) {
                                $groupedByStage[$stage] = [];
                            }
                            $groupedByStage[$stage][] = $company;
                        }
                        
                        foreach (STAGE_ORDER as $stageCode):
                            if (!isset($groupedByStage[$stageCode]) || empty($groupedByStage[$stageCode])) continue;
                            $stage = STAGES[$stageCode];
                            $stageCompanies = $groupedByStage[$stageCode];
                        ?>
                        <div class="border-b border-gray-100">
                            <div class="bg-gray-50 px-4 py-2 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">
                                    <?= count($stageCompanies) ?>
                                </span>
                                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($stage['displayName']) ?></span>
                            </div>
                            <?php foreach ($stageCompanies as $company): ?>
                            <a href="?company_id=<?= $company['id'] ?>" 
                               class="w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors flex items-center gap-3 block <?= $companyId === (int)$company['id'] ? 'bg-blue-100 border-l-4 border-blue-600' : '' ?>">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold">
                                    <?= mb_substr($company['name'], 0, 1) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate"><?= htmlspecialchars($company['name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= count($company['events']) ?> событий</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($companies)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <div class="text-4xl mb-2">📭</div>
                            <p>Нет компаний в базе</p>
                            <p class="text-sm mt-2">Проверьте подключение к БД</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Company Card -->
            <div class="lg:col-span-2">
                <?php if ($selectedCompany): 
                    $stageConfig = STAGES[$selectedCompany['current_stage']];
                    $currentIndex = array_search($selectedCompany['current_stage'], STAGE_ORDER);
                    if ($currentIndex === false) $currentIndex = 0;
                ?>
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold"><?= htmlspecialchars($selectedCompany['name']) ?></h1>
                                <p class="text-blue-100 mt-1">ID: <?= $selectedCompany['id'] ?> • <?= htmlspecialchars($selectedCompany['contact_person'] ?? '') ?></p>
                            </div>
                            <a href="?" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        </div>
                        <div class="mt-4 inline-flex items-center gap-2 bg-white/20 backdrop-blur rounded-lg px-4 py-2">
                            <span class="text-sm font-medium">Текущая стадия:</span>
                            <span class="font-bold text-lg"><?= htmlspecialchars($stageConfig['displayName']) ?></span>
                        </div>
                    </div>

                    <!-- Stage Progress -->
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <div class="stage-progress">
                            <?php foreach (STAGE_ORDER as $idx => $code): 
                                $isCompleted = $idx < $currentIndex;
                                $isCurrent = $code === $selectedCompany['current_stage'];
                            ?>
                                <div class="flex flex-col items-center">
                                    <div class="stage-dot <?= $isCompleted ? 'bg-green-500 text-white' : ($isCurrent ? 'bg-blue-600 text-white ring-4 ring-blue-200' : 'bg-gray-200 text-gray-500') ?>">
                                        <?= $isCompleted ? '✓' : $code ?>
                                    </div>
                                    <span class="text-xs mt-1 <?= $isCurrent ? 'font-bold text-blue-600' : ($isCompleted ? 'text-green-600' : 'text-gray-400') ?>">
                                        <?= STAGES[$code]['name'] ?>
                                    </span>
                                </div>
                                <?php if ($idx < count(STAGE_ORDER) - 1): ?>
                                <div class="stage-line <?= $idx < $currentIndex ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="grid lg:grid-cols-2 gap-6 p-6">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Instruction -->
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                <h3 class="font-semibold text-blue-900 mb-2">📘 Инструкция / Скрипт</h3>
                                <div class="text-blue-800 text-sm whitespace-pre-line"><?= htmlspecialchars($stageConfig['instruction']) ?></div>
                            </div>

                            <!-- Actions -->
                            <div class="bg-white border border-gray-200 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-900 mb-4">⚡ Доступные действия</h3>
                                <div class="flex flex-wrap gap-2">
                                    <?php if (empty($availableActions)): ?>
                                        <p class="text-gray-500 text-sm">Нет доступных действий</p>
                                    <?php else: ?>
                                        <?php foreach ($availableActions as $actionItem): ?>
                                        <button onclick="showActionModal('<?= $actionItem ?>')" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium text-sm hover:bg-blue-700 transition-colors">
                                            <?= ACTION_LABELS[$actionItem] ?? $actionItem ?>
                                        </button>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($blockedActions)): ?>
                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2">🚫 Заблокировано</h4>
                                    <div class="bg-red-50 rounded-lg p-3 space-y-1">
                                        <?php foreach (array_slice($blockedActions, 0, 5) as $blocked): ?>
                                        <div class="flex items-start gap-2 text-sm">
                                            <span class="text-gray-500"><?= ACTION_LABELS[$blocked['action']] ?? $blocked['action'] ?></span>
                                            <span class="text-red-600">— <?= htmlspecialchars($blocked['reason']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Stage Conditions -->
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">📋 Условия стадии</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex gap-2">
                                        <span class="text-green-600 font-medium">Вход:</span>
                                        <span class="text-gray-700"><?= htmlspecialchars($stageConfig['entryCondition']) ?></span>
                                    </div>
                                    <div class="flex gap-2">
                                        <span class="text-blue-600 font-medium">Выход:</span>
                                        <span class="text-gray-700"><?= htmlspecialchars($stageConfig['exitCondition']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($selectedCompany['discovery_data']): ?>
                            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                                <h3 class="font-semibold text-purple-900 mb-3">📊 Discovery Data</h3>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <?php $discovery = $selectedCompany['discovery_data']; ?>
                                    <div><span class="text-purple-600 font-medium">Бюджет:</span> <?= htmlspecialchars($discovery['budget'] ?? '-') ?></div>
                                    <div><span class="text-purple-600 font-medium">Сроки:</span> <?= htmlspecialchars($discovery['timeline'] ?? '-') ?></div>
                                    <div class="col-span-2"><span class="text-purple-600 font-medium">ЛПР:</span> <?= htmlspecialchars($discovery['decision_maker'] ?? '-') ?></div>
                                    <div class="col-span-2"><span class="text-purple-600 font-medium">Потребности:</span> <?= htmlspecialchars($discovery['pain_points'] ?? '-') ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right Column - Events -->
                        <div>
                            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <h3 class="font-semibold text-gray-700">📋 История событий (<?= count($selectedCompany['events']) ?>)</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <?php if (empty($selectedCompany['events'])): ?>
                                    <div class="p-6 text-center text-gray-500">
                                        <div class="text-4xl mb-2">📭</div>
                                        <p>История событий пуста</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($selectedCompany['events'] as $event): ?>
                                        <div class="px-4 py-3 flex gap-3 border-b border-gray-100">
                                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-lg">
                                                <?= getEventIcon($event['event_type']) ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-medium text-gray-900 truncate">
                                                        <?= EVENT_LABELS[$event['event_type']] ?? $event['event_type'] ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500 flex-shrink-0">
                                                        <?= date('d.m.Y H:i', strtotime($event['created_at'])) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($event['description'])): ?>
                                                <p class="text-sm text-gray-600 mt-0.5"><?= htmlspecialchars($event['description'] ?? '') ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Company Info -->
                            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">📇 Контактная информация</h3>
                                <div class="space-y-2 text-sm">
                                    <?php if ($selectedCompany['contact_person']): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Контакт:</span>
                                        <span class="text-gray-900"><?= htmlspecialchars($selectedCompany['contact_person']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($selectedCompany['phone']): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Телефон:</span>
                                        <span class="text-gray-900"><?= htmlspecialchars($selectedCompany['phone']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($selectedCompany['email']): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Email:</span>
                                        <span class="text-gray-900"><?= htmlspecialchars($selectedCompany['email']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Создана:</span>
                                        <span class="text-gray-900"><?= date('d.m.Y', strtotime($selectedCompany['created_at'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <div class="text-6xl mb-4">👈</div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Выберите компанию</h2>
                    <p class="text-gray-500">Выберите компанию из списка слева для просмотра карточки</p>
                    
                    <div class="mt-8 p-6 bg-blue-50 rounded-xl text-left">
                        <h3 class="font-semibold text-blue-900 mb-3">🎯 Ключевые особенности:</h3>
                        <ul class="space-y-2 text-blue-800 text-sm">
                            <li class="flex items-start gap-2">✅ Менеджер не может "перепрыгнуть" вперёд без обязательных действий</li>
                            <li class="flex items-start gap-2">✅ Каждая стадия имеет свои ограничения и доступные действия</li>
                            <li class="flex items-start gap-2">✅ Полная история событий для каждой компании</li>
                            <li class="flex items-start gap-2">✅ Автоматический переход на следующую стадию</li>
                        </ul>
                    </div>
                    
                    <div class="mt-6 p-4 bg-amber-50 rounded-xl text-left">
                        <h3 class="font-semibold text-amber-900 mb-2">📊 Стадии воронки:</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (STAGE_ORDER as $code): ?>
                            <span class="px-3 py-1 bg-white rounded-full text-sm font-medium text-gray-700 border border-gray-200">
                                <?= $code ?>: <?= STAGES[$code]['name'] ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Action Modal -->
    <div id="actionModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900"></h2>
            </div>
            <form id="actionForm" onsubmit="submitAction(event)">
                <div id="modalContent" class="p-6"></div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Отмена</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let currentAction = '';
    const companyId = <?= $companyId ?>;
    
    const formTemplates = {
        log_conversation: `
            <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий к разговору с ЛПР *</label>
            <textarea name="comment" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="4" required placeholder="Опишите ключевые моменты разговора..."></textarea>
        `,
        fill_discovery: `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Размер компании *</label>
                        <select name="companySize" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            <option value="">Выберите...</option>
                            <option value="1-10">1-10 сотрудников</option>
                            <option value="10-50">10-50 сотрудников</option>
                            <option value="50-100">50-100 сотрудников</option>
                            <option value="100+">100+ сотрудников</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Отрасль *</label>
                        <input type="text" name="industry" class="w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="IT, Производство...">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Болевые точки *</label>
                    <textarea name="painPoints" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="3" required placeholder="Какие проблемы хочет решить клиент..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Бюджет</label>
                        <select name="budget" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Не определён</option>
                            <option value="<100k">До 100 000 ₽</option>
                            <option value="100k-500k">100 000 - 500 000 ₽</option>
                            <option value="500k-1M">500 000 - 1 000 000 ₽</option>
                            <option value="1M+">Более 1 000 000 ₽</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Сроки</label>
                        <input type="text" name="timeline" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="1 месяц, Q2 2025...">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ЛПР *</label>
                    <input type="text" name="decisionMaker" class="w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="ФИО и должность">
                </div>
            </div>
        `,
        plan_demo: `
            <label class="block text-sm font-medium text-gray-700 mb-1">Дата и время демо *</label>
            <input type="datetime-local" name="date" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
        `,
        mark_invoice_sent: `
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Номер счёта *</label>
                    <input type="text" name="invoiceNumber" class="w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="СЧ-2024-001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Сумма *</label>
                    <input type="text" name="amount" class="w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="100 000 ₽">
                </div>
            </div>
        `,
        mark_payment_received: `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Сумма оплаты *</label>
                <input type="text" name="amount" class="w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="100 000 ₽">
            </div>
        `,
        issue_certificate: `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Номер удостоверения *</label>
                <input type="text" name="certificateNumber" class="w-full border border-gray-300 rounded-lg px-3 py-2" required placeholder="УД-2024-001">
            </div>
        `
    };
    
    const actionLabels = <?= json_encode(ACTION_LABELS) ?>;
    
    function showActionModal(action) {
        currentAction = action;
        
        if (['make_call', 'conduct_demo', 'create_application', 'send_cp'].includes(action)) {
            if (confirm('Выполнить действие: ' + actionLabels[action] + '?')) {
                executeAction(action, {});
            }
            return;
        }
        
        document.getElementById('modalTitle').textContent = actionLabels[action] || action;
        document.getElementById('modalContent').innerHTML = formTemplates[action] || '<p>Форма не найдена</p>';
        document.getElementById('actionModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('actionModal').classList.add('hidden');
    }
    
    function submitAction(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        executeAction(currentAction, data);
    }
    
    function executeAction(action, data) {
        const formData = new FormData();
        formData.append('company_id', companyId);
        formData.append('action_type', action);
        formData.append('data', JSON.stringify(data));
        
        fetch('/crm-api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('✅ ' + result.message);
                location.reload();
            } else {
                alert('❌ ' + result.message);
            }
        })
        .catch(error => {
            alert('Ошибка: ' + error.message);
        });
        
        closeModal();
    }
    
    function runTests() {
        fetch('/crm-api.php?action=test')
            .then(response => response.json())
            .then(result => {
                const panel = document.getElementById('testResults');
                const content = document.getElementById('testResultsContent');
                
                let html = '<div class="space-y-2">';
                let passed = 0, failed = 0;
                
                result.tests.forEach(test => {
                    if (test.passed) passed++; else failed++;
                    html += `
                        <div class="p-3 rounded-lg ${test.passed ? 'bg-green-50' : 'bg-red-50'} flex items-center gap-3">
                            <span class="text-2xl">${test.passed ? '✅' : '❌'}</span>
                            <div>
                                <p class="font-medium ${test.passed ? 'text-green-800' : 'text-red-800'}">${test.name}</p>
                                <p class="text-sm text-gray-600">${test.details}</p>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                html = `
                    <div class="p-4 rounded-lg mb-4 ${failed === 0 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}">
                        <span class="font-medium">${failed === 0 ? '✅ Все тесты пройдены!' : '❌ Есть неудачные тесты'}</span>
                        <span class="ml-4 text-sm"><span class="text-green-600">${passed} ✓</span> / <span class="text-red-600">${failed} ✗</span></span>
                    </div>
                ` + html;
                
                content.innerHTML = html;
                panel.classList.remove('hidden');
            });
    }
    </script>

    <footer class="bg-gray-800 text-gray-400 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            <p>CRM Stage Manager • Joomla + PHP + MySQL</p>
            <p class="mt-1">Docker Environment • localhost:8080</p>
        </div>
    </footer>
</body>
</html>
