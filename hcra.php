<?php

use GuzzleHttp\Psr7;

include __DIR__.'/vendor/autoload.php';

$colors = new Wujunze\Colors();

if( count($argv) < 2 ){
    usage();
    exit();
}

if( !file_exists($argv[1]) ){
    usage();
    echo $colors->getColoredString('
File '.$argv[1].' does not exists or is not readable!

', 'red', null);
    exit();
}

$pointer = $colors->getColoredString('[*]', 'blue');

// Reading JSON
$requests = [];

echo $pointer.' Parsing '.$colors->getColoredString($argv[1], 'yellow').'...';

if( $requests = json_decode( file_get_contents($argv[1]), 1 ) ){
    echo $colors->getColoredString("OK\n", 'green');
} else {
    echo $colors->getColoredString('ERR
It does not seems a good JSON file!', 'green').'
';
    exit();
}

$verbose_level = $requests['configuration']['verbose_level'];

// Loop through URLs :-)
echo $pointer." Found ".$colors->getColoredString( count($requests['urls']), 'yellow')." requests!\n\n";



// Requests counter
$s = 1;

// Placeholder for values extracted by regexp
$all_values = [];

$tests_run = 0;
$tests_ok = 0;

foreach ($requests['urls'] as $request) {
    $new_values = [];

    // Apply regexp substitution on url, body and headers with previous values
    $request['url'] = replace_values($request['url'], $all_values);
    $request['body'] = replace_values($request['body'], $all_values);

    $headers = [];

    if( !empty($request['headers']) ){
        foreach( $request['headers'] as $name => $values ){
            $headers[$name] = replace_values($values, $all_values);
        }
    }

    // Preview of the request
    echo $colors->getColoredString( '['.$s.'/'.count($requests['urls']).'] '.$request['title'], 'yellow')."\n";

    if( $verbose_level == 2 ){
        echo '
'.$colors->getColoredString('[>]', 'red').' '.$colors->getColoredString('METHOD         :', 'cyan').' '.$colors->getColoredString($request['method'], 'yellow').'
'.$colors->getColoredString('[>]', 'red').' '.$colors->getColoredString('URL            :', 'cyan').' '.$colors->getColoredString($request['url'], 'yellow').'
'.$colors->getColoredString('[>]', 'red').' '.$colors->getColoredString('HEADERS        :', 'cyan')."\n";
    }

    // Print all headers
    if( $verbose_level == 2 ){
        foreach( $headers as $name => $value ){
            echo '    '.$name.': '.$value."\n";
        }
    }

    // Print request body
    if( $verbose_level == 2 ){
        echo $colors->getColoredString('[>]', 'red').' '.$colors->getColoredString('BODY           :', 'cyan').' '.$colors->getColoredString($request['body'], 'yellow')."\n";
    }

    $guzzle_options = [
        'body' => $request['body'],
        'headers' => $headers,
    ];

    if( isset($request['extra_guzzle_options']) ){
        $guzzle_options = array_merge( $guzzle_options, $request['extra_guzzle_options'][0] );
    }

    // Execute the request!
    $client = new \GuzzleHttp\Client();
    $response = $client->request(
        $request['method'],
        replace_values($request['url'], $all_values),
        $guzzle_options
    );

    // Get values from regexp on headers
    foreach ($response->getHeaders() as $name => $values) {
        $header = $name.': '.implode('; ', $values);

        if( !empty($request['header-regexp']) ){
            foreach( $request['header-regexp'] as $idx => $values ){
                foreach( $values as $title => $regexp){
                    if (preg_match($regexp, $header, $m)) {
                        $new_values[$title] = $m[1];
                    }
                }
            }
        }
    }

    if( $verbose_level == 3 ){
        echo $response->getBody();
    }

    // Get values from regexp on body
    if( !empty($request['body-regexp']) ){
        foreach( $request['body-regexp'] as $idx => $values ){
            foreach( $values as $title => $regexp){
                if (preg_match($regexp, $response->getBody(), $m)) {
                    $new_values[$title] = $m[1];
                }
            }
        }
    }

    // Prints out found values with regexp!
    if( $verbose_level == 2 ){
        if( !empty($request['header-regexp']) || !empty($request['body-regexp']) ){
            foreach( $new_values as $name => $value ){
                echo $colors->getColoredString('[<]', 'green').' '.$name.': '.$colors->getColoredString($value, 'yellow')."\n";
            }
        }
    }

    // Merge all values found
    if( !empty($new_values) ){
        $all_values = array_merge( $all_values, $new_values );
    }

    // If there is an expected value, check if it corresponds
    if( !empty($request['header-expected']) ){
        foreach( $request['header-expected'] as $expected_name => $expected_value ){
            $tests_run++;

            if( isset($new_values[$expected_name]) ){
                if( $expected_value != $new_values[$expected_name] ){
                    echo $colors->getColoredString('[X]', 'red').' '.$expected_name.': '.$colors->getColoredString($new_values[$expected_name], 'red')." (should be ".$colors->getColoredString($expected_value, 'yellow').")\n";
                } else {
                    $tests_ok++;
                    echo $colors->getColoredString('[V]', 'green').' '.$expected_name.': '.$colors->getColoredString($new_values[$expected_name], 'green')."\n";
                }
            } else {
                echo $colors->getColoredString('[X]', 'red').' '.$expected_name.': '.$colors->getColoredString('<not found>', 'red')." (should be ".$colors->getColoredString($expected_value, 'yellow').")\n";
            }
        }
    }

    if( !empty($request['body-expected']) ){
        foreach( $request['body-expected'] as $expected_name => $expected_value ){
            $tests_run++;

            if( isset($new_values[$expected_name]) ){
                if( $expected_value != $new_values[$expected_name] ){
                    echo $colors->getColoredString('[X]', 'red').' '.$expected_name.': '.$colors->getColoredString($new_values[$expected_name], 'red')." (should be ".$colors->getColoredString($expected_value, 'yellow').")\n";
                } else {
                    $tests_ok++;
                    echo $colors->getColoredString('[V]', 'green').' '.$expected_name.': '.$colors->getColoredString($new_values[$expected_name], 'green')."\n";
                }
            } else {
                echo $colors->getColoredString('[X]', 'red').' '.$expected_name.': '.$colors->getColoredString('<not found>', 'red')." (should be ".$colors->getColoredString($expected_value, 'yellow').")\n";
            }
        }
    }

    $s++;

    echo "\n";
}

// Prints the result of the tests
if( $tests_run > 0 ){
    echo "Tests passed:\n";

    if( $tests_ok == $tests_run ){
        echo $colors->getColoredString('[V] ', 'green');
    } else {
        echo $colors->getColoredString('[X] ', 'red');
    }

    echo $tests_ok.'/'.$tests_run."\n";
}



function usage(){
    global $colors;

    echo '
#################
# PHP AUTOMATOR #
# by loviuz     #
#################

A PHP script that automates a chain of HTTP requests to test '.$colors->getColoredString('web logic vulnerabilities', 'green').'.

Usage: request.php <path of json configuration file> [DEBUG]
    ';
}


/**
 * Change every occurrence in $text with $regexp
 * Example:
 * $text = "Hello §nome§ World §cognome§
 * $regexp["nome"] = "Mario";
 * $regexp["cognome"] = "Rossi";
 * 
 * Result: "Hello Mario World Rossi
 */
function replace_values( $string, $values ){
    if( empty($values) ){
        return $string;
    }

    if( preg_match_all('/§(.+?)§/', $string, $matches) ){
        foreach( $matches[1] as $i => $match ){
            foreach( $values as $name => $value ){
                if( $match == $name ){
                    $string = preg_replace('/§'.preg_quote($match).'§/', $value, $string);
                }
            }
        }
    }

    return $string;
}