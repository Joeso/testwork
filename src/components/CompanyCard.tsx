import { Company, ActionType } from '../types/crm';
import { STAGES } from '../config/stages';
import { StageProgress } from './StageProgress';
import { ActionPanel } from './ActionPanel';
import { EventHistory } from './EventHistory';

interface CompanyCardProps {
  company: Company;
  onExecuteAction: (action: ActionType, data?: Record<string, unknown>) => void;
  onClose: () => void;
}

export function CompanyCard({ company, onExecuteAction, onClose }: CompanyCardProps) {
  const stageConfig = STAGES[company.currentStage];

  return (
    <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-6">
        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-2xl font-bold">{company.name}</h1>
            <p className="text-blue-100 mt-1">ID: {company.id}</p>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-white/20 rounded-lg transition-colors"
          >
            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        {/* Current Stage Badge */}
        <div className="mt-4 inline-flex items-center gap-2 bg-white/20 backdrop-blur rounded-lg px-4 py-2">
          <span className="text-sm font-medium">Текущая стадия:</span>
          <span className="font-bold text-lg">{stageConfig.displayName}</span>
        </div>
      </div>

      {/* Stage Progress */}
      <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <StageProgress currentStage={company.currentStage} />
      </div>

      {/* Content Grid */}
      <div className="grid lg:grid-cols-2 gap-6 p-6">
        {/* Left Column */}
        <div className="space-y-6">
          {/* Instruction Block */}
          <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <h3 className="font-semibold text-blue-900 mb-2 flex items-center gap-2">
              <span>📘</span> Инструкция / Скрипт
            </h3>
            <div className="text-blue-800 text-sm whitespace-pre-line">
              {stageConfig.instruction}
            </div>
          </div>

          {/* Actions */}
          <div className="bg-white border border-gray-200 rounded-xl p-4">
            <h3 className="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <span>⚡</span> Действия
            </h3>
            <ActionPanel 
              company={company} 
              onExecuteAction={onExecuteAction}
            />
          </div>

          {/* Stage Conditions */}
          <div className="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <h3 className="font-semibold text-gray-900 mb-3">📋 Условия стадии</h3>
            <div className="space-y-2 text-sm">
              <div className="flex gap-2">
                <span className="text-green-600 font-medium">Вход:</span>
                <span className="text-gray-700">{stageConfig.entryCondition}</span>
              </div>
              <div className="flex gap-2">
                <span className="text-blue-600 font-medium">Выход:</span>
                <span className="text-gray-700">{stageConfig.exitCondition}</span>
              </div>
            </div>
          </div>

          {/* Discovery Data (if filled) */}
          {company.discoveryData && (
            <div className="bg-purple-50 border border-purple-200 rounded-xl p-4">
              <h3 className="font-semibold text-purple-900 mb-3 flex items-center gap-2">
                <span>📊</span> Discovery Data
              </h3>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span className="text-purple-600 font-medium">Размер:</span>
                  <span className="ml-2 text-gray-700">{company.discoveryData.companySize}</span>
                </div>
                <div>
                  <span className="text-purple-600 font-medium">Отрасль:</span>
                  <span className="ml-2 text-gray-700">{company.discoveryData.industry}</span>
                </div>
                <div className="col-span-2">
                  <span className="text-purple-600 font-medium">Потребности:</span>
                  <span className="ml-2 text-gray-700">{company.discoveryData.painPoints}</span>
                </div>
                <div>
                  <span className="text-purple-600 font-medium">Бюджет:</span>
                  <span className="ml-2 text-gray-700">{company.discoveryData.budget || 'Не указан'}</span>
                </div>
                <div>
                  <span className="text-purple-600 font-medium">ЛПР:</span>
                  <span className="ml-2 text-gray-700">{company.discoveryData.decisionMaker}</span>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Right Column - Event History */}
        <div>
          <EventHistory events={company.events} />
          
          {/* Key Dates */}
          <div className="mt-6 bg-gray-50 border border-gray-200 rounded-xl p-4">
            <h3 className="font-semibold text-gray-900 mb-3">📅 Ключевые даты</h3>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-600">Создана:</span>
                <span className="text-gray-900">{formatDate(company.createdAt)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Обновлена:</span>
                <span className="text-gray-900">{formatDate(company.updatedAt)}</span>
              </div>
              {company.demoPlannedAt && (
                <div className="flex justify-between">
                  <span className="text-gray-600">Демо запланировано:</span>
                  <span className="text-gray-900">{formatDate(company.demoPlannedAt)}</span>
                </div>
              )}
              {company.demoConductedAt && (
                <div className="flex justify-between">
                  <span className="text-gray-600">Демо проведено:</span>
                  <span className="text-gray-900">{formatDate(company.demoConductedAt)}</span>
                </div>
              )}
              {company.invoiceSentAt && (
                <div className="flex justify-between">
                  <span className="text-gray-600">Счёт выставлен:</span>
                  <span className="text-gray-900">{formatDate(company.invoiceSentAt)}</span>
                </div>
              )}
              {company.paymentReceivedAt && (
                <div className="flex justify-between">
                  <span className="text-gray-600">Оплата получена:</span>
                  <span className="text-gray-900">{formatDate(company.paymentReceivedAt)}</span>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function formatDate(date: Date): string {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}
