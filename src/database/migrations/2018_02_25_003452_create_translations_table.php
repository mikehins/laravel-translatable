<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTranslationsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('translations', function (Blueprint $table) {
			$table->string('key');
			$table->text('value');
			$table->bigInteger('translatable_id')->unsigned();
			$table->string('translatable_type');
			$table->string('locale');
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('translations');
	}
}
