<?php

namespace gipfl\IcingaApi\ReactGlue;

use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;
use function React\Promise\resolve;

final class ConnectedConnector implements ConnectorInterface
{
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function connect($uri)
    {
        return resolve($this->connection);
    }
}
