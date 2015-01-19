[![SensioLabsInsight](https://insight.sensiolabs.com/projects/eac79c7f-ad61-477f-bb2f-4f1f34b7dcb1/mini.png)](https://insight.sensiolabs.com/projects/eac79c7f-ad61-477f-bb2f-4f1f34b7dcb1)


### What this bundle is not …
- Production ready
- An example of beautiful code or best practices
- A weapon against zombies
- A bundle for power users
 
###What it is …
- A simple way to manage entities without writing too much code
- A playground to become a better PHP dev

### Features
- Add CRUD actions for an entity in a couple of php lines
- Row and multi-row actions, search, filters and pagination
- Clean and simple UI

### What it does not
- Handle entity relations
- Dashboard widgets
- Security/Authentication
- Solve world hunger


***


## Installation

* Run `composer require ob/cms-bundle`

* Register the bundles in your `app/AppKernel.php`:

```php
<?php
...
public function registerBundles()
{
    $bundles = array(
        ...
        new Ob\CmsBundle\ObCmsBundle(),
        new Mopa\Bundle\BootstrapBundle\MopaBootstrapBundle(),
        new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
        new Liuggio\ExcelBundle\LiuggioExcelBundle(),
        ...
    );
...
```

* Add configuration for Mopa Bundle

```yaml
# app/config/config.yml
mopa_bootstrap:
    form: ~
```

* Last thing but not least is to import the routing

```yaml
# app/config/routing.yml
ob_cms:
    resource: "@ObCmsBundle/Resources/config/routing.yml"
    prefix:   /the-admin-prefix-of-your-choice
```

## Create an Admin class
To use the Cms, you must create an Admin class somewhere in your bundle. For complete list of options, dive in the Admin
class code, it's pretty simple.

```php
<?php

namespace Ob\CmsDemoBundle\Admin;

use Ob\CmsBundle\Admin\AbstractAdmin as Admin;

class GuitarAdmin extends Admin
{
    public function __construct()
    {
        $this->class = 'Ob\CmsDemoBundle\Entity\Guitar';
    }

    public function listDisplay()
    {
        return array('name', 'brand', 'strings', 'price', 'online');
    }

    public function formDisplay()
    {
        return array('name', 'brand', 'strings', 'price', 'online');
    }
}
```

And then register your new admin class as a tagged service. The `alias` tag is used for the menu and the translation
prefix.

```yaml
# Ob/CmsDemoBundle/Resources/services.yml
services:
    ob_cms_demo.guitar.admin:
        class: Ob\CmsDemoBundle\Admin\GuitarAdmin
        tags:
            -  { name: ob.cms.admin, alias: guitar }
```
