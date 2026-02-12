<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'file_upload_id',
        'email',
        'is_valid',
        'status_code'
    ];

    public function fileUpload()
    {
        return $this->belongsTo(FileUpload::class);
    }
}
