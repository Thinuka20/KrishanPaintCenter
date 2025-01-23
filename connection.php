<?php

class Database{

    public static $connection;
    public static function connection(){
        if(!isset(Database::$connection)){
            Database::$connection = new mysqli("localhost","root","@2005Thinuka20","krishan_paint_center","3306");
        }
    }

    public static function iud($q){
        Database::connection();
        Database::$connection->query($q);
    }

    public static function search($q){
        Database::connection();
        $resultset = Database::$connection->query($q);
        return $resultset;
    }

}
?>