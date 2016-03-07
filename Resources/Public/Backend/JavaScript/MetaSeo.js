/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
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
 ***************************************************************/
Ext.ns('MetaSeo');

MetaSeo = {

    /**
     * Check if entry is in list
     */
    inList: function (list, item) {
        return new RegExp(' ' + item + ' ').test(' ' + list + ' ');
    },

    /**
     * Highlight text in grid
     *
     * @copyright    Stefan Gehrig (TEQneers GmbH & Co. KG) <gehrig@teqneers.de>
     */
    highlightText: function (node, search, cls) {
        search = search.toUpperCase();
        var skip = 0;
        if (node.nodeType == 3) {
            var pos = node.data.toUpperCase().indexOf(search);
            if (pos >= 0) {
                var spannode = document.createElement('span');
                spannode.className = cls || 'metaseo-search-highlight';
                var middlebit = node.splitText(pos);
                var middleclone = middlebit.cloneNode(true);
                spannode.appendChild(middleclone);
                middlebit.parentNode.replaceChild(spannode, middlebit);
                skip = 1;
            }
        } else if (node.nodeType == 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
            for (var i = 0; i < node.childNodes.length; ++i) {
                i += MetaSeo.highlightText(node.childNodes[i], search);
            }
        }
        return skip;
    },

    /**
     * Check if highlight text is available
     *
     * @copyright    Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de>
     */
    highlightTextExists: function (value, search) {
        var pos;
        search = search.toUpperCase();
        pos = value.toUpperCase().indexOf(search);
        return pos >= 0;
    },

    /**
     * Severities for compatibility layer (compatible to old constants)
     */
    Severity: {
        notice: -2,
        info: -1,
        success: 0,
        warning: 1,
        error: 2
    },

    /**
     * Compatibility layer for Deprecation #62893 (7.0) and #66047 (7.2)
     * To be removed when support for versions older than 7 LTS is discontinued.
     *
     * This compatibility layer goes for the latest and greatest revision
     * and contains support for older infrastructure.
     *
     * @copyright    Thomas Mayer (2bis10 IT-Services UG (haftungsbeschraenkt)) <thomas.mayer@2bis10.de>
     */
    flashMessage: function(severity, title, message) {
        var duration = 3;
        var sev;
        if ((typeof top) === 'object') {
            if ('TYPO3' in top) {
                if ('Notification' in top.TYPO3) {
                    switch (severity) {
                        case this.Severity.notice:
                            top.TYPO3.Notification.notice(title, message, duration);
                            break;
                        case this.Severity.success:
                            top.TYPO3.Notification.success(title, message, duration);
                            break;
                        case this.Severity.warning:
                            top.TYPO3.Notification.warning(title, message, duration);
                            break;
                        case this.Severity.error:
                            top.TYPO3.Notification.error(title, message, duration);
                            break;
                        case this.Severity.info:
                        default:
                            top.TYPO3.Notification.info(title, message, duration);
                    }
                    return;
                }
                if ('Flashmessage' in top.TYPO3) {
                    switch (severity) {
                        case this.Severity.notice:
                            sev = top.TYPO3.Severity.notice;
                            break;
                        case this.Severity.success:
                            if ('success' in top.TYPO3.Severity) {
                                sev = top.TYPO3.Severity.success;
                            } else {
                                sev = top.TYPO3.Severity.ok;
                            }
                            break;
                        case this.Severity.warning:
                            sev = top.TYPO3.Severity.warning;
                            break;
                        case this.Severity.error:
                            sev = top.TYPO3.Severity.error;
                            break;
                        case this.Severity.info:
                        default:
                            if ('info' in TYPO3.Severity) {
                                sev = top.TYPO3.Severity.info;
                            } else {
                                sev = top.TYPO3.Severity.information;
                            }
                    }
                    top.TYPO3.Flashmessage.display(sev, title, message, duration);
                    return;
                }
            }
        }
        switch (severity) {
            case this.Severity.notice:
                sev = TYPO3.Severity.notice;
                break;
            case this.Severity.success:
                if ('success' in TYPO3.Severity) {
                    sev = TYPO3.Severity.success;
                } else {
                    sev = TYPO3.Severity.ok;
                }
                break;
            case this.Severity.warning:
                sev = TYPO3.Severity.warning;
                break;
            case this.Severity.error:
                sev = TYPO3.Severity.error;
                break;
            case this.Severity.info:
            default:
                if ('info' in TYPO3.Severity) {
                    sev = TYPO3.Severity.info;
                } else {
                    sev = TYPO3.Severity.information;
                }
        }
        TYPO3.Flashmessage.display(sev, title, message, duration);
    }
};
