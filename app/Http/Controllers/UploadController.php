<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileUpload;
use App\Jobs\ProcessEmailFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx'
        ]);

        $file = $request->file('file');

        // Generar nombres únicos
        $xlsxFilename = time() . '_' . $file->getClientOriginalName();
        $csvFilename = pathinfo($xlsxFilename, PATHINFO_FILENAME) . '.csv';

        $csvRelativePath = 'uploads/' . $csvFilename;
        $csvAbsolutePath = storage_path('app/' . $csvRelativePath);

        // Crear carpeta si no existe
        if (!file_exists(dirname($csvAbsolutePath))) {
            mkdir(dirname($csvAbsolutePath), 0777, true);
        }

        try {
            // Convertir XLSX a CSV
            $spreadsheet = IOFactory::load($file->getRealPath());
            $writer = IOFactory::createWriter($spreadsheet, 'Csv');
            $writer->save($csvAbsolutePath);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al convertir XLSX a CSV: ' . $e->getMessage()
            ], 500);
        }

        // Crear registro en DB
        $fileUpload = FileUpload::create([
            'filename' => $file->getClientOriginalName(),
            'status' => 'processing',
            'total_emails' => 0,
            'valid_emails' => 0,
            'invalid_emails' => 0
        ]);

        // Despachar job con ruta relativa correcta
        ProcessEmailFile::dispatch($fileUpload->id, $csvRelativePath);

        return response()->json([
            'message' => 'Archivo recibido y en procesamiento',
            'data' => $fileUpload
        ]);
    }
}
