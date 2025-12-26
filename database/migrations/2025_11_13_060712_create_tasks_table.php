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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_name', 100);
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->date('tanggal');
            $table->decimal('estimasi_jam')->nullable();
            $table->boolean('is_long_term')->default(false);
            $table->integer('allocation_hours')->default(0);
            $table->longText('output')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->enum('status', ['opened', 'progress', 'closed','overdue','postponed'])->default('opened');
            $table->integer('total_overdue')->default(0);
            $table->enum('priority', ['urgent', 'high', 'medium', 'low', 'not_priority'])
                ->default('not_priority');
            $table->text('input')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
