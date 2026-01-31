<?php

namespace Massaal\Dusha\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearCommand extends Command
{
    public $signature = "dusha:clear";

    public $description = "Clear compiled assets";

    public function handle(): int
    {
        $output_path = public_path(config("dusha.output_path"));

        if (File::exists($output_path)) {
            File::deleteDirectory($output_path);
            $this->info("Cleared compiled assets");
            return self::SUCCESS;
        }

        $this->comment("Nothing to clear");

        return self::SUCCESS;
    }
}
