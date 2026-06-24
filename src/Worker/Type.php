<?php

declare(strict_types=1);

namespace Mammatus\Queue\Worker;

enum Type: String
{
    // uses Mammatus Groups to have their own Kubernetes Deployment with a generated Group
    case Kubernetes = 'kubernetes';

    // Runs in a group process
    case Daemon = 'daemon';
}
