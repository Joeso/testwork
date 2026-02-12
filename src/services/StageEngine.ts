import { 
  Company, 
  StageCode, 
  ActionType, 
  TransitionResult, 
  ActionResult,
  CRMEvent,
  EventType
} from '../types/crm';
import { STAGES, STAGE_ORDER } from '../config/stages';

/**
 * CRM Stage Engine - Core business logic for stage transitions
 * 
 * This class encapsulates all validation rules and transition logic
 * ensuring managers cannot skip mandatory actions.
 */
export class StageEngine {
  
  /**
   * Check if a specific action is allowed at current stage
   */
  static isActionAllowed(company: Company, action: ActionType): boolean {
    const stageConfig = STAGES[company.currentStage];
    
    // Check if action is in available actions
    if (!stageConfig.availableActions.includes(action)) {
      return false;
    }
    
    // Check if action is restricted
    if (stageConfig.restrictions.includes(action)) {
      return false;
    }
    
    // Additional validation based on action type
    switch (action) {
      case 'fill_discovery':
        return this.hasLPRConversation(company);
      
      case 'plan_demo':
        return this.hasDiscoveryFilled(company);
      
      case 'conduct_demo':
        return this.hasDemoPlanned(company);
      
      case 'create_application':
      case 'send_cp':
        return this.hasDemoConductedWithin60Days(company);
      
      case 'mark_invoice_sent':
        return this.hasDemoConductedWithin60Days(company);
      
      case 'mark_payment_received':
        return this.hasInvoiceSent(company);
      
      case 'issue_certificate':
        return this.hasPaymentReceived(company);
      
      default:
        return true;
    }
  }

  /**
   * Get all currently available actions for a company
   */
  static getAvailableActions(company: Company): ActionType[] {
    const stageConfig = STAGES[company.currentStage];
    return stageConfig.availableActions.filter(action => 
      this.isActionAllowed(company, action)
    );
  }

  /**
   * Get blocked actions with reasons
   */
  static getBlockedActions(company: Company): Array<{action: ActionType, reason: string}> {
    const stageConfig = STAGES[company.currentStage];
    const blocked: Array<{action: ActionType, reason: string}> = [];
    
    // Check restrictions
    stageConfig.restrictions.forEach(action => {
      blocked.push({
        action: action as ActionType,
        reason: `Действие запрещено на стадии "${stageConfig.displayName}"`
      });
    });
    
    // Check conditional blocks
    if (!this.hasLPRConversation(company) && stageConfig.availableActions.includes('fill_discovery')) {
      if (!this.isActionAllowed(company, 'fill_discovery')) {
        blocked.push({
          action: 'fill_discovery',
          reason: 'Сначала проведите разговор с ЛПР'
        });
      }
    }
    
    if (!this.hasDiscoveryFilled(company)) {
      blocked.push({
        action: 'plan_demo',
        reason: 'Сначала заполните форму Discovery'
      });
    }
    
    if (!this.hasDemoPlanned(company)) {
      blocked.push({
        action: 'conduct_demo',
        reason: 'Сначала запланируйте демо'
      });
    }
    
    return blocked;
  }

  /**
   * Validate if transition to next stage is possible
   */
  static canTransitionToNext(company: Company): TransitionResult {
    const currentIndex = STAGE_ORDER.indexOf(company.currentStage);
    
    if (currentIndex === -1 || currentIndex >= STAGE_ORDER.length - 1) {
      return {
        success: false,
        message: 'Невозможно перейти на следующую стадию',
        errors: ['Текущая стадия не поддерживает переход']
      };
    }
    
    const nextStage = STAGE_ORDER[currentIndex + 1];
    return this.canTransitionTo(company, nextStage);
  }

