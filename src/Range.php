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
        if ($start !== null) {
            $start = DataPoint::wantNumber($start);
        }
        if ($end !== null) {
            $end = DataPoint::wantNumber($end);
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
            } elseif ($value >= $this->end) {
                return false;
            }
        }
        if ($this->start !== null) {
            if ($this->startIsInclusive) {
                if ($value < $this->start) {
                    return false;
                }
            } elseif ($value <= $this->start) {
                return false;
            }
        }

        return true;
    }

    public function toString()
    {
        // TODO.
    }

    #[\ReturnTypeWillChange]
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
