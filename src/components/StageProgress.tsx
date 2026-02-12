import { StageCode } from '../types/crm';
import { STAGES, STAGE_ORDER } from '../config/stages';
import { cn } from '../utils/cn';

interface StageProgressProps {
  currentStage: StageCode;
}

export function StageProgress({ currentStage }: StageProgressProps) {
  const currentIndex = STAGE_ORDER.indexOf(currentStage);

  return (
    <div className="w-full overflow-x-auto pb-2">
      <div className="flex items-center min-w-max gap-1">
        {STAGE_ORDER.map((stageCode, index) => {
          const stage = STAGES[stageCode];
          const isCompleted = index < currentIndex;
          const isCurrent = stageCode === currentStage;
          const isFuture = index > currentIndex;

          return (
            <div key={stageCode} className="flex items-center">
              <div className="flex flex-col items-center">
                <div
                  className={cn(
                    "w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold transition-all",
                    isCompleted && "bg-green-500 text-white",
                    isCurrent && "bg-blue-600 text-white ring-4 ring-blue-200",
                    isFuture && "bg-gray-200 text-gray-500"
                  )}
                >
                  {isCompleted ? '✓' : stageCode}
                </div>
                <span
                  className={cn(
                    "text-xs mt-1 text-center max-w-[80px] truncate",
                    isCurrent && "font-bold text-blue-600",
                    isCompleted && "text-green-600",
                    isFuture && "text-gray-400"
                  )}
                >
                  {stage.name}
                </span>
              </div>
              {index < STAGE_ORDER.length - 1 && (
                <div
                  className={cn(
                    "w-8 h-1 mx-1",
                    index < currentIndex ? "bg-green-500" : "bg-gray-200"
                  )}
                />
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
