<?php

namespace gipfl\IcingaApi;

use ArrayIterator;
use JsonSerializable;

class DataPoints extends ArrayIterator implements JsonSerializable
{
    protected $points = [];

    /**
     * DataPoints constructor.
     * @param array|null $array
     */
    public function __construct($array = [])
    {
        parent::__construct([], 0);
        if ($array === null) {
            return;
        }
        foreach ($array as $point) {
            if ($point instanceof DataPoint) {
                $this->append($point);
            } else {
                $this->append(IcingaPerfDataParser::fromIcinga($point));
            }
        }
    }

    public function jsonSerialize()
    {
        return (array) $this;
    }
}
