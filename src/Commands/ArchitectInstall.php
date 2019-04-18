<?php

namespace SyntesyDigital\ArchitectInstaller\Commands;

use Illuminate\Console\Command;

class ArchitectInstall extends Command
{

    private $packages = [

        // Architect Core
        [
            'name' => 'ArchitectCore',
            'description' => 'Core package of Syntesy Architect solution',
            'url' => 'https://github.com/SyntesyDigital/ArchitectCore',
            'directory' => 'Architect'
        ],

    ];


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'architect:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Architect package';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function prompt()
    {
        $packagesList = collect($this->packages)
            ->map(function($package){
                return [$package["name"], $package["description"]];
            });

        $packageNames = collect($this->packages)
            ->map(function($package){
                return $package["name"];
            })->toArray();

        $this->info('Syntesy Digital © http://syntesy.io');

        $this->table([
            'Package Name',
            'Description'
        ], $packagesList);

        $this->package = $this->choice('Chose the Architect packages to install', $packageNames, $packageNames[0]);
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->prompt();

        if(!$this->package) {
            $this->error('No package selected');
            return false;
        }

        $path = app_path('../Modules/');

        if (!is_dir($path)) {
            $this->info('--- CREATING MODULES DIRECTORY  ----');
            $this->info('Creating directory ' . $path);
            if(mkdir($path, 0775)) {
                $this->info('... DONE');
            }
        }

        // Install Laravel Module vendor
        if(!class_exists('Nwidart\\Modules\\ModulesServiceProvider')) {
            $this->info('--- INSTALLING LARAVEL MODULES PACKAGE ----');
            exec('composer require nwidart/laravel-modules');
            $this->info('... DONE');
        }

        $package = $this->getPackageByName($this->package);

        if(is_dir($path . $package["directory"])) {
            $this->error('Module '.$package["name"].' is already installed');
            return false;
        }

        // Clone Module directory
        $this->info('--- (1/3) CLONING REPOSITORY ----');
        exec("git clone ".$package["url"]."  Modules/" . $package["directory"]);
        $this->info('... DONE');

        // Dump autoload
        $this->info('--- (2/3) DUMP AUTOLOAD ----');
        exec('composer dumpautoload');
        $this->info('... DONE');

        // Migrate module DB
        $this->info('--- (3/3) MIGRATE MODULE DB ----');
        exec('php artisan module:migrate ' . $package["directory"]);
        $this->info('... DONE');
    }


    private function getPackageByName($name)
    {
        return collect($this->packages)
            ->filter(function($item) use ($name) {
                return $item["name"] === $name ? true : false;
            })->first();
    }
}
