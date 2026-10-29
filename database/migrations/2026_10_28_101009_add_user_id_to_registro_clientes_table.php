<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
            public function up(): void
        {
            if (Schema::hasColumn('registro_clientes', 'user_id')) {
                return;
            }
            Schema::table('registro_clientes', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users');
            });
        }

        public function down(): void
        {
            Schema::table('registro_clientes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
};
