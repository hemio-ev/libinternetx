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

namespace herold\libinternetx;

/**
 * Description of Request
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class Request
{
    const HOST = 'https://gateway.autodns.com';

    /**
     *
     * @var \DOMDocument
     */
    public $doc;

    /**
     *
     * @var Response
     */
    public $response;

    /**
     *
     * @var \DOMElement
     */
    public $request;

    /**
     *
     * @var integer
     */
    private $serial = 0;

    /**
     *
     * @var boolean
     */
    private $throwExceptions = false;

    public function __construct($throwExceptions = false)
    {
        $this->throwExceptions = $throwExceptions;
        $domImpl               = new \DOMImplementation();
        $this->doc             = $domImpl->createDocument(null, 'request');
        $this->request         = $this->doc->documentElement;
    }

    public function addAuth($user, $password, $context)
    {
        $nAuth = new \DOMElement('auth');
        $this->request->appendChild($nAuth);

        $nUser     = new \DOMElement('user', $user);
        $nPassword = new \DOMElement('password', $password);
        $nContext  = new \DOMElement('context', $context);

        $nAuth->appendChild($nUser);
        $nAuth->appendChild($nPassword);
        $nAuth->appendChild($nContext);
    }

    public function addOwner($user, $context)
    {
        $nOwner = new \DOMElement('owner');
        $this->request->appendChild($nOwner);

        $nUser       = new \DOMElement('user', $user);
        $authContext = new \DOMElement('context', $context);

        $nOwner->appendChild($nUser);
        $nOwner->appendChild($authContext);
    }

    public function addLanguage($language)
    {
        $nLanguage = new \DOMElement('language', $language);
        $this->request->appendChild($nLanguage);
    }

    /**
     *
     * @return \DOMElement
     */
    public function addTask($code)
    {
        $task = new \DOMElement('task');
        $this->request->appendChild($task);
        $task->setAttribute('serial', $this->serial++);
        $task->appendChild(new \DOMElement('code', $code));
        return $task;
    }

    /**
     *
     * @param \herold\libinternetx\TaskInquire $task
     * @return \DOMElement
     * @throws \Exception
     */
    public function getData(TaskInquire $task)
    {
        if ($this->response === null)
            throw new \Exception('You have to execute() first');

        $serial = $task->task->getAttribute('serial');
        return $this->response->getData($serial);
    }

    public function execute()
    {
        $data = $this->doc->saveXML();

        $ch = curl_init(self::HOST);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!$data = curl_exec($ch)) {
            throw new \Exception(curl_error($ch));
        }

        file_put_contents('response.xml', $data);
        $xml = new \DOMDocument();
        $xml->loadXML($data);

        if ($xml === false)
            throw new \Exception('Failed to parse request response as XML');

        $this->response = new Response($xml, $this->throwExceptions);
    }
}
