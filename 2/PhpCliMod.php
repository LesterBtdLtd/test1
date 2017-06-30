<?php

if(!defined('STDIN')) exit;

include "vendor/php-cli/php-cli/PhpCli.class.php";


class PhpCliMod extends PhpCli
{
    protected function hasValidArgumentRegExpression($regularExpression, $value)
    {
        return (preg_match($regularExpression, $value) == 1);
    }
}