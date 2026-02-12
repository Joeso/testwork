import { useState } from 'react';
import { useCRM } from './hooks/useCRM';
import { CompanyList } from './components/CompanyList';
import { CompanyCard } from './components/CompanyCard';
import { TestRunner } from './components/TestRunner';

export function App() {
  const {
    companies,
    selectedCompany,
    selectedCompanyId,
    setSelectedCompanyId,
    executeAction
  } = useCRM();

  const [showTests, setShowTests] = useState(false);
  const [showDocs, setShowDocs] = useState(false);

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-100 via-white to-blue-50">
      {/* Header */}
      <header className="bg-gradient-to-r from-blue-700 to-indigo-800 text-white shadow-lg">
        <div className="max-w-7xl mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                <span className="text-2xl">📊</span>
              </div>
              <div>
                <h1 className="text-2xl font-bold">CRM Stage Manager</h1>
                <p className="text-blue-200 text-sm">Прототип управления стадиями CRM</p>
              </div>
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => setShowDocs(!showDocs)}
                className={`px-4 py-2 rounded-lg transition-colors ${
                  showDocs ? 'bg-white text-blue-700' : 'bg-white/20 hover:bg-white/30'
                }`}
              >
                📖 Документация
              </button>
              <button
                onClick={() => setShowTests(!showTests)}
                className={`px-4 py-2 rounded-lg transition-colors ${
                  showTests ? 'bg-white text-blue-700' : 'bg-white/20 hover:bg-white/30'
                }`}
              >
                🧪 Тесты
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 py-6">
        {/* Documentation Panel */}
        {showDocs && (
          <div className="mb-6 bg-white rounded-xl shadow-lg p-6 prose prose-blue max-w-none">
            <h2>📘 Документация системы</h2>
            
            <h3>Архитектура</h3>
            <p>
              Система построена на базе <strong>State Machine</strong> для управления стадиями CRM.
              Каждая компания имеет текущую стадию и набор событий. Переходы между стадиями 
              контролируются через <code>StageEngine</code>.
            </p>

            <h3>Модель данных</h3>
            <div className="bg-gray-50 p-4 rounded-lg font-mono text-sm overflow-x-auto">
              <pre>{`
-- Таблица компаний
CREATE TABLE companies (
  id UUID PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  current_stage VARCHAR(10) NOT NULL DEFAULT 'C0',
  discovery_data JSONB,
  demo_planned_at TIMESTAMP,
  demo_conducted_at TIMESTAMP,
  invoice_sent_at TIMESTAMP,
  invoice_number VARCHAR(50),
  payment_received_at TIMESTAMP,
  certificate_issued_at TIMESTAMP,
  certificate_number VARCHAR(50),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Индексы для производительности
CREATE INDEX idx_companies_stage ON companies(current_stage);
CREATE INDEX idx_companies_updated ON companies(updated_at DESC);

-- Таблица событий (event sourcing)
CREATE TABLE company_events (
  id UUID PRIMARY KEY,
  company_id UUID REFERENCES companies(id),
  event_type VARCHAR(50) NOT NULL,
  event_data JSONB,
  user_id UUID NOT NULL,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Индексы для журнала событий
CREATE INDEX idx_events_company ON company_events(company_id);
CREATE INDEX idx_events_type ON company_events(event_type);
CREATE INDEX idx_events_created ON company_events(created_at DESC);
              `}</pre>
            </div>

            <h3>Стадии CRM</h3>
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-gray-100">
                  <th className="p-2 text-left">Код</th>
                  <th className="p-2 text-left">Название</th>
                  <th className="p-2 text-left">Условие входа</th>
                  <th className="p-2 text-left">Условие выхода</th>
                </tr>
              </thead>
              <tbody>
                <tr><td className="p-2 border">C0</td><td className="p-2 border">Ice</td><td className="p-2 border">-</td><td className="p-2 border">Разговор с ЛПР</td></tr>
                <tr><td className="p-2 border">C1</td><td className="p-2 border">Touched</td><td className="p-2 border">Разговор с ЛПР</td><td className="p-2 border">Discovery заполнен</td></tr>
                <tr><td className="p-2 border">C2</td><td className="p-2 border">Aware</td><td className="p-2 border">Discovery заполнен</td><td className="p-2 border">-</td></tr>
                <tr><td className="p-2 border">W1</td><td className="p-2 border">Interested</td><td className="p-2 border">Discovery заполнен</td><td className="p-2 border">Демо запланировано</td></tr>
                <tr><td className="p-2 border">W2</td><td className="p-2 border">Demo Planned</td><td className="p-2 border">Демо запланировано</td><td className="p-2 border">Демо проведено</td></tr>
                <tr><td className="p-2 border">W3</td><td className="p-2 border">Demo Done</td><td className="p-2 border">Демо проведено {'<'} 60 дней</td><td className="p-2 border">Счёт выставлен</td></tr>
                <tr><td className="p-2 border">H1</td><td className="p-2 border">Committed</td><td className="p-2 border">Счёт выставлен</td><td className="p-2 border">Оплата получена</td></tr>
                <tr><td className="p-2 border">H2</td><td className="p-2 border">Customer</td><td className="p-2 border">Оплата получена</td><td className="p-2 border">Удостоверение выдано</td></tr>
                <tr><td className="p-2 border">A1</td><td className="p-2 border">Activated</td><td className="p-2 border">Удостоверение выдано</td><td className="p-2 border">-</td></tr>
              </tbody>
            </table>

            <h3>Масштабирование до 10k компаний/день</h3>
            <ul>
              <li><strong>Индексация:</strong> B-tree индексы на stage, updated_at, company_id для быстрой выборки</li>
              <li><strong>Event Sourcing:</strong> Все изменения сохраняются как события, позволяя rebuild состояния</li>
              <li><strong>Партиционирование:</strong> Таблица events партиционируется по месяцам</li>
              <li><strong>CQRS:</strong> Разделение чтения/записи для снижения блокировок</li>
              <li><strong>Кэширование:</strong> Redis для кэша текущих состояний компаний</li>
              <li><strong>Очереди:</strong> RabbitMQ/Kafka для асинхронной обработки действий</li>
            </ul>

            <h3>AI Workflow</h3>
            <p>При разработке использовались AI-инструменты:</p>
            <ul>
              <li><strong>Claude:</strong> Генерация кода компонентов, бизнес-логики, тестов</li>
              <li><strong>Разбиение задач:</strong> Типы → Конфиги → Engine → Hooks → UI Components</li>
              <li><strong>Контроль качества:</strong> TypeScript strict mode, unit tests, code review</li>
              <li><strong>Выигрыш:</strong> Быстрая генерация boilerplate, документации, тестовых сценариев</li>
            </ul>
          </div>
        )}

        {/* Test Panel */}
        {showTests && (
          <div className="mb-6">
            <TestRunner />
          </div>
        )}

        {/* Main Grid */}
        <div className="grid lg:grid-cols-3 gap-6">
          {/* Company List */}
          <div className="lg:col-span-1">
            <CompanyList
              companies={companies}
              selectedId={selectedCompanyId}
              onSelect={setSelectedCompanyId}
            />
          </div>

          {/* Company Card */}
          <div className="lg:col-span-2">
            {selectedCompany ? (
              <CompanyCard
                company={selectedCompany}
                onExecuteAction={(action, data) => executeAction(selectedCompany.id, action, data)}
                onClose={() => setSelectedCompanyId(null)}
              />
            ) : (
              <div className="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div className="text-6xl mb-4">👈</div>
                <h2 className="text-2xl font-bold text-gray-800 mb-2">
                  Выберите компанию
                </h2>
                <p className="text-gray-500">
                  Выберите компанию из списка слева для просмотра карточки и выполнения действий
                </p>
                
                <div className="mt-8 p-6 bg-blue-50 rounded-xl text-left">
                  <h3 className="font-semibold text-blue-900 mb-3">🎯 Ключевые особенности:</h3>
                  <ul className="space-y-2 text-blue-800 text-sm">
                    <li className="flex items-start gap-2">
                      <span>✅</span>
                      <span>Менеджер не может "перепрыгнуть" вперёд без выполнения обязательных действий</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <span>✅</span>
                      <span>Каждая стадия имеет свои ограничения и доступные действия</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <span>✅</span>
                      <span>Полная история событий для каждой компании</span>
                    </li>
                    <li className="flex items-start gap-2">
                      <span>✅</span>
                      <span>Автоматический переход на следующую стадию при выполнении условий</span>
                    </li>
                  </ul>
                </div>
              </div>
            )}
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="bg-gray-800 text-gray-400 py-6 mt-12">
        <div className="max-w-7xl mx-auto px-4 text-center text-sm">
          <p>CRM Stage Manager Prototype • React + TypeScript + Tailwind CSS</p>
          <p className="mt-1">
            Демонстрация логики стадий CRM с ограничениями переходов
          </p>
        </div>
      </footer>
    </div>
  );
}
