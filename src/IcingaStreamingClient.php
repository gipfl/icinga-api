<?php

namespace gipfl\IcingaApi;

use Clue\JsonStream\StreamingJsonParser;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use gipfl\IcingaApi\ApiEvent\CheckResultApiEvent;
use gipfl\IcingaApi\ReactGlue\ConnectedConnector;
use gipfl\ReactUtils\RetryUnless;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Stream\ReadableStreamInterface;
use RuntimeException;
use function base64_encode;
use function count;
use function date;
use function json_encode;
use function sprintf;
use function strlen;

/**
 * Work in progress.
 *
 * This emits:
 * - connection(ConnectionInterface)
 * - checkResult(CheckResultApiEvent)
 */
class IcingaStreamingClient implements EventEmitterInterface
{
    use EventEmitterTrait;
    use LoggerAwareTrait;

    const DEFAULT_USER_AGENT = 'gipfl/IcingaClient v0.1.0';

    /** @var LoopInterface */
    protected $loop;

    protected $cnt = 0;

    protected $cntPerf = 0;

    protected $bytes = 0;

    /** @var StreamingJsonParser */
    protected $parser;

    protected $host;

    protected $port;

    protected $user;

    protected $pass;

    protected $shuttingDown = false;

    /** @var PromiseInterface|null */
    protected $connecting;

    /** @var ConnectionInterface|null */
    protected $connection;

    protected $types = [
        'CheckResult',
        'StateChange',
        'Notification',
        'AcknowledgementSet',
        'AcknowledgementCleared',
        'CommentAdded',
        'CommentRemoved',
        'DowntimeAdded',
        'DowntimeRemoved',
        'DowntimeStarted',
        'DowntimeTriggered',
    ];

    public function __construct(LoopInterface $loop, $host, $port, $user, $pass)
    {
        $this->loop = $loop;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->logger = new NullLogger();
        $stats = function () {
            $this->stats();
        };
        $loop->addPeriodicTimer(15, $stats);
        $loop->futureTick($stats);
    }

    public function run()
    {
        $this->logger->info("Connecting to " . $this->getUrl());
        $this->keepConnecting();
    }

    public function close()
    {
        if ($this->connection) {
            $this->shuttingDown = true;
            $this->connection->close();
            $this->connection = null;
        }
    }

    public function filterTypes(array $types)
    {
        $this->types = $types;

        return $this;
    }

    protected function getUrl()
    {
        return sprintf(
            "https://%s:%d/v1/events",
            $this->host,
            $this->port
        );
    }

    protected function keepConnecting()
    {
        if ($this->connecting || $this->shuttingDown) {
            return;
        }

        $callback = function () {
            return $this
                ->connect()
                ->then(function (ConnectionInterface $connection) {
                    $this->connection = $connection;
                    return $this->startStreaming();
                });
        };
        $onSuccess = function () {
            $this->connecting = null;
            $this->logger->info('Stream is ready');
        };
        $this->connecting = RetryUnless::succeeding($callback)
            ->setInterval(0.2)
            ->slowDownAfter(10, 10)
            ->run($this->loop)
            ->then($onSuccess)
        ;
    }

    protected function connect()
    {
        $this->parser = new StreamingJsonParser();
        $connector = new Connector($this->loop, [
            'timeout' => 5,
            'tls' => [
                // set some SSL/TLS specific options
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true,
            ]
        ]);

        return $connector->connect('tls://' . $this->host . ':' . $this->port)
            ->then(function (ConnectionInterface $connection) {
                $this->emit('connection', [$connection]);
                return $connection;
            });
    }

    protected function startStreaming()
    {
        $connector = new ConnectedConnector($this->connection);
        $browser = new Browser($this->loop, $connector);
        $browser = $browser->withTimeout(5);
        $headers = $this->prepareHeaders();
        $body = json_encode([
            'types' => $this->types,
            'queue' => 'none', // Queue has no effect, but is required
            // 'filter' => 'regex("^vz.*|^app.*", event.host)'
        ]);

        return $browser
            ->requestStreaming('POST', $this->getUrl(), $headers, $body)
            ->then(function (ResponseInterface $response) {
                $this->onResponse($response);
            });
    }

    protected function onResponse(ResponseInterface $response)
    {
        $body = $response->getBody();
        assert($body instanceof ReadableStreamInterface);

        $body->on('data', function ($chunk) {
            $this->onData($chunk);
        });
        $body->on('error', function (Exception $error) {
            $this->logger->error($error->getMessage());
        });
        $body->on('close', function () {
            if (! $this->shuttingDown) {
                $this->logger->info('Connection closed, reconnecting to ' . $this->getUrl());
                $this->keepConnecting();
            }
        });
    }

    protected function onData($chunk)
    {
        $this->bytes += strlen($chunk);
        foreach ($this->parser->push($chunk) as $object) {
            try {
                if (! isset($object['type'])) {
                    throw new RuntimeException(
                        'Object contains no type property: ' . $chunk
                    );
                }

                switch ($object['type']) {
                    case 'CheckResult':
                        $event = CheckResultApiEvent::fromObject((object) $object);
                        $this->cnt++;
                        $this->cntPerf += count($event->getCheckResult()->getDataPoints());
                        $this->emit('checkResult', [$event]);
                        break;
                    default:
                        $this->logger->error(sprintf(
                            'Type "%s" is not supported',
                            $object['type']
                        ));
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function stop()
    {
        $this->parser = null;
    }

    protected function stats()
    {
        $this->logger->info(sprintf(
            '%s: %d results, %d perfValues, %d bytes',
            date('H:i:s'),
            $this->cnt,
            $this->cntPerf,
            $this->bytes
        ));
        $this->cnt = 0;
        $this->cntPerf = 0;
        $this->bytes = 0;
    }

    protected function prepareHeaders()
    {
        return [
            'Accept'        => 'application/json',
            'Authorization' => $this->prepareAuthorizationHeader(),
            'User-Agent'    => static::DEFAULT_USER_AGENT,
            'Content-Type'  => 'application/json',
        ];
    }

    protected function prepareAuthorizationHeader()
    {
        return 'Basic ' . base64_encode($this->user . ':' . $this->pass);
    }
}
