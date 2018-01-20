# Data fixtures

TODO: Add description



### Silex 1.2 Example

```php
<?php

use Okvpn\Component\Fixture\Command\Helper\FixtureConsoleHelper;
use Symfony\Component\{
    Console\Application,
    Console\Helper\HelperSet,
    Console\Helper\QuestionHelper,
    Console\Output\ConsoleOutput
};

// Application
$app = new \Silex\Application();

//.... Silex Application configuration
// for orm.em see here http://dflydev.com/projects/doctrine-orm-service-provider/

$application = new Application();
$helperSet = new HelperSet();
$application->setHelperSet($helperSet);
$helperSet->set(new QuestionHelper(), 'dialog');
$helper = new FixtureConsoleHelper($app->offsetGet('orm.em'), $app->offsetGet('dispatcher'), 'okvpn_fixture_data');
$helperSet->set($helper);

$application->addCommands([
    new Okvpn\Component\Fixture\Command\LoadDataFixturesCommand(),
]);

$application->run();
```
