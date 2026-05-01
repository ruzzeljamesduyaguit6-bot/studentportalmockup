<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'birthday',
        'contact',
        'designation',
        'department',
        'course',
        'subject',
        'year_level',
        'email',
        'email_verified_at',
        'profile_photo_url',
        'user_type',
        'user_code',
        'password',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if user is regular user
     *
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->user_type === 'student';
    }

    /**
     * Hook model events for role-based code generation.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->assignUserCodeIfNeeded();
        });

        static::updating(function (User $user) {
            if ($user->isDirty('user_type')) {
                $user->user_code = static::generateUserCode($user->user_type);
            }
        });
    }

    /**
     * Assign a generated code when applicable.
     */
    private function assignUserCodeIfNeeded(): void
    {
        if (in_array($this->user_type, ['student', 'professor'], true) && empty($this->user_code)) {
            $this->user_code = static::generateUserCode($this->user_type);
        }
    }

    /**
     * Generate IDs in format: SYYYY 00001 or PYYYY 00001.
     */
    public static function generateUserCode(string $role): ?string
    {
        $prefixMap = [
            'student' => 'S',
            'professor' => 'P',
        ];

        if (!isset($prefixMap[$role])) {
            return null;
        }

        $year = (string) now()->year;
        $prefix = $prefixMap[$role];
        $base = $prefix . $year . ' ';

        $latest = static::query()
            ->where('user_type', $role)
            ->whereNotNull('user_code')
            ->where('user_code', 'like', $base . '%')
            ->orderByDesc('user_code')
            ->value('user_code');

        $lastNumber = 0;
        if ($latest && preg_match('/^' . preg_quote($prefix . $year, '/') . '\s(\d{5})$/', $latest, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $nextNumber = $lastNumber + 1;

        return sprintf('%s%s %05d', $prefix, $year, $nextNumber);
    }
}
