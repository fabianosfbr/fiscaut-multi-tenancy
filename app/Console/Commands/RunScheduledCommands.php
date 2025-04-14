<?php

namespace App\Console\Commands;

use App\Models\ScheduledCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunScheduledCommands extends Command
{
    protected $signature = 'scheduled-commands:run';
    protected $description = 'Executa os comandos agendados';

    public function handle()
    {
        $this->info('Iniciando verificação de comandos agendados...');

        $now = now();
        $commands = ScheduledCommand::where('enabled', true)->get();


        foreach ($commands as $command) {
            if ($this->shouldRunCommand($command, $now)) {
                $this->executeCommand($command);
            }
        }

        $this->info('Verificação de comandos agendados concluída.');
        return Command::SUCCESS;
    }

    private function shouldRunCommand(ScheduledCommand $command, $now): bool
    {
        $cronExpression = $this->getCronExpression($command);

        try {
            return \Cron\CronExpression::factory($cronExpression)->isDue($now);
        } catch (\Exception $e) {
            Log::error("Erro ao verificar expressão cron para o comando {$command->id}: {$e->getMessage()}");
            return false;
        }
    }

    private function getCronExpression(ScheduledCommand $command): string
    {
        return match ($command->preset) {
            'every_minute' => '* * * * *',
            'every_five_minutes' => '*/5 * * * *',
            'hourly' => '0 * * * *',
            'daily' => $this->getDailyCron($command),
            'weekly' => $this->getWeeklyCron($command),
            'monthly' => $this->getMonthlyCron($command),
            default => $command->cron_expression,
        };
    }

    private function getDailyCron(ScheduledCommand $command): string
    {
        if (!$command->time) {
            return '0 0 * * *';
        }

        $time = \Carbon\Carbon::parse($command->time);
        return "{$time->minute} {$time->hour} * * *";
    }

    private function getWeeklyCron(ScheduledCommand $command): string
    {
        if (!$command->time) {
            return '0 0 * * 0';
        }

        $time = \Carbon\Carbon::parse($command->time);
        return "{$time->minute} {$time->hour} * * 0";
    }

    private function getMonthlyCron(ScheduledCommand $command): string
    {
        if (!$command->time) {
            return '0 0 1 * *';
        }

        $time = \Carbon\Carbon::parse($command->time);
        return "{$time->minute} {$time->hour} 1 * *";
    }

    private function executeCommand(ScheduledCommand $command): void
    {
        try {
            $this->info("Executando comando: {$command->command}");
            
            $arguments = $this->parseArguments($command->arguments);
            
            Artisan::call($command->command, $arguments);
            
            // Atualiza a data/hora da última execução
            $command->update(['last_run_at' => now()]);
            
            $this->info("Comando {$command->command} executado com sucesso.");
            Log::info("Comando agendado executado: {$command->command}", [
                'command_id' => $command->id,
                'arguments' => $arguments,
                'last_run_at' => $command->last_run_at,
            ]);
        } catch (\Exception $e) {
            $this->error("Erro ao executar comando {$command->command}: {$e->getMessage()}");
            Log::error("Erro ao executar comando agendado: {$command->command}", [
                'command_id' => $command->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function parseArguments(?string $arguments): array
    {
        if (empty($arguments)) {
            return [];
        }

        // Tenta decodificar como JSON
        $decoded = json_decode($arguments, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Se não for JSON válido, tenta parsear como linha de comando
        $parsedArguments = [];
        $parts = explode(' ', trim($arguments));
                
        foreach ($parts as $part) {
            if (str_starts_with($part, '--')) {                
                if (str_contains($part, '=')) {
                    [$key, $value] = explode('=', $part, 2);
                    $parsedArguments[$key] = $value;
                } else {
                    $parsedArguments[$part] = true;
                }
            }
        }

        return $parsedArguments;
    }
}
