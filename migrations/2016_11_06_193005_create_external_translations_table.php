<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExternalTranslationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('translation_externals', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('status')->default(0);
            $table->string('locale');
            $table->string('manager');
            $table->string('model');
            $table->integer('model_id', false, true);
            $table->text('value')->nullable();
            $table->timestamps();
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('translation_externals');
	}

}
