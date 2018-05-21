### Performance

Performance was tested for the following PHP frameworks (alphabetical order)

- Limoncello 0.9.0-beta
- Lumen 5.6.3
- Slim 3.10.0

### Results

|               | RPS (more is better) |    %   |
| ------------- |----------------------| -------|
| Limoncello    |        29 988        | 135,5% |
| Lumen         |        22 123        | 100,0% |
| Slim          |        23 692        | 107,1% |

RPS - Requests per Second (more is better)

### What is tested

All applications were configured to
- Single HTTP route for home page ('/').
- Request handler is located in a Controller.
- The controller responds with a short pure text response (e.g. 'Hello world').

All middleware (e.g. authentication) and other loads were either removed or minimized to possible minimums.

So routing, controller support, HTTP responses and work with PHP Server Application Programming Interface (SAPI) was tested.

### Optimizations

All known optimizations were applied to all frameworks including

- Not used code was minimized as much as possible.
- Development dependencies were removed.
- Caching (where supported)
- Code was merged into smaller number of files (esp. for Slim).
- Composer autoloads were optimized.

### Hardware and Software

Server

- CPU AMD Ryzen 1700@3000MHz (X370), RAM 16Gb@3400MHz, 32GB external flash drive (USB3)
- OS Arch Linux (Linux 4.16.6), PHP 7.2.5 with Zend OPcache v7.2.5, PHP-FPM, nginx 1.14.0

Requests Generator

- Low end notebook connected to the server via Gigabit switch. 

### Methodology

The applications were configured to run on ports `8080`, `8081` and `8082`. The following [wrk](https://github.com/wg/wrk) command was used to run a test

```bash
wrk -t8 -c130 -d30s http://<Server IP address>:808X
```

where `<Server IP address>` was a local network IP address of the server and the last number in port `X` was 0, 1 and 2.

The tests were run in the following order: 3 runs for Limoncello, 3 runs for Lumen, 3 runs for Slim and again 3 runs for Limoncello, 3 runs for Lumen and 3 runs for Slim with small pauses between them to write down results. Thus every application had 6 test runs. 

|                                 |  Limoncello  |   Lumen    |    Slim    |
| ------------------------------- | ------------ | ---------- | ---------- |
| Test 1 RPS (1st batch, 1st run) |    29 878    |   22 094   |   23 661   |
| Test 2 RPS (1st batch, 2nd run) |    30 411    |   22 373   |   23 678   |
| Test 3 RPS (1st batch, 3rd run) |    30 021    |   22 226   |   23 708   |
| Test 4 RPS (2nd batch, 1st run) |    29 861    |   21 968   |   23 714   |
| Test 5 RPS (2nd batch, 2nd run) |    30 261    |   21 775   |   23 693   |
| Test 6 RPS (2nd batch, 3rd run) |    29 493    |   22 302   |   23 697   |
|                                 |              |            |            |
| **Average RPS**                 |  **29 988**  | **22 123** | **23 692** |

RPS - Requests per Second (more is better)

Stress load parameters were selected to cause maximum load on the system up until PHP-FPM starts to fail with `502 Bad Gateway` errors.

CPU usage was close to 100% and memory usage for the whole system was less than 400MB.

Here is a screen-shot taken during a test showing resources usage.

![Monitor Screen-shot](/docs/bench/minimalistic/img/monitor.png)

### Source code

[Limoncello](/docs/bench/minimalistic/limoncello)

[Lumen](/docs/bench/minimalistic/lumen)

[Slim](/docs/bench/minimalistic/slim)

### Final Thoughts

During the testing it is very important to check timed out and non 2XX/3XX responses. If server receives too many requests it starts to respond with `502 Bad Gateway` errors which should not be counted as RPS.

This is an example of a good test without timed out or failed responses

```text
Running 30s test @ http://<Server IP address>:8080
  8 threads and 130 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     4.19ms  549.57us  20.86ms   88.02%
    Req/Sec     3.82k   117.42     5.26k    84.92%
  913503 requests in 30.04s, 174.19MB read
Requests/sec:  30411.17
Transfer/sec:      5.80MB
``` 
This is an example of a bad test with many failed responses which inflate RPS

```text
Running 30s test @ http://<Server IP address>:8080
  8 threads and 200 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     5.25ms    2.47ms  25.38ms   69.28%
    Req/Sec     4.77k   127.11     5.83k    73.62%
  1138284 requests in 30.04s, 255.01MB read
  Non-2xx or 3xx responses: 303782
Requests/sec:  37893.87
Transfer/sec:      8.49MB
```

Initially, the built-in PHP server (aka development server) was used for testing which could be run with

```bash
$ php -d zend.assertions=-1 -d assert.exception=1 -S 0.0.0.0:8080 -t ./public/
```

However it provided very unstable results. For example it could give 3 000 RPS for the first run and a half of that for next one. For this reason, it looked to be a good idea to run multiple tests and select only the best result. From such testing `limoncello` had the best result, `slim` the second and `lumen` the third. As this performance test shows, PHP development server should not be used in performance testing as it can lead to inaccurate results and a dedicated server with production software stack should be used instead.
