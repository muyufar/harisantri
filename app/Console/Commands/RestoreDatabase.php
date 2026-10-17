<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class RestoreDatabase extends Command
{
    protected $signature = 'hsn:restore {file} {--force : Wajib diisi karena perintah ini menimpa data}';

    protected $description = 'Mengembalikan database MySQL dari file cadangan SQL';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Pulihkan hanya dengan --force setelah Anda yakin file cadangannya benar.');

            return self::FAILURE;
        }

        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error('File cadangan tidak ditemukan.');

            return self::FAILURE;
        }

        if (config('database.default') !== 'mysql') {
            $this->error('Pemulihan perintah ini untuk koneksi MySQL. Untuk SQLite, ganti file database lalu bersihkan cache.');

            return self::FAILURE;
        }

        $binary = env('MYSQL_PATH');
        if (! is_string($binary) || ! is_file($binary)) {
            $binary = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysql.exe';
        }
        if (! is_file($binary)) {
            $binary = 'mysql';
        }

        $mysql = config('database.connections.mysql');
        $process = Process::env($mysql['password'] ? ['MYSQL_PWD' => $mysql['password']] : [])
            ->timeout(300)
            ->input(file_get_contents($file))
            ->run([
                $binary,
                '--host='.$mysql['host'],
                '--port='.$mysql['port'],
                '--user='.$mysql['username'],
                $mysql['database'],
            ]);

        if (! $process->successful()) {
            $this->error(trim($process->errorOutput()) ?: 'Pemulihan gagal.');

            return self::FAILURE;
        }

        $this->info('Database dipulihkan.');

        return self::SUCCESS;
    }
}
