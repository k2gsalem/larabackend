<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Bootstrap the application for testing.
     */
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = require \Illuminate\Foundation\Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}

