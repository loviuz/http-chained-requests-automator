# HTTP Chained Requests Automator
This PHP script automates and chains HTTP requests, extracting values from headers or body and use them for the next requests. Very useful for penetration tests.

![image](https://github.com/loviuz/http-chained-requests-automator/blob/main/screenshots/hcra.jpg?raw=true)

It has been ispired by a business-logic challenge of Port Swigger:

https://portswigger.net/web-security/logic-flaws/examples/lab-logic-flaws-infinite-money

## Installation

First download and install **composer** from here:

https://getcomposer.org/download/

Then:

```bash
$ php composer.phar install
```

## Usage

```bash
$ php hcra.php params.json
```

- params.json: is the filename of the JSON file with specifications of the HTTP requests

## Configuration

To configure HTTP request you have to code your custom JSON file. You can start from the example in the repository:

```json
{
    "configuration": 
    {
        "verbose_level": 1
    }
    ,
    "urls": [
        {
            "title": "First open to get the cookie",
            "url": "http://localhost/mysite/",
            "method": "GET",
            "headers": null,
            "body": null,
            "header-regexp": [
                {
                    "cookie": "/Set-Cookie: (.+?)$/"
                }
            ],
            "body-regexp": null
        },
        {
            "title": "Login",
            "url": "http://localhost/mysite/",
            "method": "POST",
            "headers": {
                "Cookie": "§cookie§",
                "Content-Type": "application/x-www-form-urlencoded"
            },
            "body": "username=admin&password=s3cr3t",
            "header-regexp": [
                {
                    "next_url": "/Location: (.+)/"
                }
            ],
            "body-regexp": null,
            "extra_guzzle_options": [
                {
                    "allow_redirects": false
                }
            ]
        },
        {
            "title": "Get the email",
            "url": "http://localhost§next_url§",
            "method": "GET",
            "headers": {
                "Cookie": "§cookie§"
            },
            "body": null,
            "header-regexp": [
                {
                    "content_type": "/Content-Type: (.+)/",
                    "pragma": "/Pragma: (.+)/"
                }
            ],
            "body-regexp": [
                {
                    "email": "/<p>Your email is (.+)!<\\/p>/"
                }
            ],
            "header-expected":
            {
                "content_type": "text/html; charset=UTF-8",
                "pragma": "no-cache"
            },
            "body-expected":
            {
                "email": "dude@dudelang.com"
            }
        },
        {
            "title": "Get the flags",
            "url": "http://localhost§next_url§?email=§email§",
            "method": "GET",
            "headers": {
                "Cookie": "§cookie§"
            },
            "body": null,
            "header-regexp": [
                {
                    "flag1": "/Set-Cookie: flag2=(.+?);/"
                }
            ],
            "body-regexp": [
                {
                    "flag2": "/<p>Congratulations, the flag is: (.+?)<\\/p>/"
                }
            ]
        }
    ]
}
```

Every JSON object is an HTTP request with specific parameters:
- **configuration -> verbose_level**: the verbosity of the output. It accepts values from 1 to 3
- **title**: the title of the to request
- **url**: the URL to request
- **method**: GET|POST (you could also use PUT, DELETE, etc, but not yet tested!)
- **headers**: a JSON array with all headers you want to send with the request
- **body**: the body of the request in case you send a POST request
- **header-regexp**: an array of regular expressions you want to use to extract values from the headers. IMPORTANT: only the first value per regexp will be matched
- **body-regexp**: like header-regexp, but the values will be matched against the response body
- **header-expected**: an array of key/value to look for in the response headers (useful for test purpose)
- **body-expected**: an array of key/value to look for in the body headers (useful for test purpose)
- **extra_guzzle_options**: array of extra Guzzle options. Here you can find a full list of options: https://docs.guzzlephp.org/en/stable/request-options.html

Example:

```php
"next_url": "/Location: (.+?)$/"
```

This will match the redirection after the first request, for example:
```http
Location: /mysite/welcome
```

If match happens, you can use
```php
§next_url§
```

as a variable on the next requests, so the 2nd url will change from:
```json
"url": "http://localhost§next_url§",
```
to:
```json
"url": "http://localhost/mysite/welcome",
```

until you match another **next_url** values with another regular expression with the next requests.

## TODO
 - better error handling
 - add some logics, so for example the script can restart from a specific request after getting some specific results from the variables
