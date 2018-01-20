<?php

namespace Okvpn\Component\Fixture\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class FixtureDatabaseChecker
{
    /**
     * @param Connection $connection
     * @param string[]|string|null $tables
     *
     * @return bool
     * @internal
     */
    public static function tablesExist(Connection $connection, $tables)
    {
        $result = false;
        if (!empty($tables)) {
            try {
                $connection->connect();
                $result = $connection->getSchemaManager()->tablesExist($tables);
            } catch (\PDOException $e) {
            } catch (DBALException $e) {
            }
        }

        return $result;
    }

    /**
     * @param Connection $connection
     * @param $tables
     *
     * @internal
     */
    public static function declareTable(Connection $connection, $tables)
    {
        $schemaManager = $connection->getSchemaManager();

        $schema = new FixtureSchema($connection, $tables);
        foreach ($schema->getTables() as $table) {
            $schemaManager->createTable($table);
        }
    }
}
