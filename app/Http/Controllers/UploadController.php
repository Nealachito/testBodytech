<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{

    public function store(Request $request){
        $request->validate([
            'file' => 'required|file|mimes:xlsx'
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();

        $data = Excel::toArray([], $file);

        $rows = $data[0]; // Primera hoja

        $total = 0;
        $valid = 0;
        $invalid = 0;

       foreach ($rows as $index => $row) {

            // Saltar encabezado (fila 0)
            if ($index === 0) {
                continue;
            }

            if (!isset($row[0])) {
                continue;
            }

            $email = trim($row[0]);

            if ($email === '') {
                continue;
            }

            $total++;

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $valid++;

                // Llamada HTTP externa
                $response = Http::get("https://tools-httpstatus.pickup-services.com/200?sleep=500&email={$email}");

            } else {
                $invalid++;
            }
        }


        $fileUpload = FileUpload::create([
            'filename' => $filename,
            'total_emails' => $total,
            'valid_emails' => $valid,
            'invalid_emails' => $invalid
        ]);

        return response()->json([
            'message' => 'Archivo procesado correctamente',
            'data' => $fileUpload
        ]);
    }

}
