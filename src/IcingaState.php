<?php

namespace gipfl\IcingaApi;

class IcingaState
{
    const SERVICE_OK = 0;
    const SERVICE_WARNING = 1;
    const SERVICE_CRITICAL = 2;
    const SERVICE_UNKNOWN = 3;
    const HOST_UP = 0;
    const HOST_DOWN = 1;
    const SOFT_STATE = 0;
    const HARD_STATE = 1;
}
