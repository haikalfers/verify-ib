<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'extra_pdf_path')) {
                $table->string('extra_pdf_path')->nullable()->after('unit_kompetensi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'extra_pdf_path')) {
                $table->dropColumn('extra_pdf_path');
            }
        });
    }
};
