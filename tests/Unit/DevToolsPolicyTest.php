<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class DevToolsPolicyTest extends TestCase
{
    public function test_dev_tools_enabled_in_local_environment(): void
    {
        $this->app['env'] = 'local';
        config(['app.dev_tools' => true]);

        $this->assertTrue(User::isDevToolsEnabled());
    }

    public function test_dev_tools_strictly_disabled_in_production_environment(): void
    {
        $this->app['env'] = 'production';
        config(['app.dev_tools' => true]);

        $this->assertFalse(User::isDevToolsEnabled());
    }

    public function test_dev_tools_disabled_when_explicitly_set_false(): void
    {
        $this->app['env'] = 'local';
        config(['app.dev_tools' => false]);

        $this->assertFalse(User::isDevToolsEnabled());
    }
}

