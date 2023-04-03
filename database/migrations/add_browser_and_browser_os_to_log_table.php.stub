<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('authentication-log.table_name'), function (Blueprint $table) {
            $table->after('user_agent', function (Blueprint $table) {
                $table->string('browser')->nullable()->index();
                $table->string('browser_os')->nullable()->index();
            });

            $table->index('ip_address');
            $table->index('user_agent');
            $table->index('login_successful');
        });
    }
};
