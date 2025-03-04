<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\Firebase;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;

/**
 * @author Jeroen Spee <https://github.com/Jeroeny>
 */
final class FirebaseTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): FirebaseTransport
    {
        $scheme = $dsn->getScheme();

        if ('firebase' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'firebase', $this->getSupportedSchemes());
        }

        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();
        $projectId = $dsn->getUser();

        return (new FirebaseTransport($this->client, $this->dispatcher))->setHost($host)->setPort($port)->setProjectId($projectId);
    }

    protected function getSupportedSchemes(): array
    {
        return ['firebase'];
    }
}
