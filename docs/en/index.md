# Quickstart

This extension is here to provide robust translation system for Nette Framework.
It implements `Nette\Localization\ITranslator` using [Symfony/Translation](https://github.com/symfony/Translation).


## Installation

The best way to install Kdyby/Translation is using  [Composer](http://getcomposer.org/):

With Nette stable 2.2, this is how you install the extension

```sh
$ composer require kdyby/translation:~2.0
```

and you can enable the extension using your neon config

```yml
extensions:
	translation: Kdyby\Translation\DI\TranslationExtension
```

You might also wanna add the `@dev` stability flag if you like to live on the edge.



And if you're using old stable Nette `2.0.*`, there is release for you but it's not developed anymore, only bugfix patches are being released.

```sh
$ composer require kdyby/translation:~0.10
```

and you have to register it in `app/bootstrap.php`


```php
Kdyby\Translation\DI\TranslationExtension::register($configurator);

return $configurator->createContainer();
```


## Setup

We have to somehow tell the translator, what is the language, that the user want's to see the website in.
You should define persistent parameter `$locale` in your presenter. That way you can keep in url the language, or read it from the url.
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

There is also an interface `IUserLocaleResolver` and few default implementations, they try to figure out, what language does the visitor wanna use the website in.
The first one looks in request parameters and searches for `locale`, that's why there is the persistent parameter and example route.
If it fails, it tries to look at `Accept-Language` header, and if that fails, it fallbacks to default locale.

To change the default language, place this in your `app/config/config.neon`

```yml
translation:
	default: cs
	whitelist: [cs, en, de] #....
	fallback: [cs_CZ, cs]
```

Whitelist has default value `[cs, en]`, it's used in compile-time and prevents loading of resources that will not be used.


## Usage

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
If you're not hacking the `Latte\Engine` creation in any way, it will work right away, because the extension will register it.

If not, you have to register it manually

```php
Kdyby\Translation\Latte\TranslateMacros::install($engine->compiler);
```

This macro needs one more thing - there must be registered class `Kdyby\Translation\TemplateHelpers` as a helper loader for your templates.

It's simple, all you have to do is add this to your `BasePresenter` and to your `BaseControl`, if you have any.
But you don't have to do this, if you've registered the `TranslationExtension`, it registers the helpers automatically.

```php
protected function createTemplate($class = NULL)
{
	$template = parent::createTemplate($class);

	$this->translator->createTemplateHelpers()
		->register($template->getLatte());

	return $template;
}
```

And that's all, you're ready to translate templates.

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

### Locale resolvers

There are several resolvers that take care of figuring out what locale should be used in translator as default.

Special case of resolvers is DefaultLocaleResolver that is configured by `translation: default:`

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
