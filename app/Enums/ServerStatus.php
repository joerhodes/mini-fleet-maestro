<?php

namespace App\Enums;

enum ServerStatus: string
{
    case Pending = 'pending';
    case Unreachable = 'unreachable';
    case PingOk = 'ping_ok';
    case SshOk = 'ssh_ok';
    case Ready = 'ready';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Unreachable => 'Unreachable',
            self::PingOk => 'Ping OK',
            self::SshOk => 'SSH OK',
            self::Ready => 'Ready',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Unreachable => 'red',
            self::PingOk => 'yellow',
            self::SshOk => 'yellow',
            self::Ready => 'green',
        };
    }
}
