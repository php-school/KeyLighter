<?php
/**
 * Highlighter
 *
 * Copyright (C) 2015, Some right reserved.
 *
 * @author Kacper "Kadet" Donat <kadet1090@gmail.com>
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

namespace Kadet\Highlighter\Matcher;

use Kadet\Highlighter\Parser\Token;

class RegexMatcher implements MatcherInterface
{
    private $regex;
    private $groups;

    /**
     * RegexMatcher constructor.
     *
     * @param            $regex
     * @param array|null $groups
     */
    public function __construct($regex, array $groups = [1 => null])
    {
        $this->regex = $regex;
        $this->groups = $groups;
    }

    /**
     * Matches all occurrences and returns token list
     *
     * @param string $source Source to match tokens
     *
     * @param        $class
     *
     * @return array
     */
    public function match($source, $class)
    {
        preg_match_all($this->regex, $source, $matches, PREG_OFFSET_CAPTURE);
        $matches = array_intersect_key($matches, $this->groups);

        $result = [];
        foreach ($matches as $id => $group) {
            $name = $this->groups[$id];

            foreach ($group as $match) {
                if ($match[1] === -1) {
                    continue;
                }

                /** @var Token $token */
                $token = new $class([$name, 'pos' => $match[1], 'length' => strlen($match[0])]);

                $result[] = $token;
                $result[] = $token->getEnd();
            }
        }

        return $result;
    }
}