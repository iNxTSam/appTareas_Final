<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('category', 80)->nullable()->after('description');
            $table->string('responsible_id', 120)->nullable()->after('team');
            $table->string('responsible_name', 120)->nullable()->after('responsible_id');
            $table->json('subtasks')->nullable()->after('responsible_name');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn(['category', 'responsible_id', 'responsible_name', 'subtasks']);
        });
    }
};
