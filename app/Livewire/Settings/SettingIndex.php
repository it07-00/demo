<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Support\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Cài đặt hệ thống')]
final class SettingIndex extends Component
{
    // Tab control
    public string $activeTab = 'general';

    // Settings fields
    public string $website_name = '';
    public string $contact_email = '';
    public string $timezone = '';
    public string $language = '';

    // Status message
    public ?string $successMessage = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Setting::class);

        $this->website_name = Setting::get('website_name', 'TTVH-TC');
        $this->contact_email = Setting::get('contact_email', '');
        $this->timezone = Setting::get('timezone', 'Asia/Ho_Chi_Minh');
        $this->language = Setting::get('language', 'vi');
    }

    public function save(): void
    {
        Gate::authorize('update', Setting::class);

        $this->validate([
            'website_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'timezone' => ['required', 'string'],
            'language' => ['required', 'string'],
        ]);

        Setting::set('website_name', $this->website_name);
        Setting::set('contact_email', $this->contact_email);
        Setting::set('timezone', $this->timezone);
        Setting::set('language', $this->language);

        ActivityLogger::log('update_settings', 'Đã cập nhật cấu hình hệ thống');

        $this->successMessage = 'Lưu cấu hình hệ thống thành công!';
        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Thành công!',
            'text' => $this->successMessage,
        ]);
    }

    public function backupNow()
    {
        Gate::authorize('update', Setting::class);

        try {
            $connection = config('database.default');
            if ($connection !== 'mysql') {
                throw new \Exception('Chỉ hỗ trợ sao lưu cho cơ sở dữ liệu MySQL.');
            }

            $tables = [];
            $result = \DB::select('SHOW TABLES');

            foreach ($result as $row) {
                $tables[] = current((array)$row);
            }

            $sql = "-- TTVH-TC Database Backup\n";
            $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Get table structure
                $createStatement = \DB::select("SHOW CREATE TABLE `$table`")[0]->{'Create Table'};
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $createStatement . ";\n\n";

                // Get table data
                $rows = \DB::table($table)->get();
                if ($rows->count() > 0) {
                    $sql .= "INSERT INTO `$table` VALUES \n";
                    $insertRows = [];
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ((array)$row as $val) {
                            if (is_null($val)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . addslashes((string)$val) . "'";
                            }
                        }
                        $insertRows[] = "(" . implode(', ', $values) . ")";
                    }
                    $sql .= implode(",\n", $insertRows) . ";\n\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;
            file_put_contents($filepath, $sql);

            ActivityLogger::log('backup_db', 'Đã tải bản sao lưu cơ sở dữ liệu (SQL)');

            return response()->download($filepath);
        } catch (\Exception $e) {
            $this->successMessage = 'Lỗi khi tạo bản sao lưu: ' . $e->getMessage();
        }
    }

    public function exportData()
    {
        Gate::authorize('update', Setting::class);

        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        ActivityLogger::log('export_settings', 'Đã xuất cấu hình hệ thống (JSON)');

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, 'ttvh-tc-settings-' . date('Y-m-d') . '.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function downloadSourceCode()
    {
        Gate::authorize('update', Setting::class);

        try {
            $zip = new \ZipArchive();
            $zipDir = storage_path('app/backups');
            if (!file_exists($zipDir)) {
                mkdir($zipDir, 0755, true);
            }
            
            $filename = 'ttvh-tc-source-' . date('Y-m-d-H-i-s') . '.zip';
            $filepath = $zipDir . '/' . $filename;

            if ($zip->open($filepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Không thể tạo file ZIP.');
            }

            $basePath = base_path();
            
            $dirs = [
                'app',
                'bootstrap',
                'config',
                'database',
                'public',
                'resources',
                'routes',
                'tests'
            ];

            foreach ($dirs as $dir) {
                $dirPath = $basePath . DIRECTORY_SEPARATOR . $dir;
                if (!file_exists($dirPath)) continue;

                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dirPath),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($basePath) + 1);

                        // Skip vendors and other unwanted dynamic directories
                        if (str_contains($relativePath, 'node_modules') || str_contains($relativePath, 'vendor') || str_contains($relativePath, 'storage')) {
                            continue;
                        }

                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }

            $rootFiles = [
                'composer.json',
                'package.json',
                'vite.config.js',
                'artisan',
                '.env.example',
                'README.md'
            ];

            foreach ($rootFiles as $file) {
                $filePath = $basePath . DIRECTORY_SEPARATOR . $file;
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $file);
                }
            }

            $zip->close();

            $this->dispatch('swal:alert', [
                'icon' => 'success',
                'title' => 'Thành công!',
                'text' => 'Tạo bản sao lưu mã nguồn thành công. Bắt đầu tải về!',
            ]);

            ActivityLogger::log('backup_source', 'Đã tải bản sao lưu mã nguồn (ZIP)');

            return response()->download($filepath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            $this->successMessage = 'Lỗi khi sao lưu mã nguồn: ' . $e->getMessage();
            $this->dispatch('swal:alert', [
                'icon' => 'error',
                'title' => 'Thất bại!',
                'text' => $this->successMessage,
            ]);
        }
    }

    public function clearCache(): void
    {
        Gate::authorize('update', Setting::class);
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');

            ActivityLogger::log('clear_cache', 'Đã xóa bộ nhớ đệm hệ thống');

            $this->successMessage = 'Xóa toàn bộ cache hệ thống (Cache, View, Route, Config) thành công!';
            $this->dispatch('swal:alert', [
                'icon' => 'success',
                'title' => 'Thành công!',
                'text' => $this->successMessage,
            ]);
        } catch (\Exception $e) {
            $this->successMessage = 'Lỗi khi xóa cache: ' . $e->getMessage();
            $this->dispatch('swal:alert', [
                'icon' => 'error',
                'title' => 'Thất bại!',
                'text' => $this->successMessage,
            ]);
        }
    }

    public function render(): View
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->latest()
            ->take(50)
            ->get();

        return view('livewire.settings.setting-index', [
            'logs' => $logs,
        ]);
    }
}
