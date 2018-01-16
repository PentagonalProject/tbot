<?php
/**
 * MIT License
 *
 * Copyright (c) 2018 Block Tech Indonesia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Pentagonal\TBot\Base;

/**
 * Class Constant
 * @package Pentagonal\TBot\Base
 */
class Constant
{
    const BASE_ROUND = 4;
    const DEFAULT_FORMAT_ROUND = 10;

    const DATE_FORMAT = 'Y-m-d H:i:s e';
    const MODULE_CORE = 'module.core';
    const MODULE_API  = 'module.api';

    const NAME_INFO_KEY              = 'info';
    const NAME_NATIVE_KEY            = 'native';

    const NAME_CONVERSION_CODE_KEY   = 'conversion_code';
    const NAME_SUMMARY_KEY           = 'summary';
    const NAME_CACHED_KEY            = 'cache';
    const NAME_CACHED_EXPIRED_KEY    = 'cache_expired';

    const NAME_RESULT_KEY            = 'result';
    const NAME_DATA_KEY              = 'data';
    const NAME_STATUS_KEY            = 'status';
    const NAME_ERROR_KEY             = 'error';
    const NAME_MESSAGE_KEY           = 'message';
    const NAME_ENDPOINT_KEY          = 'endpoints';
    const NAME_FORMER_NAME_KEY       = 'former_name';
    const NAME_OFFICIAL_URL_KEY      = 'official_url';
    const NAME_BASE_URL_KEY          = 'base_url';
    const NAME_URL_KEY               = 'url';

    const NAME_BALANCE_KEY           = 'balance';
    const NAME_RATE_KEY              = 'rate';
    const NAME_RATES_KEY             = 'rates';
    const NAME_SOURCE_KEY            = 'source';

    const NAME_ADDRESS_KEY           = 'address';
    const NAME_CURRENCY_KEY          = 'currency';
    const NAME_CURRENCIES_KEY        = 'currencies';
    const NAME_CURRENCY_NAME_KEY     = 'currency_name';
    const NAME_CURRENCY_CODE_KEY     = 'currency_code';
    const NAME_CURRENCY_SYMBOL_KEY   = 'currency_symbol';
    const NAME_BASE_DENOMINATION_CODE = 'denomination_code';
    const NAME_DENOMINATION_KEY       = 'denomination';

    const NAME_VOLUME_KEY           = 'volume';
    const NAME_HIGH_KEY             = 'high';
    const NAME_LOW_KEY              = 'low';
    const NAME_LAST_KEY             = 'last';
    const NAME_SELL_KEY             = 'buy';
    const NAME_BUY_KEY              = 'sell';
}
