import { useState } from 'react';
import { StageEngineTests } from '../services/StageEngine';

interface TestResult {
  name: string;
  passed: boolean;
  error?: string;
}

export function TestRunner() {
  const [results, setResults] = useState<TestResult[]>([]);
  const [isRunning, setIsRunning] = useState(false);

  const runTests = () => {
    setIsRunning(true);
    setResults([]);

    const tests = [
      { name: 'Ice → Touched требует разговора с ЛПР', fn: StageEngineTests.testIceToTouchedRequiresLPRConversation },
      { name: 'Ice → Touched с разговором ЛПР разрешён', fn: StageEngineTests.testIceToTouchedWithLPRConversation },
      { name: 'Нельзя перепрыгивать стадии', fn: StageEngineTests.testCannotSkipStages },
      { name: 'Ограниченные действия на Touched', fn: StageEngineTests.testRestrictedActionsOnTouched },
      { name: 'Демо истекает через 60 дней', fn: StageEngineTests.testDemo60DaysExpiry },
    ];

    const testResults: TestResult[] = [];

    tests.forEach(test => {
      try {
        const result = test.fn();
        testResults.push({
          name: test.name,
          passed: result
        });
      } catch (e) {
        testResults.push({
          name: test.name,
          passed: false,
          error: String(e)
        });
      }
    });

    setResults(testResults);
    setIsRunning(false);
  };

  const passedCount = results.filter(r => r.passed).length;
  const failedCount = results.filter(r => !r.passed).length;

  return (
    <div className="bg-white rounded-xl shadow-lg overflow-hidden">
      <div className="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-4">
        <h2 className="text-lg font-bold flex items-center gap-2">
          <span>🧪</span> Unit Tests
        </h2>
        <p className="text-purple-100 text-sm mt-1">
          Тестирование логики переходов между стадиями
        </p>
      </div>

      <div className="p-4">
        <button
          onClick={runTests}
          disabled={isRunning}
          className="w-full py-3 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition-colors disabled:opacity-50"
        >
          {isRunning ? '⏳ Выполняется...' : '▶️ Запустить тесты'}
        </button>

        {results.length > 0 && (
          <div className="mt-4">
            {/* Summary */}
            <div className={`p-4 rounded-lg mb-4 ${
              failedCount === 0 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
            }`}>
              <div className="flex items-center justify-between">
                <span className="font-medium">
                  {failedCount === 0 ? '✅ Все тесты пройдены!' : '❌ Есть неудачные тесты'}
                </span>
                <span className="text-sm">
                  <span className="text-green-600">{passedCount} ✓</span>
                  {' / '}
                  <span className="text-red-600">{failedCount} ✗</span>
                </span>
              </div>
            </div>

            {/* Test Results */}
            <div className="space-y-2">
              {results.map((result, idx) => (
                <div
                  key={idx}
                  className={`p-3 rounded-lg flex items-center gap-3 ${
                    result.passed ? 'bg-green-50' : 'bg-red-50'
                  }`}
                >
                  <span className="text-2xl">
                    {result.passed ? '✅' : '❌'}
                  </span>
                  <div className="flex-1">
                    <p className={`font-medium ${result.passed ? 'text-green-800' : 'text-red-800'}`}>
                      {result.name}
                    </p>
                    {result.error && (
                      <p className="text-sm text-red-600 mt-1">{result.error}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
