import { StageConfig, StageCode } from '../types/crm';

export const STAGES: Record<StageCode, StageConfig> = {
  C0: {
    code: 'C0',
    name: 'Ice',
    displayName: 'Ice (Холодный)',
    restrictions: [],
    entryCondition: 'Начальная стадия',
    exitCondition: 'Есть разговор с ЛПР (лицо, принимающее решение)',
    availableActions: ['make_call'],
    instruction: `📞 Инструкция для менеджера:

1. Ваша цель — установить первый контакт с компанией
2. Найдите контакты ЛПР (лица, принимающего решение)
3. Совершите звонок и представьтесь
4. При успешном разговоре — обязательно заполните форму комментария

⚠️ На этой стадии доступна только кнопка звонка.
После отвеченного вызова появится форма для комментария.`
  },
  C1: {
    code: 'C1',
    name: 'Touched',
    displayName: 'Touched (Контакт)',
    restrictions: ['create_application', 'send_cp', 'plan_demo', 'conduct_demo'],
    entryCondition: 'Есть разговор с ЛПР',
    exitCondition: 'Заполнена форма Discovery',
    availableActions: ['make_call', 'log_conversation', 'fill_discovery'],
    instruction: `📋 Инструкция для менеджера:

1. Контакт установлен! Теперь нужно провести Discovery
2. Выясните потребности клиента
3. Заполните форму Discovery полностью

⛔ Запрещено на этой стадии:
- Заводить заявку
- Отправлять КП
- Планировать и проводить демо

✅ Доступно:
- Звонки для уточнения информации
- Заполнение формы Discovery`
  },
  C2: {
    code: 'C2',
    name: 'Aware',
    displayName: 'Aware (Осведомлён)',
    restrictions: ['plan_demo', 'conduct_demo'],
    entryCondition: 'Заполнена форма Discovery',
    exitCondition: 'Запланировано демо (дата и время)',
    availableActions: ['make_call', 'log_conversation', 'fill_discovery'],
    instruction: `🎯 Инструкция для менеджера:

1. Discovery заполнен! Клиент понимает наше предложение
2. Продолжайте общение для перехода к демонстрации
3. Дождитесь готовности клиента к демо

⛔ Запрещено: планировать и проводить демо (пока нет интереса)

✅ Для перехода на следующую стадию нужен интерес клиента`
  },
  W1: {
    code: 'W1',
    name: 'Interested',
    displayName: 'Interested (Заинтересован)',
    restrictions: ['create_application', 'send_cp'],
    entryCondition: 'Заполнена форма Discovery',
    exitCondition: 'Запланировано демо (дата и время)',
    availableActions: ['make_call', 'log_conversation', 'plan_demo'],
    instruction: `📅 Инструкция для менеджера:

1. Клиент заинтересован! Время планировать демонстрацию
2. Согласуйте удобную дату и время
3. Заполните форму планирования демо

⛔ Запрещено:
- Заводить заявку
- Отправлять КП

✅ Доступно:
- Кнопка планирования демо
- Звонки для согласования времени`
  },
  W2: {
    code: 'W2',
    name: 'demo_planned',
    displayName: 'Demo Planned (Демо запланировано)',
    restrictions: ['create_application', 'send_cp'],
    entryCondition: 'Есть запланированная дата демо',
    exitCondition: 'Проведено демо (переход по ссылке)',
    availableActions: ['make_call', 'log_conversation', 'conduct_demo'],
    instruction: `🎬 Инструкция для менеджера:

1. Демо запланировано! Подготовьтесь к презентации
2. В назначенное время нажмите "Провести демо"
3. После проведения — демо будет зарегистрировано автоматически

⛔ Запрещено:
- Заводить заявку
- Отправлять КП

✅ Доступно:
- Кнопка проведения демо (открывает ссылку)`
  },
  W3: {
    code: 'W3',
    name: 'Demo_done',
    displayName: 'Demo Done (Демо проведено)',
    restrictions: [],
    entryCondition: 'Демо проведено менее 60 дней назад',
    exitCondition: 'Есть заявка и/или счёт',
    availableActions: ['make_call', 'log_conversation', 'create_application', 'send_cp', 'mark_invoice_sent'],
    instruction: `💼 Инструкция для менеджера:

1. Отлично! Демо проведено
2. Теперь можно:
   - Завести заявку
   - Отправить коммерческое предложение
   - Выставить счёт

✅ Все основные действия доступны
📌 Для перехода дальше — выставите счёт клиенту`
  },
  H1: {
    code: 'H1',
    name: 'Committed',
    displayName: 'Committed (Обязательство)',
    restrictions: [],
    entryCondition: 'Выставлен счёт',
    exitCondition: 'Получена оплата',
    availableActions: ['make_call', 'log_conversation', 'mark_payment_received'],
    instruction: `💰 Инструкция для менеджера:

1. Счёт выставлен! Ожидаем оплату
2. Следите за статусом оплаты
3. При получении оплаты — отметьте это в системе

✅ Для перехода — зарегистрируйте получение оплаты`
  },
  H2: {
    code: 'H2',
    name: 'Customer',
    displayName: 'Customer (Клиент)',
    restrictions: [],
    entryCondition: 'Получена оплата',
    exitCondition: 'Выдано первое удостоверение',
    availableActions: ['make_call', 'log_conversation', 'issue_certificate'],
    instruction: `🎓 Инструкция для менеджера:

1. Оплата получена! Клиент активирован
2. Выдайте первое удостоверение
3. Проведите онбординг

✅ Для завершения — выдайте удостоверение`
  },
  A1: {
    code: 'A1',
    name: 'Activated',
    displayName: 'Activated (Активирован)',
    restrictions: [],
    entryCondition: 'Выдано удостоверение',
    exitCondition: 'Финальная стадия',
    availableActions: ['make_call', 'log_conversation'],
    instruction: `✅ Клиент полностью активирован!

Продолжайте поддерживать отношения:
- Регулярные check-in звонки
- Сбор обратной связи
- Upsell возможности`
  },
  N0: {
    code: 'N0',
    name: 'Null',
    displayName: 'Null (Отказ)',
    restrictions: [],
    entryCondition: 'Отказ на любой стадии',
    exitCondition: '-',
    availableActions: ['make_call', 'log_conversation'],
    instruction: `❌ Компания в статусе отказа

Возможные действия:
- Анализ причин отказа
- Повторная попытка контакта через время`
  }
};

