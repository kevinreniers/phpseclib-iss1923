# Instructions

Starting the different services.

- `docker compose up proftpd` to start the proftpd server
- `docker compose up app` to start the long-running process

Simulating the issue:

After `proftpd` and `app` have started, open a shell on both.

```
docker compose exec proftpd sh
docker compose exec app sh
```

In the `app` service, install `net-tools`.

```
apt update && apt install net-tools
```

Lastly, in both watch the open handles with netstat.

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

