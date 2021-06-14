<?php
use Illuminate\Database\Capsule\Manager as Capsule;

class AccesoDatosORM
{
    public function __construct()
    {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $_ENV['MYSQL_HOST'],
            'database'  => $_ENV['MYSQL_DB'],
            'username'  => $_ENV['MYSQL_USER'],
            'password'  => $_ENV['MYSQL_PASS'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
