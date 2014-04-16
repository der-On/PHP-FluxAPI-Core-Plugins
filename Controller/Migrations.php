<?php
namespace Plugins\FluxAPI\Core\Controller;

use \FluxAPI\Query;

class Migrations extends \FluxAPI\Controller
{
    public static function getActions()
    {
        return array(
            'run',
            'rollback'
        );
    }

    private function _getMigrationsPath()
    {
        return realpath(__DIR__ . '/../../../../migrations');
    }

    private function _getExistingMigrations()
    {
        $existing_migrations = array();
        $migrations_path = $this->_getMigrationsPath();
        $files = scandir($migrations_path);

        foreach($files as $file) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }

            if (!is_dir($migrations_path . '/' . $file) &&
                substr($file, -strlen('.php')) == '.php') {
                $existing_migrations[] = $file;
            }
        }

        return $existing_migrations;
    }

    private function _getDoneMigrations()
    {
        $migrations = $this->_api->loadMigrations();
        $done_migrations = array();

        foreach($migrations as $migration)
        {
            $done_migrations[] = $migration->migration;
        }

        return $done_migrations;
    }



    public function run($migration = null)
    {
        $done_migrations = $this->_getDoneMigrations();
        $existing_migrations = $this->_getExistingMigrations();
        $migrations_path = $this->_getMigrationsPath();

        if ($migration) {
            if (!in_array($migration, $done_migrations)) {
                $full_path = $migrations_path . '/' . $migration;
                if (file_exists($full_path)) {
                    echo 'Running migration ' . $migration . ' ...';
                    $this->_api->log('Running migration ' . $migration . ' ...');
                    require($full_path);

                    if (function_exists('up')) {
                        up($this->_api);

                        // add migration to DB
                        $migration_model = $this->_api->createMigration(array(
                            'migration' => $migration,
                            'createdAt' => new \DateTime()
                        ));
                        $this->_api->saveMigration($migration_model);
                    }
                    else {
                        throw new Exception('Migration ' . $migration . ' does not contain an up() function.');
                    }
                }
            }
        }
        else {
            foreach($existing_migrations as $migration) {
                $this->run($migration);
            }
        }

        return true;
    }

    public function rollback($migration = null)
    {
        $done_migrations = $this->_getDoneMigrations();
        $existing_migrations = $this->_getExistingMigrations();
        $migrations_path = $this->_getMigrationsPath();

        if ($migration) {
            if (!in_array($migration, $done_migrations)) {
                $full_path = $migrations_path . '/' . $migration;
                if (file_exists($full_path)) {
                    echo 'Rolling back migration ' . $migration . ' ...';
                    $this->_api->log('Rolling back migration ' . $migration . ' ...');
                    require($full_path);

                    if (function_exists('down')) {
                        down($this->_api);

                        // remove migration from DB
                        $query = new Query();
                        $query->filter('=', array('migration', $migration));
                        $this->_api->deleteMigration($query);
                    }
                    else {
                        throw new Exception('Migration ' . $migration . ' does not contain a down() function.');
                    }
                }
            }
        }
        else {
            // rollback last migration
            $this->rollback(array_pop($existing_migrations));
        }

        return true;
    }
}