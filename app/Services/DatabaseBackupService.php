<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /**
     * @return array{path: string, filename: string}
     */
    public function createBackup(): array
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('النسخ الاحتياطي متاح حالياً لقواعد بيانات MySQL فقط.');
        }

        $config = config('database.connections.mysql');

        if (empty($config['database'])) {
            throw new RuntimeException('اسم قاعدة البيانات غير مُعرّف في الإعدادات.');
        }

        $mysqldump = $this->resolveMysqldumpPath();
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        $fileName = 'washing_system_backup_' . now()->format('Y_m_d_His') . '.sql';
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
        $defaultsFile = $this->createDefaultsFile($config);

        try {
            $process = new Process([
                $mysqldump,
                '--defaults-extra-file=' . $defaultsFile,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--result-file=' . $outputPath,
                $config['database'],
            ]);

            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                $error = trim($process->getErrorOutput() ?: $process->getOutput());

                throw new RuntimeException(
                    'فشل إنشاء النسخة الاحتياطية.' . ($error !== '' ? ' ' . $error : '')
                );
            }

            if (! File::exists($outputPath) || File::size($outputPath) === 0) {
                throw new RuntimeException('ملف النسخة الاحتياطية فارغ أو غير موجود.');
            }

            return [
                'path' => $outputPath,
                'filename' => $fileName,
            ];
        } finally {
            if (File::exists($defaultsFile)) {
                File::delete($defaultsFile);
            }
        }
    }

    private function resolveMysqldumpPath(): string
    {
        $customPath = env('MYSQLDUMP_PATH');

        if ($customPath && File::exists($customPath)) {
            return $customPath;
        }

        $finder = PHP_OS_FAMILY === 'Windows'
            ? new Process(['where', 'mysqldump'])
            : new Process(['which', 'mysqldump']);

        $finder->run();

        if ($finder->isSuccessful()) {
            $candidate = trim(strtok($finder->getOutput(), PHP_EOL));

            if ($candidate !== '' && File::exists($candidate)) {
                return $candidate;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $commonPaths = [
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
            ];

            foreach ($commonPaths as $path) {
                if (File::exists($path)) {
                    return $path;
                }
            }

            $laragonPaths = glob('C:\\laragon\\bin\\mysql\\mysql-*\\bin\\mysqldump.exe') ?: [];

            if ($laragonPaths !== []) {
                return $laragonPaths[0];
            }

            $wampPaths = glob('C:\\wamp64\\bin\\mysql\\mysql*\\bin\\mysqldump.exe') ?: [];

            if ($wampPaths !== []) {
                return $wampPaths[0];
            }
        }

        throw new RuntimeException(
            'لم يتم العثور على mysqldump. أضف MYSQLDUMP_PATH في ملف .env (مثال: C:\\xampp\\mysql\\bin\\mysqldump.exe).'
        );
    }

    private function createDefaultsFile(array $config): string
    {
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        $file = $tempDir . DIRECTORY_SEPARATOR . 'mysql_backup_' . uniqid('', true) . '.cnf';

        $lines = [
            '[client]',
            'user=' . $config['username'],
        ];

        if (! empty($config['password'])) {
            $lines[] = 'password=' . $config['password'];
        }

        $lines[] = 'host=' . ($config['host'] ?? '127.0.0.1');

        if (! empty($config['port'])) {
            $lines[] = 'port=' . $config['port'];
        }

        File::put($file, implode(PHP_EOL, $lines) . PHP_EOL);

        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod($file, 0600);
        }

        return $file;
    }
}
