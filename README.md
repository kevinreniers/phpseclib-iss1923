# Instructions

Starting the different services.

- `docker compose up proftpd` to start the proftpd server
- `docker compose up app` to start the long-running process
- `docker compose up patched-app` to start the long-running process with patched vendor to also `SFTP::ping` as a 
  connectivity checker.

Simulating the issue:

After `proftpd` and `app`/`patched-app` have started, open a shell on both.

```
docker compose exec proftpd sh
docker container exec -it <app/patched-app_container_name> sh
```

In the `app`/`patched-app` service, watch the open handles.

```
watch -n2 "netstat -an | grep :2222"
```

Generate some work using `docker compose up generator` from another shell. Then, wait until proftpd idles out the 
connection and the underlying TCP connection on the `app` service enters CLOSE_WAIT like so:

```
tcp        1      0 172.22.0.4:56744        172.22.0.3:2222         CLOSE_WAIT
```

Then, generate some work again: `docker compose up generator`. In the open `app` service, you'll now see a message 
similar to this:

```
phpseclib-proftpd-app-226  | string(129) "Unable to write file at location: 221dc1730c37951fe77e132a91ac9b29c9ae0267654689e738274c58c89f3eb7. Connection closed prematurely"
```

**PROBLEM 1**: This was the first issue we ran into, SFTP::isConnected() behaves unexpectedly. It will return true when 
the connection has effectively closed already.

We tried to work around this issue by adding a League/Flysystem `League\Flysystem\PhpseclibV3\ConnectivityChecker` 
that does this. That triggers problem 2.

**PROBLEM 2**: SFTP::ping does not properly reconnect or allow data to transfer the new connection.

In `vendor/league/flysystem-sftp-v3` change the `isConnected` function of `SimpleConnectivityChecker` to: 

```php
public function isConnected(SFTP $connection): bool
{
    return $connection->ping() && $connection->isConnected();
}
```

After this change, we ran into the following issue when doing this:

- Start the proftpd server `docker compose up proftpd`
- Start the app service `docker compose up app`
- Generate a work instruction `docker compose up generator`

Wait until the connection is removed server-side because of the idle timeout. In the app service, `netstat -an` will 
report the connection in `CLOSE_WAIT`.

Then, generate multiple work instructions with `docker compose up generator`. This will error out like so:

```
phpseclib-proftpd-app-226  | string(127) "Unable to write file at location: 45fe39749e0139156594153953c622e122943d5049ddc039258a15caeb0a7bba. Connection closed by server"
phpseclib-proftpd-app-226  | string(127) "Unable to write file at location: 45fe39749e0139156594153953c622e122943d5049ddc039258a15caeb0a7bba. Connection closed by server"
phpseclib-proftpd-app-226  | string(128) "Unable to write file at location: 6b96f398809760a7bd891b51ba17ce84874e8d58f3d1b0b9de2cebcd1a9f119d. No data received from server"
...
```

This is unexpected because we expected that ping would transparently reconnect to the SFTP server.
