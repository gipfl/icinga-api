<?php

namespace gipfl\IcingaApi\IcingaObject;

use JsonSerializable;

class CheckResultVars implements JsonSerializable
{
    /** @var int */
    protected $attempt;

    /** @var boolean */
    protected $reachable;

    /** @var int */
    protected $state;

    /** @var int */
    protected $stateType;

    public static function fromObject($object)
    {
        if (! isset($object->state_type)) {
            var_dump($object);
            exit;
        }
        $self = new static;
        $self->attempt   = $object->attempt;
        $self->reachable = $object->reachable;
        $self->state     = $object->state;
        $self->stateType = $object->state_type;

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'attempt'    => $this->attempt,
            'reachable'  => $this->reachable,
            'state'      => $this->state,
            'state_type' => $this->stateType,
        ];
    }

    protected function __construct()
    {
    }
}
