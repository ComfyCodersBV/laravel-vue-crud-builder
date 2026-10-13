<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Installer;

use TranquilTools\VueFormBuilder\Support\Installer\ViteConfigPatcher as MovedViteConfigPatcher;

class_alias(MovedViteConfigPatcher::class, __NAMESPACE__.'\ViteConfigPatcher');
