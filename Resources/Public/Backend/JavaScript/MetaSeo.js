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
    inList: function(list, item) {
        return new RegExp(' ' + item + ' ').test(' ' + list + ' ');
    },

    /**
     * Highlight text in grid
     *
     * @copyright	Stefan Gehrig (TEQneers GmbH & Co. KG) <gehrig@teqneers.de>
     */
    highlightText: function(node, search, cls) {
        search		= search.toUpperCase();
        var skip	= 0;
        if (node.nodeType == 3) {
            var pos = node.data.toUpperCase().indexOf(search);
            if (pos >= 0) {
                var spannode		= document.createElement('span');
                spannode.className	= cls || 'metaseo-search-highlight';
                var middlebit		= node.splitText(pos);
                var endbit			= middlebit.splitText(search.length);
                var middleclone		= middlebit.cloneNode(true);
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
     * @copyright	Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de>
     */
    highlightTextExists: function(value, search) {
        search		= search.toUpperCase();
        var skip	= 0;

        var pos = value.toUpperCase().indexOf(search);
        if (pos >= 0) {
            return true;
        }

        return false;
    }
}