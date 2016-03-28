<?php
/**
 * Highlighter
 *
 * Copyright (C) 2016, Some right reserved.
 *
 * @author Kacper "Kadet" Donat <kacper@kadet.net>
 *
 * Contact with author:
 * Xmpp: me@kadet.net
 * E-mail: contact@kadet.net
 *
 * From Kadet with love.
 */

namespace Kadet\Highlighter\Parser\Token;

use Kadet\Highlighter\Language\Language;
use Kadet\Highlighter\Parser\Result;
use Kadet\Highlighter\Parser\TokenIterator;

/**
 * Class LanguageToken
 *
 * @package Kadet\Highlighter\Parser\Token
 *
 * @property bool $postProcess True if language is post processed.
 */
class LanguageToken extends Token
{
    public function getInjected()
    {
        return $this->rule->inject;
    }

    public function getLanguage()
    {
        return $this->getStart() ? $this->getStart()->rule->inject : $this->rule->language;
    }

    protected function validate(Language $language, $context)
    {
        $valid = false;

        if ($this->isStart()) {
            $lang = $this->rule->language;
            if ($lang === null && $this->getInjected() !== $language) {
                $valid = true;
            } elseif ($language === $lang && $this->rule->validator->validate($context)) {
                $valid = true;
            }
        } else {
            $desired = $this->getLanguage();
            $valid   = $language === $desired && $this->rule->validator->validate($context);
        }
        $this->setValid($valid);
    }

    public function process(array &$context, Language $language, Result $result, TokenIterator $tokens)
    {
        if(!$this->isValid($language, $context)) {
            return true;
        }

        if($this->isStart()) {
            $result->merge($this->getInjected()->parse($tokens));
        } else {
            $this->setStart($result[0]);

            if ($this->_start->postProcess) {
                $source = substr($tokens->getSource(), $this->_start->pos, $this->_start->getLength());
                $tokens = $this->_start->getInjected()->tokenize(
                    $source, $result, $this->_start->pos, Language::EMBEDDED_BY_PARENT
                );
                $result->exchangeArray($this->_start->getInjected()->parse($tokens)->getTokens());
            }

            # closing unclosed tokens
            foreach (array_reverse($context) as $hash => $name) {
                $end = new Token([$name, 'pos' => $this->pos]);
                $tokens[$hash]->setEnd($end);
                $result->append($end);
            }

            $result->append($this);
            return false;
        }

        return true;
    }
}
