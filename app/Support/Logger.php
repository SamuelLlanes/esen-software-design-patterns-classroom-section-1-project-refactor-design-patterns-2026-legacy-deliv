<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

// Refactor target en S5 M: inyectar por constructor, registrar en Container.
class Logger
{
    private array $logs = [];

    public function __construct() {}

    public function log(string $message, string $level = 'info'): void
    {
        $entry = [
            'timestamp' => now()->toIso8601String(),
            'level'     => $level,
            'message'   => $message,
        ];
        $this->logs[] = $entry;

        // También escribe al sistema de logs de Laravel
        Log::channel('legacy')->{$level}("[Legacy] {$message}");
    }

    public function getLogs(): array { return $this->logs; }
    public function clearLogs(): void { $this->logs = []; }

    // Previene clonación
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton.');
    }
}
