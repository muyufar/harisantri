<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'hsn:backup {--path=}';

    protected $description = 'Membuat cadangan database ke storage/app/backups';

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $connection = config('database.default');
        $stamp = now()->format('Ymd-His');

        if ($connection === 'sqlite') {
            $source = config('database.connections.sqlite.database');
            if ($source === ':memory:' || ! is_file($source)) {
                $this->error('File database SQLite tidak ditemukan.');

                return self::FAILURE;
            }
            $target = $this->option('path') ?: $directory."/harisantri-{$stamp}.sqlite";
            copy($source, $target);
            $this->info($target);

            return self::SUCCESS;
        }

        if ($connection !== 'mysql') {
            $this->error('Cadangan otomatis saat ini mendukung MySQL dan SQLite.');

            return self::FAILURE;
        }

        $binary = $this->binary('MYSQLDUMP_PATH', 'mysqldump', [
            'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe',
        ]);

        if (! $binary) {
            $this->error('mysqldump tidak ditemukan. Set MYSQLDUMP_PATH di file .env.');

            return self::FAILURE;
        }

        $target = $this->option('path') ?: $directory."/harisantri-{$stamp}.sql";
        $mysql = config('database.connections.mysql');
        $command = [
            $binary,
            '--host='.$mysql['host'],
            '--port='.$mysql['port'],
            '--user='.$mysql['username'],
            '--single-transaction',
            '--routines',
            '--result-file='.$target,
            $mysql['database'],
        ];

        $process = Process::env($mysql['password'] ? ['MYSQL_PWD' => $mysql['password']] : [])
            ->timeout(300)
            ->run($command);

        if (! $process->successful()) {
            $this->error(trim($process->errorOutput()) ?: 'mysqldump gagal.');

            return self::FAILURE;
        }

        $this->info($target);

        return self::SUCCESS;
    }

    /** @param  array<int, string>  $candidates */
    private function binary(string $envKey, string $name, array $candidates): ?string
    {
        $fromEnv = env($envKey);
        if (is_string($fromEnv) && $fromEnv !== '' && is_file($fromEnv)) {
            return $fromEnv;
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $name;
    }
}
