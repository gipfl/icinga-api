<?php

namespace gipfl\IcingaApi;

use InvalidArgumentException;
use function is_array;

class IcingaPerfDataParser
{
    public static function fromIcinga($perfdata)
    {
        if (is_array($perfdata)) {
            return static::fromIcinga2Array($perfdata);
        } else {
            return static::parseString($perfdata);
        }
    }

    public static function fromIcinga2Array($array)
    {
        if (! isset($array['type'])) {
            throw new InvalidArgumentException(
                'Unsupported performance_data format: ' . print_r($array, 1)
            );
        }
        if ($array['type'] !== 'PerfdataValue') {
            throw new InvalidArgumentException(\sprintf(
                "'PerfdataValue' expected, got '%s'\n",
                $array['type']
            ));
        }

        $point = new DataPoint($array['label'], $array['value'], $array['unit']);
        if (isset($array['warn']) && \strlen($array['warn']) > 0) {
            $point->setWarningThreshold(static::parseThreshold($array['warn']));
        }
        if (isset($array['crit']) && \strlen($array['crit']) > 0) {
            $point->setCriticalThreshold(static::parseThreshold($array['crit']));
        }
        if (isset($array['min']) && \strlen($array['min']) > 0) {
            // $point->setMin($array['min']);
        }
        if (isset($array['max']) && \strlen($array['max']) > 0) {
            // $point->setMax($parts[4]));
        }
        if (isset($array['counter']) && \is_bool($array['counter'])) {
            // set Counter?
        }

        return $point;
    }

    public static function parseString($string)
    {
        $parts = \str_getcsv($string, ';', "'");
        $pos = \strrpos($parts[0], '=');
        if ($pos === false) {
            throw new InvalidArgumentException("Got invalid perfdata string: $string");
        }
        $label = \substr($parts[0], 0, $pos);
        list($value, $unit) = static::splitValueUnitString(\substr($parts[0], $pos + 1));

        $point = new DataPoint($label, $value, $unit);
        if (isset($parts[1]) && \strlen($parts[1]) > 0) {
            $point->setWarningThreshold(static::parseThreshold($parts[1]));
        }
        if (isset($parts[2]) && \strlen($parts[2]) > 0) {
            $point->setCriticalThreshold(static::parseThreshold($parts[1]));
        }
        if (isset($parts[3]) && \strlen($parts[3]) > 0) {
            // $point->setMin($parts[3]);
        }
        if (isset($parts[4]) && \strlen($parts[4]) > 0) {
            // $point->setMax($parts[4]));
        }
        // min/max
        return $point;
    }

    public static function parseThreshold($string)
    {
        if ($string[0] === '@') {
            $outsideIsValid = false;
            $string = \substr($string, 1);
        } else {
            $outsideIsValid = true;
        }
        $colon = \strpos($string, ':');
        if ($colon === false) {
            $start = 0;
            $end = $string;
        } else {
            $start = \substr($string, 0, $colon);
            $end = \substr($string, $colon + 1);
        }
        if (\strlen($end) === 0) {
            $end = null;
        }
        // -INFINITY
        if ($start === '~') {
            $start = null;
        }

        return new Threshold(new Range($start, $end), $outsideIsValid);
    }

    protected static function splitValueUnitString($string)
    {
        if (\preg_match('/^(-?\d+(?:\.\d+)?)([a-zA-Z%°]{1,2})?$/u', $string, $v)) {
            if (isset($v[2])) {
                return [$v[1], $v[2]];
            } else {
                return [$v[1], null];
            }
        } else {
            throw new InvalidArgumentException("'$string' is no a valid perfdata value");
        }
    }
}
