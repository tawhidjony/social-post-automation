<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('name')->default('Workspace');
            $table->string('slug')->default('');
        });

        foreach (DB::table('workspaces')->get() as $workspace) {
            DB::table('workspaces')->where('id', $workspace->id)->update([
                'name' => 'Workspace '.$workspace->id,
                'slug' => 'workspace-'.$workspace->id,
            ]);
        }

        Schema::table('workspaces', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['name', 'slug']);
        });
    }
};
