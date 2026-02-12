# CRM Stage Manager - Прототип управления стадиями CRM

## 📋 Описание

Прототип интерфейса и логики стадий CRM, где менеджер не может "перепрыгнуть" вперёд без выполнения обязательных действий.

Реализовано два варианта:
1. **React + TypeScript** — интерактивный прототип (этот проект)
2. **Docker + Joomla + PHP + MySQL** — полноценная реализация в папке `/docker`

---

## 🐳 Запуск через Docker (Joomla + PHP + MySQL)

### Требования
- Docker Desktop
- Docker Compose

### Быстрый старт (1-2 команды!)

**Linux/Mac:**
```bash
cd docker
chmod +x start.sh
./start.sh
```

**Windows:**
```cmd
cd docker
start.bat
```

**Или вручную:**
```bash
cd docker
docker-compose up -d
```

### Доступ после запуска

| Сервис | URL | Описание |
|--------|-----|----------|
| **CRM Interface** | http://localhost:8080/crm-api.php | Основной интерфейс CRM |
| **Setup Page** | http://localhost:8080/setup-crm.php | Проверка установки |
| **Joomla Admin** | http://localhost:8080/administrator | Админка Joomla |
| **phpMyAdmin** | http://localhost:8081 | Управление БД |

### Credentials

```
MySQL:
  Host: localhost:3306
  Database: joomla_crm
  User: joomla
  Password: joomla_password
```

### Остановка

```bash
cd docker
docker-compose down
```

---

---

## 🚀 Запуск React-прототипа

```bash
npm install
npm run dev
```

Для production сборки:
```bash
npm run build
```

---

## 🏗 Архитектура

### Выбор реализации

Для Joomla рекомендуется реализовать как **компонент** (не модуль), потому что:
- Компоненты имеют полноценный MVC-паттерн
- Поддержка собственных таблиц БД
- Возможность AJAX-контроллеров
- Интеграция с ACL Joomla

### Структура компонента Joomla

```
com_crmstages/
├── admin/
│   ├── controllers/
│   │   ├── company.php
│   │   └── stage.php
│   ├── models/
│   │   ├── company.php
│   │   ├── stage.php
│   │   └── event.php
│   ├── tables/
│   │   ├── company.php
│   │   └── event.php
│   ├── views/
│   │   ├── company/
│   │   └── dashboard/
│   └── sql/
│       ├── install.mysql.sql
│       └── uninstall.mysql.sql
├── site/
│   ├── controllers/
│   ├── models/
│   └── views/
└── crmstages.xml
```

---

## 💾 Модель данных

### Таблицы

```sql
-- Основная таблица компаний
CREATE TABLE #__crm_companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    current_stage ENUM('C0','C1','C2','W1','W2','W3','H1','H2','A1','N0') 
        NOT NULL DEFAULT 'C0',
    
    -- Discovery данные (JSON)
    discovery_data JSON,
    
    -- Ключевые даты
    demo_planned_at DATETIME NULL,
    demo_conducted_at DATETIME NULL,
    invoice_sent_at DATETIME NULL,
    invoice_number VARCHAR(50) NULL,
    payment_received_at DATETIME NULL,
    certificate_issued_at DATETIME NULL,
    certificate_number VARCHAR(50) NULL,
    
    -- Метаданные
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    
    -- Индексы
    INDEX idx_stage (current_stage),
    INDEX idx_updated (updated_at DESC),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Журнал событий (Event Sourcing)
CREATE TABLE #__crm_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    description TEXT,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Индексы для быстрой выборки
    INDEX idx_company (company_id),
    INDEX idx_type (event_type),
    INDEX idx_created (created_at DESC),
    INDEX idx_company_created (company_id, created_at DESC),
    
    FOREIGN KEY (company_id) REFERENCES #__crm_companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Конфигурация стадий (опционально, для гибкости)
CREATE TABLE #__crm_stage_config (
    code VARCHAR(10) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    restrictions JSON,
    entry_condition TEXT,
    exit_condition TEXT,
    available_actions JSON,
    instruction TEXT,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Сущности

```
Company (Компания)
├── id: UUID
├── name: string
├── currentStage: StageCode
├── events: Event[]
├── discoveryData?: DiscoveryData
├── demoPlannedAt?: DateTime
├── demoConductedAt?: DateTime
├── invoiceSentAt?: DateTime
├── paymentReceivedAt?: DateTime
└── certificateIssuedAt?: DateTime

Event (Событие)
├── id: UUID
├── companyId: UUID
├── type: EventType
├── data: JSON
├── description: string
├── userId: UUID
└── timestamp: DateTime

