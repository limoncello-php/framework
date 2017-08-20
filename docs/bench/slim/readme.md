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
    Latency   152.22ms  110.39ms   1.80s    97.45%
    Req/Sec    94.87     45.58   252.00     63.75%
  4608 requests in 5.03s, 0.86MB read
  Socket errors: connect 0, read 0, write 0, timeout 7
Requests/sec:    916.51
Transfer/sec:    174.53KB
```
