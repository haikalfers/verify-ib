<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'trashed_pdf_path')) {
                $table->string('trashed_pdf_path')->nullable()->after('generated_pdf_path');
            }

            if (! Schema::hasColumn('certificates', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('trashed_pdf_path');
            }

            if (! Schema::hasColumn('certificates', 'name_search')) {
                $table->string('name_search')->nullable()->after('name');
            }
        });

        if (Schema::hasColumn('certificates', 'name_search')) {
            DB::table('certificates')
                ->whereNull('name_search')
                ->update([
                    'name_search' => DB::raw("TRIM(SUBSTRING_INDEX(name, ',', 1))"),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'name_search')) {
                $table->dropColumn('name_search');
            }

            if (Schema::hasColumn('certificates', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }

            if (Schema::hasColumn('certificates', 'trashed_pdf_path')) {
                $table->dropColumn('trashed_pdf_path');
            }
        });
    }
};
