<?php
/**
 * User: Baudev
 * Github: https://github.com/baudev
 * Date: 17/06/2018
 */

namespace FCMStream\exceptions;


use Exception;

class FCMConnectionException extends Exception
{

    public CONST CONNECTION_FAILED = 0;
    public CONST CONNECTION_NO_MORE_ALIVE = 1;
    public CONST CONNECTION_MAX_RETRY_ATTEMPTS_EXCEEDED = 2;


}