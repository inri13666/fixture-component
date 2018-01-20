<?php

namespace Okvpn\Component\Fixture\Command\Helper;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Okvpn\Component\Fixture\Entity\DataFixture;
use Okvpn\Component\Fixture\EventListener\ConfigurationResolver;
use Okvpn\Component\Fixture\Migration\DataFixturesExecutor;
use Okvpn\Component\Fixture\Migration\Loader\DataFixturesLoader;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FixtureConsoleHelper extends Helper
{
    const NAME = 'okvpn_fixtures';

    /** @var EntityManager */
    protected $doctrine;

    /** @var string */
    protected $fixtureTable;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DataFixturesExecutor */
    protected $dataFixturesExecutor;

    /** @var DataFixturesLoader */
    protected $dataFixturesLoader;

    /**
     * @param EntityManager $em
     * @param EventDispatcherInterface $eventDispatcher
     * @param $fixtureTable
     */
    public function __construct(EntityManager $em, EventDispatcherInterface $eventDispatcher, $fixtureTable)
    {
        $this->doctrine = $em;
        $this->fixtureTable = $fixtureTable;
        $this->eventDispatcher = $eventDispatcher;

        //Configure Doctrine Events
        $this->doctrine->getEventManager()->addEventListener(
            Events::loadClassMetadata,
            new ConfigurationResolver($this->fixtureTable)
        );

        $metaDriver = $this->doctrine->getConfiguration()->getMetadataDriverImpl();

        if (!$metaDriver instanceof MappingDriverChain) {
            $metaDriverChain = new MappingDriverChain();
            $metaDriverChain->setDefaultDriver($metaDriver);
        } else {
            $metaDriverChain = $metaDriver;
        }

        $namespace = 'Okvpn\Component\Fixture\Entity';
        if (!in_array($namespace, $metaDriverChain->getAllClassNames())) {
            $reflection = new \ReflectionClass(DataFixture::class);
            $metaDriverChain->addDriver(
                new AnnotationDriver(new AnnotationReader(), [dirname($reflection->getFileName())]), $namespace
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return EntityManager
     */
    public function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @return DataFixturesExecutor
     */
    public function getDataFixturesExecutor()
    {
        if (!$this->dataFixturesExecutor) {
            $this->dataFixturesExecutor = new DataFixturesExecutor($this->doctrine, $this->eventDispatcher);
        }

        return $this->dataFixturesExecutor;
    }

    /**
     * @return DataFixturesLoader
     */
    public function getDataFixturesLoader()
    {
        if (!$this->dataFixturesLoader) {
            $this->dataFixturesLoader = new DataFixturesLoader($this->doctrine);
        }

        return $this->dataFixturesLoader;
    }

    /**
     * @return string
     */
    public function getFixtureTable()
    {
        return $this->fixtureTable;
    }
}
