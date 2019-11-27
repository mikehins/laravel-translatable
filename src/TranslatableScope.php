<?php

namespace Mikehins\Translatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TranslatableScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->translated();
    }
}