  /**
   * Validate transition to specific stage
   */
  static canTransitionTo(company: Company, targetStage: StageCode): TransitionResult {
    const errors: string[] = [];
    
    // Prevent skipping stages
    const currentIndex = STAGE_ORDER.indexOf(company.currentStage);
    const targetIndex = STAGE_ORDER.indexOf(targetStage);
    
    if (targetIndex > currentIndex + 1) {
      errors.push('Нельзя перепрыгивать через стадии');
    }
    
    // Check exit conditions for current stage
    switch (company.currentStage) {
      case 'C0': // Ice -> Touched
        if (!this.hasLPRConversation(company)) {
          errors.push('Необходим разговор с ЛПР для перехода');
        }
        break;
        
      case 'C1': // Touched -> Aware
        if (!this.hasDiscoveryFilled(company)) {
          errors.push('Необходимо заполнить форму Discovery');
        }
        break;
        
      case 'C2': // Aware -> Interested
        if (!this.hasDiscoveryFilled(company)) {
          errors.push('Необходимо заполнить форму Discovery');
        }
        break;
        
      case 'W1': // Interested -> Demo Planned
        if (!this.hasDemoPlanned(company)) {
          errors.push('Необходимо запланировать демо');
        }
        break;
        
      case 'W2': // Demo Planned -> Demo Done
        if (!this.hasDemoConducted(company)) {
          errors.push('Необходимо провести демо');
        }
        break;
        
      case 'W3': // Demo Done -> Committed
        if (!this.hasInvoiceSent(company)) {
          errors.push('Необходимо выставить счёт');
        }
        break;
        
      case 'H1': // Committed -> Customer
        if (!this.hasPaymentReceived(company)) {
          errors.push('Необходимо получить оплату');
        }
        break;
        
      case 'H2': // Customer -> Activated
        if (!this.hasCertificateIssued(company)) {
          errors.push('Необходимо выдать удостоверение');
        }
        break;
    }
    
    // Check entry conditions for target stage
    const targetConfig = STAGES[targetStage];
    
    return {
      success: errors.length === 0,
      message: errors.length === 0 
        ? `Можно перейти на стадию "${targetConfig.displayName}"`
        : `Невозможно перейти на стадию "${targetConfig.displayName}"`,
      newStage: errors.length === 0 ? targetStage : undefined,
      errors
    };
  }

  /**
   * Execute stage transition
   */
  static transition(company: Company, targetStage: StageCode): TransitionResult {
    const validation = this.canTransitionTo(company, targetStage);
    
    if (!validation.success) {
      return validation;
    }
    
    return {
      success: true,
      message: `Переход на стадию "${STAGES[targetStage].displayName}" выполнен`,
      newStage: targetStage
    };
  }

  /**
   * Auto-transition: check if company should move to next stage
   */
  static checkAutoTransition(company: Company): StageCode | null {
    const result = this.canTransitionToNext(company);
    return result.success && result.newStage ? result.newStage : null;
  }

  // ============ Condition Checkers ============

  static hasLPRConversation(company: Company): boolean {
    return company.events.some(e => e.type === 'lpr_conversation');
  }

  static hasDiscoveryFilled(company: Company): boolean {
    return company.discoveryData !== undefined && company.discoveryData.filledAt !== undefined;
  }

  static hasDemoPlanned(company: Company): boolean {
    return company.demoPlannedAt !== undefined;
  }

  static hasDemoConducted(company: Company): boolean {
    return company.demoConductedAt !== undefined;
  }

  static hasDemoConductedWithin60Days(company: Company): boolean {
    if (!company.demoConductedAt) return false;
    const daysDiff = (Date.now() - new Date(company.demoConductedAt).getTime()) / (1000 * 60 * 60 * 24);
    return daysDiff <= 60;
  }

  static hasInvoiceSent(company: Company): boolean {
    return company.invoiceSentAt !== undefined;
  }

  static hasPaymentReceived(company: Company): boolean {
    return company.paymentReceivedAt !== undefined;
  }

  static hasCertificateIssued(company: Company): boolean {
    return company.certificateIssuedAt !== undefined;
  }

