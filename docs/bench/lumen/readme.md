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
    Latency    87.34ms   67.59ms 677.33ms   78.61%
    Req/Sec    78.77     37.71   250.00     74.08%
  3703 requests in 5.05s, 0.87MB read
  Socket errors: connect 0, read 3703, write 0, timeout 0
Requests/sec:    733.59
Transfer/sec:    177.04KB
```
