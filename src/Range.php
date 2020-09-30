<?php

namespace gipfl\IcingaApi;

use InvalidArgumentException;
use JsonSerializable;

class Range implements JsonSerializable
{
    /** @var int|float|null */
    protected $start = 0;

    /** @var bool */
    protected $startIsInclusive = true;

    /** @var int|float|null */
    protected $end;

    /** @var bool */
    protected $endIsInclusive = true;

    public function __construct(
        $start,
        $end,
        $startIsInclusive = true,
        $endIsInclusive = true
    ) {
        if ($start !== null && ! \is_numeric($start)) {
            throw new InvalidArgumentException(
                "Start of range must be numeric, got $start"
            );
        }
        if ($end !== null && ! \is_numeric($end)) {
            throw new InvalidArgumentException(
                "End of range must be numeric, got $end"
            );
        }
        if ($start !== null && $end !== null && $start > $end) {
            throw new InvalidArgumentException(
                "Range start cannot be greater then end, got $start > $end"
            );
        }
        $this->start = $start;
        $this->end = $end;
        $this->startIsInclusive = $startIsInclusive;
        $this->endIsInclusive = $endIsInclusive;
    }

    public function contains($value)
    {
        if ($this->end !== null) {
            if ($this->endIsInclusive) {
                if ($value > $this->end) {
                    return false;
                }
            } else {
                if ($value >= $this->end) {
                    return false;
                }
            }
        }
        if ($this->start !== null) {
            if ($this->startIsInclusive) {
                if ($value < $this->start) {
                    return false;
                }
            } else {
                if ($value <= $this->start) {
                    return false;
                }
            }
        }

        return true;
    }

    public function toString()
    {
        // TODO.
    }

    public function jsonSerialize()
    {
        return (object) [
            'start' => $this->start,
            'end'   => $this->end,
            'start_is_inclusive' => $this->startIsInclusive,
            'end_is_inclusive'   => $this->endIsInclusive,
        ];
    }
}
