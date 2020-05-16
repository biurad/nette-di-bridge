# This is a bridge to nette/di adding powerful features to di.

The DependencyInjection service container is a powerful tool for managing class dependencies and performing dependency injection. Dependency injection is a fancy phrase that essentially means this: class dependencies are "injected" into the class via the constructor or, in some cases, "setter" methods.

Purpose of the Dependecy Injection (DI) is to free classes from the responsibility for obtaining objects that they need for its operation (these objects are called **services**). To pass them these services on their instantiation instead.

**`Please note that you can get the documentation for this dependency on Nette website, di`**

## Installation

The recommended way to install DependencyInjection is via Composer:

```bash
composer require biurad/nette-di-bridge
```

It requires PHP version 7.0 and supports PHP up to 7.4. The dev-master version requires PHP 7.1.

## How To Use

A deep understanding of the DependencyInjection service container is essential to building a powerful, large application, as well as for contributing to the DependencyInjection core itself.

This dependency is an extended version of [nette/di](https://github.com/nette/di) which has been simplified for developer's convenient. With this bridge, you don't have to register each class as service in order to use in anymore. It also support private services. An example of how to use DependencyInjection is available on the [GitHub](https://github.com/dg/di-example).

### Binding Basics

Almost all of your service container bindings will be registered within container and being cached, so most of these examples will demonstrate using the container in that context.

> There is no need to bind classes into the container if they do not depend on any interfaces. The container does not need to be instructed on how to build these objects, since it can automatically resolve these objects using reflection.

### Simple Bindings

Within a service provider, you always have access to the container via the `$container` property. We can register a binding using the `bind` method, passing the class or interface name that we wish to register along with a `Closure` that returns an instance of the class:

```php
$container->bind('HelpSpot\API', function (ContainerInterface $app) {
    return new HelpSpot\API($app->get('HttpClient'));
});
```

Note that we receive the container itself as an argument to the resolver. We can then use the container to resolve sub-dependencies of the object we are building.

### Binding Instances

You may also bind an existing object instance into the container using the `addService` method. The given instance will always be returned on subsequent calls into the container:

```php
$api = new HelpSpot\API(new HttpClient);

$container->addService('HelpSpot\API', $api);
```

A very powerful feature of the service container is its ability to bind an interface to a given implementation. For example, let's assume we have an `EventPusher` interface and a `RedisEventPusher` implementation. Once we have coded our `RedisEventPusher` implementation of this interface, the interfaces and parent classes will be automatically registered using the `addService` method.

### Contextual Binding

Sometimes you may have two classes that utilize the same interface, but you wish to inject different implementations into each class. For example, two controllers may depend on different implementations of same interface. The simple solution is to create another interface for the other class.

### Tagging

Occasionally, you may need to resolve all of a certain "category" of binding. For example, perhaps you are building a report aggregator that receives an array of many different `Report` interface implementations. After registering the `Report` implementations, you can assign them a tag using the `tag` method

Once the services have been tagged, you may easily resolve them all via the `findByTag` method.

### Resolving

You may use the `make` method to resolve a class instance out of the container. The `make` method accepts the name of the class or interface you wish to resolve:

```php
$api = $container->get('HelpSpot\API');
```

If some of your class' dependencies are not resolvable via the container, you may inject them by passing them as an associative array into the `make` method:

```php
$api = $container->make('HelpSpot\API', ['id' => 1]);
```

Alternatively, and importantly, you may "type-hint" the dependency in the constructor of a class that is resolved by the container, including controllers, event listeners, middleware, and more. Additionally, you may type-hint dependencies in the classes method In practice, this is how most of your objects should be resolved by the container.

### Private Service

This creates and run a new service in container, but it doesn't registers it into container, you may easily resolve them all via the `runScope` method.

### PSR-11

DependencyInjection service container implements the [PSR-11](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md) interface. Therefore, you may type-hint the PSR-11 container interface to obtain an instance of the DependencyInjection container:

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing

To run the tests you'll have to start the included node based server if any first in a separate terminal window.

With the server running, you can start testing.

```bash
vendor/bin/phpunit
```

## Security

If you discover any security related issues, please report using the issue tracker.
use our example [Issue Report](.github/ISSUE_TEMPLATE/Bug_report.md) template.

## Want to be listed on our projects website

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a message on our website, mentioning which of our package(s) you are using.

Post Here: [Project Patreons - https://patreons.biurad.com](https://patreons.biurad.com)

We publish all received request's on our website;

## Credits

- [Divine Niiquaye](https://github.com/divineniiquaye)
- [All Contributors](https://biurad.com/projects/nette-di-bridge/contributers)

## Support us

`Biurad Lap` is a technology agency in Accra, Ghana. You'll find an overview of all our open source projects [on our website](https://biurad.com/opensource).

Does your business depend on our contributions? Reach out and support us on to build more project's. We want to build over one hundred project's in two years. [Support Us](https://biurad.com/donate) achieve our goal.

Reach out and support us on [Patreon](https://www.patreon.com/biurad). All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

[Thanks to all who made Donations and Pledges to Us.](.github/ISSUE_TEMPLATE/Support_us.md)

## License

The BSD-3-Clause . Please see [License File](LICENSE.md) for more information.

