### Performance

Performance was tested for the following PHP frameworks (alphabetical order)

- Limoncello 0.7.2
- Lumen 5.4.27
- Slim 3.8.1

### Results

|               | RPS (more is better) |    %   |
| ------------- |----------------------| -------|
| Limoncello    |        1178,49       | 171,1% |
| Slim          |        916,51        | 133,1% |
| Lumen         |        688,82        | 100,0% |

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
- Code was merged into smaller number of files (esp. for Slim).
- Composer autoloads were optimized.

### Methodology

Single threaded built-in into PHP server was used. PHP version 7.1.8 (with OPcache).

After an application were deployed and started it was manually checked in browser that it works fine. The page was reloaded a few times so PHP had enough opportunities to compile and optimize execution of the application. After a few seconds later stress load was started

```bash
wrk -t10 -d5s -c400 http://127.0.0.1:8080/
```

The test run a few times to make sure results did not differ for more than a couple of percentage points. The best result was chosen.

### Source code

[Limoncello](/docs/bench/limoncello)

[Lumen](/docs/bench/lumen)

[Slim](/docs/bench/slim)
