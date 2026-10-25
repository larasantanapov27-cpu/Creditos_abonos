<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('username', 50)->nullable()->unique());
        foreach (DB::table('users')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update(['username' => 'usuario'.$user->id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('username'));
    }
};
