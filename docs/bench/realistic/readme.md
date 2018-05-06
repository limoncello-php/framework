### Performance

Performance was tested for the following PHP frameworks (alphabetical order)

- Limoncello 0.9.0-beta
- Lumen 5.6.3
- Slim 3.10.0

### Results

|               | RPS (more is better) |    %   |
| ------------- |----------------------| -------|
| Limoncello    |        14 888        | 176,7% |
| Lumen         |         8 426        | 100,0% |
| Slim          |        12 433        | 147,6% |

RPS - Requests per Second (more is better)

### What is tested

All applications were configured to
- Emulate a dozen HTTP routes. The total number of routes was selected as 10 for plain pages and 10 for API resources such as `Users`, `Roles`, `Posts` and etc with 5 methods each (index, create, read, update and delete) which gives 60 routes in total. All the routes were configured to point to a single handler.
- Request handler is located in a Controller.
- The controller validates input form data and converts from input format to a format suitable for a database.

All middleware (e.g. authentication) and other loads were either removed or minimized to possible minimums.

(For Limoncello support for OAuth Authentication, API, Authorization, Data migration & seeding, User & Role management were kept in place)

So routing, controller support, HTTP responses, data validation and work with PHP Server Application Programming Interface (SAPI) was tested.

During the testing routes were randomly selected out of 60 available for every request. 

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

The applications were configured to run on ports `8090`, `8091` and `8092`. The following [wrk](https://github.com/wg/wrk) command was used to run a test

```bash
wrk -t8 -c130 -d30s -s ./wrk.lua http://<Server IP address>:809X
```

where [wrk.lua](/docs/bench/realistic/wrk.lua) was test script and `<Server IP address>` was a local network IP address of the server and the last number in port `X` was 0, 1 and 2.

The tests were run in the following order: 3 runs for Limoncello, 3 runs for Lumen, 3 runs for Slim and again 3 runs for Limoncello, 3 runs for Lumen and 3 runs for Slim with small pauses between them to write down results. Thus every application had 6 test runs. 

|                                 |  Limoncello  |   Lumen    |    Slim    |
| ------------------------------- | ------------ | ---------- | ---------- |
| Test 1 RPS (1st batch, 1st run) |    14 885    |    8 446   |   12 403   |
| Test 2 RPS (1st batch, 2nd run) |    14 788    |    8 425   |   12 316   |
| Test 3 RPS (1st batch, 3rd run) |    14 923    |    8 433   |   12 559   |
| Test 4 RPS (2nd batch, 1st run) |    14 887    |    8 422   |   12 466   |
| Test 5 RPS (2nd batch, 2nd run) |    14 892    |    8 421   |   12 436   |
| Test 6 RPS (2nd batch, 3rd run) |    14 955    |    8 408   |   12 420   |
|                                 |              |            |            |
| **Average RPS**                 |  **14 888**  |  **8 426** | **12 433** |

RPS - Requests per Second (more is better)

Stress load parameters were selected to cause maximum load on the system up until PHP-FPM starts to fail with `502 Bad Gateway` errors.

CPU usage was close to 100% and memory usage for the whole system was less than 400MB.

### Source code

[Limoncello](/docs/bench/realistic/limoncello)

[Lumen](/docs/bench/realistic/lumen)

[Slim](/docs/bench/realistic/slim)

### Final Thoughts

During the testing it is very important to check timed out and non 2XX/3XX responses. If server receives too many requests it starts to respond with `502 Bad Gateway` errors which should not be counted as RPS.

If you are interested in minimalistic apps test comparision you can find it [here](/docs/bench/minimalistic).
