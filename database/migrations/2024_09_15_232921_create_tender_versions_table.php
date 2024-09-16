<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tender_versions')) {
            // Убедимся, что расширение uuid-ossp установлено
            DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

            Schema::create('tender_versions', function (Blueprint $table) {
                $table->uuid('version_id')->primary()->default(DB::raw('uuid_generate_v4()'));
                $table->uuid('tender_id');
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('status', 50);
                $table->uuid('organization_id');
                $table->string('creator_username', 50);
                $table->string('service_type', 100);
                $table->integer('version');
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('tender_id')
                      ->references('id')
                      ->on('tenders')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tender_versions');
    }
};
