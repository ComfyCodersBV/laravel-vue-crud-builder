<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Installer;

use TranquilTools\VueFormBuilder\Support\Installer\TsConfigPatcher as MovedTsConfigPatcher;

class_alias(MovedTsConfigPatcher::class, __NAMESPACE__.'\TsConfigPatcher');
