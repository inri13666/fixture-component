<?php

namespace Okvpn\Component\Fixture\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\SequenceGenerator;
use Okvpn\Component\Fixture\Entity\DataFixture;

class ConfigurationResolver
{
    const DEFAULT_TABLE = 'okvpn_fixture_data';

    /** @var null|string */
    private $table;

    /**
     * @param string $table
     */
    public function __construct($table = null)
    {
        $this->table = $table;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();
        if (null !== $this->table && $metadata->getName() === DataFixture::class) {
            $table = $metadata->table;

            if ($this->table === self::DEFAULT_TABLE) {
                return;
            }
            $table['name'] = $this->table;
            $metadata->setPrimaryTable($table);

            $definition = $metadata->sequenceGeneratorDefinition;
            if ($definition === null) {
                return;
            }

            $definition['sequenceName'] = str_replace(
                self::DEFAULT_TABLE,
                $this->table,
                $definition['sequenceName']
            );

            $em = $eventArgs->getEntityManager();
            $sequenceName = $em->getConfiguration()->getQuoteStrategy()->getSequenceName(
                $definition,
                $metadata,
                $em->getConnection()->getDatabasePlatform()
            );

            $sequenceGenerator = new SequenceGenerator($sequenceName, $definition['allocationSize']);
            $metadata->sequenceGeneratorDefinition = $definition;
            $metadata->setIdGenerator($sequenceGenerator);
        }
    }
}
