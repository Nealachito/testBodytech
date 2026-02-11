<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FileUpload;
use Illuminate\Support\Facades\Http;
use League\Csv\Reader;

class ProcessEmailFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected int $fileUploadId;
    protected string $path;

    /**
     * Create a new job instance.
     */
    public function __construct(int $fileUploadId, string $path)
    {
        $this->fileUploadId = $fileUploadId;
        $this->path = $path;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
{
    $fileUpload = FileUpload::findOrFail($this->fileUploadId);

    $csvPath = storage_path('app/' . $this->path);

    if (!file_exists($csvPath)) {
        $fileUpload->update(['status' => 'failed']);
        throw new \Exception("Archivo no encontrado: $csvPath");
    }

    $reader = Reader::createFromPath($csvPath, 'r');
    $reader->setHeaderOffset(0);
    $records = $reader->getRecords();

    $total = 0;
    $valid = 0;
    $invalid = 0;

    $chunkSize = 10; // Más pequeño = progreso más visible
    $batch = [];

    foreach ($records as $row) {
        $email = trim($row['email'] ?? '');
        if ($email === '') continue;

        $total++;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid++;
            $batch[] = $email;
        } else {
            $invalid++;
        }

        if (count($batch) >= $chunkSize) {

            $this->processBatch($batch);

            // 🔥 ACTUALIZAMOS PROGRESO
            $fileUpload->update([
                'total_emails' => $total,
                'valid_emails' => $valid,
                'invalid_emails' => $invalid,
            ]);

            $batch = [];
        }
    }

    if (!empty($batch)) {
        $this->processBatch($batch);
    }

    // 🔥 Última actualización final
    $fileUpload->update([
        'total_emails' => $total,
        'valid_emails' => $valid,
        'invalid_emails' => $invalid,
        'status' => 'completed'
    ]);
}


    /**
     * Procesa un batch de emails en paralelo
     */
    protected function processBatch(array $emails): void
    {
        Http::pool(fn ($pool) => collect($emails)->map(fn ($email) => $pool->get(
            "https://tools-httpstatus.pickup-services.com/200?sleep=500&email={$email}"
        )));
    }
}
