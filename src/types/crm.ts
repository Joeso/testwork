// CRM Stage Types and Interfaces

export type StageCode = 
  | 'C0' | 'C1' | 'C2' 
  | 'W1' | 'W2' | 'W3' 
  | 'H1' | 'H2' 
  | 'A1' | 'N0';

export type StageName = 
  | 'Ice' | 'Touched' | 'Aware' | 'Interested' 
  | 'demo_planned' | 'Demo_done' | 'Committed' 
  | 'Customer' | 'Activated' | 'Null';

export type EventType = 
  | 'contact_attempt'
  | 'lpr_conversation'
  | 'discovery_filled'
  | 'demo_planned'
  | 'demo_conducted'
  | 'invoice_sent'
  | 'payment_received'
  | 'certificate_issued'
  | 'call_answered'
  | 'cp_sent'
  | 'application_created';

export interface StageConfig {
  code: StageCode;
  name: StageName;
  displayName: string;
  restrictions: string[];
  entryCondition: string;
  exitCondition: string;
  availableActions: ActionType[];
  instruction: string;
}

export type ActionType = 
  | 'make_call'
  | 'log_conversation'
  | 'fill_discovery'
  | 'plan_demo'
  | 'conduct_demo'
  | 'create_application'
  | 'send_cp'
  | 'mark_invoice_sent'
  | 'mark_payment_received'
  | 'issue_certificate';

export interface CRMEvent {
  id: string;
  type: EventType;
  timestamp: Date;
  data: Record<string, unknown>;
  userId: string;
  companyId: string;
  description: string;
}

export interface Company {
  id: string;
  name: string;
  currentStage: StageCode;
  events: CRMEvent[];
  createdAt: Date;
  updatedAt: Date;
  // Discovery form data
  discoveryData?: DiscoveryData;
  // Demo planning
  demoPlannedAt?: Date;
  // Demo conducted timestamp
  demoConductedAt?: Date;
  // Invoice
  invoiceSentAt?: Date;
  invoiceNumber?: string;
  // Payment
  paymentReceivedAt?: Date;
  // Certificate
  certificateIssuedAt?: Date;
  certificateNumber?: string;
}

export interface DiscoveryData {
  companySize: string;
  industry: string;
  painPoints: string;
  budget: string;
  decisionMaker: string;
  timeline: string;
  notes: string;
  filledAt: Date;
}

export interface TransitionResult {
  success: boolean;
  message: string;
  newStage?: StageCode;
  errors?: string[];
}

export interface ActionResult {
  success: boolean;
  message: string;
  event?: CRMEvent;
  stageChanged?: boolean;
  newStage?: StageCode;
}
