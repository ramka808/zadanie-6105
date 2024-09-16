<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем расширение uuid-ossp, если оно еще не существует
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        // Проверяем существование типа tender_service_type перед его созданием
        if (!$this->enumExists('tender_service_type')) {
            DB::statement("CREATE TYPE tender_service_type AS ENUM ('Construction', 'Delivery', 'Manufacture')");
        }

        // Проверяем существование типа tender_status перед его созданием
        if (!$this->enumExists('tender_status')) {
            DB::statement("CREATE TYPE tender_status AS ENUM ('Created', 'Published', 'Closed')");
        }

        // Проверяем существование таблицы tenders перед ее созданием
        if (!Schema::hasTable('tenders')) {
            Schema::create('tenders', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
                $table->string('name', 100);
                $table->string('description', 500)->nullable();
                $table->enum('service_type', ['Construction', 'Delivery', 'Manufacture']);
                $table->enum('status', ['Created', 'Published', 'Closed']);
                $table->uuid('organization_id');
                $table->string('creator_username', 50);
                $table->integer('version')->default(1);
                $table->timestampTz('created_at')->useCurrent();

                $table->foreign('organization_id')->references('id')->on('organization')->onDelete('cascade');
                $table->foreign('creator_username')->references('username')->on('employee')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenders');
        
        // Удаляем ENUM типы при откате миграции
        DB::statement("DROP TYPE IF EXISTS tender_service_type");
        DB::statement("DROP TYPE IF EXISTS tender_status");
    }

    /**
     * Проверяет существование ENUM типа в PostgreSQL.
     */
    private function enumExists($enumName): bool
    {
        $result = DB::select("SELECT 1 FROM pg_type WHERE typname = ?", [$enumName]);
        return !empty($result);
    }
};
