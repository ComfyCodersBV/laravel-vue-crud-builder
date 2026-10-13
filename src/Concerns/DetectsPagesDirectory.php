<?php

declare(strict_types=1);

namespace TranquilTools\CrudBuilder\Concerns;

use TranquilTools\VueFormBuilder\Support\Installer\DetectsPagesDirectory as MovedDetectsPagesDirectory;

class_alias(MovedDetectsPagesDirectory::class, __NAMESPACE__.'\DetectsPagesDirectory');
