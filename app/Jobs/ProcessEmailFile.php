<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FileUpload;
use App\Models\Email;
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
        $reader->setHeaderOffset(null); // Cada fila es solo el email
        $records = $reader->getRecords();

        $total = 0;
        $valid = 0;
        $invalid = 0;

        $chunkSize = 50; // 🔥 Ajusta aquí si quieres más rápido o más visible
        $batch = [];

        foreach ($records as $row) {

            $emailValue = trim($row[0] ?? '');

            if ($emailValue === '') {
                continue;
            }

            $total++;

            if (filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {

                $valid++;
                $batch[] = $emailValue;

            } else {

                $invalid++;

                Email::create([
                    'file_upload_id' => $fileUpload->id,
                    'email' => $emailValue,
                    'is_valid' => false,
                    'status_code' => null
                ]);
            }

            // Cuando el batch se llena → procesamos en paralelo
            if (count($batch) >= $chunkSize) {

                $this->processBatch($batch, $fileUpload);

                $batch = [];

                // 🔥 Actualizamos progreso visible
                $fileUpload->update([
                    'total_emails' => $total,
                    'valid_emails' => $valid,
                    'invalid_emails' => $invalid,
                ]);
            }
        }

        // Procesar último batch restante
        if (!empty($batch)) {
            $this->processBatch($batch, $fileUpload);
        }

        // 🔥 Actualización final
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
   protected function processBatch(array $emails, FileUpload $fileUpload): void
{
    $responses = Http::pool(function ($pool) use ($emails) {
        return collect($emails)->map(function ($email) use ($pool) {
            return $pool->get(
                "https://tools-httpstatus.pickup-services.com/200?sleep=500&email={$email}"
            );
        });
    });

    foreach ($responses as $index => $response) {

        Email::create([
            'file_upload_id' => $fileUpload->id,
            'email' => $emails[$index],
            'is_valid' => true,
            'status_code' => $response->status()
        ]);
    }
}

}
