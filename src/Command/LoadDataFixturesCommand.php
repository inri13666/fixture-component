<?php

namespace Okvpn\Component\Fixture\Command;

use Okvpn\Component\Fixture\Command\Helper\FixtureConsoleHelper;
use Okvpn\Component\Fixture\Migration\DataFixturesExecutorInterface;
use Okvpn\Component\Fixture\Tools\FixtureDatabaseChecker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadDataFixturesCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'okvpn:fixtures:data:load';

    const MAIN_FIXTURES_TYPE = DataFixturesExecutorInterface::MAIN_FIXTURES;
    const DEMO_FIXTURES_TYPE = DataFixturesExecutorInterface::DEMO_FIXTURES;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::COMMAND_NAME)
            ->setAliases(['okvpn:fixture:data:load'])
            ->setDescription('Load data fixtures.')
            ->addOption(
                'path',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'A list of paths to load data from'
            )
            ->addOption(
                'fixtures-type',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Select fixtures type to be loaded (%s or %s). By default - %s',
                    self::MAIN_FIXTURES_TYPE,
                    self::DEMO_FIXTURES_TYPE,
                    self::MAIN_FIXTURES_TYPE
                ),
                self::MAIN_FIXTURES_TYPE
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs list of fixtures without apply them');
    }

    /**
     * @return FixtureConsoleHelper
     */
    protected function getOkvpnHelper()
    {
        return $this->getHelper(FixtureConsoleHelper::NAME);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!$input->getOption('path')){
            throw new \Exception('Option "path" required');
        }

        $fixtures = null;
        $this->ensureTableExist();
        try {
            $fixtures = $this->getFixtures($input, $output);
        } catch (\RuntimeException $ex) {
            $output->writeln('');
            $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));

            return $ex->getCode() == 0 ? 1 : $ex->getCode();
        }

        if (!empty($fixtures)) {
            if ($input->getOption('dry-run')) {
                $this->outputFixtures($input, $output, $fixtures);
            } else {
                $this->processFixtures($input, $output, $fixtures);
            }
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return array
     * @throws \RuntimeException if loading of data fixtures should be terminated
     */
    protected function getFixtures(InputInterface $input, OutputInterface $output)
    {
        $loader = $this->getOkvpnHelper()->getDataFixturesLoader();
        $fixtureRelativePath = $this->getFixtureRelativePath($input);
        $paths = $input->getOption('path');
        foreach ($paths as $path) {
            $path = implode(DIRECTORY_SEPARATOR, [$path, $fixtureRelativePath]);
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $path = rtrim($path, DIRECTORY_SEPARATOR);

            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        return $loader->getFixtures();
    }

    /**
     * Output list of fixtures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $fixtures
     */
    protected function outputFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'List of "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );
        foreach ($fixtures as $fixture) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', get_class($fixture)));
        }
    }

    /**
     * Process fixtures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $fixtures
     */
    protected function processFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'Loading "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );

        $executor = $this->getOkvpnHelper()->getDataFixturesExecutor();
        $executor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $executor->execute($fixtures, $this->getTypeOfFixtures($input));
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getTypeOfFixtures(InputInterface $input)
    {
        return $input->getOption('fixtures-type');
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureRelativePath = $this->getTypeOfFixtures($input) === self::DEMO_FIXTURES_TYPE
            ? 'Migrations/Data/Demo/ORM'
            : 'Migrations/Data/ORM';

        return str_replace('/', DIRECTORY_SEPARATOR, '/' . $fixtureRelativePath);
    }

    protected function ensureTableExist()
    {
        $table = $this->getOkvpnHelper()->getFixtureTable();
        $connection = $this->getOkvpnHelper()->getDoctrine()->getConnection();

        if (!FixtureDatabaseChecker::tablesExist($connection, $table)) {
            FixtureDatabaseChecker::declareTable($connection, $table);
        }
    }
}
