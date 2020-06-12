<?php


namespace UWebPro\PHPVerbalExpressions\Validation;

use UWebPro\PHPVerbalExpressions\VerbalExpressions;

class MustInclude
{
    private $parent;

    public function __construct(VerbalExpressions $parent)
    {
        $this->parent = $parent;
    }

    public function capitalLetter(): VerbalExpressions
    {
        return $this->parent->add('(?=.*?[A-Z])');
    }

    public function lowercaseLetter(): VerbalExpressions
    {
        return $this->parent->add('(?=.*?[a-z])');
    }

    public function digit(): VerbalExpressions
    {
        return $this->parent->add('(?=.*?[0-9])');
    }

    public function specialCharacter(): VerbalExpressions
    {
        return $this->parent->add('(?=.*?[^\w\s])');
    }

    public function minLength($length = 8): VerbalExpressions
    {
        return $this->parent->add('.{' . $length . ',}');
    }
}
