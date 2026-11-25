<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'owner_id', 'archived_at'])]
class Team extends Model
{
    use HasFactory;

    protected $table = 'team';

    protected $primaryKey = 'team_id';

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'owner_id', 'account_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'team_member', 'team_id', 'account_id')
            ->withPivot('role', 'joined_at');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'team_id', 'team_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'team_id', 'team_id');
    }
}
