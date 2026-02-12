import { useState, useCallback } from 'react';
import { Company, ActionType, CRMEvent, StageCode } from '../types/crm';
import { StageEngine } from '../services/StageEngine';

// Mock initial companies
const createMockCompanies = (): Company[] => [
  {
    id: '1',
    name: 'ООО "Технологии Будущего"',
    currentStage: 'C0',
    events: [],
    createdAt: new Date('2024-01-15'),
    updatedAt: new Date()
  },
  {
    id: '2',
    name: 'АО "Инновационные Решения"',
    currentStage: 'C1',
    events: [
      {
        id: 'e1',
        type: 'contact_attempt',
        timestamp: new Date('2024-01-10'),
        data: { answered: true },
        userId: 'manager1',
        companyId: '2',
        description: 'Первый звонок - отвечен'
      },
      {
        id: 'e2',
        type: 'lpr_conversation',
        timestamp: new Date('2024-01-10'),
        data: { comment: 'Директор заинтересован, просит подробности' },
        userId: 'manager1',
        companyId: '2',
        description: 'Разговор с ЛПР: Директор заинтересован'
      }
    ],
    createdAt: new Date('2024-01-08'),
    updatedAt: new Date()
  },
  {
    id: '3',
    name: 'ИП Смирнов А.В.',
    currentStage: 'W1',
    events: [
      {
        id: 'e3',
        type: 'lpr_conversation',
        timestamp: new Date('2024-01-05'),
        data: {},
        userId: 'manager1',
        companyId: '3',
        description: 'Разговор с ЛПР'
      },
      {
        id: 'e4',
        type: 'discovery_filled',
        timestamp: new Date('2024-01-06'),
        data: {},
        userId: 'manager1',
        companyId: '3',
        description: 'Discovery заполнен'
      }
    ],
    discoveryData: {
      companySize: '10-50',
      industry: 'IT',
      painPoints: 'Нужна автоматизация',
      budget: '500k-1M',
      decisionMaker: 'Смирнов А.В.',
      timeline: '1 месяц',
      notes: 'Готов к демо',
      filledAt: new Date('2024-01-06')
    },
    createdAt: new Date('2024-01-01'),
    updatedAt: new Date()
  },
  {
    id: '4',
    name: 'ЗАО "Прогресс"',
    currentStage: 'W3',
    events: [
      {
        id: 'e5',
        type: 'lpr_conversation',
        timestamp: new Date('2024-01-01'),
        data: {},
        userId: 'manager1',
        companyId: '4',
        description: 'Разговор с ЛПР'
      },
      {
        id: 'e6',
        type: 'discovery_filled',
        timestamp: new Date('2024-01-02'),
        data: {},
        userId: 'manager1',
        companyId: '4',
        description: 'Discovery заполнен'
      },
      {
        id: 'e7',
        type: 'demo_planned',
        timestamp: new Date('2024-01-03'),
        data: { date: '2024-01-10 14:00' },
        userId: 'manager1',
        companyId: '4',
        description: 'Демо запланировано'
      },
      {
        id: 'e8',
        type: 'demo_conducted',
        timestamp: new Date('2024-01-10'),
        data: {},
        userId: 'manager1',
        companyId: '4',
        description: 'Демо проведено'
      }
    ],
    discoveryData: {
      companySize: '100+',
      industry: 'Производство',
      painPoints: 'Оптимизация процессов',
      budget: '1M+',
      decisionMaker: 'Генеральный директор',
      timeline: '3 месяца',
      notes: '',
      filledAt: new Date('2024-01-02')
    },
    demoPlannedAt: new Date('2024-01-10'),
    demoConductedAt: new Date('2024-01-10'),
    createdAt: new Date('2023-12-20'),
    updatedAt: new Date()
  }
];

export function useCRM() {
  const [companies, setCompanies] = useState<Company[]>(createMockCompanies);
  const [selectedCompanyId, setSelectedCompanyId] = useState<string | null>(null);

  const selectedCompany = companies.find(c => c.id === selectedCompanyId) || null;

  const executeAction = useCallback((
    companyId: string, 
    action: ActionType, 
    data?: Record<string, unknown>
  ) => {
    setCompanies(prev => {
      return prev.map(company => {
        if (company.id !== companyId) return company;

        const result = StageEngine.executeAction(company, action, data);
        
        if (!result.success) {
          alert(result.message);
          return company;
        }

        const updatedCompany = { ...company, updatedAt: new Date() };

        // Add event to history
        if (result.event) {
          updatedCompany.events = [...company.events, result.event];
        }

        // Update specific fields based on action
        if (action === 'fill_discovery' && data) {
          updatedCompany.discoveryData = {
            companySize: (data.companySize as string) || '',
            industry: (data.industry as string) || '',
            painPoints: (data.painPoints as string) || '',
            budget: (data.budget as string) || '',
            decisionMaker: (data.decisionMaker as string) || '',
            timeline: (data.timeline as string) || '',
            notes: (data.notes as string) || '',
            filledAt: new Date()
          };
        }

        if (action === 'plan_demo' && data?.date) {
          updatedCompany.demoPlannedAt = new Date(data.date as string);
        }

        if (action === 'conduct_demo') {
          updatedCompany.demoConductedAt = new Date();
        }

        if (action === 'mark_invoice_sent' && data) {
          updatedCompany.invoiceSentAt = new Date();
          updatedCompany.invoiceNumber = data.invoiceNumber as string;
        }

        if (action === 'mark_payment_received') {
          updatedCompany.paymentReceivedAt = new Date();
        }

        if (action === 'issue_certificate' && data) {
          updatedCompany.certificateIssuedAt = new Date();
          updatedCompany.certificateNumber = data.certificateNumber as string;
        }

        // Handle stage transition
        if (result.stageChanged && result.newStage) {
          updatedCompany.currentStage = result.newStage;
        }

        return updatedCompany;
      });
    });
  }, []);

  const addEvent = useCallback((companyId: string, event: CRMEvent) => {
    setCompanies(prev => {
      return prev.map(company => {
        if (company.id !== companyId) return company;
        return {
          ...company,
          events: [...company.events, event],
          updatedAt: new Date()
        };
      });
    });
  }, []);

  const transitionStage = useCallback((companyId: string, targetStage: StageCode) => {
    setCompanies(prev => {
      return prev.map(company => {
        if (company.id !== companyId) return company;

        const result = StageEngine.canTransitionTo(company, targetStage);
        
        if (!result.success) {
          alert(`Невозможно перейти: ${result.errors?.join(', ')}`);
          return company;
        }

        return {
          ...company,
          currentStage: targetStage,
          updatedAt: new Date()
        };
      });
    });
  }, []);

  const getAvailableActions = useCallback((company: Company) => {
    return StageEngine.getAvailableActions(company);
  }, []);

  const getBlockedActions = useCallback((company: Company) => {
    return StageEngine.getBlockedActions(company);
  }, []);

  return {
    companies,
    selectedCompany,
    selectedCompanyId,
    setSelectedCompanyId,
    executeAction,
    addEvent,
    transitionStage,
    getAvailableActions,
    getBlockedActions
  };
}
