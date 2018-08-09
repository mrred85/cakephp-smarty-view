# CakePHP Smarty View

### Requirements
- PHP >= 7.1.x
- CakePHP >= 3.6.x

## Install Smarty

### Manual
Download Smarty from [here](https://github.com/smarty-php/smarty/archive/master.zip)
Extract it to `app/vendor/smarty`

### With composer (required)
```bash
composer require smarty/smarty
```

## Install Smarty View
Copy the `SmartyView.php` file from `src > View` folder to your **View** folder.

## Include in AppController.php
Load `SmartyView` in your CakePHP project like in `AppController.php` file or below:
```php
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

class AppController extends Controller
{
    // Your code
    public function beforeRender(Event $event)
    {
        $this->viewBuilder()->setClassName('Smarty'); // SET SMARTY VIEW
    }
    // Your code
}
```

## Rename templates
Rename all template files with **.ctp** extension in **.tpl** extension.

**IMPORTANT:** You can rename all, not just the templates and include all with Smarty include `{include file="_file_.tpl"}` syntax.

## Include in layouts
`{$this->fetch('content')}`

Enjoy ;)
