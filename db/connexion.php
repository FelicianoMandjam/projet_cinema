<?php
    /* Connexion à une base MySQL avec l'invocation de pilote */
    $dsn = 'mysql:dbname=afci_cinema;host=127.0.0.1;port=8889';
    $user = 'root';
    $password = 'root';

    try 
    {
        $db = new PDO($dsn, $user, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } 
    catch (\PDOException $e) 
    {
        die("Database connection failed: " . $e->getMessage());
    }

?>