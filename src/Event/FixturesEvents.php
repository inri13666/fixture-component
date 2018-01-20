<?php

namespace Okvpn\Component\Fixture\Event;

final class FixturesEvents
{
    /**
     * This event is raised before data fixtures are loaded.
     *
     * @var string
     */
    const DATA_FIXTURES_PRE_LOAD = 'okvpn.data_fixtures.pre_load';

    /**
     * This event is raised after data fixtures are loaded.
     *
     * @var string
     */
    const DATA_FIXTURES_POST_LOAD = 'okvpn.data_fixtures.post_load';
}
