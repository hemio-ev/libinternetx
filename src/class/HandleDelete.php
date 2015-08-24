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
 * 
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class HandleDelete extends TaskInquire
{

    public static function code()
    {
        return '0303';
    }

    /**
     *
     * @param integer $handleId
     * @param string $replyToEmail
     */
    public function addHandle($handleId, $replyToEmail)
    {
        $this->task->appendChild(new \DOMElement('reply_to', $replyToEmail));
        $handle = $this->task->appendChild(new \DOMElement('handle'));
        $handle->appendChild(new \DOMElement('id', $handleId));
    }
}
