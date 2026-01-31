<?php

namespace Massaal\Dusha\Commands;

use Illuminate\Console\Command;
use Massaal\Dusha\AssetCompiler;

class CompileCommand extends Command
{
    public $signature = "dusha:compile";

    public $description = "Compile and digest assets";

    public function handle(AssetCompiler $compiler): int
    {
        $count = $compiler->compile();

        $this->info("Compiled {$count} assets");

        return self::SUCCESS;
    }
}
