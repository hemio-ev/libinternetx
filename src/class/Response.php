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
 * Description of Response
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class Response
{
    const WARNING_FORMAT = 'XmlApiStatus: [%1$s] %2$s';

    /**
     *
     * @var \DOMDocument
     */
    public $doc;

    /**
     *
     * @var \DOMElement
     */
    public $response;

    /**
     *
     * @var array[Msg]
     */
    public $msgs = [];

    /**
     *
     * @var boolean
     */
    private $throwExceptions = false;

    /**
     *
     * @param \DOMDocument $doc
     */
    public function __construct(\DOMDocument $doc, $throwExceptions = false)
    {
        $this->throwExceptions = $throwExceptions;
        $this->doc             = $doc;
        $this->response        = $doc->documentElement;

        if ($this->response->tagName !== 'response')
            throw new \Exception('Response has no element "response"');

        $this->parse();
    }

    protected function parse()
    {
        $results = $this->response->getElementsByTagName('result');

        foreach ($results as $result) {
            $msgs   = $result->getElementsByTagName('msg');
            $status = Utils::getUniqueTag($result, 'status');

            if (Utils::getUniqueTagContent($status, 'type') === 'error') {
                $code = Utils::getUniqueTagContent($status, 'code');
                $text = Utils::getUniqueTagContent($status, 'text');

                if ($this->throwExceptions)
                    throw new ApiException($text, ltrim($code, 'E'));

                trigger_error(
                    sprintf(
                        self::WARNING_FORMAT
                        , $code
                        , $text
                    )
                    , E_USER_WARNING
                );
            }

            foreach ($msgs as $msg) {
                $this->msgs[] = new Msg($msg);
            }
        }

        foreach ($this->msgs as $msg) {
            trigger_error($msg->format());
        }
    }

    public function getData($serial)
    {
        $result = $this->response->getElementsByTagName('result')[$serial];
        return Utils::getUniqueTag($result, 'data');
    }
}
