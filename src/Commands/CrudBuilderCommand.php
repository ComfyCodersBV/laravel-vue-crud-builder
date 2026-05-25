<?php

namespace TranquilTools\CrudBuilder\Commands;

use Illuminate\Console\Command;

class CrudBuilderCommand extends Command
{
    public $signature = 'laravel-vue-crud-builder';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
