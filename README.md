![Listing Page](/Resources/doc/images/listing.png?raw=true)

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
1. Add this line to your `composer.json`
``` yaml
    "require": {
        ...
        "ob/cms-bundle": "dev-master",
        ...
    },
```

2. Run `php composer.phar update "ob/cms-bundle"`

3. Register the bundles in your `app/AppKernel.php`:
``` php
    <?php
    ...
    public function registerBundles()
    {
        $bundles = array(
            ...
            new Ob\CmsBundle\ObCmsBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            ...
        );
    ...
```

4. Last thing but not least is to import the routing
``` yaml
    # app/config/routing.yml
    ob_cms:
        resource: "@ObCmsBundle/Resources/config/routing.yml"
        prefix:   /the-admin-prefix-of-your-choice
```

## Create an Admin class
To use the Cms, you must create an Admin class somewhere in your bundle. For complete list of options, dive in the Admin class code, it's pretty simple.
``` php
<?php

namespace Ob\CmsDemoBundle\Admin;

use Ob\CmsBundle\Admin\AbstractAdmin as Admin;

class GuitarAdmin extends Admin
{
    public function __construct()
    {
        $this->repository = 'ObCmsDemoBundle:Guitar';
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

And then register your new admin class as a tagged service. The `alias` tag is used for the menu and the translation prefix.
``` yaml
# Ob/CmsDemoBundle/Resources/services.yml
services:
    ob_cms_demo.guitar.admin:
        class: Ob\CmsDemoBundle\Admin\GuitarAdmin
        tags:
            -  { name: ob.cms.admin, alias: guitar }
```
