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
    Latency   164.20ms  135.40ms   1.80s    96.98%
    Req/Sec    89.99     32.80   290.00     72.78%
  4467 requests in 5.05s, 850.65KB read
  Socket errors: connect 0, read 0, write 0, timeout 11
Requests/sec:    885.37
Transfer/sec:    168.60KB
```
