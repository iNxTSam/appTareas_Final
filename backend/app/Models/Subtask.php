<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'title', 'is_done', 'position', 'assignee_id'])]
class Subtask extends Model
{
    use HasFactory;

    protected $table = 'subtask';

    protected $primaryKey = 'subtask_id';

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'assignee_id', 'account_id');
    }
}
