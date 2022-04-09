<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        config(['logging.channels.daily.path' => \Phar::running()
            ? dirname(\Phar::running(false)) . '/storage/logs/astolfo-twitter-poster.log'
            : storage_path('logs/astolfo-twitter-poster.log')
        ]);

        config(['database.connections.sqlite.database' => \Phar::running()
            ? dirname(\Phar::running(false)) . '/database/astolfo-twitter-poster.sqlite'
            : database_path('database.sqlite')
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