Stage (Стадия)
├── code: C0-A1
├── name: string
├── restrictions: ActionType[]
├── entryCondition: string
├── exitCondition: string
└── availableActions: ActionType[]
```

---

## 📊 Масштабирование до 10k компаний/день

### Принципы

1. **Индексация**
   - B-tree индексы на `current_stage`, `updated_at`, `company_id`
   - Составной индекс `(company_id, created_at)` для истории событий
   - Покрывающие индексы для частых запросов

2. **Event Sourcing / Журнал событий**
   - Все изменения сохраняются как иммутабельные события
   - Возможность восстановить состояние на любой момент
   - Аудит всех действий менеджеров

3. **Минимизация блокировок**
   - Оптимистичные блокировки через version/updated_at
   - Транзакции только для критичных операций
   - Read replicas для отчётов

4. **Партиционирование**
   ```sql
   ALTER TABLE #__crm_events 
   PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
       PARTITION p202401 VALUES LESS THAN (202402),
       PARTITION p202402 VALUES LESS THAN (202403),
       ...
   );
   ```

5. **Кэширование**
   - Redis для состояний активных компаний
   - Инвалидация при любом событии
   - TTL 5 минут для списков

6. **Асинхронная обработка**
   - Queue (RabbitMQ/Redis) для тяжёлых операций
   - Отправка уведомлений через очередь
   - Генерация отчётов в background

---

## 🧪 Тестирование

### Покрытие unit-тестами

| Тест | Что проверяет | Почему важно |
|------|---------------|--------------|
| `testIceToTouchedRequiresLPRConversation` | Нельзя перейти C0→C1 без разговора с ЛПР | Базовое ограничение воронки |
| `testIceToTouchedWithLPRConversation` | Переход C0→C1 при наличии разговора | Позитивный сценарий |
| `testCannotSkipStages` | Нельзя перепрыгнуть через стадии | Ключевое бизнес-правило |
| `testRestrictedActionsOnTouched` | Запрет заявки/КП/демо на C1 | Соблюдение ограничений |
| `testDemo60DaysExpiry` | Демо истекает через 60 дней | Временное ограничение |

### Запуск тестов

В UI приложения есть кнопка "🧪 Тесты" для интерактивного запуска.

### Пример лога

```
🧪 Running Stage Engine Tests...

✅ Ice → Touched требует разговора с ЛПР
✅ Ice → Touched с разговором ЛПР разрешён
✅ Нельзя перепрыгивать стадии
✅ Ограниченные действия на Touched
✅ Демо истекает через 60 дней

📊 Results: 5 passed, 0 failed
```

### Bug-fix cycle (пример)

1. **Обнаружен баг:** При заполнении Discovery на стадии C1 компания не переходила на W1
2. **Анализ:** В `executeAction` отсутствовала логика авто-перехода
3. **Исправление:** Добавлен переход `C1 → W1` после `fill_discovery`
4. **Тест:** Добавлен тест `testDiscoveryTriggersTransition`
5. **Результат:** ✅ Все тесты зелёные

---

## 🤖 AI Workflow

### Используемые инструменты

- **Claude (Anthropic)** - генерация кода, рефакторинг, документация

### Процесс разработки

1. **Разбиение задачи на модули:**
   - Типы и интерфейсы (`types/crm.ts`)
   - Конфигурация стадий (`config/stages.ts`)
   - Бизнес-логика (`services/StageEngine.ts`)
   - React hooks (`hooks/useCRM.ts`)
   - UI компоненты

2. **Промпты:**
   - "Создай TypeScript интерфейсы для CRM стадий с условиями переходов"
   - "Реализуй State Machine для проверки разрешённых действий"
   - "Сгенерируй unit-тесты для критичных бизнес-правил"

3. **Итеративная проверка:**
   - После каждой генерации - компиляция TypeScript
   - Проверка типов строгим режимом
   - Ручной код-ревью критичной логики

### Контроль качества и рисков

| Риск | Меры контроля |
|------|---------------|
| Галлюцинации | Проверка TypeScript компилятором, тесты |
| Некорректная логика | Unit-тесты на все бизнес-правила |
| Безопасность | Нет внешних зависимостей для бизнес-логики |
| Лицензии | Только MIT/Apache-совместимые библиотеки |

### Где AI дал выигрыш

- ⚡ **Код:** Быстрая генерация boilerplate компонентов (экономия ~2 часа)
- 📝 **Документация:** Автогенерация README, комментариев к коду
- 🧪 **Тесты:** Генерация тестовых сценариев из acceptance criteria
- 🎨 **UX-тексты:** Инструкции для менеджеров на каждой стадии

---

## 📁 Структура файлов

```
src/
├── types/
│   └── crm.ts              # TypeScript типы и интерфейсы
├── config/
│   └── stages.ts           # Конфигурация стадий
├── services/
│   └── StageEngine.ts      # Бизнес-логика переходов + тесты
├── hooks/
│   └── useCRM.ts           # React hook для состояния CRM
├── components/
│   ├── CompanyCard.tsx     # Карточка компании
│   ├── CompanyList.tsx     # Список компаний
│   ├── StageProgress.tsx   # Визуализация прогресса
│   ├── ActionPanel.tsx     # Панель действий
│   ├── EventHistory.tsx    # История событий
│   └── TestRunner.tsx      # Запуск тестов
└── App.tsx                 # Главный компонент
```

---

## 🔮 Что бы улучшил

1. **Авторизация:** Полная интеграция с Joomla ACL для разных ролей менеджеров
2. **Уведомления:** WebSocket для real-time обновлений между менеджерами
3. **Отчёты:** Дашборд с метриками воронки, конверсии по стадиям
4. **Интеграции:** API для телефонии (Asterisk), почты, 1С
5. **Mobile:** PWA для мобильных менеджеров
6. **Workflow Engine:** Настраиваемые стадии через админку

---

## 🐳 Структура Docker-окружения

```
docker/
├── docker-compose.yml      # Конфигурация контейнеров
├── init-db.sql             # SQL для создания таблиц и данных
├── crm-api.php             # Основной PHP-интерфейс CRM
├── setup-crm.php           # Страница проверки установки
├── start.sh                # Скрипт запуска (Linux/Mac)
├── start.bat               # Скрипт запуска (Windows)
├── com_crmstages/          # Joomla frontend component
└── admin_crmstages/        # Joomla admin component
```

### Контейнеры

| Контейнер | Образ | Порт | Назначение |
|-----------|-------|------|------------|
| crm_joomla | joomla:5-php8.2-apache | 8080 | Joomla + PHP + Apache |
| crm_mysql | mysql:8.0 | 3306 | База данных MySQL |
| crm_phpmyadmin | phpmyadmin:latest | 8081 | Управление БД |

