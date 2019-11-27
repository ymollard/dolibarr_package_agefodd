<?php


require_once (__DIR__ . "/agefodd_formateur.class.php");

/**
 * formateur Class for Agenda dolGetElementUrl compatibility
 */
class formateur extends Agefodd_teacher
{
    function getNomUrl($label = 'name'){ // force this to be compatible with parent but dolGetElementUrl use : dolGetElementUrl($withpicto,$option)
        return parent::getNomUrl();
    }
}