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
 * Description of ZoneUpdate
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class ZoneUpdate extends Task
{
    /**
     *
     * @var \DOMElement
     */
    public $zone;

    public static function code()
    {
        return '0202';
    }

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->zone = $this->task->appendChild(new \DOMElement('zone'));
    }

    public function addName($name)
    {
        return $this->zone->appendChild(new \DOMElement('name', $name));
    }

    public function addNsAction($nsAction)
    {
        return $this->zone->appendChild(new \DOMElement('ns_action', $nsAction));
    }

    public function addWwwInclude($enableWwwInclude)
    {
        return $this->zone->appendChild(new \DOMElement('www_include',
                                                        $enableWwwInclude));
    }

    public function addSoaLevel($soaLevel)
    {
        $soa = new \DOMElement('soa');
        $this->zone->appendChild($soa);
        return $soa->appendChild(new \DOMElement('level', $soaLevel));
    }

    public function addNameserver($server, $ttl = null)
    {
        $ns = new \DOMElement('nserver');
        $this->zone->appendChild($ns);
        $ns->appendChild(new \DOMElement('name', $server));

        if ($ttl !== null)
            $ns->appendChild(new \DOMElement('ttl', $ttl));

        return $ns;
    }

    public function addResourceRecord($name, $type, $value, $ttl, $pref = null)
    {
        $rr = new \DOMElement('rr');
        $this->zone->appendChild($rr);

        $rr->appendChild(new \DOMElement('name', $name));
        $rr->appendChild(new \DOMElement('type', $type));
        $rr->appendChild(new \DOMElement('value', $value));
        $rr->appendChild(new \DOMElement('ttl', (string) $ttl));

        if ($pref !== null)
            $rr->appendChild(new \DOMElement('pref', $pref));
    }
}
