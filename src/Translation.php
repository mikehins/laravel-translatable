<?php

namespace Mikehins\Translatable;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $table = 'translations';

    protected $primaryKey = 'translatable_id';

    protected $guarded = [];

    public static function getTableName()
    {
        return (new static)->getTable();
    }

    public function store(array $attributes, array $values = [])
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
     * @return $query
     */
    public function scopeOrderTranslationByKey($query, $key = 'name', $order = 'asc')
    {
        return $query->select(\DB::raw('
			IF(translations.`key` = "'.$key.'", translations.value, "") as '.$key.'
		'))->where(
            'translations.locale', '=', app()->getLocale()
        )->orderBy(
            \DB::raw($key.' '.$order)
        )->groupBy('translations.translatable_id');
    }

    public function translatable()
    {
        return $this->morphTo();
    }

    //	public function suggest($model, $key, $term)
    //	{
    //		return self::where('translations.locale', '=', app()->getLocale())
    //			->where('translations.key', '=', $key)
    //			->where('translations.value', 'LIKE', '%' . $term . '%')
    //			->where('translations.translatable_type', '=', $model);
    //	}

    public function getTranslatableAttribute()
    {
        return $this->attributes['translatable'] = request('translatable');
    }
}
