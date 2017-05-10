### Installation

File `composer.lock` is included to the project so you can have consistent environment and install the project with the following command.

```bash
$ composer build
```

### Run test bench

Start the server with

```bash
$ php -S 0.0.0.0:8080 -t public public/index.php
```

and run the test bench in separate console

```bash
wrk -t10 -d5s -c400 http://127.0.0.1:8080/
```

Results do vary slightly and one of the best results below
```text
Running 5s test @ http://127.0.0.1:8080/
  10 threads and 400 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   126.52ms  109.34ms   1.77s    97.47%
    Req/Sec   115.21     58.81   250.00     58.91%
  5696 requests in 5.04s, 1.14MB read
  Socket errors: connect 0, read 0, write 0, timeout 6
Requests/sec:   1130.88
Transfer/sec:    231.92KB
```
