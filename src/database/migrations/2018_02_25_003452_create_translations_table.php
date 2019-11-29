<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
		
		// sqlite does not like this...
		if (app()->environment() !== 'testing') {
			\DB::statement('ALTER TABLE `translations` ADD FULLTEXT fulltext_index (`key`)');
		}
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