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
 * Description of Msg
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class Msg
{
    const FORMAT = 'XmlApiMsg: [%1$s] %2$s'.PHP_EOL.'Help:'.PHP_EOL.'%3$s'.PHP_EOL.'Affected Objects:'.PHP_EOL.'%4$s';

    public $msg;
    public $code;
    public $text;
    public $objects = [];
    public $help    = [];

    public function __construct(\DOMElement $msg)
    {
        $this->msg = $msg;

        $this->code = Utils::getUniqueTagContent($msg, 'code');
        $this->text = Utils::getUniqueTagContent($msg, 'text');

        $this->objects = $msg->getElementsByTagName('object');

        foreach ($msg->getElementsByTagName('help') as $help)
            $this->help[] = $help->nodeValue;
    }

    public function format()
    {
        $objects = $this->formattedObjects();

        return sprintf(
            self::FORMAT
            , $this->code
            , $this->text
            ,
            count($objects) > 0 ? implode("\n -", $objects) : '(NONE)'
            , count($this->help) > 0 ? implode("\n -", $this->help) : '(NONE)'
        );
    }

    protected function formattedObjects()
    {
        $aObjects = [];
        foreach ($this->objects as $object) {
            $types  = $object->getElementsByTagName('type');
            $aTypes = [];
            foreach ($types as $type) {
                $aTypes[] = $type->nodeValue;
            }

            $values  = $object->getElementsByTagName('value');
            $aValues = [];
            foreach ($values as $value) {
                $aValues[] = $value->nodeValue;
            }

            $aObjects[] = '['.implode(', ', $aTypes).'] '.implode(', ', $aValues);
        }

        return $aObjects;
    }
}
