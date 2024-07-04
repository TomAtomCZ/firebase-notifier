Firebase Notifier
=================

Provides new [Firebase](https://firebase.google.com) HTTP v1 API integration for Symfony Notifier.

DSN example
-----------

```
GOOGLE_API_KEY=YOUR_API_KEY
GOOGLE_CLIENT_ID=YOUR_CLIENT_KEY
GOOGLE_CLIENT_SECRET=YOUR_CLIENT_SECRET
FIREBASE_DSN=firebase://PROJECT_ID@default
FIREBASE_JSON='{
                 "key": "value",
                 ...
               }'
```

where:

- `YOUR_API_KEY` is your Google API key
- `YOUR_CLIENT_KEY` is your Google client ID
- `YOUR_CLIENT_SECRET` is your Google client secret
- `PROJECT_ID` is your Firebase project ID

For `FIREBASE_JSON ` you have to download JSON file containing Firebase credentials.
In [Firebase console](https://console.firebase.google.com) navigate to "Project settings" -> "Service accounts".
Click "Generate new private key", download the JSON file and paste its contents.

Google credentials can be obtained in [Google console](https://console.cloud.google.com).

Adding Interactions to a Message
--------------------------------

With a Firebase message, you can use the `AndroidNotification`, `IOSNotification` or `WebNotification` classes to add
[message options](https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref.html).

```php
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Bridge\Firebase\Notification\AndroidNotification;

$chatMessage = new ChatMessage('');

// Create AndroidNotification options
$androidOptions = (new AndroidNotification('/topics/news', []))
    ->icon('myicon')
    ->sound('default')
    ->tag('myNotificationId')
    ->color('#cccccc')
    ->clickAction('OPEN_ACTIVITY_1')
    // ...
    ;

// Add the custom options to the chat message and send the message
$chatMessage->options($androidOptions);

$chatter->send($chatMessage);
```

Resources
---------

* [Contributing](https://symfony.com/doc/current/contributing/index.html)
* [Report issues](https://github.com/symfony/symfony/issues) and
  [send Pull Requests](https://github.com/symfony/symfony/pulls)
  in the [main Symfony repository](https://github.com/symfony/symfony)
