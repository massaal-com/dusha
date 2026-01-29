<?php

namespace Massaal\Dusha;

use Massaal\Dusha\Commands\ClearCommand;
use Massaal\Dusha\Commands\CompileCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DushaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name("dusha")
            ->hasConfigFile()
            ->hasCommands([CompileCommand::class, ClearCommand::class]);
    }

    public function packageRegistered()
    {
        require_once __DIR__ . "/helpers.php";
    }
}
