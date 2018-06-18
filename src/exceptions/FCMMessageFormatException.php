<?php
/**
 * User: Baudev
 * Github: https://github.com/baudev
 * Date: 18/06/2018
 */

namespace FCMStream\exceptions;

use Exception;

class FCMMessageFormatException extends Exception
{

    public CONST MESSAGE_FORMAT_CONDITION_OR_TO = 0;
    public CONST MESSAGE_FORMAT_PRIORITY_NEED_TO_BE_HIGH_OR_NORMAL = 1;
    public CONST MESSAGE_TIME_TO_LIVE_MUST_BE_BETWEEN_0_AND_4_WEEKS = 2;
    public CONST MESSAGE_ID_CANT_BE_NULL = 3;

}