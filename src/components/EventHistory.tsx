import { CRMEvent } from '../types/crm';
import { EVENT_LABELS } from '../config/stages';

interface EventHistoryProps {
  events: CRMEvent[];
}

export function EventHistory({ events }: EventHistoryProps) {
  const sortedEvents = [...events].sort(
    (a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  );

  if (events.length === 0) {
    return (
      <div className="bg-gray-50 rounded-lg p-6 text-center text-gray-500">
        <div className="text-4xl mb-2">📭</div>
        <p>История событий пуста</p>
        <p className="text-sm">Совершите первое действие</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div className="bg-gray-50 px-4 py-3 border-b border-gray-200">
        <h3 className="font-semibold text-gray-700 flex items-center gap-2">
          <span>📋</span> История событий ({events.length})
        </h3>
      </div>
      <div className="max-h-80 overflow-y-auto">
        {sortedEvents.map((event, index) => (
          <div
            key={event.id}
            className={`px-4 py-3 flex gap-3 ${
              index !== sortedEvents.length - 1 ? 'border-b border-gray-100' : ''
            }`}
          >
            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-lg">
              {getEventIcon(event.type)}
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between gap-2">
                <span className="font-medium text-gray-900 truncate">
                  {EVENT_LABELS[event.type] || event.type}
                </span>
                <span className="text-xs text-gray-500 flex-shrink-0">
                  {formatDate(event.timestamp)}
                </span>
              </div>
              <p className="text-sm text-gray-600 mt-0.5 line-clamp-2">
                {event.description}
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function getEventIcon(type: string): string {
  const icons: Record<string, string> = {
    contact_attempt: '📞',
    lpr_conversation: '💬',
    discovery_filled: '📋',
    demo_planned: '📅',
    demo_conducted: '🎬',
    invoice_sent: '💳',
    payment_received: '💰',
    certificate_issued: '🎓',
    call_answered: '✅',
    cp_sent: '📨',
    application_created: '📝'
  };
  return icons[type] || '📌';
}

function formatDate(date: Date): string {
  const d = new Date(date);
  return d.toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
