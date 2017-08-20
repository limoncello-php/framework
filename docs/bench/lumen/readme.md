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
Running 5s test @ http://localhost:8080/api/v1/users
  10 threads and 400 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency    98.93ms   99.13ms   1.74s    94.16%
    Req/Sec    80.11     62.88   353.00     70.14%
  3494 requests in 5.07s, 73.57MB read
  Socket errors: connect 0, read 3494, write 0, timeout 0
  Non-2xx or 3xx responses: 3494
Requests/sec:    688.82
Transfer/sec:     14.50MB
```
