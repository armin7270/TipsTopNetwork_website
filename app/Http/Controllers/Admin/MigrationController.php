<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminLog;
use App\Services\DatabaseTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * بکاپ و مهاجرت: خروجی/ورودی دیتابیس و فایل‌ها + راهنمای انتقال به اکانت جدید
 */
class MigrationController extends Controller
{
    public function __construct(protected DatabaseTransferService $transfer) {}

    public function index(): View
    {
        return view('admin.migration', [
            'driver' => $this->transfer->driver(),
            'dbSize' => $this->transfer->databaseSizeLabel(),
            'storageSize' => $this->transfer->storageSizeLabel(),
            'appUrl' => rtrim(config('app.url'), '/'),
        ]);
    }

    public function exportDb(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            $file = $this->transfer->exportDatabase($this->transfer->transferDir());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        AdminLog::record($request->user(), 'settings_updated', null, 'db:export');

        return response()->download($file)->deleteFileAfterSend(true);
    }

    public function exportFiles(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            $file = $this->transfer->exportPublicFiles($this->transfer->transferDir());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $file) {
            return back()->with('error', __('فایل عمومی برای بکاپ وجود ندارد.'));
        }

        AdminLog::record($request->user(), 'settings_updated', null, 'storage:export');

        return response()->download($file)->deleteFileAfterSend(true);
    }

    public function importDb(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dump' => ['required', 'file', 'max:51200', 'mimes:sql,sqlite,db'],
        ], [
            'dump.required' => __('فایل دامپ را انتخاب کنید.'),
        ]);

        $path = $validated['dump']->storeAs('migration', 'import-'.time().'.'.$validated['dump']->getClientOriginalExtension());

        try {
            $this->transfer->importDatabase(Storage::path($path));
        } catch (\Throwable $e) {
            Storage::delete($path);

            return back()->with('error', $e->getMessage());
        }

        Storage::delete($path);
        AdminLog::record($request->user(), 'settings_updated', null, 'db:import');

        return back()->with('success', __('دامپ با موفقیت وارد شد. ✅'));
    }

    public function importFiles(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'archive' => ['required', 'file', 'max:51200', 'mimes:zip'],
        ], [
            'archive.required' => __('فایل زیپ را انتخاب کنید.'),
        ]);

        $path = $validated['archive']->storeAs('migration', 'import-files-'.time().'.zip');

        try {
            $count = $this->transfer->importPublicFiles(Storage::path($path));
        } catch (\Throwable $e) {
            Storage::delete($path);

            return back()->with('error', $e->getMessage());
        }

        Storage::delete($path);
        AdminLog::record($request->user(), 'settings_updated', null, 'storage:import ('.$count.')');

        return back()->with('success', __(':count فایل بازیابی شد. ✅', ['count' => $count]));
    }
}
