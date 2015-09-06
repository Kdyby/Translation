# Quickstart

This extension is here to provide robust translation system for Nette Framework.
It implements `Nette\Localization\ITranslator` using [Symfony/Translation](https://github.com/symfony/Translation).


## Installation

The best way to install Kdyby/Translation is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/translation
```

and you can enable the extension using your neon config

```yml
extensions:
	translation: Kdyby\Translation\DI\TranslationExtension
```


## Setup

We have to tell the translator in what language user wants to see the website.
You should define persistent parameter `$locale` in your presenter. That way you can keep the language in the url.
Also, you probably want to inject the translator to the presenter, so let's write that code too.

```php
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	/** @persistent */
	public $locale;

	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;

	// rest of your BasePresenter
}
```

Example router might look like this

```php
$router[] = new Route('[<locale=cs cs|en>/]<presenter>/<action>', "Homepage:default");
```

There is also an interface `IUserLocaleResolver` and few default implementations. These implementations try to figure out, in what language should the website be displayed.
The first one looks in request parameters and searches for `locale`, that's why there is the persistent parameter and example route. If it fails, it tries to look at `Accept-Language` header, and if that fails, it fallbacks to default locale.

To change the default language, place this in your `app/config/config.neon`

```yml
translation:
	default: cs
	fallback: [cs_CZ, cs]
```


## Usage

The default directory for translation files is `%appDir%/lang` and they have to be named in specific way.
The mask is `<category>.<language>.<type>`, this means for example `app/lang/messages.en_US.neon`.

This example file `messages.en_US.neon` would look like this

```yml
homepage:
	hello: "Hello World"
```

and `messages.cs_CZ.neon` would look like

```yml
homepage:
	hello: "Ahoj světe!"
```

Now when we have all this prepared, you may open your website at url `/en/`.

The translator should resolve to `locale=en` and load the right message calatogues. Try this in your presenter

```php
echo $this->translator->translate('messages.homepage.hello');
```

It should print "Hello World", and when you open `/cs/` you should see "Ahoj světe".

Why `messages.homepage.hello`? The `messages` is in the catalogue filename, and you can change it to "front" or "emails", whatever you like.
`homepage.hello` is from the `.neon` file, the structure is flattened and joined by dots.


## Placeholders

Sometimes, a message containing a variable needs to be translated

```php
public function actionDefault($name)
{
    $this->flashMessage($this->translator->translate('Hello ' . $name));
}
```

But you cannot just paste the variable inside the message, how would you translate that? The translator would search for "Hello Filip" and would found nothing.
Instead of writing a translation for every possible iteration of the `$name` variable, you can replace the variable with a "placeholder".

```php
$this->translator->translate("Hello %name%", ['name' => $name]);
```

And we should probably move it to catalogue file right away

```yml
homepage:
	helloName: "Hello %name%"
```

```php
$this->translator->translate('messages.homepage.helloName', ['name' => $name]);
```

The translator will look for the given message, and replace the parameters after it finds the translation.


## Pluralization

When a translation has different forms due to pluralization, you can provide all the forms as a string separated by a pipe (|)

```yml
homepage:
	applesCount: 'There is one apple|There are %count% apples'
```

And then call it as you already know

```php
echo $this->translator->translate('messages.homepage.applesCount', 10); // There are 10 apples
```

For the exact format and all it's possibilities please check [the current Symfony documentation](http://symfony.com/doc/current/book/translation.html#pluralization).


## Templates

You don't have to call this verbose method in templates, there is a macro prepared for you!
If you're not hacking the `Latte\Engine` creation, it will work right away, because the extension will register it.

```smarty
<p>{_messages.homepage.hello}</p>
<p>{_messages.homepage.helloName, [name => $name]}</p>
<p>{_messages.homepage.applesCount, 10}</p>
```


## Additional configuration

There are several additional configurations

### Bar panel

Enables and disables Nette debugger bar panel

```yml
translation:
	debugger: off
```

### Whitelisting of resources

You might happen to have a multilingual system, that has many available translations, but you only need, let's say, 3.
Nice example is `symfony/validator`, it's translated to lots of languages but you don't want to process all of them.

Whitelist is used in compile-time and prevents loading of resources that will not be used.

It's disabled by default, but you can enable it by passing an array of regular expressions, where the simplest might be simple `cs` or `en`.

```yml
translation:
	whitelist: [cs, en, de]
