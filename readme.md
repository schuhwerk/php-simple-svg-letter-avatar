# PHP Simple svg letter avatar

You need a simple way to add avatars for your users?
Wait no more!

Use a dynamically generated avatar, which does not require an actual image file:

```php
$name = "Jane Doe";
$url  = "example.com/avatar.php?name=$username";
$img  = "<img src='$url' />";
```

The generated avatar is an SVG, they can be used as image source in every modern browser: https://caniuse.com/svg-img.
⚠ It's not yet battle-tested, so you might still run into some issues. PR's are welcome :)

## But how? Installation

```
composer install schuhwerk/php-simple-svg-letter-avatar
```

Then in your php file (which sits in example.com/avatar.php):

```php
require_once 'vendor/autoload.php';
(new Svg_Letter_Avatar())->serve();
```

Now you can go to example.com/avatar.php?name=John Doe and get an avatar image.

## Configuration

Base configuration can be done via the constructor. Check to code for more settings (wip).
```php
new Svg_Letter_Avatar( args(
	'letters_fallback' => array( '🤷‍♀️', '🤷‍♂️' ), // a random one is chosen if no name is given or it's empty.
	'palette' => array( '#fff', '#000' ), // background colors, a random one is chosen. Black or white foreground is added automatically, based on lightness of the given background.
	'cache_days' => 356, // keep in user's browser cache for a year. Keep it low if you are still trying!
	'font_family' => 'Arial',
	'...' => '...'

))
```

## More

### Should I use the full name in the query-string?
The randomness for choosing a background color is seeded (by default). 
So the same user (with the same name) receives the same image. (You can configure other seeds, too.)
If you don't pass the full name (but initials) 'Jane Doe' will have the same background color as 'John Doe',
as both have the initials 'JD'.