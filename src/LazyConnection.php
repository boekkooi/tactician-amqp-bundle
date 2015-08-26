<?php
namespace Boekkooi\Bundle\AMQP;

/**
 * A class used to initialize a @see \AMQPConnection connection
 * @final
 */
class LazyConnection
{
    /**
     * @var array
     */
    private $connectionArguments;

    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @param string $host The host to connect too. Note: Max 1024 characters.
     * @param int $port Port on the host.
     * @param string $vhost The virtual host on the host. Note: Max 128 characters.
     * @param string $login The login name to use. Note: Max 128 characters.
     * @param string $password The login password. Note: Max 128 characters.
     * @param float|int $readTimeout Timeout in for income activity. Note: 0 or greater seconds.
     * @param float|int $writeTimeout Timeout in for outcome activity. Note: 0 or greater seconds.
     * @param float|int $connectTimeout Connection timeout. Note: 0 or greater seconds.
     */
    public function __construct(
        $host = 'localhost',
        $port = 5672,
        $vhost = '/',
        $login = 'guest',
        $password = 'guest',
        $readTimeout = 10,
        $writeTimeout = 10,
        $connectTimeout = 10
    ) {
        $this->connectionArguments = [
            'host' => $host,
            'port' => $port,
            'vhost' => $vhost,
            'login' => $login,
            'password' => $password,
            'read_timeout' => $readTimeout,
            'write_timeout' => $writeTimeout,
            'connect_timeout' => $connectTimeout
        ];
    }

    /**
     * Get a connected managed @see \AMQPConnection instance.
     *
     * @return \AMQPConnection
     */
    public function instance()
    {
        if ($this->connection !== null) {
            $conn = $this->connection;

            if (!$conn->isConnected()) {
                $conn->reconnect();
            }

            return $conn;
        }

        return $this->connection = $this->create();
    }

    /**
     * Create a new connected @see \AMQPConnection instance.
     *
     * @return \AMQPConnection
     */
    public function create()
    {
        $conn = new \AMQPConnection($this->connectionArguments);
        if (!$conn->isConnected()) {
            $conn->connect();
        }

        return $conn;
    }
}
