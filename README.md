
# LogLaddy
The LogLaddy is an implementation of the PSR-3 Logger Interface, but aimed at user interface messaging
It relies on \Psr\Log\LoggerTrait and \HexMakina\Debugger\Debugger

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HexMakina/LogLaddy/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/HexMakina/LogLaddy/?branch=main)
<img src="https://img.shields.io/badge/PSR-3-brightgreen" alt="PSR-3 Compliant" />
<img src="https://img.shields.io/badge/PSR-12-brightgreen" alt="PSR-12 Compliant" />
<img src="https://img.shields.io/badge/PHP-7.0-brightgreen" alt="PHP 7.0 Required" />
[![License](http://poser.pugx.org/hexmakina/log-laddy/license)](https://packagist.org/packages/hexmakina/log-laddy)
[![Latest Stable Version](http://poser.pugx.org/hexmakina/log-laddy/v)](https://packagist.org/packages/hexmakina/log-laddy)

# Usage


To create messages outside of errors and exceptions, first initialise the LogLaddy, with a compatible state agent (for storing user messages)

```
$l = new LogLaddy(StateAgentInterface $agent);
```

Then call one of the messaging method:

```
// for detailed debug information
$l->debug($message, array $context = array())

// for interesting events 
$l->info($message, array $context = array())

// for normal but significant events (and success messages)
$l->notice($message, array $context = array())

// for exceptional occurrences that are not errors
$l->warning($message, array $context = array())

// for runtime errors that do not require immediate action but should typically be logged and monitored
$l->error($message, array $context = array())

// for critical condition (Application component unavailable, unexpected exception)
$l->critical($message, array $context = array())

// when action must be taken immediately (website down, database unavailable, etc.)
$l->alert($message, array $context = array())

// when the system is unusable
$l->emergency($message, array $context = array())

```

