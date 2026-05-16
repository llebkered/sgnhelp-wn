<?php

namespace DerekBell\Games\Tests\Feature;

use Backend\Models\User;
use Backend\Models\UserRole;
use System\Tests\Bootstrap\PluginTestCase;

class PermissionsTest extends PluginTestCase
{
    use \Backend\Tests\Concerns\InteractsWithAuthentication;

    public function setUp(): void
    {
        parent::setUp();
        $this->runPluginRefreshCommand('DerekBell.Games');
    }

    public function testPermissionsAreRegistered()
    {
        $plugin = \System\Classes\PluginManager::instance()->findByIdentifier('DerekBell.Games');
        $permissions = $plugin->registerPermissions();

        $this->assertArrayHasKey('derekbell.games.access_games', $permissions);
        $this->assertArrayHasKey('derekbell.games.access_episodes', $permissions);
        $this->assertArrayHasKey('derekbell.games.access_levels', $permissions);
    }

    public function testPermissionsHaveCorrectStructure()
    {
        $plugin = \System\Classes\PluginManager::instance()->findByIdentifier('DerekBell.Games');
        $permissions = $plugin->registerPermissions();

        foreach ($permissions as $permission) {
            $this->assertArrayHasKey('tab', $permission);
            $this->assertArrayHasKey('label', $permission);
            $this->assertArrayHasKey('comment', $permission);
            $this->assertEquals('Games', $permission['tab']);
        }
    }

    public function testUserWithGamesPermission()
    {
        $role = UserRole::create([
            'name' => 'Game Manager',
            'permissions' => ['derekbell.games.access_games'],
        ]);

        $user = User::create([
            'login' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role_id' => $role->id,
        ]);

        $this->assertTrue($user->hasAccess('derekbell.games.access_games'));
        $this->assertFalse($user->hasAccess('derekbell.games.access_episodes'));
        $this->assertFalse($user->hasAccess('derekbell.games.access_levels'));
    }

    public function testUserWithMultiplePermissions()
    {
        $role = UserRole::create([
            'name' => 'Content Manager',
            'permissions' => [
                'derekbell.games.access_games',
                'derekbell.games.access_episodes',
                'derekbell.games.access_levels',
            ],
        ]);

        $user = User::create([
            'login' => 'contentmanager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'Content',
            'last_name' => 'Manager',
            'role_id' => $role->id,
        ]);

        $this->assertTrue($user->hasAccess('derekbell.games.access_games'));
        $this->assertTrue($user->hasAccess('derekbell.games.access_episodes'));
        $this->assertTrue($user->hasAccess('derekbell.games.access_levels'));
    }

    public function testUserWithoutPermissions()
    {
        $role = UserRole::create([
            'name' => 'Limited User',
            'permissions' => [],
        ]);

        $user = User::create([
            'login' => 'limiteduser',
            'email' => 'limited@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'Limited',
            'last_name' => 'User',
            'role_id' => $role->id,
        ]);

        $this->assertFalse($user->hasAccess('derekbell.games.access_games'));
        $this->assertFalse($user->hasAccess('derekbell.games.access_episodes'));
        $this->assertFalse($user->hasAccess('derekbell.games.access_levels'));
    }
}
