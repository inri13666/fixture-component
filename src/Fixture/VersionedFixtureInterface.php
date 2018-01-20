<?php

namespace Okvpn\Component\Fixture\Fixture;

interface VersionedFixtureInterface
{
    /**
     * Return current fixture version
     *
     * @return string
     */
    public function getVersion();
}
