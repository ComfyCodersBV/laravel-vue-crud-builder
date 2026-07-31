<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class ProbeArticle extends Model
{
    protected $table = 'probe_articles';

    protected $guarded = [];
}
