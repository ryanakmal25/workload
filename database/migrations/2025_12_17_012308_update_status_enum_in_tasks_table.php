<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','progress','done','overdue','postponed') DEFAULT 'todo'");
    }
    public function down()
    {
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','progress','done') DEFAULT 'todo'");
    }
};
