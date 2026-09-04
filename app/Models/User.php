<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasRole($roleSlug)
    {
        if ($this->role_id) {
            if (! $this->relationLoaded('roleRelation')) {
                $this->load('roleRelation');
            }
            if ($this->roleRelation && $this->roleRelation->slug === $roleSlug) {
                return true;
            }
        }

        $roleValue = $this->attributes['role'] ?? null;
        if ($roleValue && is_string($roleValue) && $roleValue === $roleSlug) {
            return true;
        }

        return false;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
}
