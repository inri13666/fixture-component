<?php

namespace Okvpn\Component\Fixture\Migration\Loader;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManager;
use Okvpn\Component\Fixture\Entity\DataFixture;
use Okvpn\Component\Fixture\Fixture\LoadedFixtureVersionAwareInterface;
use Okvpn\Component\Fixture\Fixture\VersionedFixtureInterface;
use Okvpn\Component\Fixture\Migration\Sorter\DataFixturesSorter;
use Okvpn\Component\Fixture\Migration\UpdateDataFixturesFixture;

class DataFixturesLoader extends Loader
{
    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $loadedFixtures;

    /** @var \ReflectionProperty */
    protected $ref;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @inheritdoc
     */
    public function getFixtures()
    {
        $sorter = new DataFixturesSorter();
        $fixtures = $sorter->sort($this->getAllFixtures());

        // remove already loaded fixtures
        foreach ($fixtures as $key => $fixture) {
            if ($this->isFixtureAlreadyLoaded($fixture)) {
                unset($fixtures[$key]);
            }
        }

        // add a special fixture to mark new fixtures as "loaded"
        if (!empty($fixtures)) {
            $toBeLoadFixtureClassNames = [];
            foreach ($fixtures as $fixture) {
                $version = null;
                if ($fixture instanceof VersionedFixtureInterface) {
                    $version = $fixture->getVersion();
                }
                $toBeLoadFixtureClassNames[get_class($fixture)] = $version;
            }

            $updateFixture = new UpdateDataFixturesFixture();
            $updateFixture->setDataFixtures($toBeLoadFixtureClassNames);
            $fixtures[get_class($updateFixture)] = $updateFixture;
        }

        return $fixtures;
    }

    /**
     * Determines whether the given data fixture is already loaded or not
     *
     * @param object $fixtureObject
     *
     * @return bool
     */
    protected function isFixtureAlreadyLoaded($fixtureObject)
    {
        if (!$this->loadedFixtures) {
            $this->loadedFixtures = [];

            $loadedFixtures = $this->em->getRepository(DataFixture::class)->findAll();
            /** @var DataFixture $fixture */
            foreach ($loadedFixtures as $fixture) {
                $this->loadedFixtures[$fixture->getClassName()] = $fixture->getVersion() ?: '0.0';
            }
        }

        $alreadyLoaded = false;

        if (isset($this->loadedFixtures[get_class($fixtureObject)])) {
            $alreadyLoaded = true;
            $loadedVersion = $this->loadedFixtures[get_class($fixtureObject)];
            if ($fixtureObject instanceof VersionedFixtureInterface
                && version_compare($loadedVersion, $fixtureObject->getVersion()) == -1
            ) {
                if ($fixtureObject instanceof LoadedFixtureVersionAwareInterface) {
                    $fixtureObject->setLoadedVersion($loadedVersion);
                }
                $alreadyLoaded = false;
            }
        }

        return $alreadyLoaded;
    }

    /**
     * @return array
     */
    protected function getAllFixtures()
    {
        if (!$this->ref) {
            $this->ref = new \ReflectionProperty('Doctrine\Common\DataFixtures\Loader', 'fixtures');
            $this->ref->setAccessible(true);
        }

        return $this->ref->getValue($this);
    }
}
