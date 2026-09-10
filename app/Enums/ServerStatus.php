<?php

namespace App\Enums;

enum ServerStatus: string
{
    case Pending = 'pending';
    case Testing = 'testing';
    case Connected = 'connected';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Testing => 'Testing',
            self::Connected => 'Connected',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Testing => 'blue',
            self::Connected => 'green',
            self::Failed => 'red',
        };
    }
}
