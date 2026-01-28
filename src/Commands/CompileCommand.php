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
        $compiler->compile();

        $this->comment("All done");

        return self::SUCCESS;
    }
}
