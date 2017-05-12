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
    Latency    90.96ms  107.12ms   1.66s    93.29%
    Req/Sec    81.81     55.30   270.00     66.51%
  3676 requests in 5.03s, 0.87MB read
  Socket errors: connect 0, read 3676, write 0, timeout 0
Requests/sec:    731.26
Transfer/sec:    176.39KB
```
