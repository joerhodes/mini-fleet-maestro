<?php

namespace App\Support\Ansible;

use App\Enums\AnsibleRunOutcome;

final readonly class AnsibleRunResult
{
    public function __construct(
        public int $exitCode,
        public string $output,
        public string $errorOutput,
    ) {}

    public function outcome(): AnsibleRunOutcome
    {
        return AnsibleRunOutcome::fromExitCode($this->exitCode);
    }
}
