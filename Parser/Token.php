<?php
/**
 * Highlighter
 *
 * Copyright (C) 2015, Some right reserved.
 * @author Kacper "Kadet" Donat <kadet1090@gmail.com>
 * @license http://creativecommons.org/licenses/by-sa/4.0/legalcode CC BY-SA
 *
 * Contact with author:
 * Xmpp: kadet@jid.pl
 * E-mail: kadet1090@gmail.com
 *
 * From Kadet with love.
 */

namespace Kadet\Highlighter\Parser;


use Kadet\Highlighter\Utils\Helper;

class Token
{
    public $pos;
    public $name;
    public $index = 1;

    /**
     * @var Token
     */
    protected $_end;

    /**
     * @var Token
     */
    protected $_start;

    /** @var Rule */
    protected $_rule;

    protected $_valid;

    protected $_length;

    /**
     * Token constructor.
     */
    public function __construct($options)
    {
        // Name
        if(array_key_exists(0, $options)) {
            $this->name = $options[0];
        }

        if(array_key_exists('pos', $options)) {
            $this->pos = $options['pos'];
        }

        if(array_key_exists('index', $options)) {
            $this->index = $options['index'];
        }

        if(array_key_exists('start', $options)) {
            $this->setStart($options['start']);
        }

        if(array_key_exists('end', $options)) {
            $this->setEnd($options['end']);
        }

        if(array_key_exists('length', $options)) {
            new static([$this->name, 'pos' => $this->pos + $options['length'], 'start' => $this]);
        }
    }

    public static function compare(Token $a, Token $b)
    {
        if ($a->pos === $b->pos) {
            if (($a->isStart() && $b->isStart()) || ($a->isEnd() && $b->isEnd())) {
                if(($rule = Helper::cmp($b->_rule->getPriority(), $a->_rule->getPriority())) !== 0) {
                    return $rule;
                }

                return Helper::cmp($b->index, $a->index);
            }

            return $a->isEnd() ? -1 : 1;
        }

        return ($a->pos > $b->pos) ? 1 : -1;
    }

    public function isValid($context = null) {
        if ($this->_valid === null) {
            $this->validate($context);
        }

        return $this->_valid;
    }

    public function invalidate($invalid = true) {
        $this->_valid = !$invalid;

        if ($this->_end !== null) {
            $this->_end->_valid = $this->_valid;
        } elseif ($this->_start !== null) {
            $this->_start->_valid = $this->_valid;
        }
    }

    public function isEnd() {
        return $this->_end === null && !($this->_rule instanceof OpenRule);
    }

    public function isStart() {
        return $this->_start === null && !($this->_rule instanceof CloseRule);
    }

    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->_start;
    }

    /**
     * @param Token $start
     */
    public function setStart(Token $start = null)
    {
        $this->_end = null;
        $this->_start = $start;

        if($start !== null) {
            $this->_start->_end = $this;
        }
    }

    /**
     * @return mixed
     */
    public function getEnd()
    {
        return $this->_end;
    }

    /**
     * @param Token $end
     */
    public function setEnd(Token $end = null)
    {
        $this->_start = null;
        $this->_end = $end;
        $this->_length = 0;

        if($end !== null) {
            $this->_end->_start = $this;
        }
    }

    /**
     * @return Rule
     */
    public function getRule()
    {
        return $this->_rule;
    }

    /**
     * @param Rule $rule
     */
    public function setRule(Rule $rule)
    {
        $this->_rule = $rule;
    }

    public function getLength() {
        if($this->_length === null) {
            $this->_length = $this->_end === null ? 0 : $this->_end->pos - $this->pos;
        }

        return 0;
    }

    public function dump($text = null) {
        if($this->isStart()) {
            $result = "Start ({$this->name}) $this->pos";
            if ($text !== null && $this->_end !== null) {
                $result .= '  '.substr($text, $this->pos, $this->_end->pos - $this->pos);
            }
        } else {
            $result = "End ({$this->name}) $this->pos";
        }
        return $result;
    }

    protected function validate($context)
    {
        $this->invalidate(!$this->_rule->validateContext($context, $this->isEnd() ? [$this->name => Rule::CONTEXT_IN] : []));
    }
}