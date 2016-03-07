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

namespace Metaseo\Metaseo\Frontend\Controller;

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Fakes default scheme (protocol, e.g. http/https) in page properties when the
 * TypoScriptFrontendController's determineId() function is executed by Ajax requests from the backend
 *
 * This class can be used instead of TYPO3's TypoScriptFrontendController and can be put in place via XCLASSes
 * declaration in the extension's ext_localconf.php (e.g. for backend-only request with some request header):
 *
 * if (TYPO3_MODE == 'BE' && isset($_SERVER['HTTP_X_TX_MYEXT_AJAX'])) {
 *     $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']
 *         ['TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController'] = array(
 *             'className' => 'Metaseo\\Metaseo\\Frontend\\Controller\\BackendCompliantTsfeController'
 *         );
 * }
 *
 * Usage: no code change required, but you can toggle between original behaviour and using this workaround (=default):
 *
 * setIsAjaxCall(false) disabled: Gives you the original behaviour of the TypoScriptFrontendController
 * setIsAjaxCall(true)  enabled: Fakes the default scheme in a page's page properties during execution of
 *                      the fetch_the_id() function which is called from the determineId() function.
 *                      Thereby it suppresses redirect via http headers for the scheme from page properties
 *                      to ensure that TYPO3 does not redirect the Ajax request to some other scheme.
 *                      This is intentionally useful to avoid mixed content for Ajax requests and to
 *                      prevent browsers to block these requests client-side. Plus, does it not expose
 *                      Ajax tokens in the URI via non-encrypted http (non-blocking older browsers).
 */

class BackendCompliantTsfeController extends TypoScriptFrontendController
{
    /**
     * @var boolean
     */
    protected $isAjaxCall = true;

    /**
     * @var boolean
     */
    protected $isFetchingId = false;

    /**
     * @var int
     */
    protected $originalScheme;

    /**
     * {@inheritDoc}
     */
    public function fetch_the_id()
    {
        $this->isFetchingId = true;

        parent::fetch_the_id(); //void function

        if ($this->isAjaxCall === true
            && $this->isPageSchemeFetched() === true
            && isset($this->originalScheme) === true
        ) {
            // restore original scheme
            $this->page['url_scheme'] = $this->originalScheme;
        }
        $this->isFetchingId = false;
    }


    /**
     * {@inheritDoc}
     */
    public function getPageAndRootlineWithDomain($domainStartPage)
    {
        parent::getPageAndRootlineWithDomain($domainStartPage); // void function

        // remember original scheme
        if ($this->isAjaxCall === true
            && $this->isFetchingId === true
            && $this->isPageSchemeFetched() === true
        ) {
            $this->originalScheme = $this->page['url_scheme'];
            // fake scheme with default scheme (0 = same scheme as request -> does not send http headers for redirect)
            $this->page['url_scheme'] = 0;
        }
    }

    /**
     * @return bool true if the page's scheme is available
     */
    protected function isPageSchemeFetched()
    {
        return is_array($this->page) && array_key_exists('url_scheme', $this->page);
    }

    /**
     * @param boolean $isAjaxCall toggle behaviour:
     *                            true if http redirect is to be suppressed,
     *                            false for TSFEController's original behaviour
     *
     * @return $this
     */
    public function setIsAjaxCall($isAjaxCall)
    {
        $this->isAjaxCall = $isAjaxCall;

        return $this;
    }
}
