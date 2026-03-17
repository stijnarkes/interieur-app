<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('queued'); // queued | processing | completed | failed
            $table->json('input');                        // alle gebruikersinput
            $table->json('result')->nullable();           // volledig gegenereerd resultaat
            $table->text('error')->nullable();            // foutmelding bij failed
            $table->foreignId('submission_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
