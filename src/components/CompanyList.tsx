import { Company } from '../types/crm';
import { STAGES, STAGE_ORDER } from '../config/stages';
import { cn } from '../utils/cn';

interface CompanyListProps {
  companies: Company[];
  selectedId: string | null;
  onSelect: (id: string) => void;
}

export function CompanyList({ companies, selectedId, onSelect }: CompanyListProps) {
  // Group companies by stage
  const groupedByStage = STAGE_ORDER.reduce((acc, stageCode) => {
    acc[stageCode] = companies.filter(c => c.currentStage === stageCode);
    return acc;
  }, {} as Record<string, Company[]>);

  return (
    <div className="bg-white rounded-xl shadow-lg overflow-hidden">
      <div className="bg-gradient-to-r from-gray-800 to-gray-900 text-white p-4">
        <h2 className="text-lg font-bold flex items-center gap-2">
          <span>🏢</span> Компании ({companies.length})
        </h2>
      </div>
      
      <div className="max-h-[600px] overflow-y-auto">
        {STAGE_ORDER.map(stageCode => {
          const stageCompanies = groupedByStage[stageCode];
          if (stageCompanies.length === 0) return null;
          
          const stage = STAGES[stageCode];
          
          return (
            <div key={stageCode} className="border-b border-gray-100 last:border-b-0">
              <div className="bg-gray-50 px-4 py-2 flex items-center gap-2">
                <span className={cn(
                  "w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center",
                  getStageColor(stageCode)
                )}>
                  {stageCompanies.length}
                </span>
                <span className="text-sm font-medium text-gray-700">
                  {stage.displayName}
                </span>
              </div>
              
              {stageCompanies.map(company => (
                <button
                  key={company.id}
                  onClick={() => onSelect(company.id)}
                  className={cn(
                    "w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors flex items-center gap-3",
                    selectedId === company.id && "bg-blue-100 border-l-4 border-blue-600"
                  )}
                >
                  <div className="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold">
                    {company.name.charAt(0)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 truncate">{company.name}</p>
                    <p className="text-xs text-gray-500">
                      {company.events.length} событий • Обновлено {formatDate(company.updatedAt)}
                    </p>
                  </div>
                  <svg className="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              ))}
            </div>
          );
        })}
      </div>
    </div>
  );
}

function getStageColor(stageCode: string): string {
  const colors: Record<string, string> = {
    C0: 'bg-gray-400 text-white',
    C1: 'bg-blue-400 text-white',
    C2: 'bg-cyan-400 text-white',
    W1: 'bg-yellow-400 text-gray-900',
    W2: 'bg-orange-400 text-white',
    W3: 'bg-pink-400 text-white',
    H1: 'bg-purple-400 text-white',
    H2: 'bg-green-400 text-white',
    A1: 'bg-emerald-500 text-white',
  };
  return colors[stageCode] || 'bg-gray-400 text-white';
}

function formatDate(date: Date): string {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit'
  });
}
