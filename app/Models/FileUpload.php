<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    //
     protected $fillable = [
        'filename',
        'total_emails',
        'valid_emails',
        'invalid_emails'
    ];
}
