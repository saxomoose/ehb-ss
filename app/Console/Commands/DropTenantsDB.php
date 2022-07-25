<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class DropTenantsDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom:drop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all databases for tenant(s)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $env = App::environment();
        config(['database.connections.mysql.database' => 'backend']);
        DB::connection('mysql')->setDatabaseName('backend');
        DB::reconnect();

        if ($env == 'testing') {
            $dbs = DB::select('SHOW DATABASES LIKE "%_test"');
        } else if ($env == 'local') {
            $dbs = DB::select('SHOW DATABASES LIKE "tenant_%"');
        } else if ($env == 'production') {
            $dbs = DB::select('SHOW DATABASES LIKE "tenant_%"');
        }

        if (!empty($dbs)) {
            foreach ($dbs as $db) {
                $db = array_values((array) $db)[0];

                DB::statement("DROP DATABASE `{$db}`");
            }

            $this->info('Dropped tenant(s) database(s) succesfully');
        } else {
            $this->info('No database(s) to drop.');
        }
    }
}
