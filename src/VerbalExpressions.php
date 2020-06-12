<?php

namespace UWebPro\PHPVerbalExpressions;

use UWebPro\PHPVerbalExpressions\Validation\MustInclude;

/**
 * Class VerbalExpressions
 * @package VerbalExpressions\PHPVerbalExpressions
 */
class VerbalExpressions
{
    public $prefixes = '';
    public $source = '';
    public $suffixes = '';
    public $modifiers = 'm'; // default to global multi line matching
    public $replaceLimit = 1;   // the limit of preg_replace when g modifier is not set
    protected $lastAdded = false; // holds the last added regex
    private $mustInclude;

    public function __construct()
    {
        $this->mustInclude = (new MustInclude($this));
    }

    public function mustInclude(): MustInclude
    {
        return $this->mustInclude;
    }

    public static function sanitize($value): string
    {
        return $value ? preg_quote($value, '/') : $value;
    }


    public function add(string $value): VerbalExpressions
    {
        $this->source .= $this->lastAdded = $value;

        return $this;
    }


    public function startOfLine($enable = true): VerbalExpressions
    {
        $this->prefixes = $enable ? '^' : '';

        return $this;
    }

    public function endOfLine($enable = true): VerbalExpressions
    {
        $this->suffixes = $enable ? '$' : '';

        return $this;
    }

    public function then($value): VerbalExpressions
    {
        return $this->add('(?:' . self::sanitize($value) . ')');
    }

    public function find($value): VerbalExpressions
    {
        return $this->then($value);
    }


    public function maybe($value): VerbalExpressions
    {
        return $this->add('(?:' . self::sanitize($value) . ')?');
    }

    public function anything(): VerbalExpressions
    {
        return $this->add('(?:.*)');
    }

    public function anythingBut($value): VerbalExpressions
    {
        return $this->add('(?:[^' . self::sanitize($value) . ']*)');
    }

    public function something(): VerbalExpressions
    {
        return $this->add('(?:.+)');
    }

    public function somethingBut($value): VerbalExpressions
    {
        return $this->add('(?:[^' . self::sanitize($value) . ']+)');
    }

    public function replace($source, $value)
    {
        // php doesn't have g modifier so we remove it if it's there and we remove limit param
        if (strpos($this->modifiers, 'g') !== false) {
            $this->modifiers = str_replace('g', '', $this->modifiers);

            return preg_replace($this->getRegex(), $value, $source);
        }

        return preg_replace($this->getRegex(), $value, $source, $this->replaceLimit);
    }

    public function lineBreak(): VerbalExpressions
    {
        return $this->add('(?:\\n|(\\r\\n))');
    }

    public function br(): VerbalExpressions
    {
        return $this->lineBreak();
    }

    public function tab(): VerbalExpressions
    {
        return $this->add('\\t');
    }

    public function word(): VerbalExpressions
    {
        return $this->add('\\w+');
    }

    public function digit(): VerbalExpressions
    {
        return $this->add('\\d');
    }

    public function anyOf($value): VerbalExpressions
    {
        return $this->add('[' . $value . ']');
    }

    public function any($value): VerbalExpressions
    {
        return $this->anyOf($value);
    }

    public function range(): VerbalExpressions
    {
        $args = func_get_args();
        $arg_num = count($args);

        if ($arg_num % 2 != 0) {
            throw new \InvalidArgumentException('Number of args must be even', 1);
        }

        $value = '[';

        for ($i = 0; $i < $arg_num;) {
            $value .= self::sanitize($args[$i++]) . '-' . self::sanitize($args[$i++]);
        }

        $value .= ']';

        return $this->add($value);
    }

    public function addModifier($modifier): VerbalExpressions
    {
        if (strpos($this->modifiers, $modifier) === false) {
            $this->modifiers .= $modifier;
        }

        return $this;
    }

    public function removeModifier($modifier): VerbalExpressions
    {
        $this->modifiers = str_replace($modifier, '', $modifier);

        return $this;
    }

    public function withAnyCase($enable = true): VerbalExpressions
    {
        return $enable ? $this->addModifier('i') : $this->removeModifier('i');
    }


    public function stopAtFirst($enable = true): VerbalExpressions
    {
        return $enable ? $this->addModifier('g') : $this->removeModifier('g');
    }


    public function searchOneLine($enable = true): VerbalExpressions
    {
        return $enable ? $this->addModifier('m') : $this->removeModifier('m');
    }

    public function multiple($value): VerbalExpressions
    {
        $value = self::sanitize($value);

        $last = substr($value, -1);

        if ($last !== '+' && $last !== '*') {
            $value .= '+';
        }

        return $this->add($value);
    }


    public function _or($value): self
    {
        if (strpos($this->prefixes, '(') === false) {
            $this->prefixes .= '(?:';
        }

        if (strpos($this->suffixes, ')') === false) {
            $this->suffixes .= ')';
        }

        $this->add(')|(?:');

        if ($value) {
            $this->add($value);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getRegex();
    }


    public function getRegex(): string
    {
        return '/' . $this->prefixes . $this->source . $this->suffixes . '/' . $this->modifiers;
    }

    public function test($value): bool
    {
        // php doesn't have g modifier so we remove it if it's there and call preg_match_all()
        if (strpos($this->modifiers, 'g') !== false) {
            $this->modifiers = str_replace('g', '', $this->modifiers);
            $matches = array();//because it's not optional in <5.4
            return preg_match_all($this->getRegex(), $value, $matches);
        }

        return (bool)preg_match($this->getRegex(), $value);
    }

    public function clean(array $options = array()): VerbalExpressions
    {
        $options = array_merge(
            array(
                'prefixes' => '',
                'source' => '',
                'suffixes' => '',
                'modifiers' => 'gm',
                'replaceLimit' => '1'
            ),
            $options
        );
        $this->prefixes = $options['prefixes'];
        $this->source = $options['source'];
        $this->suffixes = $options['suffixes'];
        $this->modifiers = $options['modifiers'];    // default to global multi line matching
        $this->replaceLimit = $options['replaceLimit']; // default to global multi line matching

        return $this;
    }


    public function limit($min, $max = 0): VerbalExpressions
    {
        if ($max == 0) {
            $value = '{' . $min . '}';
        } elseif ($max < $min) {
            $value = '{' . $min . ',}';
        } else {
            $value = '{' . $min . ',' . $max . '}';
        }

        // check if the expression has * or + for the last expression
        if (preg_match('/\*|\+/', $this->lastAdded)) {
            $l = 1;
            $this->source = strrev(str_replace(array('+', '*'), strrev($value), strrev($this->source), $l));

            return $this;
        }

        return $this->add($value);
    }
}
