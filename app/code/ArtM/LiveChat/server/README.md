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

If Magento uses HTTPS, the Socket.IO URL must also use HTTPS/WSS to avoid browser mixed-content blocking; configure local TLS and add that host to the module CSP whitelist.