<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('room_id')->constrained();
            $table->foreignId('child_id')->constrained('children');
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'child_id']);
            $table->index(['tenant_id', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
