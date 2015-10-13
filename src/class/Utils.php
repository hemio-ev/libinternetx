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
 * Description of Utils
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class Utils
{

    /**
     *
     * @param \DOMElement $element
     * @param string $name
     * @return \DOMNode
     * @throws \Exception
     */
    public static function getUniqueTag(\DOMElement $element, $name)
    {
        $tags = Utils::getChildrenByTagName($element, $name);

        if (empty($tags))
            throw new \Exception('Expecting exactly one element named "'.$name.'", found none.');
        if (count($tags) > 1)
            throw new \Exception('Expecting exactly one element named "'.$name.'", found "'.$tags->length.'".');

        return $tags[0];
    }

    public static function getUniqueTagContent(\DOMElement $element, $name)
    {
        $tag = self::getUniqueTag($element, $name);
        return $tag->nodeValue;
    }

    public static function getChildrenByTagName(\DOMElement $element, $name)
    {
        $result = [];

        foreach ($element->childNodes as $child)
            if ($child instanceof \DOMElement && $child->tagName === $name)
                $result[] = $child;

        return $result;
    }

    public static function toArray(\DOMElement $element)
    {
        $result = [];

        foreach ($element->childNodes as $child)
            if ($child instanceof \DOMElement)
                if ($child->childNodes->length > 1)
                    $result[$child->tagName] = self::toArray($child);
                else
                    $result[$child->tagName] = $child->nodeValue;

        return $result;
    }

    public static function fromArray(\DOMElement $element, array $data)
    {
        foreach ($data as $key => $value)
            if (is_array($value))
                Utils::fromArray(
                    $element->appendChild(new \DOMElement($key))
                    , $value
                );
            else
                $element->appendChild(new \DOMElement($key, $value));

        return $element;
    }

    /**
     *
     * @param type $dateTimeStr
     * @return \DateTime
     */
    public static function sqlDate($dateTimeStr)
    {
        $dateTimeObj = new \DateTimeImmutable($dateTimeStr);

        if ($dateTimeObj->format('Y') > 1970)
            return $dateTimeObj->format(\DateTime::ATOM);
        else
            return null;
    }
}
