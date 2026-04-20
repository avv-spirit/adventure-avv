<?php
session_start();
$host='localhost';$db='adventurea_db';$user='adventurea_db';$pass='123qweASD321';$charset='utf8mb4';
$dsn="mysql:host=$host;dbname=$db;charset=$charset";
$options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,];
try{$pdo=new PDO($dsn,$user,$pass,$options);}catch(PDOException $e){die('DB error: '.$e->getMessage());}
?>