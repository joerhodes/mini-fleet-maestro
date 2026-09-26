<?php

namespace App\Enums;

enum AnsibleRunOutcome: string
{
    case Success = 'success';
    case HostFailed = 'host_failed';
    case Unreachable = 'unreachable';
    case Error = 'error';

    /**
     * Map an ansible-playbook exit code to an outcome. Verified against Ansible core 2.21:
     * 2 is a failed host, 4 is an unreachable host, and anything else (1 for a missing
     * playbook or bad arguments, 3 in older docs, 99, 250, ...) is treated as an error.
     */
    public static function fromExitCode(int $exitCode): self
    {
        return match ($exitCode) {
            0 => self::Success,
            2 => self::HostFailed,
            4 => self::Unreachable,
            default => self::Error,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Success',
            self::HostFailed => 'One or more hosts failed',
            self::Unreachable => 'One or more hosts were unreachable',
            self::Error => 'Ansible reported an error',
        };
    }
}
