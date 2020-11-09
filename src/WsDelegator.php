<?php

namespace MahmutBayri\WebSocketDelegator;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class WsDelegator implements MessageComponentInterface
{
    protected SplObjectStorage $clients;
    public ?\Closure $onGetConnect = null;
    public ?\Closure $onGetMessage = null;
    public ?\Closure $onGetClose = null;

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->setConnectionName($conn, 'connection_' . $conn->resourceId);
        echo "New connection! ({$conn->resourceId})\n";
        $this->onGetConnect($conn);
    }

    /**
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->onGetMessage($msg);
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->onGetClose($this->getConnectionName($conn));
        echo "Connection {$conn->resourceId} has disconnected\n";
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function sendMessage($message)
    {
        foreach ($this->clients as $client) {
            /** @var \Ratchet\WebSocket\WsConnection $client */
            $client->send($message);
        }
    }

    public function getConnectionName(ConnectionInterface $connection)
    {
        return $this->clients->offsetGet($connection);
    }

    private function setConnectionName(ConnectionInterface $connection, $name)
    {
        $this->clients->offsetSet($connection, $name);
    }
}
