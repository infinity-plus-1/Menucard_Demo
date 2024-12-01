<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
<<<<<<< HEAD
//sleep(2);
return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

/*$client = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => 'surukesh.ddnss.de',
    'port'   => 10289,
    'password' => 'DefSecPW-39173!'
]);


try { 

    $client->connect(); 

} catch (Predis\Connection\ConnectionException $e) { 

        throw("Connection to Cache-Server could not be established"); 

}*/

$client = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => 'surukesh.ddnss.de',
    'port'   => 10289,
    'password' => 'DefSecPW-39173!'
]);


try { 

    $client->connect(); 

} catch (Predis\Connection\ConnectionException $e) { 

        throw("Connection to Cache-Server could not be established"); 

} 

=======

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
>>>>>>> a8ada84 (Add initial set of files)
