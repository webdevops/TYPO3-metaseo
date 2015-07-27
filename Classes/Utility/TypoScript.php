<?php

/*
 *  Copyright notice
 *
 *  (c) 2015 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
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

namespace Metaseo\Metaseo\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

class TypoScript implements \Iterator
{
    ###########################################################################
    ## Attributes
    ###########################################################################

    /**
     * TYPO3 TypoScript Data
     *
     * @var array
     */
    protected $tsData;

    /**
     * TYPO3 TypoScript Data Type
     *
     * @var string
     */
    protected $tsType;

    /**
     * Iterator position
     *
     * @var mixed
     */
    protected $iteratorPosition = false;

    /**
     * cObj
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $cObj;

    ###########################################################################
    ## Constructor
    ###########################################################################

    /**
     * Constructor
     *
     * @param NULL|array  $conf TypoScript node configuration
     * @param NULL|string $type TypoScript node type
     */
    public function __construct($conf = null, $type = null)
    {
        if ($conf !== null) {
            $this->tsData = $conf;
        }

        if ($type !== null) {
            $this->tsType = $type;
        }
    }

    ###########################################################################
    ## Iterator methods
    ###########################################################################

    /**
     * Rewind iterator position
     */
    public function rewind()
    {
        reset($this->tsData);
        $this->iteratorNextNode();
    }

    /**
     * Check if current node is a valid node
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->iteratorPosition && array_key_exists($this->iteratorPosition, $this->tsData);
    }

    /**
     * Return current iterator key
     *
     * @return      string  TypoScript path-node-key
     */
    public function key()
    {
        return substr($this->iteratorPosition, 0, -1);
    }

    /**
     * Return current iterator node
     *
     * @return TypoScript
     */
    public function current()
    {
        $nodePath = substr($this->iteratorPosition, 0, -1);

        return $this->getNode($nodePath);
    }

    /**
     * Get TypoScript subnode
     *
     * @param       string $tsNodePath TypoScript node-path
     *
     * @return      TypoScript       TypoScript subnode-object
     */
    public function getNode($tsNodePath)
    {
        $ret = null;

        // extract TypoScript-path information
        $nodeSections  = explode('.', $tsNodePath);
        $nodeValueType = end($nodeSections);
        $nodeValueName = end($nodeSections) . '.';

        // remove last node from sections because we already got the node name
        unset($nodeSections[key($nodeSections)]);

        // walk though array to find node
        $nodeData = $this->tsData;
        if (!empty($nodeSections) && is_array($nodeSections)) {
            foreach ($nodeSections as $sectionName) {
                $sectionName .= '.';

                if (is_array($nodeData) && array_key_exists($sectionName, $nodeData)) {
                    $nodeData = $nodeData[$sectionName];
                } else {
                    break;
                }
            }
        }

        // Fetch TypoScript configuration data
        $tsData = array();
        if (is_array($nodeData) && array_key_exists($nodeValueName, $nodeData)) {
            $tsData = $nodeData[$nodeValueName];
        }

        // Fetch TypoScript configuration type
        $tsType = null;
        if (is_array($nodeData) && array_key_exists($nodeValueType, $nodeData)) {
            $tsType = $nodeData[$nodeValueType];
        }

        // Clone object and set values
        $ret                   = clone $this;
        $ret->tsData           = $tsData;
        $ret->tsType           = $tsType;
        $ret->iteratorPosition = false;

        return $ret;
    }

    /**
     * Next iterator node
     */
    public function next()
    {
        next($this->tsData);
        $this->iteratorNextNode();
    }

    ###########################################################################
    ## Public methods
    ###########################################################################

    /**
     * Search next iterator node
     */
    public function iteratorNextNode()
    {
        // INIT
        $iteratorPosition       = null;
        $this->iteratorPosition = false;
        $nextNode               = false;

        do {
            if ($nextNode) {
                next($this->tsData);
            }

            $currentNode = current($this->tsData);

            if ($currentNode !== false) {
                // get key
                $iteratorPosition = key($this->tsData);

                // check if node is subnode or value
                if (substr($iteratorPosition, -1) == '.') {
                    // next subnode fond
                    $this->iteratorPosition = $iteratorPosition;
                    break;
                }
            } else {
                $iteratorPosition       = false;
                $this->iteratorPosition = false;
            }

            $nextNode = true;
        } while ($iteratorPosition !== false);
    }

    /**
     * Get value from node
     *
     * @param       string $tsNodePath   TypoScript node-path
     * @param       mixed  $defaultValue Default value
     *
     * @return      mixed                                   Node value (or default value)
     */
    public function getValue($tsNodePath, $defaultValue = null)
    {
        $ret = $defaultValue;

        // extract TypoScript-path information
        $nodeFound     = true;
        $nodeSections  = explode('.', $tsNodePath);
        $nodeValueName = end($nodeSections);

        // remove last node from sections because we already got the node name
        unset($nodeSections[key($nodeSections)]);

        // walk though array to find node
        $nodeData = $this->tsData;
        if (!empty($nodeSections) && is_array($nodeSections)) {
            foreach ($nodeSections as $sectionName) {
                $sectionName .= '.';

                if (is_array($nodeData) && array_key_exists($sectionName, $nodeData)) {
                    $nodeData = $nodeData[$sectionName];
                } else {
                    $nodeFound = false;
                    break;
                }
            }
        }

        // check if we got the value of the node
        if ($nodeFound && is_array($nodeData) && array_key_exists($nodeValueName, $nodeData)) {
            $ret = $nodeData[$nodeValueName];
        }


        return $ret;
    }

    /**
     * Convert TypoScript to original array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->tsData;
    }

    /**
     * Check if TypoScript is empty/not set
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->tsData);
    }


    /**
     * Render TypoScript Config
     *
     * @return      mixed           Result of cObj
     */
    public function render()
    {
        return $this->getCObj()->cObjGetSingle($this->tsType, $this->tsData);
    }

    /**
     * StdWrap with TypoScript Configuration
     *
     * @param  mixed   $value    Value for stdWrap
     *
     * @return mixed             Result of stdWrap
     */
    public function stdWrap($value = null)
    {
        return $this->getCObj()->stdWrap($value, $this->tsData);
    }

    ###########################################################################
    ## Protected methods
    ###########################################################################

    /**
     * Return instance of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     *
     * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected function getCObj()
    {
        if ($this->cObj === null) {
            $this->cObj = Typo3GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'
            );
        }

        return $this->cObj;
    }

    ###########################################################################
    ## Private methods
    ###########################################################################

    ###########################################################################
    ## Accessors
    ###########################################################################

    public function setTypoScript(Array $conf)
    {
        $this->tsData = $conf;

        return $this;
    }

    public function getTypoScriptType()
    {
        return $this->tsType;
    }

    public function setTypoScriptType($value)
    {
        $this->tsType = (string)$value;

        return $this;
    }
}
