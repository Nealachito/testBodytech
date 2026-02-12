<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    //
     protected $fillable = [
        'filename',
        'status',
        'total_emails',
        'valid_emails',
        'invalid_emails'
    ];

    public function emails()
    {
        return $this->hasMany(Email::class);
    }
}
