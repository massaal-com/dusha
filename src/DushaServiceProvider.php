<?php

namespace Massaal\Dusha;

use Massaal\Dusha\Commands\CompileCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DushaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name("dusha")
            ->hasConfigFile()
            ->hasCommand(CompileCommand::class);
    }

    public function packageRegistered()
    {
        require_once __DIR__ . "/helpers.php";
    }
}
