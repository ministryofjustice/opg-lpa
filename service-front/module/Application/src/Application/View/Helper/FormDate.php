<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FormDate extends AbstractHelper
{
    public function __invoke($name, $value = null, $attribs = null)
    {
    	$dateValue = explode('-', $value);

    	if(isset($attribs['dayAttribs'])) {
    		$dayAttribs = $attribs['dayAttribs'];
    		$dayAttribs['size'] = 2;
    		$dayAttribs['maxlength'] = 2;
    		unset($attribs['dayAttribs']);
    	}
        else {
        	$dayAttribs = array();
        }

    	if(isset($attribs['monthAttribs'])) {
    		$monthAttribs = $attribs['monthAttribs'];
    		$monthAttribs['size'] = 2;
    		$monthAttribs['maxlength'] = 2;
    		unset($attribs['monthAttribs']);
    	}
        else {
        	$monthAttribs = array();
        }

    	if(isset($attribs['yearAttribs'])) {
    		$yearAttribs = $attribs['yearAttribs'];
    		$yearAttribs['size'] = 4;
    		$yearAttribs['maxlength'] = 4;
    		unset($attribs['yearAttribs']);
    	}
        else {
        	$yearAttribs = array();
        }

        if(isset($attribs['id'])) {
	        unset($attribs['id']);
        }

    	return	"<div class=\"form-group form-group-day\"><label for=\"dob-day\">Day</label>" . $this->view->formText($name . '[day]',  isset($dateValue[2])?$dateValue[2]:'', $attribs+$dayAttribs) . "</div>".
				"<div class=\"form-group form-group-month\"><label for=\"dob-month\">Month</label>".$this->view->formText($name . '[month]', isset($dateValue[1])?$dateValue[1]:'', $attribs+$monthAttribs)."</div>".
				"<div class=\"form-group form-group-year\"><label for=\"dob-year\">Year</label>". $this->view->formText($name . '[year]', isset($dateValue[0])?$dateValue[0]:'', $attribs+$yearAttribs). "</div>";
    }
}