<?php
require MODX_CORE_PATH . "config/config.inc.php";
require_once __DIR__ . "/vendor/autoload.php";

use \Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    "driver" => $database_type,
    "host" => $database_server,
    "database" => $dbase,
    "username" => $database_user,
    "password" => $database_password,
    'prefix' => $table_prefix,
    'charset' => 'utf8',
    'collation' => 'utf8_general_ci',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();


class Table
{
    protected static function schema()
    {
        return Capsule::schema();
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return in_array($column, self::schema()->getColumnListing($table));
    }

    public static function renameColumn(string $table, string $from, string $to): void
    {
        if (self::schema()->hasTable($table) && self::hasColumn($table, $from) && !self::hasColumn($table, $to)) {
            self::schema()->table($table, function ($table) use ($from, $to) {
                $table->renameColumn($from, $to);
            });
            echo "Renamed column $from → $to in $table\n";
        }
    }

    public static function addColumn(string $table, string $column, callable $definition): void
    {
        if (!self::hasColumn($table, $column)) {
            self::schema()->table($table, function ($table) use ($column, $definition) {
                $definition($table);
            });
            echo "Added column $column to $table\n";
        }
    }

    public static function create(string $table, callable $definition): void
    {
        if (!self::schema()->hasTable($table)) {
            self::schema()->create($table, $definition);
            echo "Created table $table\n";
        }
    }

    public static function drop(string $table): void
    {
        if (self::schema()->hasTable($table)) {
            self::schema()->drop($table);
            echo "Таблица $table удалена\n";
        }
    }
}