<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

// Preload Infection event classes; OTEL fiber instrumentation can break autoloading after mutation runs.
require __DIR__ . '/../../vendor/infection/infection/src/Event/Events/MutationAnalysis/MutationTestingWasFinished.php';
require __DIR__ . '/../../vendor/infection/infection/src/Event/Events/MutationAnalysis/MutationTestingWasStarted.php';
require __DIR__ . '/../../vendor/infection/infection/src/Event/Events/MutationAnalysis/MutationEvaluationWasStarted.php';
require __DIR__ . '/../../vendor/infection/infection/src/Event/Events/MutantProcessWasFinished.php';
