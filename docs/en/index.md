Quickstart
==========

This extension is here to provide robust translation system for Nette Framework.
It implements `Nette\Localization\ITranslator` using [Symfony/Translation](https://github.com/symfony/Translation).


Installation
-----------

The best way to install Kdyby/Translation is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/translation:@dev
```

With dev Nette, you can enable the extension using your neon config.

```yml
extensions:
	translation: Kdyby\Translation\DI\TranslationExtension
```

If you're using stable Nette, you have to register it in `app/bootstrap.php`

```php
Kdyby\Translation\DI\TranslationExtension::register($configurator);

return $configurator->createContainer();
```


Setup
-----

We have to somehow tell the translator, what is the language, that the user want's to see the website in.
You should define persistent parameter `$locale` in your presenter. That way you can keep in url the language, or read it from the url.
Also, you probably want to inject the translator to the presenter, so let's write that code too.

```php
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	/** @persistent */
	public $locale;

	/** @var \Kdyby\Translation\Translator */
	protected $translator;

	public function injectTranslator(\Kdyby\Translation\Translator $translator)
	{
		$this->translator = $translator;
	}

	// ...
}
```

Example router might look like this

```php
$router[] = new Route('[<locale=cs cs|en>/]<presenter>/<action>', "Homepage:default");
```

There is also an interface `IUserLocaleResolver` and few default implementations, they try to figure out, what language does the visitor wanna use the website in.
The first one looks in request parameters and searches for `locale`, that's why there is the persistent parameter and example route.
If it fails, it tries to look at `Accept-Language` header, and if that fails, it fallbacks to default locale.

To change the default language, place this in your `app/config/config.neon`

```yml
translation:
	default: cs
	fallback: [cs_CZ, cs]
```


Usage
----

The default directory for translation files is `%appDir%/lang` and they have to be named in specific way.
The mask is `<category>.<language>.<type>`, this means for example `app/lang/messages.en_US.neon`.

This example file `messages.en_US.neon` would look like

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


Placeholders
------------

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
$this->translator->translate("Hello %name%", NULL, array('name' => $name));
```

And we should probably move it to catalogue file right away

```yml
homepage:
	helloName: "Hello %name%"
```

```php
$this->translator->translate('messages.homepage.helloName', NULL, array('name' => $name));
```

The translator will look for the given message, and replace the parameters after it finds the translation.


Pluralization
-------------

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


Templates
---------

You don't have to call this verbose method in templates, there is a macro prepared for you!
If you're not hacking the `Latte\Engine` creation in any way, it will work right away, because the extension will register it.

If not, you have to register it manually

```php
Kdyby\Translation\Latte\TranslateMacros::install($engine->compiler);
```

This macro needs one more thing - there must be registered class `Kdyby\Translation\TemplateHelpers` as a helper loader for your templates.

It's simple, all you have to do is add this to your `BasePresenter` and to your `BaseControl`, if you have any.

```php
protected function createTemplate($class = NULL)
{
	$template = parent::createTemplate($class);
	$template->registerHelperLoader(callback($this->translator->createTemplateHelpers(), 'loader'));

	return $template;
}
```

And that's all, you're ready to translate templates.

```smarty
<p>{_messages.homepage.hello}</p>
<p>{_messages.homepage.helloName, [name => $name]}</p>
<p>{_messages.homepage.applesCount, 10}</p>
```

