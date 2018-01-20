<?php

namespace Okvpn\Component\Fixture\Migration;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManager;
use Okvpn\Component\Fixture\Event\DataFixturesEvent;
use Okvpn\Component\Fixture\Event\FixturesEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class DataFixturesExecutor implements DataFixturesExecutorInterface
{
    /** @var EntityManager */
    private $em;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var callable|null */
    private $logger;

    /**
     * @param EntityManager $em
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EntityManager $em, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $fixtures, $fixturesType)
    {
        $event = new DataFixturesEvent($this->em, $fixturesType, $this->logger);
        $this->eventDispatcher->dispatch(FixturesEvents::DATA_FIXTURES_PRE_LOAD, $event);

        $executor = new ORMExecutor($this->em);
        if (null !== $this->logger) {
            $executor->setLogger($this->logger);
        }
        $executor->execute($fixtures, true);

        $this->eventDispatcher->dispatch(FixturesEvents::DATA_FIXTURES_POST_LOAD, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
