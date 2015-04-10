# behat-js-errorlog

JS Error Logger Context for Behat 3.

The Context extends from Mink and was tested with the selenium2 driver. Basically it looks after every Step if there is
an error present.

To get this working you need to add a js snippet into the html code of your project.


## Selenium

The Context was tested soley with the selenium2 driver. There can be Situations that the check for js errors after a
step can break the testcase. There was no reason why the setup behaves that way.

The only workaround was to introduce a ignore tag called `ignore-js-error`. Scenarios with that tag will not be checked
in any way.


## Installation

Just install via composer


## Configuration

### behat

Add the context to your behat confguration file. There are no Constructor Parameters


### your application

You need to adjust your application to work with the context.

```html
<!-- Catch JS Errors for Selenium-->
<script type="text/javascript">
    window.jsErrors = [];

    window.onerror = function (errorMessage) {
        window.jsErrors[window.jsErrors.length] = errorMessage;
    };
</script>
```

Depending if you are using a javascript framework you might to adjust to that as well

#### Angular

If you are using angular you might want to add your own exception handler to catch js errors.

```javascript
"use strict";

!function (angular) {

    var Module = angular.module('myModule');

    Module.provider("$exceptionHandler",
        {
            $get: ['errorLogService', function (errorLogService) {
                return (errorLogService);
            }]
        }
    );

    //
    // Factory to provider error log service
    // - simple console logger
    //
    Module.factory(
        "errorLogService",
        ['$log', function ($log) {

            function log(exception, cause) {
                // Default behavior, log to browser console
                $log.error.apply($log, arguments);

                // for selenium
                exception.message += ' (caused by "' + cause + '")';
                if ($window.jsErrors !== undefined) {
                    $window.jsErrors[$window.jsErrors.length] = exception.message;
                }
            }

            // Return the logging function.
            return (log);
        }]
    );

}(angular);

```


### Scenarios

You don't need to adjust anything in your Scenarios to get this working. But there is way if you don't want the Context
to run at a specific Scenario or a whole Feature File. You can use the tag `@ignore-js-logging` if you don't want any
step to check for js errors.
