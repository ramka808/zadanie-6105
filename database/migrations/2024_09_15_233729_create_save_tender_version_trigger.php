<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            -- Удаляем триггер, если он существует
            DROP TRIGGER IF EXISTS trigger_save_tender_version ON tenders;

            -- Удаляем функцию, если она существует
            DROP FUNCTION IF EXISTS save_tender_version();

            CREATE OR REPLACE FUNCTION save_tender_version() RETURNS TRIGGER AS $$
            BEGIN
                -- Сохранение текущей версии тендера в таблицу tender_versions перед обновлением
                INSERT INTO tender_versions (tender_id, name, description, status, organization_id, creator_username, service_type, version, created_at)
                SELECT OLD.id, OLD.name, OLD.description, OLD.status, OLD.organization_id, OLD.creator_username, OLD.service_type, OLD.version, OLD.created_at;
                -- Увеличиваем версию на 1 при каждом обновлении
                NEW.version := OLD.version + 1;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trigger_save_tender_version
            BEFORE UPDATE ON tenders
            FOR EACH ROW
            EXECUTE FUNCTION save_tender_version();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('
            DROP TRIGGER IF EXISTS trigger_save_tender_version ON tenders;
            DROP FUNCTION IF EXISTS save_tender_version();
        ');
    }
};