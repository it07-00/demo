<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\DailyReport;
use App\Models\Setting;
use App\Models\User;
use App\Policies\DailyReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (config('app.env') === 'production' || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            URL::forceScheme('https');
        }

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(DailyReport::class, DailyReportPolicy::class);

        // Register authentication activity logging
        Event::listen(
            Login::class,
            static function (Login $event): void {
                ActivityLogger::log('login', 'Đăng nhập thành công', $event->user);
            }
        );

        Event::listen(
            Failed::class,
            static function (Failed $event): void {
                $username = $event->credentials['username'] ?? $event->credentials['email'] ?? 'Không rõ';
                ActivityLogger::log('failed_login', "Đăng nhập thất bại (tài khoản: $username)");
            }
        );

        Event::listen(
            Logout::class,
            static function (Logout $event): void {
                ActivityLogger::log('logout', 'Đăng xuất', $event->user);
            }
        );

        if (! $this->app->runningInConsole()) {
            try {
                if ($timezone = Setting::get('timezone')) {
                    date_default_timezone_set($timezone);
                    config(['app.timezone' => $timezone]);
                }
                if ($language = Setting::get('language')) {
                    $this->app->setLocale($language);
                    config(['app.locale' => $language]);
                }
            } catch (\Throwable $e) {
                // Ignore DB/connection errors during early boot or install
            }
        }
    }
}
