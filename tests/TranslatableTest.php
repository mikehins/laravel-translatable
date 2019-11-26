<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Mikehins\Translatable\Translation;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Mikehins\Translatable\Translatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestTable extends Model
{
	use Translatable;
	
	protected $guarded = [];
}

class TranslatableTest extends TestCase
{
	use RefreshDatabase;
	
	public $data;
	
	public $fr_sentence = 'Nom en français';
	public $en_sentence = 'Name in english';
	public $key         = 'name';
	
	protected function getPackageProviders($app)
	{
		return [
			'Mikehins\Translatable\TranslatableServiceProvider'
		];
	}
	
	public function setUp(): void
	{
		parent::setUp();
		
		Schema::create('test_tables', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->timestamps();
		});
		
		// <input name="translatable[fr][model]" value=""
		$this->data = [
			'translatable' => [
				'fr' => [
					$this->key => $this->fr_sentence,
				],
				'en' => [
					$this->key => $this->en_sentence,
				]
			],
		];
		
		$this->testClass = TestTable::class;
	}
	
	/** @test */
	public function it_should_create_a_translation()
	{
		(new TestTable)->fill($this->data)->save();
		
		$this->assertDatabaseHas('translations', [
			'key'               => $this->key,
			'value'             => $this->fr_sentence,
			'translatable_type' => $this->testClass,
			'locale'            => 'fr',
		]);
		
		$this->assertDatabaseHas('translations', [
			'key'               => $this->key,
			'value'             => $this->en_sentence,
			'translatable_type' => $this->testClass,
			'locale'            => 'en',
		]);
	}
	
	/** @test */
	public function it_cannot_insert_a_translation_because_the_way_insert_works()
	{
		$this->expectException(\ErrorException::class);
		
		(new TestTable)->insert($this->data);
		
		$this->expectExceptionMessage('Array to string conversion');
		
		$this->assertDatabaseMissing('translations', [
			'key'               => $this->key,
			'value'             => $this->fr_sentence,
			'translatable_type' => $this->testClass,
			'locale'            => 'fr',
		]);
		
		$this->assertDatabaseMissing('translations', [
			'key'               => $this->key,
			'value'             => $this->en_sentence,
			'translatable_type' => $this->testClass,
			'locale'            => 'en',
		]);
	}
	
	/** @test */
	public function it_should_delete_a_translation()
	{
		$model = (new TestTable)->fill($this->data);
		$model->save();
		
		$this->assertDatabaseHas('translations', [
			'key'               => $this->key,
			'value'             => $this->fr_sentence,
			'translatable_type' => $this->testClass,
			'translatable_id'   => $model->id,
			'locale'            => 'fr',
		]);
		
		(new TestTable)->find($model->id)->delete();
		
		$this->assertDatabaseMissing('translations', [
			'key'               => $this->key,
			'value'             => $this->fr_sentence,
			'translatable_type' => $this->testClass,
			'translatable_id'   => $model->id,
			'locale'            => 'fr',
		]);
	}
	
	/** @test */
	public function it_should_update_a_translation()
	{
		$model = (new TestTable)->fill($this->data);
		$model->save();
		
		$this->assertDatabaseHas('translations', [
			'key'               => $this->key,
			'value'             => $this->fr_sentence,
			'translatable_type' => $this->testClass,
			'translatable_id'   => $model->id,
			'locale'            => 'fr',
		]);
		
		(new TestTable)->find($model->id)->update([
			'translatable' => [
				'fr' => [
					$this->key => 'update fr',
				],
				'en' => [
					$this->key => 'update en',
				]
			],
		]);
		
		$this->assertDatabaseHas('translations', [
			'key'               => $this->key,
			'value'             => 'update fr',
			'translatable_type' => $this->testClass,
			'translatable_id'   => $model->id,
			'locale'            => 'fr',
		]);
	}
	
	/** @test */
	public function it_should_fill_a_translation()
	{
		$model = (new TestTable)->fill($this->data);
		$model->save();
		
		$this->assertDatabaseHas('translations', [
			'key'               => $this->key,
			'value'             => $this->fr_sentence,
			'translatable_type' => $this->testClass,
			'translatable_id'   => $model->id,
			'locale'            => 'fr',
		]);
		
		(new TestTable)->find($model->id)->update([
			'translatable' => [
				'fr' => [
					$this->key => 'update fr',
				],
				'en' => [
					$this->key => 'update en',
				]
			],
		]);
		
		$this->assertDatabaseHas('translations', [
			'key'               => $this->key,
			'value'             => 'update fr',
			'translatable_type' => $this->testClass,
			'translatable_id'   => $model->id,
			'locale'            => 'fr',
		]);
	}
	
	/** @test */
	public function it_should_automatically_retrieve_a_translation()
	{
		(new TestTable($this->data))->save();
		(new TestTable($this->data))->save();
		(new TestTable($this->data))->save();
		
		$item = (new TestTable)->locale('fr')->find(1);
		$this->assertEquals($this->fr_sentence, $item->name);
		
		$item = (new TestTable)->locale('en')->find(1);
		$this->assertEquals($this->en_sentence, $item->name);
	}
	
	/** @test */
	public function it_should_detect_the_translations_table()
	{
		$this->assertEquals('translations', Translation::getTableName());
	}
	
	/** @test */
	public function it_should_be_able_to_order_model_by_translated_keys()
	{
		(new TestTable(['translatable' => ['fr' => ['field' => 'ZZZ',], 'en' => ['field' => 'ZZZ',]],]))->save();
		(new TestTable(['translatable' => ['fr' => ['field' => 'GGG',], 'en' => ['field' => 'GGG',]],]))->save();
		(new TestTable(['translatable' => ['fr' => ['field' => '111',], 'en' => ['field' => '111',]],]))->save();
		(new TestTable(['translatable' => ['fr' => ['field' => 'AAA',], 'en' => ['field' => 'AAA',]],]))->save();
		
		$ordered = (new TestTable)->orderBy('field')->get();
		
		$this->assertEquals('111', $ordered->first()->field);
		$this->assertEquals('ZZZ', $ordered->last()->field);
	}
	
	/** @test */
	public function it_should_map_the_posted_data_to_the_translation_model()
	{
		$translation = new Translation;
		
		request()->merge($this->data);
		
		$translatableAttributes = $translation->getTranslatableAttribute();
		
		$this->assertEquals($translatableAttributes['fr']['name'], $this->data['translatable']['fr']['name']);
	}
	
	/** @test */
	public function it_should_bump_my_test_coverage_up()
	{
		$this->assertInstanceOf(MorphTo::class, (new Translation)->translatable());
	}
	
	/** @test */
	public function it_should_bump_my_test_coverage_up2()
	{
		$this->assertInstanceOf(Builder::class, (new Translation)->orderTranslationByKey());
	}

//	/** @test */
//	public function it_shoud_be_able_to_suggest()
//	{
//		(new TestTable(['translatable' => ['fr' => [$this->key => 'ABCD',], 'en' => [$this->key => 'ABCD',]],]))->save();
//		(new TestTable(['translatable' => ['fr' => [$this->key => 'BCDE',], 'en' => [$this->key => 'BCDE',]],]))->save();
//		(new TestTable(['translatable' => ['fr' => [$this->key => 'CDEF',], 'en' => [$this->key => 'CDEF',]],]))->save();
//		(new TestTable(['translatable' => ['fr' => [$this->key => 'DEFG',], 'en' => [$this->key => 'DEFG',]],]))->save();
//
//		dd((new Translation)->suggest(TestTable::class, $this->key, 'CD')->orderBy($this->key)->get()->toArray());
//	}
}