<?php

namespace Okvpn\Component\Fixture\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Okvpn\Component\Fixture\Entity\DataFixture;

class UpdateDataFixturesFixture extends AbstractFixture
{
    /**
     * @var array
     *  key - class name
     *  value - current loaded version
     */
    protected $dataFixturesClassNames;

    /**
     * Set a list of data fixtures to be updated
     *
     * @param array $classNames
     */
    public function setDataFixtures($classNames)
    {
        $this->dataFixturesClassNames = $classNames;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!empty($this->dataFixturesClassNames)) {
            $loadedAt = new \DateTime('now', new \DateTimeZone('UTC'));
            foreach ($this->dataFixturesClassNames as $className => $version) {
                $dataFixture = null;
                if ($version !== null) {
                    $dataFixture = $manager
                        ->getRepository('OkvpnFixtureBundle:DataFixture')
                        ->findOneBy(['className' => $className]);
                }
                if (!$dataFixture) {
                    $dataFixture = new DataFixture();
                    $dataFixture->setClassName($className);
                }

                $dataFixture
                    ->setVersion($version)
                    ->setLoadedAt($loadedAt);
                $manager->persist($dataFixture);
            }
            $manager->flush();
        }
    }
}
