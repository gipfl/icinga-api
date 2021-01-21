<?php

namespace gipfl\IcingaApi\IcingaObject;

use gipfl\IcingaApi\DataPoints;
use gipfl\IcingaApi\IcingaState;
use JsonSerializable;
use stdClass;
use function max;
use function microtime;

class CheckResult implements JsonSerializable
{
    const OBJECT_TYPE ='CheckResult';

    /** @var bool */
    protected $active = true;

    /** @var string|array */
    protected $command;

    /** @var int Icinga state code, 0, 1, 2 or 3 */
    protected $state;

    /** @var int real exit code, like 128 etc */
    protected $exitCode;

    /** @var string */
    protected $output;

    /** @var DataPoints */
    protected $dataPoints;

    /** @var string|null */
    protected $checkSource;

    /** @var float|null */
    protected $executionStart;

    /** @var float|null */
    protected $executionEnd;

    /** @var float|null */
    protected $executionTime;

    /** @var float */
    protected $scheduleStart;

    /** @var float */
    protected $scheduleEnd;

    /** @var float|null */
    protected $latency;

    /** @var int|float */
    protected $ttl;

    /** @var CheckResultVars */
    protected $varsBefore;

    /** @var CheckResultVars */
    protected $varsAfter;

    /**
     * @param stdClass $object
     * @return static
     */
    public static function fromObject($object)
    {
        $self = new static;
        $self->active = $object->active;
        $self->command = $object->command;
        $self->executionStart = $object->execution_start;
        $self->executionEnd = $object->execution_end;
        $self->scheduleEnd = $object->schedule_start;
        $self->state = $object->state;
        $self->output = $object->output;
        $self->dataPoints = new DataPoints($object->performance_data);
        $self->exitCode = $object->exit_status;
        $self->varsBefore = CheckResultVars::fromObject((object) $object->vars_before);
        $self->varsAfter = CheckResultVars::fromObject((object) $object->vars_after);
        if (isset($object->check_source)) {
            $self->checkSource = $object->check_source;
        }

        return $self;
    }

    /**
     * @param string|null $source
     * @return $this
     */
    public function setCheckSource($source)
    {
        $this->checkSource = $source;
        return $this;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = (int) $state;

        return $this;
    }

    /**
     * @param $code
     * @return $this
     */
    public function setExitCode($code)
    {
        $this->exitCode = (int) $code;
        if ($this->state === null) {
            if ($this->exitCode >= 0 & $this->exitCode <= 3) {
                $this->state = $this->exitCode;
            } else {
                // TODO: What if this is a host? We cannot know!
                $this->state = IcingaState::SERVICE_UNKNOWN;
            }
        }

        return $this;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput($string)
    {
        $this->output = $string;
        return $this;
    }

    public function getLatency()
    {
        if ($this->latency === null || $this->scheduleEnd === null) {
            $this->latency = $this->calculateLatency();
        }

        return $this->latency;
    }

    public function calculateLatency()
    {
        if ($this->scheduleEnd === null) {
            if ($this->scheduleStart === null) {
                return null;
            }

            return microtime(true);
        }
        if ($this->scheduleStart === null) {
            return null;
        }
        $executionTime = $this->getExecutionTime();
        if ($executionTime === null) {
            return max(0, $this->scheduleEnd - $this->scheduleStart);
        }

        return max(0, $this->scheduleEnd - $this->scheduleStart - $executionTime);
    }

    public function getExecutionTime()
    {
        if ($this->executionTime === null || $this->executionEnd === null) {
            $this->executionTime = $this->calculateExecutionTime();
        }

        return $this->executionTime;
    }

    public function calculateExecutionTime()
    {
        if ($this->executionEnd === null) {
            if ($this->executionStart === null) {
                return null;
            }

            return microtime(true);
        }
        if ($this->executionStart === null) {
            // throw new \RuntimeException(
            //     'Execution has been ended - but never started'
            // );
            return null;
        }

        return max(0, $this->executionEnd - $this->executionStart);
    }

    /**
     * @return float
     */
    public function getExecutionStart()
    {
        return $this->executionStart;
    }

    /**
     * @param float $executionStart
     */
    public function setExecutionStart($executionStart)
    {
        $this->executionStart = $executionStart;
    }

    /**
     * @return float
     */
    public function getExecutionEnd()
    {
        return $this->executionEnd;
    }

    /**
     * @param float $executionEnd
     */
    public function setExecutionEnd($executionEnd)
    {
        $this->executionEnd = $executionEnd;
    }

    /**
     * @return float
     */
    public function getScheduleStart()
    {
        return $this->scheduleStart;
    }

    /**
     * @param float $scheduleStart
     */
    public function setScheduleStart($scheduleStart)
    {
        $this->scheduleStart = $scheduleStart;
    }

    /**
     * @return float
     */
    public function getScheduleEnd()
    {
        return $this->scheduleEnd;
    }

    /**
     * @param float $scheduleEnd
     */
    public function setScheduleEnd($scheduleEnd)
    {
        $this->scheduleEnd = $scheduleEnd;
    }

    /**
     * @return float|int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @return DataPoints
     */
    public function getDataPoints()
    {
        if ($this->dataPoints === null) {
            return new DataPoints();
        }

        return $this->dataPoints;
    }

    public function jsonSerialize()
    {
        $object = (object) [
            'active'           => $this->active,
            'command'          => $this->command,
            'execution_start'  => $this->getExecutionStart(),
            'execution_end'    => $this->getExecutionEnd(),
            'schedule_start'   => $this->getScheduleStart(),
            'schedule_end'     => $this->getScheduleEnd(),
            'state'            => $this->getState(),
            'output'           => $this->getOutput(),
            'performance_data' => $this->dataPoints,
            'exit_status'      => $this->exitCode, // real exit code, like 128 etc
            'type'             => static::OBJECT_TYPE,
            'vars_before'      => $this->varsBefore,
            'vars_after'       => $this->varsAfter,
        ];
        if (null !== $this->checkSource) {
            $object['check_source'] = $this->checkSource;
        }

        return $object;
    }
}
