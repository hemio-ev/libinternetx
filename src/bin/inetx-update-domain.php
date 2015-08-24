#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2015 Michael Herold <quabla@hemio.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

chdir(__DIR__.'/../../');
require_once 'vendor/autoload.php';

use herold\libinternetx as api;
use Ulrichsg\Getopt;

$getopt = new api\CmdOpt();

$getopt->addOptions([
        (new Getopt\Option(null, 'tech-c', Getopt\Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Default TechC (handle id)'),
        (new Getopt\Option(null, 'zone-c', Getopt\Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Default ZoneC (handle id)')
]);

$getopt->parse();

if (
    !$getopt->getOption('database') ||
    !$getopt->getOption('user') ||
    !$getopt->getOption('password') ||
    !$getopt->getOption('tech-c') ||
    !$getopt->getOption('zone-c')
) {
    echo $getopt->getHelpText();
    exit(2);
}

$debug = $getopt->getOption('verbose');

$pdo = new PDO($getopt->getOption('database'));
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$pdo->beginTransaction();

if ($getopt->getOption('force-update'))
    $existingDomains = $pdo->query('SELECT * FROM domain_reseller.srv_registered()'
            .' WHERE backend_status = \'upd\' OR backend_status IS NULL')->fetchAll(PDO::FETCH_ASSOC);
else
    $existingDomains = $pdo->query('SELECT * FROM domain_reseller.srv_registered()'
            .' WHERE backend_status = \'upd\'')
        ->fetchAll(PDO::FETCH_ASSOC);


// check if created and move to existing if created
$forCreationDomains = $pdo->query('SELECT * FROM domain_reseller.srv_registered()'
        .' WHERE backend_status = \'ins\'')
    ->fetchAll(PDO::FETCH_ASSOC);

// check if deleted and in case they are put to 'old' state
$forDeletionDomains = $pdo->query('SELECT * FROM domain_reseller.srv_registered(p_include_inactive:=TRUE)'
        .' WHERE backend_status=\'del\'')->fetchAll(PDO::FETCH_ASSOC);

if (empty($existingDomains) && empty($forCreationDomains) && empty($forDeletionDomains)) {
    if ($debug)
        echo "No updates to be done.\n";
    exit(0);
}

$newRequest = function () use ($getopt) {
    $request = new api\Request(true);

    $request->addAuth(
        $getopt->getOption('user')
        , $getopt->getOption('password')
        , '4'
    );

    return $request;
};

$fwdStatus = $pdo->prepare('SELECT domain_reseller.fwd_registered_status'
    .'(p_domain := ?, p_payable := ?, p_period := ?, p_registrar_status := ?, p_registry_status := ?, p_last_status := ?)');

$fwdBackendStatus = $pdo->prepare('SELECT dns.fwd_registered_status'
    .'(p_domain := ?, p_backend_status := ?)');

$newDomainLookup = function ($name) use ($newRequest, $debug, $fwdStatus) {
    $request = $newRequest();

    $domainInquire = new api\DomainInquire($request);
    $domainInquire->addKeys(['registrar_status', 'payable']);
    $domainInquire->task
        ->appendChild(new \DOMElement('domain'))
        ->appendChild(new \DOMElement('name', $name));

    $request->execute();

    if ($debug)
        echo "Found domain, updating status …\n";

    $data = api\Utils::toArray($domainInquire->getData()[0]);

    $fwdStatus->execute([
        $data['name'],
        $data['payable'],
        $data['period'],
        $data['registrar_status'],
        $data['registry_status'],
        $data['status']
    ]);

    return $data;
};

foreach ($forCreationDomains as $key => $domain) {
    $name = $domain['domain'];

    if ($debug)
        echo "Checking if ${name} is now registered …\n";

    try {
        $domainData = $newDomainLookup($name);

        $fwdBackendStatus->execute([$name, 'upd']);

        $existingDomains[] = $domain;
        unset($forCreationDomains[$key]);
    } catch (api\ApiException $e) {
        if ($e->getCode() != '0105')
            throw $e;
    }
}

foreach ($forDeletionDomains as $key => $domain) {
    $name = $domain['domain'];

    if ($debug)
        echo "Checking if ${name} is now deleted …\n";

    try {
        $domainData = $newDomainLookup($name);
    } catch (api\ApiException $e) {
        if ($e->getCode() != '0105')
            throw $e;

        if ($debug)
            echo "Domain not found, seems to be deleted, updating status …\n";

        $fwdBackendStatus->execute([$name, 'old']);

        unset($forDeletionDomains[$key]);
    }
}

foreach ($existingDomains as $domain) {
    $name = $domain['domain'];

    if ($debug)
        echo "Updating Owner-C for ${name} …\n";

    try {
        $domainData = $newDomainLookup($name);

        $request = $newRequest();

        $ownerc = $domain['registrant_id'];
        $adminc = $domain['admin_c_id'];

        if (!$ownerc || !$adminc)
            throw new api\ApiException('OwnerC or AdminC missing for '.$name.PHP_EOL);

        $techc = $domain['tech_c_id'];
        if (!$techc)
            $techc = $getopt->getOption('tech-c');

        $zonec = $domain['zone_c_id'];
        if (!$zonec)
            $zonec = $getopt->getOption('zone-c');


        if ($domainData['ownerc'] == $ownerc &&
            $domainData['adminc'] == $adminc &&
            $domainData['techc'] == $techc &&
            $domainData['zonec'] == $zonec
        ) {
            if ($debug)
                echo "Nothing to change for {$name}, continue with next\n";

            continue;
        }

        $domainUpdate = new api\DomainUpdate($request);

        $taskData = $domainUpdate->task->appendChild(new \DOMElement('domain'));

        $taskData->appendChild(new \DOMElement('name', $name));
        $taskData->appendChild(new \DOMElement('ownerc', $ownerc));
        $taskData->appendChild(new \DOMElement('adminc', $adminc));
        $taskData->appendChild(new \DOMElement('techc', $techc));
        $taskData->appendChild(new \DOMElement('zonec', $zonec));

        usleep(10 * 1000);
        $request->execute();

        $fwdBackendStatus->execute([$name, null]);
    } catch (api\ApiException $e) {
        if ($e->getCode() == '0105')
            echo "<3><ERROR> Domain '{$name}' not found\n";
        else
            echo '<3><ERROR> '.$e->getMessage().PHP_EOL.$e->getTraceAsString().PHP_EOL;
    }
}

// remaining domains that have not been registered
if (!empty($forCreationDomains))
    echo '<3>Domains which still need to be registered: '
    .print_r($forCreationDomains, true);

// remaining domain that have not been deleted
if (!empty($forDeletionDomains))
    echo '<3>Domains which still need to be deleted: '
    .print_r($forDeletionDomains, true);

$pdo->commit();

