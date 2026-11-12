<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FocalLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_focal_user_login_does_not_replay_an_unauthorized_users_url(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $focalUser = User::factory()->create();
        $focalUser->assignRole(Role::findOrCreate('focal user', 'web'));
        $this->actingAs($focalUser);

        $usersUrl = UserResource::getUrl('index');
        session()->put('url.intended', $usersUrl);

        $loginResponse = app(LoginResponseContract::class);
        $response = $loginResponse->toResponse(request());

        $this->assertInstanceOf(LoginResponse::class, $loginResponse);
        $this->assertNotSame($usersUrl, $response->getTargetUrl());
        $this->assertSame(
            Filament::getPanel('admin')->getRedirectUrl(),
            $response->getTargetUrl(),
        );
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_focal_user_still_cannot_open_users_resource_directly(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $focalUser = User::factory()->create();
        $focalUser->assignRole(Role::findOrCreate('focal user', 'web'));

        $this->actingAs($focalUser)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }
}
