<?php

namespace Massaal\Dusha\Commands;

use Illuminate\Console\Command;

class DushaCommand extends Command
{
    public $signature = 'dusha';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
