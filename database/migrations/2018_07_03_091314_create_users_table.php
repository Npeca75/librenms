<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (LibreNMS\DB\Eloquent::getDriver() !== 'sqlite') {
            DB::statement("SET TIME_ZONE='+00:00'"); // force UTC for default timestamp value
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('auth_type', 32)->nullable();
            $table->integer('auth_id')->nullable();
            $table->string('username');
            $table->string('password')->nullable();
            $table->string('realname', 64);
            $table->string('email', 64);
            $table->char('descr', 30);
            $table->tinyInteger('level')->default(0);
            $table->boolean('can_modify_passwd')->default(1);
            $table->timestamp('created_at')->default('1970-01-02 00:00:01');
            $table->timestamp('updated_at')->useCurrent();
            $table->string('remember_token', 100)->nullable();
            $table->unique(['auth_type', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('users');
    }
};
