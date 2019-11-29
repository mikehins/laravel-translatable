<?php

namespace Mikehins\Translatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class Translation extends Model
{
    protected $table = 'translations';
    
    protected $primaryKey = 'translatable_id';
    
    protected $guarded = [];
    
    public static function getTableName()
    {
        return (new static)->getTable();
    }
    
    public function store(array $attributes, array $values = []): int
    {
        $this->deleteTranslation($attributes['translatable_type'], $attributes['translatable_id']);
        
        collect($attributes['translatable'])->each(function ($data, $locale) use ($attributes) {
            collect($data)->each(function ($value, $key) use ($attributes, $locale) {
                self::insert([
                    'key'               => $key,
                    'value'             => $value ?? '',
                    'locale'            => $locale,
                    'translatable_type' => $attributes['translatable_type'],
                    'translatable_id'   => $attributes['translatable_id'],
                ]);
            });
        });
        
        return $attributes['translatable_id'];
    }
    
    public function deleteTranslation($type, $id)
    {
        $instance = self::where([
            'translatable_type' => $type,
            'translatable_id'   => $id,
        ]);
        
        if ($instance) {
            $instance->delete();
        }
    }
    
    /**
     * @param QueryBuilder $query
     * @param string $key
     * @param string $order
     * @return Builder
     */
    public function scopeOrderTranslationByKey($query, $key = 'name', $order = 'asc')
    {
        return $query->select(\DB::raw('
			IF(translations.`key` = "' . $key . '", translations.value, "") as ' . $key . '
		'))->where(
            'translations.locale', '=', app()->getLocale()
        )->orderBy(
            \DB::raw($key . ' ' . $order)
        )->groupBy('translations.translatable_id');
    }
    
    public function translatable()
    {
        return $this->morphTo();
    }
    
    public function getTranslatableAttribute()
    {
        return $this->attributes['translatable'] = request('translatable');
    }
    
    // https://arianacosta.com/php/laravel/tutorial-full-text-search-laravel-5/
	// full text search
}