  // ============ Event Creators ============

  static createEvent(
    companyId: string,
    type: EventType,
    description: string,
    data: Record<string, unknown> = {}
  ): CRMEvent {
    return {
      id: crypto.randomUUID(),
      type,
      timestamp: new Date(),
      data,
      userId: 'current-user', // In real app, get from auth context
      companyId,
      description
    };
  }

  // ============ Action Executors ============

  static executeAction(
    company: Company, 
    action: ActionType, 
    data?: Record<string, unknown>
  ): ActionResult {
    if (!this.isActionAllowed(company, action)) {
      const blocked = this.getBlockedActions(company).find(b => b.action === action);
      return {
        success: false,
        message: blocked?.reason || 'Действие недоступно на текущей стадии'
      };
    }

    let event: CRMEvent | undefined;
    let newStage: StageCode | undefined;

    switch (action) {
      case 'make_call':
        event = this.createEvent(
          company.id,
          'contact_attempt',
          `Совершён звонок${data?.answered ? ' (отвечен)' : ' (не отвечен)'}`,
          data
        );
        break;

      case 'log_conversation':
        event = this.createEvent(
          company.id,
          'lpr_conversation',
          `Разговор с ЛПР: ${data?.comment || 'Без комментария'}`,
          data
        );
        // Auto-transition from Ice to Touched
        if (company.currentStage === 'C0') {
          newStage = 'C1';
        }
        break;

      case 'fill_discovery':
        event = this.createEvent(
          company.id,
          'discovery_filled',
          'Форма Discovery заполнена',
          data
        );
        // Auto-transition from Touched to Aware, then to Interested
        if (company.currentStage === 'C1') {
          newStage = 'W1'; // Skip C2 as discovery implies awareness
        } else if (company.currentStage === 'C2') {
          newStage = 'W1';
        }
        break;

      case 'plan_demo':
        event = this.createEvent(
          company.id,
          'demo_planned',
          `Демо запланировано на ${data?.date}`,
          data
        );
        if (company.currentStage === 'W1') {
          newStage = 'W2';
        }
        break;

      case 'conduct_demo':
        event = this.createEvent(
          company.id,
          'demo_conducted',
          'Демо проведено',
          data
        );
        if (company.currentStage === 'W2') {
          newStage = 'W3';
        }
        break;

      case 'create_application':
        event = this.createEvent(
          company.id,
          'application_created',
          `Заявка создана: ${data?.applicationNumber || 'Без номера'}`,
          data
        );
        break;

      case 'send_cp':
        event = this.createEvent(
          company.id,
          'cp_sent',
          'Коммерческое предложение отправлено',
          data
        );
        break;

      case 'mark_invoice_sent':
        event = this.createEvent(
          company.id,
          'invoice_sent',
          `Счёт выставлен: ${data?.invoiceNumber || 'Без номера'}`,
          data
        );
        if (company.currentStage === 'W3') {
          newStage = 'H1';
        }
        break;

      case 'mark_payment_received':
        event = this.createEvent(
          company.id,
          'payment_received',
          `Оплата получена: ${data?.amount || 'Сумма не указана'}`,
          data
        );
        if (company.currentStage === 'H1') {
          newStage = 'H2';
        }
        break;

      case 'issue_certificate':
        event = this.createEvent(
          company.id,
          'certificate_issued',
          `Удостоверение выдано: ${data?.certificateNumber || 'Без номера'}`,
          data
        );
        if (company.currentStage === 'H2') {
          newStage = 'A1';
        }
        break;
    }

    return {
      success: true,
      message: 'Действие выполнено успешно',
      event,
      stageChanged: newStage !== undefined,
      newStage
    };
  }
}

// ============ Unit Tests (inline for demonstration) ============

