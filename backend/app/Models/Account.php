<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'avatar_url', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
class Account extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'account';

    protected $primaryKey = 'account_id';

    /**
     * @var array<int, string>
     */
    protected $appends = ['id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::lower(trim($value)),
        );
    }

    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->account_id,
        );
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_member', 'account_id', 'team_id')
            ->withPivot('role', 'joined_at');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by', 'account_id');
    }

    public function assignments(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignee', 'account_id', 'task_id')
            ->withPivot('assigned_at');
    }

    public function assignedSubtasks(): HasMany
    {
        return $this->hasMany(Subtask::class, 'assignee_id', 'account_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
