<?php

// ############################################################################
// REGISTER AJAX CONTROLLERS
// ############################################################################

return array_merge(
    \Metaseo\Metaseo\Utility\ExtensionManagementUtility::registerAjaxRoutes(
        \Metaseo\Metaseo\Controller\Ajax\AbstractPageSeoController::getBackendAjaxClassNames()
    ),
    \Metaseo\Metaseo\Utility\ExtensionManagementUtility::registerAjaxRoutes(
        \Metaseo\Metaseo\Controller\Ajax\SitemapController::getBackendAjaxClassNames()
    )
);
