<?php

namespace Massaal\Dusha;

use Illuminate\Support\Facades\Blade;
use Massaal\Dusha\Commands\ClearCommand;
use Massaal\Dusha\Commands\CompileCommand;
use Massaal\Dusha\Components\Css;
use Massaal\Dusha\Components\Js;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DushaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name("dusha")
            ->hasConfigFile()
            ->hasCommands([CompileCommand::class, ClearCommand::class])
            ->hasViewComponents("dusha", Css::class, Js::class);

        if (app()->environment("local")) {
            $package->hasRoute("development");
        }
    }

    public function packageRegistered()
    {
        require_once __DIR__ . "/helpers.php";

        $this->app->singleton(AssetCompiler::class);
    }
}