```

This allows you to include components like `symfony/validator` and still have relevant output of `Translator::getAvailableLocales()`.
If you wouldn't enable the whitelisting, instead of only locales you want to use in your current app, you would see all the locales of all the resources that are added by each component.


### Locale resolvers

There are several resolvers that take care of figuring out what locale should be used in translator as default.

Default locale can be configured by `translation: default:`

#### Session

This resolver stores the locale in session and it has highest priority.

It is by default turned off and can be enabled by following configuration

```yml
translation:
	resolvers:
		session: on
```

It can be autowired by `Kdyby\Translation\LocaleResolver\SessionResolver`, and you can change the stored locale using `setLocale` method.


#### Parameter

This resolver looks for parameter `locale` in application `Request` produced by routers.

It is by default turned on and can be disabled by following configuration

```yml
translation:
	resolvers:
		request: off
```


#### Accept Language Header

This resolver asks the `Translator` for acceptable locales (based on loaded resources), and then tries to find one of them in Accept-Language http header provided by client's browser.

It is by default turned on and can be disabled by following configuration

```yml
translation:
	resolvers:
		header: off
```


## Database integration

### Loaders

Kdyby\Translation can use two database libraries for handling database translations: [Nette Database](https://github.com/nette/database) and [Doctrine 2 DBAL](https://github.com/doctrine/dbal)

There is minimal configuration which needs to be added to your config.neon:

```yml
translation:
	database:
		loader: doctrine
```

Loader can have values: `doctrine` or `nettedb`, it depends, which database library you use.
You can also use your own Loader, then you put to your config.neon full name with namespace:

```yml
translation:
	database:
		loader: \Namespace\CustomLoader
```

The loader needs to implement the [Symfony\Component\Translation\Loader\LoaderInterface](https://github.com/symfony/Translation/blob/3ba54181c8386b180999d942bf33681a726bb70f/Loader/LoaderInterface.php) 
but it will be much more comfortable, when you extend abstract class Kdyby\Translation\Loader\DatabaseLoader, which handles all logic and all you have to do is to implement queries for retrieving translations.

### Table structure and configuration

The table in database has 4 columns:
- `key` (like key in your .neon translations, and key, which you write to translate method)
- `locale`: language of message
- `message`: actual text of message
- `updatedAt`: column with datetime of last update. This one is used for cache invalidation because of Kdyby\Translation's sophisticated caching.

You can configure names of columns and name of translation table in your database like this

```yml
translation:
	database:
		table: translationTableName
		columns:
			key: keyColumnName
			locale: localeColumnName
			message: messageColumnName
			updatedAt: lastUpdateColumnName
```

Default values are

```yml
translation:
	database:
		table: translations
		columns:
			key: key
			locale: locale
			message: message
			updatedAt: updated_at
```

### Generating table via Symfony Commands

If you want to have your translation table generated, you need to download the [Doctrine 2 DBAL](https://github.com/doctrine/dbal) library 
and [Kdyby\Console](https://github.com/Kdyby/Console) library over Composer and then you can use kdyby:translation-create-table command to generate table

```sh
$ kdyby:translation-create-table --dump-sql dumps SQL query, but does not execute it.
$ kdyby:translation-create-table --force executes SQL query.
```


### Modifying translations

You can modify your translations in database in any way you are used to, but you must update the value in updatedAt column.
Or you can let Kdyby\Translation do that for you.

Following snippet of code will show you how you can modify translations in Presenter and save them using Kdyby\Translation (only for illustration, please, keep this in your Model layer)

```php
<?php
 
namespace App\Presenters;

use Kdyby\Translation\Translator;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Nette;

class TranslationPresenter extends BasePresenter
{
	/** @var Translator @inject */
	public $translator;

	/** @var TranslationWriter @inject */
	public $writer;

	public function renderDefault()
	{
		$catalogue = $this->translator->getCatalogue('en');	//catalogue is object with all translations for given language

		$catalogue->set('messages.homepage.hello', 'Hi');	//using this method, you can change translations. Or add tran
		$catalogue->add(['messages.homepage.farewell' => 'Farewell']);	//using this method, you can add new translations. You have to save them in associative array: key => message 

		$this->writer->writeTranslations($catalogue, 'database');	//with this, you save translations. If you use 'neon' or any other format instead of 'database', you can save all translation to neon files (or another supported format).

	}
```
