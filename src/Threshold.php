<?php

namespace gipfl\IcingaApi;

use JsonSerializable;

class Threshold implements JsonSerializable
{
    const OUTSIDE = 'outside';

    const INSIDE = 'inside';

    /** @var Range */
    protected $range;

    /** @var bool */
    protected $outsideIsValid;

    public function __construct(Range $range, $outsideIsValid = true)
    {
        $this->range = $range;
        $this->outsideIsValid = $outsideIsValid;
    }

    public function valueIsValid($value)
    {
        if ($this->outsideIsValid) {
            return $this->range->contains($value);
        } else {
            return $this->range->contains($value);
        }
    }

    public function jsonSerialize()
    {
        return (object) [
            'valid' => $this->outsideIsValid ? static::OUTSIDE : static::INSIDE,
            'range' => $this->range,
        ];
    }
}
