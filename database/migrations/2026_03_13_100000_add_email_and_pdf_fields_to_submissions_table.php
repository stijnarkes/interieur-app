<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('moodboard_path')->nullable();
            $table->string('inspiration_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('email_status')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'moodboard_path',
                'inspiration_path',
                'pdf_path',
                'email_status',
                'email_sent_at',
                'email_error',
            ]);
        });
    }
};
