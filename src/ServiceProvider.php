<?php

namespace IsapOu\Creator;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', CreatorMiddleware::class);
    }
}
