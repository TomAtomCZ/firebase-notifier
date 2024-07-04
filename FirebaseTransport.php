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

use Google\Client;
use Google\Exception;
use Google\Service\FirebaseCloudMessaging;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Jeroen Spee <https://github.com/Jeroeny>
 */
final class FirebaseTransport extends AbstractTransport
{
    protected const HOST = 'fcm.googleapis.com/v1/projects/';
    protected string $projectId;

    public function __construct(
        ?HttpClientInterface      $client = null,
        ?EventDispatcherInterface $dispatcher = null,
    )
    {
        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf('firebase://%s', $this->getEndpoint());
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage && (null === $message->getOptions() || $message->getOptions() instanceof FirebaseOptions);
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function setProjectId($projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    protected function getEndpoint(): string
    {
        return ($this->host ?: $this->getDefaultHost()) . ($this->projectId ?: '') . '/messages:send';
    }

    /**
     * @throws Exception
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof ChatMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, ChatMessage::class, $message);
        }

        if (empty($_ENV['FIREBASE_JSON'])) {
            throw new InvalidArgumentException('The ENV variable FIREBASE_JSON is required to be set.');
        }

        $endpoint = sprintf('https://%s', $this->getEndpoint());
        $options = $message->getOptions()?->toArray() ?? [];
        $validateOnly = false;

        if (!$options['topic']) {
            throw new InvalidArgumentException(sprintf('The "%s" transport required the "topic" option to be set.', __CLASS__));
        }

        if (isset($options['notification']['validate_only'])) {
            $validateOnly = $options['notification']['validate_only'];
            unset($options['notification']['validate_only']);
        }

        $options['notification']['body'] = $message->getSubject();

        if (empty($options['data'])) {
            unset($options['data']);
        }

        $options = ['message' => $options, 'validate_only' => $validateOnly];

        $response = $this->client->request('POST', $endpoint, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken()),
                'Content-Type' => 'application/json; UTF-8'
            ],
            'json' => array_filter($options)
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote Firebase server.', $response, 0, $e);
        }

        $contentType = $response->getHeaders(false)['content-type'][0] ?? '';
        $jsonContents = str_starts_with($contentType, 'application/json') ? $response->toArray(false) : null;
        $errorMessage = null;

        if ($jsonContents && isset($jsonContents['error']['message'])) {
            $errorMessage = $jsonContents['error']['message'];
        } elseif (200 !== $statusCode) {
            $errorMessage = $response->getContent(false);
        }

        if (null !== $errorMessage) {
            throw new TransportException('Unable to post the Firebase message: ' . $errorMessage, $response);
        }

        $success = $response->toArray(false);
        $messageId = isset($success['name']) ? basename($success['name'], '/') : '';
        $sentMessage = new SentMessage($message, (string)$this);
        $sentMessage->setMessageId($messageId);

        return $sentMessage;
    }

    /**
     * Create a temporary json file from the ENV variable and set it to the client auth config
     * @return string|null
     * @throws Exception
     */
    protected function getAccessToken(): ?string
    {
        $jsonTmp = tempnam(sys_get_temp_dir(), 'firebase_json');
        file_put_contents($jsonTmp, $_ENV['FIREBASE_JSON']);
        $client = new Client();
        $client->setAuthConfig($jsonTmp);
        $client->addScope(FirebaseCloudMessaging::FIREBASE_MESSAGING);
        $client->addScope(FirebaseCloudMessaging::CLOUD_PLATFORM);
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken();
        unlink($jsonTmp);
        return $accessToken['access_token'] ?? null;
    }
}
