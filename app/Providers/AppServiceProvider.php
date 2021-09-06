<?php

namespace App\Providers;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
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
        try {
            $dotEnv = Dotenv::createImmutable(getcwd(), '.bb.env');
            $dotEnv->load();
        } catch (InvalidPathException $e) {

        }

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
