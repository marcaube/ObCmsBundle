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
- Add CRUD actions for an entity in 5 lines of YAML
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

3. Register the bundle in your `app/AppKernel.php`:
``` php
    <?php
    ...
    public function registerBundles()
    {
        $bundles = array(
            ...
            new Ob\CmsBundle\ObCmsBundle(),
            ...
        );
    ...
```

4. Create a new config file, that you then import in `app/config.yml`
``` yaml
    # app/config/vendor/ob_cms.yml
    ob_cms:
        bundles:
          guitar: # This will be the name in the menu and the prefix for translations
              repository:  ObCmsDemoBundle:Guitar # The repo from wich to query
              entity:      Ob\CmsDemoBundle\Entity\Guitar # The entity class
              listDisplay: [name, brand, strings, price, online] # The fields displayed in the listing
              formDisplay: [name, brand, strings, price, online] # The fields displayed in the forms

    # app/config/config.yml
    imports:
        - { resource: vendor/config_ob.yml }
```

5. For the pagination to work properly, add this somewhere in your config
``` yaml
    knp_paginator:
        default_options:
            page_name: p
        template:
            pagination: ObCmsBundle:Paginator:sliding.html.twig
```

6. Last thing but not least is to import the routing
``` yaml
    # app/config/routing.yml
    ob_cms:
        resource: "@ObCmsBundle/Resources/config/routing.yml"
        prefix:   /the-admin-prefix-of-your-choice
```

## All options
``` yaml
    ob_cms:
        itemsPage: 50 # Set the default number of items per page at 50
        locales: [%locale%, ru] # Add russian to the list of locales, on top of the default locale
    
        bundles:
            acme: # the entity will be registered under this name
                itemsPage: 20 # Override the number of items par page for this entity
                repository: AcmeHelloBundle:Hello # The repository entities are queried from
                entity: AcmeHelloBundle:Hello # The repository entities are queried from
                listDisplay: [title, author, createdAt] # The fields to display on the listing page
                listLinks: [title] # Fields that link to the edit page, the first one is a link by default
                listSort: [title, author, createdAt] # The list of sortable fields
                listSearch: [title, createdAt] # The list of fields include in the textSearch, doesn't support foreign keys
                listActions: [publish] # A list of entity's function that can be used as actions in the listing
                formDisplay: [title, author, text] # The fields to display in the create and edit form
                listTemplate: AcmeHelloBundle:crud:index.html.twig # The template to use for this entity's listing page
                newTemplate: AcmeHelloBundle:crud:new.html.twig # The template to use for this entity's creation page
                editTemplate: AcmeHelloBundle:crud:edit.html.twig # The template to use for this entity's edit page
                listController: AcmeHelloBundle:Default:index # The controller action to handle the index action
                newController: AcmeHelloBundle:Default:new # The controller action to handle the new action
                createController: AcmeHelloBundle:Default:create # The controller action to handle the create action
                editController: AcmeHelloBundle:Default:edit # The controller action to handle the edit action
                updateController: AcmeHelloBundle:Default:update # The controller action to handle the update action
```
