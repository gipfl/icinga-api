<?php

namespace gipfl\IcingaApi\ApiEvent;

use gipfl\IcingaApi\IcingaObject\CheckResult;
use JsonSerializable;
use stdClass;
use function microtime;

class CheckResultApiEvent implements JsonSerializable
{
    const TYPE = 'CheckResult';

    /** @var string */
    protected $host;

    /** @var string|null */
    protected $service;

    /** @var CheckResult */
    protected $checkResult;

    /** @var float */
    protected $timestamp;

    /**
     * CheckResultApiEvent constructor.
     * @param CheckResult $checkResult
     * @param string $host
     * @param string|null $service
     * @param float|int|null $now
     */
    public function __construct(CheckResult $checkResult, $host, $service = null, $now = null)
    {
        $this->host = (string) $host;
        if ($service !== null) {
            $this->service = (string) $service;
        }
        $this->checkResult = $checkResult;
        if ($now === null) {
            $this->timestamp = microtime(true);
        } else {
            $this->timestamp = (float) $now;
        }
    }

    /**
     * @param stdClass $object
     * @return static
     */
    public static function fromObject($object)
    {
        if (isset($object->service)) {
            $service = $object->service;
        } else {
            $service = null;
        }

        return new static(
            CheckResult::fromObject((object) $object->check_result),
            $object->host,
            $service
        );
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return bool
     */
    public function isHost()
    {
        return $this->service === null;
    }
    /**
     * @return string|null
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return bool
     */
    public function isService()
    {
        return $this->service !== null;
    }

    /**
     * @return CheckResult
     */
    public function getCheckResult()
    {
        return $this->checkResult;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $object = (object) [
            'type'      => static::TYPE,
            'timestamp' => $this->timestamp,
            'host'      => $this->host
        ];

        if ($this->service !== null) {
            $object->service = $this->service;
        }

        $object->check_result = $this->checkResult;

        return $object;
    }
}
