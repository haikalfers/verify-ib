<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('certificate_template_variants')) {
            Schema::create('certificate_template_variants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id')->index();
                $table->string('variant_name');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('file_path');
                $table->string('file_type')->default('image'); // pdf or image
                $table->longText('coordinates')->nullable();
                $table->timestamps();

                $table->index(['template_id', 'variant_name']);
            });
        }

        // Add variant_id to certificates
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('template_id');
                $table->index('variant_id');
            }
        });

        // Backfill default variants from existing certificate_templates
        $templates = DB::table('certificate_templates')->get();
        foreach ($templates as $tpl) {
            $exists = DB::table('certificate_template_variants')
                ->where('template_id', $tpl->id)
                ->exists();
            if (!$exists) {
                DB::table('certificate_template_variants')->insert([
                    'template_id' => $tpl->id,
                    'variant_name' => 'Default',
                    'is_default' => 1,
                    'is_active' => (int) ($tpl->is_active ?? 1),
                    'file_path' => $tpl->file_path,
                    'file_type' => $tpl->file_type ?? 'image',
                    'coordinates' => $tpl->coordinates,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'variant_id')) {
                $table->dropIndex(['variant_id']);
                $table->dropColumn('variant_id');
            }
        });

        Schema::dropIfExists('certificate_template_variants');
    }
};
