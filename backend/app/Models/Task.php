<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'team_id',
    'project_id',
    'created_by',
    'title',
    'description',
    'status',
    'priority',
    'start_date',
    'end_date',
    'completed_at',
    'position',
])]
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'task';

    protected $primaryKey = 'task_id';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pendiente',
        'priority' => 'media',
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'position' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by', 'account_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'task_assignee', 'task_id', 'account_id')
            ->withPivot('assigned_at');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class, 'task_id', 'task_id')->orderBy('position');
    }

    public function scopeVisibleTo(Builder $query, Account $account): Builder
    {
        $teamIds = $account->teams()->pluck('team.team_id');

        return $query->where(function (Builder $query) use ($account, $teamIds): void {
            $query->whereIn('team_id', $teamIds)
                ->orWhere(function (Builder $query) use ($account): void {
                    $query->whereNull('team_id')->where('created_by', $account->account_id);
                });
        });
    }
}
