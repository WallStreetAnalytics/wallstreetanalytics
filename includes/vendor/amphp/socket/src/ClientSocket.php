<?php

namespace Amp\Socket;

use Amp\ByteStream\ClosedException;
use Amp\Failure;
use Amp\Promise;

class ClientSocket extends Socket {
    /**
     * {@inheritdoc}
     *
     * @param ClientTlsContext|null $tlsContext
     */
    public function enableCrypto(ClientTlsContext $tlsContext = null): Promise {
        if (($resource = $this->getResource()) === null) {
            return new Failure(new ClosedException("The socket has been closed"));
        }

        $tlsContext = $tlsContext ?? new ClientTlsContext;

        return Internal\enableCrypto($resource, $tlsContext->toStreamContextArray());
    }
}
