<?php

namespace gipfl\IcingaApi;

use InvalidArgumentException;
use JsonSerializable;
use function is_numeric;
use function preg_match;

class DataPoint implements JsonSerializable
{
    /** @var string */
    protected $label;

    /** @var int|float */
    protected $value;

    protected $unit;

    /** @var Threshold */
    protected $warning;

    /** @var Threshold */
    protected $critical;

    /** @var int|float */
    protected $min;

    /** @var int|float */
    protected $max;

    public function __construct($label, $value, $unit = null)
    {
        $this->label = $label;
        $this->value = $value;
        $this->unit = $unit;
    }

    public function hasWarningThreshold()
    {
        return $this->warning !== null;
    }

    /**
     * @return Threshold
     */
    public function getWarningThreshold()
    {
        return $this->warning;
    }

    public function setWarningThreshold(Threshold $threshold)
    {
        $this->warning = $threshold;
    }

    /**
     * @return Threshold
     */
    public function getCriticalThreshold()
    {
        return $this->critical;
    }

    /**
     * @return bool
     */
    public function hasCriticalThreshold()
    {
        return $this->critical !== null;
    }

    public function setCriticalThreshold(Threshold $threshold)
    {
        $this->critical = $threshold;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function hasUnit()
    {
        return $this->unit !== null;
    }

    /**
     * @return string|null
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param int|float|string|null $value
     */
    public function setMin($value)
    {
        if ($value === null) {
            $this->min = $value;
        } else {
            $this->min = static::wantNumber($value);
        }
    }

    /**
     * @param int|float|string|null $value
     */
    public function setMax($value)
    {
        if ($value === null) {
            $this->max = $value;
        } else {
            $this->max = static::wantNumber($value);
        }
    }

    public function jsonSerialize()
    {
        return (object) [
            'label' => $this->label,
            'value' => $this->value,
            'unit'  => $this->unit,
            'warning'  => $this->warning,
            'critical' => $this->critical,
            'min'      => $this->min,
            'max'      => $this->max,
        ];
    }

    public static function wantNumber($any)
    {
        if (is_int($any) || is_float($any)) {
            return $any;
        }

        return static::parseNumber($any);
    }

    public static function parseNumber($string)
    {
        if (! is_numeric($string)) {
            throw new InvalidArgumentException(
                "Numeric value expected, got $string"
            );
        }
        if (preg_match('/^-?\d+$/', $string)) {
            return (int) $string;
        }

        return (float) $string;
    }
}
