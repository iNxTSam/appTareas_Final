<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateAccounts();
        $this->migrateTokens();
        $this->migrateTasks();

        Schema::dropIfExists('tasks');
        Schema::dropIfExists('users');
    }

    public function down(): void
    {
        // La migración de datos es irreversible: las tablas legacy se eliminan.
    }

    private function migrateAccounts(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('account')->insertUsing(
            ['account_id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'],
            DB::table('users')->select('id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'),
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('account', 'account_id'), GREATEST((SELECT COALESCE(MAX(account_id), 0) FROM account), 1))");
        }
    }

    private function migrateTokens(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\\Models\\User')
            ->update(['tokenable_type' => 'App\\Models\\Account']);
    }

    private function migrateTasks(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        $columns = Schema::getColumnListing('tasks');
        $has = static fn (string $column): bool => in_array($column, $columns, true);
        $teams = [];
        $projects = [];

        foreach (DB::table('tasks')->orderBy('id')->get() as $row) {
            $teamId = null;
            $projectId = null;
            $teamName = $has('team') ? trim((string) $row->team) : '';

            if ($teamName !== '') {
                $teamKey = Str::lower($teamName).'|'.$row->user_id;

                if (! isset($teams[$teamKey])) {
                    $teams[$teamKey] = DB::table('team')->insertGetId([
                        'name' => $teamName,
                        'slug' => Str::slug($teamName).'-'.$row->user_id,
                        'owner_id' => $row->user_id,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ], 'team_id');

                    DB::table('team_member')->insertOrIgnore([
                        'team_id' => $teams[$teamKey],
                        'account_id' => $row->user_id,
                        'role' => 'owner',
                        'joined_at' => $row->created_at ?? now(),
                    ]);
                }

                $teamId = $teams[$teamKey];
                $projectName = $has('category') ? trim((string) $row->category) : '';
                $projectName = $projectName !== '' ? $projectName : 'General';
                $projectKey = $teamId.'|'.Str::lower($projectName);

                if (! isset($projects[$projectKey])) {
                    $projects[$projectKey] = DB::table('project')->insertGetId([
                        'team_id' => $teamId,
                        'name' => $projectName,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ], 'project_id');
                }

                $projectId = $projects[$projectKey];
            }

            $status = in_array($row->status, ['pendiente', 'en_progreso', 'completada'], true)
                ? $row->status
                : 'pendiente';
            $priority = $row->priority === 'importante' ? 'alta' : 'media';
            $completedAt = $status === 'completada'
                ? ($row->updated_at ?? $row->created_at ?? now())
                : null;

            DB::table('task')->insert([
                'task_id' => $row->id,
                'team_id' => $teamId,
                'project_id' => $projectId,
                'created_by' => $row->user_id,
                'title' => $row->name,
                'description' => $row->description,
                'status' => $status,
                'priority' => $priority,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'completed_at' => $completedAt,
                'position' => 0,
                'deleted_at' => null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);

            if ($has('subtasks')) {
                $subtasks = json_decode((string) $row->subtasks, true);

                if (is_array($subtasks)) {
                    foreach (array_values($subtasks) as $index => $subtask) {
                        DB::table('subtask')->insert([
                            'task_id' => $row->id,
                            'title' => (string) ($subtask['text'] ?? 'Subtarea'),
                            'is_done' => (bool) ($subtask['done'] ?? false),
                            'position' => $index + 1,
                            'assignee_id' => null,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ]);
                    }
                }
            }

            if ($has('responsible_name') && filled($row->responsible_name)) {
                $account = DB::table('account')
                    ->whereRaw('lower(name) = ?', [Str::lower((string) $row->responsible_name)])
                    ->first();

                if ($account !== null) {
                    DB::table('task_assignee')->insertOrIgnore([
                        'task_id' => $row->id,
                        'account_id' => $account->account_id,
                        'assigned_at' => $row->created_at ?? now(),
                    ]);
                }
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('task', 'task_id'), GREATEST((SELECT COALESCE(MAX(task_id), 0) FROM task), 1))");
        }
    }
};
