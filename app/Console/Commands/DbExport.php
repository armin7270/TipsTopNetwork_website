<?php

namespace App\Console\Commands;

use App\Services\DatabaseTransferService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:export {--files : شامل فایل‌های عمومی هم بشود}')]
#[Description('خروجی دیتابیس (+ فایل‌های عمومی) برای مهاجرت بین هاست‌ها')]
class DbExport extends Command
{
    public function handle(DatabaseTransferService $transfer): int
    {
        try {
            $db = $transfer->exportDatabase($transfer->transferDir());
            $this->info('دامپ دیتابیس: '.$db.' ('.round(filesize($db) / 1024).' KB)');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('files')) {
            try {
                $zip = $transfer->exportPublicFiles($transfer->transferDir());

                if ($zip) {
                    $this->info('فایل‌های عمومی: '.$zip.' ('.round(filesize($zip) / 1024).' KB)');
                } else {
                    $this->warn('فایل عمومی برای بکاپ وجود نداشت.');
                }
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
