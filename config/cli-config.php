<?php

/*
 * This is for doctrine.
 */

require_once __DIR__ . '/../kernel/kernel.php';

/* create kernel */
$kernel = new kernel();
$kernel->load();
$em = $kernel->getEntityManager();

// $helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
//     'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
//     'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
// ));
return Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);
