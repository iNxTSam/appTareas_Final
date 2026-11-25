<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account', function (Blueprint $table): void {
            $table->bigIncrements('account_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('avatar_url')->nullable();
            $table->string('timezone', 64)->default('America/Bogota');
            $table->timestamps();
        });

        Schema::create('team', function (Blueprint $table): void {
            $table->bigIncrements('team_id');
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->foreignId('owner_id')->constrained('account', 'account_id')->restrictOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('team_member', function (Blueprint $table): void {
            $table->foreignId('team_id')->constrained('team', 'team_id')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('account', 'account_id')->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->primary(['team_id', 'account_id']);
        });

        Schema::create('project', function (Blueprint $table): void {
            $table->bigIncrements('project_id');
            $table->foreignId('team_id')->constrained('team', 'team_id')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('color', 7)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'name']);
            $table->unique(['project_id', 'team_id']);
        });

        Schema::create('task', function (Blueprint $table): void {
            $table->bigIncrements('task_id');
            $table->foreignId('team_id')->nullable()->constrained('team', 'team_id')->cascadeOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreignId('created_by')->constrained('account', 'account_id')->restrictOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pendiente');
            $table->string('priority', 20)->default('media');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamp('completed_at')->nullable();
            $table->integer('position')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('task_assignee', function (Blueprint $table): void {
            $table->foreignId('task_id')->constrained('task', 'task_id')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('account', 'account_id')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->primary(['task_id', 'account_id']);
        });

        Schema::create('subtask', function (Blueprint $table): void {
            $table->bigIncrements('subtask_id');
            $table->foreignId('task_id')->constrained('task', 'task_id')->cascadeOnDelete();
            $table->string('title', 160);
            $table->boolean('is_done')->default(false);
            $table->integer('position')->default(0);
            $table->foreignId('assignee_id')->nullable()->constrained('account', 'account_id')->nullOnDelete();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE team_member ADD CONSTRAINT ck_team_member_role CHECK (role IN ('owner', 'admin', 'member'))");
            DB::statement("ALTER TABLE task ADD CONSTRAINT ck_task_status CHECK (status IN ('pendiente', 'en_progreso', 'completada'))");
            DB::statement("ALTER TABLE task ADD CONSTRAINT ck_task_priority CHECK (priority IN ('baja', 'media', 'alta'))");
            DB::statement('ALTER TABLE task ADD CONSTRAINT ck_task_dates CHECK (end_date >= start_date)');
            DB::statement("ALTER TABLE task ADD CONSTRAINT ck_task_completed CHECK ((status = 'completada') = (completed_at IS NOT NULL))");
            DB::statement('ALTER TABLE task ADD CONSTRAINT ck_task_personal CHECK (project_id IS NULL OR team_id IS NOT NULL)');
            DB::statement('ALTER TABLE task ADD CONSTRAINT fk_task_project_team FOREIGN KEY (project_id, team_id) REFERENCES project (project_id, team_id) ON DELETE RESTRICT');

            DB::statement('CREATE INDEX ix_team_member_account ON team_member (account_id)');
            DB::statement('CREATE INDEX ix_project_team ON project (team_id)');
            DB::statement('CREATE INDEX ix_task_team_status ON task (team_id, status) WHERE deleted_at IS NULL');
            DB::statement('CREATE INDEX ix_task_calendar ON task (team_id, start_date, end_date) WHERE deleted_at IS NULL');
            DB::statement('CREATE INDEX ix_task_personal_calendar ON task (created_by, start_date, end_date) WHERE team_id IS NULL');
            DB::statement('CREATE INDEX ix_task_project ON task (project_id) WHERE project_id IS NOT NULL');
            DB::statement('CREATE INDEX ix_task_created_by ON task (created_by)');
            DB::statement('CREATE INDEX ix_task_assignee_account ON task_assignee (account_id)');
            DB::statement('CREATE INDEX ix_subtask_task_position ON subtask (task_id, position)');
        } else {
            Schema::table('team_member', fn (Blueprint $table) => $table->index('account_id'));
            Schema::table('project', fn (Blueprint $table) => $table->index('team_id'));
            Schema::table('task', function (Blueprint $table): void {
                $table->index(['team_id', 'status']);
                $table->index(['team_id', 'start_date', 'end_date']);
                $table->index(['created_by', 'start_date', 'end_date']);
                $table->index('project_id');
                $table->index('created_by');
            });
            Schema::table('task_assignee', fn (Blueprint $table) => $table->index('account_id'));
            Schema::table('subtask', fn (Blueprint $table) => $table->index(['task_id', 'position']));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subtask');
        Schema::dropIfExists('task_assignee');
        Schema::dropIfExists('task');
        Schema::dropIfExists('project');
        Schema::dropIfExists('team_member');
        Schema::dropIfExists('team');
        Schema::dropIfExists('account');
    }
};
