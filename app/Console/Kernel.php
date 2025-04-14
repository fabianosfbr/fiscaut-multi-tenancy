<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define o agendamento de comandos da aplicação.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Executa a verificação de comandos agendados a cada minuto
        $schedule->command('scheduled-commands:run')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/scheduled-commands.log'))
            ->onFailure(function () {
                // Notifica em caso de falha
                Log::error('Falha na execução do agendador de comandos');
            });

        // Limpa os logs antigos
        $schedule->command('logs:clear')
            ->daily()
            ->appendOutputTo(storage_path('logs/scheduled-commands.log'));

        // Limpa o cache do Laravel
        $schedule->command('cache:clear')
            ->daily()
            ->appendOutputTo(storage_path('logs/scheduled-commands.log'));
    }

    /**
     * Registra os comandos da aplicação.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 