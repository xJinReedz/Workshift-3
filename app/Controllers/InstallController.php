<?php
/**
 * Installation / Migration Web Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Database\Migrator;
use WorkShift\Core\Database;
use Exception;

class InstallController extends Controller
{
    public function index(Request $request): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $isInstalled = false;
        $error = null;

        try {
            Migrator::createDatabaseIfNotExists($config['database']);
            $pdo = Database::getConnection();
            $migrator = new Migrator($pdo, $config['database']);
            $isInstalled = $migrator->isInstalled();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $this->view('install.index', [
            'pageTitle' => 'WorkShift Setup & Database Installation',
            'isInstalled' => $isInstalled,
            'dbConfig' => $config['database'],
            'error' => $error,
        ], 'public');
    }

    public function run(Request $request): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';

        try {
            Migrator::createDatabaseIfNotExists($config['database']);
            $pdo = Database::getConnection();
            $migrator = new Migrator($pdo, $config['database']);

            $schemaFile = dirname(__DIR__, 2) . '/database/schema.sql';
            $seedFile = dirname(__DIR__, 2) . '/database/seed.sql';

            $migrator->runSchema($schemaFile);

            if ($request->input('with_seed', '1') === '1') {
                $migrator->runSeed($seedFile);
            }

            $this->flash('success', 'WorkShift database has been successfully installed and seeded with demo data!');
            $this->redirect('/login');
        } catch (Exception $e) {
            $this->flash('error', 'Installation error: ' . $e->getMessage());
            $this->redirect('/install');
        }
    }
}
