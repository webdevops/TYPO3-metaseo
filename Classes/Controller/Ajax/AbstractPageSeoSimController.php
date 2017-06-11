<?php

/*
 *  Copyright notice
 *
 *  (c) 2014 - 2017 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
 *  (c) 2007 - 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de> (tq_seo)
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

namespace Metaseo\Metaseo\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractPageSeoSimController extends AbstractPageSeoController implements PageSeoSimulateInterface
{
    /**
     * @inheritDoc
     */
    public function simulateAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $this->init();
            $this->fetchParams($request);
            $response->getBody()->write(\GuzzleHttp\json_encode($this->executeSimulate($this->getLanguage())));
        } catch (\Exception $exception) {
            return $this->ajaxExceptionHandler($exception, $response);
        }

        return $response;
    }

    /**
     * @param   integer $sysLanguage System language
     *
     * @return array
     *
     * @throws \Metaseo\Metaseo\Exception\Ajax\AjaxException
     */
    abstract protected function executeSimulate($sysLanguage);

    /**
     * @return int language from Ajax request parameters
     */
    protected function getLanguage()
    {
        $sysLanguage = (int)$this->postVar['sysLanguage'];

        // Store last selected language
        $this->getBackendUserAuthentication()
            ->setAndSaveSessionData('MetaSEO.sysLanguage', $sysLanguage);

        return $sysLanguage;
    }
}
