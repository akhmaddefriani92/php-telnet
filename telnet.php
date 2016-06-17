#!/usr/bin/env php

<?php
/**
 * Command line telnet client in PHP with support for encrypted connections
 * 
 * @author gabe@fijiwebdesign.com
 * @link http://github.com/fijiwebdesign/php-telnet
 * @license MIT
 * 
 */
 
// main function
function telnet() {
    global $argv, $argc;
     
    if ($argc < 3) :
        die(usage());
    endif;
     
    // auguments
    $host = $argv[1];
    $port = (int) $argv[2];
    $protocol = isset($argv[3]) ? $argv[3] : 'tcp';
    $execute = isset($argv[4]) ? $argv[4] : false;
    
    // connecto to host
    if (!$s = @fsockopen($protocol . '://' . $host, $port, $errno, $errstr)) {
        die('Unable to connect to ' . $protocol . '://' . $host . ':' . $port . ' with error: ' . $errstr);
    }

    // 
    if ($info = stream_get_meta_data($s) && isset($info['timeout']) && $info['timeout']) {
        die('Unable to connect: Connection timed out...');
    }
     
    echo 'Connected to host: ' . $protocol . '://' . $host . ':' . $port . PHP_EOL;

    stream_set_blocking($s, 0);
     
    while (true) {

        $read_sockets = array($s, STDIN);
        $write_sockets = null;
        $error_sockets = null;
         
        // Get the list sockets which are readable
        if (stream_select($read_sockets, $write_sockets, $error_sockets, null) !== false) {
        
            foreach ($read_sockets as $sock) {

                // incoming message from remote server
                if ($sock == $s) {
                    if (!$data = stream_get_contents($s)) {
                        die(PHP_EOL . 'Connection closed');
                     } else {
                        fputs(STDOUT, $data); // print data
                    }
                }
                // user entered a message
                else {
                    $msg = fgets($sock);
                    if ($execute) {
                        $msg = execute($msg, $execute);
                    }
                    fputs($s, $msg);
                }
            }
        } else {
            die('Error reading from socket');
        }
    }
}

// usage text
function usage() {
    global $argv;

    return '
    Usage : 
        php ' . $argv[0] . ' hostname port [protocol] [execute]
                hostname    - Host domain or IP to connect to
                port        - Port number to connect to
                protocol    - Optional protocol to use. Options: ' . implode(', ', stream_get_transports()) . '. Default is tcp.
                execute     - Execute input using PHP interpreter or Shell. Options: php, shell

    Example: 
        Telnet to Gmail imap server
                php ' . $argv[0] . ' imap.gmail.com 993 ssl

        Telnet to localhost http server and execute input with php interpreter
                php ' . $argv[0] . ' localhost 80

            ' . PHP_EOL;
}

function execute($msg, $exec) {
    if (strtolower($exec) == 'php') {
        ob_start();
        eval($msg);
        return ob_get_clean();
    }
    if (strtolower($exec) == 'shell') {
        return shell_exec($msg);
    }
}

telnet();
