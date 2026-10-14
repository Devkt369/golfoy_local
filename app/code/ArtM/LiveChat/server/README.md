# ArtM Live Chat Server

This Socket.IO service connects the `ArtM_LiveChat` Magento module to MySQL. Magento customer and admin sessions are exchanged for short-lived HMAC tokens; the service derives identities from those tokens rather than trusting IDs sent in socket events.

## Local setup

1. From the Magento root, enable the module and create its chat tables:

   ```powershell
   php bin/magento module:enable ArtM_LiveChat
   php bin/magento setup:upgrade
   php bin/magento cache:flush
   ```

2. Copy `app/code/ArtM/LiveChat/server/.env.example` to `.env` in that folder. Set `ARTM_LIVECHAT_SECRET` to a random value of at least 32 characters and set the MySQL values to those in `app/etc/env.php`. PHP and Node both read this ignored file; a process environment variable overrides the file value. Keep the secret out of source control.
3. Set `TABLE_PREFIX` if Magento has a table prefix and `MAGENTO_ORIGIN` to the storefront/admin origin.
4. In `Stores > Configuration > ArtM > Live Chat`, set the realtime server URL (default `http://localhost:3001`).
5. Install and run the Node service:

   ```powershell
   cd app/code/ArtM/LiveChat/server
   npm install
   npm start
   ```

The widget appears only for signed-in customers. Admin users with the `ArtM_LiveChat::inbox` permission can use `Customers > Customer Care Chat`.

### Customer request and agent queue

Before creating a chat request, the widget asks the customer to choose a category, enter an order number for Order requests, summarize what they need help with, and briefly describe the issue. Submitted requests appear in the admin inbox with those details and queue time. Agents can accept a request to join the chat or reject it; rejected requests remain available in the queue. Customers see their live queue position and an in-chat notice naming the agent when the request is accepted.

Open conversations are automatically closed after 30 minutes without an update. When a customer returns to a conversation assigned to an agent who is no longer connected to the live-chat inbox, the assignment is cleared and the conversation returns to the queue. After an inactive conversation has closed, the customer's next visit starts the new-chat flow.

After changing the module schema or storefront/admin assets, run Magento setup and deploy the relevant static assets from the Magento root:

```powershell
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f en_US
php bin/magento cache:clean
```

Restart the Node chat service after changing `server.js`.

If Magento uses HTTPS, the Socket.IO URL must also use HTTPS/WSS to avoid browser mixed-content blocking; configure local TLS and add that host to the module CSP whitelist.

Desktop browser notifications require a secure context (HTTPS, or localhost). On a LAN over plain HTTP, the in-chat toast and unread badges still work, but the browser will not allow OS notifications; configure HTTPS for both the storefront and Socket.IO endpoint to enable them.

## Multiple Node instances

The server uses one process by default. To share Socket.IO rooms and broadcasts between instances, provide the same `REDIS_URL` to every instance. Redis is optional for a single process; if configured Redis cannot connect within three seconds, the server starts with local broadcasts so single-instance chat remains available. In that fallback mode, `/healthz` returns an unhealthy response because cross-instance messaging is unavailable. Restart the server after Redis is restored to enable the adapter.

Run the instances on separate ports with the same Redis URL, MySQL settings, and `ARTM_LIVECHAT_SECRET`. For example, in separate PowerShell terminals from the server directory:

```powershell
$env:PORT='3001'; npm start
```

```powershell
$env:PORT='3002'; npm start
```

Place a load balancer or reverse proxy in front of both ports and set the Magento Live Chat server URL to that public endpoint. Enable sticky sessions because Socket.IO can use HTTP polling, and allow WebSocket upgrades. Add the endpoint to Magento's CSP whitelist and set `MAGENTO_ORIGIN` to the Magento storefront/admin origins. Agent presence is counted across instances through the Redis adapter.

Each Node instance creates its own MySQL pool of up to 10 connections, so include all instances when sizing MySQL's connection limit. Keep Redis private to the application network and use TLS/authentication for managed or remote Redis services.