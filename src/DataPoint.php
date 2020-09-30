<?php

namespace gipfl\IcingaApi;

use JsonSerializable;

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

    public function jsonSerialize()
    {
        return (object) [
            'label' => $this->label,
            'value' => $this->value,
            'unit'  => $this->unit,
            'warning'  => $this->warning,
            'critical' => $this->critical,
        ];
    }
}
