[![Build Status](https://travis-ci.org/mikehins/laravel-translatable.svg?branch=master)](https://travis-ci.org/mikehins/laravel-translatable) [![StyleCI shield](https://github.styleci.io/repos/223970038/shield)](https://github.styleci.io/repos/223970038) [![GitHub license](https://img.shields.io/github/license/mikehins/laravel-translatable.svg)](https://github.com/mikehins/laravel-translatable/blob/master/LICENSE)

# laravel-translatable
Laravel translatable uses the model events to create update or delete translations associated with a model.

MORE INSTRUCTIONS TO COME...

Install
```composer require mikehins/laravel-translatable```

Next, you need to publish the migration
```php artisan vendor:publish --provider="Mikehins\Translatable\TranslatableServiceProvider" --tag="migrations"```

and the config file
```php artisan vendor:publish --provider="Mikehins\Translatable\TranslatableServiceProvider" --tag="config"```

You will need to run the migration to create the translations table
```php artisan migrate```

You need to edit the configuration file to tell your app which languages ​​you will use
```
<?php

return [
    'en' => 'English',
    'fr' => 'Français',
];
```

To use the package you need to add a trait to your model
```
use Illuminate\Database\Eloquent\Model;
use Mikehins\Translatable\Translatable;

class MyModel extends Model
{
	use Translatable;
		
	protected $fillable = ['translatable'];
}
```

In your forms you need to use an array input called translatable then use the language code as key and then the field name you want to use
```<input type="text" name="translatable[en][title]" ...```

You can specify the value of the field in combination with the ```old()``` helper
```value="{{ old('translatable.en.title', $model->translations->for('title', 'en') ?? null) }}" /> ```

You can also show errors if the validation fail
```{{ $errors->first('translatable.en.title') }}```

Here's a code snippet using bootstrap 4 with tabs
```
<div class="row">
    <div class="col-2">
        <div class="nav navbar-dark flex-column nav-pills mt-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
            <a href="#tab-general" data-toggle="tab" class="nav-link rounded-0 active">General</a>
        </div
    </div
    <div class="col-10">
        <div class="tab-content" id="v-pills-tabContent">
            <ul class="nav nav-tabs" id="language">
                @foreach(config('languages') as $code => $language)
                <li class="nav-item">
                    <a href="#language-{{ $code }}" data-toggle="tab" class="nav-link"><img width="18" src="/flags/4x3/{{ $code }}.svg" title="{{ $language }}"/> {{ $language }}</a>
                </li>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach(config('languages') as $code => $language)
                <div class="tab-pane active" id="language-{{ $code }}">
                    <div class="form-group">
                    <label class="col-sm-2 col-form-label" for="input-name-{{ $code }}">LABEL</label>
                    <div class="col-sm-10">
                        <input type="text" name="translatable[{{ $code }}][name]" value="{{ old('translatable.' . $code . '.name', $model->translations->for('name', $code) ?? null) }}" placeholder="MY TRANSLATABLE FIELD" id="input-name-{{ $code }}" class="form-control{{ $errors->has('translatable.' . $code . '.name') ? ' is-invalid' : '' }}"/>
                        <em class="error invalid-feedback">{{ $errors->first('translatable.' . $code . '.name') }}</em>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Validation
To validate the input you can use the validator like usual
```
public function store(Request $request)
{
    $rules = [];
    foreach (config('languages') as $code => $language) {
        $rules = array_merge($rules, [
            'translatable.' . $code . '.title' => 'required|max:255',
            'translatable.' . $code . '.body'  => 'required',
        ]);
    };
    
    $validatedData = $request->validate(array_merge($rules, [
        'other' => 'required',
        'field' => 'required',
    ]));
    ...
```

Or inside a form request validator
```
public function rules()
{
    $rules = [];
    
    foreach (config('languages') as $code => $language) {
        $rules = array_merge($rules, [
            'translatable.' . $code . '.title' => 'required',
            'translatable.' . $code . '.body'  => 'required',
        ]);
    };
    
    return $rules;
}

public function messages()
{
    $messages = [];
    
    foreach (config('languages') as $code => $language) {
        $messages = array_merge($messages, [
            'translatable.' . $code . '.name:required'        => trans('validation.required'),
            'translatable.' . $code . '.description:required' => trans('validation.required'),
        ]);
    };
    
    return $messages;
}
```