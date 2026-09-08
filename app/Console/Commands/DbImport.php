<?php

namespace App\Console\Commands;

use App\Services\DatabaseTransferService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:import {file : مسیر فایل دامپ (.sql یا .sqlite)} {--force : بدون پرسش تایید}')]
#[Description('ورود دامپ دیتابیس (برای مهاجرت به هاست/اکانت جدید)')]
class DbImport extends Command
{
    public function handle(DatabaseTransferService $transfer): int
    {
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error(__('فایل یافت نشد.'));

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('دیتابیس فعلی کاملاً جایگزین می‌شود. ادامه می‌دهید؟')) {
            return self::FAILURE;
        }

        try {
            $transfer->importDatabase($file);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(__('دامپ با موفقیت وارد شد.'));

        return self::SUCCESS;
    }
}
