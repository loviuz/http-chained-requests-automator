# HTTP Chained Requests Automator
This PHP script automates and chains HTTP requests, extracting values from headers or body and use them for the next requests. Very useful for penetration tests.

It has been ispired by a business-logic challenge of Port Swigger:

https://portswigger.net/web-security/logic-flaws/examples/lab-logic-flaws-infinite-money

## Usage

```bash
$ php hcra.php params.json DEBUG
```

- params.json: is the filename of the JSON file with specifications of the HTTP requests
- DEBUG: use only for debug purpose. It additionally prints out the body response of every HTTP request

## Configuration

To configure HTTP request you have to code your custom JSON file. You can start from the example in the repository:

```json
[
    {
        "url": "http://localhost/mysite/login",
        "method": "POST",
        "headers": null,
        "body": "username=admin&password=s3cr3t",
        "header-regexp": [
            {
                "next_url": "/Location: (.+?)$/"
            },
            {
                "cookie": "/Set-Cookie: (.+?)$/"
            }
        ],
        "body-regexp": null
    },
    {
        "url": "http://localhost§next_url§",
        "method": "GET",
        "headers": {
            "Cookie": "§cookie§"
        },
        "body": null,
        "header-regexp": null,
        "body-regexp": [
            {
                "email": "/<p>Your email is: (.+?)<\\/p>/"
            }
        ]
    },
    {
        "url": "http://localhost/mysite/sign-up",
        "method": "POST",
        "headers": {
            "Cookie": "§cookie§"
        },
        "body": "op=signup&email=§email§",
        "header-regexp": null,
        "body-regexp": null
    }
]
```

Every JSON object is an HTTP request with specific parameters:
- **url**: the URL to request
- **method**: GET|POST (you could also use PUT, DELETE, etc, but not yet tested!)
- **headers**: a JSON array with all headers you want to send with the request
- **body**: the body of the request in case you send a POST request
- **header-regexp**: an array of regular expressions you want to use to extract values from the headers. IMPORTANT: only the first value per regexp will be matched
- **body-regexp**: like header-regexp, but the values will be matched against the response body

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
 - better error managing
 - add some logics, so for example the script can restart from a specific request after getting some specific results from the variables