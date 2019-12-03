<?php

namespace Mikehins\Translatable;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait Translatable
{
    public static $transtableFieldName = 'translatable';
    
    public $translatable;
    public $model;
    
    protected $locale;
    
    public static function bootTranslatable()
    {
        static::addGlobalScope(new TranslatableScope);
        
        static::saving(function ($model) {
            $model->translatable = collect($model->attributes)->only(static::$transtableFieldName)->toArray();
            $model->attributes = collect($model->attributes)->except(static::$transtableFieldName)->toArray();
        });
        
        static::saved(function ($model) {
            if ($model->translatable) {
                (new self)->saveTranslation($model);
            }
        });
        
        static::deleted(function ($model) {
            if ((new $model)->has('translations')) {
                $model->translations()->delete();
            }
        });
    }
    
    public function locale($value = null)
    {
        $this->locale = $value;
        
        return $this;
    }
    
    // Relationship
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }
    
    // scope
    public function scopeTranslated($query, ...$fields)
    {
        $this->locale = $this->locale ?? app()->getLocale();
        
        $fields = empty($fields) ? $this->getTranslatedFieldsForCurrentModel() : collect($fields);
        
        if ($fields->isEmpty()) {
            return $query;
        }
        
        // Add select * if nothing has been selected yet
        if ($query->getQuery()->columns === null) {
            $query->select('*');
        }
        
        // Build the sub select
        $fields->each(function ($key) use ($query) {
            $this->subSelectTranslation($query, $key);
        });
        
        return $query;
    }
    
    protected function subSelectTranslation($query, $key): void
    {
        $query->addSelect([
            $key => function ($query) use ($key) {
                $query->select(Translation::getTableName() . '.value')
                    ->from(Translation::getTableName())
                    ->where(Translation::getTableName() . '.translatable_type', '=', \get_class($this))
                    ->where(Translation::getTableName() . '.locale', '=', $this->locale)
                    ->where(Translation::getTableName() . '.key', '=', $key)
                    ->where(Translation::getTableName() . '.translatable_id', '=', \DB::raw($this->getTable() . '.' . $this->primaryKey));
            }
        ]);

//        $query->addSelect(\DB::raw('(
//			SELECT      `' . Translation::getTableName() . '`.`value`
//			  FROM      `' . Translation::getTableName() . '`
//			 WHERE      `' . Translation::getTableName() . '`.`translatable_type` = "' . \get_class($this) . '"
//			   AND      `' . Translation::getTableName() . '`.`locale` = "' . $this->locale . '"
//			   AND      `' . Translation::getTableName() . '`.`key` = "' . $key . '"
//			   AND      `' . Translation::getTableName() . '`.`translatable_id` = `' . $this->getTable() . '`.`' . $this->primaryKey . '`
//			) as `' . $key . '`')
//        );
    }
    
    /**
     * Query the translations table for possible keys if none are provided.
     * @return Collection
     */
    public function getTranslatedFieldsForCurrentModel(): Collection
    {
        $table = Translation::getTableName();
        
        return DB::table($table)->select($table . '.key')
            ->where($table . '.translatable_type', \get_class($this))
            ->where($table . '.locale', $this->locale)
            ->groupBy($table . '.key')
            ->pluck('key');
    }
    
    public function saveTranslation($model): int
    {
        return (new Translation)->store([
            'translatable'      => $model->translatable[static::$transtableFieldName],
            'translatable_type' => \get_class($model),
            'translatable_id'   => $model->id,
        ]);
    }
    
    protected function fullTextWildcards($term)
    {
        return collect(explode(' ', str_replace(['-', '+', '<', '>', '@', '(', ')', '~'], '', $term)))->map(function ($word, $key) {
            return strlen($word) >= 3 ? '+' . $word . '*' : '';
        })->implode(' ');
    }
    
    public function scopeSearchFullText($query, $term, $fields = [])
    {
        $alias = Str::random(8);
        return $query->join(\DB::raw('translations as ' . $alias . ' ON ' . $alias . '.translatable_id = ' . $this->getTable() . '.' . $this->primaryKey))
            ->addSelect([
                'relevance' => function ($query) use ($term, $alias) {
                    $query->selectRaw("MATCH (`' . $alias . '`.`value`) AGAINST ('" . $this->fullTextWildcards($term) . "' IN BOOLEAN MODE)")
                        ->from('translations')
                        ->where($alias . '.translatable_id', DB::raw($this->getTable() . '.' . $this->primaryKey))
                        ->limit(1);
                }
            ])
            ->whereRaw("MATCH (`translations`.`value`) AGAINST (? IN BOOLEAN MODE)", $this->fullTextWildcards($term))
            ->orderByDesc('relevance');
    }
}
