<?php

namespace Tualo\Office\PUG;

class ValueRenderer
{

    public function render(string $type, mixed $value): string
    {

        if (is_null($value)) {
            return '';
        }

        if ($type == 'euroRenderer') {
            return $this->euroRenderer($value);
        }
        if ($type == 'deColoredMoneyRenderer') {
            return $this->deColoredMoneyRenderer($value);
        }
        if ($type == 'fullPercentRenderer') {
            return $this->fullPercentRenderer($value);
        }
        if ($type == 'CSSMetaRenderer') {
            return $this->CSSMetaRenderer($value);
        }
        if ($type == 'backgroundColorMetaRenderer') {
            return $this->backgroundColorMetaRenderer($value);
        }
        if ($type == 'deValueRenderer') {
            return $this->deValueRenderer($value);
        }
        if ($type == 'deNatualRenderer') {
            return $this->deNatualRenderer($value);
        }
        if ($type == 'deDateTime') {
            return $this->deDateTime($value);
        }
        if ($type == 'deDate') {
            return $this->deDate($value);
        }


        if ($type == '') {
            return $value;
        }


        return 'not defined (Tualo\Office\PUG\ValueRenderer) ';
    }
    public function euroRenderer(mixed $value): string
    {
        return number_format(floatval($value), 2, ',', '.') . ' €';
    }
    public function deColoredMoneyRenderer(mixed $value): string
    {
        return $this->euroRenderer($value);
    }
    public function fullPercentRenderer(mixed $value): string
    {
        return number_format(floatval($value), 3, ',', '.') . ' %';
    }
    public function CSSMetaRenderer(mixed $value): string
    {
        return $value;
    }
    public function backgroundColorMetaRenderer(mixed $value): string
    {
        return $value;
    }
    public function deValueRenderer(mixed $value): string
    {
        return number_format(floatval($value), 3, ',', '.') . '/i';
    }
    public function deNatualRenderer(mixed $value): string
    {
        return number_format(floatval($value), 3, ',', '.') . '/i';
    }
    public function deDateTime(mixed $value): string
    {
        return date('d.m.Y H:i', strtotime($value));
    }
    public function deDate(mixed $value): string
    {
        return date('d.m.Y', strtotime($value));
    }
}
