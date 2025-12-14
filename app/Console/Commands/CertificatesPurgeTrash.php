<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CertificatesPurgeTrash extends Command
{
    protected $signature = 'certificates:purge-trash';

    protected $description = 'Hapus permanen sertifikat di Trash yang sudah lebih dari 30 hari (hapus file PDF dan row database).';

    public function handle(): int
    {
        $limit = Carbon::now()->subDays(30);

        // Ambil sertifikat yang sudah soft delete dan lebih lama dari 30 hari
        $certificates = DB::table('certificates')
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', $limit)
            ->get();

        $deletedCount = 0;

        foreach ($certificates as $certificate) {
            // Hapus file di trash jika masih ada
            if (! empty($certificate->trashed_pdf_path)) {
                $relativePath = ltrim($certificate->trashed_pdf_path, '/');

                $candidates = [];
                $candidates[] = base_path($relativePath);
                $candidates[] = public_path($relativePath);

                foreach ($candidates as $path) {
                    if ($path && file_exists($path)) {
                        @unlink($path);
                    }
                }
            }

            DB::table('certificates')->where('id', $certificate->id)->delete();
            $deletedCount++;
        }

        $this->info('Trash purge selesai dijalankan. Total dihapus permanen: ' . $deletedCount);

        return Command::SUCCESS;
    }
}
