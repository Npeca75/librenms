<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('abilities')) {
            Schema::create('abilities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('title')->nullable();
                $table->bigInteger('entity_id')->unsigned()->nullable();
                $table->string('entity_type')->nullable();
                $table->boolean('only_owned')->default(false);
                $table->longText('options')->nullable();
                $table->integer('scope')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('title')->nullable();
                $table->integer('scope')->nullable()->index();
                $table->timestamps();

                $table->unique(
                    ['name', 'scope'],
                    'roles_name_unique'
                );
            });
        }

        if (! Schema::hasTable('assigned_roles')) {
            Schema::create('assigned_roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('role_id')->unsigned()->index();
                $table->bigInteger('entity_id')->unsigned();
                $table->string('entity_type');
                $table->bigInteger('restricted_to_id')->unsigned()->nullable();
                $table->string('restricted_to_type')->nullable();
                $table->integer('scope')->nullable()->index();

                $table->index(
                    ['entity_id', 'entity_type', 'scope'],
                    'assigned_roles_entity_index'
                );

                $table->foreign('role_id')
                    ->references('id')->on('roles')
                    ->onUpdate('cascade')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('ability_id')->unsigned()->index();
                $table->bigInteger('entity_id')->unsigned()->nullable();
                $table->string('entity_type')->nullable();
                $table->boolean('forbidden')->default(false);
                $table->integer('scope')->nullable()->index();

                $table->index(
                    ['entity_id', 'entity_type', 'scope'],
                    'permissions_entity_index'
                );

                $table->foreign('ability_id')
                    ->references('id')->on('abilities')
                    ->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('permissions');
        Schema::drop('assigned_roles');
        Schema::drop('roles');
        Schema::drop('abilities');
    }
};
