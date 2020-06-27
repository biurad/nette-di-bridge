# This DependencyInjection is a bridge to nette/di adding powerful features to di.

The DependencyInjection service container is a powerful tool for managing class dependencies and performing dependency injection. Dependency injection is a fancy phrase that essentially means this: class dependencies are "injected" into the class via the constructor or, in some cases, "setter" methods.

Purpose of the Dependecy Injection (DI) is to free classes from the responsibility for obtaining objects that they need for its operation (these objects are called **services**). To pass them these services on their instantiation instead.

With the new features added to [nette/di](https://github.com/nette/di), DependencyInjection container can be used even without `CompilerExtensions`.

**`Please note that you can get the documentation for this dependency on [Nette website](https://doc.nette.org), dependency-injection`**

## Installation

The recommended way to install Nette DependencyInjection Container is via Composer:

```bash
composer require biurad/nette-di-bridge
```

It requires PHP version 7.1 and supports PHP up to 7.4. The dev-master version requires PHP 7.2.

## How To Use

A deep understanding of the DependencyInjection service container is essential to building a powerful, large application, as well as for contributing to the DependencyInjection core itself. This `README` is focused on the new features added to [nette/di](https://github.com/nette/di).

This dependency is an extended version of [nette/di](https://github.com/nette/di) which has been simplified for developer's convenient. With this bridge, more features have been implemented to have a fast and flexible DependencyInjection Container. An example of how to use DependencyInjection is available on the [GitHub](https://github.com/dg/di-example).

> Container implementation is fully compatible with [PSR-11 Container](https://github.com/php-fig/container).

### PSR-11 Container

You can always access container directly in your code by requesting `Psr\Container\ContainerInterface`:

```php
use Psr\Container\ContainerInterface;

class HomeContoller
{
    public function index(ContainerInterface $container)
    {
        var_dump($container->get(App\Kernel::class));
    }
}
```

### Binding

Almost all of your service container bindings will be registered within container and being cached, so most of these examples will demonstrate using the container in that context.

> There is no need to bind classes into the container if they do not depend on any interfaces. The container does not need to be instructed on how to build these objects, since it can automatically resolve these objects using reflection.

With [nette/di](https://github.com/nette/di), we use the `addService` to bind an instance or callable to DependencyInjection container, This feature is still available in this package, but a new `bind` method has been added.

For instance, we have a class named `UserMailer` implemented to an interface, and we want to bind this class to have constructor injections, then/or access it by instance, name or interface:

```php
class UserMailer implements UserMailerInterface
{
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function do()
    {
        $this->mailer->sendMail(...);
    }
}
```

Using [nette/di](https://github.com/nette/di) we can only bind the above class to access it's name and instance. But with this package, we can bind to access it by name, instance and interface.

> Nette Example

```php
use Nette\DI\Container;

$container = new Container();
$container->addService('user.mailer', $instance = $container->createInstance(UserMailer::class)); // We created a new service and instance.

$userMailer = $container->getService('user.mailer'); // Can be accessed by name
// or
$userMailer = $container->getByType(UserMailer::class); // Can be accessed by class name.

$userMailer = $instance; // accessed by instance
```

> This DependencyInjection Example

```php
use BiuradPHP\DependencyInjection\Container;

$container = new Container();
$container->bind('user.mailer', UserMailer::class); // We created a new service.

$userMailer = $container->get('user.mailer'); // Can be accessed by name.
// or
$userMailer = $container->get(UserMailer::class); // We can access it as instance if service exists or not.

$userMailer = $container->get(UserMailerInterface::class); // Can be accessed by interface
```

Also, we can bind `UserMailer` without including a name:

```php
use BiuradPHP\DependencyInjection\Container;

$container = new Container();
$container->bind(UserMailer::class); // We created a new service.

$userMailer = $container->get(UserMailer::class); // We can access it as instance if service exists or not.
// or
$userMailer = $container->get(UserMailerInterface::class); // accessed by interface
```

The above examples will only work, if the `Mailer` class exists in DependencyInjection container. Bindings can work on callables also. Note that we receive any service or interface found in container itself as an argument to the resolver passed in the second parameter of the `bind` method. We can then use the container to resolve sub-dependencies of the object or callables we are building.

### Automatic Dependency Resolution

DependencyInjection container is able to automatically resolve the constructor or method dependencies by providing instances
of concrete classes.

```php
class MyController
{
    public function __construct(OtherClass $class, SomeInterface $some)
    {
    }
}
```

In a provided example the container will attempt to provide the instance of `OtherClass` by automatically constructing it. However,
`SomeInterface` would not be resolved unless you have the proper binding in your container.

```php
$container->bind(SomeInterface::class, SomeClass::class);
```

Please note, Container will try to resolve _all_ constructor dependencies (unless you manually provide some values). It means that
all class dependencies must be available or parameter must be declared as optional:

```php
// will fail if `value` dependency not provided
__construct(OtherClass $class, $value)

// will use `null` as `value` if no other value provided
__construct(OtherClass $class, $value = null)

// will fail if SomeInterface does not point to the concrete implemenation
__construct(OtherClass $class, SomeInterface $some)

// will use null as value of `some` if no conrete implemation is provided
__construct(OtherClass $class, ?SomeInterface $some)
```

### Resolving

In some cases, you might want to construct desired class without resolving all of it's `__constructor` dependencies. You can use `BiuradPHP\DependencyInjection\Interfaces\FactoryInterface` or `BiuradPHP\DependencyInjection\Container` for that purpose:

```php
public function makeClass(FactoryInterface $factory)
{
    return $factory->make(MyClass::class, [
        'parameter' => 'value'
        // other dependencies will be resolved automatically
    ]);
}
```

Alternatively, and importantly, you may "type-hint" the dependency in the constructor of a class that is resolved by the container, including controllers, event listeners, middleware, and more. Additionally, you may type-hint dependencies in the classes method In practice, this is how most of your objects should be resolved by the container.

### IoC Scopes - Private Service

An important aspect of developing long-living applications is proper context management. In daemonized applications, you are no longer allowed to treat user request as global singleton object and store references to its instance in your services.

Practically it means that you must explicitly request context while processing user input. DependencyInjection simplifies such requests by using global IoC container as context carrier which allows you to call request specific instances as global objects via context bounded scopes.

While processing some user request the context-specific data is located in the IoC scope, available in the container only for a limited period of time. Such operation is performed using the `BiuradPHP\DependencyInjection\Container` `runScope` method.

```php
$container->runScope(
    [
        UserContext::class => $user
    ],
    function () use ($container) {
        var_dump($container->get(UserContext::class);
    }
);
```

> DependencyInjection will guarantee that scope is clean after the execution, even in case of any exception.

You can receive values set in scope directly from the container or as method injections in your services/controllers while calling then **inside the IoC scope**:

```php
public function doSomething(UserContext $user)
{
    var_dump($user);
}
```

In short, you can receive active context from container or injection inside the IoC scope as you would normally do for any normal dependency but you **must not store** it between scopes.


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
