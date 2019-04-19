<?php

namespace SyntesyDigital\ArchitectInstaller\Commands;

use Illuminate\Console\Command;

class ArchitectInstall extends Command
{
    private $packages = [

        [
            'name' => 'ArchitectCore',
            'description' => 'Core package of Syntesy Architect solution',
            'url' => 'https://github.com/SyntesyDigital/ArchitectCore',
            'directory' => 'Architect',
            'vendors' => [
                [
                    "package" => "barryvdh/laravel-cors",
                    "version" => "^0.11.3"
                ],

                [
                    "package" => "doctrine/dbal",
                    "version" => "^2.9"
                ],

                [
                    "package" => "intervention/image",
                    "version" => "^2.4"
                ],

                [
                    "package" => "jenssegers/date",
                    "version" => "^3.5"
                ],

                [
                    "package" => "kalnoy/nestedset",
                    "version" => "^4.3"
                ],

                [
                    "package" => "laravelcollective/html",
                    "version" => "^5.4.0"
                ],

                [
                    "package" => "prettus/l5-repository",
                    "version" => "^2.6"
                ],

                [
                    "package" => "yajra/laravel-datatables-oracle",
                    "version" => "~8.0"
                ],

                [
                    "package" => "mariuzzo/laravel-js-localization",
                    "version" => "^1.4"
                ],

                [
                    "package" => "mcamara/laravel-localization",
                    "version" => "^1.3"
                ],

                [
                    "package" => "elasticsearch/elasticsearch",
                    "version" => "^6.0"
                ],

                [
                    "package" => "kevindierkx/laravel-domain-localization",
                    "version" => "^2.0"
                ]
            ],
            'webpack' => [
                "dependencies" => [
                    "@babel/core" => "^7.3.4",
                    "@babel/preset-env" => "^7.3.4",
                    "bootstrap-sass" => "^3.3.7",
                    "browser-sync" => "^2.24.4",
                    "browser-sync-webpack-plugin" => "^2.2.2",
                    "copy-webpack-plugin" => "^4.6.0",
                    "laravel-localization-loader" => "^1.0.5",
                    "webpack-shell-plugin" => "^0.5.0",
                    "import-file" => "^1.4.0",
                ]
            ]
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

        $path = base_path('Modules/');

        if (!is_dir($path)) {
            $this->info('--- CREATING MODULES DIRECTORY  ----');
            $this->info('Creating directory ' . $path);
            if(mkdir($path, 0775)) {
                $this->info('... DONE');
            }
        }

        // Get package to install
        $package = $this->getPackageByName($this->package);




        if(!$package) {
            $this->error("$package no found");
            return false;
        }

        if(is_dir($path . $package["directory"])) {
            $this->error('Module '.$package["name"].' is already installed');
            return false;
        }

        $this->installComposerDependencies($package);
        $this->installPackage($package);
        $this->installNpmDependencies($package);

        // Dump autoload
        $this->info('--- (2/4) DUMP AUTOLOAD ----');
        exec('composer dumpautoload');
        $this->info('... DONE');

        // Migrate module DB
        $this->info('--- (3/3) MIGRATE MODULE DB ----');
        exec('php artisan module:migrate ' . $package["directory"]);
        $this->info('... DONE');
    }


    private function installPackage($package)
    {
        // Clone Module directory
        $this->info('--- (1/4) CLONING REPOSITORY ----');
        exec("git clone ".$package["url"]."  Modules/" . $package["directory"]);
        $this->info('... DONE');
    }

    private function installNpmDependencies($package)
    {

        if(isset($package["webpack"]["dependencies"])) {
            $this->info('--- INSTALLING NPM DEPENDENCIES ----');
            foreach($package["webpack"]["dependencies"] as $name => $version) {
                $this->info("  -> " . $name);
                exec("npm install " . $name . "@" . $version);
            }
            $this->info('=> DONE');
        }
    }


    private function installComposerDependencies($package)
    {
        if(isset($package["vendors"])) {
            $this->info('--- INSTALLING COMPOSER VENDORS ----');
            foreach($package["vendors"] as $vendor) {
                $this->info("  -> " . $vendor["package"]);
                exec("composer require " . $vendor["package"] . " " . $vendor["version"]);
            }
            $this->info('=> DONE');
        }
    }


    private function getPackageByName($name)
    {
        return collect($this->packages)
            ->filter(function($item) use ($name) {
                return $item["name"] === $name ? true : false;
            })->first();
    }
}
