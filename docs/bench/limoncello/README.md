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
    Latency   124.60ms  108.61ms   1.77s    97.93%
    Req/Sec   115.82     41.24   343.00     68.47%
  5754 requests in 5.03s, 1.15MB read
  Socket errors: connect 0, read 0, write 0, timeout 4
Requests/sec:   1143.68
Transfer/sec:    234.54KB
```
