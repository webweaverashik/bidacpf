<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's standard database-notifications table, backing the in-app
     * notification dropdown and the notifications listing page. Rows are
     * written by App\Notifications\SystemEventNotification via the
     * "database" channel; the same notification also fans out by email.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');       // notification class FQN
            $table->morphs('notifiable'); // notifiable_type + notifiable_id (User)
            $table->text('data');         // JSON payload (title, message, url, icon, color, category)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
