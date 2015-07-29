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

chdir('../../');
require_once 'vendor/autoload.php';

use herold\libinternetx as api;

$getopt = new api\CmdOpt();
$getopt->parse();

if (
    !$getopt->getOption('database') ||
    !$getopt->getOption('user') ||
    !$getopt->getOption('password')
) {
    echo $getopt->getHelpText();
    exit(2);
}

$debug = $getopt->getOption('verbose');

$pdo = new PDO($getopt->getOption('database'));
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$pdo->beginTransaction();

if ($getopt->getOption('force-update'))
// get all registered domains
    $handles = $pdo->query('SELECT * FROM domain_reseller.srv_handle()')->fetchAll(PDO::FETCH_ASSOC);
else
// get all registered domains with NOT NULL backend status
    $handles = $pdo->query('SELECT * FROM domain_reseller.srv_handle()'
            .' WHERE backend_status IS NOT NULL OR id IS NULL')
        ->fetchAll(PDO::FETCH_ASSOC);

if (empty($handles)) {
    if ($debug)
        echo "No updates to be done.\n";
    exit(0);
}


$newRequest = function () use ($getopt) {
    $request = new api\Request();

    $request->addAuth(
        $getopt->getOption('user')
        , $getopt->getOption('password')
        , '4'
    );

    return $request;
};

$fwdHandleId = $pdo->prepare('SELECT domain_reseller.fwd_handle_id(p_alias := ?, p_id := ?)');

$requestUpdateCreate = $newRequest();

foreach ($handles as $handle) {
    $alias = $handle['alias'];
    if ($debug)
        echo "Processing ${alias} …\n";

    if ($handle['id'] === null) {
        if ($debug)
            echo "Trying to find id …\n";

        $request = $newRequest();

        $handleInquire = new api\HandleInquire($request);
        $handleInquire->addKeys(['id']);
        $handleInquire->task
            ->appendChild(new \DOMElement('handle'))
            ->appendChild(new \DOMElement('alias', $alias));

        if ($debug)
            echo $request->doc->saveXML();

        $request->execute();

        $handleEnquireData = api\Utils::toArray($handleInquire->getData()[0]);

        if ($alias === $handleEnquireData['alias']) {
            $handle['id'] = $handleEnquireData['id'];
            $fwdHandleId->execute([$alias, $handle['id']]);
        }
    }

    unset($handle['service']);
    unset($handle['subservice']);
    unset($handle['service_entity_name']);
    unset($handle['owner']);
    unset($handle['backend_status']);
    $handle['type'] = 'PERSON';

    if ($handle['id'] === null) {
        unset($handle['id']);
        $handleCreate = new api\HandleCreate($requestUpdateCreate);
        $handleCreate->addHandle($handle, $handle['email']);
    } else {
        $handleUpdate = new api\HandleUpdate($requestUpdateCreate);
        $handleUpdate->addHandle($handle, $handle['email']);
    }
}

if ($debug)
    echo $requestUpdateCreate->doc->saveXML();

$requestUpdateCreate->execute();

$pdo->commit();
