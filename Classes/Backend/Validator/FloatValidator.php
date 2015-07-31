<?php

/*
 *  Copyright notice
 *
 *  (c) 2015 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
 *  (c) 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de> (tq_seo)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

namespace Metaseo\Metaseo\Backend\Validator;

/**
 * TYPO3 Backend field validation: float
 */
class FloatValidator
{

    /**
     * Returns JavaScript validation function body
     *
     * @return string
     */
    public function returnFieldJS()
    {
        return '
value = value.replace(/[^-0-9,.]/g,\'\');

var ret = 0;
try {
    if (isNaN(value) ) {
        value = 0;
    }

    ret = parseFloat(value);
} catch(e) {}

if (isNaN(ret) ) {
    ret = 0;
}

return ret;
';
    }

    /**
     * Validate number on serverside
     *
     * @param    string $value Value
     * @param    mixed  $is_in Is in value (config)
     * @param    mixed  $set   Set
     *
     * @return    float
     */
    public function evaluateFieldValue($value, $is_in, &$set)
    {
        return (float)$value;
    }
}
