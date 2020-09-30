<?php

namespace gipfl\IcingaApi\ClusterEvent;

use gipfl\IcingaApi\IcingaObject\CheckResult;
use JsonSerializable;

class CheckResultEvent implements JsonSerializable
{
    const METHOD = 'event::CheckResult';

    /** @var string */
    protected $host;

    /** @var string|null */
    protected $service;

    /** @var CheckResult */
    protected $cr;

    public function __construct(CheckResult $cr, $host, $service = null)
    {
        $this->host = $host;
        $this->service = $service;
        $this->cr = $cr;
    }

    /**
     * @param \stdClass $object
     * @return static
     */
    public function fromObject($object)
    {
        if (isset($object->service)) {
            $service = $object->service;
        } else {
            $service = null;
        }

        return new static(
            CheckResult::fromObject($object->check_result),
            $object->host,
            $service
        );
    }

    public function jsonSerialize()
    {
        $object = (object) [
            'host' => $this->host
        ];

        if ($this->service !== null) {
            $object->service = $this->service;
        }

        $object->cr = $this->cr;

        return $object;
    }
}
