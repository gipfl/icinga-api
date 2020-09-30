<?php

namespace gipfl\IcingaApi\ReactGlue;

use React\Socket\Connection;
use React\Socket\ConnectionInterface;

/**
 * Workaround, unless we have https://github.com/reactphp/socket/pull/252
 */
class PeerCertificate
{
    /**
     * @param ConnectionInterface $connection
     * @return TlsPeer|\React\Socket\TlsPeer|null
     */
    public static function eventuallyGetTlsPeer(ConnectionInterface $connection)
    {
        if (! $connection instanceof Connection) {
            return null;
        }

        $peer = null;
        if (\method_exists($connection, 'hasTlsPeer')) {
            if ($connection->hasTlsPeer()) {
                $peer = $connection->getTlsPeer();
            }
        } elseif (property_exists($connection, 'stream')) {
            $peer = TlsPeer::fromContextOptions(
                \stream_context_get_options($connection->stream)
            );
        }

        return $peer;
    }
}
