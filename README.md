[![Build Status](https://travis-ci.org/mikehins/laravel-translatable.svg?branch=master)](https://travis-ci.org/mikehins/laravel-translatable)
[![StyleCI shield](https://github.styleci.io/repos/223970038/shield)](https://github.styleci.io/repos/223970038)
[![Quality Score](https://img.shields.io/scrutinizer/g/mikehins/laravel-translatable.svg?style=flat-square)](https://scrutinizer-ci.com/g/mikehins/laravel-translatable)
[![Latest Stable Version](https://poser.pugx.org/mikehins/laravel-translatable/v/stable?format=flat-square)](https://packagist.org/packages/mikehins/laravel-translatable)
[![codecov.io](https://codecov.io/github/mikehins/laravel-translatable/coverage.svg?branch=master)](https://codecov.io/github/mikehins/laravel-translatable?branch=master)
[![Total Downloads](https://poser.pugx.org/mikehins/laravel-translatable/downloads)](https://packagist.org/packages/mikehins/laravel-translatable)

# laravel-translatable
DO NOT USE YET... MORE INSTRUCTIONS TO COME...

Laravel translatable uses the model events to create update or delete translations associated with a model.

## Installation

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

## Usage
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
## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## Credits

- [Mike Hins](https://github.com/mikehins)
- [All contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.