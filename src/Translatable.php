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
            return strlen(trim($word)) >= 3 ? '+' . trim($word) . '*' : '';
        })->implode(' ');
    }
    
    public function scopeSearchFullText($query, $term)
    {
        $term = $this->fullTextWildcards($term);
        
        $matches = Translation::selectRaw('translatable_id, MATCH(`value`) AGAINST (\'' . $term . '\' IN BOOLEAN MODE) as relevance')
            ->whereRaw(\DB::raw('MATCH (`value`) AGAINST (\'' . $term . '\' IN BOOLEAN MODE)'))
            ->where('translations.translatable_type', '=', \get_class($this))
            ->having('relevance', '>', 0)
            ->orderBy('relevance')
            // force to search only in the current language
            //->where('translations.locale', app()->getLocale());
            ->pluck('translatable_id')
            ->toArray();
        
        return $query->whereIn(DB::raw($this->getTable() . '.' . $this->primaryKey), $matches);
    }
}