export const StageEngineTests = {
  testIceToTouchedRequiresLPRConversation: () => {
    const company: Company = {
      id: '1',
      name: 'Test Company',
      currentStage: 'C0',
      events: [],
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    const result = StageEngine.canTransitionTo(company, 'C1');
    console.assert(result.success === false, 'Should not allow transition without LPR conversation');
    console.assert(result.errors?.includes('Необходим разговор с ЛПР для перехода'), 'Should have correct error message');
    return result.success === false;
  },

  testIceToTouchedWithLPRConversation: () => {
    const company: Company = {
      id: '1',
      name: 'Test Company',
      currentStage: 'C0',
      events: [{
        id: '1',
        type: 'lpr_conversation',
        timestamp: new Date(),
        data: { comment: 'Test' },
        userId: 'user1',
        companyId: '1',
        description: 'Test conversation'
      }],
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    const result = StageEngine.canTransitionTo(company, 'C1');
    console.assert(result.success === true, 'Should allow transition with LPR conversation');
    return result.success === true;
  },

  testCannotSkipStages: () => {
    const company: Company = {
      id: '1',
      name: 'Test Company',
      currentStage: 'C0',
      events: [],
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    const result = StageEngine.canTransitionTo(company, 'W1');
    console.assert(result.success === false, 'Should not allow skipping stages');
    return result.success === false;
  },

  testRestrictedActionsOnTouched: () => {
    const company: Company = {
      id: '1',
      name: 'Test Company',
      currentStage: 'C1',
      events: [{
        id: '1',
        type: 'lpr_conversation',
        timestamp: new Date(),
        data: {},
        userId: 'user1',
        companyId: '1',
        description: 'Test'
      }],
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    const canCreateApp = StageEngine.isActionAllowed(company, 'create_application');
    const canSendCP = StageEngine.isActionAllowed(company, 'send_cp');
    const canPlanDemo = StageEngine.isActionAllowed(company, 'plan_demo');
    
    console.assert(canCreateApp === false, 'Should not allow create_application on Touched');
    console.assert(canSendCP === false, 'Should not allow send_cp on Touched');
    console.assert(canPlanDemo === false, 'Should not allow plan_demo on Touched');
    
    return !canCreateApp && !canSendCP && !canPlanDemo;
  },

  testDemo60DaysExpiry: () => {
    const company: Company = {
      id: '1',
      name: 'Test Company',
      currentStage: 'W3',
      events: [],
      createdAt: new Date(),
      updatedAt: new Date(),
      demoConductedAt: new Date(Date.now() - 61 * 24 * 60 * 60 * 1000) // 61 days ago
    };
    
    const canCreateApp = StageEngine.hasDemoConductedWithin60Days(company);
    console.assert(canCreateApp === false, 'Should not allow actions if demo was > 60 days ago');
    return canCreateApp === false;
  },

  runAll: () => {
    const tests = [
      { name: 'Ice to Touched requires LPR conversation', fn: StageEngineTests.testIceToTouchedRequiresLPRConversation },
      { name: 'Ice to Touched with LPR conversation', fn: StageEngineTests.testIceToTouchedWithLPRConversation },
      { name: 'Cannot skip stages', fn: StageEngineTests.testCannotSkipStages },
      { name: 'Restricted actions on Touched', fn: StageEngineTests.testRestrictedActionsOnTouched },
      { name: 'Demo 60 days expiry', fn: StageEngineTests.testDemo60DaysExpiry },
    ];

    console.log('🧪 Running Stage Engine Tests...\n');
    let passed = 0;
    let failed = 0;

    tests.forEach(test => {
      try {
        const result = test.fn();
        if (result) {
          console.log(`✅ ${test.name}`);
          passed++;
        } else {
          console.log(`❌ ${test.name}`);
          failed++;
        }
      } catch (e) {
        console.log(`❌ ${test.name}: ${e}`);
        failed++;
      }
    });

    console.log(`\n📊 Results: ${passed} passed, ${failed} failed`);
    return { passed, failed };
  }
};
