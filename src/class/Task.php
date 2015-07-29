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
 * Description of Task
 *
 * @author Michael Herold <quabla@hemio.de>
 */
abstract class Task
{
    /**
     *
     * @var \DOMElement
     */
    public $task;

    /**
     *
     * @var \DOMElement
     */
    public $ctid;

    /**
     *
     * @var Request
     */
    public $request;

    // TODO: Wait until abstract static functions are allowed in PHP again
    #public abstract static function code();

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->task    = $request->addTask($this::code());

        $this->ctid = new \DOMElement('ctid');
        $this->task->appendChild($this->ctid);
        $this->setCtid(uniqid('auto'));
    }

    public function setCtid($ctid)
    {
        $this->ctid->nodeValue = $ctid;
    }
}
