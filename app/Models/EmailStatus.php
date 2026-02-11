<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailStatus extends Model
{
    //
    public function up(): void {
        Schema::create('email_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_upload_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('status')->nullable(); // "valid" / "invalid"
            $table->integer('http_status')->nullable(); // código HTTP
            $table->timestamps();
        });
    }

}