export const STAGE_ORDER: StageCode[] = [
  'C0', 'C1', 'C2', 'W1', 'W2', 'W3', 'H1', 'H2', 'A1'
];

export const ACTION_LABELS: Record<string, string> = {
  make_call: '📞 Позвонить',
  log_conversation: '💬 Записать разговор с ЛПР',
  fill_discovery: '📋 Заполнить Discovery',
  plan_demo: '📅 Запланировать демо',
  conduct_demo: '🎬 Провести демо',
  create_application: '📝 Завести заявку',
  send_cp: '📨 Отправить КП',
  mark_invoice_sent: '💳 Выставить счёт',
  mark_payment_received: '💰 Отметить оплату',
  issue_certificate: '🎓 Выдать удостоверение'
};

export const EVENT_LABELS: Record<string, string> = {
  contact_attempt: 'Попытка контакта',
  lpr_conversation: 'Разговор с ЛПР',
  discovery_filled: 'Discovery заполнен',
  demo_planned: 'Демо запланировано',
  demo_conducted: 'Демо проведено',
  invoice_sent: 'Счёт выставлен',
  payment_received: 'Оплата получена',
  certificate_issued: 'Удостоверение выдано',
  call_answered: 'Звонок отвечен',
  cp_sent: 'КП отправлено',
  application_created: 'Заявка создана'
};
