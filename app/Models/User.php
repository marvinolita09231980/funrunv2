<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles;
 

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'office',
        'cellphone_number',
        'administered_category',
        'assigned_category',
        'created_by',
        'email',
        'username',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->username)) {
                $user->username = self::usernameFromName($user->name);
            }
        });

        static::deleting(function (User $user): void {
            Subcategory::query()
                ->where('username', $user->username)
                ->update(['username' => null]);
        });
    }

    public static function usernameFromName(?string $name): string
    {
        return Str::lower((string) preg_replace(
            '/[^a-z0-9]+/i',
            '',
            Str::ascii(trim((string) $name)),
        ));
    }

    public function isAdministrativeOfficer(): bool
    {
        return $this->hasAnyRole(['administrative officer', 'ao']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['superadmin', 'super_admin']);
    }

    public function isAdminWithoutSuperAdmin(): bool
    {
        return $this->hasRole('admin') && ! $this->isSuperAdmin();
    }

    public function administeredCategoryAllocations(): BelongsToMany
    {
        return $this->belongsToMany(
            CategoryAllocation::class,
            'category_allocation_user',
            'user_id',
            'category_allocation_id',
        )->withTimestamps();
    }

    public function administeredCategoryNames(): array
    {
        return $this->administeredCategoryAllocations()
            ->orderBy('categoryDescription')
            ->pluck('categoryDescription')
            ->all();
    }

    public function administersCategory(?string $category): bool
    {
        return ! $this->isAdministrativeOfficer()
            || (
                filled($category)
                && $this->administeredCategoryAllocations()
                    ->where('categoryDescription', $category)
                    ->exists()
            );
    }

    public function canManageParticipant(Participant $participant): bool
    {
        if ($this->isAdministrativeOfficer()) {
            return $this->administersCategory($participant->categoryDescription);
        }

        if ($this->hasAnyRole(['admin', 'superadmin', 'super_admin'])) {
            return true;
        }

        if ($this->hasRole('focal user')) {
            return $this->canManageParticipantData(
                $participant->categoryDescription,
                $participant->subDescription,
            );
        }

        return $participant->created_by === $this->username;
    }

    public function canManageParticipantData(
        ?string $category,
        ?string $subcategory,
    ): bool {
        if (! $this->hasRole('focal user') || blank($category)) {
            return false;
        }

        return Subcategory::query()
            ->where('username', $this->username)
            ->where('categoryDescription', $category)
            ->when(
                $category !== 'OPEN CATEGORY',
                fn ($query) => $query->where('subDescription', $subcategory),
            )
            ->exists();
    }

    public function isFocalUserForCategory(?string $category): bool
    {
        return $this->hasRole('focal user')
            && filled($category)
            && $this->assigned_category === $category;
    }

    public function canManageCreatedFocalUser(User $user): bool
    {
        return $this->isAdministrativeOfficer()
            && $user->hasRole('focal user')
            && filled($this->username)
            && $user->created_by === $this->username
            && $this->administersCategory($user->assigned_category);
    }

    public function canImpersonateFocalUser(User $user): bool
    {
        return $this->isAdministrativeOfficer()
            && $user->hasRole('focal user')
            && $this->administersCategory($user->assigned_category);
    }

    public function canImpersonate(): bool
    {
        return $this->hasAnyRole([
            'admin',
            'superadmin',
            'super_admin',
            'administrative officer',
            'ao',
        ]);
    }

    public function canBeImpersonated(): bool
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            return false;
        }

        if ($this->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        return $actor->hasAnyRole(['admin', 'superadmin', 'super_admin'])
            || $actor->canImpersonateFocalUser($this);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Allow only superadmins or users with 'access_admin_panel' permission
        return $this->hasRole('superadmin') || $this->can('access_admin_panel');
    }
}
