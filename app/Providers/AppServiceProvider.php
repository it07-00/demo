<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(\App\Models\DailyReport::class, \App\Policies\DailyReportPolicy::class);

        // Register authentication activity logging
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            static function (\Illuminate\Auth\Events\Login $event): void {
                ActivityLogger::log('login', 'Đăng nhập thành công', $event->user);
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            static function (\Illuminate\Auth\Events\Failed $event): void {
                $username = $event->credentials['username'] ?? $event->credentials['email'] ?? 'Không rõ';
                ActivityLogger::log('failed_login', "Đăng nhập thất bại (tài khoản: $username)");
            }
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            static function (\Illuminate\Auth\Events\Logout $event): void {
                ActivityLogger::log('logout', 'Đăng xuất', $event->user);
            }
        );


        if (!$this->app->runningInConsole()) {
            try {
                if ($timezone = \App\Models\Setting::get('timezone')) {
                    date_default_timezone_set($timezone);
                    config(['app.timezone' => $timezone]);
                }
                if ($language = \App\Models\Setting::get('language')) {
                    $this->app->setLocale($language);
                    config(['app.locale' => $language]);
                }
            } catch (\Throwable $e) {
                // Ignore DB/connection errors during early boot or install
            }
        }
    }
}
