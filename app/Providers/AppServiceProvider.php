<?php

namespace App\Providers;

use App\Channels\FcmChannel;
use App\Jobs\CheckGoalsDeadlinesJob;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Schedule;
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
        // Регистрируем FCM канал
        $this->app->make(ChannelManager::class)->extend('fcm', function () {
            return new FcmChannel();
        });

        // Настраиваем scheduled tasks
        Schedule::job(new CheckGoalsDeadlinesJob)->dailyAt('09:00');
    }
}
