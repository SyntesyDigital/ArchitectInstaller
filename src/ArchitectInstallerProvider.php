<?php

namespace SyntesyDigital\ArchitectInstaller;

use Illuminate\Support\ServiceProvider;

class ArchitectInstallerProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Commands\ArchitectInstall::class
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
