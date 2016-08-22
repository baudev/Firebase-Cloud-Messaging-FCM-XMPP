FCMStream
============
FCMStream is app server to send or receive messages from Firebase Cloud Messaging (FCM) using XMPP Protocol.

Usage
-----
FCMStream will stream the Firebase Cloud Messaging (FCM) server through XMPP protocol for incoming messages and registers callback functions to handle the messages and acknowledgements when the messages arrive.

Issues
-----
Firebase Cloud Messaging server sometimes has delayed issue on deliver messages to the app server or client apps, so the messages will be received on user end after at an average 2 minutes.

Start Service
-----------
To start the service from the shell:

	php FCMStream/service.php

(You may need to prefix "php" with the full path of the executable.)

The service will registers callback functions through stream.php to handle the messages and acknowledgements when the messages arrive.

Make sure you configure the code for your project,
fill the Sender ID and API key with credentials obtained from <a href="https://firebase.google.com/console/">Firebase Console</a>.
Default server address and port have values intended for development;
the production version have different endpoints port (`fcm-xmpp.googleapis.com` and `5235`) see <a href="https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref">XMPP server references</a>.
Please assure also that no firewall blocks the port specified.

When FCMStream lost connection to the FCM server, it will retry to reconnect (configurable options).
