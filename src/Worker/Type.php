<?php

declare(strict_types=1);

namespace Mammatus\Queue\Worker;

enum Type: String
{
    // uses Kubernetes Cron Jobs
    case Kubernetes = 'kubernetes';

    // Runs a group process but with shared mutex
    case Internal = 'internal';

    // Runs in a group process but without shared mutex: Meant to handle in processes cron jobs
    case Daemon = 'daemon';
}
