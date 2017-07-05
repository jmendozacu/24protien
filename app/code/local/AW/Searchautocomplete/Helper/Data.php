<?php

class AW_Searchautocomplete_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_searchQueries = array();

    private function isAdvancedSearchInstalled()
    {
        $modules = (array)Mage::getConfig()->getNode('modules')->children();
        return array_key_exists('AW_Advancedsearch', $modules)
            && 'true' == (string)$modules['AW_Advancedsearch']->active;
    }

    public function canUseADVSearch()
    {
        if(!$this->isAdvancedSearchInstalled()) return false;
        return (bool) Mage::helper('searchautocomplete/config')->getInterfaceUseAdvancedSearch() && Mage::helper('awadvancedsearch')->isEnabled();
    }

    public function isFulltext($attributeId)
    {
        $attribute = Mage::getModel('eav/entity_attribute')->load($attributeId);
        if (($attribute->getData('is_searchable') == 1) && ($attribute->getData('frontend_input') == 'textarea')) {
            return true;
        }
        return false;
    }

    public function getUsedAttributes()
    {
        $usedAttributes = array();
        $itemPattern = Mage::helper('searchautocomplete/config')->getInterfaceItemTemplate();
        $pattern = '/{([^}]*)}/si';
        preg_match_all($pattern, $itemPattern, $match);

        $attributeModel = Mage::getSingleton('searchautocomplete/source_product_attribute');
        $attributesArray = $attributeModel->toArray();
        foreach($match[1] as $attributeCode) {
            if (array_key_exists($attributeCode, $attributesArray)) {
                $usedAttributes[] = $attributeCode;
            }
        }
        return $usedAttributes;
    }

    public function getSearchedQuery()
    {
        $searchQuery =  Mage::app()->getRequest()->getParam('q');
        if (is_null($searchQuery)) {
            $searchQuery = '';
        }
        return htmlspecialchars_decode(Mage::helper('core')->escapeHtml($searchQuery));
    }

    public function getSearchedWords()
    {
        $searchedQuery = $this->getSearchedQuery();
        $searchedWords = explode(' ', trim($searchedQuery));
        $searchedSynonymsFor = array();
        for ($i = 0; $i < count($searchedWords); $i++) {
            if (strlen($searchedWords[$i]) < 2 || preg_match('(:)', $searchedWords[$i])) {
                unset($searchedWords[$i]);
            } else {
                if ($synonym = $this->getSynonymFor($searchedWords[$i])) {
                    $searchedSynonymsFor[] = $synonym;
                }
            }
        }
        return array_merge($searchedWords, $searchedSynonymsFor);
    }

    public function getSynonymFor($queryText)
    {
        if (array_key_exists($queryText, $this->_searchQueries)) {
            $query = $this->_searchQueries[$queryText];
        } else {
            $query = Mage::getModel('catalogsearch/query')->loadByQueryText($queryText);
            $this->_searchQueries[$queryText] = $query;
        }
        return ($query->getId() && $query->getSynonymFor()) ? $query->getSynonymFor() : false;
    }

    public function getEntityTypeId()
    {
        return Mage::getSingleton('searchautocomplete/source_product_attribute')->getEntityTypeId();
    }
}
