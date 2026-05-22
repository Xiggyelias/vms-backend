<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    private function uriFor(string $name): string
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route [$name] was not found.");
        return '/' . ltrim($route->uri(), '/');
    }

    public function test_versioned_api_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('api.v1.users.index'));
        $this->assertTrue(Route::has('api.v1.vehicles.search'));
        $this->assertTrue(Route::has('api.v1.notifications.read'));
        $this->assertTrue(Route::has('api.v1.drivers.store'));
    }

    public function test_legacy_compatibility_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('users.update'));
        $this->assertTrue(Route::has('users.destroy.post'));
        $this->assertTrue(Route::has('notifications.read.payload'));
        $this->assertTrue(Route::has('vehicles.scan'));
        $this->assertTrue(Route::has('vehicles.status.admin.update'));
        $this->assertTrue(Route::has('drafts.save'));
        $this->assertTrue(Route::has('registration.submit'));
        $this->assertTrue(Route::has('registration.submit.vehicle-form'));
        $this->assertTrue(Route::has('auth.google.token'));
        $this->assertTrue(Route::has('auth.google.finalize-role'));
    }

    public function test_mutating_routes_keep_throttle_protection(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.notifications.read');
        $this->assertNotNull($route);
        $this->assertContains('throttle:mutations', $route->middleware());
    }

    public function test_auth_routes_are_registered_for_contract_freeze(): void
    {
        $this->assertTrue(Route::has('auth.login.post'));
        $this->assertTrue(Route::has('auth.admin.login.post'));
        $this->assertTrue(Route::has('auth.logout'));
    }

    public function test_admin_api_route_uses_auth_admin_middleware(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.users.index');
        $this->assertNotNull($route);
        $this->assertContains('auth.admin', $route->middleware());
    }

    public function test_registration_submit_route_uses_auth_user_middleware(): void
    {
        $route = Route::getRoutes()->getByName('registration.submit');
        $this->assertNotNull($route);
        $this->assertContains('auth.user', $route->middleware());
    }

    public function test_google_auth_route_uses_web_middleware_for_csrf(): void
    {
        $route = Route::getRoutes()->getByName('auth.google.token');
        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
    }

    public function test_finalize_role_route_uses_web_middleware_for_csrf(): void
    {
        $route = Route::getRoutes()->getByName('auth.google.finalize-role');
        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
    }
}
