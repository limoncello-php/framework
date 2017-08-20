### Installation

File `composer.lock` is included to the project so you can have consistent environment and install the project with the following command.

```bash
$ composer build
```

### Run test bench

Start the server with

```bash
$ php -d zend.assertions=-1 -S 0.0.0.0:8080 -t public public/index.php
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
    Latency   128.67ms  129.60ms   1.77s    96.29%
    Req/Sec   119.44     76.89   272.00     58.35%
  5934 requests in 5.04s, 1.19MB read
  Socket errors: connect 0, read 0, write 0, timeout 6
Requests/sec:   1178.49
Transfer/sec:    241.72KB
```
