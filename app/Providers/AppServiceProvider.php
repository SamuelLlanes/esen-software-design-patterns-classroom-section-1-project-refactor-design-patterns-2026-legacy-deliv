<?php

namespace App\Providers;

use App\Services\Notifications\EmailOrderObserver;
use App\Services\Notifications\LoggerOrderObserver;
use App\Services\Notifications\OrderNotificationDispatcher;
use App\Services\Notifications\PushOrderObserver;
use App\Services\Notifications\SmsOrderObserver;
use App\Services\EmailService;
use App\Support\Logger;
use App\Services\PushService;
use App\Services\SMSService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Logger::class, function () {
            return new Logger();
        });

        $this->app->singleton(OrderNotificationDispatcher::class, function ($app) {
            return new OrderNotificationDispatcher([
                new EmailOrderObserver(new EmailService()),
                new SmsOrderObserver(new SMSService()),
                new PushOrderObserver(new PushService()),
                new LoggerOrderObserver($app->make(Logger::class)),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
