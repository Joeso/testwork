import { useState } from 'react';
import { Company, ActionType } from '../types/crm';
import { ACTION_LABELS, STAGES } from '../config/stages';
import { StageEngine } from '../services/StageEngine';
import { cn } from '../utils/cn';

interface ActionPanelProps {
  company: Company;
  onExecuteAction: (action: ActionType, data?: Record<string, unknown>) => void;
}

export function ActionPanel({ company, onExecuteAction }: ActionPanelProps) {
  const [showModal, setShowModal] = useState<ActionType | null>(null);
  
  const availableActions = StageEngine.getAvailableActions(company);
  const blockedActions = StageEngine.getBlockedActions(company);
  const stageConfig = STAGES[company.currentStage];

  const handleAction = (action: ActionType) => {
    // Actions that need modal/form
    const actionsNeedingModal: ActionType[] = [
      'log_conversation',
      'fill_discovery',
      'plan_demo',
      'mark_invoice_sent',
      'mark_payment_received',
      'issue_certificate'
    ];

    if (actionsNeedingModal.includes(action)) {
      setShowModal(action);
    } else {
      // Direct actions
      onExecuteAction(action);
    }
  };

  return (
    <div className="space-y-4">
      {/* Available Actions */}
      <div>
        <h3 className="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
          <span className="text-green-500">✓</span> Доступные действия
        </h3>
        <div className="flex flex-wrap gap-2">
          {availableActions.length === 0 ? (
            <p className="text-gray-500 text-sm">Нет доступных действий</p>
          ) : (
            availableActions.map(action => (
              <button
                key={action}
                onClick={() => handleAction(action)}
                className={cn(
                  "px-4 py-2 rounded-lg font-medium text-sm transition-all",
                  "bg-blue-600 text-white hover:bg-blue-700 active:scale-95",
                  "shadow-sm hover:shadow-md"
                )}
              >
                {ACTION_LABELS[action]}
              </button>
            ))
          )}
        </div>
      </div>

      {/* Blocked Actions */}
      {blockedActions.length > 0 && (
        <div>
          <h3 className="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <span className="text-red-500">✗</span> Заблокированные действия
          </h3>
          <div className="bg-red-50 rounded-lg p-3 space-y-2">
            {blockedActions.slice(0, 4).map((blocked, idx) => (
              <div key={idx} className="flex items-center gap-2 text-sm">
                <span className="text-gray-400">{ACTION_LABELS[blocked.action]}</span>
                <span className="text-red-600">— {blocked.reason}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Restrictions Info */}
      {stageConfig.restrictions.length > 0 && (
        <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <p className="text-amber-800 text-sm font-medium mb-1">
            ⚠️ Ограничения на стадии "{stageConfig.displayName}":
          </p>
          <ul className="text-amber-700 text-sm list-disc list-inside">
            {stageConfig.restrictions.map(r => (
              <li key={r}>{ACTION_LABELS[r]}</li>
            ))}
          </ul>
        </div>
      )}

      {/* Modals */}
      {showModal && (
        <ActionModal
          action={showModal}
          company={company}
          onClose={() => setShowModal(null)}
          onSubmit={(data) => {
            onExecuteAction(showModal, data);
            setShowModal(null);
          }}
        />
      )}
    </div>
  );
}

interface ActionModalProps {
  action: ActionType;
  company: Company;
  onClose: () => void;
  onSubmit: (data: Record<string, unknown>) => void;
}

function ActionModal({ action, onClose, onSubmit }: ActionModalProps) {
  const [formData, setFormData] = useState<Record<string, string>>({});

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };

  const renderForm = () => {
    switch (action) {
      case 'log_conversation':
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Комментарий к разговору с ЛПР *
              </label>
              <textarea
                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                rows={4}
                required
                placeholder="Опишите ключевые моменты разговора..."
                value={formData.comment || ''}
                onChange={(e) => setFormData({ ...formData, comment: e.target.value })}
              />
            </div>
          </div>
        );

      case 'fill_discovery':
        return (
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Размер компании *
                </label>
                <select
                  className="w-full border border-gray-300 rounded-lg px-3 py-2"
                  required
                  value={formData.companySize || ''}
                  onChange={(e) => setFormData({ ...formData, companySize: e.target.value })}
                >
                  <option value="">Выберите...</option>
                  <option value="1-10">1-10 сотрудников</option>
                  <option value="10-50">10-50 сотрудников</option>
                  <option value="50-100">50-100 сотрудников</option>
                  <option value="100+">100+ сотрудников</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Отрасль *
                </label>
                <input
                  type="text"
                  className="w-full border border-gray-300 rounded-lg px-3 py-2"
                  required
                  placeholder="IT, Производство..."
                  value={formData.industry || ''}
                  onChange={(e) => setFormData({ ...formData, industry: e.target.value })}
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Болевые точки / Потребности *
              </label>
              <textarea
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                rows={3}
                required
                placeholder="Какие проблемы хочет решить клиент..."
                value={formData.painPoints || ''}
                onChange={(e) => setFormData({ ...formData, painPoints: e.target.value })}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Бюджет
                </label>
                <select
                  className="w-full border border-gray-300 rounded-lg px-3 py-2"
                  value={formData.budget || ''}
                  onChange={(e) => setFormData({ ...formData, budget: e.target.value })}
                >
                  <option value="">Не определён</option>
                  <option value="<100k">До 100 000 ₽</option>
                  <option value="100k-500k">100 000 - 500 000 ₽</option>
                  <option value="500k-1M">500 000 - 1 000 000 ₽</option>
                  <option value="1M+">Более 1 000 000 ₽</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Сроки принятия решения
                </label>
                <input
                  type="text"
                  className="w-full border border-gray-300 rounded-lg px-3 py-2"
                  placeholder="1 месяц, квартал..."
                  value={formData.timeline || ''}
                  onChange={(e) => setFormData({ ...formData, timeline: e.target.value })}
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                ЛПР (лицо, принимающее решение) *
              </label>
              <input
                type="text"
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                required
                placeholder="ФИО и должность"
                value={formData.decisionMaker || ''}
                onChange={(e) => setFormData({ ...formData, decisionMaker: e.target.value })}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Дополнительные заметки
              </label>
              <textarea
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                rows={2}
                placeholder="Любая дополнительная информация..."
                value={formData.notes || ''}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              />
            </div>
          </div>
        );

      case 'plan_demo':
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Дата и время демонстрации *
              </label>
              <input
                type="datetime-local"
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                required
                value={formData.date || ''}
                onChange={(e) => setFormData({ ...formData, date: e.target.value })}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Примечания
              </label>
              <textarea
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                rows={2}
                placeholder="Особые пожелания, участники..."
                value={formData.notes || ''}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              />
            </div>
          </div>
        );

      case 'mark_invoice_sent':
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Номер счёта *
              </label>
              <input
                type="text"
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                required
                placeholder="СЧ-2024-001"
                value={formData.invoiceNumber || ''}
                onChange={(e) => setFormData({ ...formData, invoiceNumber: e.target.value })}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Сумма *
              </label>
              <input
                type="text"
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                required
                placeholder="100 000 ₽"
                value={formData.amount || ''}
                onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
              />
            </div>
          </div>
        );

      case 'mark_payment_received':
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Сумма оплаты *
              </label>
              <input
                type="text"
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                required
                placeholder="100 000 ₽"
                value={formData.amount || ''}
                onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Дата оплаты
              </label>
              <input
                type="date"
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                value={formData.paymentDate || ''}
                onChange={(e) => setFormData({ ...formData, paymentDate: e.target.value })}
              />
            </div>
          </div>
        );

      case 'issue_certificate':
        return (
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Номер удостоверения *
              </label>
              <input
                type="text"
                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                required
                placeholder="УД-2024-001"
                value={formData.certificateNumber || ''}
                onChange={(e) => setFormData({ ...formData, certificateNumber: e.target.value })}
              />
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6 border-b border-gray-200">
          <h2 className="text-xl font-bold text-gray-900">
            {ACTION_LABELS[action]}
          </h2>
        </div>
        <form onSubmit={handleSubmit}>
          <div className="p-6">
            {renderForm()}
          </div>
          <div className="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              Отмена
            </button>
            <button
              type="submit"
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Сохранить
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
